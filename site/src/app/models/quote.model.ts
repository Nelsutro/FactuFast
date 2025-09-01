import { Company } from './company.model';
import { Client } from './client.model';

export interface Quote {
  id: number;
  company_id: number;
  client_id: number;
  quote_number: string;
  amount: number;
  status: 'draft' | 'sent' | 'accepted' | 'rejected';
  valid_until?: Date;
  created_at: Date;
  updated_at: Date;
  
  // Relaciones
  company?: Company;
  client?: Client;
}