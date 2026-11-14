import { Component, OnInit, computed, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { ProfileService } from '../../services/profile.service';
import { JobService } from '../../services/job.service';
import { WorkerProfile } from '../../models/profile.model';
import { JobSummary } from '../../models/job.model';

@Component({
  selector: 'app-jobseeker-dashboard',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './jobseeker-dashboard.component.html',
  styles: [`
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin: 1rem 0; }
    .stat { border: 1px solid #ddd; border-radius: 8px; padding: 1rem; text-align: center; }
    .stat strong { display: block; font-size: 1.6rem; }
    .actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 1rem 0; }
    .next-steps ol { margin: 0.4rem 0 0; padding-left: 1.2rem; }
    .next-steps li { margin-bottom: 0.5rem; }
    .next-steps li.done { opacity: 0.6; }
  `],
})
export class JobseekerDashboardComponent implements OnInit {
  profile = signal<WorkerProfile | null>(null);
  latestJobs = signal<JobSummary[]>([]);
  loading = signal(true);
  errorMessage = signal<string | null>(null);

  // how many of these profile details have been filled in
  completeness = computed(() => {
    const p = this.profile();
    if (!p) return 0;
    const checks = [p.first_name, p.last_name, p.city, p.province_id, p.bio, p.years_experience, p.availability, p.has_cv, p.has_photo];
    const done = checks.filter((v) => v !== null && v !== undefined && v !== '' && v !== false).length;
    return Math.round((done / checks.length) * 100);
  });

  constructor(
    public auth: AuthService,
    private profileService: ProfileService,
    private jobService: JobService,
  ) {}

  ngOnInit(): void {
    this.profileService.getWorker().subscribe({
      next: (res) => this.profile.set(res.profile),
      error: () => this.errorMessage.set('Could not load your profile.'),
    });
    this.jobService.search({ page: 1 }).subscribe({
      next: (res) => {
        this.latestJobs.set((res.jobs ?? []).slice(0, 5));
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }
}
