import { ComponentFixture, TestBed } from '@angular/core/testing';
import { convertToParamMap, ActivatedRoute } from '@angular/router';
import { of } from 'rxjs';
import { RouterTestingModule } from '@angular/router/testing';
import { InvoiceDetailComponent } from './invoice-detail.component';
import { ApiService } from '../../services/api.service';

describe('InvoiceDetailComponent', () => {
  let component: InvoiceDetailComponent;
  let fixture: ComponentFixture<InvoiceDetailComponent>;
  const apiSpy = jasmine.createSpyObj<ApiService>('ApiService', ['getInvoice', 'downloadInvoicePdf']);

  beforeEach(async () => {
    apiSpy.getInvoice.and.returnValue(of({ id: 1, invoice_number: 'INV-1', amount: 10000 }));

    await TestBed.configureTestingModule({
      imports: [InvoiceDetailComponent, RouterTestingModule],
      providers: [
        { provide: ApiService, useValue: apiSpy },
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              paramMap: convertToParamMap({ id: '1' })
            }
          }
        }
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(InvoiceDetailComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('debería cargar la factura en el init', () => {
    expect(apiSpy.getInvoice).toHaveBeenCalledWith(1);
    expect(component.invoice).toEqual(jasmine.objectContaining({ invoice_number: 'INV-1' }));
    expect(component.loading).toBeFalse();
  });
});
