import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface ClientPortalAccess {
  email: string;
  token: string;
}

export interface ClientInvoice {
  id: number;
  invoice_number: string;
  issue_date: string;
  due_date: string;
  total: number;
  status: string;
  remaining_amount: number;
  is_overdue: boolean;
  payments?: any[];
}

export interface ClientPortalResponse<T> {
  success: boolean;
  message: string;
  data?: T;
  access_link?: string;
}

export interface PaymentRequest {
  amount: number;
  payment_method: 'credit_card' | 'debit_card' | 'bank_transfer' | 'other';
  transaction_id?: string;
}

@Injectable({
  providedIn: 'root'
})
export class ClientPortalService {
  private readonly apiUrl = `${environment.apiUrl}/client-portal`;

  constructor(private http: HttpClient) {}

  /**
   * Solicitar acceso al portal con email
   */
  requestAccess(email: string): Observable<ClientPortalResponse<null>> {
    return this.http.post<ClientPortalResponse<null>>(`${this.apiUrl}/request-access`, { email });
  }

  /**
   * Acceder al portal con token
   */
  accessPortal(email: string, token: string): Observable<ClientPortalResponse<any>> {
    return this.http.post<ClientPortalResponse<any>>(`${this.apiUrl}/access`, { email, token });
  }

  /**
   * Obtener facturas del cliente
   */
  getInvoices(email: string, token: string): Observable<ClientPortalResponse<ClientInvoice[]>> {
    return this.http.get<ClientPortalResponse<ClientInvoice[]>>(`${this.apiUrl}/invoices`, {
      params: { email, token }
    });
  }

  /**
   * Obtener detalle de una factura
   */
  getInvoice(invoiceId: number, email: string, token: string): Observable<ClientPortalResponse<any>> {
    return this.http.get<ClientPortalResponse<any>>(`${this.apiUrl}/invoices/${invoiceId}`, {
      params: { email, token }
    });
  }

  /**
   * Pagar una factura
   */
  payInvoice(invoiceId: number, paymentData: PaymentRequest, email: string, token: string): Observable<ClientPortalResponse<any>> {
    return this.http.post<ClientPortalResponse<any>>(`${this.apiUrl}/invoices/${invoiceId}/pay`, paymentData, {
      params: { email, token }
    });
  }
}
