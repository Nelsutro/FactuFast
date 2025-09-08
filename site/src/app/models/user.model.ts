export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  company_name?: string;
  email_verified_at?: Date | string;
  created_at: Date | string;
  updated_at: Date | string;
}