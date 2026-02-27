<div id="comercial-dashboard" class="comercial-dashboard">

  
<div class="app">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="brand">
        <div class="logo"></div>
        <div>
          <h1>Módulo Comercial</h1>
          <p>Cotizaciones · Facturas · KPIs</p>
        </div>
      </div>

      <div class="panel" id="userPanel">
        <h3>Usuario</h3>
        <div class="mini" id="userNameLabel"></div>
        <div class="mini" id="userRoleLabel" style="margin-top:6px;"></div>
      </div>

      <div class="nav" role="navigation" aria-label="Navegación">
        <button class="active" data-view="dashboard">
          <span class="icon">📊</span> Dashboard
        </button>
        <button data-view="cotizaciones">
          <span class="icon">🧾</span> Cotizaciones
        </button>
        <button data-view="pipeline">
          <span class="icon">🧩</span> Pipeline
        </button>
        <button data-view="facturas">
          <span class="icon">🧾</span> Facturas
        </button>
        <button data-view="vendedores" id="navVendedores">
          <span class="icon">🧑‍💼</span> Vendedores
        </button>
      </div>

      <div class="panel" id="filtersPanel">
        <h3>Filtros</h3>

        <div id="sellerFilterBlock">
          <label for="fSeller">Vendedor</label>
          <select id="fSeller"></select>
        </div>

        <div class="row">
          <div>
            <label for="fFrom">Desde</label>
            <input id="fFrom" type="date" />
          </div>
          <div>
            <label for="fTo">Hasta</label>
            <input id="fTo" type="date" />
          </div>
        </div>

        <label for="fChannel">Canal</label>
        <select id="fChannel"></select>

        <label for="fSegment">Segmento</label>
        <select id="fSegment"></select>

        <div class="row" style="margin-top:12px;">
          <button class="btn" id="btnResetFilters">Reset</button>
          <button class="btn primary" id="btnApplyFilters">Aplicar</button>
        </div>
        <div id="fError" class="mini" style="color:var(--bad); margin-top:8px; display:none;"></div>

        <p class="mini" style="margin:12px 0 0;">
          Para Vendedor: se fuerza su propio ID; no se muestra información de otros.
        </p>
      </div>
    </aside>

    <!-- Content -->
    <main class="content">
      <div class="topbar">
        <div class="title">
          <h2 id="viewTitle">Dashboard</h2>
          <p id="viewSubtitle">
            Conversión basada en facturación (Factura = Venta). La venta puede o no nacer de una cotización.
          </p>
          <p class="mini" id="periodLabel"></p>
        </div>

        <div class="actions">
          <button class="btn" id="btnSeed">Recargar</button>
        </div>
      </div>

      <!-- Views -->
      <section id="view-dashboard" class="view">
        <div class="grid kpis" id="kpiGrid"></div>

        <div class="split" id="dashSplit">
          <div class="card" id="scoreboardCard">
            <div class="section-title">
              <h3 id="scoreboardTitle">Scoreboard de vendedores</h3>
              <small class="muted" id="scoreboardHint">Ranking por $ facturado y conversión</small>
            </div>
            <div id="sellerTableWrap"></div>
          </div>

          <div class="card">
            <div class="section-title">
              <h3>Motivos principales de pérdida</h3>
              <small class="muted">Frecuencia y monto cotizado perdido</small>
            </div>
            <div id="lossReasonsWrap"></div>
          </div>
        </div>

        <div class="card" style="margin-top:12px;">
          <div class="section-title">
            <h3>Últimas cotizaciones</h3>
            <small class="muted">Click para ver detalle</small>
          </div>
          <div id="latestQuotesWrap"></div>
        </div>
      </section>

      <section id="view-cotizaciones" class="view" style="display:none;">
        <div class="card">
          <div class="section-title">
            <h3 id="quotesTitle">Listado de cotizaciones</h3>
            <small class="muted">Filtro por vendedor/canal/segmento/fecha</small>
          </div>
          <div id="quotesTableWrap"></div>
        </div>
      </section>

      <section id="view-pipeline" class="view" style="display:none;">
        <div class="card" style="margin-bottom:12px;">
          <div class="section-title">
            <h3>Pipeline (Cotización → Factura)</h3>
            <small class="muted">Abierta · En negociación · Facturada · Perdida/Expirada</small>
          </div>
          <div class="board" id="pipelineBoard"></div>
        </div>

        <div class="split">
          <div class="card">
            <div class="section-title">
              <h3>Aging de cotizaciones abiertas</h3>
              <small class="muted">0–7 · 8–15 · 16–30 · 31–60 · +60</small>
            </div>
            <div id="agingWrap"></div>
          </div>
          <div class="card">
            <div class="section-title">
              <h3>Conversión ponderada (solo cotizaciones)</h3>
              <small class="muted">$ facturado desde cotizaciones / $ cotizado</small>
            </div>
            <div id="weightedWrap"></div>
          </div>
        </div>
      </section>

      <section id="view-facturas" class="view" style="display:none;">
        <div class="card">
          <div class="section-title">
            <h3 id="invoicesTitle">Facturas</h3>
            <small class="muted">Venta = Factura (puede venir o no de cotización)</small>
          </div>
          <div id="invoicesWrap"></div>
        </div>
      </section>

      <section id="view-vendedores" class="view" style="display:none;">
        <div class="card">
          <div class="section-title">
            <h3>Vendedores</h3>
            <small class="muted">Metas, facturación, conversión, margen, cobranza y actividad</small>
          </div>
          <div id="vendorsWrap"></div>
        </div>
      </section>
    </main>
  </div>

  <!-- Modal Cotización -->
  <div class="modal-backdrop" id="modalBackdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="mTitle">
      <header>
        <div>
          <h3 id="mTitle">Cotización</h3>
          <p id="mSub" class="muted"></p>
        </div>
        <button class="close" id="btnCloseModal">Cerrar</button>
      </header>

      <div class="body">
        <div class="card">
          <div class="section-title">
            <h3>Información</h3>
            <small class="muted">Estado · Monto · Vendedor</small>
          </div>

          <div class="two">
            <div>
              <label>Cliente</label>
              <input id="mClient" />
            </div>
            <div>
              <label>Segmento</label>
              <select id="mSegment"></select>
            </div>
          </div>

          <div class="two">
            <div>
              <label>Canal</label>
              <select id="mChannel"></select>
            </div>
            <div>
              <label>Vendedor</label>
              <select id="mSeller" disabled></select>
            </div>
          </div>

          <div class="two">
            <div>
              <label>Monto cotizado (MXN)</label>
              <input id="mTotal" type="number" min="0" step="0.01" />
            </div>
            <div>
              <label>Descuento (%)</label>
              <input id="mDiscount" type="number" min="0" max="100" step="0.1" />
            </div>
          </div>

          <div class="two">
            <div>
              <label>Estatus</label>
              <select id="mStatus">
                <option value="OPEN">Abierta</option>
                <option value="NEGOTIATION">En negociación</option>
                <option value="WON">Facturada</option>
                <option value="LOST">Perdida</option>
                <option value="EXPIRED">Expirada</option>
              </select>
            </div>
            <div>
              <label>Probabilidad</label>
              <select id="mProb">
                <option value="0.2">Baja (20%)</option>
                <option value="0.5">Media (50%)</option>
                <option value="0.8">Alta (80%)</option>
              </select>
            </div>
          </div>

          <div class="two">
            <div>
              <label>Fecha estimada de cierre</label>
              <input id="mCloseEst" type="date" />
            </div>
            <div>
              <label>Vigencia</label>
              <input id="mValidUntil" type="date" />
            </div>
          </div>

          <div class="two">
            <div>
              <label>Motivo de NO venta (si Perdida/Expirada)</label>
              <select id="mLostReason"></select>
            </div>
            <div>
              <label>Motivo de SÍ venta (si Facturada)</label>
              <select id="mWinReason"></select>
            </div>
          </div>

          <label>Notas</label>
          <textarea id="mNotes" placeholder="Notas breves: objeción, contexto, next step, etc."></textarea>

          <div id="mError" class="mini" style="color:var(--bad); margin-top:8px; display:none;"></div>

          <div class="row" style="margin-top:12px;">
            <button class="btn primary" id="btnSaveQuote">Guardar</button>
            <button class="btn bad" id="btnMarkLost">Marcar perdida</button>
          </div>

          <div class="mini" style="margin-top:10px;">
            Regla: Factura creada = la cotización se considera “Facturada” (Venta).
          </div>
        </div>

        <div class="card">
          <div class="section-title">
            <h3>Seguimiento (actividad)</h3>
            <small class="muted">Registro mínimo sin CRM</small>
          </div>

          <div class="two">
            <div>
              <label>Tipo</label>
              <select id="mActType">
                <option value="CALL">Llamada</option>
                <option value="WHATSAPP">WhatsApp</option>
                <option value="EMAIL">Email</option>
                <option value="MEETING">Reunión</option>
              </select>
            </div>
            <div>
              <label>Fecha</label>
              <input id="mActDate" type="date" />
            </div>
          </div>

          <label>Resultado</label>
          <input id="mActResult" placeholder="Ej: cliente pidió ajuste / aprobó propuesta / pide documentos" />

          <label>Próxima acción</label>
          <input id="mActNext" placeholder="Ej: enviar propuesta ajustada / agendar llamada / emitir factura" />

          <label>Próxima fecha</label>
          <input id="mActNextDate" type="date" />

          <div class="row" style="margin-top:12px;">
            <button class="btn" id="btnAddActivity">Agregar actividad</button>
            <button class="btn warn" id="btnExpire">Marcar expirada</button>
          </div>
          <div id="mActError" class="mini" style="color:var(--bad); margin-top:8px; display:none;"></div>

          <div style="margin-top:14px;">
            <div class="section-title">
              <h3>Historial</h3>
              <small class="muted">Más reciente arriba</small>
            </div>
            <div id="mActivityList"></div>
          </div>

          <div style="margin-top:14px;">
            <div class="section-title">
              <h3>Factura vinculada</h3>
              <small class="muted">Venta = Factura</small>
            </div>
            <div id="mInvoiceBox"></div>
          </div>
        </div>
      </div>

      <footer>
        <div class="muted mini" id="mFooterLeft"></div>
        <div class="muted mini" id="mFooterRight"></div>
      </footer>
    </div>
  </div>

  <!-- Modal Nueva Cotización -->
  <div class="modal-backdrop" id="newQuoteBackdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="nqTitle">
      <header>
        <div>
          <h3 id="nqTitle">Nueva cotización</h3>
          <p class="muted">Registro mínimo para operar el embudo y medir KPIs.</p>
        </div>
        <button class="close" id="btnCloseNewQuote">Cerrar</button>
      </header>

      <div class="body">
        <div class="card" style="grid-column:1/-1;">
          <div class="two">
            <div>
              <label>Cliente</label>
              <input id="nClient" placeholder="Nombre/Razón social" />
            </div>
            <div>
              <label>Vendedor</label>
              <select id="nSeller" disabled></select>
            </div>
          </div>

          <div class="two">
            <div>
              <label>Segmento</label>
              <select id="nSegment"></select>
            </div>
            <div>
              <label>Canal</label>
              <select id="nChannel"></select>
            </div>
          </div>

          <div class="two">
            <div>
              <label>Monto cotizado (MXN)</label>
              <input id="nTotal" type="number" min="0" step="0.01" value="25000" />
            </div>
            <div>
              <label>Descuento (%)</label>
              <input id="nDiscount" type="number" min="0" max="100" step="0.1" value="0" />
            </div>
          </div>

          <div class="two">
            <div>
              <label>Probabilidad</label>
              <select id="nProb">
                <option value="0.2">Baja (20%)</option>
                <option value="0.5" selected>Media (50%)</option>
                <option value="0.8">Alta (80%)</option>
              </select>
            </div>
            <div>
              <label>Fecha estimada de cierre</label>
              <input id="nCloseEst" type="date" />
            </div>
          </div>

          <div class="two">
            <div>
              <label>Vigencia</label>
              <input id="nValidUntil" type="date" />
            </div>
            <div>
              <label>Notas</label>
              <input id="nNotes" placeholder="Resumen / objeción principal / siguiente paso" />
            </div>
          </div>

          <div class="row" style="margin-top:12px;">
            <button class="btn primary" id="btnCreateQuote">Crear cotización</button>
          </div>
          <div id="nqError" class="mini" style="color:var(--bad); margin-top:8px; display:none;"></div>
        </div>
      </div>

      <footer>
        <div class="muted mini">Tip: el “cierre” real sucede al emitir Factura. La venta puede venir sin cotización (factura directa).</div>
        <div></div>
      </footer>
    </div>
  </div>

  <!-- Modal Nueva Factura -->
  <div class="modal-backdrop" id="newInvoiceBackdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="niTitle">
      <header>
        <div>
          <h3 id="niTitle">Nueva factura</h3>
          <p class="muted">Venta = Factura. Puede estar vinculada a cotización o ser directa.</p>
        </div>
        <button class="close" id="btnCloseNewInvoice">Cerrar</button>
      </header>

      <div class="body">
        <div class="card" style="grid-column:1/-1;">
          <div class="two">
            <div>
              <label>Vincular a cotización (opcional)</label>
              <select id="niQuote"></select>
              <div class="mini" style="margin-top:6px;">Si vinculas, la cotización pasa a “Facturada”.</div>
            </div>
            <div>
              <label>Vendedor</label>
              <select id="niSeller" disabled></select>
            </div>
          </div>

          <div class="two">
            <div>
              <label>Cliente</label>
              <input id="niClient" placeholder="Nombre/Razón social" />
            </div>
            <div>
              <label>Fecha de emisión</label>
              <input id="niIssuedAt" type="date" />
            </div>
          </div>

          <div class="two">
            <div>
              <label>Segmento</label>
              <select id="niSegment"></select>
            </div>
            <div>
              <label>Canal</label>
              <select id="niChannel"></select>
            </div>
          </div>

          <div class="two">
            <div>
              <label>Total factura (MXN)</label>
              <input id="niTotal" type="number" min="0" step="0.01" value="25000" />
            </div>
            <div>
              <label>% Cobrado</label>
              <input id="niPaidPct" type="number" min="0" max="100" step="1" value="20" />
            </div>
          </div>

          <div class="two">
            <div>
              <label>% Margen estimado</label>
              <input id="niMarginPct" type="number" min="0" max="100" step="1" value="40" />
            </div>
            <div>
              <label>Motivo de sí venta (win driver)</label>
              <select id="niWinReason"></select>
            </div>
          </div>

          <label>Notas</label>
          <textarea id="niNotes" placeholder="Observaciones de facturación / condiciones / etc."></textarea>

          <div class="row" style="margin-top:12px;">
            <button class="btn ok" id="btnCreateInvoice">Emitir factura</button>
          </div>
          <div id="niError" class="mini" style="color:var(--bad); margin-top:8px; display:none;"></div>
        </div>
      </div>

      <footer>
        <div class="muted mini">Permisos: Vendedor solo puede facturar a su nombre.</div>
        <div></div>
      </footer>
    </div>
  </div>

<script>
const API_BASE = "{{ url('/' . $tenant_slug . '/tiadmin/comercial/api') }}";
const CSRF_TOKEN = (document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content')) || "{{ csrf_token() }}";

/* ========= Catálogos ========= */
let CATALOG = {
  channels: [],
  segments: [],
  lostReasons: [],
  winReasons: []
};

const STATUS_LABEL = {
  OPEN: "Abierta",
  NEGOTIATION: "En negociación",
  WON: "Facturada",
  LOST: "Perdida",
  EXPIRED: "Expirada"
};

/* ========= Estado ========= */
let state = {
  sellers: [],
  quotes: [],
  invoices: [],
  filters: { sellerId: "ALL", from: null, to: null, channel: "ALL", segment: "ALL" },
  period: { from: null, to: null, label: null },
  ui: { view: "dashboard", selectedQuoteId: null, quotesPage: 1, invoicesPage: 1 },
  auth: { role: "SELLER", sellerId: null, userId: null, userName: null }
};

/* ========= Utils ========= */
const $ = (id)=>document.getElementById(id);

function money(n){ return (n ?? 0).toLocaleString("es-MX", {style:"currency", currency:"MXN"}); }
function pct(n){ return (n*100).toFixed(1) + "%"; }
function daysBetween(d1, d2){
  const a = new Date(d1); const b = new Date(d2);
  return Math.round((b - a) / (1000*60*60*24));
}
function todayISO(){
  const d = new Date();
  const tzOffset = d.getTimezoneOffset() * 60000;
  return new Date(d - tzOffset).toISOString().slice(0,10);
}
function addDaysISO(baseISO, days){
  const d = new Date(baseISO); d.setDate(d.getDate() + days);
  const tzOffset = d.getTimezoneOffset() * 60000;
  return new Date(d - tzOffset).toISOString().slice(0,10);
}
function escapeHtml(str){
  return String(str||"")
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;")
    .replaceAll("'","&#039;");
}
function setError(id, msg){
  const el = $(id);
  if(!el) return;
  if(!msg){
    el.textContent = "";
    el.style.display = "none";
    return;
  }
  el.textContent = msg;
  el.style.display = "block";
}
function setButtonLoading(btnId, isLoading, loadingLabel){
  const btn = $(btnId);
  if(!btn) return;
  if(isLoading){
    btn.dataset.label = btn.textContent;
    btn.textContent = loadingLabel || "Procesando...";
    btn.disabled = true;
  } else {
    btn.textContent = btn.dataset.label || btn.textContent;
    btn.disabled = false;
  }
}
function validateDateRange(from, to){
  if(from && to){
    return new Date(from) <= new Date(to);
  }
  return true;
}
function fillSelect(selectEl, options, includeAll=false){
  selectEl.innerHTML = (includeAll ? `<option value="ALL">Todos</option>` : ``) +
    options.map(o=>`<option value="${o.value ?? o}">${o.label ?? o}</option>`).join("");
}
function sellerName(id){ return state.sellers.find(s=>String(s.id)===String(id))?.name ?? "—"; }
function segmentLabel(id){ return CATALOG.segments.find(s=>String(s.id)===String(id))?.nombre ?? "—"; }
function channelLabel(id){ return CATALOG.channels.find(s=>String(s.id)===String(id))?.nombre ?? "—"; }
function winReasonLabel(id){ return CATALOG.winReasons.find(s=>String(s.id)===String(id))?.nombre ?? ""; }
function lossReasonLabel(id){ return CATALOG.lostReasons.find(s=>String(s.id)===String(id))?.nombre ?? ""; }

function statusPill(status){
  if(status==="WON") return `<span class="pill ok">✅ ${STATUS_LABEL[status]}</span>`;
  if(status==="LOST") return `<span class="pill bad">⛔ ${STATUS_LABEL[status]}</span>`;
  if(status==="EXPIRED") return `<span class="pill warn">⌛ ${STATUS_LABEL[status]}</span>`;
  if(status==="NEGOTIATION") return `<span class="pill brand">🧩 ${STATUS_LABEL[status]}</span>`;
  return `<span class="pill">🟦 ${STATUS_LABEL[status]}</span>`;
}

async function apiFetch(path, {method="GET", body=null} = {}){
  const res = await fetch(API_BASE + path, {
    method,
    headers: {
      "Content-Type": "application/json",
      "Accept": "application/json",
      "X-CSRF-TOKEN": CSRF_TOKEN
    },
    credentials: "same-origin",
    body: body ? JSON.stringify(body) : null
  });
  if(!res.ok){
    const text = await res.text();
    throw new Error(text || "Error en API");
  }
  return res.json();
}

function hydratePayload(payload){
  state.auth.role = payload.auth?.role ?? "SELLER";
  state.auth.sellerId = payload.auth?.sellerId ?? null;
  state.auth.userId = payload.auth?.userId ?? null;
  state.auth.userName = payload.auth?.userName ?? null;

  state.sellers = (payload.sellers || []).map(s => ({ id: s.id, name: s.name, goal: s.goal ?? 0 }));
  state.quotes = payload.quotes || [];
  state.invoices = payload.invoices || [];

  CATALOG.segments = payload.catalog?.segments || [];
  CATALOG.channels = payload.catalog?.channels || [];
  CATALOG.winReasons = payload.catalog?.winReasons || [];
  CATALOG.lostReasons = payload.catalog?.lossReasons || [];

  const period = payload.period || {};
  state.period = {
    from: period.from || null,
    to: period.to || null,
    label: period.label || null
  };
  state.filters = {
    sellerId:"ALL",
    from: state.period.from,
    to: state.period.to,
    channel:"ALL",
    segment:"ALL"
  };
  $("periodLabel").textContent = state.period.label ? `Periodo: ${state.period.label}` : "";

  initFilterControls();
  applyRoleToUI();
  state.ui.quotesPage = 1;
  state.ui.invoicesPage = 1;
  renderAll();
}

async function loadBootstrap(params = {}){
  const qs = new URLSearchParams(params).toString();
  const url = qs ? `/bootstrap?${qs}` : "/bootstrap";
  const payload = await apiFetch(url);
  hydratePayload(payload);
}

/* ========= Roles (Manager vs Seller) ========= */
function isManager(){ return state.auth.role === "MANAGER"; }
function effectiveSellerId(){
  return isManager() ? state.filters.sellerId : state.auth.sellerId;
}
function canEditSellerField(){
  return false; // vendedor forzado
}
function canSeeVendorsView(){ return isManager(); }

function applyRoleToUI(){
  const navVend = $("navVendedores");
  navVend.disabled = !canSeeVendorsView();

  if(!canSeeVendorsView() && state.ui.view === "vendedores"){
    setView("dashboard");
    document.querySelectorAll(".nav button").forEach(b=>b.classList.remove("active"));
    document.querySelector(`.nav button[data-view="dashboard"]`).classList.add("active");
  }

  $("scoreboardTitle").textContent = isManager() ? "Scoreboard de vendedores" : "Mi desempeño";
  $("scoreboardHint").textContent = isManager() ? "Ranking por $ facturado y conversión" : "KPIs personales para tomar decisiones";
  $("quotesTitle").textContent = isManager() ? "Listado de cotizaciones" : "Mis cotizaciones";
  $("invoicesTitle").textContent = isManager() ? "Facturas" : "Mis facturas";

  initFilterControls();

  const userNameLabel = $("userNameLabel");
  const userRoleLabel = $("userRoleLabel");
  if (userNameLabel) {
    userNameLabel.textContent = state.auth.userName ? `Usuario: ${state.auth.userName}` : "Usuario: —";
  }
  if (userRoleLabel) {
    userRoleLabel.textContent = isManager() ? "Rol: Administrador" : "Rol: Vendedor";
  }
}

/* ========= Navegación ========= */
const navButtons = Array.from(document.querySelectorAll(".nav button[data-view]"));
navButtons.forEach(btn=>{
  btn.addEventListener("click", ()=>{
    if(btn.disabled) return;
    navButtons.forEach(b=>b.classList.remove("active"));
    btn.classList.add("active");
    setView(btn.dataset.view);
  });
});
function setView(view){
  state.ui.view = view;
  const titles = {
    dashboard: ["Dashboard", "Conversión basada en facturación (Factura = Venta). La venta puede o no nacer de una cotización."],
    cotizaciones: ["Cotizaciones", "Operación diaria: seguimiento, aging, motivos win/loss y vínculo a factura."],
    pipeline: ["Pipeline", "Embudo por estatus: abierta, negociación, facturada, perdida/expirada."],
    facturas: ["Facturas", "Venta = Factura. Puede venir de cotización o ser directa."],
    vendedores: ["Vendedores", "Vista gerencial: desempeño y control comercial por vendedor."],
  };
  $("viewTitle").textContent = titles[view][0];
  $("viewSubtitle").textContent = titles[view][1];

  document.querySelectorAll(".view").forEach(v=>v.style.display="none");
  $("view-"+view).style.display = "block";

  renderAll();
}

/* ========= Filtros ========= */
function initFilterControls(){
  const fs = $("fSeller");
  fs.innerHTML = `<option value="ALL">Todos</option>` + state.sellers.map(s=>`<option value="${s.id}">${s.name}</option>`).join("");

  const fc = $("fChannel");
  fc.innerHTML = `<option value="ALL">Todos</option>` + CATALOG.channels.map(c=>`<option value="${c.id}">${c.nombre}</option>`).join("");

  const fseg = $("fSegment");
  fseg.innerHTML = `<option value="ALL">Todos</option>` + CATALOG.segments.map(s=>`<option value="${s.id}">${s.nombre}</option>`).join("");

  $("fFrom").value = state.filters.from ?? "";
  $("fTo").value = state.filters.to ?? "";
  fs.value = state.filters.sellerId;
  fc.value = state.filters.channel;
  fseg.value = state.filters.segment;

  const sellerFilterBlock = $("sellerFilterBlock");
  if (sellerFilterBlock) {
    sellerFilterBlock.style.display = state.sellers.length > 1 ? "block" : "none";
  }

  if(!isManager()){
    fs.value = state.auth.sellerId;
    fs.disabled = true;
  } else {
    fs.disabled = state.sellers.length <= 1;
  }
}

$("btnApplyFilters").addEventListener("click", ()=>{
  setError("mError", "");
  setError("nqError", "");
  setError("niError", "");
  setError("fError", "");
  state.filters.sellerId = $("fSeller").value;
  state.filters.from = $("fFrom").value || null;
  state.filters.to = $("fTo").value || null;
  state.filters.channel = $("fChannel").value;
  state.filters.segment = $("fSegment").value;
  if(!validateDateRange(state.filters.from, state.filters.to)){
    setError("fError", "El rango de fechas no es válido.");
    return;
  }
  if (state.filters.from && state.filters.to && (state.filters.from !== state.period.from || state.filters.to !== state.period.to)) {
    loadBootstrap({ from: state.filters.from, to: state.filters.to }).catch(err => {
      alert("No se pudo recargar el periodo: " + err.message);
    });
    return;
  }
  state.ui.quotesPage = 1;
  state.ui.invoicesPage = 1;
  renderAll();
});

$("btnResetFilters").addEventListener("click", ()=>{
  state.filters = { sellerId:"ALL", from: state.period.from, to: state.period.to, channel:"ALL", segment:"ALL" };
  setError("fError", "");
  state.ui.quotesPage = 1;
  state.ui.invoicesPage = 1;
  initFilterControls();
  renderAll();
});

$("btnSeed").addEventListener("click", async ()=>{
  await loadBootstrap();
});

/* ========= Selectores por filtros + rol ========= */
function filteredQuotes(){
  const effSeller = effectiveSellerId();
  return state.quotes.filter(q=>{
    if(effSeller !== "ALL" && String(q.sellerId) !== String(effSeller)) return false;
    if(state.filters.channel !== "ALL" && String(q.channelId) !== String(state.filters.channel)) return false;
    if(state.filters.segment !== "ALL" && String(q.segmentId) !== String(state.filters.segment)) return false;
    const from = state.filters.from ? new Date(state.filters.from) : null;
    const to = state.filters.to ? new Date(state.filters.to) : null;
    const d = new Date(q.createdAt);
    if(from && d < from) return false;
    if(to && d > to) return false;
    return true;
  });
}

function filteredInvoices(){
  const effSeller = effectiveSellerId();
  return state.invoices.filter(inv=>{
    if(effSeller !== "ALL" && String(inv.sellerId) !== String(effSeller)) return false;
    if(state.filters.channel !== "ALL" && String(inv.channelId) !== String(state.filters.channel)) return false;
    if(state.filters.segment !== "ALL" && String(inv.segmentId) !== String(state.filters.segment)) return false;

    const from = state.filters.from ? new Date(state.filters.from) : null;
    const to = state.filters.to ? new Date(state.filters.to) : null;
    const d = new Date(inv.issuedAt);
    if(from && d < from) return false;
    if(to && d > to) return false;
    return true;
  });
}

/* ========= KPIs (Factura = Venta) ========= */
function computeKPIs(quotes, invoices){
  const totalInvoiced = invoices.reduce((a,i)=>a+i.total,0);
  const invoicedFromQuotes = invoices.filter(i=>!!i.quoteId);
  const invoicedFromQuotesValue = invoicedFromQuotes.reduce((a,i)=>a+i.total,0);
  const invoicedDirect = invoices.filter(i=>!i.quoteId);
  const invoicedDirectValue = invoicedDirect.reduce((a,i)=>a+i.total,0);

  const totalQuotes = quotes.length;
  const totalQuoted = quotes.reduce((a,q)=>a+q.total,0);

  const invoicedQuoteIds = new Set(invoicedFromQuotes.map(i=>i.quoteId));
  const invoicedQuotesCount = Array.from(invoicedQuoteIds).length;

  const conversion = totalQuotes ? (invoicedQuotesCount / totalQuotes) : 0;
  const weighted = totalQuoted ? (invoicedFromQuotesValue / totalQuoted) : 0;

  const cycles = invoicedFromQuotes.map(inv=>{
    const q = quotes.find(x=>x.id===inv.quoteId);
    if(!q) return null;
    return Math.max(0, daysBetween(q.createdAt, inv.issuedAt));
  }).filter(x=>x!==null);
  const avgCycle = cycles.length ? cycles.reduce((a,b)=>a+b,0)/cycles.length : 0;

  const paidPctWeighted = totalInvoiced ? (invoices.reduce((a,i)=>a + (i.paidPct||0)*i.total,0) / totalInvoiced) : 0;
  const marginPctWeighted = totalInvoiced ? (invoices.reduce((a,i)=>a + (i.marginPct||0)*i.total,0) / totalInvoiced) : 0;

  const avgDiscount = quotes.length ? quotes.reduce((a,q)=>a+(q.discountPct||0),0)/quotes.length : 0;

  const openCount = quotes.filter(q=>q.status==="OPEN" || q.status==="NEGOTIATION").length;
  const lostCount = quotes.filter(q=>q.status==="LOST" || q.status==="EXPIRED").length;

  return {
    totalQuotes, totalQuoted,
    totalInvoiced, invoicedFromQuotesValue, invoicedDirectValue,
    invoicedQuotesCount, openCount, lostCount,
    conversion, weighted,
    avgCycle, avgDiscount,
    paidPctWeighted, marginPctWeighted
  };
}

function lossReasons(quotes){
  const lost = quotes.filter(q=>q.status==="LOST" || q.status==="EXPIRED");
  const map = new Map();
  for(const q of lost){
    const key = q.lostReasonId || "(Sin motivo)";
    const label = q.lostReasonId ? lossReasonLabel(q.lostReasonId) : "(Sin motivo)";
    const prev = map.get(key) || {reason:label, count:0, amount:0};
    prev.count += 1;
    prev.amount += q.total;
    map.set(key, prev);
  }
  return Array.from(map.values()).sort((a,b)=>b.amount-a.amount);
}

/* ========= Render ========= */
function renderAll(){
  applyRoleToUI();

  const view = state.ui.view;
  if(view==="dashboard") renderDashboard();
  if(view==="cotizaciones") renderQuotesTable();
  if(view==="pipeline") renderPipeline();
  if(view==="facturas") renderInvoices();
  if(view==="vendedores") renderVendors();
}

function renderDashboard(){
  const quotes = filteredQuotes();
  const invoices = filteredInvoices();
  const k = computeKPIs(quotes, invoices);

  $("kpiGrid").innerHTML = `
    <div class="card kpi">
      <div class="label">Cotizaciones</div>
      <div class="value">${k.totalQuotes}</div>
      <div class="hint">${k.openCount} abiertas · ${k.invoicedQuotesCount} facturadas · ${k.lostCount} perdidas/expiradas</div>
    </div>

    <div class="card kpi">
      <div class="label">$ Cotizado</div>
      <div class="value">${money(k.totalQuoted)}</div>
      <div class="hint">Base para conversión ponderada (desde cotizaciones)</div>
    </div>

    <div class="card kpi">
      <div class="label">$ Facturado (total)</div>
      <div class="value">${money(k.totalInvoiced)}</div>
      <div class="hint">${money(k.invoicedFromQuotesValue)} desde cotizaciones · ${money(k.invoicedDirectValue)} directo</div>
    </div>

    <div class="card kpi">
      <div class="label">Conversión (por facturación)</div>
      <div class="value">${pct(k.conversion)}</div>
      <div class="hint">Cotizaciones facturadas / cotizaciones totales</div>
    </div>

    <div class="card kpi">
      <div class="label">Conversión ponderada</div>
      <div class="value">${pct(k.weighted)}</div>
      <div class="hint">$ facturado desde cotizaciones / $ cotizado</div>
    </div>

    <div class="card kpi">
      <div class="label">Ciclo promedio</div>
      <div class="value">${k.avgCycle.toFixed(1)} días</div>
      <div class="hint">De cotización a emisión de factura</div>
    </div>

    <div class="card kpi">
      <div class="label">Descuento promedio (cotizaciones)</div>
      <div class="value">${k.avgDiscount.toFixed(1)}%</div>
      <div class="hint">Control de calidad comercial</div>
    </div>

    <div class="card kpi">
      <div class="label">Margen (ponderado)</div>
      <div class="value">${pct(k.marginPctWeighted)}</div>
      <div class="hint">Requiere margen estimado por factura/paquete</div>
    </div>

    <div class="card kpi">
      <div class="label">Cobranza (ponderada)</div>
      <div class="value">${pct(k.paidPctWeighted)}</div>
      <div class="hint">% cobrado sobre facturado</div>
    </div>

    <div class="card kpi">
      <div class="label">Ventas directas</div>
      <div class="value">${money(k.invoicedDirectValue)}</div>
      <div class="hint">Facturas sin cotización (no afectan la conversión)</div>
    </div>
  `;

  const effSeller = effectiveSellerId();
  const allQuotes = filteredQuotes();
  const allInvoices = filteredInvoices();

  if(isManager()){
    const bySeller = state.sellers.map(s=>{
      const qs = allQuotes.filter(q=>String(q.sellerId)===String(s.id));
      const inv = allInvoices.filter(i=>String(i.sellerId)===String(s.id));
      const ks = computeKPIs(qs, inv);
      return {seller:s, ...ks};
    }).sort((a,b)=> b.totalInvoiced - a.totalInvoiced);

    $("sellerTableWrap").innerHTML = `
      <table>
        <thead>
          <tr>
            <th>Vendedor</th>
            <th>$ Facturado</th>
            <th>Conversión</th>
            <th>Ponderada</th>
            <th>Ciclo</th>
            <th>Descuento</th>
          </tr>
        </thead>
        <tbody>
          ${bySeller.map(r=>{
            return `
              <tr>
                <td><strong>${r.seller.name}</strong><div class="mini">${r.totalQuotes} cotizaciones · ${money(r.totalQuoted)}</div></td>
                <td><strong>${money(r.totalInvoiced)}</strong><div class="mini">${money(r.invoicedDirectValue)} directo</div></td>
                <td>${pct(r.conversion)}</td>
                <td>${pct(r.weighted)}</td>
                <td>${r.avgCycle.toFixed(1)} d</td>
                <td>${r.avgDiscount.toFixed(1)}%</td>
              </tr>
            `;
          }).join("")}
        </tbody>
      </table>
    `;
  } else {
    const seller = state.sellers.find(s=>String(s.id)===String(effSeller));
    const ks = computeKPIs(allQuotes, allInvoices);

    $("sellerTableWrap").innerHTML = `
      <div class="card" style="box-shadow:none; background:rgba(11,18,32,.55);">
        <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start;">
          <div>
            <div class="mini">Vendedor</div>
            <div style="font-size:16px; font-weight:800; margin-top:6px;">${seller?.name ?? "—"}</div>
            <div class="mini" style="margin-top:8px;">${ks.totalQuotes} cotizaciones · ${ks.invoicedQuotesCount} facturadas</div>
          </div>
          <div style="text-align:right;">
            <div class="mini">$ Facturado</div>
            <div style="font-size:18px; font-weight:900; margin-top:6px;">${money(ks.totalInvoiced)}</div>
            <div class="mini" style="margin-top:8px;">${money(ks.invoicedDirectValue)} directo</div>
          </div>
        </div>

        <div class="row" style="margin-top:12px;">
          <div class="card" style="box-shadow:none; background:rgba(16,26,46,.35);">
            <div class="mini">Conversión</div>
            <div style="font-size:18px; font-weight:900; margin-top:6px;">${pct(ks.conversion)}</div>
          </div>
          <div class="card" style="box-shadow:none; background:rgba(16,26,46,.35);">
            <div class="mini">Ciclo</div>
            <div style="font-size:18px; font-weight:900; margin-top:6px;">${ks.avgCycle.toFixed(1)} d</div>
          </div>
        </div>
      </div>
    `;
  }

  const lr = lossReasons(allQuotes).slice(0,6);
  if(lr.length===0){
    $("lossReasonsWrap").innerHTML = `<p class="muted">No hay pérdidas en el rango seleccionado.</p>`;
  } else {
    const max = Math.max(...lr.map(x=>x.amount));
    $("lossReasonsWrap").innerHTML = lr.map(x=>{
      const w = max ? (x.amount/max)*100 : 0;
      return `
        <div style="margin:10px 0;">
          <div style="display:flex; justify-content:space-between; gap:10px;">
            <div><strong>${x.reason}</strong> <span class="mini">(${x.count})</span></div>
            <div class="mini">${money(x.amount)}</div>
          </div>
          <div class="bar" style="margin-top:8px;"><div style="width:${w.toFixed(1)}%"></div></div>
        </div>
      `;
    }).join("");
  }

  const latest = [...allQuotes].sort((a,b)=> (b.createdAt.localeCompare(a.createdAt))).slice(0,8);
  $("latestQuotesWrap").innerHTML = `
    <table>
      <thead>
        <tr>
          <th>Folio</th>
          <th>Cliente</th>
          <th>Vendedor</th>
          <th>Canal</th>
          <th>Segmento</th>
          <th>Monto</th>
          <th>Estatus</th>
          <th>Factura</th>
          <th>Creación</th>
        </tr>
      </thead>
      <tbody>
        ${latest.map(q=>{
          const inv = q.invoiceId ? state.invoices.find(i=>i.id===q.invoiceId) : null;
          return `
            <tr data-open="${q.id}" style="cursor:pointer;">
              <td class="mini">${q.id}</td>
              <td><strong>${escapeHtml(q.client || "")}</strong><div class="mini">${q.notes ? escapeHtml(q.notes).slice(0,50) : ""}</div></td>
              <td>${sellerName(q.sellerId)}</td>
              <td><span class="pill">${channelLabel(q.channelId)}</span></td>
              <td><span class="pill">${segmentLabel(q.segmentId)}</span></td>
              <td><strong>${money(q.total)}</strong></td>
              <td>${statusPill(q.status)}</td>
              <td>${inv ? `<span class="pill ok">${inv.id}</span>` : `<span class="pill">—</span>`}</td>
              <td class="mini">${q.createdAt}</td>
            </tr>
          `;
        }).join("")}
      </tbody>
    </table>
  `;

  $("latestQuotesWrap").querySelectorAll("[data-open]").forEach(tr=>{
    tr.addEventListener("click", ()=> openQuoteModal(tr.dataset.open));
  });
}

function renderQuotesTable(){
  const quotes = filteredQuotes().sort((a,b)=> b.createdAt.localeCompare(a.createdAt));
  const pageSize = 20;
  const totalPages = Math.max(1, Math.ceil(quotes.length / pageSize));
  const page = Math.min(state.ui.quotesPage, totalPages);
  const start = (page - 1) * pageSize;
  const paged = quotes.slice(start, start + pageSize);
  $("quotesTableWrap").innerHTML = `
    <table>
      <thead>
        <tr>
          <th>Folio</th>
          <th>Cliente</th>
          <th>Vendedor</th>
          <th>Canal</th>
          <th>Segmento</th>
          <th>Monto</th>
          <th>Descuento</th>
          <th>Estatus</th>
          <th>Factura</th>
          <th>Último seguimiento</th>
          <th>Creación</th>
        </tr>
      </thead>
      <tbody>
        ${paged.map(q=>{
          const lastAct = q.activities?.length ? q.activities[q.activities.length-1] : null;
          const lastTxt = lastAct ? `${lastAct.type} · ${lastAct.date}` : "—";
          const inv = q.invoiceId ? state.invoices.find(i=>i.id===q.invoiceId) : null;
          return `
            <tr data-open="${q.id}" style="cursor:pointer;">
              <td class="mini">${q.id}</td>
              <td><strong>${escapeHtml(q.client || "")}</strong><div class="mini">${q.notes ? escapeHtml(q.notes).slice(0,40) : ""}</div></td>
              <td>${sellerName(q.sellerId)}</td>
              <td><span class="pill">${channelLabel(q.channelId)}</span></td>
              <td><span class="pill">${segmentLabel(q.segmentId)}</span></td>
              <td><strong>${money(q.total)}</strong></td>
              <td>${(q.discountPct||0).toFixed(1)}%</td>
              <td>${statusPill(q.status)}</td>
              <td>${inv ? `<span class="pill ok">${inv.id}</span>` : `<span class="pill">—</span>`}</td>
              <td class="mini">${lastTxt}</td>
              <td class="mini">${q.createdAt}</td>
            </tr>
          `;
        }).join("")}
      </tbody>
    </table>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
      <div class="mini">Página ${page} de ${totalPages} · ${quotes.length} registros</div>
      <div class="row" style="max-width:220px;">
        <button class="btn" data-page="prev" ${page<=1 ? "disabled":""}>Anterior</button>
        <button class="btn" data-page="next" ${page>=totalPages ? "disabled":""}>Siguiente</button>
      </div>
    </div>
  `;
  $("quotesTableWrap").querySelectorAll("[data-open]").forEach(tr=>{
    tr.addEventListener("click", ()=> openQuoteModal(tr.dataset.open));
  });
  $("quotesTableWrap").querySelectorAll("button[data-page]").forEach(btn=>{
    btn.addEventListener("click", ()=>{
      if(btn.dataset.page === "prev") state.ui.quotesPage = Math.max(1, page - 1);
      if(btn.dataset.page === "next") state.ui.quotesPage = Math.min(totalPages, page + 1);
      renderQuotesTable();
    });
  });
}

function renderPipeline(){
  const quotes = filteredQuotes();
  const cols = [
    {key:"OPEN", title:"Abiertas", icon:"🟦"},
    {key:"NEGOTIATION", title:"Negociación", icon:"🧩"},
    {key:"WON", title:"Facturadas", icon:"✅"},
    {key:"LOST", title:"Perdidas/Expiradas", icon:"⛔"}
  ];

  function card(q){
    const age = Math.max(0, daysBetween(q.createdAt, todayISO()));
    const ageChip = age<=7 ? "ok" : age<=15 ? "warn" : age<=30 ? "warn" : "bad";
    const p = q.probability ?? 0.2;
    const inv = q.invoiceId ? state.invoices.find(i=>i.id===q.invoiceId) : null;
    return `
      <div class="qcard" data-open="${q.id}">
        <div class="qtop">
          <div>
            <div class="qname">${escapeHtml(q.client || "")}</div>
            <div class="qmeta">${sellerName(q.sellerId)} · ${channelLabel(q.channelId)} · ${segmentLabel(q.segmentId)}</div>
          </div>
          <div class="qmoney">${money(q.total)}</div>
        </div>
        <div class="qchips">
          <span class="chip brand">P: ${(p*100).toFixed(0)}%</span>
          <span class="chip ${ageChip}">Aging: ${age}d</span>
          <span class="chip">Desc: ${(q.discountPct||0).toFixed(1)}%</span>
          ${inv ? `<span class="chip ok">Factura: ${inv.id}</span>` : ``}
        </div>
      </div>
    `;
  }

  $("pipelineBoard").innerHTML = cols.map(c=>{
    const list = quotes.filter(q=>{
      if(c.key==="LOST") return (q.status==="LOST" || q.status==="EXPIRED");
      return q.status===c.key;
    }).sort((a,b)=> b.total - a.total);

    const sum = list.reduce((a,q)=>a+q.total,0);
    return `
      <div class="col">
        <h4>${c.icon} ${c.title} <span class="mini">(${list.length})</span></h4>
        <div class="mini" style="margin-bottom:10px;">Total cotizado: ${money(sum)}</div>
        ${list.map(card).join("") || `<p class=\"muted\">Sin registros.</p>`}
      </div>
    `;
  }).join("");

  $("pipelineBoard").querySelectorAll("[data-open]").forEach(el=>{
    el.addEventListener("click", ()=> openQuoteModal(el.dataset.open));
  });

  const open = quotes.filter(q=>q.status==="OPEN" || q.status==="NEGOTIATION");
  const buckets = [
    {label:"0–7", min:0, max:7},
    {label:"8–15", min:8, max:15},
    {label:"16–30", min:16, max:30},
    {label:"31–60", min:31, max:60},
    {label:"+60", min:61, max:9999},
  ];
  const counts = buckets.map(b=>{
    const cCount = open.filter(q=>{
      const age = Math.max(0, daysBetween(q.createdAt, todayISO()));
      return age>=b.min && age<=b.max;
    }).length;
    return {b, cCount};
  });
  const maxC = Math.max(1, ...counts.map(x=>x.cCount));
  $("agingWrap").innerHTML = counts.map(x=>{
    const w = (x.cCount/maxC)*100;
    return `
      <div style="margin:10px 0;">
        <div style="display:flex; justify-content:space-between;">
          <strong>${x.b.label} días</strong>
          <span class="mini">${x.cCount} cotizaciones</span>
        </div>
        <div class="bar" style="margin-top:8px;"><div style="width:${w.toFixed(1)}%"></div></div>
      </div>
    `;
  }).join("");

  const invAll = filteredInvoices();
  const k = computeKPIs(quotes, invAll);
  $("weightedWrap").innerHTML = `
    <div class="card" style="box-shadow:none; background:rgba(11,18,32,.55);">
      <div class="mini">Facturado desde cotizaciones</div>
      <div style="font-size:20px; font-weight:800; margin-top:6px;">${money(k.invoicedFromQuotesValue)}</div>
      <div class="mini" style="margin-top:10px;">Cotizado</div>
      <div style="font-size:16px; font-weight:700; margin-top:6px;">${money(k.totalQuoted)}</div>
      <div style="margin-top:12px;">
        <div class="bar"><div style="width:${(k.weighted*100).toFixed(1)}%"></div></div>
        <div class="mini" style="margin-top:8px;">Conversión ponderada: ${pct(k.weighted)}</div>
      </div>
      <div class="mini" style="margin-top:10px;">Nota: ventas directas (facturas sin cotización) no entran aquí.</div>
    </div>
  `;
}

function renderInvoices(){
  const invoices = filteredInvoices().sort((a,b)=> b.issuedAt.localeCompare(a.issuedAt));
  const pageSize = 20;
  const totalPages = Math.max(1, Math.ceil(invoices.length / pageSize));
  const page = Math.min(state.ui.invoicesPage, totalPages);
  const start = (page - 1) * pageSize;
  const paged = invoices.slice(start, start + pageSize);
  $("invoicesWrap").innerHTML = `
    <table>
      <thead>
        <tr>
          <th>Factura</th>
          <th>Cliente</th>
          <th>Vendedor</th>
          <th>Canal</th>
          <th>Segmento</th>
          <th>Fecha</th>
          <th>Total</th>
          <th>Origen</th>
          <th>Cobrado</th>
          <th>Margen</th>
        </tr>
      </thead>
      <tbody>
        ${paged.map(inv=>{
          const fromQuote = inv.quoteId ? `Cotización ${inv.quoteId}` : "Directa (sin cotización)";
          return `
            <tr>
              <td><strong>${inv.id}</strong><div class="mini">${inv.quoteId ? `<span class=\"pill ok\">Vinculada</span>` : `<span class=\"pill\">Directa</span>`}</div></td>
              <td><strong>${escapeHtml(inv.client||"")}</strong><div class="mini">${escapeHtml(inv.notes||"")}</div></td>
              <td>${sellerName(inv.sellerId)}</td>
              <td><span class="pill">${channelLabel(inv.channelId)}</span></td>
              <td><span class="pill">${segmentLabel(inv.segmentId)}</span></td>
              <td class="mini">${inv.issuedAt}</td>
              <td><strong>${money(inv.total)}</strong></td>
              <td class="mini">${fromQuote}</td>
              <td>${((inv.paidPct||0)*100).toFixed(0)}%</td>
              <td>${((inv.marginPct||0)*100).toFixed(0)}%</td>
            </tr>
          `;
        }).join("")}
      </tbody>
    </table>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
      <div class="mini">Página ${page} de ${totalPages} · ${invoices.length} registros</div>
      <div class="row" style="max-width:220px;">
        <button class="btn" data-page="prev" ${page<=1 ? "disabled":""}>Anterior</button>
        <button class="btn" data-page="next" ${page>=totalPages ? "disabled":""}>Siguiente</button>
      </div>
    </div>
  `;
  $("invoicesWrap").querySelectorAll("button[data-page]").forEach(btn=>{
    btn.addEventListener("click", ()=>{
      if(btn.dataset.page === "prev") state.ui.invoicesPage = Math.max(1, page - 1);
      if(btn.dataset.page === "next") state.ui.invoicesPage = Math.min(totalPages, page + 1);
      renderInvoices();
    });
  });
}

function renderVendors(){
  if(!isManager()){
    $("vendorsWrap").innerHTML = `<p class="muted">Esta vista solo está disponible para Administrador/Gerente.</p>`;
    return;
  }

  const allQ = filteredQuotes();
  const allI = filteredInvoices();

  const rows = state.sellers.map(s=>{
    const qs = allQ.filter(q=>String(q.sellerId)===String(s.id));
    const inv = allI.filter(i=>String(i.sellerId)===String(s.id));
    const k = computeKPIs(qs, inv);
    return {s, k, quotes: qs.length};
  }).sort((a,b)=> b.k.totalInvoiced - a.k.totalInvoiced);

  $("vendorsWrap").innerHTML = `
    <table>
      <thead>
        <tr>
          <th>Vendedor</th>
          <th>$ Facturado</th>
          <th>Conversión</th>
          <th>Ponderada</th>
          <th>Ciclo</th>
          <th>Margen</th>
          <th>Cobranza</th>
          <th>Directo</th>
        </tr>
      </thead>
      <tbody>
        ${rows.map(r=>`
          <tr>
            <td><strong>${r.s.name}</strong><div class="mini">${r.quotes} cotizaciones</div></td>
            <td><strong>${money(r.k.totalInvoiced)}</strong></td>
            <td>${pct(r.k.conversion)}</td>
            <td>${pct(r.k.weighted)}</td>
            <td>${r.k.avgCycle.toFixed(1)} d</td>
            <td>${pct(r.k.marginPctWeighted)}</td>
            <td>${pct(r.k.paidPctWeighted)}</td>
            <td>${money(r.k.invoicedDirectValue)}</td>
          </tr>
        `).join("")}
      </tbody>
    </table>
  `;
}

/* ========= Modal Cotización ========= */
const modalBackdrop = $("modalBackdrop");
function openQuoteModal(quoteId){
  const q = state.quotes.find(x=>String(x.id)===String(quoteId));
  if(!q) return;

  if(!isManager() && String(q.sellerId) !== String(state.auth.sellerId)) return;

  state.ui.selectedQuoteId = quoteId;

  fillSelect($("mSegment"), CATALOG.segments.map(s=>({value:s.id,label:s.nombre})));
  fillSelect($("mChannel"), CATALOG.channels.map(c=>({value:c.id,label:c.nombre})));
  fillSelect($("mLostReason"), [{value:"", label:"(Sin motivo)"}, ...CATALOG.lostReasons.map(x=>({value:x.id,label:x.nombre}))]);
  fillSelect($("mWinReason"), [{value:"", label:"(Sin motivo)"}, ...CATALOG.winReasons.map(x=>({value:x.id,label:x.nombre}))]);

  $("mSeller").innerHTML = state.sellers.map(s=>`<option value="${s.id}">${s.name}</option>`).join("");

  $("mTitle").textContent = `Cotización ${q.id}`;
  $("mSub").textContent = `${q.createdAt} · ${sellerName(q.sellerId)} · ${channelLabel(q.channelId)} · ${segmentLabel(q.segmentId)}`;
  $("mClient").value = q.client || "";
  $("mSegment").value = q.segmentId || "";
  $("mChannel").value = q.channelId || "";
  $("mSeller").value = q.sellerId || "";
  $("mTotal").value = q.total ?? 0;
  $("mDiscount").value = q.discountPct ?? 0;
  $("mStatus").value = q.status;
  $("mProb").value = String(q.probability ?? 0.2);
  $("mCloseEst").value = q.closeEst ?? "";
  $("mValidUntil").value = q.validUntil ?? "";
  $("mLostReason").value = q.lostReasonId ?? "";
  $("mWinReason").value = q.winReasonId ?? "";
  $("mNotes").value = q.notes ?? "";

  $("mActDate").value = todayISO();
  $("mActNextDate").value = addDaysISO(todayISO(), 2);

  renderModalActivities(q);
  renderModalInvoice(q);

  $("mFooterLeft").textContent = `Estatus: ${STATUS_LABEL[q.status]} · Prob: ${(q.probability*100).toFixed(0)}%`;
  const age = Math.max(0, daysBetween(q.createdAt, todayISO()));
  $("mFooterRight").textContent = `Aging: ${age} días · Descuento: ${(q.discountPct||0).toFixed(1)}%`;

  const hasInv = !!q.invoiceId;
  const generateBtn = $("btnGenerateInvoice");
  if (generateBtn) {
    generateBtn.disabled = hasInv;
  }

  modalBackdrop.style.display = "flex";
  modalBackdrop.setAttribute("aria-hidden","false");
}
function closeQuoteModal(){
  modalBackdrop.style.display = "none";
  modalBackdrop.setAttribute("aria-hidden","true");
  state.ui.selectedQuoteId = null;
}
$("btnCloseModal").addEventListener("click", closeQuoteModal);
modalBackdrop.addEventListener("click", (e)=>{ if(e.target === modalBackdrop) closeQuoteModal(); });

function renderModalActivities(q){
  const list = [...(q.activities||[])].reverse();
  if(list.length===0){
    $("mActivityList").innerHTML = `<p class="muted">Sin actividades.</p>`;
    return;
  }
  $("mActivityList").innerHTML = list.map(a=>`
    <div class="card" style="box-shadow:none; background:rgba(11,18,32,.55); margin-bottom:10px;">
      <div style="display:flex; justify-content:space-between; gap:10px;">
        <strong>${a.type}</strong>
        <span class="mini">${a.date}</span>
      </div>
      <div class="mini" style="margin-top:6px;">Resultado: ${escapeHtml(a.result || "—")}</div>
      <div class="mini" style="margin-top:6px;">Próxima: ${escapeHtml(a.next || "—")} · ${a.nextDate || "—"}</div>
    </div>
  `).join("");
}

function renderModalInvoice(q){
  const inv = q.invoiceId ? state.invoices.find(i=>i.id===q.invoiceId) : null;
  if(!inv){
    $("mInvoiceBox").innerHTML = `<p class="muted">Aún no hay factura vinculada.</p>`;
    return;
  }
  $("mInvoiceBox").innerHTML = `
    <div class="card" style="box-shadow:none; background:rgba(11,18,32,.55);">
      <div class="mini">Factura</div>
      <div style="display:flex; justify-content:space-between; gap:10px; margin-top:6px;">
        <div><strong>${inv.id}</strong> <span class="mini">(${inv.issuedAt})</span></div>
        <div><strong>${money(inv.total)}</strong></div>
      </div>
      <div class="qchips" style="margin-top:10px;">
        <span class="chip ok">Margen: ${((inv.marginPct||0)*100).toFixed(0)}%</span>
        <span class="chip brand">Cobrado: ${((inv.paidPct||0)*100).toFixed(0)}%</span>
        ${inv.winReasonId ? `<span class="chip ok">Win: ${escapeHtml(winReasonLabel(inv.winReasonId))}</span>` : ``}
      </div>
      <div class="mini" style="margin-top:10px;">${escapeHtml(inv.notes||"")}</div>
    </div>
  `;
}

async function saveModalToQuote(){
  const q = state.quotes.find(x=>String(x.id)===String(state.ui.selectedQuoteId));
  if(!q) return;
  setError("mError", "");

  if(!$("mClient").value.trim()){
    setError("mError", "El cliente es obligatorio.");
    return;
  }
  const totalVal = Number($("mTotal").value || 0);
  if(totalVal <= 0){
    setError("mError", "El monto cotizado debe ser mayor a 0.");
    return;
  }

  const payload = {
    client: $("mClient").value.trim() || q.client,
    segmentId: $("mSegment").value || null,
    channelId: $("mChannel").value || null,
    total: Number($("mTotal").value || 0),
    discountPct: Number($("mDiscount").value || 0),
    status: $("mStatus").value,
    probability: Number($("mProb").value || 0.2),
    closeEst: $("mCloseEst").value || null,
    validUntil: $("mValidUntil").value || null,
    lostReasonId: $("mLostReason").value || null,
    winReasonId: $("mWinReason").value || null,
    notes: $("mNotes").value || "",
  };

  try{
    setButtonLoading("btnSaveQuote", true, "Guardando...");
    const payloadResp = await apiFetch(`/quotes/${q.id}`, {method:"PUT", body: payload});
    hydratePayload(payloadResp);
    openQuoteModal(q.id);
  } catch (err) {
    setError("mError", "Error al guardar: " + err.message);
  } finally {
    setButtonLoading("btnSaveQuote", false);
  }
}
$("btnSaveQuote").addEventListener("click", saveModalToQuote);

$("btnAddActivity").addEventListener("click", async ()=>{
  const q = state.quotes.find(x=>String(x.id)===String(state.ui.selectedQuoteId));
  if(!q) return;
  setError("mActError", "");

  if(!$("mActDate").value){
    setError("mActError", "La fecha de la actividad es obligatoria.");
    return;
  }
  if(!$("mActResult").value.trim() && !$("mActNext").value.trim()){
    setError("mActError", "Captura un resultado o la próxima acción.");
    return;
  }

  const payload = {
    type: $("mActType").value,
    date: $("mActDate").value || todayISO(),
    result: $("mActResult").value.trim(),
    next: $("mActNext").value.trim(),
    nextDate: $("mActNextDate").value || null
  };

  try{
    setButtonLoading("btnAddActivity", true, "Agregando...");
    const payloadResp = await apiFetch(`/quotes/${q.id}/activities`, {method:"POST", body: payload});
    hydratePayload(payloadResp);
    openQuoteModal(q.id);
  } catch (err) {
    setError("mActError", "Error al agregar actividad: " + err.message);
  } finally {
    setButtonLoading("btnAddActivity", false);
  }
});

$("btnMarkLost").addEventListener("click", async ()=>{
  const q = state.quotes.find(x=>String(x.id)===String(state.ui.selectedQuoteId));
  if(!q) return;
  const payload = { status: "LOST", lostReasonId: $("mLostReason").value || null };
  try{
    setButtonLoading("btnMarkLost", true, "Guardando...");
    const payloadResp = await apiFetch(`/quotes/${q.id}`, {method:"PUT", body: payload});
    hydratePayload(payloadResp);
    openQuoteModal(q.id);
  } catch (err) {
    setError("mError", "Error al marcar perdida: " + err.message);
  } finally {
    setButtonLoading("btnMarkLost", false);
  }
});

$("btnExpire").addEventListener("click", async ()=>{
  const q = state.quotes.find(x=>String(x.id)===String(state.ui.selectedQuoteId));
  if(!q) return;
  const payload = { status: "EXPIRED" };
  try{
    setButtonLoading("btnExpire", true, "Guardando...");
    const payloadResp = await apiFetch(`/quotes/${q.id}`, {method:"PUT", body: payload});
    hydratePayload(payloadResp);
    openQuoteModal(q.id);
  } catch (err) {
    setError("mError", "Error al marcar expirada: " + err.message);
  } finally {
    setButtonLoading("btnExpire", false);
  }
});

const btnGenerateInvoice = $("btnGenerateInvoice");
if (btnGenerateInvoice) {
  btnGenerateInvoice.addEventListener("click", ()=>{
    const q = state.quotes.find(x=>String(x.id)===String(state.ui.selectedQuoteId));
    if(!q || q.invoiceId) return;
    openNewInvoiceModal({ quoteId: q.id });
  });
}

/* ========= Nueva Cotización ========= */
const newQuoteBackdrop = $("newQuoteBackdrop");
function closeNewQuote(){
  newQuoteBackdrop.style.display = "none";
  newQuoteBackdrop.setAttribute("aria-hidden","true");
}
const btnNewQuote = $("btnNewQuote");
if (btnNewQuote) {
  btnNewQuote.addEventListener("click", ()=>{
    $("nSeller").innerHTML = state.sellers.map(s=>`<option value="${s.id}">${s.name}</option>`).join("");
    fillSelect($("nSegment"), CATALOG.segments.map(s=>({value:s.id,label:s.nombre})));
    fillSelect($("nChannel"), CATALOG.channels.map(c=>({value:c.id,label:c.nombre})));

    const base = todayISO();
    $("nCloseEst").value = addDaysISO(base, 10);
    $("nValidUntil").value = addDaysISO(base, 14);

    $("nClient").value = "";
    $("nNotes").value = "";

    $("nSeller").value = state.auth.sellerId;

    newQuoteBackdrop.style.display = "flex";
    newQuoteBackdrop.setAttribute("aria-hidden","false");
  });
}
$("btnCloseNewQuote").addEventListener("click", closeNewQuote);
newQuoteBackdrop.addEventListener("click", (e)=>{ if(e.target === newQuoteBackdrop) closeNewQuote(); });

$("btnCreateQuote").addEventListener("click", async ()=>{
  setError("nqError", "");
  if(!$("nClient").value.trim()){
    setError("nqError", "El cliente es obligatorio.");
    return;
  }
  const totalVal = Number($("nTotal").value || 0);
  if(totalVal <= 0){
    setError("nqError", "El monto cotizado debe ser mayor a 0.");
    return;
  }
  const payload = {
    client: $("nClient").value.trim() || "Cliente sin nombre",
    segmentId: $("nSegment").value || null,
    channelId: $("nChannel").value || null,
    total: Number($("nTotal").value || 0),
    discountPct: Number($("nDiscount").value || 0),
    probability: Number($("nProb").value || 0.5),
    closeEst: $("nCloseEst").value || null,
    validUntil: $("nValidUntil").value || null,
    notes: $("nNotes").value || "",
  };
  try{
    setButtonLoading("btnCreateQuote", true, "Creando...");
    const payloadResp = await apiFetch(`/quotes`, {method:"POST", body: payload});
    closeNewQuote();
    hydratePayload(payloadResp);
  } catch (err) {
    setError("nqError", "Error al crear cotización: " + err.message);
  } finally {
    setButtonLoading("btnCreateQuote", false);
  }
});

/* ========= Nueva Factura ========= */
const newInvoiceBackdrop = $("newInvoiceBackdrop");
function closeNewInvoice(){
  newInvoiceBackdrop.style.display = "none";
  newInvoiceBackdrop.setAttribute("aria-hidden","true");
  newInvoiceBackdrop.dataset.fromQuoteId = "";
}
function openNewInvoiceModal({quoteId=null} = {}){
  const visibleQuotes = filteredQuotes()
    .filter(q => !q.invoiceId && (q.status==="OPEN" || q.status==="NEGOTIATION" || q.status==="WON"))
    .sort((a,b)=> b.createdAt.localeCompare(a.createdAt));

  const quoteOptions = [
    {value:"", label:"(Factura directa / sin cotización)"},
    ...visibleQuotes.map(q=>({value:q.id, label:`${q.id} · ${q.client} · ${money(q.total)} · ${sellerName(q.sellerId)}`}))
  ];
  fillSelect($("niQuote"), quoteOptions);

  $("niSeller").innerHTML = state.sellers.map(s=>`<option value="${s.id}">${s.name}</option>`).join("");
  fillSelect($("niSegment"), CATALOG.segments.map(s=>({value:s.id,label:s.nombre})));
  fillSelect($("niChannel"), CATALOG.channels.map(c=>({value:c.id,label:c.nombre})));
  fillSelect($("niWinReason"), [{value:"", label:"(Sin motivo)"}, ...CATALOG.winReasons.map(x=>({value:x.id,label:x.nombre}))]);

  $("niIssuedAt").value = todayISO();
  $("niNotes").value = "";
  $("niPaidPct").value = "20";
  $("niMarginPct").value = "40";

  if(quoteId){
    const q = state.quotes.find(x=>String(x.id)===String(quoteId));
    if(q){
      $("niQuote").value = q.id;
      $("niClient").value = q.client;
      $("niSeller").value = q.sellerId;
      $("niSegment").value = q.segmentId || "";
      $("niChannel").value = q.channelId || "";
      const net = q.total * (1 - (q.discountPct||0)/100);
      $("niTotal").value = String(net.toFixed(2));
      $("niWinReason").value = q.winReasonId || "";
      $("niNotes").value = "Factura vinculada a cotización " + q.id + ".";
    }
  } else {
    $("niQuote").value = "";
    $("niClient").value = "";
    $("niTotal").value = "25000";
    $("niWinReason").value = "";
  }

  $("niSeller").value = state.auth.sellerId;

  newInvoiceBackdrop.style.display = "flex";
  newInvoiceBackdrop.setAttribute("aria-hidden","false");
}
const btnNewInvoice = $("btnNewInvoice");
if (btnNewInvoice) {
  btnNewInvoice.addEventListener("click", ()=> openNewInvoiceModal({quoteId:null}));
}
$("btnCloseNewInvoice").addEventListener("click", closeNewInvoice);
newInvoiceBackdrop.addEventListener("click", (e)=>{ if(e.target === newInvoiceBackdrop) closeNewInvoice(); });

$("niQuote").addEventListener("change", ()=>{
  const quoteId = $("niQuote").value;
  if(!quoteId){
    $("niClient").value = "";
    $("niSegment").value = CATALOG.segments[0]?.id || "";
    $("niChannel").value = CATALOG.channels[0]?.id || "";
    $("niTotal").value = "25000";
    return;
  }
  const q = state.quotes.find(x=>String(x.id)===String(quoteId));
  if(!q) return;

  $("niClient").value = q.client;
  $("niSeller").value = state.auth.sellerId;
  $("niSegment").value = q.segmentId || "";
  $("niChannel").value = q.channelId || "";
  const net = q.total * (1 - (q.discountPct||0)/100);
  $("niTotal").value = String(net.toFixed(2));
});

$("btnCreateInvoice").addEventListener("click", async ()=>{
  const quoteId = $("niQuote").value || null;
  setError("niError", "");
  if(!$("niClient").value.trim()){
    setError("niError", "El cliente es obligatorio.");
    return;
  }
  const totalVal = Number($("niTotal").value || 0);
  if(totalVal <= 0){
    setError("niError", "El total de la factura debe ser mayor a 0.");
    return;
  }

  const payload = {
    quoteId: quoteId || null,
    client: $("niClient").value.trim() || "Cliente sin nombre",
    issuedAt: $("niIssuedAt").value || todayISO(),
    segmentId: $("niSegment").value || null,
    channelId: $("niChannel").value || null,
    total: Number($("niTotal").value || 0),
    paidPct: Math.max(0, Math.min(1, Number($("niPaidPct").value || 0)/100)),
    marginPct: Math.max(0, Math.min(1, Number($("niMarginPct").value || 0)/100)),
    winReasonId: $("niWinReason").value || null,
    notes: $("niNotes").value || ""
  };

  try{
    setButtonLoading("btnCreateInvoice", true, "Emitiendo...");
    const payloadResp = await apiFetch(`/invoices`, {method:"POST", body: payload});
    closeNewInvoice();
    hydratePayload(payloadResp);
  } catch (err) {
    setError("niError", "Error al emitir factura: " + err.message);
  } finally {
    setButtonLoading("btnCreateInvoice", false);
  }
});

/* ========= Init ========= */
loadBootstrap().catch(err => {
  alert("No se pudo cargar datos: " + err.message);
});
</script>
</div>
