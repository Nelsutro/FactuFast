export interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'staff' | 'client';
  email_verified_at?: Date;
  created_at: Date;
  updated_at: Date;
}