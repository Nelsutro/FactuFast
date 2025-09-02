import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api.service';
import { Client, Invoice } from '../../models';

@Component({
  selector: 'app-clients',
  templateUrl: './clients.component.html',
  styleUrls: ['./clients.component.css'],
  standalone: false
})
export class ClientsComponent implements OnInit {
  
  // Data properties
  clients: Client[] = [];
  filteredClients: Client[] = [];
  paginatedClients: Client[] = [];
  invoices: Invoice[] = []; // For calculating client stats
  loading = true;
  error: string | null = null;

  // Filter properties
  searchTerm = '';
  sortBy = 'name_asc';

  // View properties
  viewMode: 'cards' | 'list' = 'cards';
  
  // Pagination properties
  currentPage = 1;
  pageSize = 12; // More items for cards view
  totalPages = 1;

  // Stats
  stats = {
    total: 0,
    active: 0,
    totalInvoices: 0,
    totalRevenue: 0
  };

  // Modal properties
  showDeleteModal = false;
  clientToDelete: Client | null = null;

  // Math property for template
  Math = Math;

  constructor(
    private apiService: ApiService,
    private router: Router
  ) {}

  ngOnInit() {
    this.loadClients();
  }

  async loadClients() {
    try {
      this.loading = true;
      this.error = null;

      // Load clients and invoices in parallel
      const [clientsResponse, invoicesResponse] = await Promise.all([
        this.simulateClientsApiCall(),
        this.simulateInvoicesApiCall()
      ]);

      this.clients = clientsResponse;
      this.invoices = invoicesResponse;
      
      this.calculateStats();
      this.applyFilters();

    } catch (error) {
      this.error = 'Error al cargar los clientes';
      console.error('Error loading clients:', error);
    } finally {
      this.loading = false;
    }
  }

  // Simulate API calls - Replace with real API calls
  private simulateClientsApiCall(): Promise<Client[]> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const mockClients: Client[] = [
          {
            id: 1,
            company_id: 1,
            name: 'ABC Corporation',
            email: 'contacto@abccorp.com',
            phone: '+56 9 8765 4321',
            address: 'Av. Providencia 1234, Santiago',
            created_at: new Date('2023-12-01'),
            updated_at: new Date('2024-01-15')
          },
          {
            id: 2,
            company_id: 1,
            name: 'XYZ Limited',
            email: 'admin@xyzltd.com',
            phone: '+56 9 1234 5678',
            address: 'Las Condes 567, Santiago',
            created_at: new Date('2023-11-15'),
            updated_at: new Date('2024-01-10')
          },
          {
            id: 3,
            company_id: 1,
            name: 'Tech Solutions Inc',
            email: 'billing@techsolutions.com',
            phone: '+56 9 9876 5432',
            address: 'Vitacura 890, Santiago',
            created_at: new Date('2024-01-05'),
            updated_at: new Date('2024-01-20')
          },
          {
            id: 4,
            company_id: 1,
            name: 'Startup Innovadora',
            email: 'founders@startup.com',
            phone: '+56 9 5555 6666',
            address: 'Ñuñoa 321, Santiago',
            created_at: new Date('2024-01-20'),
            updated_at: new Date('2024-01-25')
          },
          {
            id: 5,
            company_id: 1,
            name: 'Comercial del Sur',
            email: 'ventas@comercialsur.cl',
            phone: '+56 9 7777 8888',
            address: 'San Miguel 456, Santiago',
            created_at: new Date('2023-10-10'),
            updated_at: new Date('2024-01-01')
          },
          {
            id: 6,
            company_id: 1,
            name: 'Servicios Profesionales Ltda',
            email: 'info@servicios.cl',
            phone: '+56 9 4444 3333',
            address: 'Maipú 789, Santiago',
            created_at: new Date('2023-09-20'),
            updated_at: new Date('2023-12-15')
          }
        ];
        resolve(mockClients);
      }, 500);
    });
  }

  private simulateInvoicesApiCall(): Promise<Invoice[]> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const mockInvoices: Invoice[] = [
          {
            id: 1, company_id: 1, client_id: 1, invoice_number: '001234', amount: 1250.00,
            status: 'pending', issue_date: new Date('2024-01-15'), due_date: new Date('2024-02-15'),
            created_at: new Date('2024-01-15'), updated_at: new Date('2024-01-15')
          },
          {
            id: 2, company_id: 1, client_id: 2, invoice_number: '001235', amount: 2850.00,
            status: 'paid', issue_date: new Date('2024-01-10'), due_date: new Date('2024-02-10'),
            created_at: new Date('2024-01-10'), updated_at: new Date('2024-01-20')
          },
          {
            id: 3, company_id: 1, client_id: 3, invoice_number: '001236', amount: 750.00,
            status: 'paid', issue_date: new Date('2024-01-20'), due_date: new Date('2024-02-20'),
            created_at: new Date('2024-01-20'), updated_at: new Date('2024-01-25')
          },
          {
            id: 4, company_id: 1, client_id: 1, invoice_number: '001237', amount: 3200.00,
            status: 'cancelled', issue_date: new Date('2024-01-12'), due_date: new Date('2024-02-12'),
            created_at: new Date('2024-01-12'), updated_at: new Date('2024-01-25')
          },
          {
            id: 5, company_id: 1, client_id: 4, invoice_number: '001238', amount: 1890.00,
            status: 'pending', issue_date: new Date(), due_date: new Date(Date.now() + 5 * 24 * 60 * 60 * 1000),
            created_at: new Date(), updated_at: new Date()
          },
          {
            id: 6, company_id: 1, client_id: 2, invoice_number: '001239', amount: 4500.00,
            status: 'paid', issue_date: new Date('2023-12-20'), due_date: new Date('2024-01-20'),
            created_at: new Date('2023-12-20'), updated_at: new Date('2024-01-18')
          }
        ];
        resolve(mockInvoices);
      }, 300);
    });
  }

  calculateStats() {
    this.stats = {
      total: this.clients.length,
      active: this.clients.filter(c => this.getClientInvoiceCount(c.id) > 0).length,
      totalInvoices: this.invoices.length,
      totalRevenue: this.invoices
        .filter(i => i.status === 'paid')
        .reduce((sum, i) => sum + i.amount, 0)
    };
  }

  // Filtering and searching
  onSearchChange() {
    this.currentPage = 1;
    this.applyFilters();
  }

  applyFilters() {
    let filtered = [...this.clients];

    // Apply search filter
    if (this.searchTerm) {
      const term = this.searchTerm.toLowerCase();
      filtered = filtered.filter(client => 
        client.name.toLowerCase().includes(term) ||
        client.email?.toLowerCase().includes(term) ||
        client.phone?.toLowerCase().includes(term) ||
        client.address?.toLowerCase().includes(term)
      );
    }

    this.filteredClients = filtered;
    this.applySorting();
    this.updatePagination();
  }

  applySorting() {
    const [field, direction] = this.sortBy.split('_');
    const isDesc = direction === 'desc';

    this.filteredClients.sort((a, b) => {
      let aValue: any;
      let bValue: any;

      switch (field) {
        case 'name':
          aValue = a.name.toLowerCase();
          bValue = b.name.toLowerCase();
          break;
        case 'created':
          aValue = new Date(a.created_at).getTime();
          bValue = new Date(b.created_at).getTime();
          break;
        case 'revenue':
          aValue = this.getClientRevenue(a.id);
          bValue = this.getClientRevenue(b.id);
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
    this.totalPages = Math.ceil(this.filteredClients.length / this.pageSize);
    
    if (this.currentPage > this.totalPages) {
      this.currentPage = Math.max(1, this.totalPages);
    }

    const startIndex = (this.currentPage - 1) * this.pageSize;
    const endIndex = startIndex + this.pageSize;
    this.paginatedClients = this.filteredClients.slice(startIndex, endIndex);
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
  createClient() {
    this.router.navigate(['/clients/create']);
  }

  viewClient(client: Client) {
    this.router.navigate(['/clients', client.id]);
  }

  editClient(client: Client) {
    this.router.navigate(['/clients', client.id, 'edit']);
  }

  createInvoiceForClient(client: Client) {
    this.router.navigate(['/invoices/create'], { 
      queryParams: { clientId: client.id } 
    });
  }

  importClients() {
    this.router.navigate(['/clients/import']);
  }

  deleteClient(client: Client) {
    this.clientToDelete = client;
    this.showDeleteModal = true;
  }

  confirmDelete() {
    if (this.clientToDelete) {
      console.log('Deleting client:', this.clientToDelete.name);
      
      // Remove from local array
      this.clients = this.clients.filter(c => c.id !== this.clientToDelete!.id);
      this.calculateStats();
      this.applyFilters();
      
      // Close modal
      this.showDeleteModal = false;
      this.clientToDelete = null;

      // In real implementation:
      // this.apiService.deleteClient(this.clientToDelete.id).subscribe(...)
    }
  }

  cancelDelete() {
    this.showDeleteModal = false;
    this.clientToDelete = null;
  }

  // Client statistics methods
  getClientInvoiceCount(clientId: number): number {
    return this.invoices.filter(invoice => invoice.client_id === clientId).length;
  }

  getClientRevenue(clientId: number): number {
    return this.invoices
      .filter(invoice => invoice.client_id === clientId && invoice.status === 'paid')
      .reduce((sum, invoice) => sum + invoice.amount, 0);
  }

  getLastInvoiceDate(clientId: number): string | null {
    const clientInvoices = this.invoices
      .filter(invoice => invoice.client_id === clientId)
      .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());

    if (clientInvoices.length > 0) {
      return this.formatDate(clientInvoices[0].created_at);
    }
    return null;
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

  trackByClientId(index: number, client: Client): number {
    return client.id;
  }
}