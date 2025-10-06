import { Component, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar } from '@angular/material/snack-bar';
import { PortalPaymentService } from '../../services/portal-payment.service';
import { FormsModule } from '@angular/forms';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-public-pay',
  standalone: true,
  template: `
  <mat-card *ngIf="loaded && invoice; else loadingTmpl" class="public-pay-card">
    <h2>Factura {{ invoice.invoice_number }}</h2>
    <p>Monto: <strong>{{ invoice.total | currency:'CLP':'symbol-narrow':'1.0-0' }}</strong></p>
    <p>Estado: <span [ngClass]="invoice.status">{{ invoice.status }}</span></p>
    <p *ngIf="invoice.due_date">Vence: {{ invoice.due_date }}</p>
    <p *ngIf="expiredLink" class="warn">Enlace expirado</p>
    <div *ngIf="statusMessage" class="status-message" [ngClass]="{ success: paid, error: latestStatus === 'failed' }">
      {{ statusMessage }}
    </div>
    <div *ngIf="!paymentStarted && !invoice.is_paid && !expiredLink">
      <button mat-raised-button color="primary" (click)="startPayment()" [disabled]="starting">Pagar ahora</button>
    </div>
    <div *ngIf="paymentStarted && !completed">
      <p>Procesando intento de pago ({{ intentStatus || 'pendiente' }})...</p>
      <mat-progress-spinner diameter="40" mode="indeterminate"></mat-progress-spinner>
    </div>
    <div *ngIf="completed">
      <ng-container *ngIf="paid; else paymentFailed">
        <h3>✅ Pago confirmado</h3>
        <p>Gracias. Puedes cerrar esta ventana.</p>
      </ng-container>
      <ng-template #paymentFailed>
        <h3>⚠ No se confirmó el pago</h3>
        <p *ngIf="latestStatus">Estado reportado: {{ latestStatus }}</p>
        <button mat-stroked-button color="primary" (click)="retry()">Reintentar</button>
      </ng-template>
    </div>
  </mat-card>
  <ng-template #loadingTmpl>
    <div class="center">
      <mat-progress-spinner diameter="50" mode="indeterminate"></mat-progress-spinner>
      <p>Cargando enlace...</p>
    </div>
  </ng-template>
  `,
  styles: [`
    .public-pay-card { max-width: 420px; margin: 40px auto; display: block; }
    .center { text-align: center; margin-top: 80px; }
    .warn { color: #d32f2f; font-weight: 600; }
    .status-message { margin: 12px 0; font-weight: 600; }
    .status-message.success { color: #2e7d32; }
    .status-message.error { color: #c62828; }
  `],
  imports: [CommonModule, MatCardModule, MatButtonModule, MatProgressSpinnerModule, MatIconModule, FormsModule]
})
export class PublicPayComponent implements OnInit, OnDestroy {
  hash!: string;
  invoice: any;
  loaded = false;
  expiredLink = false;
  starting = false;
  paymentStarted = false;
  paymentId?: number;
  intentStatus?: string;
  completed = false;
  paid = false;
  provider = 'webpay';
  latestStatus: string | null = null;
  statusMessage = '';
  private pollSub?: Subscription;

  constructor(
    private route: ActivatedRoute,
    private snack: MatSnackBar,
    private portalPay: PortalPaymentService
  ) {}

  ngOnInit(): void {
    this.hash = this.route.snapshot.paramMap.get('hash')!;
    this.load();
  }

  ngOnDestroy(): void {
    this.stopPolling();
  }

  load() {
    this.portalPay.fetchPublicInvoice(this.hash).subscribe({
      next: res => {
        if (!res.success || !res.data) {
          this.snack.open('No disponible', 'Cerrar', { duration: 4000 });
          return;
        }
        this.invoice = res.data;
        this.loaded = true;
        if (Date.now()/1000 > res.data.expires_at) {
          this.expiredLink = true;
        }
        if (this.invoice.is_paid) {
          this.completed = true;
          this.paid = true;
          this.statusMessage = 'Esta factura ya se encuentra pagada.';
        }
      },
      error: err => {
        this.loaded = true;
        if (err.status === 410) this.expiredLink = true;
        this.snack.open(err.error?.message || 'Error cargando enlace', 'Cerrar', { duration: 4000 });
      }
    });
  }

  startPayment() {
    this.resetStates();
    this.starting = true;
    this.portalPay.initiatePublicPayment(this.hash, this.provider).subscribe({
      next: res => {
        this.starting = false;
        if (!res.success || !res.data) { this.snack.open(res.message || 'Error iniciando pago', 'Cerrar'); return; }
        this.paymentStarted = true;
        this.paymentId = res.data.payment_id;
        this.intentStatus = res.data.intent_status;
        this.latestStatus = res.data.status ?? null;

        if (res.data.is_paid) {
          this.markCompleted(true, res.data.status ?? 'paid');
          return;
        }

        if (res.data.redirect_url) {
          this.statusMessage = 'Redirigiendo al proveedor de pagos…';
          window.location.href = res.data.redirect_url;
          return;
        }

        this.statusMessage = 'Esperando confirmación del proveedor…';
        this.pollPublicPayment();
      },
      error: err => {
        this.starting = false;
        this.snack.open(err.error?.message || 'Error iniciando pago', 'Cerrar', { duration: 4000 });
      }
    });
  }

  retry() {
    this.resetStates();
    this.startPayment();
  }

  private pollPublicPayment() {
    if (!this.paymentId) {
      return;
    }

    this.stopPolling();
    this.pollSub = this.portalPay.pollPublicPaymentStatus(this.hash, this.paymentId).subscribe({
      next: resp => {
        if (!resp.success || !resp.data) {
          return;
        }

        const data = resp.data;
        this.intentStatus = data.intent_status;
        this.latestStatus = data.status;

        if (data.is_paid) {
          this.markCompleted(true, data.status);
          return;
        }

        if (data.status === 'failed') {
          this.markCompleted(false, data.status);
        }
      },
      error: err => {
        this.statusMessage = 'No fue posible verificar el estado del pago.';
        this.snack.open(err.error?.message || 'Error consultando estado de pago', 'Cerrar', { duration: 4000 });
        this.markCompleted(false, 'error');
      },
      complete: () => {
        if (!this.completed) {
          this.statusMessage = 'No se recibió confirmación del proveedor. Intenta nuevamente.';
        }
      }
    });
  }

  private markCompleted(isPaid: boolean, status?: string) {
    this.completed = true;
    this.paid = isPaid;
    this.paymentStarted = true;
    if (status) {
      this.latestStatus = status;
    }
    this.statusMessage = isPaid
      ? 'Pago confirmado correctamente.'
      : 'No fue posible confirmar el pago. Reintenta o contacta soporte.';
    this.stopPolling();
    this.invoice.status = isPaid ? 'paid' : this.invoice.status;
    this.invoice.is_paid = isPaid ? true : this.invoice.is_paid;
  }

  private resetStates() {
    this.stopPolling();
    this.paymentStarted = false;
    this.completed = false;
    this.paid = false;
    this.statusMessage = '';
    this.latestStatus = null;
  }

  private stopPolling() {
    if (this.pollSub) {
      this.pollSub.unsubscribe();
      this.pollSub = undefined;
    }
  }
}
