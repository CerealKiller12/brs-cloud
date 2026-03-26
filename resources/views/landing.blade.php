<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Venpi Cloud | Punto de venta sincronizado</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:600,700|ibm-plex-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root {
            color-scheme: light;
            --bg: #fffaf3;
            --bg-soft: #f6efe6;
            --bg-strong: #efe3d4;
            --panel: rgba(255, 252, 247, 0.86);
            --panel-strong: rgba(255, 248, 239, 0.96);
            --line: rgba(91, 63, 32, 0.14);
            --text: #231910;
            --muted: #6f5846;
            --accent: #8a6343;
            --accent-strong: #5f3d24;
            --accent-soft: #dcb58f;
            --shadow: 0 22px 60px rgba(95, 61, 36, 0.10);
            --success: #2f6b48;
        }
        * { box-sizing: border-box; }
        html {
            scroll-behavior: smooth;
            background: var(--bg-soft);
        }
        body {
            margin: 0;
            font-family: "IBM Plex Sans", "Aptos", "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(220, 181, 143, 0.34) 0%, transparent 34%),
                radial-gradient(circle at 82% 18%, rgba(138, 99, 67, 0.13) 0%, transparent 24%),
                linear-gradient(180deg, var(--bg) 0%, var(--bg-soft) 42%, var(--bg-strong) 100%);
        }
        a {
            color: inherit;
            text-decoration: none;
        }
        .page-shell {
            width: min(1240px, calc(100vw - 40px));
            margin: 0 auto;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            padding: 22px 0 14px;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            color: #fff8ef;
            background: linear-gradient(160deg, #8a6343 0%, #c58a53 100%);
            box-shadow: 0 18px 30px rgba(138, 99, 67, 0.22);
            font-family: "Fraunces", Georgia, serif;
            font-size: 1.15rem;
        }
        .brand-copy {
            display: grid;
            gap: 2px;
        }
        .brand-copy strong {
            font-size: 1rem;
        }
        .brand-copy span {
            font-size: 0.84rem;
            color: var(--muted);
        }
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .button,
        .button-secondary,
        .button-ghost {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 48px;
            padding: 0 18px;
            border-radius: 999px;
            font-size: 0.98rem;
            font-weight: 600;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
        }
        .button {
            color: #fff9f1;
            background: linear-gradient(135deg, #8a6343 0%, #b37b4c 100%);
            box-shadow: 0 16px 32px rgba(138, 99, 67, 0.24);
        }
        .button:hover,
        .button-secondary:hover,
        .button-ghost:hover {
            transform: translateY(-1px);
        }
        .button-secondary {
            border: 1px solid rgba(95, 61, 36, 0.14);
            background: rgba(255, 255, 255, 0.56);
            color: var(--text);
        }
        .button-ghost {
            color: var(--accent-strong);
            background: transparent;
        }
        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(360px, .92fr);
            gap: 24px;
            align-items: stretch;
            padding: 18px 0 42px;
        }
        .hero-copy,
        .hero-stage {
            border: 1px solid var(--line);
            border-radius: 34px;
            background: var(--panel);
            backdrop-filter: blur(12px);
            box-shadow: var(--shadow);
        }
        .hero-copy {
            padding: 42px;
            display: grid;
            gap: 22px;
            align-content: start;
        }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: fit-content;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255, 247, 236, 0.92);
            border: 1px solid rgba(138, 99, 67, 0.12);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--accent);
        }
        .hero h1 {
            margin: 0;
            font-family: "Fraunces", Georgia, serif;
            font-size: clamp(3rem, 6vw, 5.25rem);
            line-height: 0.94;
            letter-spacing: -0.04em;
            max-width: 10.5ch;
        }
        .hero p {
            margin: 0;
            font-size: 1.12rem;
            line-height: 1.7;
            color: var(--muted);
            max-width: 58ch;
        }
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .hero-points {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .hero-point {
            padding: 16px 18px;
            border-radius: 20px;
            background: var(--panel-strong);
            border: 1px solid rgba(95, 61, 36, 0.10);
        }
        .hero-point strong {
            display: block;
            margin-bottom: 6px;
            font-size: 1rem;
        }
        .hero-point span {
            color: var(--muted);
            line-height: 1.55;
            font-size: 0.95rem;
        }
        .hero-stage {
            padding: 22px;
            display: grid;
            gap: 16px;
            align-content: start;
            overflow: hidden;
            position: relative;
        }
        .hero-stage::before,
        .hero-stage::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            filter: blur(24px);
            opacity: 0.52;
            pointer-events: none;
        }
        .hero-stage::before {
            width: 180px;
            height: 180px;
            top: -40px;
            right: -20px;
            background: rgba(220, 181, 143, 0.48);
        }
        .hero-stage::after {
            width: 140px;
            height: 140px;
            left: -20px;
            bottom: 40px;
            background: rgba(138, 99, 67, 0.18);
        }
        .snapshot-card {
            position: relative;
            z-index: 1;
            padding: 22px;
            border-radius: 28px;
            border: 1px solid rgba(95, 61, 36, 0.10);
            background: rgba(255, 251, 244, 0.92);
        }
        .snapshot-card h2 {
            margin: 0 0 6px;
            font-size: 1.22rem;
        }
        .snapshot-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.55;
        }
        .snapshot-stats {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }
        .snapshot-stat {
            padding: 14px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(95, 61, 36, 0.08);
        }
        .snapshot-stat small {
            display: block;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 0.68rem;
            margin-bottom: 8px;
        }
        .snapshot-stat strong {
            font-size: 1.6rem;
            line-height: 1;
        }
        .activity-tape,
        .flow-row {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 12px;
        }
        .activity-item,
        .flow-card {
            padding: 16px 18px;
            border-radius: 22px;
            border: 1px solid rgba(95, 61, 36, 0.10);
            background: rgba(255, 255, 255, 0.82);
        }
        .activity-item {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: start;
        }
        .activity-item strong,
        .flow-card strong {
            display: block;
            margin-bottom: 5px;
            font-size: 1rem;
        }
        .activity-item span,
        .flow-card span {
            color: var(--muted);
            line-height: 1.5;
            font-size: 0.94rem;
        }
        .activity-item em {
            font-style: normal;
            color: var(--accent);
            font-weight: 600;
            white-space: nowrap;
        }
        .flow-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .section {
            padding: 28px 0 12px;
        }
        .section-head {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: end;
            margin-bottom: 22px;
        }
        .section-head h2 {
            margin: 0;
            font-family: "Fraunces", Georgia, serif;
            font-size: clamp(2rem, 4vw, 3.1rem);
            line-height: 0.98;
            letter-spacing: -0.03em;
        }
        .section-head p {
            max-width: 58ch;
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }
        .feature-card {
            padding: 24px;
            border-radius: 28px;
            border: 1px solid var(--line);
            background: rgba(255, 252, 247, 0.78);
            box-shadow: 0 14px 34px rgba(95, 61, 36, 0.06);
        }
        .feature-card strong {
            display: block;
            margin: 14px 0 10px;
            font-size: 1.18rem;
        }
        .feature-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.65;
        }
        .feature-kicker {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, rgba(138, 99, 67, 0.16), rgba(220, 181, 143, 0.34));
            color: var(--accent-strong);
            font-family: "Fraunces", Georgia, serif;
            font-size: 1.08rem;
            font-weight: 700;
        }
        .split-band {
            display: grid;
            grid-template-columns: minmax(0, 1.04fr) minmax(0, .96fr);
            gap: 18px;
            margin-top: 10px;
        }
        .story-card,
        .checklist-card {
            padding: 28px;
            border-radius: 30px;
            border: 1px solid var(--line);
            background: var(--panel);
            box-shadow: 0 16px 40px rgba(95, 61, 36, 0.08);
        }
        .story-card h3,
        .checklist-card h3 {
            margin: 0 0 10px;
            font-size: 1.6rem;
        }
        .story-card p,
        .checklist-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
        }
        .story-grid {
            margin-top: 20px;
            display: grid;
            gap: 14px;
        }
        .story-line {
            display: flex;
            gap: 12px;
            align-items: start;
        }
        .story-index {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: rgba(138, 99, 67, 0.10);
            color: var(--accent-strong);
            font-weight: 700;
        }
        .checklist {
            margin-top: 18px;
            display: grid;
            gap: 12px;
        }
        .checklist-item {
            display: flex;
            gap: 12px;
            align-items: start;
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.74);
            border: 1px solid rgba(95, 61, 36, 0.08);
        }
        .checklist-item i {
            width: 24px;
            height: 24px;
            flex: none;
            border-radius: 999px;
            background: rgba(47, 107, 72, 0.12);
            color: var(--success);
            display: grid;
            place-items: center;
            font-style: normal;
            font-weight: 700;
            font-size: 0.84rem;
            margin-top: 2px;
        }
        .checklist-item strong {
            display: block;
            margin-bottom: 4px;
        }
        .checklist-item span {
            color: var(--muted);
            line-height: 1.55;
            font-size: 0.95rem;
        }
        .cta-band {
            margin: 36px 0 54px;
            padding: 34px;
            border-radius: 34px;
            background:
                radial-gradient(circle at top right, rgba(220, 181, 143, 0.22) 0%, transparent 28%),
                linear-gradient(135deg, rgba(255, 249, 241, 0.94) 0%, rgba(249, 242, 235, 0.98) 100%);
            border: 1px solid var(--line);
            box-shadow: 0 20px 46px rgba(95, 61, 36, 0.09);
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: center;
        }
        .cta-band h2 {
            margin: 0 0 8px;
            font-family: "Fraunces", Georgia, serif;
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 0.98;
        }
        .cta-band p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
            max-width: 62ch;
        }
        .cta-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .micro-note {
            color: var(--muted);
            font-size: 0.92rem;
        }
        @media (max-width: 1080px) {
            .hero,
            .split-band,
            .cta-band {
                grid-template-columns: 1fr;
            }
            .feature-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .cta-actions {
                justify-content: flex-start;
            }
        }
        @media (max-width: 760px) {
            .page-shell {
                width: min(100vw - 24px, 100%);
            }
            .topbar {
                flex-direction: column;
                align-items: stretch;
            }
            .topbar-actions {
                width: 100%;
                justify-content: stretch;
                flex-wrap: wrap;
            }
            .topbar-actions a {
                flex: 1 1 180px;
            }
            .hero-copy,
            .hero-stage,
            .story-card,
            .checklist-card,
            .cta-band {
                padding: 24px;
                border-radius: 26px;
            }
            .hero-points,
            .snapshot-stats,
            .flow-row,
            .feature-grid {
                grid-template-columns: 1fr;
            }
            .section-head {
                align-items: start;
                flex-direction: column;
            }
            .hero h1 {
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <header class="topbar">
            <a class="brand" href="{{ url('/') }}">
                <span class="brand-mark">V</span>
                <span class="brand-copy">
                    <strong>Venpi Cloud</strong>
                    <span>Punto de venta offline-first para operar y crecer</span>
                </span>
            </a>
            <div class="topbar-actions">
                <a class="button-ghost" href="{{ route('login') }}">Iniciar sesion</a>
                <a class="button-secondary" href="{{ route('register') }}">Crear cuenta</a>
                <a class="button" href="{{ route('register') }}">Probar Venpi</a>
            </div>
        </header>

        <section class="hero">
            <div class="hero-copy">
                <span class="eyebrow">Offline-first · iPad · Web local · Nube</span>
                <h1>Vende rapido. Sincroniza claro. Opera sin drama.</h1>
                <p>Venpi junta cobro, inventario, cajas, sucursales y nube en una sola operacion. Cobra desde iPad o web local, trabaja aunque el internet falle y sincroniza catalogo, stock y ventas cuando la red vuelve.</p>
                <div class="hero-actions">
                    <a class="button" href="{{ route('register') }}">Crear cuenta</a>
                    <a class="button-secondary" href="{{ route('login') }}">Entrar a Venpi Cloud</a>
                </div>
                <div class="hero-points">
                    <article class="hero-point">
                        <strong>Cobro pensado para caja real</strong>
                        <span>Flujo agil en iPad, catalogo vivo, venta rapida y ticket listo para operar en mostrador.</span>
                    </article>
                    <article class="hero-point">
                        <strong>Catalogo compartido</strong>
                        <span>Productos, stock y precios viajan entre cajas y nube con sincronizacion bidireccional.</span>
                    </article>
                    <article class="hero-point">
                        <strong>Sucursales y cajas</strong>
                        <span>Controla dispositivos, actividad, resumen visual y salud operativa por sucursal.</span>
                    </article>
                    <article class="hero-point">
                        <strong>Hecho para seguir vendiendo</strong>
                        <span>Lo local manda primero; la nube completa la foto sin detener el punto de venta.</span>
                    </article>
                </div>
            </div>

            <div class="hero-stage">
                <section class="snapshot-card">
                    <h2>Una vista clara del negocio</h2>
                    <p>Resumen de sucursal, actividad reciente, stock compartido y cajas conectadas sin convertirlo en un panel tecnico.</p>
                    <div class="snapshot-stats">
                        <div class="snapshot-stat">
                            <small>Hoy</small>
                            <strong>$18,420</strong>
                        </div>
                        <div class="snapshot-stat">
                            <small>Tickets</small>
                            <strong>146</strong>
                        </div>
                        <div class="snapshot-stat">
                            <small>Cajas</small>
                            <strong>4 en linea</strong>
                        </div>
                    </div>
                </section>
                <div class="activity-tape">
                    <article class="activity-item">
                        <div>
                            <strong>Venta registrada</strong>
                            <span>Caja iPad · Ticket M-IPAD-7021 · 3 articulos</span>
                        </div>
                        <em>Hace 2 min</em>
                    </article>
                    <article class="activity-item">
                        <div>
                            <strong>Stock ajustado</strong>
                            <span>Caja local · Pepsi 355ml · +12 unidades</span>
                        </div>
                        <em>Aplicado</em>
                    </article>
                </div>
                <div class="flow-row">
                    <article class="flow-card">
                        <strong>Catalogo que si se siente vivo</strong>
                        <span>Cuando cambia el stock en una caja, las otras lo reciben sin depender de refrescos manuales.</span>
                    </article>
                    <article class="flow-card">
                        <strong>Resumen cloud util</strong>
                        <span>Ventas, medios de cobro y productos mas movidos con una lectura clara por sucursal.</span>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" id="capacidades">
            <div class="section-head">
                <div>
                    <h2>Todo lo critico en una misma operacion</h2>
                </div>
                <p>No es solo una caja. Venpi conecta lo que pasa en mostrador con lo que necesita ver el negocio: ventas, inventario, sucursales, actividad y control real de dispositivos.</p>
            </div>
            <div class="feature-grid">
                <article class="feature-card">
                    <div class="feature-kicker">01</div>
                    <strong>Cobro rapido y enfocado</strong>
                    <p>Catalogo vivo en la seccion de cobro, flujo tactil para iPad, carrito agil y ticket listo para salir con menos friccion.</p>
                </article>
                <article class="feature-card">
                    <div class="feature-kicker">02</div>
                    <strong>Inventario compartido</strong>
                    <p>Cambios de precio, stock y nombre viajan entre cajas y nube. El catalogo deja de vivir en hojas sueltas o mensajes.</p>
                </article>
                <article class="feature-card">
                    <div class="feature-kicker">03</div>
                    <strong>Sincronizacion en dos vias</strong>
                    <p>Lo que cambias localmente se publica. Lo que se mueve en cloud baja a las cajas. El sistema respeta primero lo operativo.</p>
                </article>
                <article class="feature-card">
                    <div class="feature-kicker">04</div>
                    <strong>Sucursales y cajas visibles</strong>
                    <p>Ve que dispositivos estan conectados, en que sucursal trabajan, que version del catalogo tienen y si necesitan atencion.</p>
                </article>
                <article class="feature-card">
                    <div class="feature-kicker">05</div>
                    <strong>Resumen visual del negocio</strong>
                    <p>Ventas, mezcla de cobro, productos mas vendidos y actividad reciente con un lenguaje mas cercano a operacion que a desarrollo.</p>
                </article>
                <article class="feature-card">
                    <div class="feature-kicker">06</div>
                    <strong>Impresion por dispositivo</strong>
                    <p>Cada caja puede trabajar con su propia salida local: iPad, impresora instalada o conexion dedicada segun el entorno.</p>
                </article>
            </div>
        </section>

        <section class="section" id="como-funciona">
            <div class="split-band">
                <article class="story-card">
                    <h3>Hecho para seguir vendiendo aunque la red falle</h3>
                    <p>La caja trabaja localmente primero. Cuando hay internet, Venpi publica y recibe cambios sin obligarte a detener la operacion para que “todo cargue”.</p>
                    <div class="story-grid">
                        <div class="story-line">
                            <div class="story-index">1</div>
                            <div>
                                <strong>La caja vende y guarda local</strong>
                                <p class="micro-note">Ventas, tickets, caja y catalogo siguen disponibles donde de verdad importa: en el punto de venta.</p>
                            </div>
                        </div>
                        <div class="story-line">
                            <div class="story-index">2</div>
                            <div>
                                <strong>Cloud se actualiza cuando toca</strong>
                                <p class="micro-note">La nube recibe movimientos, refleja la actividad y sirve como resumen compartido por sucursal.</p>
                            </div>
                        </div>
                        <div class="story-line">
                            <div class="story-index">3</div>
                            <div>
                                <strong>Las otras cajas se alinean</strong>
                                <p class="micro-note">Stock, catalogo y cambios operativos se propagan para que todas trabajen sobre la misma realidad.</p>
                            </div>
                        </div>
                    </div>
                </article>
                <article class="checklist-card">
                    <h3>Lo que un negocio realmente gana</h3>
                    <p>Menos dudas entre cajas, menos catalogos viejos y menos dependencia de que “la nube este perfecta” para poder cobrar.</p>
                    <div class="checklist">
                        <div class="checklist-item">
                            <i>✓</i>
                            <div>
                                <strong>Una caja iPad y una web local pueden convivir</strong>
                                <span>Cada una con su propio contexto operativo, sin perder el inventario compartido.</span>
                            </div>
                        </div>
                        <div class="checklist-item">
                            <i>✓</i>
                            <div>
                                <strong>La nube deja de ser un panel tecnico</strong>
                                <span>Se vuelve un centro claro para ver actividad, sucursales, cajas y resultados del dia.</span>
                            </div>
                        </div>
                        <div class="checklist-item">
                            <i>✓</i>
                            <div>
                                <strong>La operacion sigue primero</strong>
                                <span>La interfaz carga lo local antes que lo remoto y deja lo analitico como complemento, no como bloqueo.</span>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="cta-band">
            <div>
                <h2>Pon orden en ventas, cajas e inventario desde hoy.</h2>
                <p>Empieza con una cuenta, conecta tu primera caja y deja que Venpi una lo local con la nube sin volver todo un proyecto tecnico.</p>
            </div>
            <div class="cta-actions">
                <a class="button" href="{{ route('register') }}">Crear cuenta</a>
                <a class="button-secondary" href="{{ route('login') }}">Ya tengo acceso</a>
            </div>
        </section>
    </div>
</body>
</html>
