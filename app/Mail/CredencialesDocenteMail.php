<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CredencialesDocenteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $username;
    public $password;
    public $name;

    public function __construct($name, $username, $password)
    {
        $this->name = $name;
        $this->username = $username;
        $this->password = $password;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenido al Sistema - Tus Credenciales de Acceso',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "
                <h2>¡Hola, {$this->name}!</h2>
                <p>Te damos la bienvenida al sistema como Docente. Tus datos de acceso son:</p>
                <ul>
                    <li><strong>Usuario:</strong> {$this->username}</li>
                    <li><strong>Contraseña temporal:</strong> {$this->password}</li>
                </ul>
                <p>Por seguridad, te recomendamos cambiar tu contraseña una vez ingreses al sistema.</p>
            ",
        );
    }
}