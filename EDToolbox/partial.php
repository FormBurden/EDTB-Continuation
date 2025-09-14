<?php
// EDToolbox/partial.php — Commander's Log content (no includes here)
?>
<main id="edtbx-root" class="edtbx edtbx--log" data-request="0">

  <!-- Top bar -->
  <header class="edtbx-log-header">
    <div class="edtbx-log-title">
      <img src="/style/img/log.png" alt="" class="edtbx-log-icon">
      <span class="edtbx-log-h1">Commander's Log</span>
    </div>

    <nav class="edtbx-log-chips" id="edtbx-chips">
      <button class="edtbx-chip is-active" type="button" data-cat="smuggling">Smuggling tips &amp; tricks</button>
      <button class="edtbx-chip" type="button" data-cat="mining">Mining tips &amp; tricks</button>
      <button class="edtbx-chip" type="button" data-cat="ship_discounts">Ship Discounts</button>
      <button class="edtbx-chip" type="button" data-cat="ship_loadouts">Ship Loadouts</button>
    </nav>

    <div class="edtbx-log-mini">
      <a href="#" class="edtbx-mini">Crime &amp; Punishment</a>
      <span class="edtbx-dot">•</span>
      <a href="#" class="edtbx-mini">Rank Progression</a>
    </div>

    <div class="edtbx-log-dts">24 Jan 3301, 19:32</div>
  </header>

  <!-- Status + Results under chips -->
  <section class="edtbx-log-results">
    <div id="edtbx-logstatus" class="edtbx-logstatus"></div>
    <ul id="edtbx-loglist" class="edtbx-loglist"></ul>
  </section>

  <!-- Earth-like -->
  <section class="edtbx-catalog">
    <h2 class="edtbx-strip-title"><span>Earth-like</span></h2>
    <ul class="edtbx-strip planets-earthlike">
      <li class="planet"><img src="/SystemMap/bodies/earth-like_world_0.png" alt="Earth-like 0"></li>
      <li class="planet"><img src="/SystemMap/bodies/earth-like_world_1.png" alt="Earth-like 1"></li>
      <li class="planet"><img src="/SystemMap/bodies/earth-like_world_0.png" alt="Earth-like 0"></li>
      <li class="planet"><img src="/SystemMap/bodies/earth-like_world_1.png" alt="Earth-like 1"></li>
      <li class="planet"><img src="/SystemMap/bodies/earth-like_world_0.png" alt="Earth-like 0"></li>
      <li class="planet"><img src="/SystemMap/bodies/earth-like_world_1.png" alt="Earth-like 1"></li>
      <li class="planet"><img src="/SystemMap/bodies/earth-like_world_0.png" alt="Earth-like 0"></li>
      <li class="planet"><img src="/SystemMap/bodies/earth-like_world_1.png" alt="Earth-like 1"></li>
    </ul>
  </section>

  <!-- Water -->
  <section class="edtbx-catalog">
    <h2 class="edtbx-strip-title"><span>Water</span></h2>
    <ul class="edtbx-strip planets-water">
      <li class="planet"><img src="/SystemMap/bodies/water_world_0.png" alt="Water world 0"></li>
      <li class="planet"><img src="/SystemMap/bodies/water_world_2.png" alt="Water world 2"></li>
      <li class="planet"><img src="/SystemMap/bodies/water_world_3.png" alt="Water world 3"></li>
      <li class="planet"><img src="/SystemMap/bodies/water_world_4.png" alt="Water world 4"></li>
      <li class="planet"><img src="/SystemMap/bodies/water_world_0.png" alt="Water world 0"></li>
      <li class="planet"><img src="/SystemMap/bodies/water_world_2.png" alt="Water world 2"></li>
      <li class="planet"><img src="/SystemMap/bodies/water_world_3.png" alt="Water world 3"></li>
      <li class="planet"><img src="/SystemMap/bodies/water_world_4.png" alt="Water world 4"></li>
    </ul>
  </section>

  <!-- Guide + scoopable stars -->
  <section class="edtbx-guide">
    <div class="edtbx-guide-line">
      Nutter's explorers guide to the Galaxy
      <a href="#" class="edtbx-extlink" title="External link"></a>
    </div>
    <div class="edtbx-scoopable">
      <div class="label">Scoopable stars:</div>
      <div class="stars">A, B, F, G, K, M, O</div>
    </div>
  </section>

</main>
