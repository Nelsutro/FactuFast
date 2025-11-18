import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatTabsModule } from '@angular/material/tabs';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { FormsModule } from '@angular/forms';

interface FAQ {
  question: string;
  answer: string;
  category: string;
}

interface Guide {
  title: string;
  description: string;
  steps: string[];
  icon: string;
}

@Component({
  selector: 'app-help',
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatTabsModule,
    MatExpansionModule,
    MatButtonModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    FormsModule
  ],
  templateUrl: './help.component.html',
  styleUrl: './help.component.css'
})
export class HelpComponent {
  searchTerm = '';
  
  faqs: FAQ[] = [
    {
      question: '¿Cómo crear mi primera factura?',
      answer: '<strong>Para crear una factura:</strong><br>1. Ve a la sección "Facturas"<br>2. Haz clic en "Nueva Factura"<br>3. Selecciona el cliente<br>4. Agrega productos o servicios<br>5. Revisa los totales<br>6. Guarda y envía',
      category: 'Facturación'
    },
    {
      question: '¿Cómo agregar un nuevo cliente?',
      answer: '<strong>Para agregar un cliente:</strong><br>1. Accede a "Clientes"<br>2. Clic en "Nuevo Cliente"<br>3. Completa la información básica<br>4. Agrega datos de contacto<br>5. Guarda la información',
      category: 'Clientes'
    },
    {
      question: '¿Puedo personalizar las plantillas de facturas?',
      answer: '<strong>Sí, puedes personalizar:</strong><br>• Logo de la empresa<br>• Colores y fuentes<br>• Campos adicionales<br>• Términos y condiciones<br>• Información de contacto',
      category: 'Personalización'
    },
    {
      question: '¿Cómo configurar pagos automatizados?',
      answer: '<strong>Para configurar pagos:</strong><br>1. Ve a "Configuración"<br>2. Selecciona "Pagos"<br>3. Conecta tu cuenta bancaria<br>4. Define reglas de cobro<br>5. Activa notificaciones',
      category: 'Pagos'
    },
    {
      question: '¿Cómo generar reportes financieros?',
      answer: '<strong>Para generar reportes:</strong><br>1. Accede a "Reportes"<br>2. Selecciona el tipo de reporte<br>3. Define el período<br>4. Aplica filtros si necesario<br>5. Exporta en PDF o Excel',
      category: 'Reportes'
    },
    {
      question: '¿FactuFast cumple con las regulaciones fiscales?',
      answer: '<strong>Sí, FactuFast cumple con:</strong><br>• Facturación electrónica<br>• Reportes fiscales<br>• Retenciones automáticas<br>• Integración con SAT<br>• Timbrado fiscal',
      category: 'Legal'
    },
    {
      question: '¿Cómo hacer respaldo de mi información?',
      answer: '<strong>Los respaldos son automáticos:</strong><br>• Backup diario en la nube<br>• Cifrado de extremo a extremo<br>• Recuperación de datos<br>• Exportación manual disponible<br>• Múltiples centros de datos',
      category: 'Seguridad'
    },
    {
      question: '¿Puedo usar FactuFast en dispositivos móviles?',
      answer: '<strong>Sí, está optimizado para:</strong><br>• Navegadores móviles<br>• Tablets y smartphones<br>• Interfaz responsiva<br>• Funciones principales disponibles<br>• Sin necesidad de app',
      category: 'Accesibilidad'
    }
  ];

  guides: Guide[] = [
    {
      title: 'Primeros Pasos con FactuFast',
      description: 'Configura tu cuenta y crea tu primera factura',
      icon: 'flag',
      steps: [
        'Completa la información de tu empresa',
        'Agrega tu primer cliente',
        'Configura productos o servicios',
        'Crea tu primera factura',
        'Envía la factura al cliente'
      ]
    },
    {
      title: 'Gestión de Clientes',
      description: 'Aprende a administrar tu cartera de clientes',
      icon: 'people',
      steps: [
        'Importar clientes desde Excel/CSV',
        'Organizar clientes por categorías',
        'Configurar términos de pago',
        'Gestionar información de contacto',
        'Establecer descuentos especiales'
      ]
    },
    {
      title: 'Facturación Avanzada',
      description: 'Funciones avanzadas de facturación',
      icon: 'receipt',
      steps: [
        'Crear facturas recurrentes',
        'Aplicar descuentos y promociones',
        'Manejar diferentes monedas',
        'Configurar impuestos múltiples',
        'Gestionar notas de crédito'
      ]
    },
    {
      title: 'Reportes y Análisis',
      description: 'Genera reportes detallados de tu negocio',
      icon: 'analytics',
      steps: [
        'Acceder al panel de reportes',
        'Seleccionar métricas importantes',
        'Configurar períodos de análisis',
        'Interpretar gráficos y tendencias',
        'Exportar datos para análisis externo'
      ]
    }
  ];

  filteredFAQs: FAQ[] = [...this.faqs];

  ngOnInit() {
    this.filterFAQs();
  }

  filterFAQs() {
    if (!this.searchTerm.trim()) {
      this.filteredFAQs = [...this.faqs];
    } else {
      const term = this.searchTerm.toLowerCase();
      this.filteredFAQs = this.faqs.filter(faq =>
        faq.question.toLowerCase().includes(term) ||
        faq.answer.toLowerCase().includes(term) ||
        faq.category.toLowerCase().includes(term)
      );
    }
  }

  openEmail() {
    window.open('mailto:soporte@factufact.com?subject=Consulta%20sobre%20FactuFast');
  }

  makeCall() {
    window.open('tel:+15551234567');
  }

  openChat() {
    // Implementar integración de chat en vivo
    alert('Chat en vivo próximamente disponible');
  }
}