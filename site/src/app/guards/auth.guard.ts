import { Injectable } from '@angular/core';
import { CanActivate, Router, ActivatedRouteSnapshot, RouterStateSnapshot } from '@angular/router';
import { Observable } from 'rxjs';
import { AuthService } from '../core/services/auth.service';

@Injectable({
  providedIn: 'root'
})
export class AuthGuard implements CanActivate {

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<boolean> | Promise<boolean> | boolean {
    
    console.log('AuthGuard - Verificando acceso a:', state.url);
    
    // Verificar token básico
    const hasToken = this.authService.isAuthenticated();
    console.log('AuthGuard - Tiene token válido:', hasToken);
    
    if (!hasToken) {
      console.log('AuthGuard - No autenticado, redirigiendo a login...');
      this.router.navigate(['/login'], { 
        queryParams: { returnUrl: state.url } 
      });
      return false;
    }

    // Verificar roles si están especificados en la ruta
    const expectedRoles = route.data['roles'] as string[];
    if (expectedRoles) {
      const hasRole = this.authService.hasAnyRole(expectedRoles);
      console.log('AuthGuard - Verificando roles:', expectedRoles, 'Usuario tiene acceso:', hasRole);
      
      if (!hasRole) {
        console.log('AuthGuard - Sin permisos suficientes, redirigiendo...');
        this.router.navigate(['/unauthorized']);
        return false;
      }
    }
    
    console.log('AuthGuard - Acceso permitido');
    return true;
  }
}