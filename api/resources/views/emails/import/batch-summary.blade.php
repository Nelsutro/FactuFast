@component('mail::message')
# Resumen de importación de facturas

@if($batch->status === 'failed')
La importación de facturas falló y no pudo completarse. Revisar los detalles a continuación para reintentar.
@elseif($batch->status === 'completed' && $batch->error_count > 0)
La importación finalizó con observaciones. Algunas filas presentaron errores y quedaron fuera del registro.
@else
La importación de facturas finalizó correctamente.
@endif

**Archivo:** {{ $batch->source_filename ?: 'N/A' }}  
**Filas procesadas:** {{ $batch->processed_rows }} / {{ $batch->total_rows }}  
**Registros exitosos:** {{ $batch->success_count }}  
**Registros con errores:** {{ $batch->error_count }}  
**Estado:** {{ strtoupper($batch->status) }}  
**Inició:** {{ optional($batch->started_at)->format('d/m/Y H:i') ?: 'N/A' }}  
**Finalizó:** {{ optional($batch->finished_at)->format('d/m/Y H:i') ?: 'En proceso' }}

@if($batch->error_count > 0)
@component('mail::button', ['url' => config('app.url') . '/api/import-batches/' . $batch->id . '/errors/export'])
Descargar errores
@endcomponent
@endif

Si no solicitaste esta importación, puedes ignorar este correo.

Saludos cordiales,
{{ config('app.name') }}
@endcomponent
