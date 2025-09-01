import { Company } from './company.model';

export interface Client {
  id: number;
  company_id: number;
  name: string;
  email?: string;
  phone?: string;
  address?: string;
  created_at: Date;
  updated_at: Date;
  
  // Relación
  company?: Company;
}