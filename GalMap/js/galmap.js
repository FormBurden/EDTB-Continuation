// GalMap type-ahead + Apply wiring (single source of truth; no duplicate engines)
// - Ensures exactly one <datalist id="system-suggest"> exists
// - Binds to #center_system (or name="center_system")
// - Suggests by /GalMap/getSystemNames.json.php
// - On select, autofills X/Y/Z (with server fallback)
// - Submits to ED3D with jsonPath = /GalMap/getMapPoints.json.php?... 
(function () {
	'use strict';

	if (window.__galmap_suggest_ready__) return;
	window.__galmap_suggest_ready__ = true;

	function $(sel, root) { return (root || document).querySelector(sel); }
	function $id(id) { return document.getElementById(id); }
	function isFiniteNum(v) { return typeof v === 'number' && isFinite(v); }

	document.addEventListener('DOMContentLoaded', () => {
		const csInput = $id('center_system') || $('input[name="center_system"]');
		const form = $id('galmap-form') || (csInput && csInput.closest('form')) || document.forms[0];

		const cx = $id('centerX') || $('input[name="centerX"]');
		const cy = $id('centerY') || $('input[name="centerY"]');
		const cz = $id('centerZ') || $('input[name="centerZ"]');

		// ——— kill any server-rendered duplicate datalists, keep exactly one
		const existing = Array.from(document.querySelectorAll('datalist#system-suggest'));
		for (let i = 1; i < existing.length; i++) existing[i].remove();
		let dl = existing[0];
		if (!dl) {
			dl = document.createElement('datalist');
			dl.id = 'system-suggest';
			(csInput || document.body).insertAdjacentElement('afterend', dl);
		}
		if (csInput) csInput.setAttribute('list', 'system-suggest');

		// UI layering: keep form above ED3D always
		const mapEl = $id('ed3dmap');
		if (form) { form.style.position = 'relative'; form.style.zIndex = '50'; }
		if (mapEl) { mapEl.style.position = 'relative'; mapEl.style.zIndex = '1'; }
		// Add a small spacing so canvas never sits under inputs
		if (mapEl) { mapEl.style.marginTop = '8px'; }

		// ——— suggest cache + debounce
		const suggestCache = new Map(); // key: lowercased name -> {name,x,y,z}
		let t, inflight;
		const debounce = (fn, ms = 180) => (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };

		async function updateSuggestions() {
			const q = (csInput?.value || '').trim();
			if (!dl || q.length < 2) { if (dl) dl.innerHTML = ''; return; }

			// cancel any previous (best-effort)
			try { inflight?.abort?.(); } catch (e) { }
			inflight = new AbortController();

			let data = null;
			try {
				const res = await fetch(`/GalMap/getSystemNames.json.php?q=${encodeURIComponent(q)}&limit=15`, {
					cache: 'no-store', signal: inflight.signal
				});
				data = await res.json();
			} catch (e) {
				console.error('suggest fetch failed', e);
				return;
			}

			const arr = Array.isArray(data?.suggestions) ? data.suggestions : [];
			dl.innerHTML = '';
			suggestCache.clear();

			// Important: do NOT set opt.label — that was causing the “--- [x,y,z]” duplicates in some browsers
			for (const s of arr) {
				const name = (s.name ?? '').toString();
				const x = Number(s.x), y = Number(s.y), z = Number(s.z);
				const opt = document.createElement('option');
				opt.value = name;
				if (isFiniteNum(x) && isFiniteNum(y) && isFiniteNum(z)) {
					opt.dataset.x = String(x);
					opt.dataset.y = String(y);
					opt.dataset.z = String(z);
				}
				dl.appendChild(opt);
				suggestCache.set(name.toLowerCase(), { name, x, y, z });
			}
			  
			  
		}

		function fillCoords(hit) {
			if (!hit) return false;
			if (!isFiniteNum(hit.x) || !isFiniteNum(hit.y) || !isFiniteNum(hit.z)) return false;
			if (cx) cx.value = String(hit.x);
			if (cy) cy.value = String(hit.y);
			if (cz) cz.value = String(hit.z);
			return true;
		}

		async function autofillFromServerByName(name) {
			const url = `/GalMap/getSystemNames.json.php?q=${encodeURIComponent(name)}&limit=1&exact=1`;
			try {
				const res = await fetch(url, { cache: 'no-store' });
				const j = await res.json();
				const s = (Array.isArray(j?.suggestions) && j.suggestions[0]) || null;
				if (s) return fillCoords({ x: Number(s.x), y: Number(s.y), z: Number(s.z) });
			} catch (e) {
				console.error('exact fetch failed', e);
			}
			return false;
		}

		async function maybeAutofill() {
			if (!csInput) return;
			const key = csInput.value.trim().toLowerCase();

			// 1) Prefer datalist option’s data-* (instant, no network)
			if (dl && dl.options && dl.options.length) {
				const match = Array.from(dl.options).find(o => (o.value || '').toLowerCase() === key);
				if (match) {
					const dx = Number(match.dataset.x), dy = Number(match.dataset.y), dz = Number(match.dataset.z);
					if (isFiniteNum(dx) && isFiniteNum(dy) && isFiniteNum(dz)) {
						if (cx) cx.value = String(dx);
						if (cy) cy.value = String(dy);
						if (cz) cz.value = String(dz);
						return;
					}
				}
			}

			// 2) Cache hit
			const hit = suggestCache.get(key);
			if (fillCoords(hit)) return;

			// 3) Backend exact lookup fallback
			await autofillFromServerByName(csInput.value.trim());
		}
		  
		  

		csInput?.addEventListener('input', debounce(() => {
			if (cx) cx.value = '';
			if (cy) cy.value = '';
			if (cz) cz.value = '';
			updateSuggestions();
		}, 180));
		csInput?.addEventListener('change', () => { void maybeAutofill(); });
		csInput?.addEventListener('blur', () => { void maybeAutofill(); });
		csInput?.addEventListener('keydown', (e) => {
			if (e.key === 'Enter') { e.preventDefault(); void maybeAutofill(); form?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true })); }
		});

		function buildQueryFromForm() {
			const fd = new FormData(form || undefined);
			if (!fd.get('center_system') && csInput?.value) fd.set('center_system', csInput.value);
			if (!fd.get('centerX') && cx?.value) fd.set('centerX', cx.value);
			if (!fd.get('centerY') && cy?.value) fd.set('centerY', cy.value);
			if (!fd.get('centerZ') && cz?.value) fd.set('centerZ', cz.value);
			if (!fd.get('limit')) fd.set('limit', '15000');
			if (!fd.get('maxdistance')) fd.set('maxdistance', '50');
			return new URLSearchParams(fd);
		}

		form?.addEventListener('submit', (e) => {
			e.preventDefault();
			const qs = buildQueryFromForm();
			if (!window.Ed3d) { console.error('Ed3d not loaded'); return; }

			if (mapEl) mapEl.innerHTML = ''; // clear any old overlays/hud so z-index remains sane

			Ed3d.init({
				container: 'ed3dmap',
				basePath: '/GalMap/Vendor/ED3D-Galaxy-Map/',
				jsonPath: `/GalMap/getMapPoints.json.php?${qs.toString()}`,
				withHudPanel: true,
				startAnim: true
			});

			// After init, keep HUD below the form
			setTimeout(() => {
				const hud = $('#ed3dmap .hudPanel');
				if (hud) hud.style.zIndex = '2';
			}, 100);
		});

		// Auto-run once on load if form has defaults
		if (form) setTimeout(() => form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true })), 0);
	});
})();
  