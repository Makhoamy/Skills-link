import { Component, OnInit, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { JobService } from '../../services/job.service';
import { JobSummary } from '../../models/job.model';
import { PageNavComponent } from '../../shared/page-nav/page-nav.component';

@Component({
  selector: 'app-job-list',
  standalone: true,
  imports: [RouterLink, PageNavComponent],
  templateUrl: './job-list.component.html',
  styleUrl: './job-list.component.css',
})
export class JobListComponent implements OnInit {
  jobs = signal<JobSummary[]>([]);
  loading = signal(true);
  keyword = '';
  provinceId: number | null = null;
  page = 1;
  perPage = 20;
  hasNextPage = signal(false);

  constructor(private route: ActivatedRoute, private router: Router, private jobService: JobService) {}

  ngOnInit(): void {
    this.route.queryParamMap.subscribe((params) => {
      this.keyword = params.get('keyword') ?? '';
      this.provinceId = params.get('provinceId') ? Number(params.get('provinceId')) : null;
      this.page = params.get('page') ? Number(params.get('page')) : 1;
      this.fetch();
    });
  }

  fetch(): void {
    this.loading.set(true);
    this.jobService
      .search({ keyword: this.keyword || undefined, provinceId: this.provinceId || undefined, page: this.page })
      .subscribe({
        next: (res) => {
          this.jobs.set(res.jobs);
          this.hasNextPage.set(res.jobs.length === this.perPage);
          this.loading.set(false);
        },
        error: () => this.loading.set(false),
      });
  }

  goToPage(newPage: number): void {
    if (newPage < 1) return;
    this.router.navigate(['/jobs'], {
      queryParams: {
        keyword: this.keyword || null,
        provinceId: this.provinceId || null,
        page: newPage === 1 ? null : newPage,
      },
    });
    window.scrollTo({ top: 0 });
  }
}
