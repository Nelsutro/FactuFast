import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { Invoice } from '../models/invoice.model';
import { environment } from '../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class InvoiceService {
  private apiUrl = `${environment.apiUrl}/invoices`;

  constructor(private http: HttpClient) { }

  // Get all invoices
  getInvoices(): Observable<Invoice[]> {
    return this.http.get<Invoice[]>(this.apiUrl).pipe(
      catchError(this.handleError<Invoice[]>('getInvoices', this.getMockInvoices()))
    );
  }

  // Get invoice by ID
  getInvoice(id: number): Observable<Invoice> {
    const url = `${this.apiUrl}/${id}`;
    return this.http.get<Invoice>(url).pipe(
      catchError(this.handleError<Invoice>('getInvoice', this.getMockInvoice()))
    );
  }

  // Create new invoice
  createInvoice(invoice: Partial<Invoice>): Observable<Invoice> {
    return this.http.post<Invoice>(this.apiUrl, invoice).pipe(
      catchError(this.handleError<Invoice>('createInvoice'))
    );
  }

  // Update invoice
  updateInvoice(id: number, invoice: Partial<Invoice>): Observable<Invoice> {
    const url = `${this.apiUrl}/${id}`;
    return this.http.put<Invoice>(url, invoice).pipe(
      catchError(this.handleError<Invoice>('updateInvoice'))
    );
  }

  // Delete invoice
  deleteInvoice(id: number): Observable<any> {
    const url = `${this.apiUrl}/${id}`;
    return this.http.delete(url).pipe(
      catchError(this.handleError('deleteInvoice'))
    );
  }

  // Get pending invoices
  getPendingInvoices(): Observable<Invoice[]> {
    return this.http.get<Invoice[]>(`${this.apiUrl}?status=pending`).pipe(
      catchError(this.handleError<Invoice[]>('getPendingInvoices', this.getMockPendingInvoices()))
    );
  }

  // Get overdue invoices
  getOverdueInvoices(): Observable<Invoice[]> {
    return this.http.get<Invoice[]>(`${this.apiUrl}?overdue=true`).pipe(
      catchError(this.handleError<Invoice[]>('getOverdueInvoices', this.getMockOverdueInvoices()))
    );
  }

  // Update invoice status
  updateInvoiceStatus(id: number, status: 'pending' | 'paid' | 'cancelled'): Observable<Invoice> {
    const url = `${this.apiUrl}/${id}/status`;
    return this.http.patch<Invoice>(url, { status }).pipe(
      catchError(this.handleError<Invoice>('updateInvoiceStatus'))
    );
  }

  // Search invoices
  searchInvoices(query: string): Observable<Invoice[]> {
    const url = `${this.apiUrl}/search?q=${encodeURIComponent(query)}`;
    return this.http.get<Invoice[]>(url).pipe(
      catchError(this.handleError<Invoice[]>('searchInvoices', []))
    );
  }

  // Get invoice statistics
  getInvoiceStats(): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/stats`).pipe(
      catchError(this.handleError('getInvoiceStats', this.getMockStats()))
    );
  }

  // Private helper methods
  private handleError<T>(operation = 'operation', result?: T) {
    return (error: any): Observable<T> => {
      console.error(`${operation} failed:`, error);
      return of(result as T);
    };
  }

  // Mock data methods (for development/fallback)
  private getMockInvoices(): Invoice[] {
    return [
      {
        id: 1,
        company_id: 1,
        client_id: 1,
        invoice_number: 'INV-2024-001',
        amount: 1500.00,
        status: 'pending',
        issue_date: new Date('2024-01-15'),
        due_date: new Date('2024-02-15'),
        notes: 'Servicios de consultoría',
        created_at: new Date('2024-01-15'),
        updated_at: new Date('2024-01-15')
      },
      {
        id: 2,
        company_id: 1,
        client_id: 2,
        invoice_number: 'INV-2024-002',
        amount: 2800.00,
        status: 'paid',
        issue_date: new Date('2024-01-20'),
        due_date: new Date('2024-02-20'),
        notes: 'Desarrollo de software',
        created_at: new Date('2024-01-20'),
        updated_at: new Date('2024-01-25')
      },
      {
        id: 3,
        company_id: 1,
        client_id: 3,
        invoice_number: 'INV-2024-003',
        amount: 950.00,
        status: 'pending',
        issue_date: new Date('2024-01-25'),
        due_date: new Date('2024-02-25'),
        notes: 'Mantenimiento mensual',
        created_at: new Date('2024-01-25'),
        updated_at: new Date('2024-01-25')
      }
    ];
  }

  private getMockInvoice(): Invoice {
    return this.getMockInvoices()[0];
  }

  private getMockPendingInvoices(): Invoice[] {
    return this.getMockInvoices().filter(inv => inv.status === 'pending');
  }

  private getMockOverdueInvoices(): Invoice[] {
    const now = new Date();
    return this.getMockInvoices().filter(inv => 
      inv.status === 'pending' && new Date(inv.due_date) < now
    );
  }

  private getMockStats(): any {
    return {
      total: 15,
      pending: 5,
      paid: 8,
      cancelled: 2,
      overdue: 3,
      totalAmount: 25000.00,
      pendingAmount: 8500.00,
      paidAmount: 16500.00
    };
  }
}
