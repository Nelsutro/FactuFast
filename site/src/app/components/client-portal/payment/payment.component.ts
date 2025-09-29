import { Component, OnDestroy, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { Subscription } from 'rxjs';
import { ClientPortalService } from '../../../core/services/client-portal.service';
import { PortalPaymentService, InitiatePaymentResponse, PaymentStatusResponse } from '../../../services/portal-payment.service';

interface PaymentProviderOption {
  id: string;
  name: string;
  description: string;
  icon: string;
  badge?: string;
  available: boolean;
  comingSoon?: boolean;
}

const PROVIDER_CATALOG: PaymentProviderOption[] = [
  {
    id: 'webpay',
    name: 'Webpay',
    description: 'Paga al instante con tarjetas de crédito o débito chilenas.',
    icon: 'credit_card',
    badge: 'Recomendado',
    available: true
  },
  {
    id: 'mercadopago',
    name: 'Mercado Pago',
    description: 'Próximamente: billeteras digitales y cuotas flexibles.',
    icon: 'account_balance_wallet',
    comingSoon: true,
    available: false
  }
];

@Component({
  selector: 'app-payment',
  templateUrl: './payment.component.html',
  styleUrls: ['./payment.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule,
    MatSnackBarModule
  ]
})
export class PaymentComponent implements OnInit, OnDestroy {
  invoice: any = null;
  loading = true;
  processing = false;
  invoiceId!: number;

  providers: PaymentProviderOption[] = PROVIDER_CATALOG.map(option => ({ ...option }));
  selectedProvider: PaymentProviderOption | null = this.providers[0] ?? null;

  paymentId?: number;
  intentStatus?: string;
  paymentStatus?: string;
  redirectUrl?: string | null;
  polling = false;
  pollSub?: Subscription;
  paymentError?: string;

  constructor(
    private clientPortalService: ClientPortalService,
    private portalPaymentService: PortalPaymentService,
    private router: Router,
    private route: ActivatedRoute,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.invoiceId = +params['id'];
      this.loadInvoice();
    });
  }

  ngOnDestroy(): void {
    this.stopPolling();
  }

  private loadInvoice(): void {
    const email = localStorage.getItem('client_portal_email');
    const token = localStorage.getItem('client_portal_token');

    if (!email || !token) {
      this.router.navigate(['/client-portal/access']);
      return;
    }

    this.clientPortalService.getInvoice(this.invoiceId, email, token).subscribe({
      next: (response) => {
        if (response.success && response.data) {
          this.invoice = response.data;
          this.syncProvidersAvailability();
        }
        this.loading = false;
      },
      error: (error) => {
        console.error('Error cargando factura:', error);
        this.snackBar.open('Error al cargar la factura', 'Cerrar', { duration: 3000 });
        this.loading = false;
      }
    });
  }

  private syncProvidersAvailability(): void {
    const enabled = this.extractEnabledProviders();
    if (enabled.length) {
      this.providers = PROVIDER_CATALOG.map(option => ({
        ...option,
        available: enabled.includes(option.id)
      }));
    } else {
      // Mantener configuración por defecto (Webpay habilitado)
      this.providers = PROVIDER_CATALOG.map(option => ({ ...option }));
    }
    const firstAvailable = this.providers.find(p => p.available) ?? null;
    this.selectedProvider = firstAvailable;
  }

  private extractEnabledProviders(): string[] {
    const raw = this.invoice?.company?.payment_providers_enabled;
    if (!raw) {
      return [];
    }
    if (Array.isArray(raw)) {
      return raw;
    }
    if (typeof raw === 'string') {
      try {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) {
          return parsed;
        }
        if (typeof parsed === 'string') {
          return [parsed];
        }
      } catch (err) {
        // soporte para lista separada por comas
        return raw.split(',').map(s => s.trim()).filter(Boolean);
      }
    }
    return [];
  }

  selectProvider(option: PaymentProviderOption): void {
    if (!option.available) {
      this.snackBar.open('Este medio estará disponible pronto.', 'Cerrar', { duration: 3000 });
      return;
    }
    this.selectedProvider = option;
    this.paymentError = undefined;
  }

  startPayment(): void {
    if (!this.selectedProvider || !this.selectedProvider.available || this.processing) {
      return;
    }

    const email = localStorage.getItem('client_portal_email');
    const token = localStorage.getItem('client_portal_token');

    if (!email || !token) {
      this.router.navigate(['/client-portal/access']);
      return;
    }

    this.processing = true;
    this.paymentError = undefined;

    const returnUrl = `${window.location.origin}/client-portal/invoice/${this.invoiceId}?paid=1`;

    this.portalPaymentService
      .initiatePortalInvoicePayment(
        this.invoiceId,
        this.selectedProvider.id,
        email,
        token,
        { returnUrl }
      )
      .subscribe({
        next: (resp: InitiatePaymentResponse) => {
          if (resp.success && resp.data) {
            this.paymentId = resp.data.payment_id;
            this.intentStatus = resp.data.intent_status;
            this.redirectUrl = resp.data.redirect_url ?? null;
            this.snackBar.open('Redirigiendo al proveedor de pagos…', 'Cerrar', { duration: 3000 });
            this.startPolling(email, token);
            if (this.redirectUrl) {
              window.location.href = this.redirectUrl;
            }
          } else {
            this.paymentError = resp.message || 'No se pudo iniciar el pago.';
          }
        },
        error: (err) => {
          console.error('Error iniciando pago', err);
          this.paymentError = err.error?.message || 'Error al iniciar el pago.';
        },
        complete: () => {
          this.processing = false;
        }
      });
  }

  private startPolling(email: string, token: string): void {
    if (!this.paymentId) {
      return;
    }
    this.stopPolling();
    this.polling = true;
    this.pollSub = this.portalPaymentService
      .pollPayment(this.paymentId, email, token, 4000, 180000)
      .subscribe({
        next: (statusResp: PaymentStatusResponse) => {
          if (statusResp.success && statusResp.data) {
            this.paymentStatus = statusResp.data.status;
            this.intentStatus = statusResp.data.intent_status;
            if (statusResp.data.is_paid || statusResp.data.status === 'completed') {
              this.onPaymentCompleted();
            }
          }
        },
        error: (err) => {
          console.warn('Error verificando pago', err);
        },
        complete: () => {
          this.polling = false;
          if (this.paymentStatus !== 'completed') {
            this.reloadInvoiceAfterAttempt();
          }
        }
      });
  }

  private stopPolling(): void {
    if (this.pollSub) {
      this.pollSub.unsubscribe();
      this.pollSub = undefined;
    }
  }

  private onPaymentCompleted(): void {
    this.snackBar.open('Pago confirmado correctamente.', 'Cerrar', { duration: 3000 });
    this.stopPolling();
    this.reloadInvoiceAfterAttempt();
  }

  private reloadInvoiceAfterAttempt(): void {
    setTimeout(() => this.loadInvoice(), 800);
  }

  goBack(): void {
    this.router.navigate(['/client-portal/invoice', this.invoiceId]);
  }

  getStatusLabel(status?: string): string {
    switch (status) {
      case 'completed':
        return 'Completado';
      case 'failed':
        return 'Fallido';
      case 'cancelled':
        return 'Cancelado';
      case 'pending':
      case undefined:
        return 'Pendiente';
      default:
        return status ?? 'Pendiente';
    }
  }

  getStatusIcon(status?: string): string {
    switch (status) {
      case 'completed':
        return 'check_circle';
      case 'failed':
      case 'cancelled':
        return 'highlight_off';
      case 'pending':
      default:
        return 'pending';
    }
  }

  getStatusClasses(status?: string): string[] {
    const classes = ['status-badge'];
    switch (status) {
      case 'completed':
        classes.push('status-completed');
        break;
      case 'failed':
      case 'cancelled':
        classes.push('status-failed');
        break;
      default:
        classes.push('status-pending');
    }
    return classes;
  }
}
