<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Demo HRIS | Gestión del Colaborador</title>
  <style>
    :root {
      --navy: #1a3347;
      --orange: #ff4a26;
      --cyan: #13abcd;
      --teal: #29869e;
      --bg: #f4f7fb;
      --card: #ffffff;
      --text: #24313f;
      --muted: #6b7a8a;
      --line: #dfe7ef;
      --success: #19a55a;
      --warning: #f0a500;
      --danger: #dc4c64;
      --shadow: 0 12px 30px rgba(16, 31, 54, 0.08);
      --radius: 18px;
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Inter, Arial, Helvetica, sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    .app {
      display: grid;
      grid-template-columns: 280px 1fr;
      min-height: 100vh;
    }

    .sidebar {
      background: linear-gradient(180deg, var(--navy), #102434);
      color: #fff;
      padding: 28px 20px;
      position: sticky;
      top: 0;
      height: 100vh;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 26px;
    }

    .brand-icon {
      width: 44px;
      height: 44px;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--orange), #ff7a3d);
      display: grid;
      place-items: center;
      font-weight: 800;
      color: white;
      box-shadow: 0 10px 20px rgba(255, 74, 38, 0.25);
    }

    .brand h1 {
      margin: 0;
      font-size: 18px;
      line-height: 1.2;
    }

    .brand span {
      color: rgba(255,255,255,.75);
      font-size: 12px;
    }

    .nav-group {
      margin-top: 18px;
    }

    .nav-title {
      color: rgba(255,255,255,.55);
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: .12em;
      margin: 18px 10px 10px;
    }

    .nav-btn {
      width: 100%;
      display: flex;
      align-items: center;
      gap: 12px;
      border: none;
      background: transparent;
      color: rgba(255,255,255,.84);
      padding: 13px 14px;
      border-radius: 14px;
      cursor: pointer;
      text-align: left;
      font-size: 14px;
      transition: .2s ease;
      margin-bottom: 6px;
    }

    .nav-btn:hover,
    .nav-btn.active {
      background: rgba(255,255,255,.10);
      color: #fff;
    }

    .nav-icon {
      width: 28px;
      height: 28px;
      border-radius: 10px;
      display: grid;
      place-items: center;
      background: rgba(255,255,255,.1);
      font-size: 14px;
      flex-shrink: 0;
    }

    .sidebar-footer {
      margin-top: 22px;
      padding: 16px;
      border-radius: 16px;
      background: rgba(255,255,255,.08);
      font-size: 13px;
      color: rgba(255,255,255,.85);
      line-height: 1.5;
    }

    .main {
      padding: 24px;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      margin-bottom: 22px;
      flex-wrap: wrap;
    }

    .topbar h2 {
      margin: 0;
      font-size: 28px;
    }

    .topbar p {
      margin: 6px 0 0;
      color: var(--muted);
      font-size: 14px;
    }

    .top-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .btn {
      border: 0;
      border-radius: 12px;
      padding: 11px 16px;
      font-weight: 600;
      cursor: pointer;
      transition: .2s ease;
    }

    .btn-primary {
      background: var(--orange);
      color: white;
      box-shadow: 0 12px 20px rgba(255, 74, 38, .18);
    }

    .btn-primary:hover { transform: translateY(-1px); }

    .btn-light {
      background: #fff;
      color: var(--navy);
      border: 1px solid var(--line);
    }

    .hero {
      display: grid;
      grid-template-columns: 1.4fr .8fr;
      gap: 20px;
      margin-bottom: 20px;
    }

    .card {
      background: var(--card);
      border: 1px solid rgba(26,51,71,.06);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 20px;
    }

    .hero-card {
      background: linear-gradient(135deg, #ffffff, #eef8fb);
      position: relative;
      overflow: hidden;
    }

    .hero-card::after {
      content: "";
      position: absolute;
      right: -40px;
      top: -40px;
      width: 180px;
      height: 180px;
      background: radial-gradient(circle, rgba(19,171,205,.24), transparent 65%);
    }

    .hero-card h3, .mini-title, .section-title {
      margin: 0 0 8px;
    }

    .hero-card p {
      color: var(--muted);
      line-height: 1.6;
      margin-bottom: 18px;
    }

    .kpis {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      margin-bottom: 22px;
    }

    .kpi {
      background: white;
      border-radius: 18px;
      padding: 18px;
      box-shadow: var(--shadow);
      border: 1px solid rgba(26,51,71,.06);
    }

    .kpi .label {
      color: var(--muted);
      font-size: 12px;
      margin-bottom: 8px;
    }

    .kpi .value {
      font-size: 28px;
      font-weight: 800;
      color: var(--navy);
      margin-bottom: 6px;
    }

    .kpi .trend {
      font-size: 12px;
      color: var(--success);
      font-weight: 600;
    }

    .grid-2,
    .grid-3,
    .grid-4 {
      display: grid;
      gap: 18px;
    }

    .grid-2 { grid-template-columns: repeat(2, 1fr); }
    .grid-3 { grid-template-columns: repeat(3, 1fr); }
    .grid-4 { grid-template-columns: repeat(4, 1fr); }

    .tab-section { display: none; }
    .tab-section.active { display: block; }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      border-radius: 999px;
      padding: 8px 12px;
      font-size: 12px;
      font-weight: 700;
      background: #ecf5ff;
      color: var(--teal);
      margin-right: 8px;
      margin-bottom: 8px;
    }

    .progress {
      height: 10px;
      background: #ebf0f6;
      border-radius: 999px;
      overflow: hidden;
      margin-top: 10px;
    }

    .progress > span {
      display: block;
      height: 100%;
      background: linear-gradient(90deg, var(--cyan), var(--teal));
      border-radius: 999px;
    }

    .task {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      padding: 12px 0;
      border-bottom: 1px solid var(--line);
      align-items: center;
    }

    .task:last-child { border-bottom: 0; }

    .task .left {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .checkbox {
      width: 20px;
      height: 20px;
      border-radius: 6px;
      border: 2px solid #b9cad8;
      cursor: pointer;
      display: inline-grid;
      place-items: center;
      font-size: 13px;
      color: white;
      transition: .2s ease;
    }

    .checkbox.done {
      background: var(--success);
      border-color: var(--success);
    }

    .tag {
      font-size: 12px;
      font-weight: 700;
      padding: 6px 10px;
      border-radius: 999px;
      display: inline-block;
    }

    .tag.success { background: #eaf8f0; color: var(--success); }
    .tag.warning { background: #fff6df; color: #8b6b00; }
    .tag.danger { background: #ffe9ed; color: var(--danger); }
    .tag.info { background: #ebf8fc; color: var(--teal); }

    .list-card {
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 16px;
      background: #fcfdff;
    }

    .list-card h4 {
      margin: 0 0 6px;
      font-size: 16px;
    }

    .muted {
      color: var(--muted);
      font-size: 13px;
      line-height: 1.55;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
      margin-top: 14px;
    }

    input, select, textarea {
      width: 100%;
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 12px 14px;
      font-size: 14px;
      font-family: inherit;
      background: white;
      color: var(--text);
    }

    textarea { resize: vertical; min-height: 100px; }

    .service-list,
    .training-list,
    .portal-list,
    .org-list {
      display: grid;
      gap: 12px;
      margin-top: 16px;
    }

    .service-item,
    .training-item,
    .portal-item,
    .org-card {
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 16px;
      background: white;
    }

    .service-head,
    .training-head,
    .portal-head {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
      margin-bottom: 8px;
    }

    .attendance-box {
      text-align: center;
      padding: 26px;
      background: linear-gradient(180deg, #fefefe, #f2f9ff);
      border-radius: 18px;
      border: 1px solid var(--line);
    }

    .clock {
      font-size: 38px;
      font-weight: 800;
      color: var(--navy);
      letter-spacing: .04em;
    }

    .attendance-actions {
      display: flex;
      justify-content: center;
      gap: 12px;
      margin-top: 16px;
      flex-wrap: wrap;
    }

    .log-row {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 10px;
      padding: 12px 0;
      border-bottom: 1px solid var(--line);
      font-size: 14px;
    }

    .score-row {
      display: grid;
      grid-template-columns: 1.4fr .6fr;
      gap: 12px;
      align-items: center;
      margin-bottom: 12px;
    }

    .range-wrap {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    input[type="range"] { padding: 0; }

    .result-box {
      background: linear-gradient(135deg, #f4fbff, #fff);
      border: 1px solid var(--line);
      padding: 18px;
      border-radius: 16px;
    }

    .org-chart {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
      margin-top: 14px;
    }

    .org-card strong { display: block; font-size: 16px; color: var(--navy); }
    .org-card span { font-size: 13px; color: var(--muted); }

    .portal-banner {
      display: flex;
      justify-content: space-between;
      gap: 14px;
      align-items: center;
      flex-wrap: wrap;
      background: linear-gradient(135deg, rgba(26,51,71,1), rgba(41,134,158,1));
      color: white;
      border-radius: 20px;
      padding: 22px;
      margin-bottom: 18px;
    }

    .portal-banner p { margin: 8px 0 0; color: rgba(255,255,255,.85); }

    .small {
      font-size: 12px;
      color: var(--muted);
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 14px;
    }

    @media (max-width: 1160px) {
      .hero, .grid-4, .grid-3 { grid-template-columns: 1fr 1fr; }
      .kpis { grid-template-columns: repeat(2, 1fr); }
      .org-chart { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 900px) {
      .app { grid-template-columns: 1fr; }
      .sidebar {
        position: relative;
        height: auto;
      }
      .hero, .grid-2, .grid-3, .grid-4, .form-grid, .org-chart {
        grid-template-columns: 1fr;
      }
      .kpis { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="app">
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-icon">HR</div>
        <div>
          <h1>Gestión del Colaborador</h1>
          <span>Vista demo para sistema RH</span>
        </div>
      </div>

      <div class="nav-group">
        <div class="nav-title">Módulos</div>
        <button class="nav-btn active" data-tab="dashboard"><span class="nav-icon">⌂</span>Dashboard</button>
        <button class="nav-btn" data-tab="onboarding"><span class="nav-icon">✓</span>Onboarding</button>
        <button class="nav-btn" data-tab="servicios"><span class="nav-icon">⚙</span>Servicios al colaborador</button>
        <button class="nav-btn" data-tab="asistencia"><span class="nav-icon">⏱</span>Control de asistencia</button>
        <button class="nav-btn" data-tab="desempeno"><span class="nav-icon">★</span>Evaluación de desempeño</button>
        <button class="nav-btn" data-tab="capacitaciones"><span class="nav-icon">🎓</span>Capacitaciones</button>
        <button class="nav-btn" data-tab="estructura"><span class="nav-icon">☰</span>Estructura organizacional</button>
        <button class="nav-btn" data-tab="portal"><span class="nav-icon">👤</span>Portal del colaborador</button>
      </div>

      <div class="sidebar-footer">
        <strong>Objetivo de esta demo</strong>
        <div style="margin-top:8px;">Mostrar una vista integral de RH con navegación, métricas y acciones simuladas para validar UX, flujo y alcance funcional.</div>
      </div>
    </aside>

    <main class="main">
      <div class="topbar">
        <div>
          <h2>Vista integral RH / HRIS</h2>
          <p>Onboarding, servicios, asistencia, desempeño, capacitaciones, organigrama y portal del colaborador.</p>
        </div>
        <div class="top-actions"></div>
      </div>

      <section id="dashboard" class="tab-section active">
        <div class="hero">
          <div class="card hero-card">
            <h3>Centro de operación de Recursos Humanos</h3>
            <p>Esta pantalla resume el ciclo de vida del colaborador: ingreso, soporte interno, control operativo, evaluación, desarrollo y autogestión. La idea es que puedas tomar esta maqueta como base para tu módulo de RH.</p>
            <span class="chip">Alta de personal</span>
            <span class="chip">Incidencias</span>
            <span class="chip">Capacitación</span>
            <span class="chip">Evaluaciones</span>
            <span class="chip">Portal self-service</span>
          </div>
          <div class="card">
            <div class="mini-title"><strong>Estado general</strong></div>
            <p class="muted">Colaboradores activos: <strong>84</strong><br>Nuevos ingresos del mes: <strong>6</strong><br>Capacitaciones vigentes: <strong>12</strong><br>Evaluaciones pendientes: <strong>9</strong></p>
            <div class="progress"><span style="width:78%"></span></div>
            <div class="small" style="margin-top:8px;">Avance general del área RH: 78%</div>
          </div>
        </div>

        <div class="kpis">
          <div class="kpi"><div class="label">Onboarding activo</div><div class="value">14</div><div class="trend">+3 esta semana</div></div>
          <div class="kpi"><div class="label">Tickets internos</div><div class="value">23</div><div class="trend">91% resueltos</div></div>
          <div class="kpi"><div class="label">Asistencia de hoy</div><div class="value">96%</div><div class="trend">+2.4% vs ayer</div></div>
          <div class="kpi"><div class="label">Promedio desempeño</div><div class="value">4.4</div><div class="trend">Sobre 5.0</div></div>
        </div>

        <div class="grid-3">
          <div class="card">
            <div class="section-header"><h3 class="section-title">Pendientes inmediatos</h3><span class="tag warning">Hoy</span></div>
            <div class="task"><div class="left"><span class="checkbox done">✓</span><div><strong>Alta IMSS colaborador</strong><div class="small">Área: Administración</div></div></div><span class="tag success">Completado</span></div>
            <div class="task"><div class="left"><span class="checkbox"></span><div><strong>Firmar contrato digital</strong><div class="small">2 colaboradores nuevos</div></div></div><span class="tag warning">Pendiente</span></div>
            <div class="task"><div class="left"><span class="checkbox"></span><div><strong>Capacitación de inducción</strong><div class="small">Programada 4:00 pm</div></div></div><span class="tag info">En curso</span></div>
          </div>

          <div class="card">
            <div class="section-header"><h3 class="section-title">Autoservicio más usado</h3><span class="tag info">Portal</span></div>
            <div class="list-card">
              <h4>Solicitud de vacaciones</h4>
              <div class="muted">12 solicitudes este mes</div>
            </div>
            <div class="list-card" style="margin-top:10px;">
              <h4>Constancia laboral</h4>
              <div class="muted">8 documentos emitidos</div>
            </div>
            <div class="list-card" style="margin-top:10px;">
              <h4>Recibos de nómina</h4>
              <div class="muted">74 descargas en los últimos 7 días</div>
            </div>
          </div>

          <div class="card">
            <div class="section-header"><h3 class="section-title">Capacitación y cultura</h3><span class="tag success">Vigente</span></div>
            <div class="muted">Curso con mayor avance:</div>
            <h4 style="margin:8px 0 6px;">Inducción corporativa y cumplimiento</h4>
            <div class="progress"><span style="width:67%"></span></div>
            <div class="small" style="margin:8px 0 16px;">67% de avance promedio</div>
            <div class="muted">Próxima sesión en vivo:</div>
            <strong>Políticas internas y control documental</strong>
          </div>
        </div>
      </section>

      <section id="onboarding" class="tab-section">
        <div class="grid-2">
          <div class="card">
            <div class="section-header">
              <h3 class="section-title">Checklist de onboarding</h3>
              <span class="tag info" id="onboardingStatus">3 / 6 completados</span>
            </div>
            <p class="muted">Simulación del proceso de ingreso para un nuevo colaborador. Haz clic en cada paso para marcarlo.</p>
            <div id="onboardingTasks"></div>
            <div class="progress"><span id="onboardingProgress" style="width:50%"></span></div>
          </div>
          <div class="card">
            <h3 class="section-title">Ficha de ingreso</h3>
            <div class="form-grid">
              <input value="María Fernanda López" />
              <input value="Ejecutiva de nómina" />
              <input value="Finanzas" />
              <input value="2026-03-10" type="date" />
              <input value="maria.lopez@empresa.com" />
              <input value="4421234567" />
            </div>
            <div style="margin-top:12px;"><textarea>Documentos requeridos: INE, CURP, RFC, comprobante de domicilio, NSS, cuenta bancaria y contrato firmado.</textarea></div>
            <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
              <button class="btn btn-primary" onclick="alert('Aquí se guardaría el expediente y se dispararían tareas automáticas.')">Guardar expediente</button>
              <button class="btn btn-light" onclick="alert('Se enviaría correo / WhatsApp con bienvenida y requisitos.')">Enviar bienvenida</button>
            </div>
          </div>
        </div>
      </section>

      <section id="servicios" class="tab-section">
        <div class="grid-2">
          <div class="card">
            <h3 class="section-title">Levantar solicitud de servicio</h3>
            <p class="muted">Servicios internos para el colaborador: cartas, vacaciones, permisos, cambios de datos, constancias o soporte RH.</p>
            <div class="form-grid">
              <select id="serviceType">
                <option>Constancia laboral</option>
                <option>Solicitud de vacaciones</option>
                <option>Cambio de cuenta bancaria</option>
                <option>Permiso especial</option>
                <option>Actualización de datos personales</option>
              </select>
              <select id="servicePriority">
                <option>Normal</option>
                <option>Urgente</option>
                <option>Alta dirección</option>
              </select>
            </div>
            <div style="margin-top:12px;"><textarea id="serviceDescription" placeholder="Describe brevemente la solicitud..."></textarea></div>
            <div style="margin-top:14px;"><button class="btn btn-primary" onclick="addService()">Crear solicitud</button></div>
          </div>
          <div class="card">
            <div class="section-header"><h3 class="section-title">Bandeja de servicios</h3><span class="tag info" id="serviceCounter">3 abiertos</span></div>
            <div id="serviceList" class="service-list"></div>
          </div>
        </div>
      </section>

      <section id="asistencia" class="tab-section">
        <div class="grid-2">
          <div class="card">
            <h3 class="section-title">Reloj checador</h3>
            <div class="attendance-box">
              <div class="small">Hora actual</div>
              <div class="clock" id="clock">00:00:00</div>
              <div class="small" id="attendanceState">Sin registro hoy</div>
              <div class="attendance-actions">
                <button class="btn btn-primary" onclick="markAttendance('Entrada')">Registrar entrada</button>
                <button class="btn btn-light" onclick="markAttendance('Salida a comida')">Salida comida</button>
                <button class="btn btn-light" onclick="markAttendance('Regreso de comida')">Regreso comida</button>
                <button class="btn btn-light" onclick="markAttendance('Salida')">Registrar salida</button>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="section-header"><h3 class="section-title">Bitácora del día</h3><span class="tag success" id="attendanceCount">0 registros</span></div>
            <div class="log-row" style="font-weight:700; color:var(--muted);"><div>Movimiento</div><div>Hora</div><div>Estatus</div></div>
            <div id="attendanceLog"></div>
          </div>
        </div>
      </section>

      <section id="desempeno" class="tab-section">
        <div class="grid-2">
          <div class="card">
            <h3 class="section-title">Evaluación de desempeño</h3>
            <p class="muted">Simulación de una evaluación con ponderación promedio sobre 5 puntos.</p>
            <div class="score-row">
              <div><strong>Productividad</strong><div class="small">Cumplimiento y eficiencia</div></div>
              <div class="range-wrap"><input type="range" min="1" max="5" value="4" id="score1" oninput="calcPerformance()"><span id="score1Value">4</span></div>
            </div>
            <div class="score-row">
              <div><strong>Calidad del trabajo</strong><div class="small">Nivel de precisión</div></div>
              <div class="range-wrap"><input type="range" min="1" max="5" value="5" id="score2" oninput="calcPerformance()"><span id="score2Value">5</span></div>
            </div>
            <div class="score-row">
              <div><strong>Trabajo en equipo</strong><div class="small">Colaboración interna</div></div>
              <div class="range-wrap"><input type="range" min="1" max="5" value="4" id="score3" oninput="calcPerformance()"><span id="score3Value">4</span></div>
            </div>
            <div class="score-row">
              <div><strong>Iniciativa</strong><div class="small">Propuesta y solución</div></div>
              <div class="range-wrap"><input type="range" min="1" max="5" value="4" id="score4" oninput="calcPerformance()"><span id="score4Value">4</span></div>
            </div>
          </div>
          <div class="card">
            <div class="result-box">
              <div class="small">Resultado general</div>
              <div style="font-size:42px; font-weight:800; color:var(--navy); margin:8px 0;" id="performanceResult">4.25</div>
              <div id="performanceTag" class="tag success">Alto desempeño</div>
              <p class="muted" style="margin-top:14px;">Esta sección puede conectarse con objetivos por puesto, evaluaciones 90°, 180° o 360°, plan de mejora y bonos variables.</p>
              <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn btn-primary" onclick="alert('Aquí se guardaría la evaluación y se enviaría al expediente.')">Guardar evaluación</button>
                <button class="btn btn-light" onclick="alert('Aquí se generaría un plan de acción con metas y seguimiento.')">Generar plan de acción</button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="capacitaciones" class="tab-section">
        <div class="grid-2">
          <div class="card">
            <div class="section-header"><h3 class="section-title">Catálogo de capacitaciones</h3><span class="tag info">LMS básico</span></div>
            <div id="trainingList" class="training-list"></div>
          </div>
          <div class="card">
            <h3 class="section-title">Mi avance</h3>
            <p class="muted">Simulación de inscripción y seguimiento del colaborador en cursos internos.</p>
            <div class="list-card">
              <h4>Cursos inscritos</h4>
              <div id="enrolledCourses" class="muted" style="margin-top:10px;">Aún no hay cursos seleccionados.</div>
            </div>
            <div class="list-card" style="margin-top:12px;">
              <h4>Indicadores sugeridos</h4>
              <div class="muted">• % de cumplimiento por colaborador<br>• Horas de capacitación por mes<br>• Cursos obligatorios vencidos<br>• Evaluación posterior al curso</div>
            </div>
          </div>
        </div>
      </section>

      <section id="estructura" class="tab-section">
        <div class="card">
          <div class="section-header"><h3 class="section-title">Estructura organizacional</h3><span class="tag info">Organigrama</span></div>
          <p class="muted">Vista base de organigrama. Aquí tu programador puede enlazar puestos, jefe directo, vacantes, headcount autorizado y centro de costos.</p>
          <div class="org-chart">
            <div class="org-card"><strong>Dirección General</strong><span>1 posición</span><div class="small" style="margin-top:8px;">Define visión, resultados y estrategia.</div></div>
            <div class="org-card"><strong>Finanzas y Contabilidad</strong><span>12 posiciones</span><div class="small" style="margin-top:8px;">Nómina, fiscal, tesorería y reportes.</div></div>
            <div class="org-card"><strong>Comercial</strong><span>8 posiciones</span><div class="small" style="margin-top:8px;">Ventas, seguimiento y atención al cliente.</div></div>
            <div class="org-card"><strong>Operaciones</strong><span>16 posiciones</span><div class="small" style="margin-top:8px;">Procesos, servicio y control operativo.</div></div>
            <div class="org-card"><strong>Recursos Humanos</strong><span>4 posiciones</span><div class="small" style="margin-top:8px;">Atracción, nómina, clima y capacitación.</div></div>
            <div class="org-card"><strong>Tecnología</strong><span>6 posiciones</span><div class="small" style="margin-top:8px;">Desarrollo, soporte e integraciones.</div></div>
          </div>
        </div>
      </section>

      <section id="portal" class="tab-section">
        <div class="portal-banner">
          <div>
            <h3 style="margin:0;">Portal del colaborador</h3>
            <p>Espacio de autogestión para recibos de nómina, solicitudes, documentos, vacaciones y capacitación.</p>
          </div>
          <button class="btn btn-primary" onclick="alert('Aquí abrirías el perfil, permisos o expedientes del colaborador.')">Entrar al perfil</button>
        </div>

        <div class="grid-4">
          <div class="kpi"><div class="label">Vacaciones disponibles</div><div class="value">8</div><div class="trend">días</div></div>
          <div class="kpi"><div class="label">Recibos emitidos</div><div class="value">24</div><div class="trend">histórico</div></div>
          <div class="kpi"><div class="label">Cursos asignados</div><div class="value">5</div><div class="trend">3 activos</div></div>
          <div class="kpi"><div class="label">Solicitudes abiertas</div><div class="value">2</div><div class="trend">1 en revisión</div></div>
        </div>

        <div class="grid-2" style="margin-top:18px;">
          <div class="card">
            <h3 class="section-title">Accesos rápidos</h3>
            <div class="portal-list">
              <div class="portal-item"><div class="portal-head"><strong>Descargar recibo de nómina</strong><span class="tag success">Disponible</span></div><div class="muted">Consulta y descarga tus CFDI de nómina y acuses.</div></div>
              <div class="portal-item"><div class="portal-head"><strong>Solicitar vacaciones</strong><span class="tag info">Autoservicio</span></div><div class="muted">Envía solicitud y revisa aprobación por jefe directo.</div></div>
              <div class="portal-item"><div class="portal-head"><strong>Actualizar datos personales</strong><span class="tag warning">Revisión RH</span></div><div class="muted">Cuenta bancaria, dirección, contacto de emergencia y documentos.</div></div>
            </div>
          </div>
          <div class="card">
            <h3 class="section-title">Mi expediente</h3>
            <div class="list-card"><h4>Datos del colaborador</h4><div class="muted">Puesto: Ejecutivo Administrativo<br>Área: Finanzas<br>Antigüedad: 1 año 4 meses<br>Jefe directo: Coordinador Administrativo</div></div>
            <div class="list-card" style="margin-top:12px;"><h4>Documentos</h4><div class="muted">Contrato firmado, CURP, RFC, NSS, comprobante de domicilio, constancias y evaluaciones.</div></div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <script>
    const tabs = document.querySelectorAll('.nav-btn');
    const sections = document.querySelectorAll('.tab-section');

    tabs.forEach(btn => {
      btn.addEventListener('click', () => {
        tabs.forEach(b => b.classList.remove('active'));
        sections.forEach(s => s.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab).classList.add('active');
      });
    });

    const onboardingTasks = [
      { text: 'Alta del expediente del colaborador', done: true },
      { text: 'Carga de documentos personales', done: true },
      { text: 'Firma de contrato y políticas internas', done: false },
      { text: 'Asignación de equipo y accesos', done: false },
      { text: 'Inducción organizacional', done: true },
      { text: 'Evaluación de periodo inicial', done: false }
    ];

    function renderOnboarding() {
      const wrap = document.getElementById('onboardingTasks');
      wrap.innerHTML = '';
      onboardingTasks.forEach((task, index) => {
        const div = document.createElement('div');
        div.className = 'task';
        div.innerHTML = `
          <div class="left">
            <span class="checkbox ${task.done ? 'done' : ''}" onclick="toggleTask(${index})">${task.done ? '✓' : ''}</span>
            <div><strong>${task.text}</strong></div>
          </div>
          <span class="tag ${task.done ? 'success' : 'warning'}">${task.done ? 'Listo' : 'Pendiente'}</span>
        `;
        wrap.appendChild(div);
      });

      const completed = onboardingTasks.filter(t => t.done).length;
      const pct = Math.round((completed / onboardingTasks.length) * 100);
      document.getElementById('onboardingProgress').style.width = pct + '%';
      document.getElementById('onboardingStatus').innerText = `${completed} / ${onboardingTasks.length} completados`;
    }

    function toggleTask(index) {
      onboardingTasks[index].done = !onboardingTasks[index].done;
      renderOnboarding();
    }

    const services = [
      { type: 'Constancia laboral', priority: 'Normal', desc: 'Emitir constancia para trámite bancario.', status: 'En proceso' },
      { type: 'Solicitud de vacaciones', priority: 'Urgente', desc: 'Solicito 2 días para la próxima semana.', status: 'Pendiente' },
      { type: 'Cambio de cuenta bancaria', priority: 'Normal', desc: 'Actualizar CLABE para el siguiente pago.', status: 'Atendido' }
    ];

    function renderServices() {
      const list = document.getElementById('serviceList');
      list.innerHTML = '';
      services.forEach(item => {
        const tagClass = item.status === 'Atendido' ? 'success' : item.status === 'En proceso' ? 'info' : 'warning';
        const el = document.createElement('div');
        el.className = 'service-item';
        el.innerHTML = `
          <div class="service-head">
            <strong>${item.type}</strong>
            <span class="tag ${tagClass}">${item.status}</span>
          </div>
          <div class="muted">Prioridad: ${item.priority}</div>
          <div class="muted" style="margin-top:6px;">${item.desc}</div>
        `;
        list.appendChild(el);
      });
      const abiertos = services.filter(s => s.status !== 'Atendido').length;
      document.getElementById('serviceCounter').innerText = `${abiertos} abiertos`;
    }

    function addService() {
      const type = document.getElementById('serviceType').value;
      const priority = document.getElementById('servicePriority').value;
      const desc = document.getElementById('serviceDescription').value.trim() || 'Sin descripción adicional.';
      services.unshift({ type, priority, desc, status: 'Pendiente' });
      document.getElementById('serviceDescription').value = '';
      renderServices();
      alert('Solicitud creada correctamente.');
    }

    function updateClock() {
      const now = new Date();
      const time = now.toLocaleTimeString('es-MX');
      document.getElementById('clock').innerText = time;
    }
    setInterval(updateClock, 1000);
    updateClock();

    const attendance = [];
    function markAttendance(type) {
      const now = new Date().toLocaleTimeString('es-MX');
      attendance.unshift({ type, time: now, status: 'Registrado' });
      document.getElementById('attendanceState').innerText = `Último movimiento: ${type} a las ${now}`;
      renderAttendance();
    }

    function renderAttendance() {
      const wrap = document.getElementById('attendanceLog');
      wrap.innerHTML = '';
      attendance.forEach(item => {
        const row = document.createElement('div');
        row.className = 'log-row';
        row.innerHTML = `<div>${item.type}</div><div>${item.time}</div><div><span class="tag success">${item.status}</span></div>`;
        wrap.appendChild(row);
      });
      document.getElementById('attendanceCount').innerText = `${attendance.length} registros`;
    }

    function calcPerformance() {
      const s1 = +document.getElementById('score1').value;
      const s2 = +document.getElementById('score2').value;
      const s3 = +document.getElementById('score3').value;
      const s4 = +document.getElementById('score4').value;

      document.getElementById('score1Value').innerText = s1;
      document.getElementById('score2Value').innerText = s2;
      document.getElementById('score3Value').innerText = s3;
      document.getElementById('score4Value').innerText = s4;

      const avg = ((s1 + s2 + s3 + s4) / 4).toFixed(2);
      const result = document.getElementById('performanceResult');
      const tag = document.getElementById('performanceTag');
      result.innerText = avg;

      if (avg >= 4.5) {
        tag.className = 'tag success';
        tag.innerText = 'Desempeño sobresaliente';
      } else if (avg >= 3.5) {
        tag.className = 'tag info';
        tag.innerText = 'Alto desempeño';
      } else if (avg >= 2.5) {
        tag.className = 'tag warning';
        tag.innerText = 'Requiere seguimiento';
      } else {
        tag.className = 'tag danger';
        tag.innerText = 'Plan de mejora urgente';
      }
    }

    const trainings = [
      { title: 'Inducción corporativa', hours: '2 horas', mode: 'En línea', status: 'Disponible' },
      { title: 'Políticas internas y cumplimiento', hours: '1.5 horas', mode: 'Presencial', status: 'Disponible' },
      { title: 'Seguridad de la información', hours: '2 horas', mode: 'En línea', status: 'Obligatorio' },
      { title: 'Liderazgo para mandos medios', hours: '4 horas', mode: 'Mixto', status: 'Opcional' }
    ];

    const enrolled = [];

    function renderTrainings() {
      const wrap = document.getElementById('trainingList');
      wrap.innerHTML = '';
      trainings.forEach((item, index) => {
        const el = document.createElement('div');
        el.className = 'training-item';
        el.innerHTML = `
          <div class="training-head">
            <strong>${item.title}</strong>
            <span class="tag ${item.status === 'Obligatorio' ? 'warning' : 'info'}">${item.status}</span>
          </div>
          <div class="muted">Duración: ${item.hours} · Modalidad: ${item.mode}</div>
          <div style="margin-top:10px;"><button class="btn btn-primary" onclick="enrollTraining(${index})">Inscribirme</button></div>
        `;
        wrap.appendChild(el);
      });
      renderEnrolled();
    }

    function enrollTraining(index) {
      const course = trainings[index].title;
      if (!enrolled.includes(course)) enrolled.push(course);
      renderEnrolled();
      alert('Curso agregado al colaborador.');
    }

    function renderEnrolled() {
      const wrap = document.getElementById('enrolledCourses');
      if (!enrolled.length) {
        wrap.innerHTML = 'Aún no hay cursos seleccionados.';
        return;
      }
      wrap.innerHTML = enrolled.map(c => `• ${c}`).join('<br>');
    }

    renderOnboarding();
    renderServices();
    renderAttendance();
    calcPerformance();
    renderTrainings();
  </script>
</body>
</html>
