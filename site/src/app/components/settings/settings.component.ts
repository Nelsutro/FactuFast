import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { MatTabsModule } from '@angular/material/tabs';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatSlideToggleModule } from '@angular/material/slide-toggle';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatCardModule } from '@angular/material/card';
import { MatChipsModule } from '@angular/material/chips';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonToggleModule } from '@angular/material/button-toggle';
import { MatDividerModule } from '@angular/material/divider';
import { SettingsService, CompanySettings } from '../../core/services/settings.service';
import { ApiService } from '../../services/api.service';
import { ApiTokenSummary, ApiTokenLogsResponse, ApiTokenLogEntry, PaginationMeta } from '../../models';

@Component({
  selector: 'app-settings',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
  FormsModule,
    MatTabsModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatSlideToggleModule,
    MatIconModule,
    MatSnackBarModule,
    MatProgressSpinnerModule,
    MatCardModule,
    MatChipsModule,
  MatSelectModule,
  MatButtonToggleModule,
  MatDividerModule,
  ],
  templateUrl: './settings.component.html',
  styleUrls: ['./settings.component.css']
})
export class SettingsComponent implements OnInit {
  private fb = inject(FormBuilder);
  private settingsSvc = inject(SettingsService);
  private snackbar = inject(MatSnackBar);
  private apiSvc = inject(ApiService);

  loading = false;
  logoPreview: string | null = null;

  apiTokensLoading = false;
  apiTokens: ApiTokenSummary[] = [];
  selectedToken: ApiTokenSummary | null = null;
  tokenLogsLoading = false;
  tokenLogs: ApiTokenLogEntry[] = [];
  tokenLogsPagination: PaginationMeta | null = null;
  readonly tokenLogsPageSize = 25;
  readonly tokenLogMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
  readonly tokenLogTimeframes: Array<{ value: '1h' | '24h' | '7d' | 'all'; label: string }> = [
    { value: '1h', label: 'Última hora' },
    { value: '24h', label: 'Últimas 24h' },
    { value: '7d', label: 'Últimos 7 días' },
    { value: 'all', label: 'Todo' }
  ];
  readonly statusFamilyOptions: Array<{ value: '2xx' | '3xx' | '4xx' | '5xx' | 'all'; label: string }> = [
    { value: 'all', label: 'Todas' },
    { value: '2xx', label: '2xx Éxito' },
    { value: '3xx', label: '3xx Redirección' },
    { value: '4xx', label: '4xx Error cliente' },
    { value: '5xx', label: '5xx Error servidor' }
  ];
  readonly alertSeverityIcon: Record<'info' | 'warning' | 'danger', string> = {
    info: 'info',
    warning: 'warning',
    danger: 'error_outline'
  };

  tokenLogFilters = {
    onlyErrors: false,
    method: 'ALL' as 'ALL' | string,
    statusFamily: 'all' as 'all' | '2xx' | '3xx' | '4xx' | '5xx',
    statusFrom: null as number | null,
    statusTo: null as number | null,
    pathContains: '',
    ip: '',
    durationMin: null as number | null,
    durationMax: null as number | null,
    timeframe: '24h' as '1h' | '24h' | '7d' | 'all'
  };

  private readonly dateTimeFormatter = new Intl.DateTimeFormat('es-CL', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });

  companyForm = this.fb.group({
    name: ['', [Validators.required, Validators.maxLength(255)]],
    tax_id: ['', [Validators.required, Validators.maxLength(50)]],
    email: ['', [Validators.email]],
    phone: ['', [Validators.maxLength(20)]],
    address: ['', [Validators.maxLength(500)]],
  });

  billingForm = this.fb.group({
    currency_code: ['CLP', [Validators.required, Validators.maxLength(3)]],
    tax_rate: [0, [Validators.min(0), Validators.max(100)]],
    default_payment_terms: [''],
  });

  notificationsForm = this.fb.group({
    send_email_on_invoice: [true],
    send_email_on_payment: [true],
    portal_enabled: [true],
  });

  ngOnInit(): void {
    this.load();
    this.loadApiTokens();
  }

  load(): void {
    this.loading = true;
    this.settingsSvc.getSettings().subscribe({
      next: (res) => {
        const data = res.data as CompanySettings;
        if (!data) return;
        this.companyForm.patchValue({
          name: data.name,
          tax_id: data.tax_id,
          email: data.email || '',
          phone: data.phone || '',
          address: data.address || '',
        });
        this.billingForm.patchValue({
          currency_code: data.currency_code || 'CLP',
          tax_rate: Number(data.tax_rate || 0),
          default_payment_terms: data.default_payment_terms || '',
        });
        this.notificationsForm.patchValue({
          send_email_on_invoice: data.send_email_on_invoice ?? true,
          send_email_on_payment: data.send_email_on_payment ?? true,
          portal_enabled: data.portal_enabled ?? true,
        });
        this.logoPreview = data.logo_path || null;
      },
      error: () => {
        this.snackbar.open('No se pudo cargar la configuración', 'Cerrar', { duration: 3000 });
      },
      complete: () => (this.loading = false)
    });
  }

  saveCompany(): void {
    if (this.companyForm.invalid) return;
    const v = this.companyForm.value;
    const payload = {
      name: v.name ?? undefined,
      tax_id: v.tax_id ?? undefined,
      email: v.email ?? undefined,
      phone: v.phone ?? undefined,
      address: v.address ?? undefined,
    } as Partial<CompanySettings>;
    this.settingsSvc.updateSettings(payload).subscribe({
      next: () => this.snackbar.open('Datos de empresa guardados', 'Cerrar', { duration: 2000 }),
      error: () => this.snackbar.open('Error al guardar', 'Cerrar', { duration: 3000 }),
    });
  }

  saveBilling(): void {
    if (this.billingForm.invalid) return;
    // Normalizar tax_rate a número y quitar nulls
    const v = this.billingForm.value as any;
    const payload = {
      currency_code: (v.currency_code ?? undefined) as string | undefined,
      tax_rate: v.tax_rate != null ? Number(v.tax_rate) : undefined,
      default_payment_terms: (v.default_payment_terms ?? undefined) as string | undefined,
    } as Partial<CompanySettings>;
    this.settingsSvc.updateSettings(payload).subscribe({
      next: () => this.snackbar.open('Preferencias de facturación guardadas', 'Cerrar', { duration: 2000 }),
      error: () => this.snackbar.open('Error al guardar', 'Cerrar', { duration: 3000 }),
    });
  }

  saveNotifications(): void {
    const v = this.notificationsForm.value as any;
    const payload = {
      send_email_on_invoice: (v.send_email_on_invoice ?? undefined) as boolean | undefined,
      send_email_on_payment: (v.send_email_on_payment ?? undefined) as boolean | undefined,
      portal_enabled: (v.portal_enabled ?? undefined) as boolean | undefined,
    } as Partial<CompanySettings>;
    this.settingsSvc.updateSettings(payload).subscribe({
      next: () => this.snackbar.open('Preferencias de notificación guardadas', 'Cerrar', { duration: 2000 }),
      error: () => this.snackbar.open('Error al guardar', 'Cerrar', { duration: 3000 }),
    });
  }

  onLogoSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];
    this.settingsSvc.uploadLogo(file).subscribe({
      next: (res) => {
        const data = res.data as CompanySettings;
        this.logoPreview = data.logo_path || null;
        this.snackbar.open('Logo actualizado', 'Cerrar', { duration: 2000 });
      },
      error: () => this.snackbar.open('No se pudo subir el logo', 'Cerrar', { duration: 3000 }),
    });
  }

  refreshApiTokens(): void {
    this.loadApiTokens(true);
  }

  private loadApiTokens(force = false): void {
    if (this.apiTokensLoading && !force) {
      return;
    }

    this.apiTokensLoading = true;
    this.apiSvc.getApiTokens().subscribe({
      next: (tokens) => {
        this.apiTokens = tokens;
        if (!this.selectedToken || !this.apiTokens.some(t => t.id === this.selectedToken?.id)) {
          this.selectedToken = this.apiTokens[0] ?? null;
        } else {
          this.selectedToken = this.apiTokens.find(t => t.id === this.selectedToken?.id) ?? null;
        }

        if (this.selectedToken) {
          this.loadTokenLogs();
        } else {
          this.tokenLogs = [];
          this.tokenLogsPagination = null;
        }
      },
      error: () => {
        this.snackbar.open('No se pudieron cargar los tokens API', 'Cerrar', { duration: 3000 });
        this.apiTokensLoading = false;
      },
      complete: () => {
        this.apiTokensLoading = false;
      }
    });
  }

  selectToken(token: ApiTokenSummary): void {
    if (this.selectedToken?.id === token.id) {
      return;
    }
    this.selectedToken = token;
    this.tokenLogsPagination = null;
    this.resetTokenLogFilters();
    this.loadTokenLogs(1);
  }

  loadTokenLogs(page = 1): void {
    if (!this.selectedToken) return;

    this.tokenLogsLoading = true;
    const params: Record<string, string | number | boolean> = {
      page,
      per_page: this.tokenLogsPageSize,
    };

    const computedSince = this.computeSinceFromTimeframe();
    if (computedSince) {
      params['since'] = computedSince;
    }

    if (this.tokenLogFilters.onlyErrors) {
      params['only_errors'] = true;
    }

    if (this.tokenLogFilters.method !== 'ALL') {
      params['method'] = this.tokenLogFilters.method;
    }

    if (this.tokenLogFilters.statusFamily !== 'all') {
      params['status_family'] = this.tokenLogFilters.statusFamily;
    }

    if (this.tokenLogFilters.statusFrom != null) {
      params['status_from'] = this.tokenLogFilters.statusFrom;
    }

    if (this.tokenLogFilters.statusTo != null) {
      params['status_to'] = this.tokenLogFilters.statusTo;
    }

    if (this.tokenLogFilters.pathContains.trim().length > 0) {
      params['path_contains'] = this.tokenLogFilters.pathContains.trim();
    }

    if (this.tokenLogFilters.ip.trim().length > 0) {
      params['ip'] = this.tokenLogFilters.ip.trim();
    }

    if (this.tokenLogFilters.durationMin != null) {
      params['duration_min'] = this.tokenLogFilters.durationMin;
    }

    if (this.tokenLogFilters.durationMax != null) {
      params['duration_max'] = this.tokenLogFilters.durationMax;
    }

    this.apiSvc.getApiTokenLogs(this.selectedToken.id, params).subscribe({
      next: (response: ApiTokenLogsResponse) => {
        this.tokenLogs = response.logs;
        this.tokenLogsPagination = response.pagination;
        const updatedToken = this.apiTokens.find(token => token.id === response.token.id);
        if (updatedToken) {
          this.selectedToken = updatedToken;
        }
      },
      error: () => {
        this.snackbar.open('No se pudo obtener el historial de uso', 'Cerrar', { duration: 3000 });
        this.tokenLogsLoading = false;
      },
      complete: () => {
        this.tokenLogsLoading = false;
      }
    });
  }

  toggleOnlyErrors(): void {
    this.tokenLogFilters.onlyErrors = !this.tokenLogFilters.onlyErrors;
    if (this.tokenLogFilters.onlyErrors) {
      this.tokenLogFilters.statusFamily = '5xx';
      this.tokenLogFilters.statusFrom = 500;
      this.tokenLogFilters.statusTo = null;
    } else {
      this.tokenLogFilters.statusFamily = 'all';
      this.tokenLogFilters.statusFrom = null;
      this.tokenLogFilters.statusTo = null;
    }
    this.loadTokenLogs(1);
  }

  goToLogsPage(step: number): void {
    if (!this.tokenLogsPagination) return;
    const target = this.tokenLogsPagination.current_page + step;
    if (target < 1 || target > this.tokenLogsPagination.last_page) return;
    this.loadTokenLogs(target);
  }

  resetTokenLogFilters(): void {
    this.tokenLogFilters = {
      onlyErrors: false,
      method: 'ALL',
      statusFamily: 'all',
      statusFrom: null,
      statusTo: null,
      pathContains: '',
      ip: '',
      durationMin: null,
      durationMax: null,
      timeframe: '24h'
    };
  }

  applyTokenLogFilters(): void {
    this.loadTokenLogs(1);
  }

  clearAdvancedFilters(): void {
    const preserveOnlyErrors = this.tokenLogFilters.onlyErrors;
    this.resetTokenLogFilters();
    this.tokenLogFilters.onlyErrors = preserveOnlyErrors;
    this.loadTokenLogs(1);
  }

  get hasAdvancedFilters(): boolean {
    const filters = this.tokenLogFilters;
    return (
      filters.method !== 'ALL' ||
      filters.statusFamily !== 'all' ||
      filters.statusFrom != null ||
      filters.statusTo != null ||
      filters.pathContains.trim().length > 0 ||
      filters.ip.trim().length > 0 ||
      filters.durationMin != null ||
      filters.durationMax != null ||
      filters.timeframe !== '24h'
    );
  }

  setMethodFilter(method: string): void {
    this.tokenLogFilters.method = method as typeof this.tokenLogFilters.method;
    this.applyTokenLogFilters();
  }

  setStatusFamily(value: 'all' | '2xx' | '3xx' | '4xx' | '5xx'): void {
    this.tokenLogFilters.statusFamily = value;
    switch (value) {
      case 'all':
        this.tokenLogFilters.statusFrom = null;
        this.tokenLogFilters.statusTo = null;
        break;
      case '2xx':
        this.tokenLogFilters.statusFrom = 200;
        this.tokenLogFilters.statusTo = 299;
        break;
      case '3xx':
        this.tokenLogFilters.statusFrom = 300;
        this.tokenLogFilters.statusTo = 399;
        break;
      case '4xx':
        this.tokenLogFilters.statusFrom = 400;
        this.tokenLogFilters.statusTo = 499;
        break;
      case '5xx':
        this.tokenLogFilters.statusFrom = 500;
        this.tokenLogFilters.statusTo = 599;
        break;
    }
    this.applyTokenLogFilters();
  }

  setTimeframe(value: '1h' | '24h' | '7d' | 'all'): void {
    this.tokenLogFilters.timeframe = value;
    this.applyTokenLogFilters();
  }

  updateNumericFilter(
    key: 'statusFrom' | 'statusTo' | 'durationMin' | 'durationMax',
    value: string | number | null
  ): void {
    if (value === null || value === '') {
      this.tokenLogFilters[key] = null;
      return;
    }

    const parsed = Number(value);
    this.tokenLogFilters[key] = Number.isFinite(parsed) ? parsed : null;
  }

  private computeSinceFromTimeframe(): string | null {
    const { timeframe } = this.tokenLogFilters;
    if (!timeframe || timeframe === 'all') {
      return null;
    }

    const now = new Date();
    const since = new Date(now);
    switch (timeframe) {
      case '1h':
        since.setHours(now.getHours() - 1);
        break;
      case '24h':
        since.setHours(now.getHours() - 24);
        break;
      case '7d':
        since.setDate(now.getDate() - 7);
        break;
    }

    return since.toISOString();
  }

  trackTokenById(_: number, token: ApiTokenSummary): number {
    return token.id;
  }

  formatDate(value: string | null | undefined): string {
    if (!value) return '—';
    try {
      return this.dateTimeFormatter.format(new Date(value));
    } catch (error) {
      return value;
    }
  }

  statusLabel(status: number | null): string {
    if (status == null) return '—';
    if (status >= 500) return 'Error servidor';
    if (status >= 400) return 'Error cliente';
    if (status >= 300) return 'Redirección';
    if (status >= 200) return 'Éxito';
    return `${status}`;
  }
}
