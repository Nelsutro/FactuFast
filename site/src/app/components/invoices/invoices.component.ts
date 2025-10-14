import { Component, OnInit, ViewChild, AfterViewInit, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { MatTableModule, MatTableDataSource } from '@angular/material/table';
import { MatPaginatorModule, MatPaginator } from '@angular/material/paginator';
import { MatSortModule, MatSort } from '@angular/material/sort';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatDialog } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatChipsModule } from '@angular/material/chips';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatCardModule } from '@angular/material/card';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatGridListModule } from '@angular/material/grid-list';
import { MatMenuModule } from '@angular/material/menu';
import { MatDividerModule } from '@angular/material/divider';
import { MatDialogModule } from '@angular/material/dialog';
import { LoadingComponent } from '../shared/loading/loading.component';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../core/services/auth.service';
import { ImportBatch, Invoice, User } from '../../models';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-invoices',
  templateUrl: './invoices.component.html',
  styleUrls: ['./invoices.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    MatTableModule,
    MatPaginatorModule,
    MatSortModule,
    MatButtonModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatChipsModule,
  MatCheckboxModule,
    MatCardModule,
    MatProgressSpinnerModule,
    MatProgressBarModule,
    MatTooltipModule,
    MatGridListModule,
    MatMenuModule,
    MatDividerModule,
    MatDialogModule,
    LoadingComponent
  ]
})
export class InvoicesComponent implements OnInit, AfterViewInit, OnDestroy {
  
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;

  // Data properties
  dataSource = new MatTableDataSource<Invoice>();
  originalData: Invoice[] = [];
  displayedColumns: string[] = ['invoice_number', 'client.name', 'amount', 'status', 'due_date', 'actions'];
  loading = true;
  error: string | null = null;
  currentUser: User | null = null;

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

  // Import history
  importHistory: ImportBatch[] = [];
  historyLoading = false;
  historyError: string | null = null;
  historyFilters: { status: string; range: string; alertsOnly: boolean } = {
    status: 'all',
    range: '30d',
    alertsOnly: true
  };

  private readonly importPollInterval = 4000;
  private batchPollers = new Map<number, number>();

  // Modal properties
  showDeleteModal = false;
  invoiceToDelete: Invoice | null = null;

  // Math property for template
  Math = Math;

  constructor(
    private apiService: ApiService,
    private authService: AuthService,
    private router: Router,
    private dialog: MatDialog,
    private snackBar: MatSnackBar,
    private http: HttpClient
  ) {}

  ngOnInit() {
    console.log('InvoicesComponent iniciando...');
    this.loadUserData();
    this.loadInvoices();
    this.loadImportHistory();

    // Configurar la función de filtrado (no usada tras unificar filtros, pero dejamos por compatibilidad)
    this.dataSource.filterPredicate = (data: Invoice, filter: string): boolean => {
      const searchStr = filter.toLowerCase();
      return (
        data.invoice_number.toLowerCase().includes(searchStr) ||
        (data.client?.name?.toLowerCase().includes(searchStr) || false) ||
        (data.client?.email?.toLowerCase().includes(searchStr) || false)
      );
    };
  }

  private loadUserData() {
    console.log('Cargando datos del usuario...');
    this.authService.currentUser$.subscribe(user => {
      console.log('Usuario actual:', user);
      this.currentUser = user;
      if (user) {
        console.log('Usuario autenticado, cargando facturas...');
        this.loadInvoices();
      } else {
        console.log('Usuario no autenticado');
      }
    });
  }

  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
    this.dataSource.sort = this.sort;
  }

  ngOnDestroy(): void {
    this.batchPollers.forEach(id => clearInterval(id));
    this.batchPollers.clear();
  }

  async loadInvoices() {
    console.log('Iniciando carga de facturas...');
    const token = localStorage.getItem('auth_token');
    console.log('Token disponible:', !!token);
    
    try {
      this.loading = true;
      this.error = null;

      // Usar las rutas reales con autenticación
      this.apiService.getInvoices().subscribe({
        next: (response) => {
          console.log('Respuesta de la API (paginada):', response);
          const rows = response.data || [];
          console.log('Número de facturas recibidas:', rows.length);
          this.originalData = rows.map((invoice: any) => ({
            id: invoice.id,
            company_id: invoice.company_id ?? invoice.company?.id ?? 0,
            client_id: invoice.client_id ?? invoice.client?.id ?? 0,
            invoice_number: invoice.invoice_number,
            amount: parseFloat(invoice.amount ?? invoice.total ?? 0),
            status: invoice.status,
            issue_date: invoice.issue_date,
            due_date: invoice.due_date,
            notes: invoice.notes,
            created_at: invoice.created_at ?? new Date(),
            updated_at: invoice.updated_at ?? new Date(),
            client: invoice.client ? { ...invoice.client } : { name: invoice.client?.name || 'Cliente desconocido' }
          }));
          this.dataSource.data = this.originalData;
          // Actualizar info de paginación local si deseas usarla luego
          this.totalPages = response.pagination?.last_page || 1;
          this.applyAllFilters();
          console.log('Facturas cargadas exitosamente:', this.originalData.length);
          this.loading = false;
        },
        error: (error) => {
          console.error('Error cargando facturas:', error);
          this.error = 'Error al cargar las facturas';
          this.loading = false;
          this.snackBar.open('Error al cargar las facturas', 'Cerrar', {
            duration: 3000,
            panelClass: ['error-snackbar']
          });
        }
      });

    } catch (error) {
      this.error = 'Error al cargar las facturas';
      console.error('Error loading invoices:', error);
      this.snackBar.open(this.error, 'Cerrar', {
        duration: 3000,
        horizontalPosition: 'end',
        verticalPosition: 'top'
      });
      this.loading = false;
    }
  }

  loadImportHistory(forceReload = false): void {
    if (!forceReload && this.historyLoading) {
      return;
    }

    this.historyLoading = true;
    this.historyError = null;

    const params: Record<string, any> = {
      type: 'invoices',
      per_page: 10
    };

    if (this.historyFilters.status !== 'all') {
      params['status'] = this.historyFilters.status;
    }

    if (this.historyFilters.alertsOnly) {
      params['alerts_only'] = 1;
    }

    const fromDate = this.getHistoryStartDate(this.historyFilters.range);
    if (fromDate) {
      params['from'] = fromDate.toISOString();
    }

    this.apiService.listImportBatches(params).subscribe({
      next: response => {
        this.importHistory = response.data ?? [];
        this.historyLoading = false;
      },
      error: err => {
        console.error('Error cargando historial de importaciones:', err);
        this.historyError = err.message || 'Error al cargar el historial de importaciones';
        this.historyLoading = false;
        this.importHistory = [];
      }
    });
  }

  onHistoryStatusChange(status: string): void {
    this.historyFilters.status = status || 'all';
    this.loadImportHistory(true);
  }

  onHistoryRangeChange(range: string): void {
    this.historyFilters.range = range || 'all';
    this.loadImportHistory(true);
  }

  toggleHistoryAlertsOnly(checked: boolean): void {
    this.historyFilters.alertsOnly = checked;
    this.loadImportHistory(true);
  }

  refreshImportHistory(): void {
    this.loadImportHistory(true);
  }

  private getHistoryStartDate(range: string): Date | null {
    const now = new Date();

    switch (range) {
      case '7d':
        return new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
      case '30d':
        return new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
      case '90d':
        return new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000);
      default:
        return null;
    }
  }

  formatImportStatus(batch: ImportBatch): string {
    switch (batch.status) {
      case 'completed':
        return batch.error_count > 0 ? 'Completada con observaciones' : 'Completada';
      case 'processing':
        return 'Procesando';
      case 'failed':
        return 'Fallida';
      default:
        return 'Pendiente';
    }
  }

  formatImportTimestamp(dateValue?: string | null): string {
    if (!dateValue) {
      return 'N/A';
    }

    return new Date(dateValue).toLocaleString('es-CL', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  formatImportDuration(batch: ImportBatch): string {
    if (batch.duration_seconds && batch.duration_seconds > 0) {
      const minutes = Math.floor(batch.duration_seconds / 60);
      const seconds = batch.duration_seconds % 60;
      return minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
    }

    if (batch.started_at && batch.finished_at) {
      const start = new Date(batch.started_at).getTime();
      const end = new Date(batch.finished_at).getTime();
      const diffSeconds = Math.max(0, Math.round((end - start) / 1000));
      const minutes = Math.floor(diffSeconds / 60);
      const seconds = diffSeconds % 60;
      return minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
    }

    return 'N/A';
  }

  getImportStatusClass(batch: ImportBatch): string {
    if (batch.status === 'failed') {
      return 'import-status-error';
    }
    if (batch.status === 'completed' && batch.error_count > 0) {
      return 'import-status-warning';
    }
    if (batch.status === 'completed') {
      return 'import-status-success';
    }
    return 'import-status-info';
  }

  downloadImportErrors(batch: ImportBatch): void {
    if (!batch.has_errors) {
      return;
    }

    this.apiService.downloadImportErrors(batch.id).subscribe({
      next: blob => {
        const url = window.URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = `import_errors_${batch.id}.csv`;
        anchor.click();
        window.URL.revokeObjectURL(url);
      },
      error: err => {
        console.error('Error descargando errores de importación:', err);
        this.snackBar.open(err.message || 'No fue posible descargar los errores', 'Cerrar', { duration: 3500 });
      }
    });
  }

  private monitorBatch(batchId: number): void {
    if (this.batchPollers.has(batchId)) {
      return;
    }

    const poller = window.setInterval(() => {
      this.apiService.getImportBatch(batchId).subscribe({
        next: batch => {
          if (batch.status === 'completed' || batch.status === 'failed') {
            this.handleBatchCompletion(batch);
            this.stopMonitoring(batchId);
          }
        },
        error: err => {
          console.error('Error monitoreando importación:', err);
          this.stopMonitoring(batchId);
        }
      });
    }, this.importPollInterval);

    this.batchPollers.set(batchId, poller);
  }

  private stopMonitoring(batchId: number): void {
    const timer = this.batchPollers.get(batchId);
    if (timer) {
      clearInterval(timer);
      this.batchPollers.delete(batchId);
    }
  }

  private handleBatchCompletion(batch: ImportBatch): void {
    const actionLabel = batch.has_errors ? 'Ver errores' : 'Cerrar';
    const ref = this.snackBar.open(this.buildBatchSnackMessage(batch), actionLabel, {
      duration: 6000,
      horizontalPosition: 'end',
      verticalPosition: 'top'
    });

    if (batch.has_errors) {
      ref.onAction().subscribe(() => this.downloadImportErrors(batch));
    }

    this.loadImportHistory(true);
    this.loadInvoices();
  }

  private buildBatchSnackMessage(batch: ImportBatch): string {
    if (batch.status === 'failed') {
      return 'La importación de facturas falló. Revisa los detalles antes de reintentar.';
    }

    if (batch.status === 'completed' && batch.error_count > 0) {
      return `Importación completada con ${batch.success_count} registro(s) exitoso(s) y ${batch.error_count} con errores.`;
    }

    if (batch.status === 'completed') {
      return `Importación completada: ${batch.success_count} registro(s) agregados correctamente.`;
    }

    return 'Actualización de importación recibida.';
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
    const filterValue = (event.target as HTMLInputElement).value || '';
    this.searchTerm = filterValue;
    this.applyAllFilters();
  }

  applyStatusFilter(status: string) {
    this.statusFilter = status || '';
    this.applyAllFilters();
  }

  applyDateFilter(range: string) {
    this.dateRange = range || '';
    this.applyAllFilters();
  }

  private get filteredData(): Invoice[] {
    const term = this.searchTerm.trim().toLowerCase();
    return this.originalData.filter(invoice => {
      const matchesStatus = !this.statusFilter || invoice.status === this.statusFilter;
      const matchesDate = !this.dateRange || this.isInDateRange(invoice.issue_date, this.dateRange);
      const matchesText = !term ||
        invoice.invoice_number.toLowerCase().includes(term) ||
        (invoice.client?.name?.toLowerCase().includes(term) || false) ||
        (invoice.client?.email?.toLowerCase().includes(term) || false);
      return matchesStatus && matchesDate && matchesText;
    });
  }

  private applyAllFilters() {
    this.dataSource.data = this.filteredData;
    this.calculateStats();
    if (this.dataSource.paginator) {
      this.dataSource.paginator.firstPage();
    }
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
    import('./invoice-edit-dialog.component').then(({ InvoiceEditDialogComponent }) => {
      const dialogRef = this.dialog.open(InvoiceEditDialogComponent, {
        width: '600px',
        data: { invoice },
        disableClose: true
      });
      dialogRef.afterClosed().subscribe((saved: boolean) => {
        if (saved) {
          this.snackBar.open('Factura actualizada', 'Cerrar', { duration: 2500 });
          this.loadInvoices();
        }
      });
    });
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
    this.apiService.downloadInvoicePdf(invoice.id).subscribe({
      next: (blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `invoice_${invoice.invoice_number || invoice.id}.pdf`;
        a.click();
        window.URL.revokeObjectURL(url);
      },
      error: (err) => {
        this.snackBar.open(err.message || 'No fue posible generar el PDF', 'Cerrar', { duration: 3000 });
      }
    });
  }

  importInvoices() {
    // Dispara input file oculto
    const input = document.getElementById('invoicesCsvInput') as HTMLInputElement | null;
    if (input) input.click();
  }

  onInvoicesFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    this.snackBar.open('Importando facturas...', undefined, { duration: 1500 });
    this.apiService.importInvoicesCsv(file).subscribe({
      next: (res) => {
        const batchId = res?.data?.batch_id;
        if (batchId) {
          this.snackBar.open('Importación encolada. Te avisaremos cuando finalice.', 'Cerrar', { duration: 4000 });
          this.monitorBatch(batchId);
        } else {
          const created = res?.data?.created ?? 0;
          const skipped = res?.data?.skipped ?? res?.data?.errors ?? 0;
          this.snackBar.open(`Importación completa: ${created} creadas, ${skipped} omitidas`, 'Cerrar', { duration: 3500 });
          this.loadInvoices();
        }
        this.loadImportHistory(true);
        input.value = '';
      },
      error: (err) => {
        this.snackBar.open(err.message || 'Error al importar CSV', 'Cerrar', { duration: 3500 });
        input.value = '';
        this.loadImportHistory(true);
      }
    });
  }

  exportInvoices() {
    this.apiService.exportInvoicesCsv().subscribe({
      next: (blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `invoices_export_${new Date().toISOString().slice(0,19).replace(/[:T]/g,'-')}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
      },
      error: (err) => {
        this.snackBar.open(err.message || 'Error al exportar CSV', 'Cerrar', { duration: 3000 });
      }
    });
  }

  sendInvoiceEmail(invoice: Invoice) {
    const to = prompt('Enviar a (email):', invoice.client?.email || '');
    if (!to) return;
    const subject = prompt('Asunto:', `Factura #${invoice.invoice_number}`) || `Factura #${invoice.invoice_number}`;
    const message = prompt('Mensaje:', 'Adjuntamos su factura. Gracias por su preferencia.') || 'Adjuntamos su factura. Gracias por su preferencia.';
    const attach = confirm('¿Adjuntar PDF?');
    this.apiService.sendInvoiceEmail(invoice.id, { to, subject, message, attach_pdf: attach }).subscribe({
      next: () => this.snackBar.open('Correo enviado', 'Cerrar', { duration: 2500 }),
      error: (err) => this.snackBar.open(err.message || 'No se pudo enviar el correo', 'Cerrar', { duration: 3000 })
    });
  }

  deleteInvoice(invoice: Invoice) {
    this.invoiceToDelete = invoice;
    this.showDeleteModal = true;
  }

  confirmDelete() {
    if (this.invoiceToDelete) {
      const id = this.invoiceToDelete.id;
      this.apiService.deleteInvoice(id).subscribe({
        next: () => {
          this.snackBar.open('Factura eliminada', 'Cerrar', {
            duration: 2500,
            horizontalPosition: 'end',
            verticalPosition: 'top'
          });
          this.loadInvoices();
          this.showDeleteModal = false;
          this.invoiceToDelete = null;
        },
        error: (error) => {
          this.snackBar.open(error.message || 'Error al eliminar la factura', 'Cerrar', {
            duration: 3000,
            horizontalPosition: 'end',
            verticalPosition: 'top'
          });
          this.showDeleteModal = false;
          this.invoiceToDelete = null;
        }
      });
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
