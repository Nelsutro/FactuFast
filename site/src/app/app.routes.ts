import { Routes } from '@angular/router';
import { AuthGuard } from './guards/auth.guard';

export const routes: Routes = [
  {
    path: '',
    redirectTo: '/dashboard',
    pathMatch: 'full'
  },
  {
    path: 'login',
    loadComponent: () => import('./components/auth/login/login.component').then(m => m.LoginComponent)
  },
  {
    path: 'register',
    loadComponent: () => import('./components/auth/register/register.component').then(m => m.RegisterComponent)
  },
  {
    path: 'dashboard',
    loadComponent: () => import('./components/dashboard/dashboard.component').then(m => m.DashboardComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'invoices',
    loadComponent: () => import('./components/invoices/invoices.component').then(m => m.InvoicesComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'quotes',
    loadComponent: () => import('./components/quotes/quotes.component').then(m => m.QuotesComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'clients',
    loadComponent: () => import('./components/clients/clients.component').then(m => m.ClientsComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'payments',
    loadComponent: () => import('./components/payments/payments.component').then(m => m.PaymentsComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'companies',
    loadComponent: () => import('./components/companies/companies.component').then(m => m.CompaniesComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'automation',
    loadComponent: () => import('./components/dashboard/dashboard.component').then(m => m.DashboardComponent), // Temporalmente
    canActivate: [AuthGuard]
  },
  {
    path: 'settings',
    loadComponent: () => import('./components/dashboard/dashboard.component').then(m => m.DashboardComponent), // Temporalmente
    canActivate: [AuthGuard]
  },
  {
    path: 'test-connection',
    loadComponent: () => import('./features/connection-test/connection-test.component').then(m => m.ConnectionTestComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'unauthorized',
    loadComponent: () => import('./components/shared/unauthorized/unauthorized.component').then(m => m.UnauthorizedComponent)
  },
  {
    path: '**',
    redirectTo: '/dashboard'
  }
];
