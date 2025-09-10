import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { MatTableModule } from '@angular/material/table';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ClientPortalService } from '../../../core/services/client-portal.service';

@Component({
  selector: 'app-invoice-detail',
  templateUrl: './invoice-detail.component.html',
  styleUrls: ['./invoice-detail.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatChipsModule,
    MatTableModule,
    MatProgressSpinnerModule
  ]
})
export class InvoiceDetailComponent implements OnInit {
  invoice: any = null;
  loading = true;
  invoiceId!: number;
  displayedColumns: string[] = ['description', 'quantity', 'unit_price', 'total'];

  constructor(
    private clientPortalService: ClientPortalService,
    private router: Router,
    private route: ActivatedRoute,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit() {
    this.route.params.subscribe(params => {
      this.invoiceId = +params['id'];
      this.loadInvoice();
    });
  }

  loadInvoice() {
    const email = localStorage.getItem('client_portal_email');
    const token = localStorage.getItem('client_portal_token');

    if (!email || !token) {
      this.router.navigate(['/client-portal/access']);
      return;
    }

    this.clientPortalService.getInvoice(this.invoiceId, email, token).subscribe({
      next: (response) => {
        if (response.success && response.data) {
          this.invoice = response.data;
        }
        this.loading = false;
      },
      error: (error) => {
        console.error('Error cargando factura:', error);
        this.snackBar.open('Error al cargar la factura', 'Cerrar', { duration: 3000 });
        this.loading = false;
      }
    });
  }

  goBack() {
    this.router.navigate(['/client-portal/dashboard']);
  }

  payInvoice() {
    this.router.navigate(['/client-portal/pay', this.invoiceId]);
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
}
