import { Component, OnInit, signal } from '@angular/core';
import { AdminService } from '../../services/admin.service';
import { ProfileService } from '../../services/profile.service';
import { PendingRecruiter } from '../../models/profile.model';
import { PageNavComponent } from '../../shared/page-nav/page-nav.component';

@Component({
  selector: 'app-admin',
  standalone: true,
  imports: [PageNavComponent],
  templateUrl: './admin.component.html',
})
export class AdminComponent implements OnInit {
  statuses = ['pending', 'verified', 'rejected'];
  currentStatus = signal('pending');
  recruiters = signal<PendingRecruiter[]>([]);
  loading = signal(true);
  errorMessage = signal<string | null>(null);

  constructor(private adminService: AdminService, private profileService: ProfileService) {}

  ngOnInit(): void {
    this.load();
  }

  showStatus(status: string): void {
    this.currentStatus.set(status);
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.errorMessage.set(null);
    this.adminService.getRecruiters(this.currentStatus()).subscribe({
      next: (res) => {
        this.recruiters.set(res.items);
        this.loading.set(false);
      },
      error: (err) => {
        this.errorMessage.set(err.error?.error ?? 'Could not load recruiters.');
        this.loading.set(false);
      },
    });
  }

  setStatus(recruiter: PendingRecruiter, status: 'verified' | 'rejected'): void {
    this.adminService.setRecruiterStatus(recruiter.user_id, status).subscribe({
      next: () => this.load(),
      error: (err) => this.errorMessage.set(err.error?.error ?? 'Could not update that recruiter.'),
    });
  }

  viewDocument(recruiter: PendingRecruiter): void {
    this.profileService.downloadFile('registration_doc', recruiter.user_id);
  }
}
