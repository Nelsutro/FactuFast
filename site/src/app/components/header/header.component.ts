import { Component, Output, EventEmitter, OnInit } from '@angular/core';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.css'],
  standalone: false
})
export class HeaderComponent implements OnInit {
  @Output() toggleSidebar = new EventEmitter<void>();

  searchTerm: string = '';
  notificationCount: number = 3;
  showUserMenu: boolean = false;
  userName: string = 'Usuario Admin';
  userAvatar: string = '';

  constructor(private authService: AuthService) {}

  ngOnInit() {
    this.userAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(this.userName)}&background=3b82f6&color=fff`;
  }

  onToggleSidebar() {
    this.toggleSidebar.emit();
  }

  onSearch() {
    console.log('Buscando:', this.searchTerm);
    // Aquí implementarás la lógica de búsqueda
  }

  toggleNotifications() {
    console.log('Toggle notifications');
    // Aquí implementarás el panel de notificaciones
  }

  toggleUserMenu() {
    this.showUserMenu = !this.showUserMenu;
  }

  logout() {
    this.authService.logout();
    this.showUserMenu = false;
  }
}