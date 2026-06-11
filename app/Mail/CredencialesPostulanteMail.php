<?php

namespace App\Mail;

use App\Models\Postulante;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CredencialesPostulanteMail extends Mailable
{
    use Queueable, SerializesModels;

    public Postulante $postulante;
    public string $passwordTemporal;

    public function __construct(Postulante $postulante, string $passwordTemporal)
    {
        $this->postulante = $postulante;
        $this->passwordTemporal = $passwordTemporal;
    }

    public function build()
    {
        return $this->subject('Credenciales de acceso - Sistema CUP')
            ->view('emails.credenciales-postulante');
    }
}