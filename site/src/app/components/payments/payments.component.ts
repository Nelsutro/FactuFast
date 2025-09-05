import { Component, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api.service';
import { Payment, Invoice, Client } from '../../models';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator, PageEvent } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';

@Component({
  selector: 'app-payments',
  templateUrl: './payments.component.html',
  styleUrls: ['./payments.component.css'],
  standalone: false
})
export class PaymentsComponent implements OnInit {
  
  // Data properties
  payments: Payment[] = [];
  filteredPayments: Payment[] = [];
  loading = true;
  error: string | null = null;

  // Table and pagination properties
  displayedColumns: string[] = ['invoice', 'client', 'amount', 'method', 'status', 'date', 'actions'];
  dataSource!: MatTableDataSource<Payment>;
  pageSize = 9;
  pageSizeOptions: number[] = [9, 18, 27];

  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;

  // Filter properties
  searchTerm = '';
  statusFilter = '';
  methodFilter = '';
  dateRange = '';

  // Stats
  stats = {
    monthlyPayments: 0,
    totalCollected: 0,
    pendingPayments: 0,
    failedPayments: 0
  };

  // Math property for template
  Math = Math;

  constructor(
    private apiService: ApiService,
    private router: Router
  ) {}

  ngOnInit() {
    this.loadPayments();
  }

  async loadPayments() {
    try {
      this.loading = true;
      this.error = null;

      // Simulate API call - Replace with real API call
      const response = await this.simulateApiCall();
      this.payments = response;
      
      this.calculateStats();
      this.filteredPayments = [...this.payments];
      this.dataSource = new MatTableDataSource(this.filteredPayments);
      this.dataSource.paginator = this.paginator;
      this.dataSource.sort = this.sort;

      // Custom filter predicate
      this.dataSource.filterPredicate = (data: Payment, filter: string) => {
        const term = filter.toLowerCase();
        return data.invoice?.invoice_number?.toLowerCase().includes(term) ||
               data.invoice?.client?.name?.toLowerCase().includes(term) ||
               data.invoice?.client?.email?.toLowerCase().includes(term) ||
               this.getMethodLabel(data.method).toLowerCase().includes(term);
      };

    } catch (error) {
      this.error = 'Error al cargar los pagos';
      console.error('Error loading payments:', error);
    } finally {
      this.loading = false;
    }
  }

  // Filtering methods
  applyFilter(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    this.searchTerm = filterValue.trim().toLowerCase();
    this.applyFilters();
  }

  applyStatusFilter(value: string) {
    this.statusFilter = value;
    this.applyFilters();
  }

  applyMethodFilter(value: string) {
    this.methodFilter = value;
    this.applyFilters();
  }

  applyDateFilter(value: string) {
    this.dateRange = value;
    this.applyFilters();
  }

  applyFilters() {
    let filtered = [...this.payments];

    // Apply search filter
    if (this.searchTerm) {
      const term = this.searchTerm.toLowerCase();
      filtered = filtered.filter(payment => 
        payment.invoice?.invoice_number?.toLowerCase().includes(term) ||
        payment.invoice?.client?.name?.toLowerCase().includes(term) ||
        payment.invoice?.client?.email?.toLowerCase().includes(term) ||
        this.getMethodLabel(payment.method).toLowerCase().includes(term)
      );
    }

    // Apply status filter
    if (this.statusFilter) {
      filtered = filtered.filter(payment => payment.status === this.statusFilter);
    }

    // Apply method filter
    if (this.methodFilter) {
      filtered = filtered.filter(payment => payment.method === this.methodFilter);
    }

    // Apply date range filter
    if (this.dateRange) {
      const now = new Date();
      const startDate = this.getStartDateForRange(this.dateRange, now);
      filtered = filtered.filter(payment => 
        new Date(payment.payment_date) >= startDate
      );
    }

    this.filteredPayments = filtered;
    if (this.dataSource) {
      this.dataSource.data = this.filteredPayments;
    }
  }

  // Material table helper methods
  getMethodIcon(method: string): string {
    switch (method) {
      case 'credit_card':
        return 'credit_card';
      case 'bank_transfer':
        return 'account_balance';
      case 'cash':
        return 'payments';
      default:
        return 'more_horiz';
    }
  }

  // Simulate API call - Replace with real apiService.getPayments()
  private simulateApiCall(): Promise<Payment[]> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const mockPayments: Payment[] = [
          {
            id: 1,
            invoice_id: 2,
            amount: 2850.00,
            payment_date: new Date('2024-01-20'),
            method: 'credit_card',
            status: 'completed',
            created_at: new Date('2024-01-20'),
            updated_at: new Date('2024-01-20'),
            invoice: {
              id: 2,
              company_id: 1,
              client_id: 2,
              invoice_number: '001235',
              amount: 2850.00,
              status: 'paid',
              issue_date: new Date('2024-01-10'),
              due_date: new Date('2024-02-10'),
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
            }
          },
          {
            id: 2,
            invoice_id: 5,
            amount: 1890.00,
            payment_date: new Date('2024-02-05'),
            method: 'bank_transfer',
            status: 'completed',
            created_at: new Date('2024-02-05'),
            updated_at: new Date('2024-02-05'),
            invoice: {
              id: 5,
              company_id: 1,
              client_id: 4,
              invoice_number: '001238',
              amount: 1890.00,
              status: 'paid',
              issue_date: new Date('2024-01-28'),
              due_date: new Date('2024-02-28'),
              created_at: new Date('2024-01-28'),
              updated_at: new Date('2024-02-05'),
              client: {
                id: 4,
                company_id: 1,
                name: 'Startup Innovadora',
                email: 'founders@startup.com',
                created_at: new Date(),
                updated_at: new Date()
              }
            }
          },
          {
            id: 3,
            invoice_id: 1,
            amount: 625.00, // Partial payment
            payment_date: new Date('2024-02-01'),
            method: 'credit_card',
            status: 'pending',
            created_at: new Date('2024-02-01'),
            updated_at: new Date('2024-02-01'),
            invoice: {
              id: 1,
              company_id: 1,
              client_id: 1,
              invoice_number: '001234',
              amount: 1250.00,
              status: 'pending',
              issue_date: new Date('2024-01-15'),
              due_date: new Date('2024-02-15'),
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
            }
          },
          {
            id: 4,
            invoice_id: 6,
            amount: 980.00,
            payment_date: new Date('2024-02-03'),
            method: 'bank_transfer',
            status: 'failed',
            created_at: new Date('2024-02-03'),
            updated_at: new Date('2024-02-03'),
            invoice: {
              id: 6,
              company_id: 1,
              client_id: 3,
              invoice_number: '001239',
              amount: 980.00,
              status: 'pending',
              issue_date: new Date('2024-02-01'),
              due_date: new Date('2024-03-01'),
              created_at: new Date('2024-02-01'),
              updated_at: new Date('2024-02-01'),
              client: {
                id: 3,
                company_id: 1,
                name: 'Tech Solutions Inc',
                email: 'billing@techsolutions.com',
                created_at: new Date(),
                updated_at: new Date()
              }
            }
          }
        ];
        resolve(mockPayments);
      }, 600);
    });
  }

  calculateStats() {
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();

    this.stats = {
      monthlyPayments: this.payments.filter(p => {
        const paymentDate = new Date(p.payment_date);
        return paymentDate.getMonth() === currentMonth && 
               paymentDate.getFullYear() === currentYear;
      }).length,
      totalCollected: this.payments
        .filter(p => p.status === 'completed')
        .reduce((sum, p) => sum + p.amount, 0),
      pendingPayments: this.payments.filter(p => p.status === 'pending').length,
      failedPayments: this.payments.filter(p => p.status === 'failed').length
    };
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
      default:
        return new Date(0);
    }
  }

  // Action methods
  recordPayment() {
    this.router.navigate(['/payments/create']);
  }

  viewPayment(payment: Payment) {
    this.router.navigate(['/payments', payment.id]);
  }

  processPayment(payment: Payment) {
    console.log('Processing payment:', payment.id);
    
    // Simulate payment processing
    payment.status = 'completed';
    payment.updated_at = new Date();
    this.calculateStats();
    
    alert(`Pago de $${this.formatCurrency(payment.amount)} procesado exitosamente`);
  }

  retryPayment(payment: Payment) {
    console.log('Retrying payment:', payment.id);
    
    // Simulate retry
    payment.status = 'pending';
    payment.updated_at = new Date();
    this.calculateStats();
    
    alert(`Reintentando pago de $${this.formatCurrency(payment.amount)}`);
  }

  downloadReceipt(payment: Payment) {
    console.log('Downloading receipt for payment:', payment.id);
    // Implement PDF receipt download
  }

  reconcilePayments() {
    console.log('Starting payment reconciliation...');
    // Navigate to reconciliation page or open modal
    // this.router.navigate(['/payments/reconcile']);
    alert('Función de conciliación bancaria - En desarrollo');
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
      'completed': 'Completado',
      'pending': 'Pendiente',
      'failed': 'Fallido'
    };
    return labels[status] || status;
  }

  getMethodLabel(method: string): string {
    const labels: { [key: string]: string } = {
      'credit_card': 'Tarjeta de Crédito',
      'bank_transfer': 'Transferencia',
      'cash': 'Efectivo',
      'other': 'Otro'
    };
    return labels[method] || method;
  }

  // Pagination handler
  onPageChange(event: PageEvent): void {
    if (this.dataSource.paginator) {
      this.dataSource.paginator.pageIndex = event.pageIndex;
      this.dataSource.paginator.pageSize = event.pageSize;
      this.pageSize = event.pageSize;
    }
  }

  trackByPaymentId(index: number, payment: Payment): number {
    return payment.id;
  }
}