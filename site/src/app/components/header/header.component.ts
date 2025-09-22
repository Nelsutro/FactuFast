import { Component, OnInit, Output, EventEmitter, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatMenuTrigger, MatMenuModule } from '@angular/material/menu';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatBadgeModule } from '@angular/material/badge';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatDividerModule } from '@angular/material/divider';
import { MatChipsModule } from '@angular/material/chips';
import { CommonModule } from '@angular/common';
import { formatDistance } from 'date-fns';
import { es } from 'date-fns/locale';
import { LogoutConfirmDialogComponent } from '../dialogs/logout-confirm-dialog/logout-confirm-dialog.component';
import { NotificationService, AppNotification } from '../../core/services/notification.service';
import { AuthService } from '../../core/services/auth.service';

type Notification = AppNotification;

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    MatToolbarModule,
    MatButtonModule,
    MatIconModule,
    MatMenuModule,
    MatBadgeModule,
    MatFormFieldModule,
    MatInputModule,
    MatDividerModule,
    MatChipsModule,
    MatDialogModule
  ]
})
export class HeaderComponent implements OnInit {
  @Output() toggleSidenav = new EventEmitter<void>();
  @ViewChild('notificationMenu') notificationsTrigger!: MatMenuTrigger;
  @ViewChild('userMenuTrigger') userMenuTrigger!: MatMenuTrigger;
  @ViewChild('quickCreateMenuTrigger') quickCreateMenuTrigger!: MatMenuTrigger;
  
  notificationCount: number = 0;

  // User information (solo se llena si está autenticado)
  userName: string = '';
  userEmail: string = '';
  userAvatar: string = 'assets/images/default-avatar.png';
  userRole: string = '';
  isAuthenticated: boolean = false;

  // UI state
  isDarkTheme: boolean = false;
  isSearching: boolean = false;
  searchTerm: string = '';
  updateCount: number = 0;
  showMobileSearch: boolean = false;

  // Quick actions
  isQuickCreating: boolean = false;

  // Notifications
  notifications: Notification[] = [];

  constructor(
    private router: Router,
    private dialog: MatDialog,
    private snackBar: MatSnackBar,
    private authService: AuthService,
    private notificationService: NotificationService
  ) {
    this.loadThemePreference();
  }

  ngOnInit(): void {
    this.loadUserInfo();
    this.checkForUpdates();
    this.observeNotifications();
  }

  private observeNotifications(): void {
    this.notificationService.notifications$.subscribe(list => {
      this.notifications = list;
      this.updateNotificationCount();
    });
    // Cargar al iniciar
    this.notificationService.load();
  }

  private updateNotificationCount(): void {
    this.notificationCount = this.notifications.filter(n => !n.read).length;
  }

  // Theme Management
  private loadThemePreference(): void {
    const savedTheme = localStorage.getItem('theme');
    this.isDarkTheme = savedTheme === 'dark';
    this.applyTheme();
  }

  private applyTheme(): void {
    document.body.classList.toggle('dark-theme', this.isDarkTheme);
  }

  toggleTheme(): void {
    this.isDarkTheme = !this.isDarkTheme;
    localStorage.setItem('theme', this.isDarkTheme ? 'dark' : 'light');
    this.applyTheme();
    this.showMessage(`Cambiado a modo ${this.isDarkTheme ? 'oscuro' : 'claro'}`);
  }

  // Search Management
  toggleMobileSearch(): void {
    this.showMobileSearch = !this.showMobileSearch;
    if (!this.showMobileSearch) {
      this.searchTerm = '';
    }
  }

  // User Management
  private loadUserInfo(): void {
    this.authService.currentUser$.subscribe((user: any) => {
      if (user) {
        this.isAuthenticated = true;
        this.userName = user.name;
        this.userEmail = user.email;
        this.userRole = user.role;
        this.userAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(this.userName)}&background=1976d2&color=fff`;
      } else {
        this.isAuthenticated = false;
        this.userName = '';
        this.userEmail = '';
        this.userRole = '';
        this.userAvatar = 'assets/images/default-avatar.png';
      }
    });
  }

  onAvatarError(event: Event): void {
    const imgElement = event.target as HTMLImageElement;
    if (imgElement) {
      imgElement.src = 'assets/images/default-avatar.png';
    }
  }

  // Navigation
  toggleSidebar(): void {
    this.toggleSidenav.emit();
  }

  goToProfile(): void {
    this.router.navigate(['/perfil']);
  }

  goToSettings(): void {
    this.router.navigate(['/settings']);
  }

  viewHelp(): void {
    this.router.navigate(['/ayuda']);
  }

  viewUpdates(): void {
    this.router.navigate(['/novedades']);
  }

  // Search functionality
  onSearch(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.searchTerm = input.value;
    // Implement search logic here
  }

  clearSearch(): void {
    this.searchTerm = '';
  }

  // Notification Management
  getNotificationColor(type: string): string {
    const colors: { [key: string]: string } = {
      'success': '#4caf50',
      'warning': '#ff9800',
      'error': '#f44336',
      'info': '#2196f3'
    };
    return colors[type] || colors['info'];
  }

  getNotificationIcon(type: string): string {
    const icons: { [key: string]: string } = {
      'success': 'check_circle',
      'warning': 'warning',
      'error': 'error',
      'info': 'info'
    };
    return icons[type] || 'notifications';
  }

  formatNotificationTime(timestamp: Date): string {
    return formatDistance(timestamp, new Date(), { 
      addSuffix: true,
      locale: es 
    });
  }

  handleNotificationClick(notification: Notification): void {
    notification.read = true;
    if (notification.actionRoute) {
      this.router.navigate([notification.actionRoute]);
    }
    this.notificationsTrigger.closeMenu();
  }

  markAllNotificationsAsRead(): void {
    this.notificationService.markAllAsRead();
  }

  clearAllNotifications(): void {
    this.notificationService.clear();
    this.showMessage('Notificaciones borradas');
  }

  dismissNotification(notification: Notification, event: Event): void {
    event.stopPropagation();
    this.notificationService.markAsRead(notification.id);
    this.showMessage('Notificación descartada');
  }

  viewAllNotifications(): void {
    this.router.navigate(['/notificaciones']);
  }

  // Role Management
  getRoleColor(role: string): string {
    const colors: { [key: string]: string } = {
      'admin': 'warn',
      'staff': 'accent',
      'client': 'primary'
    };
    return colors[role] || 'primary';
  }

  getRoleLabel(role: string): string {
    const labels: { [key: string]: string } = {
      'admin': 'Administrador',
      'staff': 'Personal',
      'client': 'Cliente'
    };
    return labels[role] || 'Usuario';
  }

  // Session Management
  confirmLogout(): void {
    const dialogRef = this.dialog.open(LogoutConfirmDialogComponent, {
      width: '350px'
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.logout();
      }
    });
  }

  logout(): void {
    this.authService.logout().subscribe({
      next: () => {
        this.router.navigate(['/login']);
        this.showMessage('Sesión cerrada correctamente');
      },
      error: (error) => {
        console.error('Error al cerrar sesión:', error);
        // Aún así redirigir al login
        this.router.navigate(['/login']);
        this.showMessage('Sesión cerrada');
      }
    });
  }

  goToLogin(): void {
    this.router.navigate(['/login']);
  }

  // Quick create actions (placeholder - navegación simple)
  quickCreate(type: 'invoice' | 'client' | 'quote'): void {
    this.isQuickCreating = true;
    // Cargar de forma perezosa los componentes standalone
    const dialogCfg = {
      width: type === 'client' ? '520px' : '920px',
      maxWidth: '95vw',
      maxHeight: '90vh',
      backdropClass: 'ff-dialog-backdrop',
      panelClass: 'ff-dialog-panel',
      autoFocus: true,
      restoreFocus: true,
      disableClose: false
    } as const;

    const openDialog = async () => {
      if (type === 'invoice') {
        const { InvoiceCreateComponent } = await import('../invoices/invoice-create.component');
        return this.dialog.open(InvoiceCreateComponent, dialogCfg);
      }
      if (type === 'client') {
        const { ClientCreateComponent } = await import('../clients/client-create.component');
        return this.dialog.open(ClientCreateComponent, dialogCfg);
      }
      // quote
      const { QuoteCreateComponent } = await import('../quotes/quote-create.component');
      return this.dialog.open(QuoteCreateComponent, dialogCfg);
    };

    openDialog().then(ref => {
      ref.afterClosed().subscribe(() => {
        this.isQuickCreating = false;
        this.quickCreateMenuTrigger?.closeMenu();
      });
    }).catch(err => {
      console.error('Error abriendo diálogo:', err);
      this.isQuickCreating = false;
      this.quickCreateMenuTrigger?.closeMenu();
    });
  }

  // Updates
  private checkForUpdates(): void {
    // Implement update checking logic here
    this.updateCount = 0; // Set this based on actual updates
  }

  // Utility
  private showMessage(message: string): void {
    this.snackBar.open(message, 'Cerrar', {
      duration: 3000,
      horizontalPosition: 'end',
      verticalPosition: 'top'
    });
  }
}
