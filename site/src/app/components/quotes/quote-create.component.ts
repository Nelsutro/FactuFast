import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormArray, FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { ApiService } from '../../services/api.service';
import { MatDialogRef } from '@angular/material/dialog';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-quote-create',
  standalone: true,
  templateUrl: './quote-create.component.html',
  styleUrls: ['./quote-create.component.css'],
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatCardModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    MatSnackBarModule
  ]
})
export class QuoteCreateComponent {
  form: FormGroup;
  loading = false;

  constructor(
    private fb: FormBuilder,
    private api: ApiService,
    private router: Router,
    private snack: MatSnackBar,
    private dialogRef: MatDialogRef<QuoteCreateComponent>,
    private auth: AuthService
  ) {
    this.form = this.fb.group({
  client_id: [null, [Validators.required, Validators.min(1)]],
      valid_until: [new Date().toISOString().substring(0,10), Validators.required],
      items: this.fb.array([
        this.createItem()
      ])
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

  submit() {
    if (this.loading || this.form.invalid) return;
    this.loading = true;
    const company = this.auth.getUserCompany();
    const payload = { ...this.form.value, company_id: company?.id ?? company ?? null };
    this.api.createQuote(payload).subscribe({
      next: () => {
        this.snack.open('Cotización creada', 'Cerrar', { duration: 2500 });
        this.dialogRef.close(true);
      },
      error: (e) => {
        this.snack.open(e?.message || 'Error al crear cotización', 'Cerrar', { duration: 3000 });
        this.loading = false;
      },
      complete: () => this.loading = false
    });
  }
}
