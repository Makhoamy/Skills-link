<?php

/**
 * Password rules. The Angular app mirrors these in src/app/utils/password.ts so the
 * user sees the same checklist the server enforces - change both together.
 *
 * bcrypt silently ignores everything after 72 bytes, so longer passwords are refused
 * instead of being quietly truncated.
 */
const PASSWORD_MIN_LENGTH = 10;
const PASSWORD_MAX_BYTES  = 72;

function password_common_list()
{
    return ['password', 'passw0rd', 'qwerty', 'qwertyuiop', 'letmein', 'welcome', 'admin',
            'administrator', 'iloveyou', 'monkey', 'dragon', 'football', 'login', 'master',
            'abc', 'abcdef', 'abcdefgh', 'skillslink', 'skills', 'changeme', 'secret'];
}

/** Returns a list of things wrong with the password. Empty list = password is fine. */
function password_problems($password, $email = '')
{
    $problems = [];

    if (mb_strlen($password) < PASSWORD_MIN_LENGTH) {
        $problems[] = 'needs at least ' . PASSWORD_MIN_LENGTH . ' characters';
    }
    if (strlen($password) > PASSWORD_MAX_BYTES) {
        $problems[] = 'must be no longer than ' . PASSWORD_MAX_BYTES . ' characters';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $problems[] = 'needs a lowercase letter';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $problems[] = 'needs an uppercase letter';
    }
    if (!preg_match('/\d/', $password)) {
        $problems[] = 'needs a number';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $problems[] = 'needs a special character (for example ! @ # $ %)';
    }

    $lower = strtolower($password);
    $lettersOnly = preg_replace('/[^a-z]/', '', $lower);
    $common = password_common_list();
    if (in_array($lower, $common, true) || in_array($lettersOnly, $common, true)) {
        $problems[] = 'must not be a common password';
    }

    if (preg_match('/(.)\1{3,}/u', $password)) {
        $problems[] = 'must not repeat the same character 4 or more times in a row';
    }

    $at = strpos($email, '@');
    if ($at !== false) {
        $name = strtolower(substr($email, 0, $at));
        if (strlen($name) >= 4 && strpos($lower, $name) !== false) {
            $problems[] = 'must not contain the first part of your email address';
        }
    }

    return $problems;
}

/** One ready-to-show error message, or null when the password is acceptable. */
function password_error($password, $email = '')
{
    $problems = password_problems($password, $email);
    if (!$problems) {
        return null;
    }
    return 'Password is too weak: ' . implode('; ', $problems) . '.';
}
