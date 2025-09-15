import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, combineLatest, of } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { ApiService } from '../../services/api.service';

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
    // Derivar notificaciones desde stats del dashboard
    this.api.getDashboardStats().pipe(
      map(stats => {
        const list: AppNotification[] = [];

        // Facturas vencidas
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

        // Pagos recientes
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

        // Cotizaciones pendientes
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
    ).subscribe(list => this.notificationsSubject.next(list));
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
}
