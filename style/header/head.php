<?php require_once __DIR__ . '/../Theme.php'; ?>
<?php
/** head.php — document head & assets */
global $settings;

/** Prefer the class property if Header set it; fall back to settings */
$title = isset($this->pageTitle) && $this->pageTitle !== ''
    ? $this->pageTitle
    : ($settings['edtb_name'] ?? 'ED ToolBox');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>

<link rel="stylesheet" href="/style/style.css">
<link rel="stylesheet" href="/style/colors.css">

<!-- jQuery: CDN first; if it fails, write local fallback synchronously -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
        integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
        crossorigin="anonymous"></script>
<script>window.jQuery || document.write('<script src="/style/js/jquery.min.js"><\/script>')</script>

<!-- Legacy front-end deps expected by inline EDTB code -->
<script src="/source/Vendor/wiselinks-1.2.2.min.js"></script>
<script src="/source/Vendor/markitup/jquery.markitup.js"></script>
<script src="/source/Vendor/markitup/sets/html/set.js"></script>


<!-- Original global helpers (defines get_cs, get_data, make_gallery, etc.) -->
<script src="/source/javascript.js"></script>

<!-- Project bootstrap (must come last) -->
<script src="/style/js/edtb.js"></script>
<script>
// Force normal navigation for ED ToolBox from any tab (bypass SPA interceptors)
(function () {
  function isEdtbxLink(a) {
    if (!a) return false;
    var href = a.getAttribute('href') || '';
    return href.indexOf('/EDToolbox') !== -1 || a.dataset.hardNav === '1' || a.id === 'nav-edtoolbox';
  }
  // Capture phase so we run before bubble-phase SPA handlers
  document.addEventListener('click', function (ev) {
    var a = ev.target && ev.target.closest && ev.target.closest('a');
    if (!isEdtbxLink(a)) return;
    // Let the browser perform default navigation; block JS routers from hijacking
    ev.stopImmediatePropagation();
    // Do NOT call preventDefault(); default navigation should proceed.
  }, true);
})();
</script>



</head>

