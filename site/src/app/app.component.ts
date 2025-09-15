import { Component, OnInit } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { HeaderComponent } from './components/header/header.component';
import { SidebarComponent } from './components/sidebar/sidebar.component';
import { AuthService } from './core/services/auth.service';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [CommonModule, RouterOutlet, HeaderComponent, SidebarComponent],
  templateUrl: './app.component.html',
  styleUrl: './app.component.css'
})
export class AppComponent implements OnInit {
  title = 'FactuFast';
  sidebarOpen = false; // Para mobile
  sidebarCollapsed = false; // Estado colapsado desktop

  // Breakpoint simple (podríamos mejorar con ResizeObserver)
  isMobile = false;

  constructor(private authService: AuthService) {}

  ngOnInit() {
    // Inicializar autenticación al cargar la aplicación
    console.log('Inicializando aplicación...');
    this.authService.loadUserFromStorage();
    this.onResize();
    window.addEventListener('resize', this.onResize.bind(this));

    // Cargar estado de colapso
    this.sidebarCollapsed = localStorage.getItem('sidebar_collapsed') === '1';
  }

  toggleSidebar() {
    this.sidebarOpen = !this.sidebarOpen;
  }

  onSidebarCollapsedChange(collapsed: boolean) {
    this.sidebarCollapsed = collapsed;
  }

  onResize() {
    this.isMobile = window.innerWidth < 768;
  }
}
