import { Component, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { checkPassword } from '../../utils/password';
import { PasswordStrengthComponent } from '../../shared/password-strength/password-strength.component';

@Component({
  selector: 'app-reset-password',
  standalone: true,
  imports: [FormsModule, RouterLink, PasswordStrengthComponent],
  templateUrl: './reset-password.component.html',
  styleUrl: './reset-password.component.css'
})
export class ResetPasswordComponent implements OnInit {

  token = '';
  password = '';
  confirmPassword = '';

  loading = signal(false);
  done = signal(false);
  message = signal('');
  errorMessage = signal('');

  constructor(private route: ActivatedRoute, private auth: AuthService) {}

  ngOnInit(): void {
    this.token = this.route.snapshot.queryParamMap.get('token') ?? '';
    if (!this.token) {
      this.errorMessage.set('This reset link is not valid. Please ask for a new one.');
    }
  }

  submit() {
    this.errorMessage.set('');

    if (!checkPassword(this.password).valid) {
      this.errorMessage.set('Your password is too weak. Please meet all the requirements listed under the password box.');
      return;
    }
    if (this.password !== this.confirmPassword) {
      this.errorMessage.set('The passwords do not match.');
      return;
    }

    this.loading.set(true);

    this.auth.resetPassword(this.token, this.password).subscribe({
      next: (res) => {
        this.message.set(res.message);
        this.done.set(true);
        this.loading.set(false);
      },
      error: (err) => {
        this.errorMessage.set(err.error?.error ?? 'Something went wrong. Please try again.');
        this.loading.set(false);
      }
    });
  }
}
