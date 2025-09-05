import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { PageEvent } from '@angular/material/paginator';
import { ApiService } from '../../services/api.service';
import { Company, Invoice } from '../../models';

@Component({
  selector: 'app-companies',
  templateUrl: './companies.component.html',
  styleUrls: ['./companies.component.css'],
  standalone: false
})
export class CompaniesComponent implements OnInit {

  // Data properties
  companies: Company[] = [];
  filteredCompanies: Company[] = [];
  paginatedCompanies: Company[] = [];
  invoices: Invoice[] = []; // For calculating company stats
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
  companyToDelete: Company | null = null;

  // Math property for template
  Math = Math;

  constructor(
    private apiService: ApiService,
    private router: Router
  ) {}

  ngOnInit() {
    this.loadCompanies();
  }

  async loadCompanies() {
    try {
      this.loading = true;
      this.error = null;

      // Load companies and invoices in parallel
      const [companiesResponse, invoicesResponse] = await Promise.all([
        this.simulateCompaniesApiCall(),
        this.simulateInvoicesApiCall()
      ]);

      this.companies = companiesResponse;
      this.invoices = invoicesResponse;

      this.calculateStats();
      this.applyFilters();

    } catch (error) {
      this.error = 'Error al cargar las empresas';
      console.error('Error loading companies:', error);
    } finally {
      this.loading = false;
    }
  }

  // Simulate API calls - Replace with real API calls
  private simulateCompaniesApiCall(): Promise<Company[]> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const mockCompanies: Company[] = [
          {
            id: 1,
            name: 'FactuFast Solutions',
            email: 'admin@factufast.cl',
            phone: '+56 9 8765 4321',
            address: 'Av. Providencia 1234, Santiago',
            tax_id: '77.123.456-7',
            website: 'https://factufast.cl',
            created_at: new Date('2023-12-01'),
            updated_at: new Date('2024-01-15')
          },
          {
            id: 2,
            name: 'TechCorp Ltda',
            email: 'contacto@techcorp.cl',
            phone: '+56 9 1234 5678',
            address: 'Las Condes 567, Santiago',
            tax_id: '76.987.654-3',
            website: 'https://techcorp.cl',
            created_at: new Date('2023-11-15'),
            updated_at: new Date('2024-01-10')
          },
          {
            id: 3,
            name: 'Innovate Solutions',
            email: 'info@innovate.cl',
            phone: '+56 9 9876 5432',
            address: 'Vitacura 890, Santiago',
            tax_id: '78.456.789-1',
            website: 'https://innovate.cl',
            created_at: new Date('2024-01-05'),
            updated_at: new Date('2024-01-20')
          }
        ];
        resolve(mockCompanies);
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
            id: 3, company_id: 2, client_id: 3, invoice_number: '001236', amount: 750.00,
            status: 'paid', issue_date: new Date('2024-01-20'), due_date: new Date('2024-02-20'),
            created_at: new Date('2024-01-20'), updated_at: new Date('2024-01-25')
          },
          {
            id: 4, company_id: 3, client_id: 4, invoice_number: '001237', amount: 3200.00,
            status: 'cancelled', issue_date: new Date('2024-01-12'), due_date: new Date('2024-02-12'),
            created_at: new Date('2024-01-12'), updated_at: new Date('2024-01-25')
          }
        ];
        resolve(mockInvoices);
      }, 300);
    });
  }

  calculateStats() {
    this.stats = {
      total: this.companies.length,
      active: this.companies.filter(c => this.getCompanyInvoiceCount(c.id) > 0).length,
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
    let filtered = [...this.companies];

    // Apply search filter
    if (this.searchTerm) {
      const term = this.searchTerm.toLowerCase();
      filtered = filtered.filter(company =>
        company.name.toLowerCase().includes(term) ||
        company.email?.toLowerCase().includes(term) ||
        company.phone?.toLowerCase().includes(term) ||
        company.address?.toLowerCase().includes(term) ||
        company.tax_id?.toLowerCase().includes(term)
      );
    }

    this.filteredCompanies = filtered;
    this.applySorting();
    this.updatePagination();
  }

  applySorting() {
    const [field, direction] = this.sortBy.split('_');
    const isDesc = direction === 'desc';

    this.filteredCompanies.sort((a, b) => {
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
          aValue = this.getCompanyRevenue(a.id);
          bValue = this.getCompanyRevenue(b.id);
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
    this.totalPages = Math.ceil(this.filteredCompanies.length / this.pageSize);

    if (this.currentPage > this.totalPages) {
      this.currentPage = Math.max(1, this.totalPages);
    }

    const startIndex = (this.currentPage - 1) * this.pageSize;
    const endIndex = startIndex + this.pageSize;
    this.paginatedCompanies = this.filteredCompanies.slice(startIndex, endIndex);
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
  createCompany() {
    this.router.navigate(['/companies/create']);
  }

  viewCompany(company: Company) {
    this.router.navigate(['/companies', company.id]);
  }

  editCompany(company: Company) {
    this.router.navigate(['/companies', company.id, 'edit']);
  }

  createInvoiceForCompany(company: Company) {
    this.router.navigate(['/invoices/create'], {
      queryParams: { companyId: company.id }
    });
  }

  importCompanies() {
    this.router.navigate(['/companies/import']);
  }

  deleteCompany(company: Company) {
    this.companyToDelete = company;
    this.showDeleteModal = true;
  }

  confirmDelete() {
    if (this.companyToDelete) {
      console.log('Deleting company:', this.companyToDelete.name);

      // Remove from local array
      this.companies = this.companies.filter(c => c.id !== this.companyToDelete!.id);
      this.calculateStats();
      this.applyFilters();

      // Close modal
      this.showDeleteModal = false;
      this.companyToDelete = null;

      // In real implementation:
      // this.apiService.deleteCompany(this.companyToDelete.id).subscribe(...)
    }
  }

  cancelDelete() {
    this.showDeleteModal = false;
    this.companyToDelete = null;
  }

  // Company statistics methods
  getCompanyInvoiceCount(companyId: number): number {
    return this.invoices.filter(invoice => invoice.company_id === companyId).length;
  }

  getCompanyRevenue(companyId: number): number {
    return this.invoices
      .filter(invoice => invoice.company_id === companyId && invoice.status === 'paid')
      .reduce((sum, invoice) => sum + invoice.amount, 0);
  }

  getLastInvoiceDate(companyId: number): string | null {
    const companyInvoices = this.invoices
      .filter(invoice => invoice.company_id === companyId)
      .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());

    if (companyInvoices.length > 0) {
      return this.formatDate(companyInvoices[0].created_at);
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

  trackByCompanyId(index: number, company: Company): number {
    return company.id;
  }
}
