import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, DashboardStats, RevenueData } from '../interfaces/api-response.interface';

@Injectable({
  providedIn: 'root'
})
export class DashboardService {
  private readonly apiUrl = `${environment.apiUrl}/dashboard`;

  constructor(private http: HttpClient) {}

  /**
   * Obtener estadísticas del dashboard
   */
  getStats(): Observable<ApiResponse<DashboardStats>> {
    return this.http.get<ApiResponse<DashboardStats>>(`${this.apiUrl}/stats`);
  }

  /**
   * Obtener datos de ingresos para gráficos
   */
  getRevenueData(period: string = 'monthly'): Observable<ApiResponse<RevenueData>> {
    return this.http.get<ApiResponse<RevenueData>>(`${this.apiUrl}/revenue`, {
      params: { period }
    });
  }
}
