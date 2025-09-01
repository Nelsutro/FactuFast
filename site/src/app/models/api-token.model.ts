import { User } from './user.model';

export interface ApiToken {
  id: number;
  user_id: number;
  token: string;
  provider?: string; // 'google', 'microsoft', 'apple'
  expires_at?: Date;
  created_at: Date;
  updated_at: Date;
  
  // Relación
  user?: User;
}