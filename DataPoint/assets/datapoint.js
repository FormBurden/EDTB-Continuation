// Utilities
function dp_applyBooleanBadges(listTable, state) {
	var fmt = state.format || {};
	var boolCols = [];
	for (var key in fmt) {
		if (fmt[key] === 'bool' && state.colIndex.hasOwnProperty(key)) {
			boolCols.push(state.colIndex[key]);
		}
	}
	if (!boolCols.length) return;
	var rows = listTable.querySelectorAll('tbody tr');
	rows.forEach(function (r) {
		var cells = r.children;
		boolCols.forEach(function (idx) {
			var c = cells[idx];
			if (!c) return;
			var raw = (c.textContent || '').trim();
			if (raw === '') return;
			var yes = /^(1|true|yes)$/i.test(raw);
			var no = /^(0|false|no)$/i.test(raw);
			if (yes || no) {
				c.innerHTML = '<span class="dp-bool ' + (yes ? 'yes' : 'no') + '">' + (yes ? 'Yes' : 'No') + '</span>';
			}
		});
	});
}

function dp_findListTable(state) {
	var tables = document.querySelectorAll('.dp-mte table');
	var listTable = null;
	tables.forEach(function (t) {
		var ths = t.querySelectorAll('thead th');
		if (!listTable && ths && ths.length === state.fields.length) { listTable = t; }
	});
	return listTable;
}

function dp_applyPreset(state, presetName) {
	var listTable = dp_findListTable(state);
	if (!listTable) return;
	var showCols = null;
	if (presetName && state.presets && state.presets[presetName]) {
		showCols = state.presets[presetName];
	}
	// Default: show all fields
	var allow = {};
	if (showCols && showCols.length) {
		showCols.forEach(function (f) { if (state.colIndex.hasOwnProperty(f)) allow[state.colIndex[f]] = true; });
	} else {
		state.fields.forEach(function (f, i) { allow[i] = true; });
	}

	var thead = listTable.querySelector('thead');
	var tbody = listTable.querySelector('tbody');
	if (!thead || !tbody) return;
	var ths = thead.querySelectorAll('th');
	ths.forEach(function (th, i) {
		if (allow[i]) th.classList.remove('dp-hidden'); else th.classList.add('dp-hidden');
	});
	var rows = tbody.querySelectorAll('tr');
	rows.forEach(function (r) {
		var cells = r.children;
		Array.prototype.forEach.call(cells, function (td, i) {
			if (allow[i]) td.classList.remove('dp-hidden'); else td.classList.add('dp-hidden');
		});
	});

	// Persist choice
	try { localStorage.setItem('dp_preset_' + state.table, String(presetName || '')); } catch (e) { }
}

function dp_renderQuickFilters(state) {
	var root = document.getElementById('dp-quickfilters');
	if (!root) return;
	root.innerHTML = '';
	var items = state.quickFilters || [];
	if (!items.length) { root.style.display = 'none'; return; }
	root.style.display = 'flex';

	items.forEach(function (item) {
		var chip = document.createElement('span');
		chip.className = 'dp-chip';
		var label = '';
		if (item.label) { label = String(item.label); }
		else if (item.field && (item.value !== undefined && item.value !== null)) { label = item.field + ': ' + item.value; }
		else { label = 'Filter'; }
		chip.textContent = label;
		chip.addEventListener('click', function () {
			// Find vendor search form (first form within dp-mte)
			var form = document.querySelector('.dp-mte form');
			if (!form) {
				// If vendor hasn't rendered yet, wait for it
				var obs = new MutationObserver(function () {
					var f = document.querySelector('.dp-mte form');
					if (f) { obs.disconnect(); form = f; setTimeout(handler, 0); }
				});
				obs.observe(document.querySelector('.dp-mte') || document.body, { childList: true, subtree: true });
				return;
			}
			handler();

			function handler() {
				// Find a field selector (first <select>) and a text box (first text/search)
				var fieldSel = form.querySelector('select');
				var txt = form.querySelector('input[type="text"], input[type="search"]');

				function setFieldSelect(sel, field) {
					if (!sel) return;
					var want = String(field).toLowerCase();
					var label = (state.labels && state.labels[field]) ? String(state.labels[field]).toLowerCase() : null;
					var matched = false;
					Array.prototype.forEach.call(sel.options, function (opt) {
						var ov = String(opt.value || '').toLowerCase();
						var ot = String(opt.text || '').toLowerCase();
						if (ov === want || ot === want || (label && ot === label)) {
							opt.selected = true; matched = true;
						}
					});
					if (!matched && sel.options.length) { sel.selectedIndex = 0; }
				}

				if (item.field) setFieldSelect(fieldSel, item.field);
				if (txt && (item.value !== undefined && item.value !== null)) {
					txt.value = String(item.value);
				}
				// Operator hook if vendor exposes one (commonly a second select)
				var opSel = form.querySelectorAll('select')[1];
				if (opSel && item.op) {
					Array.prototype.forEach.call(opSel.options, function (opt) {
						var ov = String(opt.value || '').toLowerCase();
						var ot = String(opt.text || '').toLowerCase();
						if (ov === String(item.op).toLowerCase() || ot === String(item.op).toLowerCase()) {
							opt.selected = true;
						}
					});
				}
				try { form.submit(); } catch (e) { }
			}
		});
		root.appendChild(chip);
	});
}

function dp_upgradeBooleanInputs(state) {
	var root = document.querySelector('.dp-mte');
	if (!root) return;
	var observer = new MutationObserver(function (muts) {
		muts.forEach(function (m) {
			Array.prototype.forEach.call(m.addedNodes || [], function (node) {
				if (!(node instanceof HTMLElement)) return;
				// Upgrade form checkboxes where vendor renders "0/1" as text field—if it happens.
				// If vendor already uses checkbox, skip.
				// (No-op unless vendor produces inputs we can intercept reliably.)
			});
		});
	});
	observer.observe(root, { childList: true, subtree: true });
}

document.addEventListener('DOMContentLoaded', function () {
	var st = window.DP_STATE;
	if (!st) return;

	// Apply saved preset (if any) and reflect it in the dropdown
	try {
		var saved = localStorage.getItem('dp_preset_' + st.table);
		if (saved) dp_applyPreset(st, saved);
		var sel = document.getElementById('dp-preset');
		if (sel && saved && sel.querySelector('option[value="' + saved + '"]')) sel.value = saved;
	} catch (e) { }

	// Render quick filters
	dp_renderQuickFilters(st);

	// Boolean badges in cells
	var tbl = dp_findListTable(st);
	if (tbl) dp_applyBooleanBadges(tbl, st);

	// Upgrade form inputs for booleans (noop unless vendor renders text/number for bools)
	dp_upgradeBooleanInputs(st);
});
  