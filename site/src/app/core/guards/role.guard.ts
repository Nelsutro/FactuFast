import { Injectable } from '@angular/core';
import { CanActivate, Router, ActivatedRouteSnapshot } from '@angular/router';
import { Observable, map } from 'rxjs';
import { AuthService } from '../services/auth.service';

@Injectable({
  providedIn: 'root'
})
export class RoleGuard implements CanActivate {

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  canActivate(route: ActivatedRouteSnapshot): Observable<boolean> {
    const requiredRoles = route.data['roles'] as string[];
    
    return this.authService.currentUser$.pipe(
      map(user => {
        if (!user) {
          this.router.navigate(['/auth/login']);
          return false;
        }

        if (!requiredRoles || requiredRoles.length === 0) {
          return true; // No hay restricción de roles
        }

        if (requiredRoles.includes(user.role)) {
          return true;
        }

        // Usuario no tiene el rol requerido
        this.router.navigate(['/dashboard']);
        return false;
      })
    );
  }
}
