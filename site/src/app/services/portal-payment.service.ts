import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { interval, switchMap, takeWhile } from 'rxjs';

export interface InitiatePaymentResponse {
  success: boolean;
  data?: {
    payment_id: number;
    provider_payment_id?: string;
    intent_status: string;
    status?: string;
    paid_at?: string | null;
    is_paid?: boolean;
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

export interface PublicPaymentStatusResponse {
  success: boolean;
  data?: {
    id: number;
    status: string;
    intent_status: string;
    paid_at: string | null;
    is_paid: boolean;
  };
}

@Injectable({ providedIn: 'root' })
export class PortalPaymentService {
  private http = inject(HttpClient);
  private apiBase = environment.apiUrl;

  initiatePortalInvoicePayment(
    invoiceId: number,
    provider = 'webpay',
    email: string,
    token: string,
    options?: { returnUrl?: string }
  ) {
    const payload: Record<string, unknown> = { provider };
    if (options?.returnUrl) {
      payload['return_url'] = options.returnUrl;
    }
    return this.http.post<InitiatePaymentResponse>(`${this.apiBase}/client-portal/invoices/${invoiceId}/pay`, payload, {
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

  getPublicPaymentStatus(hash: string, paymentId: number) {
    return this.http.get<PublicPaymentStatusResponse>(`${this.apiBase}/public/pay/${hash}/status`, {
      params: { payment_id: paymentId }
    });
  }

  pollPublicPaymentStatus(hash: string, paymentId: number, intervalMs = 2500, maxMs = 120000) {
    const start = Date.now();
    return interval(intervalMs).pipe(
      switchMap(() => this.getPublicPaymentStatus(hash, paymentId)),
      takeWhile(response => {
        const elapsed = Date.now() - start;
        const done = response.data?.is_paid === true ||
          response.data?.status === 'failed' ||
          elapsed > maxMs;
        return !done;
      }, true)
    );
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
