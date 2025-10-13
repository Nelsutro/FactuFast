export interface ApiTokenMetrics {
  total_requests: number;
  last_request_at: string | null;
  requests_last_7_days: number;
  last_error_at: string | null;
  last_error_status: number | null;
  requests_last_short_window: number;
  errors_last_short_window: number;
  server_errors_last_short_window: number;
  error_rate_last_short_window: number;
  requests_last_long_window: number;
  errors_last_long_window: number;
}

export interface ApiTokenAlert {
  type: 'high_error_rate' | 'error_spike' | 'server_error_spike' | 'request_spike';
  severity: 'info' | 'warning' | 'danger';
  title: string;
  description: string;
  meta?: Record<string, any>;
}

export interface ApiTokenSummary {
  id: number;
  name: string;
  abilities: string[];
  rate_limit_per_minute: number;
  rate_limit_decay_seconds: number;
  created_at: string;
  last_used_at: string | null;
  expires_at: string | null;
  revoked: boolean;
  metrics: ApiTokenMetrics;
  alerts: ApiTokenAlert[];
}

export interface ApiTokenMeta {
  id: number;
  name: string;
  abilities: string[];
  revoked: boolean;
  last_used_at: string | null;
}

export interface ApiTokenLogEntry {
  id: number;
  ip: string | null;
  method: string;
  path: string;
  status_code: number | null;
  duration_ms: number | null;
  meta?: Record<string, any> | null;
  created_at: string | null;
}

export interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface ApiTokenLogsResponse {
  token: ApiTokenMeta;
  logs: ApiTokenLogEntry[];
  pagination: PaginationMeta;
}