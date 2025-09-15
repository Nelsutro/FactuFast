import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { 
  ApiResponse, 
  AuthResponse, 
  User, 
  LoginRequest, 
  RegisterRequest 
} from '../interfaces/api-response.interface';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private readonly apiUrl = `${environment.apiUrl}/auth`;
  private readonly tokenKey = 'auth_token';
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  
  public currentUser$ = this.currentUserSubject.asObservable();

  constructor(private http: HttpClient) {
    console.log('AuthService iniciando...');
    this.loadUserFromStorage();
  }

  /**
   * Registrar nuevo usuario
   */
  register(userData: RegisterRequest): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/register`, userData).pipe(
      tap(response => {
        if (response.success && response.data) {
          this.setToken(response.data.token);
          this.currentUserSubject.next(response.data.user);
        }
      })
    );
  }

  /**
   * Iniciar sesión
   */
  login(credentials: LoginRequest): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/login`, credentials).pipe(
      tap(response => {
        if (response.success && response.data) {
          this.setToken(response.data.token);
          this.currentUserSubject.next(response.data.user);
        }
      })
    );
  }

  /**
   * Cerrar sesión
   */
  logout(): Observable<ApiResponse> {
    return this.http.post<ApiResponse>(`${this.apiUrl}/logout`, {}).pipe(
      tap(() => {
        this.removeToken();
        this.currentUserSubject.next(null);
      })
    );
  }

  /**
   * Verificar conexión con el backend
   */
  checkConnection(): Observable<ApiResponse> {
    return this.http.get<ApiResponse>(`${this.apiUrl}/user`);
  }

  /**
   * Obtener usuario actual
   */
  getCurrentUser(): Observable<ApiResponse<User>> {
    return this.http.get<ApiResponse<User>>(`${this.apiUrl}/user`).pipe(
      tap(response => {
        if (response.success && response.data) {
          this.currentUserSubject.next(response.data);
        }
      })
    );
  }

  /**
   * Verificar si el usuario está autenticado
   */
  isAuthenticated(): boolean {
    const token = this.getToken();
    return !!token && !this.isTokenExpired();
  }

  /**
   * Verificar si el token está expirado
   */
  isTokenExpired(): boolean {
    const token = this.getToken();
    if (!token) return true;

    // Laravel Sanctum usa tokens opacos, no JWT
    // No podemos verificar expiración desde el token directamente
    // En lugar de eso, confiamos en la respuesta del servidor
    return false;
  }

  /**
   * Verificar si el usuario tiene alguno de los roles especificados
   */
  hasAnyRole(roles: string[]): boolean {
    const currentUser = this.currentUserSubject.value;
    if (!currentUser || !currentUser.role) return false;
    
    return roles.includes(currentUser.role);
  }

  /**
   * Obtener el rol del usuario actual
   */
  getCurrentUserRole(): string | null {
    const currentUser = this.currentUserSubject.value;
    return currentUser?.role || null;
  }

  /**
   * Verificar si el usuario actual es admin
   */
  isAdmin(): boolean {
    const currentUser = this.currentUserSubject.value;
    return currentUser?.role === 'admin';
  }

  /**
   * Verificar si el usuario actual es client
   */
  isClient(): boolean {
    const currentUser = this.currentUserSubject.value;
    return currentUser?.role === 'client';
  }

  /**
   * Obtener la empresa del usuario actual
   */
  getUserCompany(): any | null {
    const currentUser = this.currentUserSubject.value;
    return currentUser?.company || null;
  }

  /**
   * Obtener el usuario actual directamente
   */
  getCurrentUserData(): User | null {
    return this.currentUserSubject.value;
  }

  /**
   * Obtener token de autenticación
   */
  getToken(): string | null {
    return localStorage.getItem(this.tokenKey);
  }

  /**
   * Guardar token en localStorage
   */
  private setToken(token: string): void {
    console.log('Guardando token en localStorage:', token.substring(0, 20) + '...');
    localStorage.setItem(this.tokenKey, token);
  }

  /**
   * Eliminar token del localStorage
   */
  private removeToken(): void {
    console.log('Eliminando token de localStorage');
    localStorage.removeItem(this.tokenKey);
  }

  /**
   * Cargar usuario desde localStorage al inicializar
   */
  public loadUserFromStorage(): void {
    const token = this.getToken();
    if (token) {
      console.log('Token encontrado en localStorage:', token.substring(0, 20) + '...');
      
      // Primero verificar si el token no está expirado
      if (this.isTokenExpired()) {
        console.log('Token expirado, eliminando...');
        this.removeToken();
        this.currentUserSubject.next(null);
        return;
      }
      
      // Si hay token válido, obtener datos del usuario
      let attemptedRetry = false;
      const attemptLoad = () => {
        console.log('[Auth] Intentando cargar usuario con token...');
        this.getCurrentUser().subscribe({
          next: (response) => {
            console.log('Respuesta getCurrentUser:', response);
            if (response.success && response.data) {
              console.log('Usuario cargado desde token:', response.data.name);
              this.currentUserSubject.next(response.data);
            } else {
              console.warn('Respuesta sin success/data. Manteniendo token pero usuario null.');
              this.currentUserSubject.next(null);
            }
          },
          error: (error) => {
            const status = error?.status;
            console.error('[Auth] Error al validar token. Status:', status);
            if (status === 401 || status === 403) {
              console.warn('[Auth] Token inválido o no autorizado. Eliminando token.');
              this.removeToken();
              this.currentUserSubject.next(null);
            } else if (!attemptedRetry && (status === 0 || status >= 500)) {
              attemptedRetry = true;
              console.warn('[Auth] Error transitorio (status ' + status + '). Reintentando una vez en 800ms...');
              setTimeout(() => attemptLoad(), 800);
            } else {
              console.warn('[Auth] Error no crítico. Manteniendo token para reintento futuro.');
              this.currentUserSubject.next(null);
            }
          }
        });
      };
      attemptLoad();
    } else {
      console.log('No hay token en localStorage');
      this.currentUserSubject.next(null);
    }
  }
}
