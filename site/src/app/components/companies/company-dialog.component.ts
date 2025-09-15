import { Component, Inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { Company, CompanyService, CreateCompanyRequest } from '../../core/services/company.service';

export interface CompanyDialogData {
  company: Company | null;
}

@Component({
  selector: 'app-company-dialog',
  templateUrl: './company-dialog.component.html',
  styleUrls: ['./company-dialog.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule
  ]
})
export class CompanyDialogComponent {
  form: FormGroup;
  loading = false;
  isEdit = false;

  constructor(
    private fb: FormBuilder,
    private dialogRef: MatDialogRef<CompanyDialogComponent>,
    @Inject(MAT_DIALOG_DATA) public data: CompanyDialogData,
    private companyService: CompanyService
  ) {
    this.isEdit = !!data.company;
    this.form = this.fb.group({
      name: [data.company?.name || '', [Validators.required, Validators.minLength(2)]],
      tax_id: [data.company?.tax_id || '', [Validators.required, Validators.minLength(5)]],
      email: [data.company?.email || '', [Validators.email]],
      phone: [data.company?.phone || ''],
      address: [data.company?.address || '']
    });
  }

  save() {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.loading = true;
    const payload: CreateCompanyRequest = this.form.value;

    if (this.isEdit && this.data.company) {
      this.companyService.updateCompany(this.data.company.id, payload).subscribe({
        next: () => {
          this.loading = false;
          this.dialogRef.close(true);
        },
        error: () => {
          this.loading = false;
        }
      });
    } else {
      this.companyService.createCompany(payload).subscribe({
        next: () => {
          this.loading = false;
          this.dialogRef.close(true);
        },
        error: () => {
          this.loading = false;
        }
      });
    }
  }

  cancel() {
    this.dialogRef.close(false);
  }
}
