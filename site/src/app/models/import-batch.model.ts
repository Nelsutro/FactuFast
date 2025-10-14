export type ImportBatchStatus = 'pending' | 'processing' | 'completed' | 'failed';

export interface ImportBatch {
  id: number;
  type: string;
  status: ImportBatchStatus;
  alert_level: 'info' | 'success' | 'warning' | 'error';
  source_filename?: string;
  total_rows: number;
  processed_rows: number;
  success_count: number;
  error_count: number;
  started_at?: string | null;
  finished_at?: string | null;
  duration_seconds?: number | null;
  summary_message: string;
  meta?: Record<string, any> | null;
  notified_at?: string | null;
  last_error_message?: string | null;
  user?: {
    id: number;
    name: string;
    email: string;
  } | null;
  company?: {
    id: number;
    name: string;
  } | null;
  has_errors: boolean;
  download_errors_url?: string | null;
}
