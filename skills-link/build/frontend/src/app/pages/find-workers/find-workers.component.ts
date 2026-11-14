import { Component, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AdvertService } from '../../services/advert.service';
import { JobService } from '../../services/job.service';
import { Advert } from '../../models/advert.model';
import { JobCategory, Province } from '../../models/job.model';
import { PageNavComponent } from '../../shared/page-nav/page-nav.component';

@Component({
  selector: 'app-find-workers',
  standalone: true,
  imports: [FormsModule, PageNavComponent],
  templateUrl: './find-workers.component.html',
  styleUrl: './find-workers.component.css',
})
export class FindWorkersComponent implements OnInit {
  adverts = signal<Advert[]>([]);
  categories = signal<JobCategory[]>([]);
  provinces = signal<Province[]>([]);
  loading = signal(true);
  errorMessage = signal<string | null>(null);

  keyword = '';
  provinceId: number | null = null;
  categoryId: number | null = null;

  constructor(private advertService: AdvertService, private jobService: JobService) {}

  ngOnInit(): void {
    this.jobService.getCategories().subscribe((list) => this.categories.set(list));
    this.jobService.getProvinces().subscribe((list) => this.provinces.set(list));
    this.search();
  }

  search(): void {
    this.loading.set(true);
    this.errorMessage.set(null);
    this.advertService
      .list({
        keyword: this.keyword.trim() || undefined,
        provinceId: this.provinceId || undefined,
        categoryId: this.categoryId || undefined,
      })
      .subscribe({
        next: (res) => {
          this.adverts.set(res.items);
          this.loading.set(false);
        },
        error: (err) => {
          this.errorMessage.set(err.error?.error ?? 'Could not load the adverts.');
          this.loading.set(false);
        },
      });
  }

  clear(): void {
    this.keyword = '';
    this.provinceId = null;
    this.categoryId = null;
    this.search();
  }
}
