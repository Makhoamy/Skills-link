import { Component, OnInit, computed, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { JobService } from '../../services/job.service';
import { EmploymentType, JobCategory, PayPeriod, Province } from '../../models/job.model';
import { PageNavComponent } from '../../shared/page-nav/page-nav.component';

const CURRENT_NMW_HOURLY = 28.79;

@Component({
  selector: 'app-job-post',
  standalone: true,
  imports: [FormsModule, PageNavComponent],
  templateUrl: './job-post.component.html',
  styleUrl: './job-post.component.css',
})
export class JobPostComponent implements OnInit {
  title = '';
  description = '';
  categoryId: number | null = null;
  provinceId: number | null = null;
  city = '';
  employmentType: EmploymentType | '' = '';
  payAmount: number | null = null;
  payPeriod: PayPeriod | '' = '';

  submitting = signal(false);
  errorMessage = signal<string | null>(null);
  successMessage = signal<string | null>(null);

  hourlyEquivalent = computed(() => {
    if (!this.payAmount || !this.payPeriod) return null;
    switch (this.payPeriod) {
      case 'hourly': return this.payAmount;
      case 'daily': return this.payAmount / 9;
      case 'weekly': return this.payAmount / 45;
      case 'monthly': return this.payAmount / 195;
      default: return null;
    }
  });

  isBelowNmw = computed(() => {
    const h = this.hourlyEquivalent();
    return h !== null && h < CURRENT_NMW_HOURLY;
  });

  categories = signal<JobCategory[]>([]);
  provinces = signal<Province[]>([]);

  constructor(private jobs: JobService, private router: Router) {}

  ngOnInit(): void {
    this.jobs.getCategories().subscribe((list) => this.categories.set(list));
    this.jobs.getProvinces().subscribe((list) => this.provinces.set(list));
  }

  submit(): void {
    this.errorMessage.set(null);
    if (!this.categoryId || !this.provinceId || !this.employmentType || !this.payPeriod || !this.payAmount) {
      this.errorMessage.set('Please fill in all required fields.');
      return;
    }

    this.submitting.set(true);
    this.jobs
      .create({
        title: this.title,
        description: this.description,
        categoryId: this.categoryId,
        provinceId: this.provinceId,
        city: this.city,
        employmentType: this.employmentType,
        payAmount: this.payAmount,
        payPeriod: this.payPeriod,
      })
      .subscribe({
        next: (res) => {
          this.submitting.set(false);
          this.successMessage.set(res.message);
          setTimeout(() => this.router.navigate(['/jobs', res.jobId]), 1200);
        },
        error: (err) => {
          this.submitting.set(false);
          this.errorMessage.set(err.error?.error ?? 'Could not post this job.');
        },
      });
  }
}
