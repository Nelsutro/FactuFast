import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatDialog } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatCardModule } from '@angular/material/card';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatMenuModule } from '@angular/material/menu';
import { ClientService, Client } from '../../core/services/client.service';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-clients',
  templateUrl: './clients.component.html',
  styleUrls: ['./clients.component.css'],
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
export class ClientsComponent implements OnInit {
  
  // Component state
  loading = true;
  clients: Client[] = [];
  filteredClients: Client[] = [];
  searchTerm = '';
  error: string | null = null;

  constructor(
    private clientService: ClientService,
    private authService: AuthService,
    private router: Router,
    private snackBar: MatSnackBar,
    private dialog: MatDialog
  ) {}

  ngOnInit() {
    this.loadClients();
  }

  loadClients() {
    this.loading = true;
    this.error = null;
    
    this.clientService.getClients().subscribe({
      next: (response) => {
        if (response.success && response.data) {
          this.clients = response.data;
          this.filteredClients = [...this.clients];
        }
        this.loading = false;
      },
      error: (error) => {
        console.error('Error cargando clientes:', error);
        this.error = 'Error al cargar los clientes. Por favor, intenta de nuevo.';
        this.loading = false;
      }
    });
  }

  filterClients() {
    if (!this.searchTerm.trim()) {
      this.filteredClients = [...this.clients];
      return;
    }

    const term = this.searchTerm.toLowerCase().trim();
    this.filteredClients = this.clients.filter(client => 
      client.name.toLowerCase().includes(term) ||
      (client.email && client.email.toLowerCase().includes(term)) ||
      (client.phone && client.phone.toLowerCase().includes(term))
    );
  }

  openClientDialog(client?: Client) {
    import('./client-dialog.component').then(({ ClientDialogComponent }) => {
      const dialogRef = this.dialog.open(ClientDialogComponent, {
        width: '560px',
        data: { client: client || null },
        disableClose: true
      });

      dialogRef.afterClosed().subscribe((saved: boolean) => {
        if (saved) {
          this.snackBar.open(client ? 'Cliente actualizado' : 'Cliente creado', 'Cerrar', { duration: 2500 });
          this.loadClients();
        }
      });
    });
  }

  viewClientInvoices(client: Client) {
    // Navegar a la vista de facturas filtradas por cliente
    this.router.navigate(['/invoices'], { queryParams: { client_id: client.id } });
  }

  deleteClient(client: Client) {
    if (confirm(`¿Estás seguro de que quieres eliminar el cliente "${client.name}"?`)) {
      this.clientService.deleteClient(client.id).subscribe({
        next: (response) => {
          if (response.success) {
            this.snackBar.open('Cliente eliminado exitosamente', 'Cerrar', { duration: 3000 });
            this.loadClients(); // Reload the list
          }
        },
        error: (error) => {
          console.error('Error eliminando cliente:', error);
          this.snackBar.open('Error al eliminar cliente', 'Cerrar', { duration: 3000 });
        }
      });
    }
  }

  // Helper methods
  formatDate(dateString: string): string {
    return new Intl.DateTimeFormat('es-ES', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    }).format(new Date(dateString));
  }

  getCompanyName(client: Client): string {
    return client.company ? client.company.name : 'N/A';
  }

  isAdmin(): boolean {
    return this.authService.isAdmin();
  }

  isClient(): boolean {
    return this.authService.isClient();
  }
}
