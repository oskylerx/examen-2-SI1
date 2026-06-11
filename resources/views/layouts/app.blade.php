<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistema CUP')</title>

    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            margin: 0;
            background: #f1f5f9;
            color: #0f172a;
        }

        .app {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: #0f172a;
            color: #ffffff;
            padding: 24px 18px;
        }

        .sidebar h2 {
            margin: 0;
            font-size: 22px;
        }

        .sidebar p {
            margin-top: 6px;
            color: #cbd5e1;
            font-size: 13px;
        }

        .menu {
            margin-top: 28px;
        }

        .menu-title {
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .menu a {
            display: block;
            color: #e2e8f0;
            text-decoration: none;
            padding: 11px 12px;
            border-radius: 9px;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .menu a:hover {
            background: #1e293b;
        }

        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: #ffffff;
            padding: 18px 28px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar h1 {
            margin: 0;
            font-size: 22px;
        }

        .user-box {
            text-align: right;
            font-size: 14px;
        }

        .user-box strong {
            display: block;
            color: #0f172a;
        }

        .user-box span {
            color: #64748b;
            font-size: 13px;
        }

        .content {
            padding: 28px;
        }

        .card {
            background: #ffffff;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            margin-bottom: 20px;
        }

        .card h2 {
            margin-top: 0;
            font-size: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .stat {
            background: #ffffff;
            padding: 20px;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .stat span {
            color: #64748b;
            font-size: 14px;
        }

        .stat strong {
            display: block;
            margin-top: 8px;
            font-size: 28px;
            color: #2563eb;
        }

        .logout-btn {
            margin-top: 24px;
            width: 100%;
            padding: 11px;
            border: none;
            border-radius: 9px;
            background: #dc2626;
            color: #ffffff;
            cursor: pointer;
            font-weight: bold;
        }

        .logout-btn:hover {
            background: #b91c1c;
        }

        @media (max-width: 900px) {
            .app {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
            }

            .grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 560px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .topbar {
                align-items: flex-start;
                gap: 12px;
                flex-direction: column;
            }

            .user-box {
                text-align: left;
            }
        }
    </style>
</head>
<body>

    <div class="app">
        <aside class="sidebar">
            <h2>Sistema CUP</h2>
            <p>Examen de Suficiencia Académica</p>

            <nav class="menu">
                <div class="menu-title">Menú principal</div>

                @if(auth()->user()->rol->nombre === 'Administrador')
                    <a href="{{ route('dashboard.admin') }}">Dashboard</a>
                    <a href="{{ route('postulantes.index') }}">Gestionar postulantes</a>
                    <a href="#">Gestionar docentes</a>
                    <a href="#">Asignación académica</a>
                    <a href="#">Reportes</a>
                @endif

                @if(auth()->user()->rol->nombre === 'Coordinador')
                    <a href="{{ route('dashboard.coordinador') }}">Dashboard</a>
                    <a href="#">Grupos habilitados</a>
                    <a href="#">Asignación docente</a>
                    <a href="#">Reportes académicos</a>
                @endif

                @if(auth()->user()->rol->nombre === 'Docente')
                    <a href="{{ route('dashboard.docente') }}">Dashboard</a>
                    <a href="#">Mis grupos</a>
                    <a href="#">Registrar notas</a>
                    <a href="#">Calificaciones</a>
                @endif

                @if(auth()->user()->rol->nombre === 'Postulante')
                    <a href="{{ route('dashboard.postulante') }}">Dashboard</a>
                    <a href="#">Mi inscripción</a>
                    <a href="#">Mis documentos</a>
                    <a href="#">Mis resultados</a>
                @endif
            </nav>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn">
                    Cerrar sesión
                </button>
            </form>
        </aside>

        <main class="main">
            <header class="topbar">
                <h1>@yield('page-title')</h1>

                <div class="user-box">
                    <strong>{{ auth()->user()->name }} {{ auth()->user()->apellido }}</strong>
                    <span>{{ auth()->user()->rol->nombre }}</span>
                </div>
            </header>

            <section class="content">
                @yield('content')
            </section>
        </main>
    </div>

</body>
</html>