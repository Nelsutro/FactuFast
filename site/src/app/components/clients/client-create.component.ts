import { Component, Optional } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatIconModule } from '@angular/material/icon';
import { ApiService } from '../../services/api.service';
import { MatDialogRef } from '@angular/material/dialog';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-client-create',
  standalone: true,
  templateUrl: './client-create.component.html',
  styleUrls: ['./client-create.component.css'],
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatCardModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatSnackBarModule,
    MatIconModule
  ]
})
export class ClientCreateComponent {
  form: FormGroup;
  loading = false;

  constructor(
    private fb: FormBuilder,
    private api: ApiService,
    private router: Router,
    private snack: MatSnackBar,
    private auth: AuthService,
    @Optional() private dialogRef?: MatDialogRef<ClientCreateComponent>
  ) {
    this.form = this.fb.group({
      name: ['', [Validators.required, Validators.minLength(2)]],
      email: ['', [Validators.email]],
      phone: ['']
    });
  }

  submit() {
    if (this.loading || this.form.invalid) return;
    this.loading = true;
  const company = this.auth.getUserCompany();
  const payload = { ...this.form.value, company_id: company?.id ?? company ?? null };
  this.api.createClient(payload).subscribe({
      next: () => {
        this.snack.open('Cliente creado', 'Cerrar', { duration: 2500 });
        if (this.dialogRef) {
          this.dialogRef.close(true);
        } else {
          this.router.navigate(['/clients']);
        }
      },
      error: (e) => {
        this.snack.open(e?.message || 'Error al crear cliente', 'Cerrar', { duration: 3000 });
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
      this.router.navigate(['/clients']);
    }
  }
}
