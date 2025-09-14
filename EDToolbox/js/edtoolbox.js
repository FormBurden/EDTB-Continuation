// /EDToolbox/js/edtoolbox.js — Commander’s Log wiring (chips -> Log/getData_logs.php)
(function () {
	function $(sel) { return document.querySelector(sel); }
	function $all(sel) { return Array.prototype.slice.call(document.querySelectorAll(sel)); }
	function stripTags(html) {
		if (html == null) return '';
		return String(html).replace(/<[^>]*>/g, '').trim();
	}

	// Derive current system name from /get/getData.php payload
	async function getCurrentSystem() {
		const cfg = (window.EDTBX_CFG || {});
		const api = cfg.api || '/get/getData.php';
		const req = cfg.request || '0';
		const res = await fetch(api + '?request=' + encodeURIComponent(req), { cache: 'no-store' });
		if (!res.ok) throw new Error('HTTP ' + res.status);
		const data = await res.json();

		// Prefer a clean field inside si_detailed if available; else strip tags from si_name
		let sys = '';
		if (data && data.si_detailed) {
			sys = data.si_detailed.system_name || data.si_detailed.name || '';
		}
		if (!sys) sys = stripTags(data.si_name || '');
		return sys;
	}

	// Fetch logs list for a category + system
	async function fetchLogs(systemName, category, limit) {
		const params = new URLSearchParams();
		if (systemName) params.set('system_name', systemName);
		if (category) params.set('category', category);
		if (limit) params.set('limit', String(limit));

		const url = '/Log/getData_logs.php?' + params.toString();
		const res = await fetch(url, { cache: 'no-store' });

		// Endpoint may return JSON or HTML/text; try JSON first, else text
		const txt = await res.text();
		try { return { kind: 'json', data: JSON.parse(txt) }; }
		catch { return { kind: 'html', data: txt }; }
	}

	// Render list under chips
	function renderLogs(result) {
		const ul = $('#edtbx-loglist');
		if (!ul) return;

		if (!result) { ul.innerHTML = '<li class="log-empty">No data</li>'; return; }

		if (result.kind === 'html') {
			// The endpoint already produced HTML rows; inject safely
			ul.innerHTML = '<li class="log-html">' + result.data + '</li>';
			return;
		}

		// JSON array → render simple rows (title + time if present)
		const rows = Array.isArray(result.data) ? result.data : [];
		if (rows.length === 0) {
			ul.innerHTML = '<li class="log-empty">No entries</li>';
			return;
		}

		const frag = document.createDocumentFragment();
		rows.forEach((r) => {
			const li = document.createElement('li'); li.className = 'log-row';
			const t = document.createElement('div'); t.className = 'log-title'; t.textContent = (r.title || r.name || r.header || '—');
			li.appendChild(t);

			if (r.time || r.date || r.ts) {
				const dt = document.createElement('div'); dt.className = 'log-time';
				dt.textContent = String(r.time || r.date || r.ts);
				li.appendChild(dt);
			}

			if (r.text || r.desc || r.body) {
				const d = document.createElement('div'); d.className = 'log-text';
				d.textContent = String(r.text || r.desc || r.body);
				li.appendChild(d);
			}

			frag.appendChild(li);
		});
		ul.innerHTML = '';
		ul.appendChild(frag);
	}

	// Activate a chip, fetch, and render
	async function activateChip(btn, systemName) {
		$all('#edtbx-chips .edtbx-chip').forEach(b => b.classList.remove('is-active'));
		if (btn) btn.classList.add('is-active');

		const cat = (btn && btn.dataset.cat) || 'smuggling';
		const result = await fetchLogs(systemName, cat, 8);
		renderLogs(result);
	}

	document.addEventListener('DOMContentLoaded', async function () {
		// Fallback: mark the wrapper so CSS can make the background transparent
		(document.querySelector('#content') || document.querySelector('#container') || document.body).classList.add('edtbx-page');
		const root = $('#edtbx-root');
		if (!root) return;

		// Determine current system name once
		let systemName = '';
		try { systemName = await getCurrentSystem(); }
		catch (e) { systemName = ''; }

		// Wire chips
		const chips = $all('#edtbx-chips .edtbx-chip');
		chips.forEach((btn) => {
			btn.addEventListener('click', function () {
				activateChip(btn, systemName);
			});
		});

		// Initial category
		const first = chips[0] || null;
		activateChip(first, systemName);
	});
})();
  