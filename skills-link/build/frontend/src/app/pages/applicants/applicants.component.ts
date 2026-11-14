import { Component, OnInit, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ApplicationService } from '../../services/application.service';
import { ProfileService } from '../../services/profile.service';
import { Applicant, ApplicationStatus } from '../../models/application.model';
import { PageNavComponent } from '../../shared/page-nav/page-nav.component';

@Component({
  selector: 'app-applicants',
  standalone: true,
  imports: [RouterLink, PageNavComponent],
  templateUrl: './applicants.component.html',
})
export class ApplicantsComponent implements OnInit {
  jobTitle = signal('');
  applicants = signal<Applicant[]>([]);
  loading = signal(true);
  errorMessage = signal<string | null>(null);

  private jobId!: number;

  constructor(
    private route: ActivatedRoute,
    private applicationService: ApplicationService,
    private profileService: ProfileService
  ) {}

  ngOnInit(): void {
    this.jobId = Number(this.route.snapshot.paramMap.get('id'));
    this.load();
  }

  load(): void {
    this.applicationService.getApplicants(this.jobId).subscribe({
      next: (res) => {
        this.jobTitle.set(res.job.title);
        this.applicants.set(res.items);
        this.loading.set(false);
      },
      error: (err) => {
        this.errorMessage.set(err.error?.error ?? 'Could not load the applicants.');
        this.loading.set(false);
      },
    });
  }

  setStatus(applicant: Applicant, status: ApplicationStatus): void {
    this.errorMessage.set(null);
    this.applicationService.updateStatus(applicant.id, status).subscribe({
      next: () => this.load(),
      error: (err) => this.errorMessage.set(err.error?.error ?? 'Could not update that application.'),
    });
  }

  downloadCv(applicant: Applicant): void {
    this.profileService.downloadFile('cv', applicant.worker_id);
  }
}
