<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Preinscripción CUP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @livewireStyles

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #1f2937;
        }

        .hero {
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            color: white;
            padding: 60px 20px;
            text-align: center;
        }

        .hero h1 {
            margin: 0 0 15px;
            font-size: 36px;
        }

        .hero p {
            max-width: 700px;
            margin: 0 auto;
            font-size: 18px;
            line-height: 1.5;
        }

        .container {
            max-width: 950px;
            margin: -30px auto 40px;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-box {
            background: #eff6ff;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #bfdbfe;
        }

        .info-box strong {
            display: block;
            font-size: 22px;
            color: #1d4ed8;
        }

        .info-box span {
            font-size: 14px;
            color: #374151;
        }

        .btn-primary {
            display: inline-block;
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .footer {
            text-align: center;
            padding: 25px;
            color: #6b7280;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr 1fr;
            }

            .hero h1 {
                font-size: 28px;
            }
        }

        @media (max-width: 480px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <section class="hero">
        <h1>Preinscripción CUP</h1>
        <p>
            Bienvenido al sistema de preinscripción del Curso Preuniversitario.
            Complete sus datos, realice el pago mediante QR y suba sus documentos requeridos.
        </p>
    </section>

    <main class="container">

        <div class="card">
            <h2>Información del proceso</h2>

            <div class="info-grid">
                <div class="info-box">
                    <strong>700 Bs</strong>
                    <span>Costo CUP</span>
                </div>

                <div class="info-box">
                    <strong>4</strong>
                    <span>Materias</span>
                </div>

                <div class="info-box">
                    <strong>3</strong>
                    <span>Evaluaciones</span>
                </div>

                <div class="info-box">
                    <strong>60</strong>
                    <span>Nota mínima</span>
                </div>
            </div>

            <p>
                Las materias del CUP son Matemática, Física, Inglés y Computación.
                Cada materia cuenta con P1, P2 y EF. Si el promedio final de una materia
                es menor a 60, el postulante queda reprobado.
            </p>
        </div>

        <div class="card">
            <livewire:preinscripcion-postulante />
        </div>

    </main>

    <footer class="footer">
        Sistema CUP - Examen de Suficiencia Académica
    </footer>

    @livewireScripts

</body>
</html>