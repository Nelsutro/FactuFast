import { Component, OnInit, Output, EventEmitter, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { MatDialog } from '@angular/material/dialog';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatMenuTrigger } from '@angular/material/menu';
import { formatDistance } from 'date-fns';
import { es } from 'date-fns/locale';
import { LogoutConfirmDialogComponent } from '../dialogs/logout-confirm-dialog/logout-confirm-dialog.component';
import { AuthService } from '../../services/auth.service';

interface Notification {
  id: number;
  type: string;
  icon: string;
  title: string;
  message: string;
  timestamp: Date;
  read?: boolean;
  actionRoute?: string;
}

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.css'],
  standalone: false
})
export class HeaderComponent implements OnInit {
  @Output() toggleSidenav = new EventEmitter<void>();
  @ViewChild('notificationMenu') notificationsTrigger!: MatMenuTrigger;
  @ViewChild('userMenuTrigger') userMenuTrigger!: MatMenuTrigger;
  
  notificationCount: number = 0;

  // User information
  userName: string = 'Usuario Demo';
  userEmail: string = 'usuario@demo.com';
  userAvatar: string = 'assets/images/avatar.png';
  userRole: string = 'client';

  // UI state
  isDarkTheme: boolean = false;
  isSearching: boolean = false;
  searchTerm: string = '';
  updateCount: number = 0;

  // Notifications
  notifications: Notification[] = [
    {
      id: 1,
      type: 'warning',
      icon: 'warning',
      title: 'Factura Pendiente',
      message: 'Tienes facturas pendientes por pagar',
      timestamp: new Date(),
      actionRoute: '/facturas'
    },
    {
      id: 2,
      type: 'success',
      icon: 'check_circle',
      title: 'Pago Recibido',
      message: 'Se ha registrado un nuevo pago',
      timestamp: new Date(),
      actionRoute: '/pagos'
    }
  ];

  constructor(
    private router: Router,
    private dialog: MatDialog,
    private snackBar: MatSnackBar,
    private authService: AuthService
  ) {
    this.loadThemePreference();
  }

  ngOnInit(): void {
    this.loadUserInfo();
    this.checkForUpdates();
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

  // User Management
  private loadUserInfo(): void {
    this.authService.currentUser.subscribe(user => {
      if (user) {
        this.userName = user.name;
        this.userEmail = user.email;
        this.userRole = user.role;
        this.userAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(this.userName)}&background=1976d2&color=fff`;
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
    this.router.navigate(['/configuracion']);
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

  dismissNotification(notification: Notification, event: Event): void {
    event.stopPropagation();
    this.notifications = this.notifications.filter(n => n.id !== notification.id);
    this.showMessage('Notificación eliminada');
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
    this.authService.logout();
    this.router.navigate(['/login']);
    this.showMessage('Sesión cerrada correctamente');
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
