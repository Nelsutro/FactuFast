import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, combineLatest, of } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { ApiService } from '../../services/api.service';
import { ImportBatch } from '../../models';

export interface AppNotification {
  id: string | number;
  type: 'success' | 'warning' | 'error' | 'info';
  title: string;
  message: string;
  timestamp: Date;
  read: boolean;
  actionRoute?: string;
}

@Injectable({ providedIn: 'root' })
export class NotificationService {
  private notificationsSubject = new BehaviorSubject<AppNotification[]>([]);
  notifications$ = this.notificationsSubject.asObservable();

  constructor(private api: ApiService) {}

  load(): void {
    combineLatest([
      this.api.getDashboardStats().pipe(
        map(stats => {
          const list: AppNotification[] = [];

          if (stats?.overdue_invoices && stats.overdue_invoices > 0) {
            list.push({
              id: 'overdue-' + Date.now(),
              type: 'warning',
              title: 'Facturas vencidas',
              message: `${stats.overdue_invoices} factura(s) vencida(s) requieren atención`,
              timestamp: new Date(),
              read: false,
              actionRoute: '/invoices'
            });
          }

          if (stats?.recent_payments && stats.recent_payments > 0) {
            list.push({
              id: 'payments-' + Date.now(),
              type: 'success',
              title: 'Pagos recientes',
              message: `Se registraron ${stats.recent_payments} pago(s) recientemente`,
              timestamp: new Date(),
              read: false,
              actionRoute: '/payments'
            });
          }

          if (stats?.pending_quotes && stats.pending_quotes > 0) {
            list.push({
              id: 'quotes-' + Date.now(),
              type: 'info',
              title: 'Cotizaciones pendientes',
              message: `${stats.pending_quotes} cotización(es) esperando respuesta`,
              timestamp: new Date(),
              read: false,
              actionRoute: '/quotes'
            });
          }

          return list;
        }),
        catchError(() => of([]))
      ),
      this.api.listImportBatches({ alerts_only: 1, per_page: 5 }).pipe(
        map(response => response.data ?? []),
        catchError(() => of([]))
      )
    ]).subscribe(([baseNotifications, importBatches]) => {
      const importNotifications = this.buildImportNotifications(importBatches);
      this.notificationsSubject.next([...baseNotifications, ...importNotifications]);
    });
  }

  markAllAsRead(): void {
    const updated = this.notificationsSubject.value.map(n => ({ ...n, read: true }));
    this.notificationsSubject.next(updated);
  }

  clear(): void {
    this.notificationsSubject.next([]);
  }

  markAsRead(id: string | number): void {
    const updated = this.notificationsSubject.value.map(n => n.id === id ? { ...n, read: true } : n);
    this.notificationsSubject.next(updated);
  }

  private buildImportNotifications(batches: ImportBatch[]): AppNotification[] {
    return batches.map(batch => ({
      id: `import-${batch.id}`,
      type: this.mapAlertLevel(batch.alert_level),
      title: `Importación ${batch.source_filename || '#'+batch.id}`,
      message: this.formatImportMessage(batch),
      timestamp: new Date(batch.finished_at || batch.started_at || new Date().toISOString()),
      read: false,
      actionRoute: '/invoices'
    }));
  }

  private mapAlertLevel(level: ImportBatch['alert_level']): AppNotification['type'] {
    if (level === 'error') return 'error';
    if (level === 'warning') return 'warning';
    if (level === 'success') return 'success';
    return 'info';
  }

  private formatImportMessage(batch: ImportBatch): string {
    if (batch.status === 'failed') {
      return 'La importación falló. Revisa los detalles antes de reintentar.';
    }

    if (batch.status === 'completed' && batch.error_count > 0) {
      return `Importación completada con ${batch.error_count} fila(s) en error.`;
    }

    if (batch.status === 'completed') {
      return `Importación completada: ${batch.success_count} registros agregados.`;
    }

    if (batch.status === 'processing') {
      return 'Importación en curso... te avisaremos al finalizar.';
    }

    return 'Importación pendiente en la cola de procesamiento.';
  }
}
