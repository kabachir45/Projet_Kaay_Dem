<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/_urls.php';

use App\Controllers\TrajetController;

$isLoggedIn = isset($_SESSION['utilisateur_id']);
$activePage = 'recherche';

$depart   = trim($_GET['depart']   ?? '');
$arrivee  = trim($_GET['arrivee']  ?? '');
$date     = $_GET['date']          ?? '';
$prixMax  = trim($_GET['prix_max'] ?? '');
$places   = trim($_GET['places']   ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$searched = ($depart || $arrivee || $date || $prixMax !== '' || $places !== '');

$trajets  = [];
$total    = 0;
$pages    = 1;
$erreurBd = false;
try {
    $resultat = (new TrajetController())->rechercher([
        'depart'     => $depart,
        'arrivee'    => $arrivee,
        'date'       => $date,
        'prix_max'   => $prixMax,
        'places_min' => $places,
        'page'       => $page,
    ]);
    $trajets = $resultat['trajets'];
    $total   = $resultat['total'];
    $page    = $resultat['page'];
    $pages   = $resultat['pages'];
} catch(\Throwable $e) { $erreurBd = true; }

// Conserve les filtres courants dans les liens de pagination
$queryFiltres = http_build_query(array_filter([
    'depart'   => $depart,
    'arrivee'  => $arrivee,
    'date'     => $date,
    'prix_max' => $prixMax,
    'places'   => $places,
], fn($v) => $v !== '' && $v !== null));

$retour = $isLoggedIn ? 'dashboard.php' : $baseUrl . 'index.php';

// Préparer les données JSON pour la carte
$trajetsGeo = array_filter($trajets, fn($t) => $t['lat_depart'] && $t['lat_arrivee']);
$trajetsJson = json_encode(array_values(array_map(fn($t) => [
    'id'       => $t['id'],
    'depart'   => $t['ville_depart'],
    'arrivee'  => $t['ville_arrivee'],
    'lat_d'    => (float)$t['lat_depart'],
    'lng_d'    => (float)$t['lng_depart'],
    'lat_a'    => (float)$t['lat_arrivee'],
    'lng_a'    => (float)$t['lng_arrivee'],
    'prix'     => (float)$t['prix'],
    'places'   => $t['places_disponibles'],
    'date'     => (new DateTime($t['date_depart']))->format('d/m/Y H:i'),
    'conducteur' => htmlspecialchars($t['conducteur_prenom'].' '.$t['conducteur_nom']),
    'note'     => (float)$t['note_moyenne'],
    'dist'     => $t['distance_km'] ? round($t['distance_km']).' km' : null,
], $trajetsGeo)));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rechercher un trajet — Kaay Dem !</title>
<?php include __DIR__ . '/_style.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<style>
.search-hero{background:linear-gradient(135deg,var(--navy),var(--navy-mid));padding:36px 24px;color:white}
.search-hero h1{font-family:var(--font-head);font-size:26px;font-weight:800;text-align:center;margin-bottom:4px}
.search-hero p{text-align:center;color:rgba(255,255,255,.7);margin-bottom:24px}
.search-box{max-width:860px;margin:0 auto;background:white;border-radius:var(--radius);padding:18px 22px;box-shadow:var(--shadow-lg);display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:14px;align-items:end}
.search-box label{display:block;font-size:12px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
.search-box input{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:15px;color:var(--navy);outline:none;transition:.2s;font-family:var(--font-body)}
.search-box input:focus{border-color:var(--green)}
.btn-search{background:var(--green);color:white;border:none;padding:0 22px;border-radius:var(--radius-sm);font-weight:700;font-size:15px;cursor:pointer;transition:.2s;height:46px;white-space:nowrap}
.btn-search:hover{background:var(--green-dark)}

/* Pagination */
.pagination{display:flex;gap:6px;flex-wrap:wrap;align-items:center;justify-content:center;margin-top:24px}
.page-link{min-width:38px;height:38px;padding:0 12px;display:inline-flex;align-items:center;justify-content:center;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:white;color:var(--navy);font-size:14px;font-weight:600;text-decoration:none;transition:.2s}
.page-link:hover{border-color:var(--green);color:var(--green-dark)}
.page-link.active{background:var(--green);border-color:var(--green);color:white}
.page-link.disabled{color:var(--muted);background:var(--bg);cursor:not-allowed;opacity:.6}
[data-theme="dark"] .page-link{background:#1e293b;color:#e2e8f0}

/* Layout carte + liste */
.results-layout{max-width:1200px;margin:0 auto;padding:28px 24px;display:grid;grid-template-columns:1fr 420px;gap:24px;align-items:start}
.results-list{min-width:0}
.results-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.results-count{font-family:var(--font-head);font-size:18px;font-weight:700;color:var(--navy)}
.results-count span{color:var(--green)}

/* Carte côté droit */
.map-panel{position:sticky;top:80px}
#map-results{height:560px;border-radius:var(--radius);border:1.5px solid var(--border);box-shadow:var(--shadow);z-index:0}
.map-panel-title{font-family:var(--font-head);font-size:14px;font-weight:700;color:var(--navy);margin-bottom:8px;display:flex;align-items:center;gap:6px}

/* Cartes trajet */
.trajet-card{background:white;border:1.5px solid var(--border);border-radius:var(--radius);padding:18px 20px;margin-bottom:12px;transition:.25s;cursor:pointer}
.trajet-card:hover,.trajet-card.highlighted{border-color:var(--green);box-shadow:0 4px 20px rgba(21,208,224,.15);transform:translateY(-2px)}
.trajet-card.highlighted{background:var(--green-light)}
.trajet-route{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.trajet-city{font-family:var(--font-head);font-weight:800;font-size:16px;color:var(--navy)}
.trajet-arrow{color:var(--green);font-size:18px}
.trajet-meta{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px}
.meta-chip{display:inline-flex;align-items:center;gap:4px;background:var(--bg);border:1px solid var(--border);padding:4px 10px;border-radius:20px;font-size:12px;color:var(--slate);font-weight:500}
.trajet-bottom{display:flex;align-items:center;justify-content:space-between;padding-top:10px;border-top:1px solid var(--border)}
.driver-info{display:flex;align-items:center;gap:8px}
.driver-avatar{width:30px;height:30px;background:var(--green);border-radius:50%;display:grid;place-items:center;font-weight:700;color:white;font-size:12px}
.driver-name{font-weight:600;font-size:13px;color:var(--navy)}
.driver-rating{font-size:12px;color:var(--gold);font-weight:600}
.trajet-price{font-family:var(--font-head);font-size:20px;font-weight:800;color:var(--green);text-align:right}
.trajet-price small{display:block;font-size:11px;color:var(--muted);font-weight:400}

.login-prompt{background:var(--green-light);border:1px solid rgba(21,208,224,.2);border-radius:var(--radius);padding:14px 18px;text-align:center;margin-bottom:18px;color:var(--green-dark);font-size:14px}
.login-prompt a{font-weight:700;color:var(--green-dark);text-decoration:underline}
.empty-state{text-align:center;padding:50px 20px;color:var(--muted)}
.empty-state div{font-size:48px;margin-bottom:12px}

/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:500;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:white;border-radius:var(--radius);padding:32px;max-width:420px;width:90%;box-shadow:var(--shadow-lg)}
.modal h3{font-family:var(--font-head);font-size:18px;font-weight:800;margin-bottom:16px;color:var(--navy)}
.modal-actions{display:flex;gap:12px;margin-top:20px}
[data-theme="dark"] .modal{background:#1e293b;color:#e2e8f0}

/* Popup Leaflet custom */
.leaflet-popup-content-wrapper{border-radius:var(--radius-sm)!important;box-shadow:var(--shadow)!important}
.popup-content{font-family:var(--font-body);font-size:14px;min-width:200px}
.popup-route{font-family:var(--font-head);font-weight:800;font-size:15px;color:var(--navy);margin-bottom:8px}
.popup-meta{color:var(--slate);font-size:13px;line-height:1.8}
.popup-price{font-family:var(--font-head);font-size:18px;font-weight:800;color:var(--green);margin:8px 0}
.leaflet-popup-content a.popup-btn{display:block;text-align:center;padding:9px;background:var(--grad-green, #00853F);color:#fff !important;border-radius:8px;font-weight:700;font-size:13px;text-decoration:none;margin-top:8px;box-shadow:0 4px 12px -4px rgba(21,208,224,.5);transition:filter .15s ease}
.leaflet-popup-content a.popup-btn:hover{filter:brightness(1.08)}

@media(max-width:900px){
  .results-layout{grid-template-columns:1fr}
  .map-panel{position:static}
  #map-results{height:300px}
  .search-box{grid-template-columns:1fr;gap:10px}
}
</style>
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="search-hero">
  <h1>🔍 Rechercher un trajet</h1>
  <p>Trouvez votre covoiturage parmi les trajets disponibles</p>
  <form method="GET" class="search-box">
    <div>
      <label>Ville de départ</label>
      <input type="text" name="depart" placeholder="ex. Dakar, Rufisque..." value="<?= htmlspecialchars($depart) ?>">
    </div>
    <div>
      <label>Ville d'arrivée</label>
      <input type="text" name="arrivee" placeholder="ex. Diamniadio, Thiès..." value="<?= htmlspecialchars($arrivee) ?>">
    </div>
    <div>
      <label>Date</label>
      <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" min="<?= date('Y-m-d') ?>">
    </div>
    <div>
      <label>Prix max (FCFA)</label>
      <input type="number" name="prix_max" min="0" step="50" placeholder="ex. 1000" value="<?= htmlspecialchars($prixMax) ?>">
    </div>
    <div>
      <label>Places min.</label>
      <input type="number" name="places" min="1" max="8" placeholder="ex. 2" value="<?= htmlspecialchars($places) ?>">
    </div>
    <button type="submit" class="btn-search">Chercher</button>
  </form>
</div>

<div class="results-layout">

  <!-- ── Liste des trajets ────────────────────────────── -->
  <div class="results-list">
    <?php if(!$isLoggedIn): ?>
    <div class="login-prompt">
      💡 <strong>Connectez-vous</strong> pour réserver —
      <a href="login.php">Se connecter</a> · <a href="register.php">Créer un compte</a>
    </div>
    <?php endif; ?>

    <?php if($erreurBd): ?>
    <div class="alert alert-error">⚠️ Impossible de charger les trajets. Vérifiez la connexion à la base de données.</div>
    <?php else: ?>

    <div class="results-header">
      <div class="results-count">
        <span><?= $total ?></span> trajet<?= $total>1?'s':'' ?>
        <?= $searched ? 'trouvé'.($total>1?'s':'') : 'disponible'.($total>1?'s':'') ?>
        <?php if($pages>1): ?><span style="color:var(--muted);font-weight:400">— page <?= $page ?>/<?= $pages ?></span><?php endif; ?>
      </div>
      <?php if($searched): ?>
      <a href="rechercher_trajet.php" style="font-size:13px;color:var(--muted);font-weight:600">✕ Réinitialiser</a>
      <?php endif; ?>
    </div>

    <?php if(empty($trajets)): ?>
    <div class="empty-state">
      <div>🚗</div>
      <h3 style="font-family:var(--font-head);font-size:18px;font-weight:700;margin-bottom:6px">Aucun trajet disponible</h3>
      <p><?= $searched ? 'Essayez avec d\'autres critères.' : 'Aucun trajet n\'a encore été publié.' ?></p>
    </div>
    <?php else: ?>
      <?php foreach($trajets as $i => $t): ?>
      <?php
        $dateDepart  = new DateTime($t['date_depart']);
        $conducteur  = htmlspecialchars($t['conducteur_prenom'].' '.$t['conducteur_nom']);
        $initiale    = mb_strtoupper(mb_substr($t['conducteur_prenom'],0,1));
        $note        = (float)$t['note_moyenne'];
        $vehicule    = trim(($t['marque']??'').' '.($t['modele']??''));
        $hasCoords   = $t['lat_depart'] && $t['lat_arrivee'];
      ?>
      <div class="trajet-card" id="card-<?= $t['id'] ?>"
           <?= $hasCoords ? "onclick=\"highlightTrajet({$t['id']},{$t['lat_depart']},{$t['lng_depart']},{$t['lat_arrivee']},{$t['lng_arrivee']})\"" : '' ?>>
        <div class="trajet-route">
          <span class="trajet-city"><?= htmlspecialchars($t['ville_depart']) ?></span>
          <span class="trajet-arrow">→</span>
          <span class="trajet-city"><?= htmlspecialchars($t['ville_arrivee']) ?></span>
        </div>
        <div class="trajet-meta">
          <span class="meta-chip">📅 <?= $dateDepart->format('d/m/Y') ?></span>
          <span class="meta-chip">🕐 <?= $dateDepart->format('H:i') ?></span>
          <span class="meta-chip">💺 <?= $t['places_disponibles'] ?> place<?= $t['places_disponibles']>1?'s':'' ?></span>
          <?php if($vehicule): ?><span class="meta-chip">🚙 <?= htmlspecialchars($vehicule) ?></span><?php endif; ?>
          <?php if($t['distance_km']): ?><span class="meta-chip">📍 <?= round($t['distance_km']) ?> km</span><?php endif; ?>
        </div>
        <div class="trajet-bottom">
          <div class="driver-info">
            <div class="driver-avatar"><?= $initiale ?></div>
            <div>
              <div class="driver-name"><?= $conducteur ?></div>
              <div class="driver-rating">
                <?= $note>0 ? '★ '.number_format($note,1).' <span style="color:var(--muted);font-weight:400">('.$t['nb_evaluations'].' avis)</span>' : '<span style="color:var(--muted)">Pas encore d\'avis</span>' ?>
              </div>
            </div>
          </div>
          <div>
            <div class="trajet-price"><?= number_format((float)$t['prix'],0,',',' ') ?><small>FCFA/place</small></div>
            <?php if($isLoggedIn): ?>
            <button class="btn btn-green" style="margin-top:6px;width:100%;justify-content:center;font-size:13px;padding:7px 14px"
                    onclick="event.stopPropagation();ouvrirModal(<?= $t['id'] ?>,'<?= htmlspecialchars(addslashes($t['ville_depart'])) ?>','<?= htmlspecialchars(addslashes($t['ville_arrivee'])) ?>','<?= $dateDepart->format('d/m/Y H:i') ?>',<?= (float)$t['prix'] ?>)">
              Réserver
            </button>
            <?php else: ?>
            <a href="login.php" class="btn btn-outline" style="margin-top:6px;font-size:12px;padding:6px 12px">Se connecter</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if($pages > 1): ?>
      <?php $lien = fn($p) => '?' . ($queryFiltres ? $queryFiltres . '&' : '') . 'page=' . $p; ?>
      <nav class="pagination" aria-label="Pagination">
        <?php if($page > 1): ?>
          <a class="page-link" href="<?= htmlspecialchars($lien($page-1)) ?>">← Précédent</a>
        <?php else: ?>
          <span class="page-link disabled">← Précédent</span>
        <?php endif; ?>

        <?php for($p = 1; $p <= $pages; $p++): ?>
          <?php if($p === $page): ?>
            <span class="page-link active"><?= $p ?></span>
          <?php else: ?>
            <a class="page-link" href="<?= htmlspecialchars($lien($p)) ?>"><?= $p ?></a>
          <?php endif; ?>
        <?php endfor; ?>

        <?php if($page < $pages): ?>
          <a class="page-link" href="<?= htmlspecialchars($lien($page+1)) ?>">Suivant →</a>
        <?php else: ?>
          <span class="page-link disabled">Suivant →</span>
        <?php endif; ?>
      </nav>
      <?php endif; ?>

    <?php endif; ?>
    <?php endif; ?>

    <div style="margin-top:20px">
      <a href="<?= htmlspecialchars($retour) ?>" style="color:var(--muted);font-size:14px;font-weight:500">← Retour</a>
    </div>
  </div>

  <!-- ── Carte ─────────────────────────────────────────── -->
  <div class="map-panel">
    <div class="map-panel-title">🗺️ Carte des trajets disponibles</div>
    <div id="map-results"></div>
  </div>

</div>

<!-- Modal réservation -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <h3>🚗 Confirmer la réservation</h3>
    <div id="modalContent" style="color:var(--slate);line-height:1.9;font-size:15px"></div>
    <div class="modal-actions">
      <form method="POST" action="reserver.php" style="flex:1">
        <input type="hidden" name="trajet_id" id="modalTrajetId">
        <button type="submit" class="btn btn-green" style="width:100%;justify-content:center;padding:12px">✓ Confirmer</button>
      </form>
      <button class="btn btn-outline" onclick="fermerModal()" style="flex:1;justify-content:center;padding:12px">Annuler</button>
    </div>
  </div>
</div>

<footer><div class="footer-stripe"></div><strong>Kaay Dem !</strong> — ISM Campus Baobab</footer>

<script>
// ── Initialiser la carte ──────────────────────────────────────
const map = L.map('map-results').setView([14.72, -17.20], 9);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
    maxZoom: 18
}).addTo(map);

// Données trajets depuis PHP
const trajets = <?= $trajetsJson ?>;
const markers = {};
const routeLayers = {};

const iconD = L.divIcon({className:'',html:'<div style="background:#00853F;width:12px;height:12px;border-radius:50%;border:2.5px solid white;box-shadow:0 2px 6px rgba(0,0,0,.4)"></div>'});
const iconA = L.divIcon({className:'',html:'<div style="background:#E31E24;width:12px;height:12px;border-radius:50%;border:2.5px solid white;box-shadow:0 2px 6px rgba(0,0,0,.4)"></div>'});

trajets.forEach(t => {
    // Marqueur départ
    const mD = L.marker([t.lat_d, t.lng_d], {icon: iconD}).addTo(map);
    // Marqueur arrivée
    const mA = L.marker([t.lat_a, t.lng_a], {icon: iconA}).addTo(map);

    // Ligne entre départ et arrivée
    const line = L.polyline([[t.lat_d, t.lng_d],[t.lat_a, t.lng_a]], {
        color:'#00853F', weight:3, opacity:.6, dashArray:'6,4'
    }).addTo(map);

    // Popup au clic sur le marqueur départ
    const popupHtml = `
        <div class="popup-content">
            <div class="popup-route">${t.depart} → ${t.arrivee}</div>
            <div class="popup-meta">
                📅 ${t.date}<br>
                💺 ${t.places} place${t.places>1?'s':''}<br>
                👤 ${t.conducteur}
                ${t.note > 0 ? '<br>★ ' + t.note : ''}
                ${t.dist ? '<br>📍 ' + t.dist : ''}
            </div>
            <div class="popup-price">${t.prix.toLocaleString('fr-FR')} FCFA</div>
            ${<?= $isLoggedIn?'true':'false' ?> ?
                `<a href="#" class="popup-btn" onclick="ouvrirModal(${t.id},'${t.depart}','${t.arrivee}','${t.date}',${t.prix});return false">Réserver</a>`
                : '<a href="login.php" class="popup-btn" style="background:#1a3557">Se connecter pour réserver</a>'
            }
        </div>`;

    mD.bindPopup(popupHtml, {maxWidth:260});
    mA.bindPopup(popupHtml, {maxWidth:260});
    line.bindPopup(popupHtml, {maxWidth:260});

    markers[t.id] = {mD, mA, line};
});

// Zoom sur tous les marqueurs si il y en a
function ajusterVueGlobale() {
    if (trajets.length > 0) {
        const allCoords = trajets.flatMap(t => [[t.lat_d, t.lng_d],[t.lat_a, t.lng_a]]);
        map.fitBounds(allCoords, {padding:[30,30]});
    }
}

// Leaflet calcule mal sa taille tant que la mise en page n'est pas stabilisée
// (panneau « sticky » dans une grille) : on force le recalcul une fois la page
// chargée, sinon les tuiles restent grises et la carte ne suit pas les actions.
map.whenReady(() => setTimeout(() => { map.invalidateSize(); ajusterVueGlobale(); }, 0));
window.addEventListener('load', () => { map.invalidateSize(); ajusterVueGlobale(); });
window.addEventListener('resize', () => map.invalidateSize());

// ── Interaction carte ↔ liste ─────────────────────────────────
let activeId = null;

function highlightTrajet(id, latD, lngD, latA, lngA) {
    // Reset previous
    if (activeId && markers[activeId]) {
        markers[activeId].line.setStyle({color:'#00853F', weight:3, opacity:.6, dashArray:'6,4'});
    }
    document.querySelectorAll('.trajet-card').forEach(c => c.classList.remove('highlighted'));

    activeId = id;
    const card = document.getElementById('card-'+id);
    if (card) { card.classList.add('highlighted'); card.scrollIntoView({behavior:'smooth',block:'nearest'}); }

    if (markers[id]) {
        markers[id].line.setStyle({color:'#00853F', weight:5, opacity:1, dashArray:null});
        map.invalidateSize();   // garantit un recentrage correct au clic
        map.fitBounds([[latD,lngD],[latA,lngA]], {padding:[40,40]});
        markers[id].mD.openPopup();
    }
}

// Clic sur marqueur → highlight carte
Object.entries(markers).forEach(([id, m]) => {
    [m.mD, m.mA, m.line].forEach(layer => {
        layer.on('click', () => {
            const t = trajets.find(x => x.id == id);
            if (t) highlightTrajet(parseInt(id), t.lat_d, t.lng_d, t.lat_a, t.lng_a);
        });
    });
});

// ── Modal réservation ─────────────────────────────────────────
function ouvrirModal(id, depart, arrivee, date, prix) {
    document.getElementById('modalTrajetId').value = id;
    document.getElementById('modalContent').innerHTML =
        '<strong>Trajet :</strong> ' + depart + ' → ' + arrivee + '<br>' +
        '<strong>Date :</strong> ' + date + '<br>' +
        '<strong>Prix :</strong> ' + prix.toLocaleString('fr-FR') + ' FCFA/place<br><br>' +
        '<span style="font-size:13px;color:var(--muted)">Le règlement se fait directement au conducteur le jour du trajet.</span>';
    document.getElementById('modalOverlay').classList.add('open');
}
function fermerModal() { document.getElementById('modalOverlay').classList.remove('open'); }
document.getElementById('modalOverlay').addEventListener('click', e => { if(e.target===e.currentTarget) fermerModal(); });
</script>
</body>
</html>
