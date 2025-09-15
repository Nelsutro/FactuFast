import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatDividerModule } from '@angular/material/divider';
import { AuthService } from '../../../core/services/auth.service';
import { finalize } from 'rxjs/operators';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatCardModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    MatCheckboxModule,
    MatProgressSpinnerModule,
    MatDividerModule
  ]
})
export class LoginComponent implements OnInit {

  loginForm!: FormGroup;
  loading = false;
  error: string | null = null;
  showPassword = false;
  returnUrl = '/dashboard';

  constructor(
    private authService: AuthService,
    private router: Router,
    private route: ActivatedRoute,
    private formBuilder: FormBuilder
  ) {}

  ngOnInit() {
    // Initialize form
    this.loginForm = this.formBuilder.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]],
      rememberMe: [false]
    });

    // Get return url from route parameters or default to dashboard
    this.returnUrl = this.route.snapshot.queryParams['returnUrl'] || '/dashboard';

    // Check if already logged in
    if (this.authService.isAuthenticated()) {
      this.router.navigate([this.returnUrl]);
    }

    // Check for registration success message
    const message = this.route.snapshot.queryParams['message'];
    if (message === 'registration-success') {
      console.log('Registro exitoso, ya puedes iniciar sesión');
    }

    console.log('Login component initialized, returnUrl:', this.returnUrl);
  }

  async onSubmit() {
    if (this.loading) return;

    this.loading = true;
    this.error = null;

    if (this.loginForm.invalid) {
      this.error = 'Por favor completa todos los campos correctamente';
      this.loading = false;
      return;
    }

    const formValue = this.loginForm.value;
    console.log('Intentando login con:', formValue.email);

    this.authService.login({
      email: formValue.email,
      password: formValue.password
    })
    .pipe(
      finalize(() => {
        // Asegura que loading vuelva a false en éxito o error
        this.loading = false;
      })
    )
    .subscribe({
      next: (response) => {
        console.log('Response received:', response);
        
        if (response.success && response.data) {
          console.log('Login exitoso, token guardado');
          console.log('Usuario logueado:', response.data.user);
          
          // Forzar actualización del estado de autenticación
          this.authService.loadUserFromStorage();
          
          // Usar timeout para dar tiempo a la actualización del estado
          setTimeout(() => {
            console.log('Navegando a:', this.returnUrl);
            this.router.navigate([this.returnUrl]).then((navigated) => {
              console.log('Navigation result:', navigated);
              if (!navigated) {
                console.error('Navegación falló, usando window.location');
                window.location.href = this.returnUrl;
              }
            });
          }, 100);
        } else {
          this.error = response.message || 'Credenciales inválidas';
        }
      },
      error: (error) => {
        console.error('Error en login:', error);
        this.error = error?.error?.message || 'Error al iniciar sesión. Verifica tus credenciales.';
      }
    });
  }

  // Utility methods
  togglePasswordVisibility() {
    this.showPassword = !this.showPassword;
  }

  // Demo credentials
  fillDemoCredentials() {
    this.loginForm.patchValue({
      email: 'admin@factufast.com',
      password: 'password123'
    });
  }

  // Navigate to register
  goToRegister() {
    console.log('Navigating to register...');
    this.router.navigate(['/register']).then((navigated) => {
      console.log('Register navigation result:', navigated);
    });
  }

  // Navigate to forgot password
  goToForgotPassword() {
    this.router.navigate(['/forgot-password']);
  }
}