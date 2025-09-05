// Path: /EDToolbox/js/edtoolbox.loader.js
// Injects the ED Toolbox partial into a container and loads its JS/CSS.
(function(){
  function ensureCss(href){
    if ([...document.querySelectorAll('link[rel="stylesheet"]')].some(l => l.href.includes(href))) return;
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
  }
  function loadScript(src){
    return new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = src;
      s.defer = true;
      s.onload = resolve;
      s.onerror = () => reject(new Error('Failed to load ' + src));
      document.body.appendChild(s);
    });
  }
  async function loadEDToolbox(containerSelector, request){
    const container = document.querySelector(containerSelector);
    if (!container) throw new Error('Container not found: ' + containerSelector);

    ensureCss('/EDToolbox/css/edtoolbox.css?v=1');

    const res = await fetch('/EDToolbox/partial.php?request=' + encodeURIComponent(request || '0'), { cache: 'no-store' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    container.innerHTML = await res.text();

    window.EDTBX_CFG = { api: '/get/getData.php' };
    await loadScript('/EDToolbox/js/edtoolbox.js?v=1');
    // edtoolbox.js self-inits and calls load()
  }
  // Expose globally so Header.php can call it on nav click:
  window.loadEDToolbox = loadEDToolbox;
})();
