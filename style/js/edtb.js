(function (w, d) {
  function ready(fn){ if (d.readyState !== 'loading') fn(); else d.addEventListener('DOMContentLoaded', fn); }
  ready(function(){
    w.EDTB = w.EDTB || {};
    console.log('[EDTB] edtb.js loaded');
    // place page bootstrap hooks here as we re-enable features
  });
})(window, document);
