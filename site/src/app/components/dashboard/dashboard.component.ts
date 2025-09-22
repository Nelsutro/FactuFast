// src/app/components/dashboard/dashboard.component.ts
import { Component, OnInit, ViewChild, ElementRef, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Chart, ChartConfiguration, ChartType, registerables } from 'chart.js';
import { firstValueFrom } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../core/services/auth.service';
import { DashboardStats, Invoice, User } from '../../models';

import { MatSelectModule } from '@angular/material/select';
import { MatOptionModule } from '@angular/material/core';
import { MatChipsModule, MatChip } from '@angular/material/chips';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatListModule } from '@angular/material/list';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { InvoiceCreateComponent } from '../invoices/invoice-create.component';
import { QuoteCreateComponent } from '../quotes/quote-create.component';
import { ClientCreateComponent } from '../clients/client-create.component';
import { LoadingComponent } from '../shared/loading/loading.component';

Chart.register(...registerables);

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    MatSelectModule,
    MatOptionModule,
    MatChipsModule,
    MatIconModule,
    MatButtonModule,
    MatCardModule,
    MatListModule,
    MatProgressSpinnerModule,
    MatSnackBarModule,
    MatDialogModule,
    InvoiceCreateComponent,
    QuoteCreateComponent,
    ClientCreateComponent,
    LoadingComponent
  ]
})
export class DashboardComponent implements OnInit, OnDestroy {
  @ViewChild('revenueChart') revenueChartRef!: ElementRef<HTMLCanvasElement>;
  @ViewChild('statusChart') statusChartRef!: ElementRef<HTMLCanvasElement>;

  // Data properties
  dashboardStats: DashboardStats | null = null;
  currentUser: User | null = null;
  loading = true;
  error: string | null = null;
  chartPeriod = '6m';

  // Charts
  revenueChart: Chart | null = null;
  statusChart: Chart | null = null;

  // Alerts
  alerts = {
    overdue: 0,
    pending: 0,
    quotes: 0
  };

  constructor(
    private apiService: ApiService,
    private authService: AuthService,
    private router: Router,
    private snackBar: MatSnackBar,
    private dialog: MatDialog
  ) {}

  ngOnInit() {
    this.loadUserData();
    this.loadDashboardData();
  }

  private loadUserData() {
    this.authService.currentUser$.subscribe(user => {
      this.currentUser = user;
    });
  }

  ngOnDestroy() {
    // Destroy charts to prevent memory leaks
    if (this.revenueChart) {
      this.revenueChart.destroy();
    }
    if (this.statusChart) {
      this.statusChart.destroy();
    }
  }

  async loadDashboardData() {
    try {
      this.loading = true;
      this.error = null;

      // Cargar datos reales desde el API
      const period = this.mapChartPeriod(this.chartPeriod);
      const [stats, revenueData, invoicesResp] = await Promise.all([
        firstValueFrom(this.apiService.getDashboardStats()),
        firstValueFrom(this.apiService.getRevenueChart(period)),
        firstValueFrom(this.apiService.getInvoices({ per_page: 5, sort: 'desc' }))
      ]);

      // Extraer facturas recientes (manejo defensivo de distintas formas de respuesta)
      let recent: any[] = [];
      // Si stats ya trae recent_invoices, úsalo directamente
      if (Array.isArray((stats as any)?.recent_invoices)) {
        recent = (stats as any).recent_invoices;
      } else {
        const raw: any = invoicesResp;
        if (Array.isArray(raw?.data)) {
          recent = raw.data;
        } else if (Array.isArray(raw?.data?.data)) {
          recent = raw.data.data;
        } else if (Array.isArray(raw?.items)) {
          recent = raw.items;
        }
      }

      // Mapear datos del gráfico de ingresos
      const revenue_chart = this.mapRevenueResponseToChartData(revenueData);

      // Construir gráfico de estados a partir de stats disponibles
      const invoice_status_chart = [
        { status: 'Pagadas', count: stats?.paid_invoices ?? 0, color: '#22c55e' },
        { status: 'Pendientes', count: stats?.pending_invoices ?? 0, color: '#eab308' }
      ];

      this.dashboardStats = {
        pending_invoices: stats?.pending_invoices ?? 0,
        paid_invoices: stats?.paid_invoices ?? 0,
        total_revenue: stats?.total_revenue ?? 0,
        active_quotes: stats?.active_quotes ?? 0,
        recent_invoices: recent as Invoice[],
        revenue_chart,
        invoice_status_chart
      };

      // Setear alertas desde datos reales disponibles
      this.alerts = {
        overdue: (stats as any)?.overdue_invoices ?? 0,
        pending: this.dashboardStats.pending_invoices ?? 0,
        quotes: (stats as any)?.pending_quotes ?? (this.dashboardStats.active_quotes ?? 0)
      };

      // Crear gráficos después de cargar datos
      setTimeout(() => {
        this.createRevenueChart();
        this.createStatusChart();
      }, 100);

    } catch (error) {
      this.error = 'Error al cargar los datos del dashboard';
      console.error('Dashboard error:', error);
    } finally {
      this.loading = false;
    }
  }

  // Mapear respuesta de /dashboard/revenue al formato usado por el componente
  private mapRevenueResponseToChartData(revenueResponse: any) {
    if (!revenueResponse) return [];
    const labels: string[] = revenueResponse.labels || [];
    const datasets = revenueResponse.datasets || [];
    const data: number[] = (datasets[0]?.data) || [];
    return labels.map((label, idx) => ({ month: label, revenue: Number(data[idx] ?? 0) }));
  }

  // Mapear el periodo del selector al esperado por el backend
  private mapChartPeriod(period: string): string | undefined {
    // El backend usa 'period' (p.ej. 'monthly'). Ajustar si hay otros valores válidos.
    if (period === '6m') return 'monthly';
    if (period === '1y') return 'yearly';
    return undefined;
  }

  createRevenueChart() {
    if (!this.dashboardStats?.revenue_chart) return;

    const ctx = this.revenueChartRef.nativeElement.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (this.revenueChart) {
      this.revenueChart.destroy();
    }

    const config: ChartConfiguration = {
      type: 'line' as ChartType,
      data: {
        labels: this.dashboardStats.revenue_chart.map(item => item.month),
        datasets: [{
          label: 'Ingresos',
          data: this.dashboardStats.revenue_chart.map(item => item.revenue),
          borderColor: 'rgb(59, 130, 246)',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          tension: 0.1,
          fill: true
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return '$' + Number(value).toLocaleString();
              }
            }
          }
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return 'Ingresos: $' + Number(context.parsed.y).toLocaleString();
              }
            }
          }
        }
      }
    };

    this.revenueChart = new Chart(ctx, config);
  }

  createStatusChart() {
    if (!this.dashboardStats?.invoice_status_chart) return;

    const ctx = this.statusChartRef.nativeElement.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (this.statusChart) {
      this.statusChart.destroy();
    }

    const config: ChartConfiguration = {
      type: 'doughnut' as ChartType,
      data: {
        labels: this.dashboardStats.invoice_status_chart.map(item => item.status),
        datasets: [{
          data: this.dashboardStats.invoice_status_chart.map(item => item.count),
          backgroundColor: this.dashboardStats.invoice_status_chart.map(item => item.color)
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    };

    this.statusChart = new Chart(ctx, config);
  }

  // Event handlers
  onChartPeriodChange() {
    // Recargar solo el gráfico de ingresos según el periodo seleccionado
    const period = this.mapChartPeriod(this.chartPeriod);
    firstValueFrom(this.apiService.getRevenueChart(period)).then((revenueData) => {
      const chartData = this.mapRevenueResponseToChartData(revenueData);
      if (!this.dashboardStats) {
        this.dashboardStats = {
          pending_invoices: 0,
          paid_invoices: 0,
          total_revenue: 0,
          active_quotes: 0,
          recent_invoices: [],
          revenue_chart: chartData,
          invoice_status_chart: []
        };
      } else {
        this.dashboardStats.revenue_chart = chartData;
      }

      // Actualizar el gráfico si ya existe
      if (this.revenueChart) {
        this.revenueChart.data.labels = chartData.map(i => i.month);
        // @ts-ignore
        this.revenueChart.data.datasets[0].data = chartData.map(i => i.revenue);
        this.revenueChart.update();
      } else {
        setTimeout(() => this.createRevenueChart(), 50);
      }
    }).catch(err => {
      console.error('Error recargando gráfico de ingresos:', err);
    });
  }

  // Navigation methods
  createInvoice() {
    const ref = this.dialog.open(InvoiceCreateComponent, {
      width: '800px',
      disableClose: true
    });
    ref.afterClosed().subscribe((created: boolean) => {
      if (created) {
        this.snackBar.open('Factura creada', 'Cerrar', { duration: 2500 });
        this.loadDashboardData();
      }
    });
  }

  createQuote() {
    const ref = this.dialog.open(QuoteCreateComponent, {
      width: '800px',
      disableClose: true
    });
    ref.afterClosed().subscribe((created: boolean) => {
      if (created) {
        this.snackBar.open('Cotización creada', 'Cerrar', { duration: 2500 });
        this.loadDashboardData();
      }
    });
  }

  createClient() {
    const ref = this.dialog.open(ClientCreateComponent, {
      width: '600px',
      disableClose: true
    });
    ref.afterClosed().subscribe((created: boolean) => {
      if (created) {
        this.snackBar.open('Cliente creado', 'Cerrar', { duration: 2500 });
        this.loadDashboardData();
      }
    });
  }

  importInvoices() {
    // Intenta disparar el input de Invoices si está en el DOM (por ejemplo en el listado)
    const input = document.getElementById('invoicesCsvInput') as HTMLInputElement | null;
    if (input) {
      input.click();
      this.snackBar.open('Selecciona un archivo CSV para importar', 'Cerrar', { duration: 2500 });
    } else {
      // Si no existe el input (p.ej. desde Dashboard), informa al usuario y navega al listado
      this.snackBar.open('Abriremos Facturas para importar el CSV...', undefined, { duration: 1800 });
      this.router.navigate(['/invoices']).then(() => {
        setTimeout(() => {
          const afterNavInput = document.getElementById('invoicesCsvInput') as HTMLInputElement | null;
          if (afterNavInput) afterNavInput.click();
        }, 300);
      });
    }
  }

  goToInvoices() {
    this.router.navigate(['/invoices']);
  }

  goToClients() {
    this.router.navigate(['/clients']);
  }

  goToQuotes() {
    this.router.navigate(['/quotes']);
  }

  viewInvoice(id: number) {
    this.router.navigate(['/invoices', id]);
  }

  // Utility methods
  formatCurrency(amount: number): string {
    return amount.toLocaleString('es-CL', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
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

  getStatusIcon(status: string): string {
    const icons: { [key: string]: string } = {
      'pending': 'schedule',
      'paid': 'check_circle',
      'cancelled': 'cancel'
    };
    return icons[status] || 'help_outline';
  }

  getStatusIconClass(status: string): string {
    const classes: { [key: string]: string } = {
      'pending': 'pending-icon',
      'paid': 'paid-icon',
      'cancelled': 'cancelled-icon'
    };
    return classes[status] || 'default-icon';
  }

  getStatusChipClass(status: string): string {
    const classes: { [key: string]: string } = {
      'pending': 'pending-chip',
      'paid': 'paid-chip',
      'cancelled': 'cancelled-chip'
    };
    return classes[status] || 'default-chip';
  }

  logout(): void {
    console.log('Cerrando sesión...');
    this.authService.logout().subscribe({
      next: () => {
        console.log('Logout exitoso');
        this.router.navigate(['/login']);
      },
      error: (error) => {
        console.error('Error en logout:', error);
        // Aunque haya error, remover token localmente
        this.router.navigate(['/login']);
      }
    });
  }
}
