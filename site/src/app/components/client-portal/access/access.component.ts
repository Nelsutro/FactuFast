import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ClientPortalService } from '../../../core/services/client-portal.service';

@Component({
  selector: 'app-access',
  imports: [
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    MatCardModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule
  ],
  templateUrl: './access.component.html',
  styleUrl: './access.component.css'
})
export class AccessComponent implements OnInit {
  accessForm: FormGroup;
  requestForm: FormGroup;
  loading = false;
  step: 'request' | 'access' = 'request';
  
  constructor(
    private fb: FormBuilder,
    private clientPortalService: ClientPortalService,
    private router: Router,
    private route: ActivatedRoute,
    private snackBar: MatSnackBar
  ) {
    this.requestForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]]
    });

    this.accessForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      token: ['', Validators.required]
    });
  }

  ngOnInit() {
    // Verificar si hay parámetros de token en la URL
    this.route.queryParams.subscribe(params => {
      if (params['token'] && params['email']) {
        this.step = 'access';
        this.accessForm.patchValue({
          email: params['email'],
          token: params['token']
        });
        // Auto-intentar acceso
        this.accessPortal();
      }
    });
  }

  requestAccess() {
    if (this.requestForm.invalid) return;

    this.loading = true;
    const email = this.requestForm.get('email')?.value;

    this.clientPortalService.requestAccess(email).subscribe({
      next: (response) => {
        if (response.success) {
          this.snackBar.open(response.message, 'Cerrar', { duration: 5000 });
          this.step = 'access';
          this.accessForm.patchValue({ email });
        }
        this.loading = false;
      },
      error: (error) => {
        console.error('Error solicitando acceso:', error);
        this.snackBar.open(error.error?.message || 'Error al solicitar acceso', 'Cerrar', { duration: 3000 });
        this.loading = false;
      }
    });
  }

  accessPortal() {
    if (this.accessForm.invalid) return;

    this.loading = true;
    const { email, token } = this.accessForm.value;

    this.clientPortalService.accessPortal(email, token).subscribe({
      next: (response) => {
        if (response.success) {
          // Guardar credenciales en localStorage para la sesión
          localStorage.setItem('client_portal_email', email);
          localStorage.setItem('client_portal_token', token);
          localStorage.setItem('client_portal_data', JSON.stringify(response.data));
          
          this.snackBar.open('¡Acceso autorizado!', 'Cerrar', { duration: 2000 });
          this.router.navigate(['/client-portal/dashboard']);
        }
        this.loading = false;
      },
      error: (error) => {
        console.error('Error accediendo al portal:', error);
        this.snackBar.open(error.error?.message || 'Error de acceso', 'Cerrar', { duration: 3000 });
        this.loading = false;
      }
    });
  }

  goBack() {
    this.step = 'request';
    this.accessForm.reset();
  }
}
