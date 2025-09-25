import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar } from '@angular/material/snack-bar';
import { PortalPaymentService } from '../../services/portal-payment.service';
import { FormsModule } from '@angular/forms';

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
    <div *ngIf="!paymentStarted && !invoice.is_paid && !expiredLink">
      <button mat-raised-button color="primary" (click)="startPayment()" [disabled]="starting">Pagar ahora</button>
    </div>
    <div *ngIf="paymentStarted && !completed">
      <p>Procesando intento de pago ({{ intentStatus }})...</p>
      <mat-progress-spinner diameter="40" mode="indeterminate"></mat-progress-spinner>
    </div>
    <div *ngIf="completed && paid">
      <h3>✅ Pago confirmado</h3>
      <p>Gracias. Puedes cerrar esta ventana.</p>
    </div>
    <div *ngIf="completed && !paid">
      <h3>⚠ No se confirmó el pago</h3>
      <button mat-stroked-button color="primary" (click)="retry()">Reintentar</button>
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
  `],
  imports: [CommonModule, MatCardModule, MatButtonModule, MatProgressSpinnerModule, MatIconModule, FormsModule]
})
export class PublicPayComponent implements OnInit {
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

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private snack: MatSnackBar,
    private portalPay: PortalPaymentService
  ) {}

  ngOnInit(): void {
    this.hash = this.route.snapshot.paramMap.get('hash')!;
    this.load();
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
      },
      error: err => {
        this.loaded = true;
        if (err.status === 410) this.expiredLink = true;
        this.snack.open(err.error?.message || 'Error cargando enlace', 'Cerrar', { duration: 4000 });
      }
    });
  }

  startPayment() {
    this.starting = true;
    this.portalPay.initiatePublicPayment(this.hash, this.provider).subscribe({
      next: res => {
        this.starting = false;
        if (!res.success || !res.data) { this.snack.open(res.message || 'Error iniciando pago', 'Cerrar'); return; }
        this.paymentStarted = true;
        this.paymentId = res.data.payment_id;
        this.intentStatus = res.data.intent_status;
        if (res.data.redirect_url) {
          window.location.href = res.data.redirect_url;
        } else {
          // Polling sólo aplica al flujo client-portal (requiere email/token). Para público necesitaríamos endpoint de estado público (futuro)
        }
        // Marcar completado simulado (hasta que exista webhook real)
        setTimeout(()=> { this.completed = true; this.paid = true; }, 3000);
      },
      error: err => {
        this.starting = false;
        this.snack.open(err.error?.message || 'Error iniciando pago', 'Cerrar', { duration: 4000 });
      }
    });
  }

  retry() {
    this.completed = false; this.paid = false; this.paymentStarted = false; this.startPayment();
  }
}
