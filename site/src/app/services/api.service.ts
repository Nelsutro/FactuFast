import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { AuthService } from './auth.service';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private apiUrl = 'http://localhost:8000/api';

  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) {}

  private getHeaders(): HttpHeaders {
    const token = this.authService.getToken();
    return new HttpHeaders({
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    });
  }

  // Métodos para Facturas
  getInvoices(): Observable<any> {
    return this.http.get(`${this.apiUrl}/invoices`, { headers: this.getHeaders() });
  }

  getInvoice(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/invoices/${id}`, { headers: this.getHeaders() });
  }

  createInvoice(invoice: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/invoices`, invoice, { headers: this.getHeaders() });
  }

  updateInvoice(id: number, invoice: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/invoices/${id}`, invoice, { headers: this.getHeaders() });
  }

  deleteInvoice(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/invoices/${id}`, { headers: this.getHeaders() });
  }

  // Métodos para Clientes
  getClients(): Observable<any> {
    return this.http.get(`${this.apiUrl}/clients`, { headers: this.getHeaders() });
  }

  getClient(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/clients/${id}`, { headers: this.getHeaders() });
  }

  createClient(client: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/clients`, client, { headers: this.getHeaders() });
  }

  updateClient(id: number, client: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/clients/${id}`, client, { headers: this.getHeaders() });
  }

  deleteClient(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/clients/${id}`, { headers: this.getHeaders() });
  }

  // Métodos para Cotizaciones
  getQuotes(): Observable<any> {
    return this.http.get(`${this.apiUrl}/quotes`, { headers: this.getHeaders() });
  }

  createQuote(quote: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/quotes`, quote, { headers: this.getHeaders() });
  }

  // Métodos para Pagos
  getPayments(): Observable<any> {
    return this.http.get(`${this.apiUrl}/payments`, { headers: this.getHeaders() });
  }

  // Métodos para Dashboard
  getDashboardStats(): Observable<any> {
    return this.http.get(`${this.apiUrl}/dashboard/stats`, { headers: this.getHeaders() });
  }

  getRevenueChart(): Observable<any> {
    return this.http.get(`${this.apiUrl}/dashboard/revenue`, { headers: this.getHeaders() });
  }
}