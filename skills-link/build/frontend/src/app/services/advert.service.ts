import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Advert, CreateAdvertPayload } from '../models/advert.model';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class AdvertService {
  constructor(private http: HttpClient) {}

  create(payload: CreateAdvertPayload): Observable<{ success: boolean; advertId: number }> {
    return this.http.post<any>(`${environment.apiUrl}/adverts/create.php`, payload);
  }

  list(params: { provinceId?: number; categoryId?: number; keyword?: string }): Observable<{ items: Advert[]; total: number }> {
    let query = new URLSearchParams();
    if (params.keyword) query.set('q', params.keyword);
    if (params.provinceId) query.set('province_id', String(params.provinceId));
    if (params.categoryId) query.set('category_id', String(params.categoryId));
    return this.http.get<{ items: Advert[]; total: number }>(`${environment.apiUrl}/adverts/list.php?${query.toString()}`);
  }
}
