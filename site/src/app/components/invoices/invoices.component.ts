import { Component, OnInit, ViewChild, AfterViewInit } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api.service';
import { Invoice } from '../../models';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatDialog } from '@angular/material/dialog';

@Component({
  selector: 'app-invoices',
  templateUrl: './invoices.component.html',
  styleUrls: ['./invoices.component.css'],
  standalone: false
})
export class InvoicesComponent implements OnInit, AfterViewInit {
  
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;

  // Data properties
  dataSource = new MatTableDataSource<Invoice>();
  originalData: Invoice[] = [];
  displayedColumns: string[] = ['invoice_number', 'client.name', 'amount', 'status', 'due_date', 'actions'];
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
    private router: Router,
    private dialog: MatDialog,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit() {
    this.loadInvoices();

    // Configurar la función de filtrado
    this.dataSource.filterPredicate = (data: Invoice, filter: string): boolean => {
      const searchStr = filter.toLowerCase();
      return data.invoice_number.toLowerCase().includes(searchStr) ||
             (data.client?.name?.toLowerCase().includes(searchStr) || false) ||
             (data.client?.email?.toLowerCase().includes(searchStr) || false);
    };
  }

  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
    this.dataSource.sort = this.sort;
  }

  async loadInvoices() {
    try {
      this.loading = true;
      this.error = null;

      // Simulate API call - Replace with real API call
      const response = await this.simulateApiCall();
      this.originalData = response;
      this.dataSource.data = response;
      this.calculateStats();

    } catch (error) {
      this.error = 'Error al cargar las facturas';
      console.error('Error loading invoices:', error);
      this.snackBar.open(this.error, 'Cerrar', {
        duration: 3000,
        horizontalPosition: 'end',
        verticalPosition: 'top'
      });
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
    const data = this.dataSource.data;
    this.stats = {
      total: data.length,
      pending: data.filter(i => i.status === 'pending').length,
      paid: data.filter(i => i.status === 'paid').length,
      totalAmount: data.reduce((sum, i) => sum + i.amount, 0)
    };
  }

  applyFilter(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    this.dataSource.filter = filterValue.trim().toLowerCase();

    if (this.dataSource.paginator) {
      this.dataSource.paginator.firstPage();
    }
  }

  applyStatusFilter(status: string) {
    this.statusFilter = status;
    this.dataSource.data = this.filteredData;
  }

  applyDateFilter(range: string) {
    this.dateRange = range;
    this.dataSource.data = this.filteredData;
  }

  private get filteredData(): Invoice[] {
    return this.originalData.filter(invoice => {
      const matchesStatus = !this.statusFilter || invoice.status === this.statusFilter;
      const matchesDate = !this.dateRange || this.isInDateRange(invoice.issue_date, this.dateRange);
      return matchesStatus && matchesDate;
    });
  }

  private isInDateRange(date: Date | string, range: string): boolean {
    const startDate = this.getStartDateForRange(range, new Date());
    return new Date(date) >= startDate;
  }

  private updateFilter() {
    this.dataSource.filterPredicate = (data: Invoice, filter: string): boolean => {
      return !filter || (data.invoice_number.toLowerCase().includes(filter) ||
             (data.client?.name?.toLowerCase().includes(filter) || false) ||
             (data.client?.email?.toLowerCase().includes(filter) || false));
    };

    // Trigger filtering
    this.dataSource.filter = this.searchTerm.trim().toLowerCase();
    
    if (this.dataSource.paginator) {
      this.dataSource.paginator.firstPage();
    }
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

  private applySorting(sort: { active: string; direction: string }) {
    if (this.dataSource.sort) {
      this.dataSource.sort.active = sort.active;
      this.dataSource.sort.direction = sort.direction === 'asc' ? 'asc' : 'desc';
    }
  }

  updatePagination(e: any) {
    this.pageSize = e.pageSize;
    this.currentPage = e.pageIndex + 1;
  }

  clearSort() {
    if (this.dataSource.sort) {
      this.dataSource.sort.active = '';
      this.dataSource.sort.direction = '';
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
      // Remove from data source
      const currentData = this.dataSource.data;
      this.dataSource.data = currentData.filter(i => i.id !== this.invoiceToDelete!.id);
      
      // Update stats and close modal
      this.calculateStats();
      this.showDeleteModal = false;
      this.invoiceToDelete = null;

      // Show success message
      this.snackBar.open('Factura eliminada', 'Cerrar', {
        duration: 3000,
        horizontalPosition: 'end',
        verticalPosition: 'top'
      });

      // In real implementation:
      // this.apiService.deleteInvoice(this.invoiceToDelete.id).subscribe({
      //   next: () => {
      //     this.loadInvoices();
      //   },
      //   error: (error) => {
      //     this.snackBar.open('Error al eliminar la factura', 'Cerrar', {
      //       duration: 3000,
      //       horizontalPosition: 'end',
      //       verticalPosition: 'top'
      //     });
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
