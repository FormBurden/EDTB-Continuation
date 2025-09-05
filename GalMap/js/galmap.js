// GalMap type-ahead + Apply wiring (self-contained)
// - Creates <datalist id="system-suggest"> if missing
// - Binds to #center_system (or name="center_system"), sets list="system-suggest"
// - Fetches suggestions and autofills X/Y/Z on selection
// - Submits to getMapPoints.json.php on form submit

document.addEventListener('DOMContentLoaded', () => {
	// --- locate inputs ---
	const csInput = document.getElementById('center_system') || document.querySelector('input[name="center_system"]');
	const form = document.getElementById('galmap-form') || csInput?.closest('form') || document.forms[0];

	const cx = document.getElementById('centerX') || document.querySelector('input[name="centerX"]');
	const cy = document.getElementById('centerY') || document.querySelector('input[name="centerY"]');
	const cz = document.getElementById('centerZ') || document.querySelector('input[name="centerZ"]');

	const outCount = document.getElementById('results-count');
	const outList = document.getElementById('results-list');

	// --- ensure datalist exists and attach to input ---
	let dl = document.getElementById('system-suggest');
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
		if (q.length < 2) { dl.innerHTML = ''; return; }

		const url = `/GalMap/getSystemNames.json.php?q=${encodeURIComponent(q)}&limit=15`;
		const res = await fetch(url, { cache: 'no-store' });
		const data = await res.json();

		const arr = Array.isArray(data?.suggestions) ? data.suggestions : [];
		dl.innerHTML = '';
		suggestCache.clear();

		for (const s of arr) {
			if (!s?.name) continue;
			const opt = document.createElement('option');
			opt.value = s.name;
			dl.appendChild(opt);
			suggestCache.set(String(s.name).toLowerCase(), s);
		}
	};

	// --- autofill coords when a suggestion is chosen ---
	const maybeAutofill = () => {
		const k = (csInput?.value || '').trim().toLowerCase();
		const hit = suggestCache.get(k);
		if (hit && typeof hit.x === 'number') {
			if (cx) cx.value = String(hit.x);
			if (cy) cy.value = String(hit.y);
			if (cz) cz.value = String(hit.z);
		}
	};

	// --- events for typing + choosing ---
	csInput?.addEventListener('input', debounce(() => {
		if (cx) cx.value = '';
		if (cy) cy.value = '';
		if (cz) cz.value = '';
		updateSuggestions();
	}, 200));
	csInput?.addEventListener('change', maybeAutofill);
	csInput?.addEventListener('blur', maybeAutofill);

	// --- Apply (keep it simple; only runs if a form exists) ---
	form?.addEventListener('submit', async (e) => {
		e.preventDefault();

		const getVal = (id, name) => (document.getElementById(id)?.value ?? document.querySelector(`input[name="${name}"]`)?.value ?? '').trim();

		const limit = getVal('limit', 'limit') || '15000';
		const maxd = getVal('maxdistance', 'maxdistance') || '50';
		const vX = getVal('centerX', 'centerX');
		const vY = getVal('centerY', 'centerY');
		const vZ = getVal('centerZ', 'centerZ');
		const vS = getVal('center_system', 'center_system');

		const qs = new URLSearchParams();
		qs.set('limit', limit);
		if (maxd) qs.set('maxdistance', maxd);

		if (vX && vY && vZ) {
			qs.set('centerX', vX);
			qs.set('centerY', vY);
			qs.set('centerZ', vZ);
		} else if (vS) {
			qs.set('center_system', vS);
		}

		// optional checkboxes if present
		if (document.getElementById('visited_only')?.checked) qs.set('visited_only', '1');
		if (document.getElementById('bookmarked_only')?.checked) qs.set('bookmarked_only', '1');

		if (outCount) outCount.textContent = '…';
		if (outList) outList.textContent = 'Loading…';

		const res = await fetch(`/GalMap/getMapPoints.json.php?${qs.toString()}`, { cache: 'no-store' });
		const data = await res.json();

		const systems = Array.isArray(data?.systems) ? data.systems : [];
		if (outCount) outCount.textContent = String(systems.length);

		// back-fill coords from backend resolve (for name-only submits)
		if (data?.resolved_center && (!vX || !vY || !vZ)) {
			if (cx) cx.value = data.resolved_center.x ?? '';
			if (cy) cy.value = data.resolved_center.y ?? '';
			if (cz) cz.value = data.resolved_center.z ?? '';
		}

		if (outList) outList.textContent = systems.slice(0, 50).map(s => s.name).join(', ') || '(no systems matched)';
	});
});
  