<?php

/**
 * Load secrets (ENCRYPTION_KEY, HASH_PEPPER, DB_*, etc.) from api/config/.env
 * when they are not already set as real environment variables.
 * If ENCRYPTION_KEY or HASH_PEPPER are missing, they are generated once and
 * saved to that file so ID numbers can be stored securely. KEEP THAT FILE:
 * losing it makes already-encrypted ID numbers unreadable.
 */
(function () {
    $file = __DIR__ . '/../config/.env';

    if (is_readable($file)) {
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\"'");
            if ($k !== '' && $v !== '' && getenv($k) === false) {
                putenv("$k=$v");
                $_ENV[$k] = $v;
            }
        }
    }

    $generated = [];
    $key = getenv('ENCRYPTION_KEY');
    if (!$key || !preg_match('/^[0-9a-fA-F]{64}$/', $key)) {
        $generated['ENCRYPTION_KEY'] = bin2hex(random_bytes(32));
    }
    if (!getenv('HASH_PEPPER')) {
        $generated['HASH_PEPPER'] = bin2hex(random_bytes(32));
    }

    if ($generated) {
        $out = '';
        foreach ($generated as $k => $v) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
            $out .= "$k=$v\n";
        }
        $header = file_exists($file) ? '' : "# Auto-generated secrets. Back this file up and never commit it.\n";
        if (@file_put_contents($file, $header . $out, FILE_APPEND | LOCK_EX) === false) {
            error_log('Could not write ' . $file . ' - set ENCRYPTION_KEY and HASH_PEPPER manually.');
        } else {
            @chmod($file, 0600);
        }
    }
})();

$origin = getenv('ALLOWED_ORIGIN');
if (!$origin) {
    $origin = 'http://localhost:4200';
}
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Vary: Origin');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

set_exception_handler(function ($e) {
    error_log((string)$e);
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
});

function db()
{
    static $pdo = null;
    if ($pdo === null) {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $name = getenv('DB_NAME') ?: 'skills_link';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';

        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function q($sql, $params = [])
{
    $st = db()->prepare($sql);
    $i = 1;
    foreach (array_values($params) as $value) {
        if (is_int($value)) {
            $type = PDO::PARAM_INT;
        } elseif (is_bool($value)) {
            $type = PDO::PARAM_BOOL;
        } elseif (is_null($value)) {
            $type = PDO::PARAM_NULL;
        } else {
            $type = PDO::PARAM_STR;
        }
        $st->bindValue($i, $value, $type);
        $i++;
    }
    $st->execute();
    return $st;
}

function json_out($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail($msg, $code = 400)
{
    json_out(['error' => $msg], $code);
}

function body()
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (is_array($data)) {
        return $data;
    }
    return [];
}

function require_method(...$allowed)
{
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        fail('Method not allowed', 405);
    }
    return $method;
}

function bearer_token()
{
    $header = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            if (strtolower($name) === 'authorization') {
                $header = $value;
            }
        }
    }

    if (preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
        return $m[1];
    }
    return null;
}

function current_user()
{
    $token = bearer_token();
    if (!$token) {
        return null;
    }

    $user = q("SELECT u.id, u.email, u.role, u.phone, u.is_verified
               FROM auth_tokens t JOIN users u ON u.id = t.user_id
               WHERE t.token_hash = ? AND t.expires_at > NOW()
                 AND u.deleted_at IS NULL AND u.is_active = 1", [hash('sha256', $token)])->fetch();

    if (!$user) {
        return null;
    }
    return $user;
}

/**
 * $allowUnverified is only true for the email-verification endpoints themselves.
 * Everywhere else a signed-in user must have confirmed their email first.
 */
function require_auth($roles = [], $allowUnverified = false)
{
    $user = current_user();
    if (!$user) {
        fail('Not authenticated', 401);
    }
    if ($roles && !in_array($user['role'], $roles, true)) {
        fail('Forbidden', 403);
    }
    if (!$allowUnverified && !(int)$user['is_verified']) {
        json_out(['error' => 'Please verify your email address to continue.', 'code' => 'email_not_verified'], 403);
    }
    $user['id'] = (int)$user['id'];
    $user['is_verified'] = (bool)$user['is_verified'];
    return $user;
}

function page_params($defaultPer = 20, $maxPer = 50)
{
    $page = max(1, (int)(isset($_GET['page']) ? $_GET['page'] : 1));
    $per = (int)(isset($_GET['per_page']) ? $_GET['per_page'] : $defaultPer);
    $per = min($maxPer, max(1, $per));
    return [$page, $per, ($page - 1) * $per];
}

function pepper()
{
    $p = getenv('HASH_PEPPER');
    if (!$p) {
        throw new RuntimeException('HASH_PEPPER is not set');
    }
    return $p;
}

function user_ref($userId)
{
    return hash_hmac('sha256', (string)$userId, pepper());
}

function id_hash($idNumber)
{
    return hash_hmac('sha256', $idNumber, pepper());
}

function sl_encrypt($plain)
{
    $key = hex2bin(getenv('ENCRYPTION_KEY') ?: '');
    if ($key === false || strlen($key) !== 32) {
        throw new RuntimeException('ENCRYPTION_KEY must be 64 hex chars');
    }
    $iv = random_bytes(12);
    $tag = '';
    $encrypted = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($encrypted === false) {
        throw new RuntimeException('Encryption failed');
    }
    return $iv . $tag . $encrypted;
}

function log_consent($userId, $type, $given)
{
    $version = getenv('POLICY_VERSION') ?: '1.0';
    $ip = @inet_pton(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
    if (!$ip) {
        $ip = null;
    }
    q('INSERT INTO consent_log (user_id, user_ref, consent_type, consent_given, policy_version, ip_address)
       VALUES (?,?,?,?,?,?)',
      [$userId, user_ref($userId), $type, $given ? 1 : 0, $version, $ip]);
}

function valid_date($value)
{
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return $d && $d->format('Y-m-d') === $value;
}

function upload_dir()
{
    $dir = getenv('UPLOAD_DIR');
    if (!$dir) {
        // Default: api/uploads, blocked from direct web access. Set UPLOAD_DIR to a
        // folder outside the web root in production.
        $dir = __DIR__ . '/../uploads';
    }
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        error_log("Upload directory could not be created: $dir");
        fail('Upload directory is not writable', 500);
    }
    if (!is_writable($dir)) {
        error_log("Upload directory is not writable: $dir");
        fail('Upload directory is not writable', 500);
    }
    $htaccess = rtrim($dir, '/') . '/.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents($htaccess, "Require all denied\n");
    }
    return rtrim($dir, '/');
}
