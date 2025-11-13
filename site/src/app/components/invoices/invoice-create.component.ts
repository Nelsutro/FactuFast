import { Component, Optional, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormArray, FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatOptionModule } from '@angular/material/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { ApiService } from '../../services/api.service';
import { MatDialogRef } from '@angular/material/dialog';
import { AuthService } from '../../core/services/auth.service';

interface ClientOption { id: number; name: string; email?: string; }

@Component({
  selector: 'app-invoice-create',
  standalone: true,
  templateUrl: './invoice-create.component.html',
  styleUrls: ['./invoice-create.component.css'],
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatCardModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatOptionModule,
    MatButtonModule,
    MatIconModule,
    MatSnackBarModule
  ]
})

export class InvoiceCreateComponent implements OnInit {
  form: FormGroup;
  loading = false;
  serverErrors: string[] = []; // nuevos errores backend
  clients: ClientOption[] = [];
  filtering = false;

  constructor(
    private fb: FormBuilder,
    private api: ApiService,
    private router: Router,
    private snack: MatSnackBar,
    private auth: AuthService,
    @Optional() private dialogRef?: MatDialogRef<InvoiceCreateComponent>
  ) {
    this.form = this.fb.group({
      client_id: ['', [Validators.required]],
      issue_date: [new Date().toISOString().substring(0,10), Validators.required],
      due_date: [new Date().toISOString().substring(0,10), Validators.required],
      items: this.fb.array([
        this.createItem()
      ])
    });
  }

  ngOnInit(): void {
    this.loadClients();
  }

  loadClients() {
    // Reutilizamos getClients (devuelve data directa según ApiService actual)
    this.api.getClients().subscribe({
      next: (data) => {
        if (Array.isArray(data)) {
          this.clients = data.map((c: any) => ({ id: c.id, name: c.name, email: c.email }));
        }
      },
      error: () => {}
    });
  }

  get items(): FormArray { return this.form.get('items') as FormArray; }

  createItem(): FormGroup {
    return this.fb.group({
      description: ['', Validators.required],
      quantity: [1, [Validators.required, Validators.min(1)]],
      price: [0, [Validators.required, Validators.min(0)]]
    });
  }

  addItem() { this.items.push(this.createItem()); }
  removeItem(i: number) { if (this.items.length>1) this.items.removeAt(i); }

  getItemSubtotal(index: number): number {
    const item = this.items.at(index);
    if (!item) return 0;
    const quantity = parseFloat(item.get('quantity')?.value) || 0;
    const price = parseFloat(item.get('price')?.value) || 0;
    return quantity * price;
  }

  getTotalAmount(): number {
    let total = 0;
    for (let i = 0; i < this.items.length; i++) {
      total += this.getItemSubtotal(i);
    }
    return total;
  }

  submit() {
    if (this.loading || this.form.invalid) return;
    this.loading = true;
    this.serverErrors = [];
    const company = this.auth.getUserCompany();
    // Enviar client_id; backend validará que existe y pertenece a la empresa
    const { client_id, issue_date, due_date, items } = this.form.value;
    const payload = { client_id, issue_date, due_date, items };
    this.api.createInvoice(payload).subscribe({
      next: () => {
        this.snack.open('Factura creada', 'Cerrar', { duration: 2500 });
        if (this.dialogRef) {
          this.dialogRef.close(true);
        } else {
          this.router.navigate(['/invoices']);
        }
      },
      error: (e) => {
        if (e?.status === 422 && e?.error?.errors) {
          const errs = e.error.errors;
          this.serverErrors = Object.keys(errs).flatMap(k => Array.isArray(errs[k]) ? errs[k] : [errs[k]]);
          this.snack.open('Hay errores de validación', 'Cerrar', { duration: 3000 });
        } else {
          this.snack.open(e?.message || 'Error al crear factura', 'Cerrar', { duration: 3000 });
        }
        this.loading = false;
      },
      complete: () => this.loading = false
    });
  }

  onCancel() {
    if (this.loading) return;
    if (this.dialogRef) {
      this.dialogRef.close(false);
    } else {
      this.router.navigate(['/invoices']);
    }
  }
}
