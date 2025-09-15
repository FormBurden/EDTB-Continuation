<?php
declare(strict_types=1);
?>
<div id="edtbx-root" class="edtbx">
  <header class="edtbx-hd">
    <h1 class="edtbx-title">ED ToolBox</h1>

    <div class="edtbx-controls">
      <div class="edtbx-row">
        <label for="edtbx-cat" class="edtbx-label">Category</label>
        <select id="edtbx-cat" class="edtbx-select" aria-label="Log category">
          <option value="general">General</option>
          <option value="personal">Personal</option>
        </select>
      </div>

      <div class="edtbx-row">
        <span class="edtbx-label">Date</span>
        <div class="edtbx-chips" role="listbox" aria-label="Quick ranges">
          <button type="button" class="chip" data-range="1d" aria-selected="false">24h</button>
          <button type="button" class="chip" data-range="7d" aria-selected="false">7d</button>
          <button type="button" class="chip" data-range="1m" aria-selected="false">1m</button>
          <button type="button" class="chip" data-range="" aria-selected="true">All</button>
        </div>
        <div class="edtbx-dates">
          <input type="date" id="edtbx-from" class="edtbx-date" aria-label="From date">
          <span class="edtbx-date-sep">to</span>
          <input type="date" id="edtbx-to" class="edtbx-date" aria-label="To date">
        </div>
      </div>

      <div class="edtbx-row">
        <label for="edtbx-limit" class="edtbx-label">Limit</label>
        <input type="number" id="edtbx-limit" class="edtbx-num" min="1" max="50" value="10" aria-label="Result limit">
        <button type="button" id="edtbx-refresh" class="edtbx-button">Refresh</button>
      </div>
    </div>
  </header>

  <section id="edtbx-list" class="edtbx-list" aria-live="polite"></section>
</div>
