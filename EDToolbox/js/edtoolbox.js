/* EDToolbox/js/edtoolbox.js - 2025 UI wiring for ED ToolBox (≤300 LOC) */
(() => {
	"use strict";

	const $ = (sel, root = document) => root.querySelector(sel);
	const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

	const state = {
		category: "general",
		range: "",
		from: "",
		to: "",
		limit: 10,
		system_name: "",
	};


	const els = {};

	function init() {
		els.root = $("#edtbx-root");
		if (!els.root) return;

		els.list = $("#edtbx-list");
		els.cat = $("#edtbx-cat");
		els.limit = $("#edtbx-limit");
		els.refresh = $("#edtbx-refresh");
		els.from = $("#edtbx-from");
		els.to = $("#edtbx-to");
		els.chips = $$(".chip");
		els.sysfilter = $("#edtbx-sysfilter");
		els.sysgo = $("#edtbx-sysgo");


		hydrateFromURL();
		bindEvents();
		applyUIFromState();
		fetchAndRender();
	}

	function hydrateFromURL() {
		const u = new URL(window.location.href);
		const qp = u.searchParams;

		const cat = qp.get("category") || qp.get("type");
		const range = qp.get("range");
		const from = qp.get("from") || qp.get("date_from") || qp.get("start");
		const to = qp.get("to") || qp.get("date_to") || qp.get("end");
		const limit = qp.get("limit");
		const sys = qp.get("system_name") || qp.get("system") || qp.get("sys");

		if (cat && (cat === "general" || cat === "personal")) state.category = cat;
		if (range !== null) state.range = range;
		if (from !== null) state.from = from;
		if (to !== null) state.to = to;
		if (limit && /^\d+$/.test(limit)) state.limit = clamp(parseInt(limit, 10), 1, 50);
		if (sys !== null) state.system_name = sys;

	}

	function bindEvents() {
		if (els.cat) els.cat.addEventListener("change", () => {
			state.category = els.cat.value;
			fetchAndRender();
		});

		if (els.limit) els.limit.addEventListener("input", () => {
			state.limit = clamp(parseInt(els.limit.value || "10", 10), 1, 50);
		});

		if (els.refresh) els.refresh.addEventListener("click", () => fetchAndRender());

		// System filter: button + Enter key
		if (els.sysgo) els.sysgo.addEventListener("click", () => {
			state.system_name = (els.sysfilter && els.sysfilter.value || "").trim();
			fetchAndRender();
		});
		if (els.sysfilter) els.sysfilter.addEventListener("keydown", (e) => {
			if (e.key === "Enter") {
				e.preventDefault();
				state.system_name = (els.sysfilter.value || "").trim();
				fetchAndRender();
			}
		});

		if (els.from) els.from.addEventListener("change", () => {
			state.from = els.from.value;
			if (state.from || state.to) {
				state.range = ""; // explicit dates override quick range
				updateChipSelection("");
			}
		});
		if (els.to) els.to.addEventListener("change", () => {
			state.to = els.to.value;
			if (state.from || state.to) {
				state.range = "";
				updateChipSelection("");
			}
		});

		if (els.chips && els.chips.length) {
			els.chips.forEach(chip => {
				chip.addEventListener("click", () => {
					const r = chip.getAttribute("data-range") || "";
					state.range = r;
					// clear explicit dates if a quick range is chosen
					if (r !== "") {
						state.from = "";
						state.to = "";
						if (els.from) els.from.value = "";
						if (els.to) els.to.value = "";
					}
					updateChipSelection(r);
					fetchAndRender();
				});
			});
		}
	}

	function updateChipSelection(activeRange) {
		els.chips.forEach(c => {
			const r = c.getAttribute("data-range") || "";
			const isActive = r === activeRange;
			c.setAttribute("aria-selected", isActive ? "true" : "false");
			c.classList.toggle("chip--active", isActive);
		});
	}

	function applyUIFromState() {
		if (els.cat) els.cat.value = state.category;
		if (els.limit) els.limit.value = String(state.limit);
		if (els.from) els.from.value = state.from;
		if (els.to) els.to.value = state.to;
		if (els.sysfilter) els.sysfilter.value = state.system_name || "";
		updateChipSelection(state.range);
	}


	function buildParams() {
		const qp = new URLSearchParams();
		qp.set("limit", String(state.limit));

		// Per-system view
		if (state.system_name) {
			qp.set("system_name", state.system_name);
		}

		// Category only applies when NOT viewing a specific system
		// (backend hides general/personal when system_name is present anyway)
		if (!state.system_name && state.category && (state.category === "general" || state.category === "personal")) {
			qp.set("category", state.category);
		}

		// Dates: if explicit from/to present => ignore range
		if (state.from) qp.set("from", state.from);
		if (state.to) qp.set("to", state.to);
		if (!state.from && !state.to && state.range) qp.set("range", state.range);

		return qp;
	}


	async function fetchAndRender() {
		const params = buildParams();
		const urls = [
			"/EDToolbox/getData_logs.php", // prefer local scope
			"/Log/getData_logs.php"        // legacy fallback
		];

		setLoading(true);

		try {
			const html = await tryFetch(urls, params);
			render(html);
		} catch (err) {
			renderError(err instanceof Error ? err.message : String(err));
		} finally {
			setLoading(false);
		}
	}

	async function tryFetch(urls, params) {
		const attempts = [];
		for (const base of urls) {
			attempts.push(`${base}?${params.toString()}`);
		}

		let lastErr = null;
		for (const u of attempts) {
			try {
				const r = await fetch(u, { credentials: "same-origin" });
				if (!r.ok) { lastErr = new Error(`HTTP ${r.status}`); continue; }
				const text = await r.text();
				if (!text) { lastErr = new Error("Empty response"); continue; }
				return text;
			} catch (e) {
				lastErr = e;
			}
		}
		throw lastErr || new Error("All attempts failed");
	}

	function render(html) {
		if (!els.list) return;
		els.list.innerHTML = sanitizeBasic(html);
	}

	function renderError(msg) {
		if (!els.list) return;
		els.list.innerHTML = `<div class="edtbx-error">Failed to load logs: ${escapeHtml(msg)}</div>`;
	}

	function setLoading(loading) {
		if (!els.list) return;
		els.list.classList.toggle("is-loading", !!loading);
		if (loading) {
			els.list.innerHTML = `<div class="edtbx-skel">
		  <div class="skel-line"></div><div class="skel-line"></div><div class="skel-line short"></div>
		</div>`;
		}
	}

	// --- tiny utils ---
	function clamp(n, a, b) { return Math.max(a, Math.min(b, isFinite(n) ? n : a)); }
	function escapeHtml(s) {
		return String(s).replace(/[&<>"']/g, m => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[m]));
	}
	function sanitizeBasic(html) {
		// Very light sanitization: strip <script> tags; trust server to output safe markup.
		return String(html).replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, "");
	}

	// --- Add/Edit/Delete modal wiring (no audio) ---
	document.addEventListener("DOMContentLoaded", () => {
		const list = document.getElementById("edtbx-list");
		const modal = document.getElementById("edtbx-modal");
		const form = document.getElementById("edtbx-form");
		const btnNew = document.getElementById("edtbx-newlog");
		const btnDel = document.getElementById("edtbx-delete");
		const btnCancel = document.getElementById("edtbx-cancel");
		const titleEl = document.getElementById("edtbx-modal-title");
		const editIdEl = document.getElementById("edtbx-edit-id");

		if (!list || !modal || !form) return;

		function showModal(mode, id) {
			modal.classList.add("show");
			if (mode === "edit") {
				titleEl.textContent = "Edit log";
				btnDel.style.display = "";
				editIdEl.value = id || "";
			} else {
				titleEl.textContent = "New log";
				btnDel.style.display = "none";
				editIdEl.value = "";
				form.reset();
			}
		}

		function hideModal() { modal.classList.remove("show"); }

		function getIdFromNode(node) {
			let id = node.getAttribute("data-log-id") || node.dataset?.logId;
			if (id) return id;
			const href = node.getAttribute && node.getAttribute("href");
			if (href) {
				const m = href.match(/[?&](?:logid|id)=(\d+)/);
				if (m) return m[1];
			}
			return null;
		}

		async function prefillEdit(id) {
			const url = `/Log/getLogEditData.php?logid=${encodeURIComponent(id)}`;
			const res = await fetch(url, { headers: { "Accept": "application/json" } });
			if (!res.ok) throw new Error(`Prefill failed: ${res.status}`);
			const data = await res.json();
			form.log_type.value = data.log_type || "general";
			form.title.value = data.title || "";
			form.system_1.value = data.system_1 || "";
			form.statname.value = data.statname || "";
			form.pinned.value = String(data.pinned ? 1 : 0);
			form.weight.value = String(data.weight ?? 0);
			form.html.value = data.html || "";
		}

		function toQS(formEl) {
			const fd = new FormData(formEl);
			const usp = new URLSearchParams();
			for (const [k, v] of fd.entries()) usp.append(k, v);
			if (!usp.has("pinned")) usp.set("pinned", "0");
			return usp;
		}

		async function submitForm(evt) {
			evt.preventDefault();
			const isEdit = !!editIdEl.value;
			const usp = toQS(form);
			const doMode = isEdit ? "edit" : "add";
			if (isEdit) usp.set("edit_id", editIdEl.value);
			const url = `/Log/add_log.php?do=${doMode}`;
			const res = await fetch(url, { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: usp.toString() });
			if (!res.ok) throw new Error(`Save failed: ${res.status}`);
			hideModal();
			if (typeof fetchAndRender === "function") fetchAndRender();
		}

		async function doDelete() {
			const id = editIdEl.value;
			if (!id) return hideModal();
			if (!confirm("Delete this log entry?")) return;
			const url = `/Log/add_log.php?do=delete&id=${encodeURIComponent(id)}`;
			const res = await fetch(url, { method: "POST" });
			if (!res.ok) throw new Error(`Delete failed: ${res.status}`);
			hideModal();
			if (typeof fetchAndRender === "function") fetchAndRender();
		}

		// New log
		if (btnNew) btnNew.addEventListener("click", () => showModal("add"));

		// Delegated list actions
		list.addEventListener("click", async (e) => {
			const a = e.target.closest("a,button");
			if (!a) return;
			const action = a.getAttribute("data-action") || "";
			if (action === "edit" || /do=edit/.test(a.getAttribute("href") || "")) {
				e.preventDefault();
				const id = getIdFromNode(a);
				if (!id) return;
				showModal("edit", id);
				try { await prefillEdit(id); } catch (err) { console.error(err); }
			} else if (action === "delete" || /do=delete/.test(a.getAttribute("href") || "")) {
				e.preventDefault();
				const id = getIdFromNode(a);
				if (!id) return;
				showModal("edit", id);
				try { await prefillEdit(id); } catch { }
				try { await doDelete(); } catch (err) { console.error(err); }
			}
		});

		form.addEventListener("submit", submitForm);
		btnCancel && btnCancel.addEventListener("click", () => hideModal());
		btnDel && btnDel.addEventListener("click", () => { doDelete().catch(console.error); });
	}, { once: true });

	// keep original init
	document.addEventListener("DOMContentLoaded", init, { capture: true, once: true });
})();

  