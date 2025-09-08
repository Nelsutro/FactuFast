import { Component, OnInit, ViewChild, AfterViewInit } from '@angular/core';
import { Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
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
import { MatCardModule } from '@angular/material/card';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatTabsModule } from '@angular/material/tabs';
import { MatGridListModule } from '@angular/material/grid-list';
import { ApiService } from '../../services/api.service';

interface Client {
  id: number;
  company_id: number;
  name: string;
  email: string;
  phone: string;
  address: string;
  created_at: Date;
  updated_at: Date;
}

interface Invoice {
  id: number;
  company_id: number;
  client_id: number;
  invoice_number: string;
  amount: number;
  status: string;
  issue_date: Date;
  due_date: Date;
  created_at: Date;
  updated_at: Date;
}

@Component({
  selector: 'app-clients',
  templateUrl: './clients.component.html',
  styleUrls: ['./clients.component.css'],
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
    MatProgressBarModule,
    MatTooltipModule,
    MatTabsModule,
    MatGridListModule
  ]
})
export class ClientsComponent implements OnInit, AfterViewInit {
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;
  
  dataSource = new MatTableDataSource<Client>();
  displayedColumns: string[] = ['id', 'name', 'email', 'phone', 'address', 'actions'];
  loading = true;
  error: string | null = null;

  stats = {
    total: 0,
    active: 0,
    totalInvoices: 0,
    totalRevenue: 0
  };

  // View control
  viewMode: 'cards' | 'list' = 'list';
  searchTerm = '';
  currentPage = 1;
  pageSize = 10;
  filteredClients: Client[] = [];
  paginatedClients: Client[] = [];
  totalPages = 0;

  // Modal control
  showDeleteModal = false;
  clientToDelete: Client | null = null;

  constructor(
    private apiService: ApiService,
    private router: Router,
    private dialog: MatDialog,
    private snackBar: MatSnackBar
  ) {}

  mockClients: Client[] = [
    {
      id: 1,
      company_id: 1,
      name: 'ABC Corporation',
      email: 'contacto@abccorp.com',
      phone: '+56 9 8765 4321',
      address: 'Av. Providencia 1234, Santiago',
      created_at: new Date('2023-12-01'),
      updated_at: new Date('2024-01-15')
    }
  ];

  mockInvoices: Invoice[] = [
    {
      id: 1,
      company_id: 1,
      client_id: 1,
      invoice_number: '001234',
      amount: 1250.00,
      status: 'pending',
      issue_date: new Date('2024-01-15'),
      due_date: new Date('2024-02-15'),
      created_at: new Date('2024-01-15'),
      updated_at: new Date('2024-01-15')
    }
  ];

  ngOnInit() {
    this.loadClients();
  }

  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
    this.dataSource.sort = this.sort;
  }

  loadClients() {
    try {
      this.loading = true;
      this.error = null;

      // TODO: Reemplazar con llamada real al API
      setTimeout(() => {
        this.dataSource.data = this.mockClients;
        this.calculateStats();
        this.loading = false;
      }, 500);

    } catch (error) {
      this.error = 'Error al cargar los clientes';
      console.error('Error loading clients:', error);
      this.showMessage(this.error);
      this.loading = false;
    }
  }

  applyFilter(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    this.dataSource.filter = filterValue.trim().toLowerCase();
    this.searchTerm = filterValue;

    // Actualizar lista filtrada
    this.filteredClients = this.dataSource.filteredData;
    this.updatePagination();

    if (this.dataSource.paginator) {
      this.dataSource.paginator.firstPage();
    }
  }

  updatePagination(): void {
    this.totalPages = Math.ceil(this.filteredClients.length / this.pageSize);
    const start = (this.currentPage - 1) * this.pageSize;
    const end = start + this.pageSize;
    this.paginatedClients = this.filteredClients.slice(start, end);
  }

  previousPage(): void {
    if (this.currentPage > 1) {
      this.currentPage--;
      this.updatePagination();
    }
  }

  nextPage(): void {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
      this.updatePagination();
    }
  }

  addClient() {
    // TODO: Implementar diálogo de creación
    this.showMessage('Función en desarrollo');
  }

  editClient(client: Client) {
    // TODO: Implementar diálogo de edición
    this.showMessage('Función en desarrollo');
  }

  deleteClient(client: Client) {
    this.clientToDelete = client;
    this.showDeleteModal = true;
  }

  viewClient(client: Client): void {
    // TODO: Implementar vista detallada
    this.showMessage('Vista detallada en desarrollo');
  }

  createInvoiceForClient(client: Client): void {
    // TODO: Implementar creación de factura
    this.showMessage('Creación de factura en desarrollo');
  }

  getClientInvoiceCount(clientId: number): number {
    return this.mockInvoices.filter(i => i.client_id === clientId).length;
  }

  getClientRevenue(clientId: number): number {
    return this.mockInvoices
      .filter(i => i.client_id === clientId && i.status === 'paid')
      .reduce((sum, i) => sum + i.amount, 0);
  }

  getLastInvoiceDate(clientId: number): string {
    const lastInvoice = this.mockInvoices
      .filter(i => i.client_id === clientId)
      .sort((a, b) => b.created_at.getTime() - a.created_at.getTime())[0];
    
    return lastInvoice ? this.formatDate(lastInvoice.created_at) : 'Nunca';
  }

  trackByClientId(index: number, client: Client): number {
    return client.id;
  }

  private calculateStats() {
    this.stats = {
      total: this.dataSource.data.length,
      active: this.dataSource.data.length,
      totalInvoices: this.mockInvoices.length,
      totalRevenue: this.mockInvoices
        .filter(i => i.status === 'paid')
        .reduce((sum, i) => sum + i.amount, 0)
    };
  }

  private showMessage(message: string) {
    this.snackBar.open(message, 'Cerrar', {
      duration: 3000,
      horizontalPosition: 'end',
      verticalPosition: 'top'
    });
  }

  formatCurrency(amount: number) {
    return amount.toLocaleString('es-CL', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    });
  }

  // Simulación temporal de facturas
  private simulateInvoicesApiCall(): Promise<Invoice[]> {
    return new Promise((resolve) => {
      setTimeout(() => {
        resolve(this.mockInvoices);
      }, 300);
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

  protected readonly Math = Math;

  createClient(): void {
    // Alias para addClient para mantener consistencia en la interfaz
    this.addClient();
  }

  confirmDelete(): void {
    if (this.clientToDelete) {
      // TODO: Implementar eliminación real
      this.showMessage('Eliminación en desarrollo');
      this.clientToDelete = null;
      this.showDeleteModal = false;
    }
  }

  cancelDelete(): void {
    this.clientToDelete = null;
    this.showDeleteModal = false;
  }
}