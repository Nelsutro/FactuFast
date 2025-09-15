import { Component, Inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { Client, ClientService, CreateClientRequest } from '../../core/services/client.service';

export interface ClientDialogData {
  client: Client | null;
}

@Component({
  selector: 'app-client-dialog',
  templateUrl: './client-dialog.component.html',
  styleUrls: ['./client-dialog.component.css'],
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
export class ClientDialogComponent {
  form: FormGroup;
  loading = false;
  isEdit = false;

  constructor(
    private fb: FormBuilder,
    private dialogRef: MatDialogRef<ClientDialogComponent>,
    @Inject(MAT_DIALOG_DATA) public data: ClientDialogData,
    private clientService: ClientService
  ) {
    this.isEdit = !!data.client;
    this.form = this.fb.group({
      name: [data.client?.name || '', [Validators.required, Validators.minLength(2)]],
      email: [data.client?.email || '', [Validators.email]],
      phone: [data.client?.phone || ''],
      address: [data.client?.address || '']
    });
  }

  save() {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.loading = true;
    const payload: CreateClientRequest = this.form.value;

    if (this.isEdit && this.data.client) {
      this.clientService.updateClient(this.data.client.id, payload).subscribe({
        next: () => {
          this.loading = false;
          this.dialogRef.close(true);
        },
        error: () => {
          this.loading = false;
        }
      });
    } else {
      this.clientService.createClient(payload).subscribe({
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
