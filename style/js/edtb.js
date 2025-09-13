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

    // place page bootstrap hooks here as we re-enable features
  });
})(window, document);
// === Keep overlays from blocking the maps (Linux parity) ===
$(function () {
  // Hide any open panels/tooltips when interacting with either map
  $('#ed3dmap, #container').on('mousedown click', function () {
    $('#addlog,#addBm,#distance,#search_system,.tooltip,#map_legend2').hide();
  });

  // ESC closes any of the panels if opened
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') {
      $('#addlog,#addBm,#distance,#search_system,.tooltip,#map_legend2').hide();
    }
  });
});

