import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface OAuthRedirectResponse {
  authorizationUrl: string;
  state: string;
  expiresAt: string;
  provider: string;
}

@Injectable({ providedIn: 'root' })
export class OauthService {
  private readonly baseUrl = `${environment.apiUrl}/auth/oauth`;
  private readonly defaultRedirect = `${environment.appUrl}/oauth/callback`;

  constructor(private http: HttpClient) {}

  getProviders(): Observable<string[]> {
    return this.http.get<{ success: boolean; data: string[] }>(`${this.baseUrl}/providers`).pipe(
      map((response) => response.data || [])
    );
  }

  requestRedirect(provider: string, options?: { redirectUri?: string; returnUrl?: string; from?: string }): Observable<OAuthRedirectResponse> {
    let params = new HttpParams().set('redirect_uri', options?.redirectUri ?? this.defaultRedirect);

    if (options?.returnUrl) {
      params = params.set('return_url', options.returnUrl);
    }

    if (options?.from) {
      params = params.set('from', options.from);
    }

    return this.http
      .get<{ success: boolean; data: any }>(`${this.baseUrl}/${provider}/redirect`, { params })
      .pipe(
        map((response) => ({
          authorizationUrl: response.data.authorization_url,
          state: response.data.state,
          expiresAt: response.data.expires_at,
          provider: response.data.provider,
        }))
      );
  }

  getDefaultRedirectUri(): string {
    return this.defaultRedirect;
  }
}
