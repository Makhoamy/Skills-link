<?php

function send_mail($to, $subject, $text)
{
    $cfg = require __DIR__ . '/../config/mail.php';

    // no mail settings yet, so save the email in a file instead
    if (empty($cfg['host'])) {
        save_email_to_file($to, $subject, $text);
        return true;
    }

    return send_with_smtp($cfg, $to, $subject, $text);
}

function mail_is_set_up()
{
    $cfg = require __DIR__ . '/../config/mail.php';
    return !empty($cfg['host']);
}

function save_email_to_file($to, $subject, $text)
{
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $entry = '[' . date('Y-m-d H:i:s') . "] To: $to\nSubject: $subject\n\n$text\n----------\n\n";
    file_put_contents($dir . '/emails.log', $entry, FILE_APPEND);
}

function read_reply($fp)
{
    $reply = '';
    while ($line = fgets($fp, 515)) {
        $reply .= $line;
        if (substr($line, 3, 1) == ' ') {
            break;
        }
    }
    return $reply;
}

function send_with_smtp($cfg, $to, $subject, $text)
{
    $fp = @fsockopen('ssl://' . $cfg['host'], $cfg['port'], $errno, $errstr, 15);
    if (!$fp) {
        error_log('Mail error: could not connect to ' . $cfg['host'] . ' - ' . $errstr);
        return false;
    }
    stream_set_timeout($fp, 15);

    $from = $cfg['from_email'];
    if (!$from) {
        $from = $cfg['username'];
    }

    $text = str_replace("\n", "\r\n", $text);
    $message = 'From: ' . $cfg['from_name'] . ' <' . $from . ">\r\n"
        . 'To: ' . $to . "\r\n"
        . 'Subject: ' . $subject . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n\r\n"
        . $text;

    // each step: what to send, and the reply code we expect back
    $steps = [
        ['', '220'],
        ['EHLO localhost', '250'],
        ['AUTH LOGIN', '334'],
        [base64_encode($cfg['username']), '334'],
        [base64_encode($cfg['password']), '235'],
        ['MAIL FROM:<' . $from . '>', '250'],
        ['RCPT TO:<' . $to . '>', '250'],
        ['DATA', '354'],
        [$message . "\r\n.", '250'],
    ];

    foreach ($steps as $step) {
        if ($step[0] != '') {
            fwrite($fp, $step[0] . "\r\n");
        }
        $reply = read_reply($fp);
        if (substr($reply, 0, 3) != $step[1]) {
            error_log('Mail error: expected ' . $step[1] . ' but got ' . trim($reply));
            fclose($fp);
            return false;
        }
    }

    fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return true;
}
