import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { Router } from '@angular/router';
import { RouterTestingModule } from '@angular/router/testing';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ForgotPasswordComponent } from './forgot-password.component';

describe('ForgotPasswordComponent', () => {
  let component: ForgotPasswordComponent;
  let fixture: ComponentFixture<ForgotPasswordComponent>;
  let snackBar: MatSnackBar;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ForgotPasswordComponent, RouterTestingModule]
    }).compileComponents();

    fixture = TestBed.createComponent(ForgotPasswordComponent);
    component = fixture.componentInstance;
    snackBar = TestBed.inject(MatSnackBar);
    spyOn(snackBar, 'open');
    fixture.detectChanges();
  });

  it('debería marcar el formulario como enviado cuando el correo es válido', fakeAsync(() => {
    component.form.setValue({ email: 'user@example.com' });
    component.onSubmit();
    expect(component.loading).toBeTrue();

    tick(900);
    expect(snackBar.open).toHaveBeenCalled();
    expect(component.submitted).toBeTrue();
    expect(component.loading).toBeFalse();
  }));
});
