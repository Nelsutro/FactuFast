import { Component, Inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatSelectModule } from '@angular/material/select';
import { ApiService } from '../../services/api.service';

export interface InvoiceEditDialogData {
  invoice: any;
}

@Component({
  selector: 'app-invoice-edit-dialog',
  templateUrl: './invoice-edit-dialog.component.html',
  styleUrls: ['./invoice-edit-dialog.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    MatSelectModule
  ]
})
export class InvoiceEditDialogComponent {
  form: FormGroup;
  loading = false;

  statuses = [
    { value: 'pending', label: 'Pendiente' },
    { value: 'paid', label: 'Pagada' },
    { value: 'cancelled', label: 'Cancelada' }
  ];

  constructor(
    private fb: FormBuilder,
    private dialogRef: MatDialogRef<InvoiceEditDialogComponent>,
    @Inject(MAT_DIALOG_DATA) public data: InvoiceEditDialogData,
    private api: ApiService
  ) {
    const inv = data.invoice;
    this.form = this.fb.group({
      status: [inv.status || 'pending', [Validators.required]],
      issue_date: [inv.issue_date || '', []],
      due_date: [inv.due_date || '', []],
      notes: [inv.notes || '']
    });
  }

  save() {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.loading = true;
    this.api.updateInvoice(this.data.invoice.id, this.form.value).subscribe({
      next: () => {
        this.loading = false;
        this.dialogRef.close(true);
      },
      error: () => {
        this.loading = false;
      }
    });
  }

  cancel() {
    this.dialogRef.close(false);
  }
}
