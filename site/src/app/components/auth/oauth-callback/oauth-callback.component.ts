import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatIconModule } from '@angular/material/icon';
import { AuthService } from '../../../core/services/auth.service';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-oauth-callback',
  standalone: true,
  imports: [CommonModule, MatProgressSpinnerModule, MatIconModule],
  templateUrl: './oauth-callback.component.html',
  styleUrls: ['./oauth-callback.component.css']
})
export class OauthCallbackComponent implements OnInit {
  status: 'loading' | 'success' | 'error' = 'loading';
  message = 'Procesando autenticación...';
  provider?: string;
  returnUrl?: string;
  private state?: string;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private authService: AuthService
  ) {}

  ngOnInit(): void {
    this.route.queryParamMap.subscribe(params => {
      const status = (params.get('status') || 'success') as 'success' | 'error';
      const token = params.get('token');
      this.provider = params.get('provider') || undefined;
      this.returnUrl = params.get('return_url') || undefined;
      this.state = params.get('state') || undefined;

      if (status === 'success' && token) {
        this.handleSuccess(token);
      } else {
        const message = params.get('message') || 'No fue posible completar el inicio de sesión.';
        this.handleError(message);
      }
    });
  }

  private handleSuccess(token: string): void {
    this.status = 'success';
    this.message = 'Inicio de sesión exitoso. Redirigiendo...';

    this.authService.applyToken(token);

    this.notifyOpener({
      status: 'success',
      token,
      provider: this.provider,
      returnUrl: this.returnUrl,
      state: this.state
    });

    setTimeout(() => {
      if (this.returnUrl) {
        this.router.navigateByUrl(this.returnUrl).catch(() => window.location.assign(this.returnUrl!));
      } else {
        this.router.navigate(['/dashboard']).catch(() => window.location.assign('/dashboard'));
      }

      if (window.opener && !window.opener.closed) {
        window.close();
      }
    }, 900);
  }

  private handleError(message: string): void {
    this.status = 'error';
    this.message = message;

    this.notifyOpener({
      status: 'error',
      provider: this.provider,
      message,
      state: this.state
    });
  }

  private notifyOpener(payload: Record<string, unknown>): void {
    const data = {
      type: 'factufast:oauth',
      ...payload
    };

    try {
      const target = window.location.origin || environment.appUrl;
      if (window.opener && !window.opener.closed) {
        window.opener.postMessage(data, target);
      } else if (window.parent && window.parent !== window) {
        window.parent.postMessage(data, target);
      }
    } catch (error) {
      console.warn('[OAuth Callback] No se pudo notificar a la ventana origen', error);
    }
  }

  closeWindow(): void {
    window.close();
  }
}
