import { Component, OnInit } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { MatSidenavModule } from '@angular/material/sidenav';
import { HeaderComponent } from './components/header/header.component';
import { SidebarComponent } from './components/sidebar/sidebar.component';
import { AuthService } from './core/services/auth.service';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, MatSidenavModule, HeaderComponent, SidebarComponent],
  templateUrl: './app.component.html',
  styleUrl: './app.component.css'
})
export class AppComponent implements OnInit {
  title = 'FactuFast';
  sidebarOpen = false;

  constructor(private authService: AuthService) {}

  ngOnInit() {
    // Inicializar autenticación al cargar la aplicación
    console.log('Inicializando aplicación...');
    this.authService.loadUserFromStorage();
  }

  toggleSidebar() {
    this.sidebarOpen = !this.sidebarOpen;
  }
}
