
import { Component, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { checkPassword } from '../../utils/password';
import { PasswordStrengthComponent } from '../../shared/password-strength/password-strength.component';

@Component({
  selector: 'app-signup',
  standalone: true,
  imports: [FormsModule, RouterLink, PasswordStrengthComponent],
  templateUrl: './signup.component.html',
  styleUrl: './signup.component.css',
})
export class SignupComponent {
  email = '';
  phone = '';
  password = '';
  confirmPassword = '';
  role: 'jobseeker' | 'recruiter' | '' = '';
  popiaConsent = false;
  marketingConsent = false;

  errorMessage = signal<string | null>(null);
  loading = signal(false);

  constructor(private auth: AuthService, private router: Router, route: ActivatedRoute) {
    // The home page links here with ?role=jobseeker or ?role=recruiter
    const wanted = route.snapshot.queryParamMap.get('role');
    if (wanted === 'jobseeker' || wanted === 'recruiter') {
      this.role = wanted;
    }
  }

  submit(): void {
    this.errorMessage.set(null);

    // Check email
    if (!this.email.trim()) {
      this.errorMessage.set('Please enter your email address.');
      return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email.trim())) {
      this.errorMessage.set('Please enter a valid email address.');
      return;
    }

    // Check phone number
    if (!this.phone.trim()) {
      this.errorMessage.set('Please enter your phone number.');
      return;
    }

    if (!/^[0-9]{10}$/.test(this.phone)) {
      this.errorMessage.set('Please enter a valid 10-digit phone number.');
      return;
    }

    // Check password
    if (!this.password) {
      this.errorMessage.set('Please enter a password.');
      return;
    }

    if (!checkPassword(this.password, this.email.trim()).valid) {
      this.errorMessage.set('Your password is too weak. Please meet all the requirements listed under the password box.');
      return;
    }

    // Check confirm password
    if (!this.confirmPassword) {
      this.errorMessage.set('Please confirm your password.');
      return;
    }

    if (this.password !== this.confirmPassword) {
      this.errorMessage.set('Passwords do not match.');
      return;
    }

    // Check account type
    if (!this.role) {
      this.errorMessage.set(
        'Please choose whether you are a jobseeker or a recruiter.'
      );
      return;
    }

    // Check POPIA consent
    if (!this.popiaConsent) {
      this.errorMessage.set(
        'You must accept the Privacy Policy to continue.'
      );
      return;
    }

    this.loading.set(true);

    this.auth
      .signup({
        email: this.email.trim(),
        password: this.password,
        role: this.role,
        phone: this.phone,
        popiaConsent: this.popiaConsent,
        marketingConsent: this.marketingConsent,
      })
      .subscribe({
        next: () => {
          // Account created. Sign in and go straight to the user's dashboard.
          this.auth.login(this.email.trim(), this.password).subscribe({
            next: (res) => {
              this.loading.set(false);
              this.router.navigate([this.auth.homeFor(res.user.role)]);
            },
            error: () => {
              this.loading.set(false);
              this.router.navigate(['/login']);
            },
          });
        },
        error: (err) => {
          this.loading.set(false);
          this.errorMessage.set(
            err.error?.error ?? 'Could not create your account.'
          );
        },
      });
  }
}
