import { Component, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AdvertService } from '../../services/advert.service';
import { JobService } from '../../services/job.service';
import { JobCategory, Province } from '../../models/job.model';
import { PageNavComponent } from '../../shared/page-nav/page-nav.component';

@Component({
  selector: 'app-my-adverts',
  standalone: true,
  imports: [FormsModule, PageNavComponent],
  templateUrl: './my-adverts.component.html',
  styleUrl: './my-adverts.component.css',
})
export class MyAdvertsComponent implements OnInit {
  title = '';
  description = '';
  categoryId: number | null = null;
  provinceId: number | null = null;
  city = '';
  contactPhone = '';
  rateAmount: number | null = null;
  ratePeriod: 'hourly' | 'daily' | 'weekly' | 'per_job' | '' = '';

  submitting = signal(false);
  submitted = signal(false);
  message = signal<string | null>(null);
  success = signal(false);

  categories = signal<JobCategory[]>([]);
  provinces = signal<Province[]>([]);

  constructor(private advertService: AdvertService, private jobService: JobService) {}

  ngOnInit(): void {
    this.jobService.getCategories().subscribe({
      next: (list) => this.categories.set(list),
      error: () => this.message.set('Could not load categories. Please refresh the page.'),
    });
    this.jobService.getProvinces().subscribe({
      next: (list) => this.provinces.set(list),
      error: () => this.message.set('Could not load provinces. Please refresh the page.'),
    });
  }

  /** Returns a map of field -> message. Empty map means the form is valid. */
  validate(): Record<string, string> {
    const e: Record<string, string> = {};

    const title = (this.title ?? '').trim();
    if (!title) e['title'] = 'Headline is required.';
    else if (title.length < 5) e['title'] = 'Headline must be at least 5 characters.';
    else if (title.length > 120) e['title'] = 'Headline is too long (max 120 characters).';

    const desc = (this.description ?? '').trim();
    if (!desc) e['description'] = 'Please describe your work.';
    else if (desc.length < 20) e['description'] = 'Please write at least 20 characters.';
    else if (desc.length > 800) e['description'] = 'Description is too long (max 800 characters).';

    if (!this.categoryId) e['categoryId'] = 'Please select a category.';
    if (!this.provinceId) e['provinceId'] = 'Please select a province.';

    const city = (this.city ?? '').trim();
    if (!city) e['city'] = 'City / town is required.';
    else if (city.length > 80) e['city'] = 'City / town is too long (max 80 characters).';

    const amount = this.rateAmount as any;
    const hasAmount = amount !== null && amount !== undefined && amount !== '';
    const hasPeriod = !!this.ratePeriod;
    if (hasAmount && (isNaN(Number(amount)) || Number(amount) <= 0)) {
      e['rateAmount'] = 'Rate must be a number greater than 0.';
    } else if (hasAmount && !hasPeriod) {
      e['ratePeriod'] = 'Choose what your rate is per.';
    } else if (hasPeriod && !hasAmount) {
      e['rateAmount'] = 'Enter your rate, or clear the "Per" selection.';
    }

    const phone = (this.contactPhone ?? '').trim();
    if (!phone) e['contactPhone'] = 'Contact phone is required.';
    else if (!/^\+?[0-9\s\-()]{9,20}$/.test(phone) || phone.replace(/\D/g, '').length < 9) {
      e['contactPhone'] = 'Please enter a valid phone number (at least 9 digits).';
    }
    return e;
  }

  get errors(): Record<string, string> {
    return this.submitted() ? this.validate() : {};
  }

  submit(): void {
    this.message.set(null);
    this.success.set(false);
    this.submitted.set(true);
    if (this.submitting()) return;

    const problems = this.validate();
    const keys = Object.keys(problems);
    if (keys.length) {
      this.message.set('Please fix the highlighted fields before posting.');
      setTimeout(() => document.getElementById(keys[0])?.focus(), 0);
      return;
    }

    this.submitting.set(true);
    this.advertService
      .create({
        title: this.title.trim(),
        description: this.description.trim(),
        categoryId: this.categoryId!,
        provinceId: this.provinceId!,
        city: this.city.trim(),
        contactPhone: this.contactPhone.trim(),
        rateAmount: this.rateAmount !== null && (this.rateAmount as any) !== '' ? Number(this.rateAmount) : undefined,
        ratePeriod: this.ratePeriod || undefined,
      })
      .subscribe({
        next: () => {
          this.submitting.set(false);
          this.success.set(true);
          this.message.set('Your advert is live for 60 days.');
          this.resetForm();
        },
        error: (err) => {
          this.submitting.set(false);
          this.success.set(false);
          this.message.set(
            err?.error?.error ??
              (err?.status === 0
                ? 'Cannot reach the server. Please check your connection and try again.'
                : err?.status === 401
                  ? 'Your session has expired. Please log in again.'
                  : 'Could not post your advert.')
          );
        },
      });
  }

  private resetForm(): void {
    this.title = '';
    this.description = '';
    this.categoryId = null;
    this.provinceId = null;
    this.city = '';
    this.contactPhone = '';
    this.rateAmount = null;
    this.ratePeriod = '';
    this.submitted.set(false);
  }
}
