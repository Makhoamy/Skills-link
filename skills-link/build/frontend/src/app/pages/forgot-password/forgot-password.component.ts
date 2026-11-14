import { Component, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-forgot-password',
  standalone: true,
  imports: [FormsModule, RouterLink],
  templateUrl: './forgot-password.component.html',
  styleUrl: './forgot-password.component.css'
})
export class ForgotPasswordComponent {

  email = '';

  loading = signal(false);
  message = signal('');
  resetToken = signal('');
  errorMessage = signal('');

  constructor(private auth: AuthService) {}

  submit() {
    this.message.set('');
    this.errorMessage.set('');
    this.resetToken.set('');

    if (!this.email) {
      this.errorMessage.set('Please enter your email address.');
      return;
    }

    this.loading.set(true);

    this.auth.forgotPassword(this.email.trim()).subscribe({
      next: (res) => {
        this.message.set(res.message);
        if (res.resetLink) {
          const token = new URL(res.resetLink).searchParams.get('token');
          this.resetToken.set(token ?? '');
        }
        this.loading.set(false);
      },
      error: (err) => {
        this.errorMessage.set(err.error?.error ?? 'Something went wrong. Please try again.');
        this.loading.set(false);
      }
    });
  }
}
