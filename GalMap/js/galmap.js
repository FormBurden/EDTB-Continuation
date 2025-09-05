document.addEventListener('DOMContentLoaded', () => {
	const form = document.getElementById('galmap-form');
	const outCount = document.getElementById('results-count');
	const outList = document.getElementById('results-list');

	const get = id => document.getElementById(id);
	const val = id => (get(id)?.value ?? '').trim();

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

			// If we got resolved_center and the user left coords blank, fill them for convenience
			if (data?.resolved_center && (!cx || !cy || !cz)) {
				get('centerX').value = data.resolved_center.x ?? '';
				get('centerY').value = data.resolved_center.y ?? '';
				get('centerZ').value = data.resolved_center.z ?? '';
			}

			// very simple render
			outList.textContent = systems.slice(0, 50).map(s => s.name).join(', ') || '(no systems matched)';
		} catch (err) {
			outCount.textContent = '0';
			outList.textContent = `Error: ${err}`;
		}
	});
});
  