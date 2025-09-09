import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatCardModule } from '@angular/material/card';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatMenuModule } from '@angular/material/menu';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatDialog } from '@angular/material/dialog';
import { CompanyService, Company } from '../../core/services/company.service';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-companies',
  templateUrl: './companies.component.html',
  styleUrls: ['./companies.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    MatButtonModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatCardModule,
    MatProgressSpinnerModule,
    MatMenuModule
  ]
})
export class CompaniesComponent implements OnInit {

  // Data properties
  companies: Company[] = [];
  filteredCompanies: Company[] = [];
  loading = true;
  error: string | null = null;

  // Search
  searchTerm = '';

  constructor(
    private companyService: CompanyService,
    private authService: AuthService,
    private router: Router,
    private snackBar: MatSnackBar,
    private dialog: MatDialog
  ) {}

  ngOnInit() {
    // Verificar que el usuario sea admin
    if (!this.authService.isAdmin()) {
      this.router.navigate(['/dashboard']);
      return;
    }
    
    this.loadCompanies();
  }

  loadCompanies() {
    this.loading = true;
    this.error = null;
    
    this.companyService.getCompanies().subscribe({
      next: (response) => {
        if (response.success && response.data) {
          this.companies = response.data;
          this.filteredCompanies = [...this.companies];
        }
        this.loading = false;
      },
      error: (error) => {
        console.error('Error cargando empresas:', error);
        this.error = 'Error al cargar las empresas. Por favor, intenta de nuevo.';
        this.loading = false;
      }
    });
  }

  filterCompanies() {
    if (!this.searchTerm.trim()) {
      this.filteredCompanies = [...this.companies];
      return;
    }

    const term = this.searchTerm.toLowerCase().trim();
    this.filteredCompanies = this.companies.filter(company => 
      company.name.toLowerCase().includes(term) ||
      (company.email && company.email.toLowerCase().includes(term)) ||
      (company.phone && company.phone.toLowerCase().includes(term)) ||
      (company.tax_id && company.tax_id.toLowerCase().includes(term))
    );
  }

  openCompanyDialog(company?: Company) {
    // TODO: Implementar dialog para crear/editar empresa
    if (company) {
      console.log('Editando empresa:', company);
      // Navegar a página de edición o abrir modal
      this.router.navigate(['/companies', company.id, 'edit']);
    } else {
      console.log('Creando nueva empresa');
      // Navegar a página de creación o abrir modal
      this.router.navigate(['/companies/new']);
    }
  }

  viewCompanyClients(company: Company) {
    // Navegar a la vista de clientes filtrados por empresa
    this.router.navigate(['/clients'], { queryParams: { company_id: company.id } });
  }

  viewCompanyInvoices(company: Company) {
    // Navegar a la vista de facturas filtradas por empresa
    this.router.navigate(['/invoices'], { queryParams: { company_id: company.id } });
  }

  deleteCompany(company: Company) {
    if (confirm(`¿Estás seguro de que quieres eliminar la empresa "${company.name}"?`)) {
      this.companyService.deleteCompany(company.id).subscribe({
        next: (response) => {
          if (response.success) {
            this.snackBar.open('Empresa eliminada exitosamente', 'Cerrar', { duration: 3000 });
            this.loadCompanies(); // Reload the list
          }
        },
        error: (error) => {
          console.error('Error eliminando empresa:', error);
          this.snackBar.open('Error al eliminar empresa', 'Cerrar', { duration: 3000 });
        }
      });
    }
  }

  formatDate(dateString: string): string {
    return new Intl.DateTimeFormat('es-ES', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    }).format(new Date(dateString));
  }
}
