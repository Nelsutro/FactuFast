import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { MatDividerModule } from '@angular/material/divider';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { ApiService } from '../../services/api.service';
import { LoadingComponent } from '../shared/loading/loading.component';

@Component({
  selector: 'app-invoice-detail',
  standalone: true,
  templateUrl: './invoice-detail.component.html',
  styleUrls: ['./invoice-detail.component.css'],
  imports: [
    CommonModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatChipsModule,
    MatDividerModule,
    MatSnackBarModule,
    MatProgressSpinnerModule,
    LoadingComponent
  ]
})
export class InvoiceDetailComponent implements OnInit {
  invoice: any | null = null;
  loading = true;
  error: string | null = null;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private apiService: ApiService,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error = 'Identificador de factura inválido';
      this.loading = false;
      return;
    }
    this.loadInvoice(id);
  }

  private loadInvoice(id: number): void {
    this.loading = true;
    this.error = null;

    this.apiService.getInvoice(id).subscribe({
      next: (data) => {
        this.invoice = data;
        this.loading = false;
      },
      error: (err) => {
        this.error = err?.message || 'No fue posible cargar la factura';
        this.loading = false;
      }
    });
  }

  refresh(): void {
    if (!this.invoice?.id) {
      return;
    }
    this.loadInvoice(this.invoice.id);
  }

  goBack(): void {
    this.router.navigate(['/invoices']);
  }

  downloadPdf(): void {
    if (!this.invoice?.id) {
      return;
    }
    this.apiService.downloadInvoicePdf(this.invoice.id).subscribe({
      next: (blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `invoice_${this.invoice?.invoice_number || this.invoice?.id}.pdf`;
        a.click();
        window.URL.revokeObjectURL(url);
      },
      error: (err) => {
        this.snackBar.open(err?.message || 'No fue posible descargar el PDF', 'Cerrar', {
          duration: 3000
        });
      }
    });
  }

  formatCurrency(amount: number | string | null | undefined): string {
    const value = typeof amount === 'number' ? amount : parseFloat(amount ?? '0');
    return value.toLocaleString('es-CL', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    });
  }

  formatDate(value?: string | Date | null): string {
    if (!value) {
      return '—';
    }
    return new Date(value).toLocaleDateString('es-CL');
  }

  getStatusChipClass(status?: string): string {
    switch (status) {
      case 'paid':
        return 'status-chip paid';
      case 'pending':
        return 'status-chip pending';
      case 'overdue':
      case 'cancelled':
        return 'status-chip danger';
      default:
        return 'status-chip default';
    }
  }

  hasItems(): boolean {
    return Array.isArray(this.invoice?.items) && this.invoice!.items.length > 0;
  }

  hasPayments(): boolean {
    return Array.isArray(this.invoice?.payments) && this.invoice!.payments.length > 0;
  }
}
