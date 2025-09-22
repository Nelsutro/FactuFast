import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { UserService } from '../../core/services/user.service';
import { ApiResponse, User } from '../../core/interfaces/api-response.interface';

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    MatSnackBarModule
  ],
  templateUrl: './profile.component.html',
  styleUrls: ['./profile.component.css']
})
export class ProfileComponent implements OnInit {
  private fb = inject(FormBuilder);
  private userSvc = inject(UserService);
  private snackbar = inject(MatSnackBar);

  loading = false;
  user: User | null = null;

  profileForm = this.fb.group({
    name: ['', [Validators.required, Validators.maxLength(255)]],
    email: ['', [Validators.required, Validators.email, Validators.maxLength(255)]],
  });

  passwordForm = this.fb.group({
    current_password: ['', [Validators.required, Validators.minLength(8)]],
    password: ['', [Validators.required, Validators.minLength(8)]],
    password_confirmation: ['', [Validators.required, Validators.minLength(8)]],
  });

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.userSvc.getProfile().subscribe({
      next: (res: ApiResponse<User>) => {
        if (res.success && res.data) {
          this.user = res.data;
          this.profileForm.patchValue({
            name: res.data.name,
            email: res.data.email,
          });
        }
      },
      error: () => this.snackbar.open('No se pudo cargar el perfil', 'Cerrar', { duration: 3000 }),
      complete: () => (this.loading = false)
    });
  }

  saveProfile(): void {
    if (this.profileForm.invalid) return;
    const v = this.profileForm.value;
    const payload = {
      name: v.name ?? undefined,
      email: v.email ?? undefined,
    } as Partial<User> & { name?: string; email?: string };
    this.userSvc.updateProfile(payload).subscribe({
      next: () => this.snackbar.open('Perfil actualizado', 'Cerrar', { duration: 2000 }),
      error: () => this.snackbar.open('Error al actualizar', 'Cerrar', { duration: 3000 }),
    });
  }

  changePassword(): void {
    if (this.passwordForm.invalid) return;
    const v = this.passwordForm.value;
    const payload: any = {
      current_password: v.current_password ?? undefined,
      password: v.password ?? undefined,
      password_confirmation: v.password_confirmation ?? undefined,
    };
    this.userSvc.updateProfile(payload).subscribe({
      next: () => {
        this.snackbar.open('Contraseña actualizada', 'Cerrar', { duration: 2000 });
        this.passwordForm.reset();
      },
      error: (err) => {
        const msg = err?.error?.message || 'Error al cambiar contraseña';
        this.snackbar.open(msg, 'Cerrar', { duration: 3000 });
      },
    });
  }
}
