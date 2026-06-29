<?php /* Feuille de style commune — inclure dans <head> */ ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
/* ── Design Tokens ─────────────────────────────────────────── */
:root{
  --green:#00853F;        /* vert sénégalais */
  --green-light:#e8f7ee;
  --green-dark:#006830;
  --gold:#FDEF42;         /* jaune kénédougou */
  --red:#E31E24;
  --navy:#0B1F3A;
  --navy-mid:#1a3557;
  --slate:#3D566E;
  --muted:#6B8299;
  --border:#DDE4EC;
  --bg:#F4F7FB;
  --white:#FFFFFF;
  --radius:12px;
  --radius-sm:8px;
  --shadow:0 4px 24px rgba(11,31,58,.09);
  --shadow-lg:0 12px 48px rgba(11,31,58,.15);
  --font-head:'Plus Jakarta Sans',system-ui,sans-serif;
  --font-body:'Inter',system-ui,sans-serif;
}

/* ── Reset ─────────────────────────────────────────────────── */
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:var(--font-body);background:var(--bg);color:var(--navy);line-height:1.6;min-height:100vh;display:flex;flex-direction:column}
img{max-width:100%;display:block}
a{color:inherit;text-decoration:none}

/* ── Navbar ────────────────────────────────────────────────── */
.navbar{
  background:var(--white);
  border-bottom:1px solid var(--border);
  position:sticky;top:0;z-index:100;
  box-shadow:0 2px 12px rgba(11,31,58,.06);
}
.nav-inner{
  max-width:1200px;margin:0 auto;
  display:flex;align-items:center;gap:8px;
  padding:0 24px;height:68px;
}
.nav-logo{
  display:flex;align-items:center;gap:10px;
  font-family:var(--font-head);font-weight:800;font-size:22px;
  color:var(--navy);margin-right:auto;
}
.nav-logo span.accent{color:var(--green)}
.nav-logo .logo-icon{
  width:38px;height:38px;background:var(--green);border-radius:10px;
  display:grid;place-items:center;font-size:18px;
}
.nav-links{display:flex;align-items:center;gap:6px}
.nav-links a{
  padding:8px 14px;border-radius:8px;font-weight:500;font-size:14px;
  color:var(--slate);transition:background .2s,color .2s;
}
.nav-links a:hover,.nav-links a.active{background:var(--green-light);color:var(--green-dark)}
.nav-auth{display:flex;align-items:center;gap:8px;margin-left:16px;padding-left:16px;border-left:1px solid var(--border)}
.btn-ghost{padding:8px 16px;border-radius:8px;font-size:14px;font-weight:600;color:var(--navy);border:1.5px solid var(--border);transition:.2s;cursor:pointer;background:transparent}
.btn-ghost:hover{border-color:var(--green);color:var(--green)}
.btn-primary{padding:9px 20px;background:var(--green);color:var(--white);border-radius:8px;font-size:14px;font-weight:700;border:none;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:6px}
.btn-primary:hover{background:var(--green-dark);transform:translateY(-1px)}
.nav-user{display:flex;align-items:center;gap:10px}
.nav-user-name{font-size:14px;font-weight:600;color:var(--navy)}
.nav-user-avatar{width:36px;height:36px;background:var(--green);border-radius:50%;display:grid;place-items:center;color:white;font-weight:700;font-size:14px}
.btn-logout{padding:7px 14px;border-radius:8px;font-size:13px;font-weight:600;color:var(--red);border:1.5px solid rgba(227,30,36,.3);background:transparent;cursor:pointer;transition:.2s}
.btn-logout:hover{background:rgba(227,30,36,.07)}

/* ── Boutons généraux ──────────────────────────────────────── */
.btn{display:inline-flex;align-items:center;gap:6px;padding:12px 24px;border-radius:var(--radius-sm);font-weight:700;font-size:15px;transition:.2s;cursor:pointer;border:none;text-decoration:none}
.btn-lg{padding:15px 32px;font-size:16px;border-radius:var(--radius)}
.btn-green{background:var(--green);color:white}
.btn-green:hover{background:var(--green-dark);transform:translateY(-1px);box-shadow:0 6px 20px rgba(0,133,63,.3)}
.btn-outline{background:white;color:var(--navy);border:2px solid var(--border)}
.btn-outline:hover{border-color:var(--green);color:var(--green)}
.btn-navy{background:var(--navy);color:white}
.btn-navy:hover{background:var(--navy-mid)}
.btn-red{background:var(--red);color:white}
.btn-red:hover{background:#c01a1f}

/* ── Formulaires ───────────────────────────────────────────── */
.form-group{margin-bottom:18px}
.form-label{display:block;font-size:13px;font-weight:600;color:var(--slate);margin-bottom:6px;letter-spacing:.3px;text-transform:uppercase}
.form-control{width:100%;padding:13px 16px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:15px;font-family:var(--font-body);color:var(--navy);background:var(--white);transition:.2s;outline:none}
.form-control:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(0,133,63,.12)}
.form-control::placeholder{color:var(--muted)}
select.form-control{cursor:pointer}

/* ── Alertes ───────────────────────────────────────────────── */
.alert{padding:14px 18px;border-radius:var(--radius-sm);font-size:14px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}
.alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}
.alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.alert-info{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe}

/* ── Cartes ────────────────────────────────────────────────── */
.card{background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border)}
.card-body{padding:28px}

/* ── Page wrapper ──────────────────────────────────────────── */
.page{max-width:1200px;margin:0 auto;padding:40px 24px;flex:1}
.page-sm{max-width:480px;margin:0 auto;padding:40px 24px;flex:1}
.page-md{max-width:760px;margin:0 auto;padding:40px 24px;flex:1}

/* ── Footer ────────────────────────────────────────────────── */
footer{background:var(--navy);color:rgba(255,255,255,.7);text-align:center;padding:24px;font-size:13px;margin-top:auto}
footer strong{color:white}
footer .footer-stripe{height:4px;background:linear-gradient(90deg,var(--green) 33%,var(--gold) 33% 66%,var(--red) 66%);margin-bottom:16px}

/* ── Tableau ────────────────────────────────────────────────── */
.table-wrap{overflow-x:auto;border-radius:var(--radius);border:1px solid var(--border)}
table{width:100%;border-collapse:collapse;font-size:14px}
thead{background:var(--navy);color:white}
thead th{padding:13px 16px;text-align:left;font-weight:600;font-size:13px;letter-spacing:.4px}
tbody tr{border-bottom:1px solid var(--border);transition:background .15s}
tbody tr:hover{background:var(--green-light)}
tbody td{padding:13px 16px;color:var(--slate)}
tbody tr:last-child{border-bottom:none}

/* ── Badge statut ──────────────────────────────────────────── */
.badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:.3px}
.badge-green{background:var(--green-light);color:var(--green-dark)}
.badge-red{background:#fef2f2;color:#b91c1c}
.badge-yellow{background:#fffbeb;color:#92400e}
.badge-blue{background:#eff6ff;color:#1e40af}
.badge-gray{background:#f3f4f6;color:#4b5563}

/* ── Responsive ─────────────────────────────────────────────── */
@media(max-width:768px){
  .nav-links{display:none}
  .nav-inner{padding:0 16px}
  .page,.page-md,.page-sm{padding:24px 16px}
}
</style>
<style>
/* ── MODE NUIT ─────────────────────────────────────────────── */
[data-theme="dark"]{
  --navy:#e2e8f0;--navy-mid:#cbd5e1;
  --slate:#94a3b8;--muted:#64748b;
  --border:#2d3748;--bg:#0f172a;--white:#1e293b;
  --green-light:rgba(0,133,63,.15);--green-dark:#4ade80;
  --shadow:0 4px 24px rgba(0,0,0,.4);
  --shadow-lg:0 12px 48px rgba(0,0,0,.5);
}
[data-theme="dark"] body{background:var(--bg);color:var(--navy)}
[data-theme="dark"] .navbar{background:#1e293b;border-color:#2d3748}
[data-theme="dark"] .nav-links a{color:var(--slate)}
[data-theme="dark"] .nav-links a:hover,[data-theme="dark"] .nav-links a.active{background:rgba(0,133,63,.2);color:#4ade80}
[data-theme="dark"] .nav-logo{color:#e2e8f0}
[data-theme="dark"] .card,[data-theme="dark"] .section-card,[data-theme="dark"] .trajet-card,
[data-theme="dark"] .resa-card,[data-theme="dark"] .trajet-bloc,[data-theme="dark"] .dash-card,
[data-theme="dark"] .step,[data-theme="dark"] .testi,[data-theme="dark"] .stat-box,
[data-theme="dark"] .admin-stat{background:#1e293b;border-color:#2d3748;color:#e2e8f0}
[data-theme="dark"] .form-control{background:#0f172a;border-color:#2d3748;color:#e2e8f0}
[data-theme="dark"] .form-control::placeholder{color:#475569}
[data-theme="dark"] .stats-strip,[data-theme="dark"] .stat-item{background:#1e293b;border-color:#2d3748}
[data-theme="dark"] .meta-chip{background:#0f172a;border-color:#2d3748;color:#94a3b8}
[data-theme="dark"] thead{background:#0f172a}
[data-theme="dark"] tbody tr{border-color:#2d3748}
[data-theme="dark"] tbody tr:hover{background:rgba(0,133,63,.1)}
[data-theme="dark"] .btn-ghost{background:#1e293b;color:#94a3b8;border-color:#2d3748}
[data-theme="dark"] .btn-outline{background:#1e293b;color:#e2e8f0;border-color:#2d3748}
[data-theme="dark"] .section-card-header,[data-theme="dark"] .trajet-bloc-header{border-color:#2d3748;background:#1e293b}
[data-theme="dark"] .topbar{background:#1e293b;border-color:#2d3748}
[data-theme="dark"] .search-hero{background:linear-gradient(135deg,#020617,#0f172a)}
[data-theme="dark"] .page-header,[data-theme="dark"] .dash-header,[data-theme="dark"] .admin-header{background:linear-gradient(135deg,#020617,#0f172a)}
[data-theme="dark"] .eval-form{background:#0f172a}
[data-theme="dark"] .auth-right{background:#0f172a}
[data-theme="dark"] .auth-form-title{color:#e2e8f0}

/* Toggle bouton */
.dark-toggle{
  background:none;border:1.5px solid var(--border);border-radius:20px;
  padding:6px 12px;cursor:pointer;font-size:14px;color:var(--slate);
  transition:.2s;display:flex;align-items:center;gap:6px;font-family:var(--font-body);
}
.dark-toggle:hover{border-color:var(--green);color:var(--green)}
</style>
<script>
// Appliquer le thème dès le chargement (avant rendu)
(function(){
  const t = localStorage.getItem('kaaydem_theme') || 'light';
  document.documentElement.setAttribute('data-theme', t);
})();
</script>
