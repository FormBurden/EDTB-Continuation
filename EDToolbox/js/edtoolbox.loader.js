// Path: /EDToolbox/js/edtoolbox.loader.js
// Injects the ED Toolbox partial into a container and loads its JS/CSS.
// === on-demand loader; does nothing until loadEDToolbox(...) is called ===
(function () {
  function ensureCss(href) {
    if ([].some.call(document.styleSheets, s => s.href && s.href.indexOf(href) !== -1)) return;
    var l = document.createElement('link');
    l.rel = 'stylesheet';
    l.href = href;
    document.head.appendChild(l);
  }

  function loadEdtbxScript(cb) {
    var old = document.querySelector('script[data-edtbx="1"]');
    if (old) old.remove();
    var s = document.createElement('script');
    s.src = '/EDToolbox/js/edtoolbox.js?v=2';
    s.defer = true;
    s.setAttribute('data-edtbx', '1');
    s.onload = function () { if (typeof cb === 'function') cb(); };
    document.head.appendChild(s);
  }

  window.loadEDToolbox = function (containerSelector, requestFlag) {
    var target = document.querySelector(containerSelector || '#scrollable');
    if (!target) return;

    // 1) ensure CSS for this view
    ensureCss('/EDToolbox/css/edtoolbox.css?v=1');

    // 2) fetch partial and inject
    var url = '/EDToolbox/partial.php' + (requestFlag ? ('?request=' + encodeURIComponent(requestFlag)) : '');
    fetch(url, { credentials: 'same-origin' })
      .then(function (r) { return r.text(); })
      .then(function (html) {
        target.innerHTML = html;

        // 3) load script and then call load() to populate
        loadEdtbxScript(function () {
          if (typeof load === 'function') { load(); }
        });
      });
  };
})();

