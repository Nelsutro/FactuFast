// src/app/components/dashboard/dashboard.component.ts
import { Component, OnInit, ViewChild, ElementRef, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { Chart, ChartConfiguration, ChartType, registerables } from 'chart.js';
import { ApiService } from '../../services/api.service';
import { DashboardStats, Invoice } from '../../models';

Chart.register(...registerables);

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  standalone: false
})
export class DashboardComponent implements OnInit, OnDestroy {
  @ViewChild('revenueChart') revenueChartRef!: ElementRef<HTMLCanvasElement>;
  @ViewChild('statusChart') statusChartRef!: ElementRef<HTMLCanvasElement>;

  // Data properties
  dashboardStats: DashboardStats | null = null;
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
    private router: Router
  ) {}

  ngOnInit() {
    this.loadDashboardData();
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

      // Simulate API call (replace with real API call)
      const response = await this.simulateApiCall();
      this.dashboardStats = response;
      
      // Set alerts
      this.alerts = {
        overdue: 3, // Calculate from real data
        pending: 5,
        quotes: 2
      };

      // Create charts after data is loaded
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

  // Simulate API call - Replace this with real apiService.getDashboardStats()
  private simulateApiCall(): Promise<DashboardStats> {
    return new Promise((resolve) => {
      setTimeout(() => {
        resolve({
          pending_invoices: 24,
          paid_invoices: 156,
          total_revenue: 48530.75,
          active_quotes: 12,
          recent_invoices: [
            {
              id: 1,
              company_id: 1,
              client_id: 1,
              invoice_number: '001234',
              amount: 1250.00,
              status: 'pending',
              issue_date: new Date(),
              due_date: new Date(),
              created_at: new Date(),
              updated_at: new Date(),
              client: { 
                id: 1, 
                company_id: 1, 
                name: 'ABC Corp', 
                created_at: new Date(), 
                updated_at: new Date() 
              }
            },
            {
              id: 2,
              company_id: 1,
              client_id: 2,
              invoice_number: '001235',
              amount: 2850.00,
              status: 'paid',
              issue_date: new Date(),
              due_date: new Date(),
              created_at: new Date(),
              updated_at: new Date(),
              client: { 
                id: 2, 
                company_id: 1, 
                name: 'XYZ Ltd', 
                created_at: new Date(), 
                updated_at: new Date() 
              }
            }
          ],
          revenue_chart: [
            { month: 'Ene', revenue: 12000 },
            { month: 'Feb', revenue: 19000 },
            { month: 'Mar', revenue: 15000 },
            { month: 'Abr', revenue: 25000 },
            { month: 'May', revenue: 22000 },
            { month: 'Jun', revenue: 30000 }
          ],
          invoice_status_chart: [
            { status: 'Pagadas', count: 156, color: '#22c55e' },
            { status: 'Pendientes', count: 24, color: '#eab308' },
            { status: 'Vencidas', count: 8, color: '#ef4444' }
          ]
        });
      }, 1000);
    });
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
    console.log('Chart period changed to:', this.chartPeriod);
    // Here you would reload chart data based on the selected period
    // this.loadChartData(this.chartPeriod);
  }

  // Navigation methods
  createInvoice() {
    this.router.navigate(['/invoices/create']);
  }

  createQuote() {
    this.router.navigate(['/quotes/create']);
  }

  createClient() {
    this.router.navigate(['/clients/create']);
  }

  importInvoices() {
    this.router.navigate(['/invoices/import']);
  }

  goToInvoices() {
    this.router.navigate(['/invoices']);
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
}