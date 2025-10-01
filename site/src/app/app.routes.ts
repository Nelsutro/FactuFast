import { Routes } from '@angular/router';
import { AuthGuard } from './guards/auth.guard';
import { RoleGuard } from './core/guards/role.guard';

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
    path: 'forgot-password',
    loadComponent: () => import('./components/auth/forgot-password/forgot-password.component').then(m => m.ForgotPasswordComponent)
  },
  {
    path: 'oauth/callback',
    loadComponent: () => import('./components/auth/oauth-callback/oauth-callback.component').then(m => m.OauthCallbackComponent)
  },
  // Rutas del Portal de Clientes (sin autenticación normal)
  {
    path: 'client-portal',
    children: [
      {
        path: '',
        redirectTo: 'access',
        pathMatch: 'full'
      },
      {
        path: 'access',
        loadComponent: () => import('./components/client-portal/access/access.component').then(m => m.AccessComponent)
      },
      {
        path: 'dashboard',
        loadComponent: () => import('./components/client-portal/dashboard/dashboard.component').then(m => m.ClientPortalDashboardComponent)
      },
      {
        path: 'invoice/:id',
        loadComponent: () => import('./components/client-portal/invoice-detail/invoice-detail.component').then(m => m.InvoiceDetailComponent)
      },
      {
        path: 'pay/:id',
        loadComponent: () => import('./components/client-portal/payment/payment.component').then(m => m.PaymentComponent)
      }
    ]
  },
  {
    path: 'public-pay/:hash',
    loadComponent: () => import('./components/public-pay/public-pay.component').then(m => m.PublicPayComponent)
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
    path: 'invoices/create',
    loadComponent: () => import('./components/invoices/invoice-create.component').then(m => m.InvoiceCreateComponent),
    canActivate: [AuthGuard]
  },
  // Placeholder para detalle de factura (carga el listado por ahora)
  {
    path: 'invoices/:id',
    loadComponent: () => import('./components/invoices/invoice-detail.component').then(m => m.InvoiceDetailComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'quotes',
    loadComponent: () => import('./components/quotes/quotes.component').then(m => m.QuotesComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'quotes/create',
    loadComponent: () => import('./components/quotes/quote-create.component').then(m => m.QuoteCreateComponent),
    canActivate: [AuthGuard]
  },
  // Placeholders para detalle/edición de cotización (cargan el listado por ahora)
  {
    path: 'quotes/:id',
    loadComponent: () => import('./components/quotes/quote-detail.component').then(m => m.QuoteDetailComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'quotes/:id/edit',
    loadComponent: () => import('./components/quotes/quote-detail.component').then(m => m.QuoteDetailComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'clients',
    loadComponent: () => import('./components/clients/clients.component').then(m => m.ClientsComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'clients/create',
    loadComponent: () => import('./components/clients/client-create.component').then(m => m.ClientCreateComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'payments',
    loadComponent: () => import('./components/payments/payments.component').then(m => m.PaymentsComponent),
    canActivate: [AuthGuard]
  },
  // Placeholders de pagos (cargan el listado por ahora)
  {
    path: 'payments/new',
    loadComponent: () => import('./components/payments/payments.component').then(m => m.PaymentsComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'payments/:id',
    loadComponent: () => import('./components/payments/payments.component').then(m => m.PaymentsComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'companies',
    loadComponent: () => import('./components/companies/companies.component').then(m => m.CompaniesComponent),
    canActivate: [AuthGuard, RoleGuard],
    data: { roles: ['admin'] }
  },
  {
    path: 'automation',
    loadComponent: () => import('./components/dashboard/dashboard.component').then(m => m.DashboardComponent), // Temporalmente
    canActivate: [AuthGuard]
  },
  {
    path: 'settings',
    loadComponent: () => import('./components/settings/settings.component').then(m => m.SettingsComponent),
    canActivate: [AuthGuard]
  },
  {
    path: 'perfil',
    loadComponent: () => import('./components/profile/profile.component').then(m => m.ProfileComponent),
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
