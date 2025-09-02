import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api.service';
import { Invoice } from '../../models';

@Component({
  selector: 'app-invoices',
  templateUrl: './invoices.component.html',
  styleUrls: ['./invoices.component.css'],
  standalone: false
})
export class InvoicesComponent implements OnInit {
  
  // Data properties
  invoices: Invoice[] = [];
  filteredInvoices: Invoice[] = [];
  paginatedInvoices: Invoice[] = [];
  loading = true;
  error: string | null = null;

  // Filter properties
  searchTerm = '';
  statusFilter = '';
  dateRange = '';
  sortBy = 'created_at_desc';

  // View properties
  viewMode: 'table' | 'cards' = 'table';
  
  // Pagination properties
  currentPage = 1;
  pageSize = 10;
  totalPages = 1;

  // Stats
  stats = {
    total: 0,
    pending: 0,
    paid: 0,
    totalAmount: 0
  };

  // Modal properties
  showDeleteModal = false;
  invoiceToDelete: Invoice | null = null;

  // Math property for template
  Math = Math;

  constructor(
    private apiService: ApiService,
    private router: Router
  ) {}

  ngOnInit() {
    this.loadInvoices();
  }

  async loadInvoices() {
    try {
      this.loading = true;
      this.error = null;

      // Simulate API call - Replace with real API call
      const response = await this.simulateApiCall();
      this.invoices = response;
      
      this.calculateStats();
      this.applyFilters();

    } catch (error) {
      this.error = 'Error al cargar las facturas';
      console.error('Error loading invoices:', error);
    } finally {
      this.loading = false;
    }
  }

  // Simulate API call - Replace this with real apiService.getInvoices()
  private simulateApiCall(): Promise<Invoice[]> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const mockInvoices: Invoice[] = [
          {
            id: 1,
            company_id: 1,
            client_id: 1,
            invoice_number: '001234',
            amount: 1250.00,
            status: 'pending',
            issue_date: new Date('2024-01-15'),
            due_date: new Date('2024-02-15'),
            notes: 'Servicios de consultoría enero',
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
            invoice_number: '001235',
            amount: 2850.00,
            status: 'paid',
            issue_date: new Date('2024-01-10'),
            due_date: new Date('2024-02-10'),
            notes: 'Desarrollo de aplicación móvil',
            created_at: new Date('2024-01-10'),
            updated_at: new Date('2024-01-20'),
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
            invoice_number: '001236',
            amount: 750.00,
            status: 'pending',
            issue_date: new Date('2024-01-20'),
            due_date: new Date('2024-01-25'), // Overdue
            notes: 'Mantenimiento servidor',
            created_at: new Date('2024-01-20'),
            updated_at: new Date('2024-01-20'),
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
            client_id: 1,
            invoice_number: '001237',
            amount: 3200.00,
            status: 'cancelled',
            issue_date: new Date('2024-01-12'),
            due_date: new Date('2024-02-12'),
            notes: 'Proyecto cancelado por el cliente',
            created_at: new Date('2024-01-12'),
            updated_at: new Date('2024-01-25'),
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
            id: 5,
            company_id: 1,
            client_id: 4,
            invoice_number: '001238',
            amount: 1890.00,
            status: 'pending',
            issue_date: new Date(),
            due_date: new Date(Date.now() + 5 * 24 * 60 * 60 * 1000), // Due in 5 days
            notes: 'Diseño UI/UX aplicación web',
            created_at: new Date(),
            updated_at: new Date(),
            client: { 
              id: 4, 
              company_id: 1, 
              name: 'Startup Innovadora', 
              email: 'founders@startup.com',
              created_at: new Date(), 
              updated_at: new Date() 
            }
          }
        ];
        resolve(mockInvoices);
      }, 800);
    });
  }

  calculateStats() {
    this.stats = {
      total: this.invoices.length,
      pending: this.invoices.filter(i => i.status === 'pending').length,
      paid: this.invoices.filter(i => i.status === 'paid').length,
      totalAmount: this.invoices.reduce((sum, i) => sum + i.amount, 0)
    };
  }

  // Filtering and searching
  onSearchChange() {
    this.currentPage = 1;
    this.applyFilters();
  }

  applyFilters() {
    let filtered = [...this.invoices];

    // Apply search filter
    if (this.searchTerm) {
      const term = this.searchTerm.toLowerCase();
      filtered = filtered.filter(invoice => 
        invoice.invoice_number.toLowerCase().includes(term) ||
        invoice.client?.name?.toLowerCase().includes(term) ||
        invoice.client?.email?.toLowerCase().includes(term)
      );
    }

    // Apply status filter
    if (this.statusFilter) {
      filtered = filtered.filter(invoice => invoice.status === this.statusFilter);
    }

    // Apply date range filter
    if (this.dateRange) {
      const now = new Date();
      const startDate = this.getStartDateForRange(this.dateRange, now);
      filtered = filtered.filter(invoice => 
        new Date(invoice.issue_date) >= startDate
      );
    }

    this.filteredInvoices = filtered;
    this.applySorting();
    this.updatePagination();
  }

  private getStartDateForRange(range: string, now: Date): Date {
    const date = new Date(now);
    
    switch (range) {
      case 'today':
        date.setHours(0, 0, 0, 0);
        return date;
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
        return new Date(0); // Beginning of time
    }
  }

  applySorting() {
    const [field, direction] = this.sortBy.split('_');
    const isDesc = direction === 'desc';

    this.filteredInvoices.sort((a, b) => {
      let aValue: any;
      let bValue: any;

      switch (field) {
        case 'created':
          aValue = new Date(a.created_at).getTime();
          bValue = new Date(b.created_at).getTime();
          break;
        case 'amount':
          aValue = a.amount;
          bValue = b.amount;
          break;
        case 'due':
          aValue = new Date(a.due_date).getTime();
          bValue = new Date(b.due_date).getTime();
          break;
        default:
          return 0;
      }

      if (aValue < bValue) return isDesc ? 1 : -1;
      if (aValue > bValue) return isDesc ? -1 : 1;
      return 0;
    });

    this.updatePagination();
  }

  updatePagination() {
    this.totalPages = Math.ceil(this.filteredInvoices.length / this.pageSize);
    
    // Adjust current page if necessary
    if (this.currentPage > this.totalPages) {
      this.currentPage = Math.max(1, this.totalPages);
    }

    const startIndex = (this.currentPage - 1) * this.pageSize;
    const endIndex = startIndex + this.pageSize;
    this.paginatedInvoices = this.filteredInvoices.slice(startIndex, endIndex);
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
  createInvoice() {
    this.router.navigate(['/invoices/create']);
  }

  viewInvoice(invoice: Invoice) {
    this.router.navigate(['/invoices', invoice.id]);
  }

  editInvoice(invoice: Invoice) {
    this.router.navigate(['/invoices', invoice.id, 'edit']);
  }

  duplicateInvoice(invoice: Invoice) {
    // Create a copy of the invoice with new number
    const duplicated = {
      ...invoice,
      id: 0, // Will be assigned by backend
      invoice_number: '', // Will be auto-generated
      status: 'pending' as const,
      issue_date: new Date(),
      due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000) // 30 days from now
    };

    // Navigate to create form with pre-filled data
    this.router.navigate(['/invoices/create'], { 
      queryParams: { duplicate: invoice.id } 
    });
  }

  downloadInvoice(invoice: Invoice) {
    // Implement PDF download
    console.log('Downloading invoice:', invoice.invoice_number);
    // This would typically call a service to generate and download the PDF
    // this.apiService.downloadInvoicePDF(invoice.id).subscribe(...)
  }

  importInvoices() {
    this.router.navigate(['/invoices/import']);
  }

  deleteInvoice(invoice: Invoice) {
    this.invoiceToDelete = invoice;
    this.showDeleteModal = true;
  }

  confirmDelete() {
    if (this.invoiceToDelete) {
      // Implement actual deletion
      console.log('Deleting invoice:', this.invoiceToDelete.invoice_number);
      
      // Remove from local array (in real app, call API first)
      this.invoices = this.invoices.filter(i => i.id !== this.invoiceToDelete!.id);
      this.calculateStats();
      this.applyFilters();
      
      // Close modal
      this.showDeleteModal = false;
      this.invoiceToDelete = null;

      // In real implementation:
      // this.apiService.deleteInvoice(this.invoiceToDelete.id).subscribe({
      //   next: () => {
      //     this.loadInvoices(); // Reload the list
      //   },
      //   error: (error) => {
      //     this.error = 'Error al eliminar la factura';
      //   }
      // });
    }
  }

  cancelDelete() {
    this.showDeleteModal = false;
    this.invoiceToDelete = null;
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
      'pending': 'Pendiente',
      'paid': 'Pagada',
      'cancelled': 'Cancelada'
    };
    return labels[status] || status;
  }

  isOverdue(dueDate: Date | string): boolean {
    return new Date(dueDate) < new Date();
  }

  isDueSoon(dueDate: Date | string): boolean {
    const due = new Date(dueDate);
    const now = new Date();
    const diffDays = (due.getTime() - now.getTime()) / (1000 * 60 * 60 * 24);
    return diffDays > 0 && diffDays <= 7; // Due within 7 days
  }

  trackByInvoiceId(index: number, invoice: Invoice): number {
    return invoice.id;
  }
}
