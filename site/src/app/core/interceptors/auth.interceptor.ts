import { inject } from '@angular/core';
import { HttpInterceptorFn } from '@angular/common/http';
import { AuthService } from '../services/auth.service';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const authService = inject(AuthService);
  const token = authService.getToken();

  // Solo agregar headers básicos
  let authReq = req.clone({
    setHeaders: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    }
  });

  // Agregar token a las peticiones si existe
  if (token && !req.headers.has('Authorization')) {
    authReq = authReq.clone({
      setHeaders: {
        'Authorization': `Bearer ${token}`
      }
    });
  }

  return next(authReq);
};
