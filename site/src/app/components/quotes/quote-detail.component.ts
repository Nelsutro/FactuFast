import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatDividerModule } from '@angular/material/divider';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { ApiService } from '../../services/api.service';
import { LoadingComponent } from '../shared/loading/loading.component';

@Component({
  selector: 'app-quote-detail',
  standalone: true,
  templateUrl: './quote-detail.component.html',
  styleUrls: ['./quote-detail.component.css'],
  imports: [
    CommonModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatChipsModule,
    MatSnackBarModule,
    MatDividerModule,
    MatProgressSpinnerModule,
    LoadingComponent
  ]
})
export class QuoteDetailComponent implements OnInit {
  quote: any | null = null;
  loading = true;
  error: string | null = null;
  converting = false;
  downloading = false;
  private autoNavigateToEdit = false;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private apiService: ApiService,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    const segments = this.route.snapshot.url.map(segment => segment.path);
    this.autoNavigateToEdit = segments.includes('edit');

    if (!id) {
      this.error = 'Identificador de cotización inválido';
      this.loading = false;
      return;
    }
    this.loadQuote(id);
  }

  private loadQuote(id: number): void {
    this.loading = true;
    this.error = null;
    this.apiService.getQuote(id).subscribe({
      next: (data) => {
        this.quote = data;
        this.loading = false;
        if (this.autoNavigateToEdit) {
          this.autoNavigateToEdit = false;
          setTimeout(() => this.startEdit(), 0);
        }
      },
      error: (err) => {
        this.error = err?.message || 'No fue posible cargar la cotización';
        this.loading = false;
      }
    });
  }

  refresh(): void {
    if (!this.quote?.id) {
      return;
    }
    this.loadQuote(this.quote.id);
  }

  goBack(): void {
    this.router.navigate(['/quotes']);
  }

  startEdit(): void {
    if (!this.quote?.id) {
      return;
    }
    this.autoNavigateToEdit = false;
    this.router.navigate(['/quotes/create'], {
      queryParams: { duplicate: this.quote.id, mode: 'edit' }
    });
  }

  convertToInvoice(): void {
    if (!this.quote?.id || this.converting) {
      return;
    }

    this.converting = true;
    this.apiService.convertQuoteToInvoice(this.quote.id).subscribe({
      next: (response) => {
        this.snackBar.open('Cotización enviada para facturación', 'Cerrar', { duration: 3000 });
        if (response?.data?.invoice_id) {
          this.router.navigate(['/invoices', response.data.invoice_id]);
        }
      },
      error: (err) => {
        this.snackBar.open(err?.message || 'No fue posible convertir la cotización', 'Cerrar', { duration: 3500 });
      },
      complete: () => {
        this.converting = false;
      }
    });
  }

  downloadPdf(): void {
    if (!this.quote?.id || this.downloading) {
      return;
    }

    this.downloading = true;
    this.apiService.downloadQuotePdf(this.quote.id).subscribe({
      next: (blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `quote_${this.quote?.quote_number || this.quote?.id}.pdf`;
        a.click();
        window.URL.revokeObjectURL(url);
        this.downloading = false;
      },
      error: (err) => {
        this.snackBar.open(err?.message || 'No fue posible descargar el PDF', 'Cerrar', {
          duration: 3000
        });
        this.downloading = false;
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

  getStatusClass(status?: string): string {
    switch (status) {
      case 'accepted':
        return 'status-chip accepted';
      case 'sent':
        return 'status-chip sent';
      case 'draft':
        return 'status-chip draft';
      case 'rejected':
        return 'status-chip rejected';
      default:
        return 'status-chip default';
    }
  }

  hasItems(): boolean {
    return Array.isArray(this.quote?.items) && this.quote!.items.length > 0;
  }
}
