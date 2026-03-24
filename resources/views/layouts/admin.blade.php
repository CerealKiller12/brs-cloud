<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Venpi Admin' }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f6f8;
            --panel: rgba(255,255,255,.96);
            --panel-soft: #f7fafc;
            --muted: #66788f;
            --text: #1f3042;
            --accent: #142534;
            --accent-soft: #24435d;
            --line: #d8e1ea;
            --soft: #ecf2f7;
            --success-bg: #edf7ef;
            --success-line: #c9e6cf;
            --success-text: #24523a;
            --warning-bg: #fff6e6;
            --warning-line: #f0d5a0;
            --warning-text: #8a5a17;
            --danger-bg: #fde8e3;
            --danger-line: #f6c9bf;
            --danger-text: #9d4635;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top right, rgba(28, 60, 88, .08), transparent 22%),
                linear-gradient(180deg, #eef3f7 0%, #f4f6f8 30%, #eef3f7 100%);
            color: var(--text);
        }
        a { color: inherit; text-decoration: none; }
        .shell {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            min-height: 100vh;
        }
        .sidebar {
            background: linear-gradient(180deg, #13212d 0%, #162836 100%);
            color: #eef5fb;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .brand,
        .admin-context {
            border-radius: 24px;
            padding: 18px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.05);
        }
        .brand small,
        .admin-context small {
            display: block;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: #c7d4df;
            font-size: 11px;
            margin-bottom: 8px;
        }
        .brand strong {
            display: block;
            font-size: 28px;
            line-height: 1.02;
        }
        .admin-context strong {
            display: block;
            font-size: 18px;
            margin-bottom: 4px;
        }
        .admin-context span {
            color: #c4d1dc;
            font-size: 13px;
            line-height: 1.5;
        }
        .nav-section {
            display: grid;
            gap: 8px;
        }
        .nav-section span {
            font-size: 11px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #9db1c3;
            padding: 0 8px;
        }
        .nav-link {
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(255,255,255,.04);
            border: 1px solid transparent;
            transition: background .18s ease, border-color .18s ease;
        }
        .nav-link:hover { background: rgba(255,255,255,.08); }
        .nav-link.active {
            background: #27445d;
            border-color: rgba(255,255,255,.08);
        }
        .sidebar form { margin-top: auto; }
        .sidebar button {
            width: 100%;
            border: 0;
            border-radius: 14px;
            padding: 12px 16px;
            background: rgba(255,255,255,.1);
            color: #fff;
            cursor: pointer;
        }
        .content {
            padding: 24px;
            display: grid;
            gap: 20px;
            align-content: start;
        }
        .hero, .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 14px 40px rgba(25, 47, 69, .06);
        }
        .hero { padding: 28px; }
        .hero small, .eyebrow {
            display: block;
            font-size: 11px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: #9b6b3d;
            margin-bottom: 10px;
        }
        .card { padding: 22px; }
        .card h3, .card h4, .hero h1, .hero h2 {
            margin: 0;
        }
        .hero h2 { font-size: 30px; margin-bottom: 8px; }
        .hero p, .muted {
            color: var(--muted);
            line-height: 1.55;
        }
        .metrics-grid,
        .grid-4,
        .grid-3,
        .grid-2 {
            display: grid;
            gap: 16px;
        }
        .metrics-grid,
        .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .stat {
            padding: 20px;
            border-radius: 20px;
            border: 1px solid var(--line);
            background: var(--panel-soft);
        }
        .stat-label {
            font-size: 12px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #70849a;
            margin-bottom: 12px;
        }
        .stat-value {
            font-size: 30px;
            font-weight: 700;
            line-height: 1.05;
            margin-bottom: 6px;
        }
        .stat-note { color: var(--muted); font-size: 14px; }
        .surface {
            padding: 16px 18px;
            border-radius: 18px;
            background: var(--panel-soft);
            border: 1px solid var(--line);
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: var(--soft);
            font-size: 13px;
            color: #486175;
        }
        .pill.success {
            background: var(--success-bg);
            color: var(--success-text);
            border-color: var(--success-line);
        }
        .pill.warning {
            background: var(--warning-bg);
            color: var(--warning-text);
            border-color: var(--warning-line);
        }
        .pill.danger {
            background: var(--danger-bg);
            color: var(--danger-text);
            border-color: var(--danger-line);
        }
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
        }
        .toolbar-stack {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            text-align: left;
            padding: 14px 10px;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }
        .table th {
            font-size: 12px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #70849a;
            font-weight: 600;
        }
        .table tr:last-child td {
            border-bottom: 0;
        }
        label {
            display: block;
            font-size: 14px;
            color: #536a80;
            margin-bottom: 8px;
        }
        input,
        select,
        textarea {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid var(--line);
            background: #fff;
            font-size: 16px;
            color: var(--text);
            font: inherit;
        }
        .field { margin-bottom: 16px; }
        .button,
        .button-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 16px;
            padding: 14px 18px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
        }
        .button {
            background: var(--accent);
            color: #fff;
        }
        .button-secondary {
            background: var(--soft);
            color: #3d566d;
            border: 1px solid var(--line);
        }
        .notice {
            padding: 16px 20px;
            border-radius: 18px;
            border: 1px solid var(--line);
        }
        .notice.success {
            background: var(--success-bg);
            border-color: var(--success-line);
            color: var(--success-text);
        }
        .notice.danger {
            background: var(--danger-bg);
            border-color: var(--danger-line);
            color: var(--danger-text);
        }
        .meta-list {
            display: grid;
            gap: 14px;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--line);
        }
        .meta-row:last-child { border-bottom: 0; }
        .pagination { margin-top: 16px; }
        .pagination nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .pagination nav > div:first-child {
            color: var(--muted);
            font-size: 14px;
        }
        .pagination nav > div:last-child {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .pagination a,
        .pagination span[aria-current="page"] > span,
        .pagination nav > div:last-child > span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            min-height: 42px;
            padding: 10px 14px;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: var(--panel-soft);
            color: #486175;
            font-size: 14px;
            line-height: 1;
        }
        .pagination span[aria-current="page"] > span {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }
        @media (min-width: 821px) {
            html, body {
                height: 100%;
                overflow: hidden;
            }
            .shell {
                min-height: 100dvh;
                height: 100dvh;
                overflow: hidden;
            }
            .sidebar {
                min-height: 100dvh;
                height: 100dvh;
                overflow: hidden;
            }
            .content {
                min-height: 0;
                height: 100dvh;
                overflow-y: auto;
            }
        }
        @media (max-width: 1280px) {
            .metrics-grid,
            .grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 1024px) {
            .grid-3 { grid-template-columns: 1fr; }
        }
        @media (max-width: 820px) {
            .shell { grid-template-columns: 1fr; }
            .metrics-grid,
            .grid-4,
            .grid-3,
            .grid-2 { grid-template-columns: 1fr; }
            .toolbar { flex-direction: column; align-items: flex-start; }
        }
    </style>
    @stack('head')
</head>
<body>
    <div class="shell">
        <aside class="sidebar">
            <div class="brand">
                <small>Venpi Platform</small>
                <strong>Admin Console</strong>
            </div>

            <div class="admin-context">
                <small>Sesion actual</small>
                <strong>{{ auth()->user()->name }}</strong>
                <span>{{ auth()->user()->email }}</span>
            </div>

            <div class="nav-section">
                <span>Vista global</span>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Resumen</a>
                <a class="nav-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}" href="{{ route('admin.clients.index') }}">Clientes</a>
                <a class="nav-link {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}" href="{{ route('admin.subscriptions.index') }}">Subscripciones</a>
            </div>

            @if (auth()->user()->tenant_id)
                <div class="nav-section">
                    <span>Cliente</span>
                    <a class="nav-link" href="{{ route('dashboard') }}">Abrir portal cloud</a>
                </div>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Salir</button>
            </form>
        </aside>

        <main class="content">
            @if (session('status'))
                <section class="notice success">{{ session('status') }}</section>
            @endif

            @if ($errors->any())
                <section class="notice danger">{{ $errors->first() }}</section>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
