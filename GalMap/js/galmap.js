(() => {
	'use strict';

	// ---------------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------------
	const $ = (sel, root = document) => root.querySelector(sel);
	const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
	const $id = (id) => document.getElementById(id);
	const isFiniteNum = (v) => typeof v === 'number' && Number.isFinite(v);
	const toNum = (v) => (v === '' || v === null || v === undefined) ? '' : Number(v);

	// A tiny memo for exact matches (system -> coords)
	window.__galmapSuggestCache ||= new Map();

	// ---------------------------------------------------------------------------
	// Suggest-as-you-type (datalist)
	// ---------------------------------------------------------------------------
	let suggestCtl = null;   // AbortController
	let suggestTick = 0;

	function fillDatalist(items) {
		const dl = $id('system_names');
		if (!dl) return;
		dl.innerHTML = items.map(it => {
			const x = (it && isFiniteNum(it.x)) ? it.x : '';
			const y = (it && isFiniteNum(it.y)) ? it.y : '';
			const z = (it && isFiniteNum(it.z)) ? it.z : '';
			const name = String(it?.name ?? '');
			if (name) window.__galmapSuggestCache.set(name.toLowerCase(), { name, x, y, z });
			return `<option value="${name}" data-x="${x}" data-y="${y}" data-z="${z}"></option>`;
		}).join('');
	}

	let suggestTimer = null;
	function scheduleSuggestFetch(q) {
		if (suggestTimer) clearTimeout(suggestTimer);
		suggestTimer = setTimeout(() => doSuggestFetch(q), 120);
	}

	async function doSuggestFetch(q) {
		if (!q || q.length < 2) { fillDatalist([]); return; }

		if (suggestCtl) suggestCtl.abort();
		suggestCtl = new AbortController();
		const myTick = ++suggestTick;

		try {
			const url = `/GalMap/getSystemNames.json.php?q=${encodeURIComponent(q)}&limit=25`;
			const res = await fetch(url, { cache: 'no-store', signal: suggestCtl.signal });
			if (!res.ok) return;
			const data = await res.json();
			if (myTick !== suggestTick) return; // stale
			const items = Array.isArray(data?.suggestions) ? data.suggestions : [];
			fillDatalist(items);
		} catch (_) {
			/* ignore */
		}
	}

	// ---------------------------------------------------------------------------
	// Results panel updater — reads the same JSON as ED3D
	// ---------------------------------------------------------------------------
	async function updateResults(qs) {
		const url = `/GalMap/getMapPoints.json.php?${qs.toString()}`;
		const res = await fetch(url, { cache: 'no-store' });
		const data = await res.json();

		const sys = Array.isArray(data?.systems) ? data.systems : [];
		const countEl = $id('results-count');
		const listEl = $id('results-list');
		const legendEl = $id('legend');

		if (countEl) countEl.textContent = String(sys.length);

		// Legend (from categories/colors)
		if (legendEl && data?.categories && typeof data.categories === 'object') {
			const entries = Object.entries(data.categories);
			legendEl.innerHTML = entries.map(([key, cat]) => {
				const color = cat?.color || '#7f8c8d';
				const name = cat?.name ?? key;
				return `<span class="legend-item"><span class="legend-swatch" style="background:${color}"></span>${name}</span>`;
			}).join('');
		}

		if (!listEl) return;

		if (!sys.length) {
			listEl.textContent = '(nothing yet)';
			return;
		}

		// Distance from resolved center (preferred) or current form values
		const rc = data?.resolved_center;
		let cxn = Number($id('centerX')?.value), cyn = Number($id('centerY')?.value), czn = Number($id('centerZ')?.value);
		if (rc && isFinite(rc.x) && isFinite(rc.y) && isFinite(rc.z)) {
			cxn = Number(rc.x); cyn = Number(rc.y); czn = Number(rc.z);
		}
		const haveCenter = isFiniteNum(cxn) && isFiniteNum(cyn) && isFiniteNum(czn);

		const html = sys.slice(0, 50).map((s) => {
			const c = Array.isArray(s.coords) ? s.coords : [];
			const bm = s.bookmarked ? ' ★' : '';
			const vi = s.visited ? ' ✓' : '';
			const cat = Array.isArray(s.cat) && s.cat.length ? ` <span class="result-cat">(${s.cat[0]})</span>` : '';
			let distTxt = '';
			if (haveCenter && c.length === 3 && c.every(isFiniteNum)) {
				const dx = c[0] - cxn, dy = c[1] - cyn, dz = c[2] - czn;
				const d = Math.sqrt(dx * dx + dy * dy + dz * dz);
				if (isFiniteNum(d)) distTxt = ` — ${d.toFixed(1)} ly`;
			} else if (isFiniteNum(s.dist)) {
				distTxt = ` — ${Number(s.dist).toFixed(1)} ly`;
			}
			const dxAttr = c.length === 3 ? ` data-x="${c[0]}" data-y="${c[1]}" data-z="${c[2]}"` : '';
			return `<div class="result-item" data-name="${s.name}"${dxAttr}>${s.name}${bm}${vi}${cat}${distTxt}</div>`;
		}).join('');

		listEl.innerHTML = html;
	}

	// ---------------------------------------------------------------------------
	// Autofill center X/Y/Z from exact match or cache
	// ---------------------------------------------------------------------------
	async function maybeAutofill() {
		const csInput = $id('center_system');
		const cx = $id('centerX'), cy = $id('centerY'), cz = $id('centerZ');
		if (!csInput || !cx || !cy || !cz) return;

		const name = (csInput.value || '').trim();
		if (!name) return;

		// If already filled with numbers, skip
		const have = [cx.value, cy.value, cz.value].every(v => v !== '' && !Number.isNaN(Number(v)));
		if (have) return;

		// Try cache first
		const cached = window.__galmapSuggestCache.get(name.toLowerCase());
		if (cached && isFiniteNum(cached.x) && isFiniteNum(cached.y) && isFiniteNum(cached.z)) {
			cx.value = cached.x; cy.value = cached.y; cz.value = cached.z;
			return;
		}

		// Fetch exact=1
		try {
			const url = `/GalMap/getSystemNames.json.php?q=${encodeURIComponent(name)}&exact=1&limit=1`;
			const res = await fetch(url, { cache: 'no-store' });
			const data = await res.json();
			const item = Array.isArray(data?.suggestions) ? data.suggestions[0] : null;
			if (item && isFiniteNum(item.x) && isFiniteNum(item.y) && isFiniteNum(item.z)) {
				cx.value = item.x; cy.value = item.y; cz.value = item.z;
				window.__galmapSuggestCache.set(String(item.name).toLowerCase(), { name: item.name, x: item.x, y: item.y, z: item.z });
			}
		} catch (e) {
			console.error('maybeAutofill failed', e);
		}
	}

	// ---------------------------------------------------------------------------
	// Build URLSearchParams from form
	// ---------------------------------------------------------------------------
	function buildQueryFromForm() {
		const params = new URLSearchParams();

		const cs = $id('center_system')?.value ?? '';
		const cx = $id('centerX')?.value ?? '';
		const cy = $id('centerY')?.value ?? '';
		const cz = $id('centerZ')?.value ?? '';
		const r = $id('maxdistance')?.value ?? '50';
		const lim = $id('limit')?.value ?? '15000';
		const visited = $id('visited_only')?.checked ? '1' : '';
		const bookmarked = $id('bookmarked_only')?.checked ? '1' : '';
		const colorBy = $id('color_by')?.value ?? 'none';

		if (cs) params.set('center_system', cs);
		if (cx !== '') params.set('centerX', cx);
		if (cy !== '') params.set('centerY', cy);
		if (cz !== '') params.set('centerZ', cz);
		params.set('maxdistance', r);
		params.set('limit', lim);
		if (visited) params.set('visited_only', '1');
		if (bookmarked) params.set('bookmarked_only', '1');
		if (colorBy && colorBy !== 'none') params.set('color_by', colorBy);

		return params;
	}

	// ---------------------------------------------------------------------------
	// Main wiring
	// ---------------------------------------------------------------------------
	const form = $id('galmap-form');
	const csInput = $id('center_system');
	const mapEl = $id('ed3dmap');
	const cx = $id('centerX'), cy = $id('centerY'), cz = $id('centerZ');
	const view2d = $id('view2d');

	// Suggest while typing
	if (csInput) {
		csInput.addEventListener('input', (e) => {
			const v = (e.target.value || '').trim();
			if (v.length >= 2) scheduleSuggestFetch(v);
			else fillDatalist([]);
		});
		csInput.addEventListener('change', async () => {
			await maybeAutofill();
		});
		csInput.addEventListener('focus', () => {
			const v = (csInput.value || '').trim();
			if (v.length >= 2) scheduleSuggestFetch(v);
		});
	}

	// Click a result to re-center and resubmit
	const resultsBox = $id('results-list');
	if (resultsBox) {
		resultsBox.addEventListener('click', (e) => {
			const el = e.target.closest('.result-item');
			if (!el) return;
			const n = el.getAttribute('data-name') || '';
			const xn = toNum(el.getAttribute('data-x'));
			const yn = toNum(el.getAttribute('data-y'));
			const zn = toNum(el.getAttribute('data-z'));

			if (csInput) csInput.value = n;
			if (cx) cx.value = Number.isFinite(xn) ? xn : '';
			if (cy) cy.value = Number.isFinite(yn) ? yn : '';
			if (cz) cz.value = Number.isFinite(zn) ? zn : '';

			(window.__galmapSuggestCache ||= new Map()).set(n.toLowerCase(), { name: n, x: xn, y: yn, z: zn });
			form?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
		});
	}

	// Auto-submit on filter changes
	$id('visited_only')?.addEventListener('change', () => {
		form?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
	});
	$id('bookmarked_only')?.addEventListener('change', () => {
		form?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
	});
	$id('color_by')?.addEventListener('change', () => {
		form?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
	});

	// Center on current system
	const btnCur = $id('center_current');
	if (btnCur) {
		btnCur.addEventListener('click', async () => {
			try {
				const res = await fetch('/GalMap/getCurrentSystem.json.php', { cache: 'no-store' });
				const d = await res.json();
				if (!d || !d.name || !isFiniteNum(d.x) || !isFiniteNum(d.y) || !isFiniteNum(d.z)) return;
				if (csInput) csInput.value = d.name;
				if (cx) cx.value = d.x;
				if (cy) cy.value = d.y;
				if (cz) cz.value = d.z;
				(window.__galmapSuggestCache ||= new Map()).set(d.name.toLowerCase(), { name: d.name, x: d.x, y: d.y, z: d.z });
				form?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
			} catch (e) {
				console.error('center_current failed', e);
			}
		});
	}

	// Enter submits (await autofill)
	csInput?.addEventListener('keydown', async (e) => {
		if (e.key === 'Enter') {
			e.preventDefault();
			await maybeAutofill();
			form?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
		}
	});

	// Submit => update Results immediately, then init ED3D
	form?.addEventListener('submit', async (e) => {
		e.preventDefault();
		await maybeAutofill();

		const qs = buildQueryFromForm();

		// Update Results panel & legend first (fast)
		void updateResults(qs);

		// Init ED3D map
		if (!window.Ed3d) {
			console.error('Ed3d not loaded');
			return;
		}
		if (mapEl) mapEl.innerHTML = '';

		Ed3d.init({
			container: 'ed3dmap',
			basePath: '/GalMap/Vendor/ED3D-Galaxy-Map/',
			jsonPath: `/GalMap/getMapPoints.json.php?${qs.toString()}`,
			withHudPanel: true,
			startAnim: true
		});
		setTimeout(() => window.dispatchEvent(new Event('resize')), 0);
	});
	// After ED3D init, push the resolved center into controls so HUD/grid match
	(function applyResolvedCenter() {
		const rc = window.__galmapResolvedCenter;
		if (!rc || !window.controls) return;
		const x = Number(rc.x), y = Number(rc.y), z = Number(rc.z);
		if (Number.isFinite(x) && Number.isFinite(y) && Number.isFinite(z)) {
			try {
				// OrbitControls standard uses `target`; ED3D’s HUD sometimes reads `center`
				controls.target.set(x, y, z);
				if (controls.center && typeof controls.center.set === 'function') {
					controls.center.set(x, y, z);
				}
			} catch (_) {/* ignore */ }
		}
	})();
  
	// Kick once on load if center is present
	document.addEventListener('DOMContentLoaded', async () => {
		const hasCenter = ($id('center_system')?.value || '').trim().length > 0 ||
			($id('centerX')?.value ?? '') !== '';
		if (hasCenter) {
			await maybeAutofill();
			form?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
		}
	});
})();
  