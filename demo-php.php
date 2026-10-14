<?php
/**
 * Demo für die serverseitige PHP-Einbindung
 * Rendert das HTML direkt auf dem Server.
 */
declare(strict_types=1);
require_once __DIR__ . '/reviews_template.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Google Rezensionen Demo (PHP Serverseitig)</title>
  <link rel="stylesheet" href="assets/reviews.css">
  <style>
    body {
      margin: 0;
      padding: 2rem 1rem;
      background: #f8fafc;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    .demo-wrapper {
      max-width: 1100px;
      margin: 0 auto;
      background: #ffffff;
      padding: 2rem;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    }
    .demo-intro {
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid #e2e8f0;
    }
  </style>
</head>
<body>

  <div class="demo-wrapper">
    <div class="demo-intro">
      <h1>Serverseitige PHP-Einbindung</h1>
      <p>Der Inhalt wird sofort serverseitig gerendert. Keine Ladezeit für den Nutzer, 100% indexierbar für SEO und keine Google-Verbindung im Browser.</p>
    </div>

    <!-- Direkte Ausgabe über den PHP-Helper -->
    <?= renderGoogleReviews(); ?>
  </div>

  <!-- JS wird hier nur für die "Mehr lesen" Klick-Interaktion bei langen Texten benötigt -->
  <script src="assets/reviews.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Aktiviert die interaktive Textausklappung
      const widget = new GoogleReviewsWidget({ container: document.body });
      widget.attachToggleListeners();
    });
  </script>

</body>
</html>
