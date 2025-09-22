import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse } from '../interfaces/api-response.interface';

export interface CompanySettings {
  id: number;
  name: string;
  tax_id: string;
  email?: string;
  phone?: string;
  address?: string;
  currency_code?: string;
  tax_rate?: number;
  default_payment_terms?: string | null;
  logo_path?: string | null;
  send_email_on_invoice?: boolean;
  send_email_on_payment?: boolean;
  portal_enabled?: boolean;
}

@Injectable({ providedIn: 'root' })
export class SettingsService {
  private readonly baseUrl = `${environment.apiUrl}`;

  constructor(private http: HttpClient) {}

  getSettings(): Observable<ApiResponse<CompanySettings>> {
    return this.http.get<ApiResponse<CompanySettings>>(`${this.baseUrl}/settings`);
  }

  updateSettings(payload: Partial<CompanySettings>): Observable<ApiResponse<CompanySettings>> {
    return this.http.put<ApiResponse<CompanySettings>>(`${this.baseUrl}/settings`, payload);
  }

  uploadLogo(file: File): Observable<ApiResponse<CompanySettings>> {
    const formData = new FormData();
    formData.append('logo', file);
    return this.http.post<ApiResponse<CompanySettings>>(`${this.baseUrl}/settings/logo`, formData);
  }
}
