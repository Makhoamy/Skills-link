<?php
/**
 * System backup helpers (pure PHP, so they work on shared hosting without mysqldump).
 *
 * A backup is a pair of files in the backup folder:
 *   skillslink_YYYYmmdd_HHMMSS_<label>.sql.gz   every table (structure + data)
 *   skillslink_YYYYmmdd_HHMMSS_<label>.files.zip uploaded CVs / photos / documents
 *
 * NOT included on purpose: api/config/.env. It holds ENCRYPTION_KEY and HASH_PEPPER.
 * Without that file, restored ID numbers cannot be decrypted, so store it separately.
 */

const BACKUP_HEADER = '-- SkillsLink backup v1';
const BACKUP_NAME_RE = '/^skillslink_\d{8}_\d{6}_(manual|auto|prerestore)\.sql\.gz$/';

function backup_dir()
{
    $dir = getenv('BACKUP_DIR');
    if (!$dir) {
        // Default: api/backups, blocked from the web. Point BACKUP_DIR outside the web root in production.
        $dir = __DIR__ . '/../backups';
    }
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        throw new RuntimeException('Backup folder could not be created');
    }
    if (!is_writable($dir)) {
        throw new RuntimeException('Backup folder is not writable');
    }
    $htaccess = rtrim($dir, '/') . '/.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents($htaccess, "Require all denied\n");
    }
    return rtrim($dir, '/');
}

function backup_is_valid_name($name)
{
    return is_string($name) && preg_match(BACKUP_NAME_RE, $name) === 1;
}

function backup_files_name($sqlName)
{
    return preg_replace('/\.sql\.gz$/', '.files.zip', $sqlName);
}

/** One SQL value. Everything is sent as a string (MySQL converts it); binary uses a hex literal. */
function backup_value(PDO $pdo, $v)
{
    if ($v === null) return 'NULL';
    $v = (string)$v;
    if ($v === '') return "''";
    if (preg_match('//u', $v) !== 1) return '0x' . bin2hex($v); // encrypted ID numbers
    return $pdo->quote($v);
}

/** Build the .sql text. Every statement sits on ONE line so restore can read it line by line. */
function backup_dump_sql()
{
    $pdo = db();
    $out = BACKUP_HEADER . "\n";
    $out .= '-- Created ' . date('c') . ' database ' . (getenv('DB_NAME') ?: 'skills_link') . "\n";
    $out .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";

    $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
    foreach ($tables as $t) {
        $table = $t[0];
        $id = '`' . str_replace('`', '``', $table) . '`';

        $create = $pdo->query("SHOW CREATE TABLE $id")->fetch(PDO::FETCH_NUM)[1];
        $create = preg_replace('/\s*\R\s*/', ' ', $create); // one line
        $out .= "DROP TABLE IF EXISTS $id;\n$create;\n";

        $stmt = $pdo->query("SELECT * FROM $id");
        $batch = [];
        $cols = null;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($cols === null) {
                $cols = implode(',', array_map(fn($c) => '`' . str_replace('`', '``', $c) . '`', array_keys($row)));
            }
            $batch[] = '(' . implode(',', array_map(fn($v) => backup_value($pdo, $v), $row)) . ')';
            if (count($batch) >= 200) {
                $out .= "INSERT INTO $id ($cols) VALUES " . implode(',', $batch) . ";\n";
                $batch = [];
            }
        }
        if ($batch) {
            $out .= "INSERT INTO $id ($cols) VALUES " . implode(',', $batch) . ";\n";
        }
    }
    $out .= "SET FOREIGN_KEY_CHECKS = 1;\n-- End of backup\n";
    return $out;
}

/** Zip everything in the uploads folder. Returns the zip path, or null if there is nothing / no ZipArchive. */
function backup_zip_uploads($zipPath)
{
    if (!class_exists('ZipArchive')) return null;
    $dir = upload_dir_quiet();
    if ($dir === null) return null;

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return null;
    $count = 0;
    foreach (scandir($dir) as $f) {
        $p = $dir . '/' . $f;
        if ($f === '.' || $f === '..' || $f === '.htaccess' || !is_file($p)) continue;
        $zip->addFile($p, $f);
        $count++;
    }
    $zip->close();
    if ($count === 0) { @unlink($zipPath); return null; }
    return $zipPath;
}

function upload_dir_quiet()
{
    $dir = getenv('UPLOAD_DIR') ?: (__DIR__ . '/../uploads');
    return is_dir($dir) ? rtrim($dir, '/') : null;
}

/**
 * Create a backup. $label is manual | auto | prerestore.
 * Returns ['name', 'size', 'created_at', 'label', 'has_files'].
 */
function backup_create($label = 'manual')
{
    if (!in_array($label, ['manual', 'auto', 'prerestore'], true)) $label = 'manual';
    $dir = backup_dir();
    $name = 'skillslink_' . date('Ymd_His') . '_' . $label . '.sql.gz';
    $path = $dir . '/' . $name;

    $gz = gzencode(backup_dump_sql(), 9);
    if ($gz === false || file_put_contents($path, $gz, LOCK_EX) === false) {
        throw new RuntimeException('Could not write the backup file');
    }
    @chmod($path, 0640);

    $hasFiles = backup_zip_uploads($dir . '/' . backup_files_name($name)) !== null;
    return backup_info($name) + ['has_files' => $hasFiles];
}

function backup_info($name)
{
    $path = backup_dir() . '/' . $name;
    preg_match(BACKUP_NAME_RE, $name, $m);
    return [
        'name' => $name,
        'label' => $m[1] ?? 'manual',
        'size' => (int)@filesize($path),
        'created_at' => date('Y-m-d H:i:s', (int)@filemtime($path)),
        'has_files' => file_exists(backup_dir() . '/' . backup_files_name($name)),
    ];
}

function backup_list()
{
    $dir = backup_dir();
    $items = [];
    foreach (scandir($dir) as $f) {
        if (backup_is_valid_name($f)) $items[] = backup_info($f);
    }
    usort($items, fn($a, $b) => strcmp($b['name'], $a['name'])); // newest first
    return $items;
}

function backup_delete($name)
{
    if (!backup_is_valid_name($name)) throw new InvalidArgumentException('Invalid backup name');
    $dir = backup_dir();
    $ok = false;
    foreach ([$name, backup_files_name($name)] as $f) {
        if (file_exists("$dir/$f")) $ok = @unlink("$dir/$f") || $ok;
    }
    return $ok;
}

/** Keep only the newest $keep automatic backups. */
function backup_prune_auto($keep)
{
    $auto = array_values(array_filter(backup_list(), fn($b) => $b['label'] === 'auto'));
    $removed = 0;
    foreach (array_slice($auto, max(1, $keep)) as $b) {
        if (backup_delete($b['name'])) $removed++;
    }
    return $removed;
}

/**
 * Restore database (and uploaded files, if the backup has them).
 * A safety backup of the current data is taken first.
 */
function backup_restore($name)
{
    if (!backup_is_valid_name($name)) throw new InvalidArgumentException('Invalid backup name');
    $dir = backup_dir();
    $path = "$dir/$name";
    if (!is_file($path)) throw new InvalidArgumentException('Backup not found');

    $sql = gzdecode((string)file_get_contents($path));
    if ($sql === false || strncmp($sql, BACKUP_HEADER, strlen(BACKUP_HEADER)) !== 0 || strpos($sql, '-- End of backup') === false) {
        throw new InvalidArgumentException('This file is not a complete Skills Link backup');
    }

    $safety = backup_create('prerestore');

    $pdo = db();
    $statements = 0;
    foreach (preg_split('/\R/', $sql) as $line) {
        if ($line === '' || strncmp($line, '--', 2) === 0) continue;
        $pdo->exec($line);
        $statements++;
    }

    // files
    $filesRestored = 0;
    $zipPath = "$dir/" . backup_files_name($name);
    if (is_file($zipPath) && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $target = upload_dir();
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = basename($zip->getNameIndex($i)); // basename blocks path tricks
                if ($entry === '' || $entry === '.htaccess') continue;
                $data = $zip->getFromIndex($i);
                if ($data !== false && file_put_contents("$target/$entry", $data) !== false) $filesRestored++;
            }
            $zip->close();
        }
    }

    return ['statements' => $statements, 'files_restored' => $filesRestored, 'safety_backup' => $safety['name']];
}
