document.addEventListener('DOMContentLoaded', () => {
	const form = document.getElementById('galmap-form');
	const outCount = document.getElementById('results-count');
	const outList = document.getElementById('results-list');

	const get = id => document.getElementById(id);
	const val = id => (get(id)?.value ?? '').trim();

	// --- Type-ahead state ---
	let dl = document.getElementById('system-suggest');
	if (!dl) {
		dl = document.createElement('datalist');
		dl.id = 'system-suggest';
		const csInput = document.getElementById('center_system');
		if (csInput && csInput.parentNode) {
			csInput.parentNode.insertAdjacentElement('afterend', dl);
		} else {
			document.body.appendChild(dl);
		}
	}

	const csInput = get('center_system');
	const suggestCache = new Map(); // name (lower) -> {name,x,y,z}

	// Simple debounce
	let t;
	const debounce = (fn, ms = 200) => (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };

	// Fetch suggestions and populate <datalist>
	const updateSuggestions = async () => {
		const q = csInput.value.trim();
		if (q.length < 2) { dl.innerHTML = ''; return; }

		try {
			const url = `/GalMap/getSystemNames.json.php?q=${encodeURIComponent(q)}&limit=15`;
			const res = await fetch(url, { cache: 'no-store' });
			const data = await res.json();

			dl.innerHTML = '';
			suggestCache.clear();

			const arr = Array.isArray(data?.suggestions) ? data.suggestions : [];
			for (const s of arr) {
				const opt = document.createElement('option');
				opt.value = s.name;
				dl.appendChild(opt);
				if (s?.name) suggestCache.set(String(s.name).toLowerCase(), s);
			}
		} catch {
			// ignore
		}
	};

	// When user types, refresh suggestions; also clear coords (they may be stale)
	csInput?.addEventListener('input', debounce(() => {
		get('centerX').value = '';
		get('centerY').value = '';
		get('centerZ').value = '';
		updateSuggestions();
	}, 200));

	// When user picks a suggestion, auto-fill coords if we have them
	const maybeAutofillFromCache = () => {
		const k = csInput.value.trim().toLowerCase();
		const hit = suggestCache.get(k);
		if (hit && typeof hit.x === 'number') {
			get('centerX').value = String(hit.x);
			get('centerY').value = String(hit.y);
			get('centerZ').value = String(hit.z);
		}
	};
	csInput?.addEventListener('change', maybeAutofillFromCache);
	csInput?.addEventListener('blur', maybeAutofillFromCache);

	// --- Existing Apply handler ---
	form?.addEventListener('submit', async (e) => {
		e.preventDefault();

		const qs = new URLSearchParams();
		const limit = val('limit') || '15000';
		const maxd = val('maxdistance') || '50';

		const cx = val('centerX');
		const cy = val('centerY');
		const cz = val('centerZ');
		const cs = val('center_system');

		qs.set('limit', limit);
		if (maxd) qs.set('maxdistance', maxd);

		if (cx && cy && cz) {
			qs.set('centerX', cx);
			qs.set('centerY', cy);
			qs.set('centerZ', cz);
		} else if (cs) {
			qs.set('center_system', cs);
		}

		if (get('visited_only')?.checked) qs.set('visited_only', '1');
		if (get('bookmarked_only')?.checked) qs.set('bookmarked_only', '1');

		outCount.textContent = '…';
		outList.textContent = 'Loading…';

		try {
			const res = await fetch(`/GalMap/getMapPoints.json.php?${qs.toString()}`, { cache: 'no-store' });
			const data = await res.json();

			const systems = Array.isArray(data?.systems) ? data.systems : [];
			outCount.textContent = String(systems.length);

			// If backend resolved center, back-fill coords (for name-only submits)
			if (data?.resolved_center && (!cx || !cy || !cz)) {
				get('centerX').value = data.resolved_center.x ?? '';
				get('centerY').value = data.resolved_center.y ?? '';
				get('centerZ').value = data.resolved_center.z ?? '';
			}

			outList.textContent = systems.slice(0, 50).map(s => s.name).join(', ') || '(no systems matched)';
		} catch (err) {
			outCount.textContent = '0';
			outList.textContent = `Error: ${err}`;
		}
	});
});
  