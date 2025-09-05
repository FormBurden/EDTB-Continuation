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

  async function load(){
    setLog('Loading…');
    try{
      const data = await fetchData();
      renderSystemTitle(data?.system_title || '');
      renderSystemInfo(data?.system_info || {});
      renderStations(data?.station_data || []);
      const extra = {
        request,
        keys: Object.keys(data || {}),
        station_count: Array.isArray(data?.station_data) ? data.station_data.length : 0
      };
      setLog({ status: 'ok', ...extra });
    }catch(err){
      setLog({ status: 'error', message: err?.message || String(err) });
    }
  }

  refreshBtn?.addEventListener('click', load);
  document.addEventListener('DOMContentLoaded', load);
  // If DOMContentLoaded already fired (defer), call directly:
  if (document.readyState === 'interactive' || document.readyState === 'complete'){
    load();
  }
})();
