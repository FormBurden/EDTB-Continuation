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
        <button type="button" id="edtbx-newlog" class="edtbx-button">New Log</button>
      </div>
    </div>
  </header>

  <section id="edtbx-list" class="edtbx-list" aria-live="polite"></section>

  <!-- Add/Edit Log Modal -->
  <div id="edtbx-modal" class="edtbx-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="edtbx-modal-title">
    <div class="edtbx-modal">
      <h2 id="edtbx-modal-title">Log entry</h2>
      <form id="edtbx-form" class="edtbx-form">
        <input type="hidden" name="edit_id" id="edtbx-edit-id" value="">
        <div>
          <label for="edtbx-type">Type</label>
          <select id="edtbx-type" name="log_type">
            <option value="general">General</option>
            <option value="personal">Personal</option>
            <option value="system">System</option>
          </select>
        </div>
        <div>
          <label for="edtbx-pinned">Pinned</label>
          <select id="edtbx-pinned" name="pinned">
            <option value="0">No</option>
            <option value="1">Yes</option>
          </select>
        </div>
        <div>
          <label for="edtbx-weight">Weight</label>
          <input type="number" id="edtbx-weight" name="weight" value="0" min="-9" max="9" step="1">
        </div>
        <div>
          <label for="edtbx-title">Title</label>
          <input type="text" id="edtbx-title" name="title" maxlength="200">
        </div>
        <div>
          <label for="edtbx-system">System</label>
          <input type="text" id="edtbx-system" name="system_1" placeholder="Optional">
        </div>
        <div>
          <label for="edtbx-station">Station</label>
          <input type="text" id="edtbx-station" name="statname" placeholder="Optional">
        </div>
        <div class="full">
          <label for="edtbx-body">Body</label>
          <textarea id="edtbx-body" name="html"></textarea>
        </div>
        <div class="actions full">
          <button type="button" id="edtbx-delete" class="edtbx-button danger" style="display:none">Delete</button>
          <button type="button" id="edtbx-cancel" class="edtbx-button secondary">Cancel</button>
          <button type="submit" id="edtbx-save" class="edtbx-button">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

