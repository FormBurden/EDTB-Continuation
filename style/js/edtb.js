(function (w, d) {
  function ready(fn){ if (d.readyState !== 'loading') fn(); else d.addEventListener('DOMContentLoaded', fn); }
  ready(function(){
    w.EDTB = w.EDTB || {};
    console.log('[EDTB] edtb.js loaded');
    // jQuery 3: restore legacy `$(...).load(fn)` shorthand to mean "on load"
    if (window.jQuery && jQuery.fn && !jQuery.fn.__edtbLoadPatched) {
      (function ($) {
        const oldLoad = $.fn.load;
        $.fn.load = function (a, b, c) {
          if (typeof a === 'function' && (b === undefined && c === undefined)) {
            return this.on('load', a);
          }
          return oldLoad ? oldLoad.call(this, a, b, c) : this;
        };
        $.fn.__edtbLoadPatched = true;
      })(jQuery);
    }
    // === Journal badge updater ===
    (function () {
      function fmtAgo(iso) {
        var t = new Date(iso).getTime();
        var s = Math.max(0, Math.floor((Date.now() - t) / 1000));
        if (s < 60) return s + 's ago';
        var m = Math.floor(s / 60);
        if (m < 60) return m + 'm ago';
        var h = Math.floor(m / 60);
        if (h < 24) return h + 'h ago';
        var d = Math.floor(h / 24);
        return d + 'd ago';
      }

      function updateJournalBadge() {
        fetch('/get/getData_status.php', { cache: 'no-store' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data || !data.journal) return;
            var j = data.journal;
            var el = document.getElementById('journal-badge');
            if (!el) return;
            var txt = 'Journal: ' + (j.last_system || '-') + ' \u2022 ' + (j.last_ts ? fmtAgo(j.last_ts) : '-');
            el.textContent = txt;
            if (j.file) el.setAttribute('title', j.file);
          });
      }

      updateJournalBadge();
      setInterval(updateJournalBadge, 15000);
    })();

    // place page bootstrap hooks here as we re-enable features
  });
})(window, document);
// === Keep overlays from blocking the maps (Linux parity) ===
$(function () {
  // Hide any open panels/tooltips when interacting with either map
  $('#ed3dmap, #container').on('mousedown click', function () {
    $('#addlog,#addBm,#distance,#search_system,.tooltip,#map_legend2,#settings,#about').hide();
  });

  // ESC closes any of the panels if opened
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') {
      $('#addlog,#addBm,#distance,#search_system,.tooltip,#map_legend2,#settings,#about').hide();
    }
  });
});
// === Settings/About panel behavior (original parity) ===
$(function () {
  var $settings = $('#settings');
  var $about = $('#about');

  // Cog click: open/close Settings (slides), and hide About
  $('#settings_click').on('click', function (e) {
    e.preventDefault();
    $about.hide();
    $settings.stop(true, true).slideToggle(120);
    return false;
  });

  // About click: open/close About (slides), and hide Settings
  $('#about_click').on('click', function (e) {
    e.preventDefault();
    $settings.hide();
    $about.stop(true, true).slideToggle(120);
    return false;
  });

  // Click outside either panel closes both (fade, like original UX)
  $(document).on('mousedown', function (e) {
    if (!$(e.target).closest('#settings,#about,.rightpanel-icons').length) {
      $('#settings,#about').stop(true, true).fadeOut(100);
    }
  });
});


