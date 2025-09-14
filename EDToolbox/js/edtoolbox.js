// Path: /EDToolbox/js/edtoolbox.js
(function(){
  const root = document.getElementById('edtbx-root');
  const request = root?.dataset?.request || '0';
  const refreshBtn = document.getElementById('edtbx-refresh');
  const titleEl = document.getElementById('edtbx-system-title');
  const infoEl = document.getElementById('edtbx-system-info');
  const table = document.getElementById('edtbx-stations');
  const thead = table.querySelector('thead');
  const tbody = table.querySelector('tbody');
  const logEl = document.getElementById('edtbx-log');

  function fmtKey(k){
    return (k || '')
      .replace(/_/g, ' ')
      .replace(/\b\w/g, s => s.toUpperCase());
  }

  function setLog(msg){
    if (typeof msg === 'string') {
      logEl.textContent = msg;
    } else {
      try {
        logEl.textContent = JSON.stringify(msg, null, 2);
      } catch(e) {
        logEl.textContent = String(msg);
      }
    }
  }

  async function fetchData(){
    const url = `${window.EDTBX_CFG.api}?request=${encodeURIComponent(request)}`;
    const res = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    return data;
  }

  function renderSystemTitle(val){
    titleEl.textContent = val || '—';
  }

  function renderSystemInfo(obj){
    infoEl.innerHTML = '';
    if (!obj || typeof obj !== 'object') return;
    const frag = document.createDocumentFragment();
    Object.entries(obj).forEach(([k, v]) => {
      const row = document.createElement('div'); row.className = 'edtbx-kv';
      const key = document.createElement('div'); key.className = 'edtbx-k'; key.textContent = fmtKey(k);
      const val = document.createElement('div'); val.className = 'edtbx-v'; val.textContent = (v == null ? '' : String(v));
      row.appendChild(key); row.appendChild(val);
      frag.appendChild(row);
    });
    infoEl.appendChild(frag);
  }

  function renderStations(rows){
    thead.innerHTML = '';
    tbody.innerHTML = '';
    if (!Array.isArray(rows) || rows.length === 0) return;

    const cols = Object.keys(rows[0]);
    const trh = document.createElement('tr');
    for (const c of cols){
      const th = document.createElement('th');
      th.textContent = fmtKey(c);
      trh.appendChild(th);
    }
    thead.appendChild(trh);

    const frag = document.createDocumentFragment();
    for (const r of rows){
      const tr = document.createElement('tr');
      for (const c of cols){
        const td = document.createElement('td');
        const v = r[c];
        td.textContent = (v == null ? '' : String(v));
        tr.appendChild(td);
      }
      frag.appendChild(tr);
    }
    tbody.appendChild(frag);
  }

  async function load() {
    // 1) Get system name from Journal
    const sys = await edtbxCurrentSystem();

    // 2) Reflect immediately in the header
    var titleEl = document.getElementById('edtbx-system-title');
    if (titleEl) titleEl.textContent = sys || '—';

    // 3) Fetch System Info JSON for this system (si_name, si_stations, si_detailed)
    const res = await fetch(edtbxApiUrlForSystem(sys), { credentials: 'same-origin' });
    const data = await res.json();

    // 4) Render into ED ToolBox using existing renderers
    if (typeof renderSystem === 'function') renderSystem(data);
    if (typeof renderStations === 'function') renderStations(data);

    // 5) Show a minimal journal line in Logs for now
    await edtbxLoadLogsInto(document.getElementById('edtbx-log'), sys);
  }
  
  

  refreshBtn?.addEventListener('click', load);
  document.addEventListener('DOMContentLoaded', load);
  // If DOMContentLoaded already fired (defer), call directly:
  if (document.readyState === 'interactive' || document.readyState === 'complete'){
    load();
  }
})();
// === Journal wiring helpers (appended) ===
async function edtbxCurrentSystem() {
  const r = await fetch('/get/getData_status.php', { credentials: 'same-origin' });
  const j = await r.json();
  return (j.current_system && (j.current_system.name || j.current_system.system)) || '';
}
function edtbxApiUrlForSystem(sysName) {
  // API is set by partial.php to /System/getData_systemInfo.php
  return window.EDTBX_CFG.api + '?system_name=' + encodeURIComponent(sysName);
}
async function edtbxLoadLogsInto(el, sysName) {
  // For now, show a simple journal status line; we can swap to a logs endpoint later.
  if (!el) return;
  el.textContent = 'Journal wired: ' + (sysName || 'Unknown system');
}
// === ensure edtoolbox.js is (re)loaded, then call load()
(function ensureEdtbxScript() {
  var existing = document.querySelector('script[data-edtbx="1"]');
  if (existing) existing.remove(); // drop stale cached script

  var s = document.createElement('script');
  s.src = '/EDToolbox/js/edtoolbox.js?v=2'; // bump to v=2 so browser fetches new code
  s.setAttribute('data-edtbx', '1');
  s.defer = true;
  s.onload = function () {
    if (typeof load === 'function') { load(); }
  };
  document.head.appendChild(s);
})();
// === Journal wiring helpers (appended) ===
async function edtbxCurrentSystem() {
  const r = await fetch('/get/getData_status.php', { credentials: 'same-origin' });
  const j = await r.json();
  return (j.current_system && (j.current_system.name || j.current_system.system)) || '';
}
function edtbxApiUrlForSystem(sysName) {
  return (window.EDTBX_CFG && window.EDTBX_CFG.api ? window.EDTBX_CFG.api : '/System/getData_systemInfo.php')
    + '?system_name=' + encodeURIComponent(sysName);
}
async function edtbxLoadLogsInto(el, sysName) {
  if (!el) return;
  el.textContent = 'Journal wired: ' + (sysName || 'Unknown system');
}
// Open ToolBox when landing via redirect: /?go=EDToolbox
(function () {
  var p = new URLSearchParams(location.search);
  if (p.get('go') === 'EDToolbox') {
    if (typeof loadEDToolbox === 'function') {
      loadEDToolbox('#scrollable', '0');
      $('#pageTitle').text('ED ToolBox');
      // highlight the left-nav row
      $('.leftpanel .links_link').removeClass('active');
      $('.leftpanel a[href="/EDToolbox/"] .links_link').addClass('active');
    }
  }
})();


