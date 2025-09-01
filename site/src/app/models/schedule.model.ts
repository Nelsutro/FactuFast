import { Company } from './company.model';

export interface Schedule {
  id: number;
  company_id: number;
  task_name: string;
  frequency: 'daily' | 'weekly' | 'monthly' | 'yearly';
  execution_time?: string;
  config?: any;
  created_at: Date;
  updated_at: Date;
  
  // Relación
  company?: Company;
}