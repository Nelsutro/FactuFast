// src/app/models/invoice.model.ts
import { Company } from './company.model';
import { Client } from './client.model';
import { Payment } from './payment.model';

export interface Invoice {
  id: number;
  company_id: number;
  client_id: number;
  invoice_number: string;
  amount: number;
  status: 'pending' | 'paid' | 'cancelled';
  issue_date: Date;
  due_date: Date;
  notes?: string;
  created_at: Date;
  updated_at: Date;
  
  // Relaciones (opcionales cuando vienen del backend)
  company?: Company;
  client?: Client;
  payments?: Payment[];
}

// Permite representar una factura mínima embebida en otras respuestas (ej. pagos)
// Referencia liviana usada en pagos u otros listados donde sólo necesitamos
// algunos campos. Permitimos un client parcial (solo name, opcionalmente id).
export interface PartialInvoiceRef {
  invoice_number: string;
  id?: number;
  amount?: number;
  client?: Partial<Client> & { name?: string };
}