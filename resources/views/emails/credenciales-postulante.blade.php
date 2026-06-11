<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Credenciales Sistema CUP</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">

    <h2>Inscripción aceptada - Sistema CUP</h2>

    <p>
        Estimado/a
        <strong>{{ $postulante->user->name }} {{ $postulante->user->apellido }}</strong>,
    </p>

    <p>
        Su inscripción al Curso Preuniversitario fue aceptada.
        A continuación, se detallan sus credenciales de acceso al sistema:
    </p>

    <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <p><strong>Usuario:</strong> {{ $postulante->user->username }}</p>
        <p><strong>Contraseña temporal:</strong> {{ $passwordTemporal }}</p>
    </div>

    <p>
        Puede ingresar al sistema desde el siguiente enlace:
    </p>

    <p>
        <a href="{{ url('/login') }}" style="background:#2563eb; color:white; padding:10px 15px; text-decoration:none; border-radius:6px;">
            Iniciar sesión
        </a>
    </p>

    <p>
        Se recomienda cambiar la contraseña después de ingresar al sistema.
    </p>

    <hr>

    <p style="font-size: 13px; color: #6b7280;">
        Este mensaje fue enviado automáticamente por el Sistema CUP.
    </p>

</body>
</html>