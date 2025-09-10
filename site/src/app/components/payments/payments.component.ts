import { Component, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { MatTableModule, MatTableDataSource } from '@angular/material/table';
import { MatPaginatorModule, MatPaginator } from '@angular/material/paginator';
import { MatSortModule, MatSort } from '@angular/material/sort';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatCardModule } from '@angular/material/card';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatChipsModule } from '@angular/material/chips';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatGridListModule } from '@angular/material/grid-list';
import { MatMenuModule } from '@angular/material/menu';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../core/services/auth.service';
import { Payment, User } from '../../models';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-payments',
  templateUrl: './payments.component.html',
  styleUrls: ['./payments.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    MatTableModule,
    MatPaginatorModule,
    MatSortModule,
    MatButtonModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatCardModule,
    MatProgressSpinnerModule,
    MatChipsModule,
    MatTooltipModule,
    MatGridListModule,
    MatMenuModule
  ]
})
export class PaymentsComponent implements OnInit {
  
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;

  displayedColumns: string[] = ['invoice', 'client', 'amount', 'method', 'status', 'date', 'actions'];
  dataSource = new MatTableDataSource<Payment>();
  
  payments: Payment[] = [];
  filteredPayments: Payment[] = [];
  searchTerm: string = '';
  methodFilter: string = '';
  dateFilter: string = '';
  statusFilter: string = '';
  dateRange: string = '';
  pageSize: number = 10;
  loading = true;
  error: string | null = null;
  currentUser: User | null = null;

  stats = {
    total: 0,
    totalAmount: 0,
    cash: 0,
    card: 0,
    transfer: 0,
    thisMonth: 0,
    monthlyPayments: 0,
    totalCollected: 0,
    pendingPayments: 0,
    failedPayments: 0
  };

  paymentMethods = [
    { value: 'cash', label: 'Efectivo', icon: 'account_balance_wallet' },
    { value: 'credit_card', label: 'Tarjeta', icon: 'credit_card' },
    { value: 'bank_transfer', label: 'Transferencia', icon: 'account_balance' },
    { value: 'other', label: 'Otro', icon: 'more_horiz' }
  ];

  constructor(
    private apiService: ApiService,
    private authService: AuthService,
    private router: Router,
    private http: HttpClient
  ) {}

  ngOnInit() {
    this.loadUserData();
    this.loadPayments();
  }

  private loadUserData() {
    this.authService.currentUser$.subscribe(user => {
      this.currentUser = user;
    });
  }

  async loadPayments() {
    try {
      this.loading = true;
      this.error = null;

      // Usar las rutas reales con autenticación
      this.apiService.getPayments().subscribe({
        next: (response) => {
          console.log('Respuesta de pagos API:', response);
          if (response.success && response.data) {
            this.payments = response.data.map((payment: any) => ({
              id: payment.id,
              invoice_id: payment.invoice_id,
              amount: parseFloat(payment.amount),
              payment_date: new Date(payment.payment_date),
              method: payment.payment_method || 'other',
              status: payment.status,
              created_at: new Date(payment.created_at),
              updated_at: new Date(payment.updated_at),
              invoice: {
                invoice_number: payment.invoice?.invoice_number || 'N/A',
                client: { 
                  name: payment.invoice?.client?.name || 'Cliente desconocido' 
                }
              }
            }));
            
            this.calculateStats();
            this.filteredPayments = [...this.payments];
            this.dataSource = new MatTableDataSource(this.filteredPayments);
            
            setTimeout(() => {
              if (this.paginator) {
                this.dataSource.paginator = this.paginator;
              }
              if (this.sort) {
                this.dataSource.sort = this.sort;
              }
            });

            this.dataSource.filterPredicate = (data: Payment, filter: string) => {
              const term = filter.toLowerCase();
              return data.invoice?.invoice_number?.toLowerCase().includes(term) ||
                     data.invoice?.client?.name?.toLowerCase().includes(term) ||
                     data.method?.toLowerCase().includes(term);
            };
          }
          this.loading = false;
        },
        error: (error) => {
          console.error('Error cargando pagos:', error);
          this.error = 'Error al cargar los pagos';
          this.loading = false;
        }
      });

    } catch (error) {
      this.error = 'Error al cargar los pagos';
      console.error('Error loading payments:', error);
      this.loading = false;
    }
  }

  applyFilter(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    this.dataSource.filter = filterValue.trim().toLowerCase();

    if (this.dataSource.paginator) {
      this.dataSource.paginator.firstPage();
    }
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
    this.dateFilter = value;
    this.applyFilters();
  }

  applyFilters() {
    let filtered = [...this.payments];

    if (this.statusFilter) {
      filtered = filtered.filter(payment => payment.status === this.statusFilter);
    }

    if (this.methodFilter) {
      filtered = filtered.filter(payment => payment.method === this.methodFilter);
    }

    if (this.dateFilter) {
      const now = new Date();
      const startDate = this.getStartDateForRange(this.dateFilter, now);
      filtered = filtered.filter(payment => {
        const paymentDate = new Date(payment.payment_date);
        return paymentDate >= startDate && paymentDate <= now;
      });
    }

    if (this.searchTerm) {
      const term = this.searchTerm.toLowerCase();
      filtered = filtered.filter(payment =>
        payment.invoice?.invoice_number?.toLowerCase().includes(term) ||
        payment.invoice?.client?.name?.toLowerCase().includes(term) ||
        payment.method?.toLowerCase().includes(term)
      );
    }

    this.filteredPayments = filtered;
    this.dataSource.data = this.filteredPayments;
    this.calculateStats();
  }

  private getStartDateForRange(range: string, now: Date): Date {
    const start = new Date(now);
    switch (range) {
      case 'today':
        start.setHours(0, 0, 0, 0);
        break;
      case 'week':
        start.setDate(start.getDate() - 7);
        break;
      case 'month':
        start.setMonth(start.getMonth() - 1);
        break;
      case 'year':
        start.setFullYear(start.getFullYear() - 1);
        break;
      default:
        start.setDate(start.getDate() - 30);
    }
    return start;
  }

  getMethodIcon(method: string): string {
    const methodObj = this.paymentMethods.find(m => m.value === method);
    return methodObj?.icon || 'payment';
  }

  getMethodLabel(method: string): string {
    const methodObj = this.paymentMethods.find(m => m.value === method);
    return methodObj?.label || method;
  }

  calculateStats() {
    const filtered = this.filteredPayments;
    this.stats = {
      total: filtered.length,
      totalAmount: filtered.reduce((sum, p) => sum + p.amount, 0),
      cash: filtered.filter(p => p.method === 'cash').length,
      card: filtered.filter(p => p.method === 'credit_card').length,
      transfer: filtered.filter(p => p.method === 'bank_transfer').length,
      thisMonth: filtered.filter(p => {
        const paymentDate = new Date(p.payment_date);
        const now = new Date();
        return paymentDate.getMonth() === now.getMonth() && 
               paymentDate.getFullYear() === now.getFullYear();
      }).length,
      monthlyPayments: filtered.filter(p => {
        const paymentDate = new Date(p.payment_date);
        const now = new Date();
        return paymentDate.getMonth() === now.getMonth() && 
               paymentDate.getFullYear() === now.getFullYear();
      }).length,
      totalCollected: filtered.filter(p => p.status === 'completed').reduce((sum, p) => sum + p.amount, 0),
      pendingPayments: filtered.filter(p => p.status === 'pending').length,
      failedPayments: filtered.filter(p => p.status === 'failed').length
    };
  }

  recordPayment() {
    this.router.navigate(['/payments/new']);
  }

  viewPayment(payment: Payment) {
    this.router.navigate(['/payments', payment.id]);
  }

  processPayment(payment: Payment) {
    console.log('Processing payment:', payment);
  }

  downloadReceipt(payment: Payment) {
    console.log('Downloading receipt for payment:', payment);
  }

  formatCurrency(amount: number): string {
    return new Intl.NumberFormat('es-CL', {
      style: 'currency',
      currency: 'CLP',
      minimumFractionDigits: 0
    }).format(amount);
  }

  formatDate(date: Date | string): string {
    return new Date(date).toLocaleDateString('es-CL');
  }

  getStatusColor(status: string): string {
    switch (status) {
      case 'completed': return 'success';
      case 'pending': return 'warning';
      case 'failed': return 'danger';
      default: return 'default';
    }
  }

  getStatusIcon(status: string): string {
    switch (status) {
      case 'completed': return 'check_circle';
      case 'pending': return 'schedule';
      case 'failed': return 'error';
      default: return 'help';
    }
  }

  getStatusLabel(status: string): string {
    switch (status) {
      case 'completed': return 'Completado';
      case 'pending': return 'Pendiente';
      case 'failed': return 'Fallido';
      default: return status;
    }
  }

  retryPayment(payment: Payment) {
    console.log('Retrying payment:', payment);
  }

  reconcilePayments() {
    console.log('Reconciling payments');
  }

  onPageChange(event: any) {
    console.log('Page changed:', event);
  }
}
