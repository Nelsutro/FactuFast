import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { HttpClientModule } from '@angular/common/http';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';

// Main Layout Components
import { SidebarComponent } from './components/sidebar/sidebar.component';
import { HeaderComponent } from './components/header/header.component';

// Page Components
import { DashboardComponent } from './components/dashboard/dashboard.component';
import { InvoicesComponent } from './components/invoices/invoices.component';
import { ClientsComponent } from './components/clients/clients.component';
import { QuotesComponent } from './components/quotes/quotes.component';
import { PaymentsComponent } from './components/payments/payments.component';
import { LoginComponent } from './components/auth/login/login.component';

// Services
import { ApiService } from './services/api.service';
import { AuthService } from './services/auth.service';

@NgModule({
  declarations: [
    AppComponent,
    SidebarComponent,
    HeaderComponent,
    DashboardComponent,
    InvoicesComponent,
    ClientsComponent,
    QuotesComponent,
    PaymentsComponent,
    LoginComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    FormsModule,
    ReactiveFormsModule,
    HttpClientModule
  ],
  providers: [
    ApiService,
    AuthService
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }