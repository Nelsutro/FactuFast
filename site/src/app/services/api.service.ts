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

export interface PaginatedResponse<T> {
  success: boolean; // Mantener compatibilidad con componentes que chequean success
  message?: string;
  data: T[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private apiUrl = environment.apiUrl;

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
  getInvoices(params?: any): Observable<PaginatedResponse<any>> {
    let url = `${this.apiUrl}/invoices`;
    if (params) {
      const queryParams = new URLSearchParams(params).toString();
      url += `?${queryParams}`;
    }
    return this.http.get<any>(url, { headers: this.getHeaders() })
      .pipe(
        map(response => ({
          success: response.success !== false,
            message: response.message,
            data: response.data ?? [],
            pagination: response.pagination ?? {
              current_page: 1,
              per_page: response.data?.length || 0,
              total: response.data?.length || 0,
              last_page: 1
            }
        } as PaginatedResponse<any>)),
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

  downloadInvoicePdf(id: number): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/invoices/${id}/pdf`, { headers: this.getHeaders(), responseType: 'blob' as 'json' }) as unknown as Observable<Blob>;
  }

  sendInvoiceEmail(id: number, payload: { to: string; cc?: string[]; subject: string; message: string; attach_pdf?: boolean; }): Observable<any> {
    return this.http.post<ApiResponse<any>>(`${this.apiUrl}/invoices/${id}/email`, payload, { headers: this.getHeaders() })
      .pipe(catchError(this.handleError));
  }

  getInvoiceStats(): Observable<any> {
    return this.http.get<ApiResponse<any>>(`${this.apiUrl}/invoices-stats`, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  exportInvoicesCsv(): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/invoices/export`, { headers: this.getHeaders(), responseType: 'blob' as 'json' }) as unknown as Observable<Blob>;
  }

  importInvoicesCsv(file: File): Observable<any> {
    const form = new FormData();
    form.append('file', file);
    const token = localStorage.getItem('auth_token');
    let headers = new HttpHeaders({ 'Accept': 'application/json' });
    if (token) headers = headers.set('Authorization', `Bearer ${token}`);
    return this.http.post<ApiResponse<any>>(`${this.apiUrl}/invoices/import`, form, { headers })
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

  exportClientsCsv(): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/clients/export`, { headers: this.getHeaders(), responseType: 'blob' as 'json' }) as unknown as Observable<Blob>;
  }

  importClientsCsv(file: File): Observable<any> {
    const form = new FormData();
    form.append('file', file);
    // No seteamos Content-Type manualmente para que el navegador aplique boundary
    const token = localStorage.getItem('auth_token');
    let headers = new HttpHeaders({ 'Accept': 'application/json' });
    if (token) headers = headers.set('Authorization', `Bearer ${token}`);
    return this.http.post<ApiResponse<any>>(`${this.apiUrl}/clients/import`, form, { headers })
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

  updateQuote(id: number, payload: any): Observable<any> {
    return this.http.put<ApiResponse<any>>(`${this.apiUrl}/quotes/${id}`, payload, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  getQuoteStats(): Observable<any> {
    return this.http.get<ApiResponse<any>>(`${this.apiUrl}/quotes-stats`, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  exportQuotesCsv(): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/quotes/export`, { headers: this.getHeaders(), responseType: 'blob' as 'json' }) as unknown as Observable<Blob>;
  }

  importQuotesCsv(file: File): Observable<any> {
    const form = new FormData();
    form.append('file', file);
    const token = localStorage.getItem('auth_token');
    let headers = new HttpHeaders({ 'Accept': 'application/json' });
    if (token) headers = headers.set('Authorization', `Bearer ${token}`);
    return this.http.post<ApiResponse<any>>(`${this.apiUrl}/quotes/import`, form, { headers })
      .pipe(catchError(this.handleError));
  }

  convertQuoteToInvoice(id: number): Observable<any> {
    return this.http.post<ApiResponse<any>>(`${this.apiUrl}/quotes/${id}/convert`, {}, { headers: this.getHeaders() })
      .pipe(
        map(response => response),
        catchError(this.handleError)
      );
  }

  // Métodos para Pagos
  getPayments(params?: any): Observable<PaginatedResponse<any>> {
    let url = `${this.apiUrl}/payments`;
    if (params) {
      const queryParams = new URLSearchParams(params).toString();
      url += `?${queryParams}`;
    }
    return this.http.get<any>(url, { headers: this.getHeaders() })
      .pipe(
        map(response => ({
          success: response.success !== false,
          message: response.message,
          data: response.data ?? [],
          pagination: response.pagination ?? {
            current_page: 1,
            per_page: response.data?.length || 0,
            total: response.data?.length || 0,
            last_page: 1
          }
        } as PaginatedResponse<any>)),
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

  getPaymentStats(): Observable<any> {
    return this.http.get<ApiResponse<any>>(`${this.apiUrl}/payments-stats`, { headers: this.getHeaders() })
      .pipe(
        map(response => response.data),
        catchError(this.handleError)
      );
  }

  getInvoicePayments(invoiceId: number): Observable<any> {
    return this.http.get<ApiResponse<any>>(`${this.apiUrl}/invoices/${invoiceId}/payments`, { headers: this.getHeaders() })
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