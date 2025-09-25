import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { Observable, interval, switchMap, takeWhile, map } from 'rxjs';

export interface InitiatePaymentResponse {
  success: boolean;
  data?: {
    payment_id: number;
    provider_payment_id?: string;
    intent_status: string;
    redirect_url?: string | null;
  };
  message?: string;
}

export interface PaymentStatusResponse {
  success: boolean;
  data?: {
    id: number;
    status: string;
    intent_status: string;
    paid_at: string | null;
    is_paid: boolean;
  };
}

export interface PublicInvoiceData {
  success: boolean;
  data?: {
    invoice_id: number;
    invoice_number: string;
    status: string;
    due_date: string | null;
    total: number;
    company: { name?: string|null; tax_id?: string|null };
    is_paid: boolean;
    expires_at: number;
  };
}

@Injectable({ providedIn: 'root' })
export class PortalPaymentService {
  private http = inject(HttpClient);
  private apiBase = environment.apiUrl;

  initiatePortalInvoicePayment(invoiceId: number, provider = 'webpay', email: string, token: string) {
    return this.http.post<InitiatePaymentResponse>(`${this.apiBase}/client-portal/invoices/${invoiceId}/pay`, { provider }, {
      params: { email, token }
    });
  }

  getPortalPaymentStatus(paymentId: number, email: string, token: string) {
    return this.http.get<PaymentStatusResponse>(`${this.apiBase}/client-portal/payments/${paymentId}/status`, {
      params: { email, token }
    });
  }

  fetchPublicInvoice(hash: string) {
    return this.http.get<PublicInvoiceData>(`${this.apiBase}/public/pay/${hash}`);
  }

  initiatePublicPayment(hash: string, provider = 'webpay') {
    return this.http.post<InitiatePaymentResponse>(`${this.apiBase}/public/pay/${hash}/init`, { provider });
  }

  pollPayment(paymentId: number, email: string, token: string, intervalMs = 2000, maxMs = 60000) {
    const start = Date.now();
    return interval(intervalMs).pipe(
      switchMap(() => this.getPortalPaymentStatus(paymentId, email, token)),
      takeWhile(resp => {
        const elapsed = Date.now() - start;
        const done = resp.data?.is_paid || elapsed > maxMs;
        return !done;
      }, true)
    );
  }
}
