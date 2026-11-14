import { Component, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AdminService } from '../../services/admin.service';
import { AuthService } from '../../services/auth.service';
import { LoginRecord, UserRecord } from '../../models/admin.model';
import { PageNavComponent } from '../../shared/page-nav/page-nav.component';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [FormsModule, PageNavComponent],
  templateUrl: './admin-dashboard.component.html',
  styleUrl: './admin-dashboard.component.css',
})
export class AdminDashboardComponent implements OnInit {
  totalUsers = signal(0);
  totalJobseekers = signal(0);
  totalRecruiters = signal(0);
  totalSignIns = signal(0);
  loginsToday = signal(0);
  activeLast7Days = signal(0);
  loginHistory = signal<LoginRecord[]>([]);
  users = signal<UserRecord[]>([]);

  days = 30;
  role = '';
  search = '';

  loading = signal(false);
  errorMessage = signal('');
  message = signal('');
  generatedAt = signal('');

  constructor(private adminService: AdminService, public auth: AuthService) {}

  ngOnInit(): void {
    this.loadDashboard();
  }

  loadDashboard(): void {
    this.loading.set(true);
    this.errorMessage.set('');

    this.adminService.getDashboard(this.days, this.role, this.search.trim()).subscribe({
      next: (res) => {
        this.totalUsers.set(res.totalUsers);
        this.totalJobseekers.set(res.totalJobseekers);
        this.totalRecruiters.set(res.totalRecruiters);
        this.totalSignIns.set(res.totalSignIns);
        this.loginsToday.set(res.loginsToday);
        this.activeLast7Days.set(res.activeLast7Days);
        this.loginHistory.set(res.loginHistory);
        this.users.set(res.users);
        this.generatedAt.set(new Date().toLocaleString());
        this.loading.set(false);
      },
      error: (err) => {
        this.errorMessage.set(err.error?.error ?? 'Could not load the login history.');
        this.loading.set(false);
      },
    });
  }

  clearFilters(): void {
    this.days = 30;
    this.role = '';
    this.search = '';
    this.loadDashboard();
  }

  periodText(): string {
    return this.days === 0 ? 'All time' : 'Last ' + this.days + ' days';
  }

  // loginDate looks like 2026-11-01 10:30:00
  dayName(loginDate: string): string {
    const date = new Date(loginDate.split(' ')[0] + 'T00:00:00');
    return date.toLocaleDateString('en-ZA', { weekday: 'long' });
  }

  deleteUser(user: UserRecord): void {
    const sure = confirm('Delete ' + user.email + '? This removes their account and all their data and cannot be undone.');
    if (!sure) return;

    this.message.set('');
    this.errorMessage.set('');
    this.adminService.deleteUser(user.id).subscribe({
      next: () => {
        this.message.set(user.email + ' was deleted.');
        this.loadDashboard();
      },
      error: (err) => this.errorMessage.set(err.error?.error ?? 'Could not delete that user.'),
    });
  }

  // downloads whatever is shown in the table, opens in Excel
  exportToCsv(): void {
    const records = this.loginHistory();
    if (records.length === 0) {
      alert('There are no login records to export.');
      return;
    }

    const headings = ['Login ID', 'Email Address', 'Role', 'Day', 'Date', 'Time', 'IP Address'];
    const rows = records.map((r) => [
      r.loginID,
      r.email,
      r.role,
      this.dayName(r.loginDate),
      r.loginDate.split(' ')[0],
      r.loginDate.split(' ')[1] ?? '',
      r.ipAddress ?? '',
    ]);

    const lines = [headings, ...rows].map((row) =>
      row.map((value) => '"' + String(value).replace(/"/g, '""') + '"').join(',')
    );

    // the sep line makes Excel use commas even when the computer is set to semicolons
    const csv = 'sep=,\n' + lines.join('\n');

    const file = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(file);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'SkillsLink_Login_Report_' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
    URL.revokeObjectURL(url);
  }

  // the browser print window lets the admin save it as a PDF
  printReport(): void {
    window.print();
  }
}
