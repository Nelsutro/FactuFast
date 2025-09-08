import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { AuthService } from '../../core/services/auth.service';
import { DashboardService } from '../../core/services/dashboard.service';
import { ClientService, Client } from '../../core/services/client.service';
import { DashboardStats } from '../../core/interfaces/api-response.interface';

@Component({
  selector: 'app-connection-test',
  standalone: true,
  imports: [CommonModule, MatCardModule, MatButtonModule, MatSnackBarModule],
  template: `
    <div class="container">
      <h2>Prueba de Conexión Backend</h2>
      
      <mat-card class="test-card">
        <mat-card-header>
          <mat-card-title>Estado de la Conexión</mat-card-title>
        </mat-card-header>
        <mat-card-content>
          <p>Estado del servidor: <span [class]="serverStatus === 'online' ? 'status-online' : 'status-offline'">
            {{ serverStatus === 'online' ? 'En línea' : 'Desconectado' }}
          </span></p>
          
          <div class="test-buttons">
            <button mat-raised-button color="primary" (click)="testDashboardConnection()">
              Probar Dashboard
            </button>
            <button mat-raised-button color="accent" (click)="testClientConnection()">
              Probar Clientes
            </button>
            <button mat-raised-button color="warn" (click)="testAuthConnection()">
              Probar Auth
            </button>
          </div>
        </mat-card-content>
      </mat-card>

      <mat-card class="results-card" *ngIf="testResults.length > 0">
        <mat-card-header>
          <mat-card-title>Resultados de Pruebas</mat-card-title>
        </mat-card-header>
        <mat-card-content>
          <div *ngFor="let result of testResults" class="test-result">
            <strong>{{ result.test }}:</strong>
            <span [class]="result.success ? 'result-success' : 'result-error'">
              {{ result.success ? 'Exitoso' : 'Error' }}
            </span>
            <p *ngIf="result.message">{{ result.message }}</p>
            <pre *ngIf="result.data">{{ result.data | json }}</pre>
          </div>
        </mat-card-content>
      </mat-card>
    </div>
  `,
  styles: [`
    .container {
      padding: 20px;
      max-width: 800px;
      margin: 0 auto;
    }

    .test-card, .results-card {
      margin-bottom: 20px;
    }

    .test-buttons {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 20px;
    }

    .status-online {
      color: #4caf50;
      font-weight: bold;
    }

    .status-offline {
      color: #f44336;
      font-weight: bold;
    }

    .test-result {
      margin-bottom: 15px;
      padding: 10px;
      border: 1px solid #e0e0e0;
      border-radius: 4px;
    }

    .result-success {
      color: #4caf50;
      font-weight: bold;
    }

    .result-error {
      color: #f44336;
      font-weight: bold;
    }

    pre {
      background: #f5f5f5;
      padding: 10px;
      border-radius: 4px;
      overflow-x: auto;
      font-size: 12px;
    }
  `]
})
export class ConnectionTestComponent implements OnInit {
  serverStatus: 'online' | 'offline' = 'offline';
  testResults: any[] = [];

  constructor(
    private authService: AuthService,
    private dashboardService: DashboardService,
    private clientService: ClientService,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit() {
    this.checkServerStatus();
  }

  async checkServerStatus() {
    try {
      const response = await fetch('http://127.0.0.1:8000/api/dashboard/stats');
      this.serverStatus = response.ok ? 'online' : 'offline';
    } catch (error) {
      this.serverStatus = 'offline';
    }
  }

  testDashboardConnection() {
    this.dashboardService.getStats().subscribe({
      next: (response) => {
        this.addTestResult('Dashboard Stats', true, 'Conexión exitosa', response.data);
        this.snackBar.open('Dashboard conectado correctamente', 'Cerrar', { duration: 3000 });
      },
      error: (error) => {
        this.addTestResult('Dashboard Stats', false, error.message, error);
        this.snackBar.open('Error al conectar con dashboard', 'Cerrar', { duration: 3000 });
      }
    });
  }

  testClientConnection() {
    this.clientService.getClients().subscribe({
      next: (response) => {
        this.addTestResult('Clientes', true, 'Lista de clientes obtenida', response.data);
        this.snackBar.open('Clientes conectados correctamente', 'Cerrar', { duration: 3000 });
      },
      error: (error) => {
        this.addTestResult('Clientes', false, error.message, error);
        this.snackBar.open('Error al conectar con clientes', 'Cerrar', { duration: 3000 });
      }
    });
  }

  testAuthConnection() {
    // Probar un endpoint público de auth
    this.authService.checkConnection().subscribe({
      next: (response: any) => {
        this.addTestResult('Autenticación', true, 'Endpoint de auth disponible', response);
        this.snackBar.open('Auth conectado correctamente', 'Cerrar', { duration: 3000 });
      },
      error: (error: any) => {
        this.addTestResult('Autenticación', false, error.message, error);
        this.snackBar.open('Error al conectar con auth', 'Cerrar', { duration: 3000 });
      }
    });
  }

  private addTestResult(test: string, success: boolean, message: string, data?: any) {
    this.testResults.unshift({
      test,
      success,
      message,
      data: data ? (typeof data === 'object' ? data : { result: data }) : null,
      timestamp: new Date()
    });
  }
}
