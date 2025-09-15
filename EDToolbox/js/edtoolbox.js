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

		if (cat && (cat === "general" || cat === "personal")) state.category = cat;
		if (range !== null) state.range = range;
		if (from !== null) state.from = from;
		if (to !== null) state.to = to;
		if (limit && /^\d+$/.test(limit)) state.limit = clamp(parseInt(limit, 10), 1, 50);
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
		updateChipSelection(state.range);
	}

	function buildParams() {
		const qp = new URLSearchParams();
		qp.set("limit", String(state.limit));

		// Category only applies when NOT viewing a specific system
		// (the backend hides general/personal when system_name is present anyway)
		if (state.category && (state.category === "general" || state.category === "personal")) {
			qp.set("category", state.category);
		}

		// Dates: if explicit from/to present => ignore range
		if (state.from) qp.set("from", state.from);
		if (state.to) qp.set("to", state.to);
		if (!state.from && !state.to && state.range) qp.set("range", state.range);

		// If you later wire a system-level view, add ?system_name=... to qp here.
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

	document.addEventListener("DOMContentLoaded", init, { capture: true, once: true });
})();
  