<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'BRS Cloud' }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f3f6f9;
            --panel: rgba(255,255,255,.94);
            --panel-soft: #f8fbfd;
            --muted: #6a7a8f;
            --text: #213043;
            --accent: #1f3244;
            --line: #d8e0e8;
            --soft: #edf3f8;
            --success-bg: #edf7ef;
            --success-line: #c9e6cf;
            --success-text: #24523a;
            --danger-bg: #fde8e3;
            --danger-line: #f6c9bf;
            --danger-text: #9d4635;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: linear-gradient(180deg, #eef4f9 0%, #f3f6f9 28%, #eef3f7 100%);
            color: var(--text);
        }
        a { color: inherit; text-decoration: none; }
        code {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 12px;
            background: #f6f9fb;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 3px 8px;
            display: inline-block;
        }
        .shell {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            min-height: 100vh;
        }
        .sidebar {
            background: #162330;
            color: #eef4f8;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .brand {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 22px;
            padding: 18px;
        }
        .brand small {
            display: block;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #c8d4df;
            font-size: 11px;
            margin-bottom: 6px;
        }
        .brand strong {
            display: block;
            font-size: 28px;
            line-height: 1.05;
        }
        .store-context {
            display: grid;
            gap: 12px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 20px;
            padding: 14px;
        }
        .store-context label {
            color: #b5c5d4;
            font-size: 12px;
            margin: 0;
        }
        .store-context select {
            background: rgba(255,255,255,.08);
            color: #eef4f8;
            border-color: rgba(255,255,255,.08);
            padding: 12px 14px;
            font-size: 14px;
        }
        .store-meta {
            display: grid;
            gap: 6px;
            color: #d3dee8;
            font-size: 13px;
        }
        .store-meta strong {
            font-size: 18px;
            color: #fff;
        }
        .nav-section { display: grid; gap: 8px; }
        .nav-section span {
            font-size: 11px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #9eb1c1;
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
            background: #29475f;
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
                align-content: start;
            }
        }
        .hero, .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 14px 40px rgba(30,55,90,.06);
            backdrop-filter: blur(12px);
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
        h1, h2, h3, h4 { margin: 0; }
        h1 { font-size: 36px; line-height: 1.05; margin-bottom: 8px; }
        h2 { font-size: 28px; }
        h3 { font-size: 22px; }
        h4 { font-size: 18px; }
        p { margin: 0; color: var(--muted); line-height: 1.55; }
        small.inline-note {
            display: inline;
            color: var(--muted);
            font-size: 13px;
            letter-spacing: 0;
            text-transform: none;
        }
        .grid { display: grid; gap: 18px; }
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
        .card { padding: 22px; }
        .surface {
            padding: 16px 18px;
            border-radius: 18px;
            background: var(--panel-soft);
            border: 1px solid var(--line);
        }
        .surface h4 { margin-bottom: 6px; }
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
            background: #eef7ef;
            color: #24523a;
            border-color: #c9e6cf;
        }
        .pill.danger {
            background: #fff0ec;
            color: #9d4635;
            border-color: #f6c9bf;
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
        .empty {
            padding: 22px;
            border-radius: 18px;
            border: 1px dashed var(--line);
            color: var(--muted);
            background: rgba(237,243,248,.55);
        }
        .login-wrap {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .login-card {
            width: min(560px, 100%);
            background: rgba(255,255,255,.95);
            border: 1px solid var(--line);
            border-radius: 28px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(30,55,90,.08);
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
        textarea { min-height: 120px; resize: vertical; }
        .field { margin-bottom: 16px; }
        .button,
        .button-secondary,
        .button-danger {
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
        .button-danger {
            background: #fff1ec;
            color: #9d4635;
            border: 1px solid #f6c9bf;
        }
        .error {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 14px;
            background: var(--danger-bg);
            color: var(--danger-text);
            border: 1px solid var(--danger-line);
        }
        .notice.success {
            padding: 16px 20px;
            background: var(--success-bg);
            border: 1px solid var(--success-line);
            color: var(--success-text);
            border-radius: 18px;
        }
        .notice.danger {
            padding: 16px 20px;
            background: var(--danger-bg);
            border: 1px solid var(--danger-line);
            color: var(--danger-text);
            border-radius: 18px;
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
        .muted { color: var(--muted); }
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
        .pagination a:hover {
            background: #eaf1f6;
        }
        .pagination span[aria-current="page"] > span {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }
        .pagination svg {
            width: 16px;
            height: 16px;
            display: block;
            flex: none;
        }
        .stack { display: grid; gap: 12px; }
        .row-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }
        @media (max-width: 1280px) {
            .metrics-grid,
            .grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 1100px) {
            .grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 820px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar { padding-bottom: 12px; }
            .metrics-grid,
            .grid-4,
            .grid-3,
            .grid-2 { grid-template-columns: 1fr; }
            .toolbar,
            .row-actions { flex-direction: column; align-items: flex-start; }
        }
    </style>
    @stack('head')
</head>
<body>
@auth
    <div class="shell">
        <aside class="sidebar">
            <div class="brand">
                <small>Tu operacion en la nube</small>
                <strong>{{ auth()->user()->tenant?->name ?? 'BRS Cloud' }}</strong>
            </div>

            @if (!empty($cloudActiveStore) && !empty($cloudAvailableStores))
                <div class="store-context">
                    <div class="store-meta">
                        <span>Sucursal activa</span>
                        <strong>{{ $cloudActiveStore->name }}</strong>
                        <span>{{ $cloudActiveStore->code }} · Catalogo v{{ $cloudActiveStore->catalog_version }}</span>
                    </div>
                    <form method="POST" action="{{ route('context.store') }}">
                        @csrf
                        <label for="sidebar-store-selector">Cambiar sucursal</label>
                        <select id="sidebar-store-selector" name="store_id" onchange="this.form.submit()">
                            @foreach ($cloudAvailableStores as $availableStore)
                                <option value="{{ $availableStore->id }}" {{ (int) $availableStore->id === (int) $cloudActiveStore->id ? 'selected' : '' }}>
                                    {{ $availableStore->name }} · {{ $availableStore->code }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif

            <div class="nav-section">
                <span>Operacion</span>
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Inicio</a>
                <a class="nav-link {{ request()->routeIs('stores.index') ? 'active' : '' }}" href="{{ route('stores.index') }}">Sucursales</a>
                <a class="nav-link {{ request()->routeIs('devices.index') ? 'active' : '' }}" href="{{ route('devices.index') }}">Cajas</a>
                <a class="nav-link {{ request()->routeIs('catalog.index') ? 'active' : '' }}" href="{{ route('catalog.index') }}">Catalogo compartido</a>
                <a class="nav-link {{ request()->routeIs('sync.index') ? 'active' : '' }}" href="{{ route('sync.index') }}">Actividad</a>
                <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">Cuenta</a>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Salir de la cuenta</button>
            </form>
        </aside>
        <main class="content">
            @yield('content')
        </main>
    </div>
@else
    @yield('content')
@endauth
@stack('scripts')
</body>
</html>
