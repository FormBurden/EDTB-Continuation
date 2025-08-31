<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use EDTB\Config;
use EDTB\DB;

$config = Config::fromEnv(__DIR__ . '/../');
$db     = DB::connect($config);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

switch ($path) {
    case '/':
    case '/health':
        header('Content-Type: application/json');
        echo json_encode([
            'ok'  => true,
            'php' => PHP_VERSION,
            'env' => $config->env,
            'db'  => $db->ping(),
        ], JSON_PRETTY_PRINT);
        exit;

    case '/api/systems/search':
        header('Content-Type: application/json');
        $q     = (string)($_GET['name'] ?? '');
        $limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));

        $stmt = $db->pdo()->prepare(
            "SELECT name, x, y, z
             FROM systems
             WHERE name LIKE :q
             ORDER BY name
             LIMIT :lim"
        );
        $stmt->bindValue(':q', '%' . $q . '%', PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode($stmt->fetchAll(), JSON_PRETTY_PRINT);
        exit;

    case '/api/systems/count':
        header('Content-Type: application/json');
        $cnt = (int)$db->pdo()->query("SELECT COUNT(*) FROM systems")->fetchColumn();
        echo json_encode(['count' => $cnt], JSON_PRETTY_PRINT);
        exit;

    case '/api/poi':
        // Simple JSON API for POIs
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'GET') {
            header('Content-Type: application/json');
            $system = isset($_GET['system']) ? (string)$_GET['system'] : null;
            $q      = isset($_GET['q']) ? (string)$_GET['q'] : null;
            $limit  = max(1, min(100, (int)($_GET['limit'] ?? 50)));

            $sql = "SELECT id, name, system_name AS system, x, y, z, notes, created_at, updated_at
                    FROM poi";
            $where = [];
            $params = [];

            if ($system) {
                $where[] = "system_name = :system";
                $params[':system'] = $system;
            }
            if ($q) {
                $where[] = "name LIKE :q";
                $params[':q'] = '%' . $q . '%';
            }
            if ($where) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            $sql .= " ORDER BY created_at DESC LIMIT :lim";

            $stmt = $db->pdo()->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode($stmt->fetchAll(), JSON_PRETTY_PRINT);
            exit;
        }

        if ($method === 'POST') {
            header('Content-Type: application/json');

            $raw = file_get_contents('php://input') ?: '';
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON body']);
                exit;
            }
            $name   = trim((string)($data['name'] ?? ''));
            $system = trim((string)($data['system'] ?? ''));
            $notes  = trim((string)($data['notes'] ?? ''));

            if ($name === '' || $system === '') {
                http_response_code(400);
                echo json_encode(['error' => 'name and system are required']);
                exit;
            }

            // ensure system exists and get coords
            $stmt = $db->pdo()->prepare("SELECT x,y,z FROM systems WHERE name = :n LIMIT 1");
            $stmt->execute([':n' => $system]);
            $coords = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$coords) {
                http_response_code(400);
                echo json_encode(['error' => 'Unknown system: ' . $system]);
                exit;
            }

            // Upsert POI (unique per name+system)
            $ins = $db->pdo()->prepare("
                INSERT INTO poi (name, system_name, x, y, z, notes)
                VALUES (:name, :system, :x, :y, :z, :notes)
                ON DUPLICATE KEY UPDATE
                  x = VALUES(x),
                  y = VALUES(y),
                  z = VALUES(z),
                  notes = VALUES(notes),
                  updated_at = CURRENT_TIMESTAMP
            ");
            $ins->execute([
                ':name'   => $name,
                ':system' => $system,
                ':x'      => (float)$coords['x'],
                ':y'      => (float)$coords['y'],
                ':z'      => (float)$coords['z'],
                ':notes'  => $notes,
            ]);

            echo json_encode(['ok' => true], JSON_PRETTY_PRINT);
            exit;
        }

        http_response_code(405);
        header('Allow: GET, POST');
        echo "Method Not Allowed";
        exit;

    case '/poi':
        // Minimal HTML page to add/list POIs
        header('Content-Type: text/html; charset=utf-8');
        ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>EDTB POI</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, sans-serif; margin: 2rem; }
form, .card { max-width: 720px; }
label { display:block; margin-top: .75rem; font-weight: 600; }
input, textarea { width: 100%; padding: .5rem; margin-top: .25rem; }
button { margin-top: .75rem; padding: .5rem .75rem; cursor: pointer; }
.list { margin-top: 2rem; }
.card { border: 1px solid #e3e3e3; border-radius: .5rem; padding: 1rem; margin-bottom: .75rem; }
.small { color: #666; font-size: .9rem; }
</style>
</head>
<body>
  <h1>Points of Interest</h1>

  <form id="poiForm">
    <label>POI name
      <input type="text" id="name" required placeholder="e.g., Home Base">
    </label>
    <label>System (type to search)
      <input list="systems" id="system" required placeholder="e.g., Sol">
      <datalist id="systems"></datalist>
    </label>
    <label>Notes (optional)
      <textarea id="notes" rows="3" placeholder="why this matters"></textarea>
    </label>
    <button type="submit">Save POI</button>
    <div class="small" id="msg"></div>
  </form>

  <div class="list">
    <h2>Recent POIs</h2>
    <div id="poiList"></div>
  </div>

<script>
const $ = sel => document.querySelector(sel);

async function fetchPOI() {
  const r = await fetch('/api/poi?limit=20');
  const data = await r.json();
  const list = $('#poiList');
  list.innerHTML = '';
  data.forEach(p => {
    const div = document.createElement('div');
    div.className = 'card';
    div.innerHTML = '<strong>' + p.name + '</strong> @ ' + p.system +
                    '<div class="small">Coords: [' + p.x + ', ' + p.y + ', ' + p.z + ']</div>' +
                    (p.notes ? ('<div>' + p.notes + '</div>') : '');
    list.appendChild(div);
  });
}

let searchTimer = null;
$('#system').addEventListener('input', () => {
  clearTimeout(searchTimer);
  const q = $('#system').value.trim();
  if (q.length < 2) return;
  searchTimer = setTimeout(async () => {
    const r = await fetch('/api/systems/search?name=' + encodeURIComponent(q) + '&limit=10');
    const data = await r.json();
    const dl = $('#systems');
    dl.innerHTML = '';
    data.forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.name;
      dl.appendChild(opt);
    });
  }, 250);
});

$('#poiForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const name = $('#name').value.trim();
  const system = $('#system').value.trim();
  const notes = $('#notes').value.trim();
  $('#msg').textContent = '';
  const r = await fetch('/api/poi', {
    method: 'POST',
    headers: { 'Content-Type':'application/json' },
    body: JSON.stringify({ name, system, notes })
  });
  if (r.ok) {
    $('#msg').textContent = 'Saved.';
    $('#name').value = '';
    // keep system to add many at same place if desired
    $('#notes').value = '';
    fetchPOI();
  } else {
    const txt = await r.text();
    $('#msg').textContent = 'Error: ' + txt;
  }
});

fetchPOI();
</script>
</body>
</html>
<?php
        exit;

    default:
        // Legacy fallback while we refactor
        $legacy = __DIR__ . '/../source' . $path;
        if (is_file($legacy)) {
            return require $legacy;
			// Always bring legacy compat online first
			require_once __DIR__ . '/../source/config.inc.php';
			return require $legacy;
        }
        http_response_code(404);
        echo "Not found";
        exit;
}
