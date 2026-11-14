import { Component, Input } from '@angular/core';
import { checkPassword, PasswordCheck } from '../../utils/password';

/** Strength bar + checklist. Put it under a password input: <app-password-strength [password]="password" [email]="email" /> */
@Component({
  selector: 'app-password-strength',
  standalone: true,
  template: `
    @if (password) {
      <div class="meter" aria-hidden="true">
        @for (n of [1, 2, 3, 4]; track n) {
          <span [class.on]="check.level >= n" [attr.data-level]="check.level"></span>
        }
      </div>
      <p class="strength-label" [attr.data-level]="check.level" role="status">Password strength: {{ check.label }}</p>
    }
    <ul class="rules">
      @for (r of check.rules; track r.id) {
        @if (r.always || !r.ok) {
          <li [class.ok]="r.ok" [class.bad]="!r.ok && !!password">
            <span aria-hidden="true">{{ r.ok ? '✓' : '•' }}</span> {{ r.label }}
          </li>
        }
      }
    </ul>
  `,
  styles: [`
    .meter { display: flex; gap: 4px; margin: 0.4rem 0 0.2rem; }
    .meter span { flex: 1; height: 6px; border-radius: 3px; background: #ddd; }
    .meter span.on[data-level="1"] { background: #d9534f; }
    .meter span.on[data-level="2"] { background: #f0ad4e; }
    .meter span.on[data-level="3"] { background: #5cb85c; }
    .meter span.on[data-level="4"] { background: #2e8b57; }
    .strength-label { margin: 0 0 0.3rem; font-size: 0.85rem; }
    .rules { list-style: none; margin: 0.3rem 0 0; padding: 0; font-size: 0.85rem; }
    .rules li { color: #666; }
    .rules li.ok { color: #2e8b57; }
    .rules li.bad { color: #b3261e; }
  `],
})
export class PasswordStrengthComponent {
  @Input() password = '';
  @Input() email = '';

  get check(): PasswordCheck {
    return checkPassword(this.password, this.email);
  }
}
