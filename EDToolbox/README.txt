EDToolbox isolated tab bundle
=============================

Files:
- /EDToolbox/index.php
- /EDToolbox/js/edtoolbox.js
- /EDToolbox/css/edtoolbox.css

Usage:
1) Copy the EDToolbox folder to your project web root.
2) Visit /EDToolbox/?request=0 in your browser.
3) This view fetches JSON from /get/getData.php?request=<id> and renders:
   - system_title (header)
   - system_info (key/value grid; underscores prettified)
   - station_data (table)
   - a small log panel

Notes:
- Kept self-contained; no cross-tab dependencies.
- Styling is namespaced with .edtbx- to avoid collisions.
