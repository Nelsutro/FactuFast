export interface ApiResponse<T = any> {
  success: boolean;
  message?: string;
  data?: T;
  errors?: { [key: string]: string[] };
  total?: number;
}

export interface AuthResponse {
  success: boolean;
  message?: string;
  data?: {
    user: User;
    token: string;
    token_type: string;
    expires_at: string;
  };
  errors?: { [key: string]: string[] };
}

export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  company_name?: string;
  email_verified_at?: string;
  created_at: string;
  updated_at: string;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface RegisterRequest {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  role?: string;
}

export interface DashboardStats {
  total_invoices: number;
  pending_invoices: number;
  paid_invoices: number;
  total_clients: number;
  total_revenue: number;
  pending_revenue: number;
  this_month_revenue: number;
  last_month_revenue: number;
  growth_percentage: number;
}

export interface RevenueData {
  labels: string[];
  datasets: {
    label: string;
    data: number[];
    backgroundColor: string;
    borderColor: string;
    borderWidth: number;
  }[];
}
