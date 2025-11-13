import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { Observable } from 'rxjs';

export interface FlowDirectPaymentRequest {
  amount: number;
  subject: string;
  email: string;
  invoice_id?: number;
  url_return?: string;
  optional?: any;
  timeout?: number;
}

export interface FlowCustomerRequest {
  name: string;
  email: string;
  external_id?: string;
}

export interface FlowCustomerPaymentRequest {
  customer_id: number;
  amount: number;
  subject: string;
  invoice_id?: number;
  url_return?: string;
}

export interface FlowPaymentResponse {
  success: boolean;
  data?: {
    payment_id: number;
    flow_order: number;
    token: string;
    redirect_url: string;
    status: string;
    created_at: string;
  };
  message?: string;
}

export interface FlowPaymentStatus {
  success: boolean;
  data?: {
    id: number;
    flow_order: number;
    status: string;
    payment_date?: string;
    flow_response: any;
  };
}

export interface FlowCustomer {
  id: number;
  flow_customer_id: string;
  name: string;
  email: string;
  external_id?: string;
  company_id: number;
  created_at: string;
  updated_at: string;
}

@Injectable({
  providedIn: 'root'
})
export class FlowPaymentService {
  private http = inject(HttpClient);
  private apiBase = environment.apiUrl;

  /**
   * Crear pago directo Flow (one-time payment)
   */
  createDirectPayment(paymentData: FlowDirectPaymentRequest): Observable<FlowPaymentResponse> {
    return this.http.post<FlowPaymentResponse>(`${this.apiBase}/flow/payments/direct`, paymentData);
  }

  /**
   * Obtener estado de un pago Flow
   */
  getPaymentStatus(paymentId: number): Observable<FlowPaymentStatus> {
    return this.http.get<FlowPaymentStatus>(`${this.apiBase}/flow/payments/${paymentId}/status`);
  }

  /**
   * Listar pagos Flow de la empresa actual
   */
  listFlowPayments(): Observable<{success: boolean, data: any[], message?: string}> {
    return this.http.get<{success: boolean, data: any[], message?: string}>(`${this.apiBase}/flow/payments`);
  }

  /**
   * Crear cliente Flow para pagos recurrentes
   */
  createFlowCustomer(customerData: FlowCustomerRequest): Observable<{success: boolean, data?: FlowCustomer, message?: string}> {
    return this.http.post<{success: boolean, data?: FlowCustomer, message?: string}>(`${this.apiBase}/flow/customers`, customerData);
  }

  /**
   * Cobrar a un cliente Flow existente
   */
  chargeFlowCustomer(paymentData: FlowCustomerPaymentRequest): Observable<FlowPaymentResponse> {
    return this.http.post<FlowPaymentResponse>(`${this.apiBase}/flow/customers/${paymentData.customer_id}/charge`, {
      amount: paymentData.amount,
      subject: paymentData.subject,
      invoice_id: paymentData.invoice_id,
      url_return: paymentData.url_return
    });
  }

  /**
   * Obtener lista de clientes Flow
   */
  getFlowCustomers(): Observable<{success: boolean, data: FlowCustomer[], message?: string}> {
    return this.http.get<{success: boolean, data: FlowCustomer[], message?: string}>(`${this.apiBase}/flow/customers`);
  }
}