import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { PendingRecruiter } from '../models/profile.model';
import { BackupItem, BackupListResponse, DashboardResponse, ReportResponse, ReportType } from '../models/admin.model';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class AdminService {
  constructor(private http: HttpClient) {}

  getRecruiters(status: string): Observable<{ items: PendingRecruiter[] }> {
    return this.http.get<any>(`${environment.apiUrl}/admin/recruiters.php?status=${status}`);
  }

  setRecruiterStatus(userId: number, status: 'verified' | 'rejected'): Observable<{ ok: boolean }> {
    return this.http.post<any>(`${environment.apiUrl}/admin/recruiters.php`, { user_id: userId, status });
  }

  deleteUser(userId: number): Observable<{ ok: boolean }> {
    return this.http.post<any>(`${environment.apiUrl}/admin/delete_user.php`, { user_id: userId });
  }

  getDashboard(days: number, role: string, search: string): Observable<DashboardResponse> {
    const query = new URLSearchParams();
    query.set('days', String(days));
    if (role) query.set('role', role);
    if (search) query.set('search', search);
    return this.http.get<DashboardResponse>(`${environment.apiUrl}/admin/dashboard.php?${query.toString()}`);
  }

  getReport(type: ReportType, from: string, to: string): Observable<ReportResponse> {
    const query = new URLSearchParams({ type });
    if (from) query.set('from', from);
    if (to) query.set('to', to);
    return this.http.get<ReportResponse>(`${environment.apiUrl}/admin/reports.php?${query.toString()}`);
  }

  listBackups(): Observable<BackupListResponse> {
    return this.http.get<BackupListResponse>(`${environment.apiUrl}/admin/backup.php`);
  }

  createBackup(): Observable<{ ok: boolean; backup: BackupItem }> {
    return this.http.post<any>(`${environment.apiUrl}/admin/backup.php`, { action: 'create' });
  }

  restoreBackup(name: string, confirm: string): Observable<{ ok: boolean; statements: number; files_restored: number; safety_backup: string }> {
    return this.http.post<any>(`${environment.apiUrl}/admin/backup.php`, { action: 'restore', name, confirm });
  }

  deleteBackup(name: string): Observable<{ ok: boolean }> {
    return this.http.post<any>(`${environment.apiUrl}/admin/backup.php`, { action: 'delete', name });
  }

  // the download needs the login token, so it goes through HttpClient instead of a plain link
  downloadBackup(name: string, kind: 'db' | 'files'): Observable<Blob> {
    return this.http.get(`${environment.apiUrl}/admin/backup.php?download=${encodeURIComponent(name)}&kind=${kind}`, { responseType: 'blob' });
  }
}
