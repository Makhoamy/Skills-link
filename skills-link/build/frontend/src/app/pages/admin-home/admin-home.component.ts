import { Component, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AdminService } from '../../services/admin.service';
import { DashboardResponse } from '../../models/admin.model';

@Component({
  selector: 'app-admin-home',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './admin-home.component.html',
  styles: [`
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin: 1rem 0; }
    .stat { border: 1px solid #ddd; border-radius: 8px; padding: 1rem; text-align: center; }
    .stat strong { display: block; font-size: 1.6rem; }
    .actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 1rem 0; }
  `],
})
export class AdminHomeComponent implements OnInit {
  stats = signal<DashboardResponse | null>(null);
  pendingRecruiters = signal(0);
  loading = signal(true);
  errorMessage = signal<string | null>(null);

  constructor(private adminService: AdminService) {}

  ngOnInit(): void {
    this.adminService.getDashboard(30, '', '').subscribe({
      next: (res) => {
        this.stats.set(res);
        this.loading.set(false);
      },
      error: (err) => {
        this.errorMessage.set(err.error?.error ?? 'Could not load the dashboard.');
        this.loading.set(false);
      },
    });
    this.adminService.getRecruiters('pending').subscribe({
      next: (res) => this.pendingRecruiters.set(res.items.length),
      error: () => {},
    });
  }
}
