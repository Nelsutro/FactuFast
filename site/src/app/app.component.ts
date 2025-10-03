import { Component, OnInit } from '@angular/core';
import { NavigationEnd, Router, RouterOutlet } from '@angular/router';
import { HeaderComponent } from './components/header/header.component';
import { SidebarComponent } from './components/sidebar/sidebar.component';
import { AuthService } from './core/services/auth.service';
import { CommonModule } from '@angular/common';
import { filter } from 'rxjs/operators';

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
  showCompanyLayout = true;

  constructor(private authService: AuthService, private router: Router) {}

  ngOnInit() {
    // Inicializar autenticación al cargar la aplicación
    console.log('Inicializando aplicación...');
    this.authService.loadUserFromStorage();
    this.onResize();
    window.addEventListener('resize', this.onResize.bind(this));

    // Cargar estado de colapso
    this.sidebarCollapsed = localStorage.getItem('sidebar_collapsed') === '1';

    // Controlar layout según la ruta
    this.evaluateLayout(this.router.url);
    this.router.events
      .pipe(filter((event): event is NavigationEnd => event instanceof NavigationEnd))
      .subscribe(() => {
        this.evaluateLayout(this.router.url);
      });
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

  private evaluateLayout(url: string) {
    const normalized = url.split('?')[0] ?? '/';
    const isStandaloneContext = /^\/?(client-portal|public-pay|oauth|about)/.test(normalized);
    this.showCompanyLayout = !isStandaloneContext;

    if (!this.showCompanyLayout) {
      this.sidebarOpen = false;
    }
  }
}
