import { Injectable } from '@angular/core';
import { CanActivate, Router, ActivatedRouteSnapshot, RouterStateSnapshot } from '@angular/router';
import { AuthService } from '../services/auth.service';

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
  ): boolean {
    
    // Check if user is authenticated
    if (this.authService.isAuthenticated()) {
      return true;
    }

    // If not authenticated, redirect to login
    console.log('Usuario no autenticado, redirigiendo a login...');
    this.router.navigate(['/login'], { 
      queryParams: { returnUrl: state.url } 
    });
    
    return false;
  }
}