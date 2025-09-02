import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api.service';
import { Quote, Client } from '../../models';

@Component({
  selector: 'app-quotes',
  templateUrl: './quotes.component.html',
  styleUrls: ['./quotes.component.css'],
  standalone: false
})
export class QuotesComponent implements OnInit {
  
  // Data properties
  quotes: Quote[] = [];
  filteredQuotes: Quote[] = [];
  paginatedQuotes: Quote[] = [];
  loading = true;
  error: string | null = null;

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
    private router: Router
  ) {}

  ngOnInit() {
    this.loadQuotes();
  }

  async loadQuotes() {
    try {
      this.loading = true;
      this.error = null;

      // Simulate API call - Replace with real API call
      const response = await this.simulateApiCall();
      this.quotes = response;
      
      this.calculateStats();
      this.applyFilters();

    } catch (error) {
      this.error = 'Error al cargar las cotizaciones';
      console.error('Error loading quotes:', error);
    } finally {
      this.loading = false;
    }
  }

  // Simulate API call - Replace with real apiService.getQuotes()
  private simulateApiCall(): Promise<Quote[]> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const mockQuotes: Quote[] = [
          {
            id: 1,
            company_id: 1,
            client_id: 1,
            quote_number: 'Q-2024-001',
            amount: 5200.00,
            status: 'sent',
            valid_until: new Date(Date.now() + 10 * 24 * 60 * 60 * 1000), // Valid for 10 more days
            created_at: new Date('2024-01-15'),
            updated_at: new Date('2024-01-15'),
            client: { 
              id: 1, 
              company_id: 1, 
              name: 'ABC Corp', 
              email: 'contacto@abccorp.com',
              created_at: new Date(), 
              updated_at: new Date() 
            }
          },
          {
            id: 2,
            company_id: 1,
            client_id: 2,
            quote_number: 'Q-2024-002',
            amount: 3800.00,
            status: 'accepted',
            valid_until: new Date(Date.now() + 5 * 24 * 60 * 60 * 1000), // Valid for 5 more days
            created_at: new Date('2024-01-20'),
            updated_at: new Date('2024-01-22'),
            client: { 
              id: 2, 
              company_id: 1, 
              name: 'XYZ Ltd', 
              email: 'admin@xyzltd.com',
              created_at: new Date(), 
              updated_at: new Date() 
            }
          },
          {
            id: 3,
            company_id: 1,
            client_id: 3,
            quote_number: 'Q-2024-003',
            amount: 1500.00,
            status: 'draft',
            valid_until: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000), // Valid for 30 more days
            created_at: new Date('2024-01-25'),
            updated_at: new Date('2024-01-25'),
            client: { 
              id: 3, 
              company_id: 1, 
              name: 'Tech Solutions Inc', 
              email: 'billing@techsolutions.com',
              created_at: new Date(), 
              updated_at: new Date() 
            }
          },
          {
            id: 4,
            company_id: 1,
            client_id: 4,
            quote_number: 'Q-2024-004',
            amount: 2250.00,
            status: 'rejected',
            valid_until: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000), // Expired 5 days ago
            created_at: new Date('2024-01-10'),
            updated_at: new Date('2024-01-18'),
            client: { 
              id: 4, 
              company_id: 1, 
              name: 'Startup Innovadora', 
              email: 'founders@startup.com',
              created_at: new Date(), 
              updated_at: new Date() 
            }
          },
          {
            id: 5,
            company_id: 1,
            client_id: 1,
            quote_number: 'Q-2024-005',
            amount: 4750.00,
            status: 'sent',
            valid_until: new Date(Date.now() + 2 * 24 * 60 * 60 * 1000), // Expires in 2 days
            created_at: new Date('2024-01-28'),
            updated_at: new Date('2024-01-28'),
            client: { 
              id: 1, 
              company_id: 1, 
              name: 'ABC Corp', 
              email: 'contacto@abccorp.com',
              created_at: new Date(), 
              updated_at: new Date() 
            }
          }
        ];
        resolve(mockQuotes);
      }, 700);
    });
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
  onSearchChange() {
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
    // Update quote status to 'sent'
    console.log('Sending quote:', quote.quote_number);
    
    // In real implementation, call API
    quote.status = 'sent';
    quote.updated_at = new Date();
    this.calculateStats();
    
    // Show success message (you can implement a toast service)
    alert(`Cotización #${quote.quote_number} enviada exitosamente`);
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
      
      // In real implementation, call API to create invoice from quote
      // this.apiService.convertQuoteToInvoice(this.quoteToConvert.id).subscribe({
      //   next: (newInvoice) => {
      //     this.router.navigate(['/invoices', newInvoice.id]);
      //   },
      //   error: (error) => {
      //     this.error = 'Error al convertir la cotización';
      //   }
      // });

      // For now, simulate the conversion
      this.quoteToConvert.status = 'accepted';
      this.quoteToConvert.updated_at = new Date();
      this.calculateStats();
      
      alert(`Cotización #${this.quoteToConvert.quote_number} convertida a factura exitosamente`);
      
      // Close modal
      this.showConvertModal = false;
      this.quoteToConvert = null;
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