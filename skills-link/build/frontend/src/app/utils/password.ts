/**
 * Password rules. These mirror api/lib/password_policy.php, which is what the server
 * actually enforces - if you change one, change the other.
 */
export const PASSWORD_MIN_LENGTH = 10;
export const PASSWORD_MAX_BYTES = 72; // bcrypt ignores anything after 72 bytes

const COMMON_PASSWORDS = [
  'password', 'passw0rd', 'qwerty', 'qwertyuiop', 'letmein', 'welcome', 'admin',
  'administrator', 'iloveyou', 'monkey', 'dragon', 'football', 'login', 'master',
  'abc', 'abcdef', 'abcdefgh', 'skillslink', 'skills', 'changeme', 'secret',
];

export interface PasswordRule {
  id: string;
  label: string;
  ok: boolean;
  /** true = always listed; false = only listed when the password breaks it */
  always: boolean;
}

export interface PasswordCheck {
  rules: PasswordRule[];
  valid: boolean;
  /** 0 = nothing typed, 1 = too weak, 2 = almost there, 3 = good, 4 = strong */
  level: 0 | 1 | 2 | 3 | 4;
  label: string;
}

export function checkPassword(password: string, email = ''): PasswordCheck {
  const lower = password.toLowerCase();
  const lettersOnly = lower.replace(/[^a-z]/g, '');
  const emailName = email.includes('@') ? email.split('@')[0].toLowerCase() : '';
  const bytes = new TextEncoder().encode(password).length;

  const core: PasswordRule[] = [
    { id: 'length', label: `At least ${PASSWORD_MIN_LENGTH} characters`, ok: [...password].length >= PASSWORD_MIN_LENGTH, always: true },
    { id: 'lower', label: 'A lowercase letter', ok: /[a-z]/.test(password), always: true },
    { id: 'upper', label: 'An uppercase letter', ok: /[A-Z]/.test(password), always: true },
    { id: 'digit', label: 'A number', ok: /\d/.test(password), always: true },
    { id: 'special', label: 'A special character (for example ! @ # $ %)', ok: /[^A-Za-z0-9]/.test(password), always: true },
  ];
  const extra: PasswordRule[] = [
    { id: 'max', label: `No longer than ${PASSWORD_MAX_BYTES} characters`, ok: bytes <= PASSWORD_MAX_BYTES, always: false },
    { id: 'common', label: 'Not a common password', ok: !(COMMON_PASSWORDS.includes(lower) || COMMON_PASSWORDS.includes(lettersOnly)), always: false },
    { id: 'repeat', label: 'No character repeated 4 or more times in a row', ok: !/(.)\1{3,}/u.test(password), always: false },
    { id: 'email', label: 'Does not contain the first part of your email address', ok: !(emailName.length >= 4 && lower.includes(emailName)), always: false },
  ];

  const rules = [...core, ...extra];
  const valid = rules.every((r) => r.ok);
  const corePassed = core.filter((r) => r.ok).length;

  let level: PasswordCheck['level'];
  if (!password) level = 0;
  else if (!valid) level = corePassed >= 4 ? 2 : 1;
  else level = [...password].length >= 14 ? 4 : 3;

  const label = ['', 'Too weak', 'Almost there', 'Good', 'Strong'][level];
  return { rules, valid, level, label };
}
