# Google Rezensionen Einbindung (DSGVO-konform, PHP & JS)

Ein schlankes, modulares und wiederverwendbares Skript, um **Google Rezensionen** (Google Business Profile) sauber und datenschutzkonform in beliebige Kunden-Websites (statisches HTML, JavaScript, PHP auf Apache Shared Hosting) einzubinden.

## Die Kernvorteile auf einen Blick

- **100% DSGVO-konform:** Webseiten-Besucher kommunizieren **nie** mit Google. Kein Google-Skript oder externer iFrame im Frontend. Daher ist **kein Cookie-Consent-Banner** für die Rezensionen nötig.
- **Wiederverwendbar & flexibel:** Konfiguration komplett über eine zentrale Datei (`config.php`). Funktioniert auf unzähligen Domains/Websites parallel.
- **Kostenlos & ToS-konform:** Dank serverseitigem Caching (empfohlen 24h oder 7 Tage, max. 30 Tage laut Google ToS) bleibt der Abruf im monatlichen Free-Kontingent der Google Places API.
- **Modernes, neutrales Design:** Responsive CSS-Grid-Karten, Google-Sterne, User-Avatar mit Fallback und eleganter **Text-Fade-Out-Verlauf** mit „Mehr lesen“-Toggle für lange Rezensionen.
- **Zwei Einbindungs-Wege:** 
  1. Asynchron in rein statische HTML/JS-Seiten via `api.php`.
  2. Direkt serverseitig in PHP-Seiten via `renderGoogleReviews()`.

---

## Projektstruktur

```text
rezensions-script/
├── config.php                 # Zentrale Konfiguration (API-Key, Place-ID, Caching, Filter)
├── GoogleReviewsService.php   # Service-Klasse (Google Places API v1 Call, Atomarer Cache, Formatierung)
├── api.php                    # JSON-Endpunkt für statische HTML/JS-Websites
├── reviews_template.php       # PHP-Helper zum direkten serverseitigen Rendern
├── demo-static.html           # Demo für HTML + JS Einbindung
├── demo-php.php               # Demo für direkte PHP Einbindung
├── cache/                     # Speicherort für gecachte JSON-Daten (per .htaccess geschützt)
│   └── .htaccess
└── assets/
    ├── reviews.css            # Neutrales, variables CSS (CSS Custom Properties)
    ├── reviews.js             # Vanilla JS Component (kein jQuery oder Build-Step nötig)
    └── avatar-placeholder.svg # DSGVO-konformer Platzhalter für fehlende User-Bilder
```

---

## Schnellstart

### 1. Dateien hochladen
Kopiere den Ordner (oder die Dateien) auf deinen Webspace (Shared Hosting / Apache mit PHP 7.4, 8.0, 8.1, 8.2, 8.3 oder 8.5).

### 2. Konfiguration anpassen (`config.php`)
Öffne `config.php` und trage deinen Google API-Key und deine Place-ID ein:

```php
return [
    // Dein Google API-Key mit aktivierter "Places API (New)"
    'api_key' => 'AIzaSy...',

    // Die Place-ID deines Google Unternehmensprofils
    'place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',

    // Cache-Dauer in Sekunden (z.B. 86400 = 24 Stunden, 604800 = 7 Tage)
    // HINWEIS: Google ToS erlaubt maximal 30 Tage Caching!
    'cache_lifetime_seconds' => 86400,

    // Max. Anzahl an Rezensionen (Google liefert technisch max. 5)
    'max_reviews' => 5,

    // Mindestanzahl an Sternen (z.B. 4 für nur 4- und 5-Sterne-Rezensionen)
    'min_rating' => 1,

    // Textlänge vor dem Fade-Out-Ausschnitt
    'excerpt_length' => 160,
];
```

---

## Einbindungsmethoden

### Variante A: In eine statische HTML-Seite (mit JavaScript)

Füge im `<head>` das CSS ein:
```html
<link rel="stylesheet" href="assets/reviews.css">
```

Platziere an der gewünschten Stelle den Ziel-Container:
```html
<div id="google-reviews"></div>
```

Binde vor dem schließenden `</body>` das Script ein und initialisiere es:
```html
<script src="assets/reviews.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    new GoogleReviewsWidget({
      container: '#google-reviews',
      apiUrl: 'api.php',
      excerptLength: 160
    });
  });
</script>
```

---

### Variante B: Direkt serverseitig in eine PHP-Seite

Ideal für maximale SEO-Sichtbarkeit und null Layout-Shift / Ladezeit:

```php
<?php
require_once __DIR__ . '/reviews_template.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <link rel="stylesheet" href="assets/reviews.css">
</head>
<body>

  <!-- Rendert die Rezensionen sofort ins HTML -->
  <?= renderGoogleReviews(); ?>

  <!-- JS wird hier nur für den interaktiven "Mehr lesen"-Klick benötigt -->
  <script src="assets/reviews.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const widget = new GoogleReviewsWidget({ container: document.body });
      widget.attachToggleListeners();
    });
  </script>
</body>
</html>
```

---

## Design-Anpassungen (CSS Variablen)

Das Design ist standardmäßig neutral und schlicht gehalten. Du kannst alle Farben, Eckenradien und Schriften in deinem eigenen Stylesheet über CSS-Variablen überschreiben:

```css
:root {
  --gr-card-bg: #ffffff;
  --gr-card-border: #e2e8f0;
  --gr-card-radius: 12px;
  --gr-star-filled: #f59e0b; /* Farbe der Bewertungssterne */
  --gr-accent: #2563eb;      /* Farbe für "Mehr lesen" & Links */
}
```

---

## Google Places API Limits & Hinweise

1. **Maximal 5 Rezensionen:** Die Google Places API (Place Details) gibt per Definition von Google **maximal die 5 relevantesten Rezensionen** aus. Mehr Rezensionen stellt Google nur über die Google Business Profile Performance API für verifizierte Profileigentümer zur Verfügung.
2. **Kosten & Free Tier:** Google Maps Platform gewährt monatlich ein Guthaben (bzw. bis zu 1.000 Aufrufe für Place Details). Mit dem integrierten 24h-Cache verbraucht eine Website nur ca. 30 Abfragen pro Monat und ist somit **dauerhaft 100% kostenlos**.
3. **Google Terms of Service:** Google untersagt das unbegrenzte Archivieren von Inhalten. Die maximale Cache-Gültigkeit ist im Service auf höchstens 30 Tage gedeckelt.

---

## Lizenz
Frei verwendbar für eigene und Kundenprojekte (MIT-kompatibel).
