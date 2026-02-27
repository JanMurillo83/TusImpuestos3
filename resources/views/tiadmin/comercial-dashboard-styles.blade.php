<style>
    html, body{height:100%;}
    body{margin:0;}
    #comercial-dashboard{
      --bg:#0b1220; --panel:#101a2e; --panel2:#0f1a33; --muted:#8fa3c7;
      --text:#e9f0ff; --line:#1f2c4a; --brand:#4ea1ff; --ok:#37d67a; --warn:#ffcc66; --bad:#ff5b5b;
      --shadow: 0 12px 24px rgba(0,0,0,.35);
      --radius:14px;
      --mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      --sans: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji","Segoe UI Emoji";
    }
    #comercial-dashboard *{box-sizing:border-box}
    #comercial-dashboard{margin:0; min-height:100vh; font-family:var(--sans); background:linear-gradient(180deg,#070c16 0%, #0b1220 100%); color:var(--text);}
    .app{display:grid; grid-template-columns: 300px 1fr; min-height:100vh;}
    .sidebar{
      border-right:1px solid var(--line);
      padding:18px 14px 24px;
      position:sticky; top:0; height:100vh;
      overflow-y:auto;
      background:linear-gradient(180deg,#091022 0%, #0b1220 100%);
    }
    .brand{
      display:flex; align-items:center; gap:10px;
      padding:10px 10px; border:1px solid var(--line); border-radius: var(--radius);
      background: rgba(16,26,46,.6); box-shadow: var(--shadow);
      margin-bottom:12px;
    }
    .logo{
      width:36px; height:36px; border-radius:10px;
      background: radial-gradient(circle at 30% 30%, #7cc4ff, #2b73ff 55%, #1a2a5a);
      border:1px solid rgba(255,255,255,.12);
    }
    .brand h1{font-size:14px; margin:0;}
    .brand p{margin:2px 0 0; font-size:12px; color:var(--muted)}
    .panel{
      margin-top:10px; padding:12px; border:1px solid var(--line);
      border-radius: var(--radius); background: rgba(16,26,46,.45);
    }
    .panel h3{margin:0 0 10px; font-size:12px; color:var(--muted); letter-spacing:.02em; text-transform:uppercase}
    #comercial-dashboard label{display:block; font-size:12px; color:var(--muted); margin:10px 0 6px;}
    #comercial-dashboard select,
    #comercial-dashboard input,
    #comercial-dashboard textarea{
      width:100%;
      padding:10px 10px;
      border-radius:12px;
      border:1px solid var(--line);
      background: rgba(11,18,32,.9);
      color: var(--text);
      outline:none;
      font-family: var(--sans);
    }
    #comercial-dashboard input[type="date"]{padding:9px 10px;}
    #comercial-dashboard textarea{min-height:86px; resize:vertical;}
    .row{display:flex; gap:10px; flex-wrap:wrap;}
    .row > *{flex:1; min-width:0}
    .nav{display:flex; flex-direction:column; gap:8px; margin-top:12px;}
    .nav button{
      display:flex; align-items:center; gap:10px; width:100%;
      padding:10px 12px; border-radius:12px; border:1px solid var(--line);
      background: rgba(16,26,46,.45);
      color:var(--text); cursor:pointer;
      transition:.15s ease;
    }
    .nav button:hover{transform: translateY(-1px); border-color: rgba(78,161,255,.55)}
    .nav button.active{background: rgba(78,161,255,.12); border-color: rgba(78,161,255,.65)}
    .nav button[disabled]{opacity:.45; cursor:not-allowed}
    .nav button[disabled]:hover{transform:none; border-color: var(--line)}
    .content{padding:18px 18px 80px;}
    .topbar{
      display:flex; align-items:flex-start; justify-content:space-between; gap:14px;
      margin-bottom:12px;
    }
    .title h2{margin:0; font-size:18px;}
    .title p{margin:6px 0 0; color:var(--muted); font-size:13px; max-width:980px;}
    .actions{display:flex; gap:10px; flex-wrap:wrap;}
    .btn{
      border:1px solid var(--line);
      background: rgba(16,26,46,.55);
      color:var(--text);
      border-radius:12px;
      padding:10px 12px;
      cursor:pointer;
      transition:.15s ease;
      display:inline-flex; gap:8px; align-items:center;
    }
    .btn:hover{border-color: rgba(78,161,255,.55); transform: translateY(-1px);}
    .btn.primary{background: rgba(78,161,255,.16); border-color: rgba(78,161,255,.65)}
    .btn.ok{background: rgba(55,214,122,.12); border-color: rgba(55,214,122,.55)}
    .btn.bad{background: rgba(255,91,91,.12); border-color: rgba(255,91,91,.55)}
    .btn.warn{background: rgba(255,204,102,.12); border-color: rgba(255,204,102,.55)}
    .grid{display:grid; gap:12px;}
    .grid.kpis{grid-template-columns: repeat(12, 1fr);}
    .card{
      border:1px solid var(--line);
      border-radius: var(--radius);
      background: rgba(16,26,46,.45);
      box-shadow: var(--shadow);
      padding:14px;
    }
    .kpi{grid-column: span 3;}
    .kpi .label{font-size:12px; color:var(--muted); margin-bottom:6px}
    .kpi .value{font-size:22px; font-weight:700}
    .kpi .hint{font-size:12px; color:var(--muted); margin-top:6px}
    .split{display:grid; grid-template-columns: 1.3fr .7fr; gap:12px;}
    .section-title{
      display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;
    }
    .section-title h3{margin:0; font-size:14px;}
    .section-title small{color:var(--muted)}
    #comercial-dashboard table{width:100%; border-collapse: collapse;}
    #comercial-dashboard th,
    #comercial-dashboard td{border-bottom:1px solid rgba(31,44,74,.75); padding:10px 8px; text-align:left; font-size:13px; vertical-align:top}
    #comercial-dashboard th{color:var(--muted); font-size:12px; font-weight:600}
    #comercial-dashboard tr:hover td{background: rgba(78,161,255,.06)}
    .pill{
      display:inline-flex; align-items:center; gap:6px;
      padding:6px 10px;
      border-radius:999px;
      border:1px solid var(--line);
      background: rgba(20,33,66,.6);
      font-size:12px;
      color: var(--text);
      white-space:nowrap;
    }
    .pill.ok{border-color: rgba(55,214,122,.45); background: rgba(55,214,122,.12)}
    .pill.bad{border-color: rgba(255,91,91,.45); background: rgba(255,91,91,.12)}
    .pill.warn{border-color: rgba(255,204,102,.45); background: rgba(255,204,102,.12)}
    .pill.brand{border-color: rgba(78,161,255,.55); background: rgba(78,161,255,.12)}
    .mini{font-family: var(--mono); font-size: 12px; color: var(--muted);}
    .bar{
      height:10px; background: rgba(255,255,255,.07); border-radius:999px; overflow:hidden; border:1px solid rgba(31,44,74,.85);
    }
    .bar > div{height:100%; background: linear-gradient(90deg, #4ea1ff, #37d67a); width:0%}
    .board{display:grid; grid-template-columns: repeat(4, 1fr); gap:12px;}
    .col{border:1px solid var(--line); border-radius: var(--radius); background: rgba(16,26,46,.35); padding:12px;}
    .col h4{margin:0 0 10px; font-size:13px; color:var(--muted); text-transform:uppercase; letter-spacing:.03em}
    .qcard{
      border:1px solid rgba(31,44,74,.95);
      border-radius: 14px;
      background: rgba(11,18,32,.85);
      padding:10px;
      margin-bottom:10px;
      cursor:pointer;
      transition:.12s ease;
    }
    .qcard:hover{transform: translateY(-1px); border-color: rgba(78,161,255,.55)}
    .qtop{display:flex; justify-content:space-between; gap:10px; align-items:flex-start}
    .qname{font-weight:700; font-size:13px}
    .qmeta{margin-top:6px; color:var(--muted); font-size:12px}
    .qmoney{font-weight:800; font-size:13px}
    .qchips{display:flex; flex-wrap:wrap; gap:6px; margin-top:8px}
    .chip{
      font-size:11px; padding:5px 8px; border-radius:999px;
      border:1px solid var(--line);
      background: rgba(20,33,66,.55);
      color: var(--text);
    }
    .chip.brand{border-color: rgba(78,161,255,.55); background: rgba(78,161,255,.12)}
    .chip.ok{border-color: rgba(55,214,122,.55); background: rgba(55,214,122,.12)}
    .chip.bad{border-color: rgba(255,91,91,.55); background: rgba(255,91,91,.12)}
    .chip.warn{border-color: rgba(255,204,102,.55); background: rgba(255,204,102,.12)}

    /* Modal */
    .modal-backdrop{
      position:fixed; inset:0; background: rgba(0,0,0,.6);
      display:none; align-items:center; justify-content:center;
      padding:18px;
    }
    .modal{
      width:min(1000px, 100%);
      max-height: calc(100vh - 80px);
      border-radius: 18px;
      border:1px solid rgba(31,44,74,.95);
      background: linear-gradient(180deg, rgba(16,26,46,.95), rgba(11,18,32,.95));
      box-shadow: 0 30px 80px rgba(0,0,0,.55);
      overflow:hidden;
    }
    .modal header{
      display:flex; align-items:flex-start; justify-content:space-between; gap:12px;
      padding:14px 14px 10px;
      border-bottom:1px solid rgba(31,44,74,.95);
    }
    .modal header h3{margin:0; font-size:16px}
    .modal header p{margin:6px 0 0; color:var(--muted); font-size:13px}
    .modal .body{
      padding:14px;
      display:grid;
      grid-template-columns: 1.2fr .8fr;
      gap:12px;
      max-height: calc(100vh - 220px);
      overflow:auto;
    }
    .modal .body .card{box-shadow:none}
    .modal footer{
      padding:12px 14px; border-top:1px solid rgba(31,44,74,.95);
      display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;
    }
    .close{background: transparent; border:1px solid var(--line); color:var(--text); border-radius:12px; padding:10px 12px; cursor:pointer}
    .two{display:grid; grid-template-columns: 1fr 1fr; gap:10px;}
    .muted{color:var(--muted)}

    @media (max-width: 1020px){
      .app{grid-template-columns: 1fr;}
      .sidebar{position:relative; height:auto;}
      .split{grid-template-columns: 1fr;}
      .board{grid-template-columns: 1fr;}
      .grid.kpis{grid-template-columns: repeat(6, 1fr);}
      .kpi{grid-column: span 3;}
      .modal .body{grid-template-columns: 1fr;}
    }
  </style>
