import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
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
import { MatChipsModule } from '@angular/material/chips';
import { MatTooltipModule } from '@angular/material/tooltip';
import { ClientService, Client } from '../../core/services/client.service';
import { AuthService } from '../../core/services/auth.service';
import { LoadingComponent } from '../shared/loading/loading.component';

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
    MatMenuModule,
    MatChipsModule,
    MatTooltipModule,
    LoadingComponent
  ]
})
export class ClientsComponent implements OnInit {
  @ViewChild('fileInput') fileInput!: ElementRef<HTMLInputElement>;
  
  // Component state
  loading = true;
  clients: Client[] = [];
  filteredClients: Client[] = [];
  searchTerm = '';
  error: string | null = null;
  filterSegment: 'all' | 'withEmail' | 'withoutEmail' = 'all';

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

  // CSV Export/Import
  exportCsv() {
    // Llamar al endpoint /clients/export que devuelve un CSV
    // Usaremos fetch para manejar blob con headers auth ya presentes via cookies/token si es necesario.
    const token = localStorage.getItem('auth_token');
    fetch(`${(window as any).ENV_API_URL || ''}/clients/export`, {
      headers: token ? { 'Authorization': `Bearer ${token}` } : {}
    }).then(async res => {
      if (!res.ok) throw new Error('No se pudo exportar');
      const blob = await res.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `clientes_${new Date().toISOString().slice(0,19).replace(/[:T]/g,'-')}.csv`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
    }).catch(() => {
      this.snackBar.open('Error al exportar CSV', 'Cerrar', { duration: 2500 });
    });
  }

  triggerImport() {
    this.fileInput.nativeElement.value = '';
    this.fileInput.nativeElement.click();
  }

  onFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];

    const formData = new FormData();
    formData.append('file', file);

    const token = localStorage.getItem('auth_token');
    fetch(`${(window as any).ENV_API_URL || ''}/clients/import`, {
      method: 'POST',
      headers: token ? { 'Authorization': `Bearer ${token}` } as any : undefined,
      body: formData
    }).then(async res => {
      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(json?.message || 'Error');
      const created = json?.data?.created ?? 0;
      const skipped = json?.data?.skipped ?? 0;
      this.snackBar.open(`Importación completa: creados ${created}, omitidos ${skipped}`, 'Cerrar', { duration: 3500 });
      this.loadClients();
    }).catch((e) => {
      this.snackBar.open(`Error al importar CSV: ${e?.message || ''}`.trim(), 'Cerrar', { duration: 3500 });
    });
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
    const term = this.searchTerm.toLowerCase().trim();
    let list = [...this.clients];

    // Segment filter
    if (this.filterSegment === 'withEmail') {
      list = list.filter(c => !!c.email);
    } else if (this.filterSegment === 'withoutEmail') {
      list = list.filter(c => !c.email);
    }

    // Text search
    if (term) {
      list = list.filter(client => 
        client.name.toLowerCase().includes(term) ||
        (client.email && client.email.toLowerCase().includes(term)) ||
        (client.phone && client.phone.toLowerCase().includes(term))
      );
    }

    this.filteredClients = list;
  }

  setSegment(seg: 'all' | 'withEmail' | 'withoutEmail') {
    if (this.filterSegment !== seg) {
      this.filterSegment = seg;
      this.filterClients();
    }
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
