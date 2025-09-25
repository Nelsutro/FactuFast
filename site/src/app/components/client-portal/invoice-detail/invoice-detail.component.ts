import { Component, OnInit, OnDestroy } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { MatTableModule } from '@angular/material/table';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ClientPortalService } from '../../../core/services/client-portal.service';
import { PortalPaymentService, InitiatePaymentResponse, PaymentStatusResponse } from '../../../services/portal-payment.service';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-invoice-detail',
  templateUrl: './invoice-detail.component.html',
  styleUrls: ['./invoice-detail.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatChipsModule,
    MatTableModule,
    MatProgressSpinnerModule
  ]
})
export class InvoiceDetailComponent implements OnInit, OnDestroy {
  invoice: any = null;
  loading = true;
  invoiceId!: number;
  displayedColumns: string[] = ['description', 'quantity', 'unit_price', 'total'];
  paying = false;
  paymentId?: number;
  paymentStatus?: string; // completed | pending | failed
  intentStatus?: string; // gateway intent status
  pollSub?: Subscription;
  provider = 'webpay';
  redirectUrl?: string | null;
  polling = false;
  pollStartedAt?: number;

  constructor(
    private clientPortalService: ClientPortalService,
    private portalPaymentService: PortalPaymentService,
    private router: Router,
    private route: ActivatedRoute,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit() {
    this.route.params.subscribe(params => {
      this.invoiceId = +params['id'];
      this.loadInvoice();
    });
  }

  ngOnDestroy() {
    this.stopPolling();
  }

  loadInvoice() {
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

  goBack() {
    this.router.navigate(['/client-portal/dashboard']);
  }

  payInvoice() {
    if (this.invoice?.status === 'paid' || this.paying) return;
    const email = localStorage.getItem('client_portal_email');
    const token = localStorage.getItem('client_portal_token');
    if (!email || !token) {
      this.router.navigate(['/client-portal/access']);
      return;
    }
    this.paying = true;
    this.portalPaymentService.initiatePortalInvoicePayment(this.invoiceId, this.provider, email, token)
      .subscribe({
        next: (resp: InitiatePaymentResponse) => {
          if (resp.success && resp.data) {
            this.paymentId = resp.data.payment_id;
            this.intentStatus = resp.data.intent_status;
            this.redirectUrl = resp.data.redirect_url ?? null;
            if (this.redirectUrl) {
              // Abrir en nueva pestaña (simulado)
              window.open(this.redirectUrl, '_blank');
            }
            this.startPolling();
          } else {
            this.snackBar.open(resp.message || 'No se pudo iniciar el pago', 'Cerrar', { duration: 3500 });
            this.paying = false;
          }
        },
        error: (err) => {
          console.error('Error iniciando pago', err);
          this.snackBar.open('Error iniciando pago', 'Cerrar', { duration: 3500 });
          this.paying = false;
        }
      });
  }

  private startPolling() {
    if (!this.paymentId) return;
    const email = localStorage.getItem('client_portal_email');
    const token = localStorage.getItem('client_portal_token');
    if (!email || !token) return;
    this.polling = true;
    this.pollStartedAt = Date.now();
    this.pollSub = this.portalPaymentService.pollPayment(this.paymentId, email, token, 3000, 180000)
      .subscribe({
        next: (statusResp: PaymentStatusResponse) => {
          if (statusResp.success && statusResp.data) {
            this.paymentStatus = statusResp.data.status;
            this.intentStatus = statusResp.data.intent_status;
            if (statusResp.data.is_paid || statusResp.data.status === 'completed') {
              this.finishPaymentSuccess();
            }
          }
        },
        error: (err) => {
          console.warn('Error polling pago', err);
        },
        complete: () => {
          this.polling = false;
          if (this.paymentStatus !== 'completed') {
            // refrescar estado de la factura por si cambió
            this.reloadInvoiceAfterAttempt();
            this.paying = false;
          }
        }
      });
  }

  private stopPolling() {
    if (this.pollSub) {
      this.pollSub.unsubscribe();
      this.pollSub = undefined;
    }
  }

  private finishPaymentSuccess() {
    this.snackBar.open('Pago completado', 'Cerrar', { duration: 3000 });
    this.stopPolling();
    this.reloadInvoiceAfterAttempt();
    this.paying = false;
  }

  private reloadInvoiceAfterAttempt() {
    // Pequeño delay para dar tiempo a que backend actualice invoice (si corresponde)
    setTimeout(() => this.loadInvoice(), 800);
  }

  showPayButton(): boolean {
    return !!this.invoice && this.invoice.status !== 'paid' && !this.paying;
  }

  getPaymentBadgeColor(): string {
    if (!this.paymentId) return 'default';
    if (this.paymentStatus === 'completed') return 'primary';
    if (this.intentStatus && ['processing','pending','created'].includes(this.intentStatus)) return 'accent';
    if (this.intentStatus && ['failed','canceled','expired','error'].includes(this.intentStatus)) return 'warn';
    return 'default';
  }

  getStatusColor(status: string): string {
    switch (status) {
      case 'paid': return 'success';
      case 'pending': return 'warning';
      case 'overdue': return 'danger';
      default: return 'default';
    }
  }

  getStatusText(status: string): string {
    switch (status) {
      case 'paid': return 'Pagada';
      case 'pending': return 'Pendiente';
      case 'overdue': return 'Vencida';
      default: return status;
    }
  }
}
