<form id="dp-switch" class="dp-switch" method="get" action="/DataPoint/">
  <label for="table" style="margin-right:6px;">Table:</label>
  <select name="table" id="table" onchange="this.form.submit()">
    <?php foreach ($allowedTables as $t): ?>
      <option value="<?= htmlspecialchars($t) ?>"<?= $t === $dataTable ? ' selected' : '' ?>>
        <?= function_exists('datapoint_table_title') ? htmlspecialchars(datapoint_table_title($t)) : htmlspecialchars($t) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <noscript><button type="submit" class="dp-button">Open</button></noscript>
</form>
