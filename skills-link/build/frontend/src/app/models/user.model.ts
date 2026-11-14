export type UserRole = 'jobseeker' | 'recruiter' | 'admin';

export interface AuthUser {
  id: number;
  email: string;
  role: UserRole;
  /** false until the person has entered the code we emailed them. Missing = an older saved session; the server decides. */
  isVerified?: boolean;
}

export interface AuthResponse {
  success: boolean;
  token: string;
  expiresAt: string;
  user: AuthUser;
}
