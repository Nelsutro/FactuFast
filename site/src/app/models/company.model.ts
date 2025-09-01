export interface Company {
  id: number;
  name: string;
  tax_id: string;
  email?: string;
  phone?: string;
  address?: string;
  created_at: Date;
  updated_at: Date;
}