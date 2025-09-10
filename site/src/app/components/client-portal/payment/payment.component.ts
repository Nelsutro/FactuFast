import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatStepperModule } from '@angular/material/stepper';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ClientPortalService, PaymentRequest } from '../../../core/services/client-portal.service';

@Component({
  selector: 'app-payment',
  templateUrl: './payment.component.html',
  styleUrls: ['./payment.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatProgressSpinnerModule,
    MatStepperModule
  ]
})
export class PaymentComponent implements OnInit {
  invoice: any = null;
  paymentForm: FormGroup;
  loading = true;
  processing = false;
  invoiceId!: number;
  
  paymentMethods = [
    { value: 'credit_card', label: 'Tarjeta de Crédito', icon: 'credit_card' },
    { value: 'debit_card', label: 'Tarjeta de Débito', icon: 'payment' },
    { value: 'bank_transfer', label: 'Transferencia Bancaria', icon: 'account_balance' },
    { value: 'other', label: 'Otro', icon: 'more_horiz' }
  ];

  constructor(
    private fb: FormBuilder,
    private clientPortalService: ClientPortalService,
    private router: Router,
    private route: ActivatedRoute,
    private snackBar: MatSnackBar
  ) {
    this.paymentForm = this.fb.group({
      amount: ['', [Validators.required, Validators.min(0.01)]],
      payment_method: ['', Validators.required],
      transaction_id: ['']
    });
  }

  ngOnInit() {
    this.route.params.subscribe(params => {
      this.invoiceId = +params['id'];
      this.loadInvoice();
    });
  }

  loadInvoice() {
    const email = localStorage.getItem('client_portal_email');
    const token = localStorage.getItem('client_portal_token');

    if (!email || !token) {
      this.router.navigate(['/client-portal/access']);
      return;
    }

    this.clientPortalService.getInvoice(this.invoiceId, email, token).subscribe({
      next: (response) => {
        if (response.success && response.data) {
          this.invoice = response.data;
          // Establecer el monto pendiente como valor por defecto
          this.paymentForm.patchValue({
            amount: this.invoice.remaining_amount
          });
        }
        this.loading = false;
      },
      error: (error) => {
        console.error('Error cargando factura:', error);
        this.snackBar.open('Error al cargar la factura', 'Cerrar', { duration: 3000 });
        this.loading = false;
      }
    });
  }

  setFullAmount() {
    this.paymentForm.patchValue({
      amount: this.invoice.remaining_amount
    });
  }

  processPayment() {
    if (this.paymentForm.invalid) return;

    this.processing = true;
    const email = localStorage.getItem('client_portal_email');
    const token = localStorage.getItem('client_portal_token');

    if (!email || !token) {
      this.router.navigate(['/client-portal/access']);
      return;
    }

    const paymentData: PaymentRequest = this.paymentForm.value;

    this.clientPortalService.payInvoice(this.invoiceId, paymentData, email, token).subscribe({
      next: (response) => {
        if (response.success) {
          this.snackBar.open('¡Pago procesado exitosamente!', 'Cerrar', { duration: 5000 });
          this.router.navigate(['/client-portal/dashboard']);
        }
        this.processing = false;
      },
      error: (error) => {
        console.error('Error procesando pago:', error);
        this.snackBar.open(error.error?.message || 'Error al procesar el pago', 'Cerrar', { duration: 3000 });
        this.processing = false;
      }
    });
  }

  goBack() {
    this.router.navigate(['/client-portal/invoice', this.invoiceId]);
  }

  getPaymentMethodIcon(method: string): string {
    const methodObj = this.paymentMethods.find(m => m.value === method);
    return methodObj ? methodObj.icon : 'payment';
  }

  getSelectedPaymentMethodLabel(): string {
    const selectedValue = this.paymentForm.get('payment_method')?.value;
    const methodObj = this.paymentMethods.find(m => m.value === selectedValue);
    return methodObj ? methodObj.label : 'No seleccionado';
  }

  getSelectedPaymentMethodIcon(): string {
    const selectedValue = this.paymentForm.get('payment_method')?.value;
    return this.getPaymentMethodIcon(selectedValue);
  }

  getRemainingAmount(): number {
    const paymentAmount = this.paymentForm.get('amount')?.value || 0;
    const remainingAmount = this.invoice?.remaining_amount || 0;
    return remainingAmount - paymentAmount;
  }
}
