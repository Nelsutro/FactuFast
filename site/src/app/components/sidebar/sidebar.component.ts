import { Component, Output, EventEmitter, OnInit, OnDestroy } from '@angular/core';
import { Router, RouterModule } from '@angular/router';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatListModule } from '@angular/material/list';
import { MatIconModule } from '@angular/material/icon';
import { MatBadgeModule } from '@angular/material/badge';
import { MatTooltipModule } from '@angular/material/tooltip';
import { CommonModule } from '@angular/common';
import { Subject, takeUntil } from 'rxjs';
import { InvoiceService } from '../../services/invoice.service';
import { ClientService } from '../../services/client.service';
import { QuoteService } from '../../services/quote.service';
import { PaymentService } from '../../services/payment.service';

@Component({
  selector: 'app-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    RouterModule,
    MatListModule,
    MatIconModule,
    MatBadgeModule,
    MatTooltipModule
  ]
})
export class SidebarComponent implements OnInit, OnDestroy {
  @Output() closeSidenav = new EventEmitter<void>();

  // Navigation counters and badges
  dashboardNotifications: number = 0;
  pendingInvoices: number = 0;
  expiredQuotes: number = 0;
  totalClients: number = 0;
  overduePayments: number = 0;
  
  // App info
  appVersion: string = '1.0.0';

  // Cleanup
  private destroy$ = new Subject<void>();

  constructor(
    private router: Router,
    private snackBar: MatSnackBar,
    private invoiceService: InvoiceService,
    private clientService: ClientService,
    private quoteService: QuoteService,
    private paymentService: PaymentService
  ) {}

  ngOnInit() {
    this.loadNavigationData();
    this.setupDataRefresh();
  }

  ngOnDestroy() {
    this.destroy$.next();
    this.destroy$.complete();
  }

  // Navigation Methods
  onItemClick() {
    // Close sidenav on mobile after navigation
    this.closeSidenav.emit();
  }

  // Quick Actions
  quickCreateInvoice() {
    this.router.navigate(['/invoices/create']);
    this.onItemClick();
    this.showSuccessMessage('Creando nueva factura...');
  }

  quickCreateClient() {
    this.router.navigate(['/clients/create']);
    this.onItemClick();
    this.showSuccessMessage('Creando nuevo cliente...');
  }

  // Data Loading Methods
  private loadNavigationData() {
    this.loadInvoiceData();
  //  this.loadClientData();
  //  this.loadQuoteData();
  //  this.loadPaymentData();
    this.loadDashboardNotifications();
  }

  private loadInvoiceData() {
    this.invoiceService.getInvoices()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (invoices) => {
          this.pendingInvoices = invoices.filter(inv => inv.status === 'pending').length;
        },
        error: (error) => {
          console.error('Error loading invoice data:', error);
          // Use mock data as fallback
          this.pendingInvoices = 5;
        }
      });
  }

//  private loadClientData() {
//    this.clientService.getClients()
//      .pipe(takeUntil(this.destroy$))
//      .subscribe({
//        next: (clients) => {
//          this.totalClients = clients.length;
//        },
//        error: (error) => {
//          console.error('Error loading client data:', error);
//          // Use mock data as fallback
//          this.totalClients = 25;
//        }
//      });
//  }
//
//  private loadQuoteData() {
//    this.quoteService.getQuotes()
//      .pipe(takeUntil(this.destroy$))
//      .subscribe({
//        next: (quotes) => {
//          const now = new Date();
//          this.expiredQuotes = quotes.filter(quote => 
//            new Date(quote.expiry_date) < now && quote.status === 'pending'
//          ).length;
//        },
//        error: (error) => {
//          console.error('Error loading quote data:', error);
//          // Use mock data as fallback
//          this.expiredQuotes = 2;
//        }
//      });
//  }
//
//  private loadPaymentData() {
//    this.paymentService.getPayments()
//      .pipe(takeUntil(this.destroy$))
//      .subscribe({
//        next: (payments) => {
//          const now = new Date();
//          // Count overdue invoices (payments not received by due date)
//          this.overduePayments = 3; // This would need more complex logic with invoice data
//        },
//        error: (error) => {
//          console.error('Error loading payment data:', error);
//          // Use mock data as fallback
//          this.overduePayments = 3;
//        }
//      });
//  }

  private loadDashboardNotifications() {
    // Calculate total notifications for dashboard badge
    setTimeout(() => {
      this.dashboardNotifications = this.pendingInvoices + this.expiredQuotes + this.overduePayments;
    }, 100);
  }

  private setupDataRefresh() {
    // Refresh data every 5 minutes
    setInterval(() => {
      this.loadNavigationData();
    }, 5 * 60 * 1000);
  }

  // Utility Methods
  private showSuccessMessage(message: string) {
    this.snackBar.open(message, 'Cerrar', {
      duration: 3000,
      horizontalPosition: 'end',
      verticalPosition: 'top'
    });
  }

  // Public methods for external updates
  refreshNavigationData() {
    this.loadNavigationData();
  }

  updateInvoiceCount(count: number) {
    this.pendingInvoices = count;
    this.loadDashboardNotifications();
  }

  updateClientCount(count: number) {
    this.totalClients = count;
  }

  updateQuoteCount(count: number) {
    this.expiredQuotes = count;
    this.loadDashboardNotifications();
  }

  updatePaymentCount(count: number) {
    this.overduePayments = count;
    this.loadDashboardNotifications();
  }
}
