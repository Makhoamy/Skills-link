import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { CreateJobPayload, JobCategory, JobDetail, JobSummary, MyJob, Province } from '../models/job.model';
import { Review } from '../models/review.model';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class JobService {
  constructor(private http: HttpClient) {}

  search(params: { keyword?: string; provinceId?: number; categoryId?: number; page?: number }): Observable<{ success: boolean; jobs: JobSummary[] }> {
    let query = new URLSearchParams();
    if (params.keyword) query.set('keyword', params.keyword);
    if (params.provinceId) query.set('provinceId', String(params.provinceId));
    if (params.categoryId) query.set('categoryId', String(params.categoryId));
    if (params.page) query.set('page', String(params.page));
    return this.http.get<any>(`${environment.apiUrl}/jobs/list.php?${query.toString()}`);
  }

  getById(id: number): Observable<{ success: boolean; job: JobDetail; reviews: Review[] }> {
    return this.http.get<any>(`${environment.apiUrl}/jobs/get.php?id=${id}`);
  }

  create(payload: CreateJobPayload): Observable<{ success: boolean; jobId: number; belowNmwFlag: boolean; message: string }> {
    return this.http.post<any>(`${environment.apiUrl}/jobs/create.php`, payload);
  }

  /** The signed-in recruiter's own jobs, each with an applicant count. */
  getMine(): Observable<MyJob[]> {
    return this.http
      .get<{ items: MyJob[] }>(`${environment.apiUrl}/jobs/mine.php`)
      .pipe(map((res) => res.items));
  }

  getCategories(): Observable<JobCategory[]> {
    return this.http
      .get<{ items: JobCategory[] }>(`${environment.apiUrl}/reference/categories.php`)
      .pipe(map((res) => res.items));
  }

  getProvinces(): Observable<Province[]> {
    return this.http
      .get<{ items: Province[] }>(`${environment.apiUrl}/reference/provinces.php`)
      .pipe(map((res) => res.items));
  }
}
