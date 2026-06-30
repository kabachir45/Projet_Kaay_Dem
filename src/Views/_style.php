<?php /* Feuille de style commune — inclure dans <head> */ ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
/* ── Design Tokens ─────────────────────────────────────────── */
:root{
  /* Couleur de marque — cyan / turquoise néon */
  --brand:#15d0e0;
  --brand-dark:#0a93a3;   /* teal plus foncé : lisible en TEXTE sur fond clair */
  --brand-light:#e2fbff;
  --brand-2:#1fb6ff;      /* bleu pour le dégradé */
  /* Les tokens "green" sont conservés comme ALIAS de la marque :
     tout le code existant (var(--green)…) bascule automatiquement. */
  --green:#15d0e0;
  --green-light:#e2fbff;
  --green-dark:#0a93a3;
  --green-bright:#3ee9f2;
  --gold:#FFC23D;         /* or / ambre — accent chaud */
  --gold-soft:#f6b73c;
  --on-brand:#062a30;     /* texte foncé à poser SUR le cyan (boutons, badges) */
  --red:#E31E24;
  --navy:#0B1F3A;
  --navy-mid:#1a3557;
  --slate:#3D566E;
  --muted:#6B8299;
  --border:#DDE4EC;
  --border-soft:#EAF0F6;
  --bg:#F4F7FB;
  --surface:#FFFFFF;
  --white:#FFFFFF;
  --radius:14px;
  --radius-sm:9px;
  --radius-lg:20px;
  --grad-green:linear-gradient(135deg,#3ee9f2 0%,#15d0e0 50%,#06b6c9 100%);
  --grad-navy:linear-gradient(135deg,#13202e 0%,#0c1722 55%,#0c2b33 100%);
  --grad-gold:linear-gradient(135deg,#ffd84d 0%,#f6b73c 100%);
  --shadow-xs:0 1px 2px rgba(11,31,58,.06);
  --shadow:0 1px 2px rgba(11,31,58,.04),0 10px 24px -10px rgba(11,31,58,.16);
  --shadow-lg:0 4px 10px rgba(11,31,58,.05),0 28px 56px -16px rgba(11,31,58,.26);
  --ring:0 0 0 4px rgba(21,208,224,.15);
  --ease:cubic-bezier(.4,0,.2,1);
  --font-head:'Plus Jakarta Sans',system-ui,sans-serif;
  --font-body:'Inter',system-ui,sans-serif;
}

/* ── Reset ─────────────────────────────────────────────────── */
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:var(--font-body);background:var(--bg);color:var(--navy);line-height:1.6;min-height:100vh;display:flex;flex-direction:column;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;text-rendering:optimizeLegibility}
::selection{background:rgba(21,208,224,.18)}
img{max-width:100%;display:block}
a{color:inherit;text-decoration:none}

/* ── Navbar ────────────────────────────────────────────────── */
.navbar{
  background:rgba(255,255,255,.82);
  backdrop-filter:saturate(180%) blur(14px);
  -webkit-backdrop-filter:saturate(180%) blur(14px);
  border-bottom:1px solid var(--border);
  position:sticky;top:0;z-index:100;
  box-shadow:0 1px 0 rgba(11,31,58,.04),0 10px 28px -22px rgba(11,31,58,.5);
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
  width:40px;height:40px;background:var(--grad-green);border-radius:12px;
  display:grid;place-items:center;font-size:18px;
  box-shadow:0 8px 18px -6px rgba(21,208,224,.6),inset 0 1px 0 rgba(255,255,255,.3);
}
.nav-links{display:flex;align-items:center;gap:6px}
.nav-links a{
  padding:8px 14px;border-radius:8px;font-weight:500;font-size:14px;
  color:var(--slate);transition:background .2s,color .2s;
}
.nav-links a{position:relative}
.nav-links a:hover{background:var(--green-light);color:var(--green-dark)}
.nav-links a.active{background:var(--green-light);color:var(--green-dark);box-shadow:inset 0 0 0 1.5px var(--green)}
.nav-auth{display:flex;align-items:center;gap:8px;margin-left:16px;padding-left:16px;border-left:1px solid var(--border)}
.btn-ghost{padding:8px 16px;border-radius:8px;font-size:14px;font-weight:600;color:var(--navy);border:1.5px solid var(--border);transition:.2s;cursor:pointer;background:transparent}
.btn-ghost:hover{border-color:var(--green);color:var(--green)}
.btn-primary{padding:9px 20px;background:var(--grad-green);color:var(--on-brand);border-radius:10px;font-size:14px;font-weight:800;border:none;cursor:pointer;transition:transform .18s var(--ease),box-shadow .18s var(--ease);display:inline-flex;align-items:center;gap:6px;box-shadow:0 6px 16px -8px rgba(21,208,224,.6)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 12px 22px -8px rgba(21,208,224,.6)}
.nav-user{display:flex;align-items:center;gap:10px}
.nav-user-name{font-size:14px;font-weight:600;color:var(--navy)}
.nav-user-avatar{width:36px;height:36px;background:var(--green);border-radius:50%;display:grid;place-items:center;color:white;font-weight:700;font-size:14px}
.btn-logout{padding:7px 14px;border-radius:8px;font-size:13px;font-weight:600;color:var(--red);border:1.5px solid rgba(227,30,36,.3);background:transparent;cursor:pointer;transition:.2s}
.btn-logout:hover{background:rgba(227,30,36,.07)}

/* ── Boutons généraux ──────────────────────────────────────── */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:13px 26px;border-radius:12px;font-weight:700;font-size:15px;letter-spacing:.2px;position:relative;overflow:hidden;transition:transform .2s var(--ease),box-shadow .2s var(--ease),filter .2s var(--ease);cursor:pointer;border:none;text-decoration:none}
/* reflet animé au survol */
.btn::after{content:'';position:absolute;top:0;left:-120%;width:60%;height:100%;background:linear-gradient(120deg,transparent,rgba(255,255,255,.35),transparent);transform:skewX(-18deg);transition:left .55s var(--ease)}
.btn:hover::after{left:140%}
.btn:active{transform:translateY(0) scale(.98)}
.btn-lg{padding:16px 34px;font-size:16px;border-radius:14px}
.btn-green{background:var(--grad-green);color:var(--on-brand);font-weight:800;box-shadow:0 10px 26px -8px rgba(21,208,224,.65),inset 0 1px 0 rgba(255,255,255,.4)}
.btn-green:hover{transform:translateY(-3px);box-shadow:0 22px 44px -10px rgba(21,208,224,.8),inset 0 1px 0 rgba(255,255,255,.5);filter:brightness(1.06)}
.btn-outline{background:var(--white);color:var(--navy);border:1.5px solid var(--border)}
.btn-outline:hover{border-color:var(--green);color:var(--green-dark);transform:translateY(-2px);box-shadow:0 10px 22px -10px rgba(11,31,58,.25)}
.btn-navy{background:var(--grad-navy);color:white;box-shadow:0 10px 24px -8px rgba(11,31,58,.5),inset 0 1px 0 rgba(255,255,255,.12)}
.btn-navy:hover{transform:translateY(-3px);box-shadow:0 20px 38px -10px rgba(11,31,58,.6)}
.btn-red{background:linear-gradient(135deg,#ff3b41 0%,#E31E24 60%,#c01a1f 100%);color:white;box-shadow:0 10px 24px -8px rgba(227,30,36,.5)}
.btn-red:hover{transform:translateY(-3px);box-shadow:0 20px 38px -10px rgba(227,30,36,.6)}

/* ── Formulaires ───────────────────────────────────────────── */
.form-group{margin-bottom:18px}
.form-label{display:block;font-size:13px;font-weight:600;color:var(--slate);margin-bottom:6px;letter-spacing:.3px;text-transform:uppercase}
.form-control{width:100%;padding:13px 16px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:15px;font-family:var(--font-body);color:var(--navy);background:var(--white);transition:border-color .18s var(--ease),box-shadow .18s var(--ease);outline:none}
.form-control:hover{border-color:#c2cedd}
.form-control:focus{border-color:var(--green);box-shadow:var(--ring)}
.form-control::placeholder{color:var(--muted)}
select.form-control{cursor:pointer}

/* ── Alertes ───────────────────────────────────────────────── */
.alert{padding:14px 18px;border-radius:var(--radius-sm);font-size:14px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}
.alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}
.alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.alert-info{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe}

/* ── Cartes ────────────────────────────────────────────────── */
.card{background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border-soft);transition:transform .2s var(--ease),box-shadow .2s var(--ease)}
.card-hover:hover{transform:translateY(-3px);box-shadow:var(--shadow-lg)}
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
  --navy:#e7edf5;--navy-mid:#cbd5e1;
  --slate:#9fb0c3;--muted:#7689a0;
  --border:#26303f;--border-soft:#222b38;--bg:#0b1220;--surface:#161f2e;--white:#161f2e;
  --brand:#2ee6f2;--brand-dark:#7df0f8;--brand-light:rgba(21,208,224,.20);
  --green:#2ee6f2;--green-light:rgba(21,208,224,.20);--green-dark:#7df0f8;--green-bright:#5cf0fa;
  --grad-green:linear-gradient(135deg,#5cf0fa 0%,#1fd6e6 50%,#13b6cf 100%);
  --grad-navy:linear-gradient(135deg,#0b1220 0%,#10212a 60%,#0c2b33 100%);
  --shadow-xs:0 1px 2px rgba(0,0,0,.5);
  --shadow:0 2px 6px rgba(0,0,0,.4),0 16px 40px -16px rgba(0,0,0,.65);
  --shadow-lg:0 8px 24px rgba(0,0,0,.5),0 32px 64px -20px rgba(0,0,0,.75);
  --ring:0 0 0 4px rgba(46,230,242,.3);
}
[data-theme="dark"] body{background:var(--bg);color:var(--navy)}
[data-theme="dark"] .navbar{background:#161f2e;border-color:#26303f}
[data-theme="dark"] .nav-links a{color:var(--slate)}
[data-theme="dark"] .nav-links a:hover,[data-theme="dark"] .nav-links a.active{background:rgba(21,208,224,.2);color:#34d27a}
[data-theme="dark"] .nav-logo{color:#e7edf5}
[data-theme="dark"] .card,[data-theme="dark"] .section-card,[data-theme="dark"] .trajet-card,
[data-theme="dark"] .resa-card,[data-theme="dark"] .trajet-bloc,[data-theme="dark"] .dash-card,
[data-theme="dark"] .step,[data-theme="dark"] .testi,[data-theme="dark"] .stat-box,
[data-theme="dark"] .admin-stat{background:#161f2e;border-color:#26303f;color:#e7edf5}
[data-theme="dark"] .form-control{background:#0b1220;border-color:#26303f;color:#e7edf5}
[data-theme="dark"] .form-control::placeholder{color:#33415a}
[data-theme="dark"] .stats-strip,[data-theme="dark"] .stat-item{background:#161f2e;border-color:#26303f}
[data-theme="dark"] .meta-chip{background:#0b1220;border-color:#26303f;color:#9fb0c3}
[data-theme="dark"] thead{background:#0b1220}
[data-theme="dark"] tbody tr{border-color:#26303f}
[data-theme="dark"] tbody tr:hover{background:rgba(21,208,224,.1)}
[data-theme="dark"] .btn-ghost{background:#161f2e;color:#9fb0c3;border-color:#26303f}
[data-theme="dark"] .btn-outline{background:#161f2e;color:#e7edf5;border-color:#26303f}
[data-theme="dark"] .section-card-header,[data-theme="dark"] .trajet-bloc-header{border-color:#26303f;background:#161f2e}
[data-theme="dark"] .topbar{background:#161f2e;border-color:#26303f}
[data-theme="dark"] .search-hero{background:linear-gradient(135deg,#060b14,#0b1220)}
[data-theme="dark"] .page-header,[data-theme="dark"] .dash-header,[data-theme="dark"] .admin-header{background:linear-gradient(135deg,#060b14,#0b1220)}
[data-theme="dark"] .eval-form{background:#0b1220}
[data-theme="dark"] .auth-right{background:#0b1220}
[data-theme="dark"] .auth-form-title{color:#e7edf5}

/* Surfaces "marine" (fond = var(--navy)) : en mode nuit, --navy devient une
   couleur de TEXTE claire. Sans ces surcharges, ces blocs deviendraient clairs
   avec un texte blanc (illisible). On force donc un fond sombre explicite. */
[data-theme="dark"] .hero{background:linear-gradient(135deg,#060b14 0%,#0b1220 60%,#0c2b33 100%)}
[data-theme="dark"] .cities{background:#0b1220}
[data-theme="dark"] footer{background:#060b14}
[data-theme="dark"] .btn-navy{background:#1c2738;color:#e7edf5}
[data-theme="dark"] .btn-navy:hover{background:#33415a}

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
  const t = localStorage.getItem('kaaydem_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);
})();
</script>
