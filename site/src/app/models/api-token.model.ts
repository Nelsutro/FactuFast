export interface ApiTokenMetrics {
  total_requests: number;
  last_request_at: string | null;
  requests_last_7_days: number;
  last_error_at: string | null;
  last_error_status: number | null;
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