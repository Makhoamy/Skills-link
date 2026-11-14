import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { RecruiterProfile, UploadKind, WorkerProfile } from '../models/profile.model';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ProfileService {
  constructor(private http: HttpClient) {}

  getWorker(): Observable<{ profile: WorkerProfile | null; skill_ids: number[] }> {
    return this.http.get<any>(`${environment.apiUrl}/profiles/worker.php`);
  }

  saveWorker(data: any): Observable<{ ok: boolean }> {
    return this.http.post<any>(`${environment.apiUrl}/profiles/worker.php`, data);
  }

  getRecruiter(): Observable<{ profile: RecruiterProfile | null }> {
    return this.http.get<any>(`${environment.apiUrl}/profiles/recruiter.php`);
  }

  saveRecruiter(data: any): Observable<{ ok: boolean }> {
    return this.http.post<any>(`${environment.apiUrl}/profiles/recruiter.php`, data);
  }

  upload(kind: UploadKind, file: File): Observable<{ ok: boolean }> {
    const form = new FormData();
    form.append('kind', kind);
    form.append('file', file);
    return this.http.post<any>(`${environment.apiUrl}/profiles/upload.php`, form);
  }

  saveEquity(given: boolean, ethnicity: string, hasDisability: string): Observable<{ ok: boolean }> {
    return this.http.post<any>(`${environment.apiUrl}/consent/update.php`, {
      type: 'equity_info',
      given,
      ethnicity,
      has_disability: hasDisability,
    });
  }

  getConsent(): Observable<{ marketing: boolean; equity: { ethnicity: string | null; has_disability: string | null } | null }> {
    return this.http.get<any>(`${environment.apiUrl}/consent/update.php`);
  }

  downloadFile(kind: UploadKind, userId: number): void {
    this.http
      .get(`${environment.apiUrl}/files/download.php?kind=${kind}&user_id=${userId}`, { responseType: 'blob' })
      .subscribe({
        next: (blob) => {
          const url = URL.createObjectURL(blob);
          window.open(url, '_blank');
          setTimeout(() => URL.revokeObjectURL(url), 60000);
        },
        error: () => alert('Could not download that file.'),
      });
  }
}
