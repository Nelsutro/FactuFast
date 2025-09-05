import { Component } from '@angular/core';
import { MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatDialogModule } from '@angular/material/dialog';

@Component({
  selector: 'app-logout-confirm-dialog',
  template: `
    <h2 mat-dialog-title>Confirmar cierre de sesión</h2>
    <mat-dialog-content>
      ¿Está seguro que desea cerrar sesión?
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button mat-dialog-close>Cancelar</button>
      <button mat-raised-button color="warn" [mat-dialog-close]="true">Cerrar Sesión</button>
    </mat-dialog-actions>
  `,
  standalone: true,
  imports: [MatDialogModule, MatButtonModule]
})
export class LogoutConfirmDialogComponent {
  constructor(
    public dialogRef: MatDialogRef<LogoutConfirmDialogComponent>
  ) {}
}
