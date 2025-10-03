import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatCardModule } from '@angular/material/card';
import { MatDividerModule } from '@angular/material/divider';
import { MatChipsModule } from '@angular/material/chips';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

interface Metric {
  label: string;
  value: string;
  description: string;
}

interface Pillar {
  title: string;
  description: string;
  icon: string;
}

interface TimelineEvent {
  year: string;
  title: string;
  description: string;
}

interface ContactChannel {
  label: string;
  value: string;
  icon: string;
  href?: string;
}

@Component({
  selector: 'app-about-page',
  standalone: true,
  templateUrl: './about.component.html',
  styleUrls: ['./about.component.css'],
  imports: [
    CommonModule,
    MatButtonModule,
    MatIconModule,
    MatCardModule,
    MatDividerModule,
    MatChipsModule,
    ReactiveFormsModule,
    MatFormFieldModule,
    MatInputModule,
    MatSnackBarModule
  ]
})
export class AboutComponent {
  readonly metrics: Metric[] = [
    { label: 'Empresas activas', value: '+10', description: 'Pymes que automatizan su facturación con nosotros.' },
    { label: 'Documentos procesados', value: '3,5M', description: 'Facturas, cotizaciones y pagos gestionados al año.' },
    { label: 'Reducción operativa', value: '45%', description: 'Menos tiempo invertido en tareas administrativas.' },
    { label: 'Integraciones', value: '12', description: 'Con sistemas contables, ERP y pasarelas de pago.' }
  ];

  readonly pillars: Pillar[] = [
    {
      title: 'Automatización inteligente',
      description: 'Flujos configurables, recordatorios automáticos y conciliación en segundos.',
      icon: 'bolt'
    },
    {
      title: 'Seguridad y cumplimiento',
      description: 'Datos encriptados, controles de acceso y cumplimiento tributario al día.',
      icon: 'verified_user'
    },
    {
      title: 'Acompañamiento experto',
      description: 'Equipo especializado que te guía en cada etapa del proceso.',
      icon: 'support_agent'
    },
    {
      title: 'Resultados medibles',
      description: 'Paneles claros y KPIs que muestran el impacto en tu negocio.',
      icon: 'insights'
    }
  ];

  readonly timeline: TimelineEvent[] = [
    {
      year: '2025',
      title: 'Nacimiento de FactuFast',
      description: 'Detectamos el dolor de las pymes para conciliar ventas y decidimos construir una plataforma enfocada en eficiencia.'
    }
  ];

  readonly contactChannels: ContactChannel[] = [
    { label: 'Escríbenos', value: 'hola@factufast.com', icon: 'mail', href: 'mailto:hola@factufast.com' },
    { label: 'Llámanos', value: '+56 9 4567 8900', icon: 'call', href: 'tel:+56945678900' },
    { label: 'Agenda una demo', value: 'Calendly / FactuFast', icon: 'event', href: 'https://calendly.com/factufast/demo' }
  ];

  contactForm: FormGroup;
  submitting = false;

  constructor(
    private fb: FormBuilder,
    private snackBar: MatSnackBar,
    private router: Router,
    private http: HttpClient
  ) {
    this.contactForm = this.fb.group({
      fullName: ['', [Validators.required, Validators.minLength(3)]],
      email: ['', [Validators.required, Validators.email]],
      company: ['', Validators.required],
      message: ['', [Validators.required, Validators.minLength(10)]]
    });
  }

  goToLogin(): void {
    this.router.navigate(['/login']);
  }

  scrollTo(sectionId: string): void {
    const el = document.getElementById(sectionId);
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  submitContact(): void {
    if (this.contactForm.invalid || this.submitting) {
      this.contactForm.markAllAsTouched();
      return;
    }

    this.submitting = true;
    const payload = { ...this.contactForm.value, source: 'about-page' };
    this.http.post(`${environment.apiUrl}/contact`, payload).subscribe({
      next: () => {
        this.snackBar.open('¡Gracias! Nos pondremos en contacto pronto.', 'Cerrar', { duration: 3500 });
        this.contactForm.reset();
        this.submitting = false;
      },
      error: (err) => {
        const errorMessage = err?.error?.message || err?.message || 'No fue posible enviar el mensaje. Intenta nuevamente.';
        this.snackBar.open(errorMessage, 'Cerrar', { duration: 3500 });
        this.submitting = false;
      }
    });
  }
}
