import { Invoice, PartialInvoiceRef } from './invoice.model';

export interface Payment {
  id: number;
  invoice_id: number;
  amount: number;
  payment_date: Date;
  method: 'credit_card' | 'bank_transfer' | 'cash' | 'other';
  status: 'completed' | 'pending' | 'failed';
  created_at: Date;
  updated_at: Date;
  
  // Relación
  invoice?: Invoice | PartialInvoiceRef;
}