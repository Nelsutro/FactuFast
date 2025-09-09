import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError, map } from 'rxjs/operators';
import { environment } from '../../environments/environment';

export interface ApiResponse<T> {
  data: T;
  message?: string;
  success: boolean;
  errors?: any;
}

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private apiUrl = environment.production ? environment.apiUrl : 'http://localhost:8000/api';

  constructor(private http: HttpClient) {}

  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('auth_token');
    let headers = new HttpHeaders({
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    });
    
    if (token) {
      headers = headers.set('Authorization', `Bearer ${token}`);
    }
    
    return headers;
  }

  private handleError(error: HttpErrorResponse) {
    let errorMessage = 'Ha ocurrido un error inesperado';
    
    if (error.error instanceof ErrorEvent) {
      // Error del lado del cliente
      errorMessage = `Error: ${error.error.message}`;
    } else {
      // Error del lado del servidor
      if (error.status === 401) {
        errorMessage = 'No tienes autorización para realizar esta acción';
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
      } else if (error.status === 403) {
        errorMessage = 'No tienes permisos para realizar esta acción';
      } else if (error.status === 404) {
        errorMessage = 'Recurso no encontrado';
      } else if (error.status === 422) {
        errorMessage = 'Datos de entrada inválidos';
      } else if (error.status === 500) {
        errorMessage = 'Error interno del servidor';
      } else if (error.error?.message) {
        errorMessage = error.error.message;
      }
    }
    
    return throwError(() => ({ message: errorMessage, status: error.status, error: error.error }));
  }

  // Métodos de Autenticación
  login(credentials: { email: string; password: string }): Observable<any> {
    return this.http.post<ApiResponse<any>>(`${this.apiUrl}/auth/login`, credentials)
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  register(userData: any): Observable<any> {
    return this.http.post<ApiResponse<any>>(`${this.apiUrl}/auth/register`, userData)
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  logout(): Observable<any> {
    return this.http.post(`${this.apiUrl}/auth/logout`, {}, { headers: this.getHeaders() })
      .pipe(catchError(this.handleError));
  }

  refreshToken(): Observable<any> {
    return this.http.post(`${this.apiUrl}/auth/refresh`, {}, { headers: this.getHeaders() })
      .pipe(
        map(response => response),
        catchError(this.handleError)
      );
  }

  getCurrentUser(): Observable<any> {
    return this.http.get<ApiResponse<any>>(`${this.apiUrl}/auth/user`, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  // Métodos para Facturas
  getInvoices(params?: any): Observable<any> {
    let url = `${this.apiUrl}/invoices`;
    if (params) {
      const queryParams = new URLSearchParams(params).toString();
      url += `?${queryParams}`;
    }
    return this.http.get<ApiResponse<any>>(url, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  getInvoice(id: number): Observable<any> {
    return this.http.get<ApiResponse<any>>(`${this.apiUrl}/invoices/${id}`, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  createInvoice(invoice: any): Observable<any> {
    return this.http.post<ApiResponse<any>>(`${this.apiUrl}/invoices`, invoice, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  updateInvoice(id: number, invoice: any): Observable<any> {
    return this.http.put<ApiResponse<any>>(`${this.apiUrl}/invoices/${id}`, invoice, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  deleteInvoice(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/invoices/${id}`, { headers: this.getHeaders() })
      .pipe(catchError(this.handleError));
  }

  // Métodos para Clientes
  getClients(params?: any): Observable<any> {
    let url = `${this.apiUrl}/clients`;
    if (params) {
      const queryParams = new URLSearchParams(params).toString();
      url += `?${queryParams}`;
    }
    return this.http.get<ApiResponse<any>>(url, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  getClient(id: number): Observable<any> {
    return this.http.get<ApiResponse<any>>(`${this.apiUrl}/clients/${id}`, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  createClient(client: any): Observable<any> {
    return this.http.post<ApiResponse<any>>(`${this.apiUrl}/clients`, client, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  updateClient(id: number, client: any): Observable<any> {
    return this.http.put<ApiResponse<any>>(`${this.apiUrl}/clients/${id}`, client, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  deleteClient(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/clients/${id}`, { headers: this.getHeaders() })
      .pipe(catchError(this.handleError));
  }

  // Métodos para Cotizaciones
  getQuotes(params?: any): Observable<any> {
    let url = `${this.apiUrl}/quotes`;
    if (params) {
      const queryParams = new URLSearchParams(params).toString();
      url += `?${queryParams}`;
    }
    return this.http.get<ApiResponse<any>>(url, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  createQuote(quote: any): Observable<any> {
    return this.http.post<ApiResponse<any>>(`${this.apiUrl}/quotes`, quote, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  // Métodos para Pagos
  getPayments(params?: any): Observable<any> {
    let url = `${this.apiUrl}/payments`;
    if (params) {
      const queryParams = new URLSearchParams(params).toString();
      url += `?${queryParams}`;
    }
    return this.http.get<ApiResponse<any>>(url, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  createPayment(payment: any): Observable<any> {
    return this.http.post<ApiResponse<any>>(`${this.apiUrl}/payments`, payment, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  // Métodos para Dashboard
  getDashboardStats(): Observable<any> {
    return this.http.get<ApiResponse<any>>(`${this.apiUrl}/dashboard/stats`, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  getRevenueChart(period?: string): Observable<any> {
    let url = `${this.apiUrl}/dashboard/revenue`;
    if (period) {
      url += `?period=${period}`;
    }
    return this.http.get<ApiResponse<any>>(url, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  // Métodos para Empresas
  getCompanies(): Observable<any> {
    return this.http.get<ApiResponse<any>>(`${this.apiUrl}/companies`, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  getCompany(id: number): Observable<any> {
    return this.http.get<ApiResponse<any>>(`${this.apiUrl}/companies/${id}`, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  updateCompany(id: number, company: any): Observable<any> {
    return this.http.put<ApiResponse<any>>(`${this.apiUrl}/companies/${id}`, company, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }
}