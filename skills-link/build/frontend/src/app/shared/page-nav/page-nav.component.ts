import { Component, Input } from '@angular/core';
import { Location } from '@angular/common';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-page-nav',
  standalone: true,
  template: `
    <div class="page-nav no-print">
      <button type="button" class="btn btn-outline" (click)="goBack()">← Back</button>
      @if (nextLink) {
        <button type="button" class="btn btn-primary" (click)="goNext()">{{ nextLabel }} →</button>
      }
      @if (auth.isLoggedIn()) {
        <button type="button" class="btn btn-outline" (click)="exit()">Exit</button>
      }
    </div>
  `,
  styles: [`
    .page-nav {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-top: 2rem;
      padding-top: 1rem;
      border-top: 1px solid #ddd;
    }
  `],
})
export class PageNavComponent {
  @Input() nextLink = '';
  @Input() nextLabel = 'Next';

  constructor(private location: Location, private router: Router, public auth: AuthService) {}

  goBack(): void {
    if (window.history.length > 1) {
      this.location.back();
    } else {
      this.router.navigate(['/']);
    }
  }

  goNext(): void {
    this.router.navigate([this.nextLink]);
  }

  exit(): void {
    this.auth.logout();
    this.router.navigate(['/']);
  }
}
