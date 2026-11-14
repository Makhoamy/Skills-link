import { Component, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { AdminService } from '../../services/admin.service';
import { ReportResponse, ReportType } from '../../models/admin.model';
import { PageNavComponent } from '../../shared/page-nav/page-nav.component';

@Component({
  selector: 'app-reports',
  standalone: true,
  imports: [FormsModule, RouterLink, PageNavComponent],
  templateUrl: './reports.component.html',
  styleUrls: ['../admin-dashboard/admin-dashboard.component.css', './reports.component.css'],
})
export class ReportsComponent implements OnInit {
  tabs: { type: ReportType; label: string; dated: boolean }[] = [
    { type: 'users', label: 'User Registrations', dated: true },
    { type: 'jobs', label: 'Job Postings', dated: true },
    { type: 'applications', label: 'Applications', dated: true },
    { type: 'provinces', label: 'By Province', dated: false },
  ];

  active = signal<ReportType>('users');
  report = signal<ReportResponse | null>(null);
  loading = signal(false);
  errorMessage = signal('');
  generatedAt = signal('');

  from = '';
  to = '';

  constructor(private adminService: AdminService) {}

  ngOnInit(): void {
    this.load();
  }

  select(type: ReportType): void {
    this.active.set(type);
    this.load();
  }

  isDated(): boolean {
    return this.tabs.find((t) => t.type === this.active())?.dated ?? false;
  }

  load(): void {
    if (this.from && this.to && this.from > this.to) {
      this.errorMessage.set('The "From" date must be before the "To" date.');
      return;
    }
    this.loading.set(true);
    this.errorMessage.set('');
    this.adminService.getReport(this.active(), this.isDated() ? this.from : '', this.isDated() ? this.to : '').subscribe({
      next: (res) => {
        this.report.set(res);
        this.generatedAt.set(new Date().toLocaleString());
        this.loading.set(false);
      },
      error: (err) => {
        this.report.set(null);
        this.errorMessage.set(err.error?.error ?? 'Could not load this report.');
        this.loading.set(false);
      },
    });
  }

  clearFilters(): void {
    this.from = '';
    this.to = '';
    this.load();
  }

  periodText(): string {
    if (!this.isDated()) return 'Today (snapshot)';
    if (!this.from && !this.to) return 'All time';
    return (this.from || 'start') + ' to ' + (this.to || 'today');
  }

  exportToCsv(): void {
    const r = this.report();
    if (!r || r.rows.length === 0) {
      alert('There is nothing to export.');
      return;
    }
    const lines = [r.columns.map((c) => c.label), ...r.rows.map((row) => r.columns.map((c) => row[c.key] ?? ''))].map((row) =>
      row.map((v) => '"' + String(v).replace(/"/g, '""') + '"').join(',')
    );
    const csv = 'sep=,\n' + lines.join('\n');
    const url = URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = 'SkillsLink_' + this.active() + '_report_' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
    URL.revokeObjectURL(url);
  }

  printReport(): void {
    window.print();
  }
}
