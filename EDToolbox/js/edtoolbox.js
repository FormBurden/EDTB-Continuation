// Path: /EDToolbox/js/edtoolbox.js
// Simple: one-time render + manual refresh. No timers, no loaders.
(function () {
	function $(sel) { return document.querySelector(sel); }
	function text(el, v) { if (el) el.textContent = v == null ? '' : String(v); }

	function setLog(msg) {
		var el = $('#edtbx-log');
		if (!el) return;
		if (typeof msg === 'string') { el.textContent = msg; return; }
		try { el.textContent = JSON.stringify(msg, null, 2); }
		catch (e) { el.textContent = String(msg); }
	}

	function fmtKey(k) {
		return (k || '').replace(/_/g, ' ').replace(/\b\w/g, s => s.toUpperCase());
	}

	function renderSystemTitle(val) {
		var el = $('#edtbx-system-title');
		text(el, val || '—');
	}

	function renderSystemInfo(obj) {
		var wrap = $('#edtbx-system-info');
		if (!wrap) return;
		wrap.innerHTML = '';
		if (!obj || typeof obj !== 'object') return;
		var frag = document.createDocumentFragment();
		Object.entries(obj).forEach(([k, v]) => {
			var row = document.createElement('div'); row.className = 'edtbx-kv';
			var kk = document.createElement('div'); kk.className = 'edtbx-k'; kk.textContent = fmtKey(k);
			var vv = document.createElement('div'); vv.className = 'edtbx-v'; vv.textContent = (v == null ? '' : String(v));
			row.appendChild(kk); row.appendChild(vv);
			frag.appendChild(row);
		});
		wrap.appendChild(frag);
	}

	function renderStations(rows) {
		var table = $('#edtbx-stations'); if (!table) return;
		var thead = table.querySelector('thead'), tbody = table.querySelector('tbody');
		thead.innerHTML = ''; tbody.innerHTML = '';
		if (!Array.isArray(rows) || rows.length === 0) return;

		var cols = Object.keys(rows[0]);
		var trh = document.createElement('tr');
		cols.forEach(c => { var th = document.createElement('th'); th.textContent = fmtKey(c); trh.appendChild(th); });
		thead.appendChild(trh);

		var frag = document.createDocumentFragment();
		rows.forEach(r => {
			var tr = document.createElement('tr');
			cols.forEach(c => { var td = document.createElement('td'); td.textContent = (r[c] == null ? '' : String(r[c])); tr.appendChild(td); });
			frag.appendChild(tr);
		});
		tbody.appendChild(frag);
	}

	async function loadOnce() {
		var root = document.getElementById('edtbx-root');
		if (!root) return;
		var cfg = window.EDTBX_CFG || {};
		var api = cfg.api || '/get/getData.php';
		var request = root.dataset.request || cfg.request || '0';

		setLog('Loading…');
		try {
			var url = api + '?request=' + encodeURIComponent(request);
			var res = await fetch(url, { cache: 'no-store' });
			if (!res.ok) throw new Error('HTTP ' + res.status);
			var data = await res.json();

			// Expect: si_name, si_detailed, si_stations
			renderSystemTitle(data.si_name);
			renderSystemInfo(data.si_detailed);
			renderStations(data.si_stations);

			setLog('Ready');
		} catch (e) {
			setLog(e);
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		var refresh = document.getElementById('edtbx-refresh');
		if (refresh) refresh.addEventListener('click', loadOnce);
		loadOnce();
	});
})();
  