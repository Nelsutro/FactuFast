import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatCardModule } from '@angular/material/card';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatChipsModule } from '@angular/material/chips';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatPaginatorModule } from '@angular/material/paginator';
import { MatGridListModule } from '@angular/material/grid-list';
import { MatMenuModule } from '@angular/material/menu';
import { MatDividerModule } from '@angular/material/divider';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../core/services/auth.service';
import { Quote, Client, User } from '../../models';
import { LoadingComponent } from '../shared/loading/loading.component';

@Component({
  selector: 'app-quotes',
  templateUrl: './quotes.component.html',
  styleUrls: ['./quotes.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    MatButtonModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatCardModule,
    MatProgressSpinnerModule,
    MatChipsModule,
    MatTooltipModule,
    MatPaginatorModule,
    MatGridListModule,
    MatMenuModule,
    MatDividerModule,
    LoadingComponent
  ]
})
export class QuotesComponent implements OnInit {
  
  // Data properties
  quotes: Quote[] = [];
  filteredQuotes: Quote[] = [];
  paginatedQuotes: Quote[] = [];
  loading = true;
  error: string | null = null;
  currentUser: User | null = null;

  // Filter properties
  searchTerm = '';
  statusFilter = '';
  dateRange = '';

  // Pagination properties
  currentPage = 1;
  pageSize = 9; // 3x3 grid
  totalPages = 1;

  // Stats
  stats = {
    total: 0,
    sent: 0,
    accepted: 0,
    rejected: 0,
    totalValue: 0
  };

  // Modal properties
  showDeleteModal = false;
  showConvertModal = false;
  quoteToDelete: Quote | null = null;
  quoteToConvert: Quote | null = null;

  // Math property for template
  Math = Math;

  constructor(
    private apiService: ApiService,
    private authService: AuthService,
    private router: Router,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit() {
    this.loadUserData();
    this.loadQuotes();
  }

  private loadUserData() {
    this.authService.currentUser$.subscribe(user => {
      this.currentUser = user;
      if (this.currentUser && this.quotes.length === 0) {
        this.loadQuotes();
      }
    });
  }

  async loadQuotes() {
    try {
      this.loading = true;
      this.error = null;

      // Usar las rutas reales con autenticación
      this.apiService.getQuotes().subscribe({
        next: (response) => {
          console.log('Respuesta de la API:', response);
          if (response.success && response.data) {
            this.quotes = response.data.map((quote: any) => ({
              id: quote.id,
              quote_number: quote.quote_number,
              client: {
                name: quote.client?.name || 'Cliente desconocido',
                email: quote.client?.email || ''
              },
              amount: isNaN(parseFloat(quote.amount)) ? 0 : parseFloat(quote.amount),
              status: quote.status,
              quote_date: quote.quote_date,
              valid_until: quote.valid_until,
              notes: quote.notes,
              items: quote.items || [],
              created_at: quote.created_at || quote.quote_date,
              updated_at: quote.updated_at || quote.quote_date
            }));
            this.calculateStats();
            this.applyFilters();
          }
          this.loading = false;
        },
        error: (error) => {
          console.error('Error cargando cotizaciones:', error);
          this.error = 'Error al cargar las cotizaciones';
          this.loading = false;
        }
      });

    } catch (error) {
      this.error = 'Error al cargar las cotizaciones';
      console.error('Error loading quotes:', error);
      this.loading = false;
    }
  }

  calculateStats() {
    this.stats = {
      total: this.quotes.length,
      sent: this.quotes.filter(q => q.status === 'sent').length,
      accepted: this.quotes.filter(q => q.status === 'accepted').length,
      rejected: this.quotes.filter(q => q.status === 'rejected').length,
      totalValue: this.quotes.reduce((sum, q) => sum + q.amount, 0)
    };
  }

  // Filtering and searching
  applyFilter(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    this.searchTerm = filterValue.trim().toLowerCase();
    this.currentPage = 1;
    this.applyFilters();
  }

  applyStatusFilter(value: string) {
    this.statusFilter = value;
    this.currentPage = 1;
    this.applyFilters();
  }

  applyDateFilter(value: string) {
    this.dateRange = value;
    this.currentPage = 1;
    this.applyFilters();
  }

  applyFilters() {
    let filtered = [...this.quotes];

    // Apply search filter
    if (this.searchTerm) {
      const term = this.searchTerm.toLowerCase();
      filtered = filtered.filter(quote => 
        quote.quote_number.toLowerCase().includes(term) ||
        quote.client?.name?.toLowerCase().includes(term) ||
        quote.client?.email?.toLowerCase().includes(term)
      );
    }

    // Apply status filter
    if (this.statusFilter) {
      filtered = filtered.filter(quote => quote.status === this.statusFilter);
    }

    // Apply date range filter
    if (this.dateRange) {
      const now = new Date();
      const startDate = this.getStartDateForRange(this.dateRange, now);
      filtered = filtered.filter(quote => 
        new Date(quote.created_at) >= startDate
      );
    }

    this.filteredQuotes = filtered;
    this.updatePagination();
  }

  private getStartDateForRange(range: string, now: Date): Date {
    const date = new Date(now);
    
    switch (range) {
      case 'week':
        date.setDate(date.getDate() - 7);
        return date;
      case 'month':
        date.setMonth(date.getMonth() - 1);
        return date;
      case 'quarter':
        date.setMonth(date.getMonth() - 3);
        return date;
      default:
        return new Date(0);
    }
  }

  updatePagination() {
    this.totalPages = Math.ceil(this.filteredQuotes.length / this.pageSize);
    
    if (this.currentPage > this.totalPages) {
      this.currentPage = Math.max(1, this.totalPages);
    }

    const startIndex = (this.currentPage - 1) * this.pageSize;
    const endIndex = startIndex + this.pageSize;
    this.paginatedQuotes = this.filteredQuotes.slice(startIndex, endIndex);
  }

  // Pagination methods
  previousPage() {
    if (this.currentPage > 1) {
      this.currentPage--;
      this.updatePagination();
    }
  }

  nextPage() {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
      this.updatePagination();
    }
  }

  // Action methods
  createQuote() {
    this.router.navigate(['/quotes/create']);
  }

  viewQuote(quote: Quote) {
    this.router.navigate(['/quotes', quote.id]);
  }

  editQuote(quote: Quote) {
    this.router.navigate(['/quotes', quote.id, 'edit']);
  }

  sendQuote(quote: Quote) {
    // Actualizar estado real via API
    this.apiService.updateQuote(quote.id, { status: 'sent' }).subscribe({
      next: (updated) => {
        quote.status = updated?.status || 'sent';
        quote.updated_at = new Date();
        this.calculateStats();
        this.applyFilters();
        this.snackBar.open(`Cotización #${quote.quote_number} enviada`, 'Cerrar', { duration: 3000 });
      },
      error: (err) => {
        this.snackBar.open(err?.message || 'No se pudo enviar la cotización', 'Cerrar', { duration: 3500 });
      }
    });
  }

  duplicateQuote(quote: Quote) {
    this.router.navigate(['/quotes/create'], { 
      queryParams: { duplicate: quote.id } 
    });
  }

  downloadQuote(quote: Quote) {
    console.log('Downloading quote:', quote.quote_number);
    // Implement PDF download
    // this.apiService.downloadQuotePDF(quote.id).subscribe(...)
  }

  convertToInvoice(quote: Quote) {
    this.quoteToConvert = quote;
    this.showConvertModal = true;
  }

  confirmConvert() {
    if (this.quoteToConvert) {
      console.log('Converting quote to invoice:', this.quoteToConvert.quote_number);
      // Llamar API para preparar conversión
      this.apiService.convertQuoteToInvoice(this.quoteToConvert.id).subscribe({
        next: (res) => {
          // Si el backend solo prepara datos, navegamos a crear factura con query params o estado
          const invoiceTemplate = res?.data?.invoice_template;
          if (invoiceTemplate) {
            // Redirigir al creador de facturas (si hay ruta standalone, podemos pasar estado o query)
            this.router.navigate(['/invoices/new'], { queryParams: { fromQuote: this.quoteToConvert!.id } });
          }
          // Opcional: actualizar estado local si backend lo cambia
          this.quoteToConvert!.status = 'accepted';
          this.quoteToConvert!.updated_at = new Date();
          this.calculateStats();
          this.applyFilters();
          this.snackBar.open(`Cotización #${this.quoteToConvert!.quote_number} lista para facturar`, 'Cerrar', { duration: 3000 });
        },
        error: (err) => {
          this.snackBar.open(err?.message || 'Error al convertir la cotización', 'Cerrar', { duration: 3500 });
        },
        complete: () => {
          this.showConvertModal = false;
          this.quoteToConvert = null;
        }
      });
    }
  }

  cancelConvert() {
    this.showConvertModal = false;
    this.quoteToConvert = null;
  }

  deleteQuote(quote: Quote) {
    this.quoteToDelete = quote;
    this.showDeleteModal = true;
  }

  confirmDelete() {
    if (this.quoteToDelete) {
      console.log('Deleting quote:', this.quoteToDelete.quote_number);
      
      // Remove from local array (in real app, call API first)
      this.quotes = this.quotes.filter(q => q.id !== this.quoteToDelete!.id);
      this.calculateStats();
      this.applyFilters();
      
      // Close modal
      this.showDeleteModal = false;
      this.quoteToDelete = null;
    }
  }

  cancelDelete() {
    this.showDeleteModal = false;
    this.quoteToDelete = null;
  }

  // CSV Export/Import
  exportQuotes() {
    this.apiService.exportQuotesCsv().subscribe({
      next: (blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `quotes_export_${new Date().toISOString().slice(0,19).replace(/[:T]/g,'-')}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
      },
      error: (err) => {
        this.snackBar.open(err.message || 'Error al exportar CSV', 'Cerrar', { duration: 3000 });
      }
    });
  }

  importQuotes() {
    const input = document.getElementById('quotesCsvInput') as HTMLInputElement | null;
    if (input) input.click();
  }

  onQuotesFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    this.snackBar.open('Importando cotizaciones...', undefined, { duration: 1500 });
    this.apiService.importQuotesCsv(file).subscribe({
      next: (res) => {
        this.snackBar.open(`Importación completa: ${res.data?.created ?? 0} creadas, ${res.data?.skipped ?? 0} omitidas`, 'Cerrar', { duration: 3500 });
        this.loadQuotes();
        input.value = '';
      },
      error: (err) => {
        this.snackBar.open(err.message || 'Error al importar CSV', 'Cerrar', { duration: 3500 });
        input.value = '';
      }
    });
  }

  // Utility methods
  formatCurrency(amount: number): string {
    return amount.toLocaleString('es-CL', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    });
  }

  formatDate(date: Date | string): string {
    const d = new Date(date);
    return d.toLocaleDateString('es-CL', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit'
    });
  }

  getStatusLabel(status: string): string {
    const labels: { [key: string]: string } = {
      'draft': 'Borrador',
      'sent': 'Enviada',
      'accepted': 'Aceptada',
      'rejected': 'Rechazada'
    };
    return labels[status] || status;
  }

  isExpiringSoon(validUntil: Date | string | undefined): boolean {
    if (!validUntil) return false;
    const due = new Date(validUntil);
    const now = new Date();
    const diffDays = (due.getTime() - now.getTime()) / (1000 * 60 * 60 * 24);
    return diffDays > 0 && diffDays <= 3; // Expires within 3 days
  }

  isExpired(validUntil: Date | string | undefined): boolean {
    if (!validUntil) return false;
    return new Date(validUntil) < new Date();
  }

  trackByQuoteId(index: number, quote: Quote): number {
    return quote.id;
  }
}