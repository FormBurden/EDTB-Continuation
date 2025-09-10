(function (w, d) {
  function ready(fn){ if (d.readyState !== 'loading') fn(); else d.addEventListener('DOMContentLoaded', fn); }
  ready(function(){
    w.EDTB = w.EDTB || {};
    console.log('[EDTB] edtb.js loaded');
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

