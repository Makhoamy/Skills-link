import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { CreateReviewPayload } from '../models/review.model';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ReviewService {
  constructor(private http: HttpClient) {}

  create(payload: CreateReviewPayload): Observable<{ success: boolean }> {
    return this.http.post<any>(`${environment.apiUrl}/reviews/create.php`, payload);
  }
}
