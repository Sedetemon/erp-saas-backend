<?php

namespace App\Mail;

use App\Models\Landlord\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $adminEmail,
        public string $temporaryPassword
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Bienvenue sur votre espace {$this->tenant->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenant.welcome',
            with: [
                'tenantName' => $this->tenant->name,
                'loginUrl'   => "https://{$this->tenant->slug}.votre-domaine.com/login",
                'email'      => $this->adminEmail,
                'password'   => $this->temporaryPassword,
            ]
        );
    }
}
