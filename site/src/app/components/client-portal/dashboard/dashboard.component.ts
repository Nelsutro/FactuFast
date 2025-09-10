import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ClientPortalService, ClientInvoice } from '../../../core/services/client-portal.service';

@Component({
  selector: 'app-client-portal-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatChipsModule,
    MatProgressBarModule
  ]
})
export class ClientPortalDashboardComponent implements OnInit {
  client: any = null;
  invoices: ClientInvoice[] = [];
  loading = true;
  
  constructor(
    private clientPortalService: ClientPortalService,
    private router: Router,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit() {
    this.loadClientData();
    this.loadInvoices();
  }

  loadClientData() {
    const clientData = localStorage.getItem('client_portal_data');
    if (clientData) {
      this.client = JSON.parse(clientData);
    } else {
      this.router.navigate(['/client-portal/access']);
    }
  }

  loadInvoices() {
    const email = localStorage.getItem('client_portal_email');
    const token = localStorage.getItem('client_portal_token');

    if (!email || !token) {
      this.router.navigate(['/client-portal/access']);
      return;
    }

    this.clientPortalService.getInvoices(email, token).subscribe({
      next: (response) => {
        if (response.success && response.data) {
          this.invoices = response.data;
        }
        this.loading = false;
      },
      error: (error) => {
        console.error('Error cargando facturas:', error);
        this.snackBar.open('Error al cargar facturas', 'Cerrar', { duration: 3000 });
        this.loading = false;
      }
    });
  }

  viewInvoice(invoice: ClientInvoice) {
    this.router.navigate(['/client-portal/invoice', invoice.id]);
  }

  payInvoice(invoice: ClientInvoice) {
    this.router.navigate(['/client-portal/pay', invoice.id]);
  }

  getStatusColor(status: string): string {
    switch (status) {
      case 'paid': return 'success';
      case 'pending': return 'warning';
      case 'overdue': return 'danger';
      default: return 'default';
    }
  }

  getStatusText(status: string): string {
    switch (status) {
      case 'paid': return 'Pagada';
      case 'pending': return 'Pendiente';
      case 'overdue': return 'Vencida';
      default: return status;
    }
  }

  get pendingInvoices() {
    return this.invoices.filter(invoice => invoice.status === 'pending');
  }

  get overdueInvoices() {
    return this.invoices.filter(invoice => invoice.is_overdue);
  }

  get totalPending() {
    return this.pendingInvoices.reduce((sum, invoice) => sum + invoice.remaining_amount, 0);
  }

  logout() {
    localStorage.removeItem('client_portal_email');
    localStorage.removeItem('client_portal_token');
    localStorage.removeItem('client_portal_data');
    this.router.navigate(['/client-portal/access']);
  }
}
