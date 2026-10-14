<?php
/**
 * Konfiguration für das Google Rezensionen Script
 *
 * HINWEIS: Trage hier deinen echten API-Key und deine Place-ID ein.
 * Für zusätzliche Sicherheit sollte diese Datei im Webserver vor direktem
 * HTTP-Zugriff geschützt sein (z.B. per .htaccess).
 */

return [
    // -------------------------------------------------------------------------
    // Google Places API Zugangsdaten
    // -------------------------------------------------------------------------

    // Dein Google Maps Platform API-Key mit aktivierter "Places API (New)"
    'api_key' => 'DEIN_GOOGLE_PLACES_API_KEY_HIER',

    // Die Google Place-ID deines Standorts/Unternehmens
    // Findest du z.B. über: https://developers.google.com/maps/documentation/places/web-service/place-id
    'place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',

    // -------------------------------------------------------------------------
    // Caching & Gültigkeit
    // -------------------------------------------------------------------------

    // Cache-Gültigkeit in Sekunden:
    // Empfehlung: 86400 (24 Stunden = 1 Tag) oder 604800 (7 Tage).
    // WICHTIG laut Google Terms of Service: Caching maximal 30 Tage (2.592.000 Sekunden) erlaubt!
    'cache_lifetime_seconds' => 86400,

    // Pfad zur lokalen Cache-Datei (relativ zu diesem Verzeichnis oder absolut)
    'cache_file' => __DIR__ . '/cache/reviews_cache.json',

    // Sprache der Rezensionen (ISO 639-1 Code, z.B. 'de', 'en')
    'language_code' => 'de',

    // -------------------------------------------------------------------------
    // Anzeige & Filter
    // -------------------------------------------------------------------------

    // Maximale Anzahl an Rezensionen, die ausgegeben werden sollen (Google liefert max. 5)
    'max_reviews' => 5,

    // Mindestanzahl an Sternen (1 bis 5). Rezensionen unter diesem Rating werden ausgefiltert.
    // 1 = alle anzeigen, 4 = nur 4 und 5 Sterne anzeigen
    'min_rating' => 1,

    // Maximale Textlänge vor dem Ausblenden/Auslaufen (in Zeichen)
    // Längere Texte erhalten im Frontend einen sanften Farbverlauf und "Mehr lesen"-Button
    'excerpt_length' => 160,

    // Fallback: Wenn der Google-User kein Profilbild hat oder es geblockt wird
    'default_avatar' => 'assets/avatar-placeholder.svg',

    // Timeout für HTTP-Anfragen an die Google API in Sekunden
    'http_timeout' => 8,
];
