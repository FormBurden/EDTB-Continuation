<?php
declare(strict_types=1);
$root = dirname(__DIR__);
require_once $root . '/style/Header.php';
require_once __DIR__ . '/../style/Footer.php';

$header = new Header();
$pageTitle = 'Galaxy Map';
$header->displayHeader();
?>
<div class="container" style="padding:30px;">
  <h2>Galaxy Map (JSON-backed)</h2>

  <form id="galmap-form" class="form-inline" style="margin-bottom:12px;">
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
      <label>limit <input id="limit" name="limit" type="number" value="15000" style="width:90px;"></label>
      <label>maxdistance <input id="maxdistance" name="maxdistance" type="number" value="50" style="width:90px;"></label>

      <label>center system <input id="center_system" name="center_system" type="text" placeholder="Sol" list="system-suggest" style="width:160px;"></label>
      <label>x <input id="centerX" name="centerX" type="text" style="width:80x;"></label>
      <label>y <input id="centerY" name="centerY" type="text" style="width:80px;"></label>
      <label>z <input id="centerZ" name="centerZ" type="text" style="width:80px;"></label>

      <label><input id="visited_only" name="visited_only" type="checkbox" value="1"> visited only</label>
      <label><input id="bookmarked_only" name="bookmarked_only" type="checkbox" value="1"> bookmarked only</label>

      <button type="submit">Apply</button>
    </div>
  </form>

  <div>
    <strong>Results:</strong> <span id="results-count">0</span>
    <div id="results-list" style="margin-top:6px; font-size: 0.95em; color:#ccc;">(nothing yet)</div>
  </div>

  <hr>
  <p style="font-size:0.9em;opacity:.8">Data source: <code>/GalMap/getMapPoints.json.php</code> (works even without player logs; user tables are optional joins).</p>
</div>
<script src="/GalMap/js/galmap.js"></script>
<script>
// Fallback type-ahead in case /GalMap/js/galmap.js isn't loaded or errors out
(function(){
  if (window.__galmap_suggest_ready__) return;
  window.__galmap_suggest_ready__ = true;

  const csInput = document.getElementById('center_system');
  const dl = document.getElementById('system-suggest');
  const cx = document.getElementById('centerX');
  const cy = document.getElementById('centerY');
  const cz = document.getElementById('centerZ');

  if (!csInput || !dl) return;

  const cache = new Map();
  let t;

  function debounce(fn, ms){ return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; }

  async function updateSuggestions(){
    const q = (csInput.value||'').trim();
    if (q.length < 2) { dl.innerHTML=''; return; }
    try {
      const res = await fetch(`/GalMap/getSystemNames.json.php?q=${encodeURIComponent(q)}&limit=15`, { cache: 'no-store' });
      const data = await res.json();
      const arr = Array.isArray(data?.suggestions) ? data.suggestions : [];
      dl.innerHTML = '';
      cache.clear();
      for (const s of arr) {
        if (!s?.name) continue;
        const opt = document.createElement('option');
        opt.value = s.name;
        dl.appendChild(opt);
        cache.set(String(s.name).toLowerCase(), s);
      }
    } catch (e) {
      // silent
    }
  }

  function maybeAutofill(){
    const k = (csInput.value||'').trim().toLowerCase();
    const hit = cache.get(k);
    if (hit && typeof hit.x === 'number') {
      if (cx) cx.value = String(hit.x);
      if (cy) cy.value = String(hit.y);
      if (cz) cz.value = String(hit.z);
    }
  }

  csInput.addEventListener('input', debounce(()=>{
    if (cx) cx.value = '';
    if (cy) cy.value = '';
    if (cz) cz.value = '';
    updateSuggestions();
  }, 200));

  csInput.addEventListener('change', maybeAutofill);
  csInput.addEventListener('blur', maybeAutofill);
})();
</script>

<?php
$footer = new Footer();
$footer->displayFooter();

