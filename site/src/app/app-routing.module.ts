import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './components/dashboard/dashboard.component';
import { InvoicesComponent } from './components/invoices/invoices.component';
import { ClientsComponent } from './components/clients/clients.component';
import { QuotesComponent } from './components/quotes/quotes.component';
import { PaymentsComponent } from './components/payments/payments.component';
import { LoginComponent } from './components/auth/login/login.component';
import { AuthGuard } from './guards/auth.guard';

const routes: Routes = [
  { path: '', redirectTo: '/dashboard', pathMatch: 'full' },
  { path: 'login', component: LoginComponent },
  { 
    path: 'dashboard', 
    component: DashboardComponent,
    canActivate: [AuthGuard]
  },
  { 
    path: 'invoices', 
    component: InvoicesComponent,
    canActivate: [AuthGuard]
  },
  { 
    path: 'quotes', 
    component: QuotesComponent,
    canActivate: [AuthGuard]
  },
  { 
    path: 'clients', 
    component: ClientsComponent,
    canActivate: [AuthGuard]
  },
  { 
    path: 'payments', 
    component: PaymentsComponent,
    canActivate: [AuthGuard]
  },
  { 
    path: 'automation', 
    component: DashboardComponent, // Temporalmente
    canActivate: [AuthGuard]
  },
  { 
    path: 'settings', 
    component: DashboardComponent, // Temporalmente
    canActivate: [AuthGuard]
  },
  // Redirect any unknown routes to login
  { path: '**', redirectTo: '/login' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }