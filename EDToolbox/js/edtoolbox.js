// /EDToolbox/js/edtoolbox.js — Commander’s Log wiring with robust fallbacks
(function () {
	function $(sel) { return document.querySelector(sel); }
	function $all(sel) { return Array.prototype.slice.call(document.querySelectorAll(sel)); }
	function stripTags(html) { return html ? String(html).replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim() : ''; }
	function setStatus(msg) { var s = $('#edtbx-logstatus'); if (s) s.textContent = msg || ''; }

	// Derive current system name from /get/getData.php payload
	async function getCurrentSystem() {
		const cfg = (window.EDTBX_CFG || {});
		const api = cfg.api || '/get/getData.php';
		const req = cfg.request || '0';
		const res = await fetch(api + '?request=' + encodeURIComponent(req), { cache: 'no-store' });
		if (!res.ok) throw new Error('HTTP ' + res.status);
		const data = await res.json();

		let sys = '';
		if (data && data.si_detailed) sys = data.si_detailed.system_name || data.si_detailed.name || '';
		if (!sys) sys = stripTags(data.si_name || '');
		return sys;
	}

	// Try a single HTTP request and coerce result to {kind, data}
	async function requestOnce(url, opts) {
		const res = await fetch(url, opts);
		const text = await res.text();
		try {
			const json = JSON.parse(text);
			if (Array.isArray(json) ? json.length : Object.keys(json || {}).length) {
				return { kind: 'json', data: json };
			}
		} catch (_) { /* not JSON */ }

		// If HTML/text looks non-empty, pass it through
		if (text && String(text).trim().length > 0) {
			return { kind: 'html', data: text };
		}
		return null;
	}

	// Attempt matrix: param key combos × method (GET→POST) until something non-empty returns
	async function fetchLogs(systemName, category, limit) {
		const sysKeys = ['system_name', 'system', 'sys'];
		const catKeys = ['category', 'type', 'topic', 'tag'];
		const base = '/Log/getData_logs.php';

		const attempts = [];

		// Build parameter permutations (GET first)
		for (const sk of sysKeys) {
			for (const ck of catKeys) {
				const qs = new URLSearchParams();
				if (systemName) qs.set(sk, systemName);
				if (category) qs.set(ck, category);
				if (limit) qs.set('limit', String(limit));
				attempts.push({ method: 'GET', url: `${base}?${qs.toString()}`, body: null, desc: `GET ${sk}+${ck}` });
			}
		}
		// Then POST variants
		for (const sk of sysKeys) {
			for (const ck of catKeys) {
				const form = new URLSearchParams();
				if (systemName) form.set(sk, systemName);
				if (category) form.set(ck, category);
				if (limit) form.set('limit', String(limit));
				attempts.push({ method: 'POST', url: base, body: form.toString(), desc: `POST ${sk}+${ck}` });
			}
		}

		// Run attempts until one yields content
		for (const a of attempts) {
			try {
				const result = await requestOnce(a.url, a.method === 'POST'
					? { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: a.body, cache: 'no-store' }
					: { cache: 'no-store' });
				if (result) {
					result._attempt = a.desc;
					return result;
				}
			} catch (e) {
				// keep trying
			}
		}
		return { kind: 'html', data: '', _attempt: 'no-data' };
	}

	// Render list under chips
	function renderLogs(result) {
		const ul = $('#edtbx-loglist');
		if (!ul) return;

		if (!result || (!result.data && result.data !== 0)) {
			ul.innerHTML = '<li class="log-empty">No data</li>';
			return;
		}

		// If HTML snippet returned, inject it inside one container item
		if (result.kind === 'html') {
			const html = String(result.data || '').trim();
			if (!html) {
				ul.innerHTML = '<li class="log-empty">No entries</li>';
			} else {
				ul.innerHTML = '<li class="log-html">' + html + '</li>';
			}
			return;
		}

		// JSON array → render rows
		const rows = Array.isArray(result.data) ? result.data : [];
		if (rows.length === 0) {
			ul.innerHTML = '<li class="log-empty">No entries</li>';
			return;
		}

		const frag = document.createDocumentFragment();
		rows.forEach((r) => {
			const li = document.createElement('li'); li.className = 'log-row';

			const title = (r.title || r.name || r.header || '');
			if (title) {
				const t = document.createElement('div'); t.className = 'log-title'; t.textContent = String(title);
				li.appendChild(t);
			}

			const when = (r.time || r.date || r.ts || '');
			if (when) {
				const dt = document.createElement('div'); dt.className = 'log-time';
				dt.textContent = String(when);
				li.appendChild(dt);
			}

			const text = (r.text || r.desc || r.body || r.content || '');
			if (text) {
				const d = document.createElement('div'); d.className = 'log-text';
				d.textContent = String(text);
				li.appendChild(d);
			}

			frag.appendChild(li);
		});
		ul.innerHTML = '';
		ul.appendChild(frag);
	}

	async function activateChip(btn, systemName) {
		$all('#edtbx-chips .edtbx-chip').forEach(b => b.classList.remove('is-active'));
		if (btn) btn.classList.add('is-active');

		const cat = (btn && btn.dataset.cat) || 'smuggling';
		setStatus(`Loading ${cat}…`);
		const result = await fetchLogs(systemName, cat, 8);
		renderLogs(result);
		if (result && result._attempt) setStatus(`Loaded via ${result._attempt}`);
		else setStatus('');
	}

	document.addEventListener('DOMContentLoaded', async function () {
		const root = $('#edtbx-root');
		if (!root) return;

		// Determine current system name once
		let systemName = '';
		try { systemName = await getCurrentSystem(); }
		catch (e) { systemName = ''; }

		// Wire chips
		const chips = $all('#edtbx-chips .edtbx-chip');
		chips.forEach((btn) => { btn.addEventListener('click', function () { activateChip(btn, systemName); }); });

		// Initial category
		const first = chips[0] || null;
		activateChip(first, systemName);
	});
})();
  