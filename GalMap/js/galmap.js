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

	// Results panel updater — mirrors the map JSON
	async function updateResults(qs) {
		const url = `/GalMap/getMapPoints.json.php?${qs.toString()}`;
		const res = await fetch(url, { cache: 'no-store' });
		const data = await res.json();

		const sys = Array.isArray(data?.systems) ? data.systems : [];
		const countEl = $id('results-count');
		const listEl = $id('results-list');

		if (countEl) countEl.textContent = String(sys.length);
		if (!listEl) return;

		if (!sys.length) {
			listEl.textContent = '(nothing yet)';
			return;
		}

		// Distance from resolved center (preferred) or current form values
		const rc = data?.resolved_center;
		let cx = Number($id('centerX')?.value), cy = Number($id('centerY')?.value), cz = Number($id('centerZ')?.value);
		if (rc && isFinite(rc.x) && isFinite(rc.y) && isFinite(rc.z)) {
			cx = Number(rc.x); cy = Number(rc.y); cz = Number(rc.z);
		}
		const haveCenter = isFiniteNum(cx) && isFiniteNum(cy) && isFiniteNum(cz);

		const html = sys.slice(0, 25).map((s, idx) => {
			const c = Array.isArray(s.coords) ? s.coords : [];
			const bm = s.bookmarked ? ' ★' : '';
			const vi = s.visited ? ' ✓' : '';
			let distTxt = '';
			if (haveCenter && c.length === 3 && c.every(isFiniteNum)) {
				const dx = c[0] - cx, dy = c[1] - cy, dz = c[2] - cz;
				const d = Math.sqrt(dx * dx + dy * dy + dz * dz);
				if (isFiniteNum(d)) distTxt = ` — ${d.toFixed(1)} ly`;
			} else if (isFiniteNum(s.dist)) {
				distTxt = ` — ${Number(s.dist).toFixed(1)} ly`;
			}
			const dxAttr = c.length === 3 ? ` data-x="${c[0]}" data-y="${c[1]}" data-z="${c[2]}"` : '';
			return `<div class="result-item" data-name="${s.name}"${dxAttr}>${s.name}${bm}${vi}${distTxt}</div>`;
		}).join('');
		  

		listEl.innerHTML = html;
	}
  

	document.addEventListener('DOMContentLoaded', () => {
		const csInput = $id('center_system') || $('input[name="center_system"]');
		const form = $id('galmap-form') || (csInput && csInput.closest('form')) || document.forms[0];
		// Auto-submit on filter changes
		const visitedBox = $id('visited_only');
		const bookmarkedBox = $id('bookmarked_only');
		visitedBox?.addEventListener('change', () => {
			form?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
		});
		bookmarkedBox?.addEventListener('change', () => {
			form?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
		});

		// Center on current system (via PHP/curSys.php)
		const btnCur = $id('center_current');
		if (btnCur) {
			btnCur.addEventListener('click', async () => {
				try {
					const res = await fetch('/GalMap/getCurrentSystem.json.php', { cache: 'no-store' });
					const d = await res.json();

					if (!d || !d.name || !isFiniteNum(d.x) || !isFiniteNum(d.y) || !isFiniteNum(d.z)) return;

					// Fill inputs
					const csInput = $id('center_system') || $('input[name="center_system"]');
					if (csInput) csInput.value = d.name;
					if (cx) cx.value = d.x;
					if (cy) cy.value = d.y;
					if (cz) cz.value = d.z;

					// Seed suggest cache for instant X/Y/Z reuse
					if (typeof d.name === 'string') {
						(window.__galmapSuggestCache ||= new Map()).set(d.name.toLowerCase(), { name: d.name, x: d.x, y: d.y, z: d.z });
					}

					// Submit (Results panel updates immediately; map follows)
					form?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
				} catch (e) {
					console.error('center_current failed', e);
				}
			});
		}


		const cx = $id('centerX') || $('input[name="centerX"]');
		const cy = $id('centerY') || $('input[name="centerY"]');
		const cz = $id('centerZ') || $('input[name="centerZ"]');

		// ——— kill any server-rendered duplicate datalists, keep exactly one
		const existing = Array.from(document.querySelectorAll('datalist#system-suggest'));
		for (let i = 1; i < existing.length; i++) existing[i].remove();
		let dl = existing[0];
		if (!dl) {
			// Click a result to re-center and resubmit
			const resultsBox = $id('results-list');
			if (resultsBox) {
				resultsBox.addEventListener('click', (e) => {
					const el = e.target.closest('.result-item');
					if (!el) return;
					const n = el.getAttribute('data-name') || '';
					const x = Number(el.getAttribute('data-x'));
					const y = Number(el.getAttribute('data-y'));
					const z = Number(el.getAttribute('data-z'));

					if (csInput) csInput.value = n;
					if (cx) cx.value = Number.isFinite(x) ? x : '';
					if (cy) cy.value = Number.isFinite(y) ? y : '';
					if (cz) cz.value = Number.isFinite(z) ? z : '';

					(window.__galmapSuggestCache ||= new Map()).set(n.toLowerCase(), { name: n, x, y, z });
					form?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
				});
			}

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
		csInput?.addEventListener('keydown', async (e) => {
			if (e.key === 'Enter') {
				e.preventDefault();
				await maybeAutofill();
				form?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
			}
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

		form?.addEventListener('submit', async (e) => {
			e.preventDefault();
		  
			// Ensure X/Y/Z are filled before building the query
			await maybeAutofill();
		  
			const qs = buildQueryFromForm();
			void updateResults(qs);
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
})();
  