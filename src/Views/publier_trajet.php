<?php

/**
 * Vue de publication d'un trajet.
 *
 * Architecture MVC : aucune requête SQL ici. La vue interroge le
 * TrajetController pour savoir si l'utilisateur est conducteur validé et
 * pour récupérer ses véhicules (affichage), puis lui délègue la publication
 * (POST). Toute la logique métier et la validation vivent dans le contrôleur.
 */

session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/_urls.php';

use App\Controllers\TrajetController;
use App\Exceptions\KaayDemException;

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: {$viewsUrl}login.php?redirect=publier_trajet.php"); exit;
}

$userId     = (int) $_SESSION['utilisateur_id'];
$activePage = 'publier';
$flash      = null;

$controller   = new TrajetController();
$conducteurId = $controller->profilConducteurValide($userId);          // null si non validé
$vehicules    = $conducteurId ? $controller->vehiculesDe($conducteurId) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conducteurId) {
    try {
        $controller->publier($userId, $_POST);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Trajet publié avec succès !'];
        header("Location: mes_trajets.php"); exit;
    } catch (KaayDemException $e) {
        // Erreur métier (rôle non autorisé, données invalides) : message propre
        $flash = ['type' => 'error', 'msg' => $e->messageUtilisateur()];
    } catch (\Throwable $e) {
        $flash = ['type' => 'error', 'msg' => 'Erreur : ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Publier un trajet — Kaay Dem !</title>
<?php include __DIR__ . '/_style.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<style>
#map-preview{height:320px;border-radius:var(--radius);border:1.5px solid var(--border);margin-top:8px;display:none;position:relative;z-index:0}
.map-loading{display:none;align-items:center;gap:8px;color:var(--muted);font-size:14px;margin-top:8px}
.map-loading.show{display:flex}
.route-info{display:none;background:var(--green-light);border:1px solid rgba(21,208,224,.2);border-radius:var(--radius-sm);padding:12px 16px;margin-top:8px;font-size:14px;color:var(--green-dark);font-weight:600;gap:20px}
.route-info.show{display:flex;align-items:center;flex-wrap:wrap}
.autocomplete-wrap{position:relative}
.autocomplete-list{position:absolute;top:100%;left:0;right:0;background:white;border:1.5px solid var(--green);border-top:none;border-radius:0 0 var(--radius-sm) var(--radius-sm);z-index:1000;max-height:200px;overflow-y:auto;box-shadow:var(--shadow)}
.autocomplete-list div{padding:10px 14px;cursor:pointer;font-size:14px;color:var(--navy);border-bottom:1px solid var(--border)}
.autocomplete-list div:hover{background:var(--green-light);color:var(--green-dark)}
.autocomplete-list div:last-child{border-bottom:none}
[data-theme="dark"] .autocomplete-list{background:#1e293b;border-color:var(--green)}
[data-theme="dark"] .autocomplete-list div{color:#e2e8f0;border-color:#2d3748}
[data-theme="dark"] .autocomplete-list div:hover{background:rgba(21,208,224,.2)}
</style>
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>
<div class="page-md">
  <a href="dashboard.php" style="color:var(--muted);font-size:14px;font-weight:500;display:inline-block;margin-bottom:24px">← Mon espace</a>
  <h1 style="font-family:var(--font-head);font-size:26px;font-weight:800;color:var(--navy);margin-bottom:6px">🚘 Publier un trajet</h1>
  <p style="color:var(--muted);margin-bottom:28px">Proposez vos places disponibles aux autres étudiants.</p>

  <?php if($flash): ?>
  <div class="alert alert-<?= $flash['type']==='success'?'success':'error' ?>"><?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <?php if(!$conducteurId): ?>
  <div class="card"><div class="card-body" style="text-align:center;padding:50px">
    <div style="font-size:48px;margin-bottom:14px">🚘</div>
    <h3 style="font-family:var(--font-head);font-size:18px;font-weight:700;margin-bottom:8px">Profil conducteur requis</h3>
    <p style="color:var(--muted);margin-bottom:20px">Vous devez être conducteur validé pour publier un trajet.</p>
    <a href="devenir_conducteur.php" class="btn btn-green">Devenir conducteur</a>
  </div></div>
  <?php else: ?>
  <div class="card">
    <div class="card-body">
      <form method="POST" id="formTrajet">
        <!-- Champs cachés coordonnées -->
        <input type="hidden" name="lat_depart"  id="lat_depart"  value="">
        <input type="hidden" name="lng_depart"  id="lng_depart"  value="">
        <input type="hidden" name="lat_arrivee" id="lat_arrivee" value="">
        <input type="hidden" name="lng_arrivee" id="lng_arrivee" value="">
        <input type="hidden" name="distance_km" id="distance_km" value="">
        <input type="hidden" name="duree_min"   id="duree_min"   value="">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
          <div class="form-group">
            <label class="form-label">Ville de départ *</label>
            <div class="autocomplete-wrap">
              <input class="form-control" type="text" id="ville_depart" name="ville_depart"
                     placeholder="ex. Dakar, Yoff, Rufisque..." required autocomplete="off"
                     value="<?= htmlspecialchars($_POST['ville_depart']??'') ?>">
              <div class="autocomplete-list" id="list_depart"></div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Ville d'arrivée *</label>
            <div class="autocomplete-wrap">
              <input class="form-control" type="text" id="ville_arrivee" name="ville_arrivee"
                     placeholder="ex. Diamniadio, Thiès..." required autocomplete="off"
                     value="<?= htmlspecialchars($_POST['ville_arrivee']??'') ?>">
              <div class="autocomplete-list" id="list_arrivee"></div>
            </div>
          </div>
        </div>

        <!-- Carte + infos itinéraire -->
        <div class="map-loading" id="mapLoading">
          <div style="width:16px;height:16px;border:2px solid var(--green);border-top-color:transparent;border-radius:50%;animation:spin .8s linear infinite"></div>
          Calcul de l'itinéraire...
        </div>
        <div class="route-info" id="routeInfo">
          <span>📍 <strong id="routeDist">—</strong></span>
          <span>⏱️ <strong id="routeDuree">—</strong></span>
          <span style="font-size:12px;color:var(--green-dark);font-weight:400">Itinéraire calculé via OpenStreetMap</span>
        </div>
        <div id="map-preview"></div>

        <div class="form-group" style="margin-top:20px">
          <label class="form-label">Points d'arrêt (optionnel)</label>
          <input class="form-control" type="text" name="points_arret" placeholder="ex. Rufisque, Bargny" value="<?= htmlspecialchars($_POST['points_arret']??'') ?>">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
          <div class="form-group">
            <label class="form-label">Date de départ *</label>
            <input class="form-control" type="date" name="date_depart" required
                   min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['date_depart']??date('Y-m-d')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Heure de départ *</label>
            <input class="form-control" type="time" name="heure_depart" required value="<?= htmlspecialchars($_POST['heure_depart']??'07:30') ?>">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
          <div class="form-group">
            <label class="form-label">Nombre de places *</label>
            <input class="form-control" type="number" name="nb_places" min="1" max="7" placeholder="ex. 3" required value="<?= htmlspecialchars($_POST['nb_places']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Prix par place (FCFA) *</label>
            <input class="form-control" type="number" name="prix_par_place" min="0" step="50" placeholder="ex. 500" required value="<?= htmlspecialchars($_POST['prix_par_place']??'') ?>">
          </div>
        </div>
        <?php if($vehicules): ?>
        <div class="form-group">
          <label class="form-label">Véhicule</label>
          <select class="form-control" name="vehicule_id">
            <option value="">— Sélectionner un véhicule (optionnel)</option>
            <?php foreach($vehicules as $v): ?>
            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['marque'].' '.$v['modele'].' ('.$v['immatriculation'].')') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="form-group">
          <label class="form-label">Description (optionnel)</label>
          <textarea class="form-control" name="description" rows="3" placeholder="Point de rendez-vous, bagages autorisés..."><?= htmlspecialchars($_POST['description']??'') ?></textarea>
        </div>
        <button type="submit" class="btn btn-green btn-lg" style="width:100%;justify-content:center;margin-top:8px">
          Publier le trajet →
        </button>
      </form>
    </div>
  </div>
  <?php endif; ?>
</div>
<footer><div class="footer-stripe"></div><strong>Kaay Dem !</strong> — ISM Campus Baobab</footer>

<style>
@keyframes spin{to{transform:rotate(360deg)}}
</style>
<script>
// ── Autocomplétion Nominatim ──────────────────────────────────
let mapInstance = null;
let routeLayer  = null;
let markerD = null, markerA = null;
let coordsDepart = null, coordsArrivee = null;
let debounceT = {};

function geocode(query, listEl, onSelect) {
    if (query.length < 3) { listEl.innerHTML = ''; return; }
    clearTimeout(debounceT[listEl.id]);
    debounceT[listEl.id] = setTimeout(async () => {
        try {
            const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=5&countrycodes=sn&accept-language=fr`;
            const res = await fetch(url, {headers:{'Accept-Language':'fr'}});
            const data = await res.json();
            listEl.innerHTML = '';
            if (!data.length) {
                listEl.innerHTML = '<div style="color:var(--muted)">Aucun résultat</div>';
                return;
            }
            data.forEach(place => {
                const div = document.createElement('div');
                // Nom court : on retire le pays
                const parts = place.display_name.split(',');
                div.textContent = parts.slice(0, 3).join(',').trim();
                div.addEventListener('click', () => {
                    onSelect(div.textContent, parseFloat(place.lat), parseFloat(place.lon));
                    listEl.innerHTML = '';
                });
                listEl.appendChild(div);
            });
        } catch(e) { listEl.innerHTML = ''; }
    }, 350);
}

// Champ départ
document.getElementById('ville_depart').addEventListener('input', function() {
    coordsDepart = null;
    document.getElementById('lat_depart').value = '';
    document.getElementById('lng_depart').value = '';
    geocode(this.value, document.getElementById('list_depart'), (nom, lat, lng) => {
        document.getElementById('ville_depart').value = nom;
        document.getElementById('lat_depart').value = lat;
        document.getElementById('lng_depart').value = lng;
        coordsDepart = [lat, lng];
        if (coordsArrivee) calculerItineraire();
    });
});

// Champ arrivée
document.getElementById('ville_arrivee').addEventListener('input', function() {
    coordsArrivee = null;
    document.getElementById('lat_arrivee').value = '';
    document.getElementById('lng_arrivee').value = '';
    geocode(this.value, document.getElementById('list_arrivee'), (nom, lat, lng) => {
        document.getElementById('ville_arrivee').value = nom;
        document.getElementById('lat_arrivee').value = lat;
        document.getElementById('lng_arrivee').value = lng;
        coordsArrivee = [lat, lng];
        if (coordsDepart) calculerItineraire();
    });
});

// Fermer les listes si clic ailleurs
document.addEventListener('click', e => {
    if (!e.target.closest('.autocomplete-wrap')) {
        document.querySelectorAll('.autocomplete-list').forEach(l => l.innerHTML = '');
    }
});

// ── Leaflet + OSRM ───────────────────────────────────────────
async function calculerItineraire() {
    const mapEl = document.getElementById('map-preview');
    const loading = document.getElementById('mapLoading');
    const routeInfo = document.getElementById('routeInfo');

    loading.classList.add('show');
    mapEl.style.display = 'block';

    // Init carte si besoin
    if (!mapInstance) {
        mapInstance = L.map('map-preview').setView([14.7167, -17.4677], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
            maxZoom: 18
        }).addTo(mapInstance);
    }

    // Marqueurs
    const iconD = L.divIcon({className:'',html:'<div style="background:#00853F;width:14px;height:14px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,.4)"></div>'});
    const iconA = L.divIcon({className:'',html:'<div style="background:#E31E24;width:14px;height:14px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,.4)"></div>'});

    if (markerD) markerD.remove();
    if (markerA) markerA.remove();
    markerD = L.marker(coordsDepart, {icon:iconD}).addTo(mapInstance)
               .bindPopup('<strong>Départ</strong><br>'+document.getElementById('ville_depart').value);
    markerA = L.marker(coordsArrivee, {icon:iconA}).addTo(mapInstance)
               .bindPopup('<strong>Arrivée</strong><br>'+document.getElementById('ville_arrivee').value);

    try {
        // OSRM routing
        const url = `https://router.project-osrm.org/route/v1/driving/${coordsDepart[1]},${coordsDepart[0]};${coordsArrivee[1]},${coordsArrivee[0]}?overview=full&geometries=geojson`;
        const res  = await fetch(url);
        const data = await res.json();

        if (data.routes && data.routes[0]) {
            const route = data.routes[0];
            const distKm  = (route.distance / 1000).toFixed(1);
            const dureeMin = Math.round(route.duration / 60);

            // Stocker dans les champs cachés
            document.getElementById('distance_km').value = distKm;
            document.getElementById('duree_min').value   = dureeMin;

            // Afficher
            document.getElementById('routeDist').textContent  = distKm + ' km';
            document.getElementById('routeDuree').textContent = dureeMin >= 60
                ? Math.floor(dureeMin/60)+'h'+(dureeMin%60?dureeMin%60+'min':'')
                : dureeMin + ' min';
            routeInfo.classList.add('show');

            // Tracé itinéraire
            if (routeLayer) routeLayer.remove();
            routeLayer = L.geoJSON(route.geometry, {
                style: {color:'#00853F', weight:5, opacity:.8}
            }).addTo(mapInstance);

            // Zoom sur l'itinéraire
            mapInstance.fitBounds(routeLayer.getBounds(), {padding:[30,30]});
        } else {
            // Fallback : juste une ligne droite
            if (routeLayer) routeLayer.remove();
            routeLayer = L.polyline([coordsDepart, coordsArrivee], {color:'#00853F',weight:4,dashArray:'8,6'}).addTo(mapInstance);
            mapInstance.fitBounds(routeLayer.getBounds(), {padding:[30,30]});
        }
    } catch(e) {
        // OSRM indisponible : ligne droite
        if (routeLayer) routeLayer.remove();
        routeLayer = L.polyline([coordsDepart, coordsArrivee], {color:'#00853F',weight:4,dashArray:'8,6'}).addTo(mapInstance);
        mapInstance.fitBounds(routeLayer.getBounds(), {padding:[30,30]});
    }

    loading.classList.remove('show');
    // Fix taille carte après affichage
    setTimeout(() => mapInstance && mapInstance.invalidateSize(), 100);
}
</script>
</body>
</html>
