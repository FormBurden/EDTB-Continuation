// GalMap type-ahead + Apply wiring (self-contained)
// - Creates <datalist id="system-suggest"> if missing
// - Binds to #center_system (or name="center_system"), sets list="system-suggest"
// - Fetches suggestions and autofills X/Y/Z on selection
// - Submits to getMapPoints.json.php on form submit

(function () {
	'use strict';

	function $(sel, root) { return (root || document).querySelector(sel); }
	function $id(id) { return document.getElementById(id); }

	document.addEventListener('DOMContentLoaded', () => {
		// --- locate inputs ---
		const csInput = $id('center_system') || $('input[name="center_system"]');
		const form = $id('galmap-form') || (csInput && csInput.closest('form')) || document.forms[0];

		const cx = $id('centerX') || $('input[name="centerX"]');
		const cy = $id('centerY') || $('input[name="centerY"]');
		const cz = $id('centerZ') || $('input[name="centerZ"]');

		const limitEl = $id('limit') || $('input[name="limit"]');
		const maxdEl = $id('maxdistance') || $('input[name="maxdistance"]');

		const outCount = $id('results-count');

		// --- ensure datalist exists and attach to input ---
		let dl = $id('system-suggest');
		if (!dl) {
			dl = document.createElement('datalist');
			dl.id = 'system-suggest';
			if (csInput) csInput.insertAdjacentElement('afterend', dl);
			else document.body.appendChild(dl);
		}
		if (csInput) csInput.setAttribute('list', 'system-suggest');

		// --- suggest cache + debounce ---
		const suggestCache = new Map(); // key: lowercased name -> {name,x,y,z}
		let t;
		const debounce = (fn, ms = 200) => (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };

		// --- fetch suggestions ---
		const updateSuggestions = async () => {
			const q = (csInput?.value || '').trim();
			if (!dl) return;
			if (q.length < 2) { dl.innerHTML = ''; return; }

			const url = `/GalMap/getSystemNames.json.php?q=${encodeURIComponent(q)}&limit=15`;
			let data = null;
			try {
				const res = await fetch(url, { cache: 'no-store' });
				data = await res.json();
			} catch (e) {
				console.error('suggest fetch failed', e);
				return;
			}

			const arr = Array.isArray(data?.suggestions) ? data.suggestions : [];
			dl.innerHTML = '';
			suggestCache.clear();

			for (const s of arr) {
				const name = (s.name ?? '').toString();
				const x = Number(s.x), y = Number(s.y), z = Number(s.z);
				const opt = document.createElement('option');
				opt.value = name;
				opt.label = `${name} — [${isFinite(x) ? x : '?'}, ${isFinite(y) ? y : '?'}, ${isFinite(z) ? z : '?'}]`;
				dl.appendChild(opt);
				suggestCache.set(name.toLowerCase(), { name, x, y, z });
			}
		};

		// --- choose -> autofill ---
		function maybeAutofill() {
			if (!csInput || !cx || !cy || !cz) return;
			const key = csInput.value.trim().toLowerCase();
			const hit = suggestCache.get(key);
			if (hit && isFinite(hit.x) && isFinite(hit.y) && isFinite(hit.z)) {
				cx.value = String(hit.x);
				cy.value = String(hit.y);
				cz.value = String(hit.z);
			}
		}

		// --- events for typing + choosing ---
		csInput?.addEventListener('input', debounce(() => {
			if (cx) cx.value = '';
			if (cy) cy.value = '';
			if (cz) cz.value = '';
			updateSuggestions();
		}, 200));
		csInput?.addEventListener('change', maybeAutofill);
		csInput?.addEventListener('blur', maybeAutofill);

		// --- build query from form ---
		function buildQueryFromForm() {
			const fd = new FormData(form);
			// Normalize legacy names
			if (!fd.get('center_system') && csInput?.value) fd.set('center_system', csInput.value);
			if (!fd.get('centerX') && cx?.value) fd.set('centerX', cx.value);
			if (!fd.get('centerY') && cy?.value) fd.set('centerY', cy.value);
			if (!fd.get('centerZ') && cz?.value) fd.set('centerZ', cz.value);

			const qs = new URLSearchParams(fd);
			if (!qs.get('limit')) qs.set('limit', '15000');
			if (!qs.get('maxdistance')) qs.set('maxdistance', '50');
			return qs;
		}

		// --- Apply (runs if a form exists) ---
		form?.addEventListener('submit', (e) => {
			e.preventDefault();
			const qs = buildQueryFromForm();
			const url = `/GalMap/getMapPoints.json.php?${qs.toString()}`;

			// fire up ED3D
			if (!window.Ed3d) {
				console.error('Ed3d not loaded');
				return;
			}
			// Ensure container exists
			const containerEl = $id('ed3dmap');
			if (containerEl) containerEl.innerHTML = '';

			Ed3d.init({
				container: 'ed3dmap',
				basePath: '/GalMap/Vendor/ED3D-Galaxy-Map/',
				jsonPath: url,
				withHudPanel: true,
				startAnim: true
			});
		});

		// --- auto-init on first load if the form has defaults ---
		if (form) {
			// Small delay to allow ED3D script to settle
			setTimeout(() => form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true })), 0);
		}
	});
})();
  