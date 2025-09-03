import { Component, Output, EventEmitter, OnInit, OnDestroy, ViewChild } from '@angular/core';
import { MatMenuTrigger } from '@angular/material/menu';
import { Router } from '@angular/router';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatDialog } from '@angular/material/dialog';
import { Subject, debounceTime, distinctUntilChanged, takeUntil } from 'rxjs';
import { AuthService } from '../../services/auth.service';
import { ConfirmDialogComponent } from '../dialogs/confirm-dialog/confirm-dialog.component';

interface SearchResult {
  id: number;
  display: string;
  type: string;
  icon: string;
  route: string;
}

interface Notification {
  id: number;
  title: string;
  message: string;
  type: 'info' | 'warning' | 'error' | 'success';
  timestamp: Date;
  read: boolean;
  actionRoute?: string;
}

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.css'],
  standalone: false
})
export class HeaderComponent implements OnInit, OnDestroy {
  @Output() toggleSidenav = new EventEmitter<void>();
  @ViewChild('notificationsBtn', { read: MatMenuTrigger }) notificationsTrigger!: MatMenuTrigger;

  // Search properties
  searchTerm: string = '';
  searchResults: SearchResult[] = [];
  private searchSubject = new Subject<string>();

  // User properties
  userName: string = 'Usuario Admin';
  userEmail: string = 'admin@factufast.com';
  userRole: string = 'admin';
  userAvatar: string = '';
  
  // Notifications
  notifications: Notification[] = [];
  notificationCount: number = 0;
  hasNewNotifications: boolean = false;

  // UI State
  isLoading: boolean = false;
  isDarkTheme: boolean = false;
  updateCount: number = 2;

  // Cleanup
  private destroy$ = new Subject<void>();

  constructor(
    private authService: AuthService,
    private router: Router,
    private snackBar: MatSnackBar,
    private dialog: MatDialog
  ) {}

  ngOnInit() {
    this.initializeUser();
    this.setupSearch();
    this.loadNotifications();
    this.loadThemePreference();
  }

  ngOnDestroy() {
    this.destroy$.next();
    this.destroy$.complete();
  }

  // Initialization Methods
  private initializeUser() {
    const currentUser = this.authService.currentUserValue;
    if (currentUser) {
      this.userName = currentUser.name || 'Usuario';
      this.userEmail = currentUser.email || '';
      this.userRole = currentUser.role || 'client';
    }
    
    this.userAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(this.userName)}&background=1976d2&color=fff&size=40`;
  }

  private setupSearch() {
    this.searchSubject
      .pipe(
        debounceTime(300),
        distinctUntilChanged(),
        takeUntil(this.destroy$)
      )
      .subscribe(searchTerm => {
        if (searchTerm.length >= 2) {
          this.performSearchQuery(searchTerm);
        } else {
          this.searchResults = [];
        }
      });
  }

  private loadNotifications() {
    // Simulate loading notifications
    this.notifications = [
      {
        id: 1,
        title: 'Factura Vencida',
        message: 'La factura #001234 de ABC Corp está vencida',
        type: 'error',
        timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000), // 2 hours ago
        read: false,
        actionRoute: '/invoices/1'
      },
      {
        id: 2,
        title: 'Pago Recibido',
        message: 'Se recibió el pago de $2,850 de XYZ Ltd',
        type: 'success',
        timestamp: new Date(Date.now() - 5 * 60 * 60 * 1000), // 5 hours ago
        read: false,
        actionRoute: '/payments/2'
      },
      {
        id: 3,
        title: 'Cotización Pendiente',
        message: 'La cotización Q-2024-005 expira mañana',
        type: 'warning',
        timestamp: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000), // 1 day ago
        read: true,
        actionRoute: '/quotes/5'
      }
    ];

    this.updateNotificationCount();
  }

  private loadThemePreference() {
    this.isDarkTheme = localStorage.getItem('darkTheme') === 'true';
  }

  // Navigation Methods
  onToggleSidenav() {
    this.toggleSidenav.emit();
  }

  goToProfile() {
    this.router.navigate(['/profile']);
  }

  goToSettings() {
    this.router.navigate(['/settings']);
  }

  viewHelp() {
    this.router.navigate(['/help']);
  }

  viewUpdates() {
    this.router.navigate(['/updates']);
    this.updateCount = 0; // Reset update count
  }

  viewAllNotifications() {
    this.router.navigate(['/notifications']);
    this.notificationsTrigger.closeMenu();
  }

  // Search Methods
  onSearch() {
    this.searchSubject.next(this.searchTerm);
  }

  private performSearchQuery(term: string) {
    // Simulate search API call
    const mockResults: SearchResult[] = [
      { id: 1, display: `Factura #001234 - ABC Corp`, type: 'Factura', icon: 'receipt', route: '/invoices/1' },
      { id: 2, display: `Cliente: ABC Corp`, type: 'Cliente', icon: 'person', route: '/clients/1' },
      { id: 3, display: `Cotización Q-2024-001`, type: 'Cotización', icon: 'format_quote', route: '/quotes/1' }
    ].filter(item => 
      item.display.toLowerCase().includes(term.toLowerCase())
    );

    this.searchResults = mockResults;
  }

  performSearch() {
    if (this.searchTerm.trim()) {
      this.router.navigate(['/search'], { 
        queryParams: { q: this.searchTerm } 
      });
    }
  }

  clearSearch() {
    this.searchTerm = '';
    this.searchResults = [];
  }

  onSearchOptionSelected(event: any) {
    const selectedResult = this.searchResults.find(r => r.display === event.option.value);
    if (selectedResult) {
      this.router.navigate([selectedResult.route]);
      this.clearSearch();
    }
  }

  // Quick Actions
  quickCreateInvoice() {
    this.router.navigate(['/invoices/create']);
    this.showSuccessMessage('Creando nueva factura...');
  }

  quickCreateClient() {
    this.router.navigate(['/clients/create']);
    this.showSuccessMessage('Creando nuevo cliente...');
  }

  // Notification Methods
  toggleNotifications() {
    // This will be handled by the matMenuTriggerFor
  }

  handleNotificationClick(notification: Notification) {
    if (!notification.read) {
      notification.read = true;
      this.updateNotificationCount();
    }

    if (notification.actionRoute) {
      this.router.navigate([notification.actionRoute]);
    }

    this.notificationsTrigger.closeMenu();
  }

  dismissNotification(notification: Notification, event: Event) {
    event.stopPropagation();
    this.notifications = this.notifications.filter(n => n.id !== notification.id);
    this.updateNotificationCount();
    
    this.showSuccessMessage('Notificación eliminada');
  }

  markAllAsRead() {
    this.notifications.forEach(n => n.read = true);
    this.updateNotificationCount();
    this.showSuccessMessage('Todas las notificaciones marcadas como leídas');
  }

  private updateNotificationCount() {
    this.notificationCount = this.notifications.filter(n => !n.read).length;
    this.hasNewNotifications = this.notificationCount > 0;
  }

  // Utility Methods
  getNotificationIcon(type: string): string {
    const icons = {
      'info': 'info',
      'warning': 'warning',
      'error': 'error',
      'success': 'check_circle'
    };
    return icons[type as keyof typeof icons] || 'notifications';
  }

  getNotificationColor(type: string): string {
    const colors = {
      'info': '#2196f3',
      'warning': '#ff9800',
      'error': '#f44336',
      'success': '#4caf50'
    };
    return colors[type as keyof typeof colors] || '#666';
  }

  formatNotificationTime(timestamp: Date): string {
    const now = new Date();
    const diff = now.getTime() - timestamp.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (days > 0) return `Hace ${days} día${days > 1 ? 's' : ''}`;
    if (hours > 0) return `Hace ${hours} hora${hours > 1 ? 's' : ''}`;
    if (minutes > 0) return `Hace ${minutes} minuto${minutes > 1 ? 's' : ''}`;
    return 'Ahora mismo';
  }

  getRoleColor(role: string): string {
    const colors = {
      'admin': 'warn',
      'staff': 'accent',
      'client': 'primary'
    };
    return colors[role as keyof typeof colors] || 'primary';
  }

  getRoleLabel(role: string): string {
    const labels = {
      'admin': 'Administrador',
      'staff': 'Personal',
      'client': 'Cliente'
    };
    return labels[role as keyof typeof labels] || 'Usuario';
  }

  // Theme Methods
  toggleTheme() {
    this.isDarkTheme = !this.isDarkTheme;
    localStorage.setItem('darkTheme', this.isDarkTheme.toString());
    
    // Apply theme logic here
    document.body.classList.toggle('dark-theme', this.isDarkTheme);
    
    this.showSuccessMessage(
      `Cambiado a modo ${this.isDarkTheme ? 'oscuro' : 'claro'}`
    );
  }

  // Avatar Methods
  onAvatarError(event: any) {
    // Fallback to default avatar on error
    event.target.src = 'assets/images/default-avatar.png';
  }

  // Logout Methods
  confirmLogout() {
    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      width: '400px',
      data: {
        title: 'Cerrar Sesión',
        message: '¿Estás seguro de que deseas cerrar sesión?',
        confirmText: 'Cerrar Sesión',
        cancelText: 'Cancelar'
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.logout();
      }
    });
  }

  logout() {
    this.isLoading = true;
    
    setTimeout(() => {
      this.authService.logout();
      this.showSuccessMessage('Sesión cerrada correctamente');
      this.isLoading = false;
    }, 1000);
  }

  // Helper Methods
  private showSuccessMessage(message: string) {
    this.snackBar.open(message, 'Cerrar', {
      duration: 3000,
      horizontalPosition: 'end',
      verticalPosition: 'top'
    });
  }
}

//Simple Confirmation Dialog Component (you can create this separately)
//import { Component as DialogComponent, Inject } from '@angular/core';
//import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
//
//interface DialogData {
//  title: string;
//  message: string;
//  confirmText: string;
//  cancelText: string;
//}
//
//@DialogComponent({
//  selector: 'app-confirm-dialog',
//  template: `
//    <h1 mat-dialog-title>{{ data.title }}</h1>
//    <div mat-dialog-content>
//      <p>{{ data.message }}</p>
//    </div>
//    <div mat-dialog-actions align="end">
//      <button mat-button [mat-dialog-close]="false">{{ data.cancelText }}</button>
//      <button mat-raised-button color="warn" [mat-dialog-close]="true">{{ data.confirmText }}</button>
//    </div>
//  `
//})
//export class ConfirmDialogComponent {
//  constructor(
//    public dialogRef: MatDialogRef<ConfirmDialogComponent>,
//    @Inject(MAT_DIALOG_DATA) public data: DialogData
//  ) {}
//}
