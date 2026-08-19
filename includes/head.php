<?php
/**
 * $page_title and $page_sub should be set before including this file.
 */
$page_title = $page_title ?? 'Hissab';
$page_sub = $page_sub ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>
// Apply saved theme immediately, before CSS paints, to avoid a light-mode flash
(function () {
  try {
    var t = localStorage.getItem('hissab-theme');
    if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
  } catch (e) {}
})();
</script>
<title><?= e($page_title) ?> · Hissab</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/addon.css">
<link rel="stylesheet" href="css/dark-mode.css">
</head>
<body>
<div class="app-shell">
