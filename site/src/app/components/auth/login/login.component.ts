import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { AuthService } from '../../../services/auth.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css'],
  standalone: false
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
      // Show success message (you can implement a toast service)
      console.log('Registro exitoso, ya puedes iniciar sesión');
    }
  }

  async onSubmit() {
    if (this.loading) return;

    try {
      this.loading = true;
      this.error = null;

      if (this.loginForm.invalid) {
        this.error = 'Por favor completa todos los campos correctamente';
        return;
      }

      const formValue = this.loginForm.value;

      // Simulate login (replace with real API call)
      await this.simulateLogin(formValue.email, formValue.password);

    } catch (error: any) {
      this.error = error.message || 'Error al iniciar sesión. Verifica tus credenciales.';
    } finally {
      this.loading = false;
    }
  }

  // Simulate login - Replace with real authService.login()
  private simulateLogin(email: string, password: string): Promise<void> {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        // Mock validation
        if (email === 'admin@factufast.com' && password === 'password') {
          // Mock successful login
          const mockUser = {
            id: 1,
            name: 'Administrador',
            email: email,
            role: 'admin',
            token: 'mock-jwt-token-' + Date.now()
          };

          // Store user data
          localStorage.setItem('currentUser', JSON.stringify(mockUser));

          // Navigate to return URL
          this.router.navigate([this.returnUrl]);
          resolve();
        } else {
          reject(new Error('Credenciales inválidas'));
        }
      }, 1500);
    });
  }

  // Social login methods
  async loginWithGoogle() {
    try {
      this.loading = true;
      this.error = null;

      console.log('Iniciando sesión con Google...');
      
      // In real implementation, integrate with Google OAuth
      // For now, simulate successful Google login
      await this.simulateOAuthLogin('google', 'Usuario Google');

    } catch (error: any) {
      this.error = 'Error al iniciar sesión con Google';
    } finally {
      this.loading = false;
    }
  }

  async loginWithMicrosoft() {
    try {
      this.loading = true;
      this.error = null;

      console.log('Iniciando sesión con Microsoft...');
      
      // In real implementation, integrate with Microsoft OAuth
      await this.simulateOAuthLogin('microsoft', 'Usuario Microsoft');

    } catch (error: any) {
      this.error = 'Error al iniciar sesión con Microsoft';
    } finally {
      this.loading = false;
    }
  }

  async loginWithApple() {
    try {
      this.loading = true;
      this.error = null;

      console.log('Iniciando sesión con Apple...');
      
      // In real implementation, integrate with Apple OAuth
      await this.simulateOAuthLogin('apple', 'Usuario Apple');

    } catch (error: any) {
      this.error = 'Error al iniciar sesión con Apple';
    } finally {
      this.loading = false;
    }
  }

  // Simulate OAuth login
  private simulateOAuthLogin(provider: string, name: string): Promise<void> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const mockUser = {
          id: Date.now(),
          name: name,
          email: `user@${provider}.com`,
          role: 'client',
          token: `mock-${provider}-token-` + Date.now(),
          provider: provider
        };

        localStorage.setItem('currentUser', JSON.stringify(mockUser));
        this.router.navigate([this.returnUrl]);
        resolve();
      }, 2000);
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
      password: 'password'
    });
  }
}