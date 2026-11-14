import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Applicant, ApplicationStatus } from '../models/application.model';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ApplicationService {
  constructor(private http: HttpClient) {}

  apply(jobId: number, coverNote: string): Observable<{ ok: boolean; id: number }> {
    return this.http.post<any>(`${environment.apiUrl}/jobs/apply.php`, {
      job_id: jobId,
      cover_note: coverNote,
    });
  }

  getApplicants(jobId: number): Observable<{ job: { id: number; title: string }; items: Applicant[] }> {
    return this.http.get<any>(`${environment.apiUrl}/jobs/applicants.php?job_id=${jobId}`);
  }

  updateStatus(applicationId: number, status: ApplicationStatus): Observable<{ ok: boolean; status: string }> {
    return this.http.post<any>(`${environment.apiUrl}/jobs/update_application.php`, {
      application_id: applicationId,
      status,
    });
  }
}
