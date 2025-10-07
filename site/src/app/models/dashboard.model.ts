import { Invoice } from './invoice.model';

export interface DashboardStats {
  pending_invoices: number;
  paid_invoices: number;
  total_revenue: number;
  active_quotes: number;
  recent_invoices: Invoice[];
  revenue_chart: ChartData[];
  invoice_status_chart: StatusData[];
  import_metrics?: ImportMetrics | null;
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

export interface ImportMetrics {
  last_import_at?: string | null;
  recent_batches: number;
  rows_processed: number;
  success_rate?: number | null;
  error_rows: number;
  avg_duration_seconds?: number | null;
  pending_batches: number;
  failed_batches: number;
}