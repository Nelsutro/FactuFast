import { ComponentFixture, TestBed } from '@angular/core/testing';
import { QuoteCreateComponent } from './quote-create.component';
import { ApiService } from '../../services/api.service';
import { Router, ActivatedRoute, convertToParamMap } from '@angular/router';
import { MatSnackBar } from '@angular/material/snack-bar';
import { AuthService } from '../../core/services/auth.service';
import { of } from 'rxjs';

describe('QuoteCreateComponent', () => {
  function setupTest(routeParams: Record<string, string> = {}) {
    const apiServiceSpy = jasmine.createSpyObj<ApiService>('ApiService', ['createQuote', 'updateQuote', 'getQuote']);
    const routerSpy = jasmine.createSpyObj<Router>('Router', ['navigate']);
    const snackSpy = jasmine.createSpyObj<MatSnackBar>('MatSnackBar', ['open']);
    const authStub = { getUserCompany: () => ({ id: 7 }) } as AuthService;

    apiServiceSpy.createQuote.and.returnValue(of({ id: 100 }));
    apiServiceSpy.updateQuote.and.returnValue(of({ id: Number(routeParams['duplicate']) || 100 }));
    apiServiceSpy.getQuote.and.returnValue(of({
      id: Number(routeParams['duplicate']) || 1,
      client_id: 5,
      valid_until: '2025-12-31',
      items: [
        { description: 'Servicio', quantity: 2, price: 15000 }
      ]
    }));

    return TestBed.configureTestingModule({
      imports: [QuoteCreateComponent],
      providers: [
        { provide: ApiService, useValue: apiServiceSpy },
        { provide: Router, useValue: routerSpy },
        { provide: MatSnackBar, useValue: snackSpy },
        { provide: AuthService, useValue: authStub },
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              queryParamMap: convertToParamMap(routeParams)
            }
          }
        }
      ]
    }).compileComponents().then(() => ({ apiServiceSpy, routerSpy }));
  }

  afterEach(() => TestBed.resetTestingModule());

  it('should start in create mode by default', async () => {
  const { apiServiceSpy } = await setupTest();
    const fixture: ComponentFixture<QuoteCreateComponent> = TestBed.createComponent(QuoteCreateComponent);
    const component = fixture.componentInstance;

    fixture.detectChanges();

    expect(component.editing).toBeFalse();
    expect(component.title).toBe('Nueva Cotización');
    expect(component.submitLabel).toBe('Crear');
    expect(component.initializing).toBeFalse();
    expect(apiServiceSpy.getQuote).not.toHaveBeenCalled();
  });

  it('should load quote data when editing', async () => {
    await setupTest({ duplicate: '42', mode: 'edit' });
    const fixture: ComponentFixture<QuoteCreateComponent> = TestBed.createComponent(QuoteCreateComponent);
    const component = fixture.componentInstance;

    fixture.detectChanges();

    expect(component.editing).toBeTrue();
    expect(component.title).toBe('Editar Cotización');
    expect(component.submitLabel).toBe('Guardar cambios');
    expect(component.initializing).toBeFalse();
    expect(component.form.get('client_id')?.value).toBe(5);
    expect(component.items.length).toBe(1);
    expect(component.items.at(0).get('description')?.value).toBe('Servicio');
  });

  it('should call updateQuote when submitting in edit mode', async () => {
    const { apiServiceSpy, routerSpy } = await setupTest({ duplicate: '10', mode: 'edit' });
    const fixture: ComponentFixture<QuoteCreateComponent> = TestBed.createComponent(QuoteCreateComponent);
    const component = fixture.componentInstance;

    fixture.detectChanges();

    component.submit();

    expect(apiServiceSpy.updateQuote).toHaveBeenCalledWith(10, jasmine.objectContaining({
      client_id: 5,
      valid_until: '2025-12-31'
    }));
    expect(routerSpy.navigate).toHaveBeenCalledWith(['/quotes', 10]);
  });
});
