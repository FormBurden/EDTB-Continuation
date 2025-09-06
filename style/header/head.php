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

<script src="/style/js/edtb.js"></script>

</head>

