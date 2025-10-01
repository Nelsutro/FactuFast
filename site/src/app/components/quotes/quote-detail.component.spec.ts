import { ComponentFixture, TestBed } from '@angular/core/testing';
import { convertToParamMap, ActivatedRoute } from '@angular/router';
import { RouterTestingModule } from '@angular/router/testing';
import { of } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { QuoteDetailComponent } from './quote-detail.component';

describe('QuoteDetailComponent', () => {
  let component: QuoteDetailComponent;
  let fixture: ComponentFixture<QuoteDetailComponent>;
  const apiSpy = jasmine.createSpyObj<ApiService>('ApiService', ['getQuote', 'convertQuoteToInvoice', 'downloadQuotePdf']);
  const originalCreateObjectURL = window.URL.createObjectURL;
  const originalRevokeObjectURL = window.URL.revokeObjectURL;
  const createObjectURLSpy = jasmine.createSpy('createObjectURL').and.returnValue('blob:url');
  const revokeObjectURLSpy = jasmine.createSpy('revokeObjectURL');

  beforeAll(() => {
    (window.URL as any).createObjectURL = createObjectURLSpy;
    (window.URL as any).revokeObjectURL = revokeObjectURLSpy;
  });

  afterAll(() => {
    (window.URL as any).createObjectURL = originalCreateObjectURL;
    (window.URL as any).revokeObjectURL = originalRevokeObjectURL;
  });

  beforeEach(async () => {
    apiSpy.getQuote.and.returnValue(of({ id: 5, quote_number: 'Q-0005', amount: 25000 }));
    apiSpy.downloadQuotePdf.and.returnValue(of(new Blob(['test'])));
    createObjectURLSpy.calls.reset();
    revokeObjectURLSpy.calls.reset();

    await TestBed.configureTestingModule({
      imports: [QuoteDetailComponent, RouterTestingModule],
      providers: [
        { provide: ApiService, useValue: apiSpy },
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              paramMap: convertToParamMap({ id: '5' }),
              url: []
            }
          }
        }
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(QuoteDetailComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('debería cargar la cotización en el init', () => {
    expect(apiSpy.getQuote).toHaveBeenCalledWith(5);
    expect(component.quote).toEqual(jasmine.objectContaining({ quote_number: 'Q-0005' }));
    expect(component.loading).toBeFalse();
  });

  it('debería descargar la cotización en PDF', () => {
    component.quote = { id: 5, quote_number: 'Q-0005' };
    component.downloadPdf();

    expect(apiSpy.downloadQuotePdf).toHaveBeenCalledWith(5);
    expect(component.downloading).toBeFalse();
    expect(createObjectURLSpy).toHaveBeenCalled();
    expect(revokeObjectURLSpy).toHaveBeenCalledWith('blob:url');
  });
});
