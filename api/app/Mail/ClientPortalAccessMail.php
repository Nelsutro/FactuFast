<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientPortalAccessMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Client $client,
        public string $accessToken,
        public ?string $customMessage = null
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('mail.from.address', 'pagos@factufast.cl'),
            subject: 'Acceso a tu Portal de Facturas - FactuFast',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.client-portal-access',
            with: [
                'client' => $this->client,
                'accessToken' => $this->accessToken,
                'customMessage' => $this->customMessage,
                'accessUrl' => $this->getAccessUrl(),
                'companyName' => $this->client->company->name ?? 'FactuFast',
                'expiresAt' => $this->client->access_token_expires_at,
            ],
        );
    }

    /**
     * Get the access URL for the client portal
     */
    private function getAccessUrl(): string
    {
        $baseUrl = config('app.frontend_url', 'http://localhost:4200');
        return $baseUrl . '/client-portal/access?token=' . $this->accessToken . '&email=' . urlencode($this->client->email);
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}