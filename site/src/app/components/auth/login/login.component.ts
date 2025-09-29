import { Component, OnInit, OnDestroy } from '@angular/core';
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
import { OauthService } from '../../../core/services/oauth.service';
import { finalize } from 'rxjs/operators';
import { environment } from '../../../../environments/environment';

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
export class LoginComponent implements OnInit, OnDestroy {

  loginForm!: FormGroup;
  loading = false;
  error: string | null = null;
  showPassword = false;
  returnUrl = '/dashboard';
  oauthLoading = false;
  oauthError: string | null = null;
  oauthProvider: string | null = null;
  private oauthPopup: Window | null = null;
  private pendingState: string | null = null;
  private messageListener = (event: MessageEvent) => this.handleOAuthMessage(event);
  private readonly allowedOrigins = new Set<string>([
    environment.appUrl.replace(/\/$/, ''),
    window.location.origin.replace(/\/$/, '')
  ]);

  constructor(
    private authService: AuthService,
    private oauthService: OauthService,
    private router: Router,
    private route: ActivatedRoute,
    private formBuilder: FormBuilder
  ) {}

  ngOnInit() {
    // Initialize form
    this.loginForm = this.formBuilder.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]],
      tax_id: [''], // RUT opcional
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

  ngOnDestroy() {
    this.cleanupOAuth();
    window.removeEventListener('message', this.messageListener);
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
      password: formValue.password,
      tax_id: formValue.tax_id || undefined
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

  startOAuth(provider: 'google' | 'microsoft' | 'apple') {
    if (this.oauthLoading) {
      return;
    }

    this.oauthError = null;
    this.oauthProvider = provider;
    this.oauthLoading = true;

    const returnUrl = this.route.snapshot.queryParams['returnUrl'] || this.returnUrl;

    this.oauthService
      .requestRedirect(provider, {
        returnUrl,
        redirectUri: this.oauthService.getDefaultRedirectUri(),
        from: 'login'
      })
      .subscribe({
        next: (response) => {
          this.pendingState = response.state;
          this.registerMessageListener();

          const features = 'width=480,height=680,top=100,left=100,menubar=no,toolbar=no,status=no';
          this.oauthPopup = window.open(response.authorizationUrl, 'factufast-oauth', features);

          if (!this.oauthPopup) {
            this.oauthError = 'No se pudo abrir la ventana de autenticación. Deshabilita el bloqueador de ventanas emergentes e inténtalo nuevamente.';
            this.oauthLoading = false;
            this.pendingState = null;
          }
        },
        error: (error) => {
          console.error('Error solicitando OAuth redirect:', error);
          this.oauthError = error?.error?.message || 'No fue posible iniciar la autenticación externa.';
          this.oauthLoading = false;
          this.pendingState = null;
        }
      });
  }

  private registerMessageListener() {
    window.removeEventListener('message', this.messageListener);
    window.addEventListener('message', this.messageListener);
  }

  private handleOAuthMessage(event: MessageEvent) {
    if (!event?.data || typeof event.data !== 'object') {
      return;
    }

    const origin = event.origin.replace(/\/$/, '');
    if (!this.allowedOrigins.has(origin)) {
      return;
    }

    const { type, status, token, message, provider, state, returnUrl } = event.data;
    if (type !== 'factufast:oauth') {
      return;
    }

    if (this.pendingState && state && state !== this.pendingState) {
      console.warn('State mismatch en OAuth, ignorando mensaje.');
      return;
    }

    this.cleanupOAuth(true);

    if (status === 'success' && token) {
      this.authService.applyToken(token);

      const target = returnUrl || this.returnUrl;
      setTimeout(() => this.navigateToTarget(target), 200);
    } else {
      this.oauthError = message || 'El proveedor rechazó la autenticación.';
    }
  }

  private cleanupOAuth(forceClosePopup: boolean = false) {
    this.oauthLoading = false;
    this.oauthProvider = null;
    this.pendingState = null;
    window.removeEventListener('message', this.messageListener);

    if (forceClosePopup && this.oauthPopup && !this.oauthPopup.closed) {
      this.oauthPopup.close();
    }

    this.oauthPopup = null;
  }

  private navigateToTarget(target: string) {
    if (/^https?:\/\//i.test(target)) {
      window.location.href = target;
      return;
    }

    this.router.navigate([target]).catch(() => (window.location.href = target));
  }
}