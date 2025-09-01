import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './components/dashboard/dashboard.component';
import { InvoicesComponent } from './components/invoices/invoices.component';
import { ClientsComponent } from './components/clients/clients.component';

const routes: Routes = [
  { path: '', redirectTo: '/dashboard', pathMatch: 'full' },
  { path: 'dashboard', component: DashboardComponent },
  { path: 'invoices', component: InvoicesComponent },
  { path: 'quotes', component: DashboardComponent }, // Temporalmente
  { path: 'clients', component: ClientsComponent },
  { path: 'payments', component: DashboardComponent }, // Temporalmente
  { path: 'automation', component: DashboardComponent }, // Temporalmente
  { path: 'settings', component: DashboardComponent }, // Temporalmente
  { path: '**', redirectTo: '/dashboard' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }