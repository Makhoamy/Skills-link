import { Component, OnInit, computed, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ProfileService } from '../../services/profile.service';
import { JobService } from '../../services/job.service';
import { RecruiterProfile } from '../../models/profile.model';
import { MyJob } from '../../models/job.model';

@Component({
  selector: 'app-recruiter-dashboard',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './recruiter-dashboard.component.html',
  styles: [`
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin: 1rem 0; }
    .stat { border: 1px solid #ddd; border-radius: 8px; padding: 1rem; text-align: center; }
    .stat strong { display: block; font-size: 1.6rem; }
    .actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 1rem 0; }
    .next-steps ol { margin: 0.4rem 0 0; padding-left: 1.2rem; }
    .next-steps li { margin-bottom: 0.5rem; }
  `],
})
export class RecruiterDashboardComponent implements OnInit {
  profile = signal<RecruiterProfile | null>(null);
  jobs = signal<MyJob[]>([]);
  loading = signal(true);
  errorMessage = signal<string | null>(null);

  openJobs = computed(() => this.jobs().filter((j) => j.status === 'open').length);
  totalApplicants = computed(() => this.jobs().reduce((sum, j) => sum + j.applicant_count, 0));
  newApplicants = computed(() => this.jobs().reduce((sum, j) => sum + j.new_count, 0));
  recentJobs = computed(() => this.jobs().slice(0, 5));

  constructor(private profileService: ProfileService, private jobService: JobService) {}

  ngOnInit(): void {
    this.profileService.getRecruiter().subscribe({
      next: (res) => this.profile.set(res.profile),
      error: () => {},
    });
    this.jobService.getMine().subscribe({
      next: (items) => {
        this.jobs.set(items);
        this.loading.set(false);
      },
      error: (err) => {
        this.errorMessage.set(err.error?.error ?? 'Could not load your jobs.');
        this.loading.set(false);
      },
    });
  }
}
