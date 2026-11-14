import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { AuthResponse, AuthUser } from '../models/user.model';
import { environment } from '../../environments/environment';

const TOKEN_KEY = 'skillslink_token';
const USER_KEY = 'skillslink_user';

@Injectable({ providedIn: 'root' })
export class AuthService {
  currentUser = signal<AuthUser | null>(this.loadStoredUser());

  constructor(private http: HttpClient) {}

  private loadStoredUser(): AuthUser | null {
    const raw = localStorage.getItem(USER_KEY);
    return raw ? JSON.parse(raw) : null;
  }

  signup(payload: {
    email: string;
    password: string;
    role: 'jobseeker' | 'recruiter';
    phone?: string;
    popiaConsent: boolean;
    marketingConsent: boolean;
  }): Observable<{ success: boolean; userId: number; role: string; requiresVerification: boolean }> {
    return this.http.post<any>(`${environment.apiUrl}/auth/signup.php`, payload);
  }

  login(email: string, password: string): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${environment.apiUrl}/auth/login.php`, { email, password }).pipe(
      tap((res) => {
        localStorage.setItem(TOKEN_KEY, res.token);
        localStorage.setItem(USER_KEY, JSON.stringify(res.user));
        this.currentUser.set(res.user);
      })
    );
  }

  /** Where each kind of user lands after they are signed in and verified. */
  homeFor(role: AuthUser['role']): string {
    if (role === 'admin') return '/admin-home';
    if (role === 'recruiter') return '/recruiter-dashboard';
    return '/dashboard';
  }

  isVerified(): boolean {
    // only an explicit "false" means unverified; the server rejects anything that slips through
    return this.currentUser()?.isVerified !== false;
  }

  /** Update the saved user after verifying (or when the server tells us they are not verified). */
  setVerified(verified: boolean): void {
    const user = this.currentUser();
    if (!user) return;
    const updated = { ...user, isVerified: verified };
    localStorage.setItem(USER_KEY, JSON.stringify(updated));
    this.currentUser.set(updated);
  }

  forgotPassword(email: string): Observable<{ success: boolean; message: string; resetLink?: string }> {
    return this.http.post<any>(`${environment.apiUrl}/auth/forgot_password.php`, { email });
  }

  resetPassword(token: string, password: string): Observable<{ success: boolean; message: string }> {
    return this.http.post<any>(`${environment.apiUrl}/auth/reset_password.php`, { token, password });
  }

  logout(): void {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    this.currentUser.set(null);
  }

  getToken(): string | null {
    return localStorage.getItem(TOKEN_KEY);
  }

  isLoggedIn(): boolean {
    return !!this.getToken();
  }
}
