import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AboutComponent } from './about.component';
import { MatSnackBar } from '@angular/material/snack-bar';
import { NoopAnimationsModule } from '@angular/platform-browser/animations';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { environment } from '../../../../environments/environment';

describe('AboutComponent', () => {
  let component: AboutComponent;
  let fixture: ComponentFixture<AboutComponent>;
  let httpMock: HttpTestingController;
  const snackBarSpy = jasmine.createSpyObj<MatSnackBar>('MatSnackBar', ['open']);

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AboutComponent, NoopAnimationsModule, HttpClientTestingModule],
      providers: [{ provide: MatSnackBar, useValue: snackBarSpy }]
    }).compileComponents();

    fixture = TestBed.createComponent(AboutComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
    httpMock = TestBed.inject(HttpTestingController);
    snackBarSpy.open.calls.reset();
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should create the about page', () => {
    expect(component).toBeTruthy();
  });

  it('should keep contact form invalid when empty', () => {
    expect(component.contactForm.valid).toBeFalse();
    component.submitContact();
    expect(component.contactForm.touched || component.contactForm.dirty).toBeTrue();
  });

  it('should submit contact form when valid', () => {
    component.contactForm.setValue({
      fullName: 'María González',
      email: 'maria@empresa.cl',
      company: 'Empresa Demo',
      message: 'Quisiera agendar una demo para conocer FactuFast.'
    });

    component.submitContact();
    expect(component.submitting).toBeTrue();

    const req = httpMock.expectOne(`${environment.apiUrl}/contact`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({
      fullName: 'María González',
      email: 'maria@empresa.cl',
      company: 'Empresa Demo',
      message: 'Quisiera agendar una demo para conocer FactuFast.',
      source: 'about-page'
    });
    req.flush({ success: true });

    expect(component.submitting).toBeFalse();
    expect(snackBarSpy.open).toHaveBeenCalledWith(
      '¡Gracias! Nos pondremos en contacto pronto.',
      'Cerrar',
      jasmine.objectContaining({ duration: 3500 })
    );
    expect(component.contactForm.value.fullName).toBeNull();
  });

  it('should show error message when submission fails', () => {
    component.contactForm.setValue({
      fullName: 'Pedro López',
      email: 'pedro@empresa.cl',
      company: 'Empresa Prueba',
      message: 'Necesitamos más información del servicio.'
    });

    component.submitContact();
    const req = httpMock.expectOne(`${environment.apiUrl}/contact`);
    req.flush({ message: 'Error interno' }, { status: 500, statusText: 'Server Error' });

    expect(component.submitting).toBeFalse();
    expect(snackBarSpy.open).toHaveBeenCalledWith(
      'Error interno',
      'Cerrar',
      jasmine.objectContaining({ duration: 3500 })
    );
  });
});
