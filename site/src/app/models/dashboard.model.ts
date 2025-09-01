import { Invoice } from './invoice.model';

export interface DashboardStats {
  pending_invoices: number;
  paid_invoices: number;
  total_revenue: number;
  active_quotes: number;
  recent_invoices: Invoice[];
  revenue_chart: ChartData[];
  invoice_status_chart: StatusData[];
}

export interface ChartData {
  month: string;
  revenue: number;
}

export interface StatusData {
  status: string;
  count: number;
  color: string;
}