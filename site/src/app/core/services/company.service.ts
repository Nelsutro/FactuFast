import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse } from '../interfaces/api-response.interface';

export interface Company {
  id: number;
  name: string;
  tax_id: string;
  email?: string;
  phone?: string;
  address?: string;
  created_at: string;
  updated_at: string;
  // Relaciones (cuando se cargan)
  users?: any[];
  clients?: any[];
  invoices?: any[];
}

export interface CreateCompanyRequest {
  name: string;
  tax_id: string;
  email?: string;
  phone?: string;
  address?: string;
}

@Injectable({
  providedIn: 'root'
})
export class CompanyService {
  private readonly apiUrl = `${environment.apiUrl}/companies`;

  constructor(private http: HttpClient) {}

  /**
   * Obtener lista de empresas (SOLO ADMIN)
   */
  getCompanies(): Observable<ApiResponse<Company[]>> {
    return this.http.get<ApiResponse<Company[]>>(this.apiUrl);
  }

  /**
   * Obtener una empresa específica (SOLO ADMIN)
   */
  getCompany(id: number): Observable<ApiResponse<Company>> {
    return this.http.get<ApiResponse<Company>>(`${this.apiUrl}/${id}`);
  }

  /**
   * Crear una nueva empresa (SOLO ADMIN)
   */
  createCompany(companyData: CreateCompanyRequest): Observable<ApiResponse<Company>> {
    return this.http.post<ApiResponse<Company>>(this.apiUrl, companyData);
  }

  /**
   * Actualizar una empresa existente (SOLO ADMIN)
   */
  updateCompany(id: number, companyData: Partial<CreateCompanyRequest>): Observable<ApiResponse<Company>> {
    return this.http.put<ApiResponse<Company>>(`${this.apiUrl}/${id}`, companyData);
  }

  /**
   * Eliminar una empresa (SOLO ADMIN)
   */
  deleteCompany(id: number): Observable<ApiResponse<void>> {
    return this.http.delete<ApiResponse<void>>(`${this.apiUrl}/${id}`);
  }
}
