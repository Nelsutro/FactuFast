import { Component, OnInit, Optional } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormArray, FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { ApiService } from '../../services/api.service';
import { MatDialogRef } from '@angular/material/dialog';
import { AuthService } from '../../core/services/auth.service';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';

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
    MatSnackBarModule,
    MatProgressSpinnerModule
  ]
})
export class QuoteCreateComponent implements OnInit {
  form: FormGroup;
  loading = false;
  initializing = false;
  editing = false;
  title = 'Nueva Cotización';
  submitLabel = 'Crear';
  private quoteId: number | null = null;

  constructor(
    private fb: FormBuilder,
    private api: ApiService,
    private router: Router,
    private snack: MatSnackBar,
    private auth: AuthService,
    private route: ActivatedRoute,
    @Optional() private dialogRef?: MatDialogRef<QuoteCreateComponent>
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

  ngOnInit(): void {
    const params = this.route.snapshot.queryParamMap;
    const duplicateIdParam = params.get('duplicate');
    const mode = params.get('mode');

    if (duplicateIdParam) {
      const duplicateId = Number(duplicateIdParam);
      if (!Number.isNaN(duplicateId)) {
        this.initializing = true;
        this.editing = mode === 'edit';
        if (this.editing) {
          this.quoteId = duplicateId;
          this.title = 'Editar Cotización';
          this.submitLabel = 'Guardar cambios';
        } else {
          this.title = 'Duplicar Cotización';
          this.submitLabel = 'Guardar copia';
        }
        this.loadQuote(duplicateId);
        return;
      }
    }

    this.initializing = false;
  }

  createItem(): FormGroup {
    return this.fb.group({
      description: ['', Validators.required],
      quantity: [1, [Validators.required, Validators.min(1)]],
      price: [0, [Validators.required, Validators.min(0)]]
    });
  }

  addItem() { this.items.push(this.createItem()); }
  removeItem(i: number) { if (this.items.length>1) this.items.removeAt(i); }

  private loadQuote(id: number): void {
    this.api.getQuote(id).subscribe({
      next: (quote) => {
        this.populateFormFromQuote(quote);
        if (!this.editing) {
          // Para duplicados, aseguramos que se cree como nueva cotización
          this.quoteId = null;
        }
        this.initializing = false;
      },
      error: (err) => {
        this.snack.open(err?.message || 'No se pudo cargar la cotización seleccionada', 'Cerrar', { duration: 3500 });
        this.initializing = false;
        this.router.navigate(['/quotes']);
      }
    });
  }

  private populateFormFromQuote(quote: any): void {
    if (!quote) {
      return;
    }

    const itemsArray = this.items;
    while (itemsArray.length) {
      itemsArray.removeAt(0);
    }

    const items = Array.isArray(quote.items) ? quote.items : [];
    if (items.length === 0) {
      itemsArray.push(this.createItem());
    } else {
      items.forEach((item: any) => {
        itemsArray.push(this.fb.group({
          description: [item.description || '', Validators.required],
          quantity: [item.quantity ?? 1, [Validators.required, Validators.min(1)]],
          price: [item.price ?? item.unit_price ?? 0, [Validators.required, Validators.min(0)]]
        }));
      });
    }

    const validUntil = quote.valid_until ? this.toDateInput(quote.valid_until) : new Date().toISOString().substring(0,10);

    this.form.patchValue({
      client_id: quote.client_id ?? quote.client?.id ?? null,
      valid_until: validUntil
    });
  }

  private toDateInput(value: string | Date): string {
    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) {
      return new Date().toISOString().substring(0,10);
    }
    const timezoneOffset = date.getTimezoneOffset() * 60000;
    const localISO = new Date(date.getTime() - timezoneOffset).toISOString();
    return localISO.substring(0, 10);
  }

  submit() {
    if (this.loading || this.form.invalid) return;
    this.loading = true;
    const company = this.auth.getUserCompany();
    const payload = { ...this.form.value, company_id: company?.id ?? company ?? null };
    const request$ = this.editing && this.quoteId
      ? this.api.updateQuote(this.quoteId, payload)
      : this.api.createQuote(payload);

    request$.subscribe({
      next: (response) => {
        const isUpdating = this.editing && this.quoteId;
        this.snack.open(isUpdating ? 'Cotización actualizada' : 'Cotización creada', 'Cerrar', { duration: 2500 });
        if (this.dialogRef) {
          this.dialogRef.close(true);
        } else if (isUpdating) {
          this.router.navigate(['/quotes', this.quoteId!]);
        } else {
          const newQuoteId = response?.id ?? response?.data?.id;
          if (newQuoteId) {
            this.router.navigate(['/quotes', newQuoteId]);
          } else {
            this.router.navigate(['/quotes']);
          }
        }
      },
      error: (e) => {
        this.snack.open(e?.message || 'Error al guardar la cotización', 'Cerrar', { duration: 3000 });
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
      this.router.navigate(['/quotes']);
    }
  }
}
