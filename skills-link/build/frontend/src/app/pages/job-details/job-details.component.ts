import { Component, OnInit, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { JobService } from '../../services/job.service';
import { ApplicationService } from '../../services/application.service';
import { AuthService } from '../../services/auth.service';
import { ReviewService } from '../../services/review.service';
import { JobDetail } from '../../models/job.model';
import { Review } from '../../models/review.model';
import { PageNavComponent } from '../../shared/page-nav/page-nav.component';

@Component({
  selector: 'app-job-details',
  standalone: true,
  imports: [FormsModule, RouterLink, PageNavComponent],
  templateUrl: './job-details.component.html',
  styleUrl: './job-details.component.css',
})
export class JobDetailsComponent implements OnInit {
  job = signal<JobDetail | null>(null);
  reviews = signal<Review[]>([]);
  loading = signal(true);

  newRating = 5;
  newComment = '';
  reviewSubmitting = signal(false);
  reviewMessage = signal<string | null>(null);

  coverNote = '';
  applying = signal(false);
  applyMessage = signal<string | null>(null);
  applied = signal(false);

  private jobId!: number;

  constructor(
    private route: ActivatedRoute,
    private jobService: JobService,
    private reviewService: ReviewService,
    private applicationService: ApplicationService,
    public auth: AuthService
  ) {}

  ngOnInit(): void {
    this.jobId = Number(this.route.snapshot.paramMap.get('id'));
    this.jobService.getById(this.jobId).subscribe({
      next: (res) => {
        this.job.set(res.job);
        this.reviews.set(res.reviews);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  apply(): void {
    this.applyMessage.set(null);
    this.applying.set(true);
    this.applicationService.apply(this.jobId, this.coverNote).subscribe({
      next: () => {
        this.applying.set(false);
        this.applied.set(true);
        this.applyMessage.set('Your application has been sent.');
      },
      error: (err) => {
        this.applying.set(false);
        this.applyMessage.set(err.error?.error ?? 'Could not send your application.');
      },
    });
  }

  submitReview(): void {
    const job = this.job();
    if (!job) return;
    this.reviewSubmitting.set(true);
    this.reviewService
      .create({ jobId: this.jobId, revieweeUserId: job.recruiter_id, rating: this.newRating, comment: this.newComment })
      .subscribe({
        next: () => {
          this.reviewSubmitting.set(false);
          this.reviewMessage.set('Thanks — your review has been posted.');
        },
        error: (err) => {
          this.reviewSubmitting.set(false);
          this.reviewMessage.set(err.error?.error ?? 'Could not submit your review.');
        },
      });
  }
}
