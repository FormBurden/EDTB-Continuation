<div class="dp-controls">
  <div class="left">
    <!-- Rows per page -->
    <form method="get" action="/DataPoint/">
      <input type="hidden" name="table" value="<?= htmlspecialchars($dataTable) ?>">
      <label for="rows">Rows:</label>
      <select id="rows" name="rows" onchange="this.form.submit()">
        <?php foreach ([10,25,50,100] as $opt): ?>
          <option value="<?= $opt ?>"<?= $rows === $opt ? ' selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
      <noscript><button class="dp-button" type="submit">Apply</button></noscript>
    </form>

    <!-- Distance sort (only shown when available in index.php logic) -->
    <?php if ($canDistance): ?>
      <a class="dp-button dp-dist"
         href="/DataPoint/?table=<?= urlencode($dataTable) ?>&sort=distance&ad=<?= htmlspecialchars($nextAd) ?>">
        Sort by Distance
      </a>
    <?php endif; ?>

    <!-- Preset selector -->
    <?php if (!empty($presets)): ?>
      <div class="dp-preset">
        <label for="dp-preset">Preset:</label>
        <select id="dp-preset" onchange="dp_applyPreset(window.DP_STATE, this.value)">
          <option value="">All Columns</option>
          <?php foreach ($presets as $pname => $plist): ?>
            <option value="<?= htmlspecialchars($pname) ?>"><?= htmlspecialchars($pname) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>
  </div>

  <div class="right">
    <a class="dp-button" href="/DataPoint/export.php?table=<?= urlencode($dataTable) ?>">Export CSV</a>
  </div>
</div>
