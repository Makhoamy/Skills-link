import { Component, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { JobService } from '../../services/job.service';
import { MyJob } from '../../models/job.model';
import { PageNavComponent } from '../../shared/page-nav/page-nav.component';

@Component({
  selector: 'app-my-jobs',
  standalone: true,
  imports: [RouterLink, PageNavComponent],
  templateUrl: './my-jobs.component.html',
})
export class MyJobsComponent implements OnInit {
  jobs = signal<MyJob[]>([]);
  loading = signal(true);
  errorMessage = signal<string | null>(null);

  constructor(private jobService: JobService) {}

  ngOnInit(): void {
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
