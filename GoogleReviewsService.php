<?php
/**
 * Service-Klasse zum serverseitigen Abruf und Caching von Google Places Rezensionen.
 *
 * Nutzt die moderne "Google Places API (New)" per v1/places/{placeId}
 * mit präzisem FieldMask zur Kosten- und Ressourcenschonung.
 * DSGVO-konform: Webseiten-Besucher kommunizieren nie mit Google.
 */

// Direkten HTTP-Aufruf im Browser verhindern
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('403 Forbidden: Direkter Zugriff verweigert.');
}

class GoogleReviewsService
{
    private array $config;

    public function __construct(?array $config = null)
    {
        if ($config === null) {
            $configFile = __DIR__ . '/config.php';
            if (!file_exists($configFile)) {
                throw new RuntimeException("Konfigurationsdatei nicht gefunden: {$configFile}");
            }
            $config = require $configFile;
        }

        $this->config = $config;
        $this->ensureCacheDirectory();
    }

    /**
     * Stellt sicher, dass das Cache-Verzeichnis existiert und beschreibbar ist.
     */
    private function ensureCacheDirectory(): void
    {
        $cacheFile = $this->config['cache_file'];
        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    /**
     * Liefert die Rezensionsdaten (aus Cache oder frisch von Google).
     *
     * @param bool $forceRefresh Wenn true, wird der Cache ignoriert und neu geladen.
     * @return array Strukturierte Daten für die Ausgabe im Frontend.
     */
    public function getReviews(bool $forceRefresh = false): array
    {
        $cacheFile = $this->config['cache_file'];
        $maxLifetime = (int) ($this->config['cache_lifetime_seconds'] ?? 86400);

        // Google ToS: Maximal 30 Tage Caching zulässig
        if ($maxLifetime > 2592000) {
            $maxLifetime = 2592000;
        }

        // Cache prüfen
        if (!$forceRefresh && file_exists($cacheFile)) {
            $mtime = filemtime($cacheFile);
            $age = time() - $mtime;

            if ($age < $maxLifetime) {
                $cachedRaw = file_get_contents($cacheFile);
                $cachedData = json_decode($cachedRaw, true);
                if (is_array($cachedData) && isset($cachedData['status']) && $cachedData['status'] === 'success') {
                    $cachedData['cached'] = true;
                    $cachedData['cache_age_seconds'] = $age;
                    return $cachedData;
                }
            }
        }

        // Cache abgelaufen oder nicht vorhanden: frisch abrufen
        try {
            $data = $this->fetchFromGoogle();
            $data['status'] = 'success';
            $data['cached'] = false;
            $data['cache_age_seconds'] = 0;
            $data['fetched_at'] = date('c');

            // Cache-Datei atomar schreiben
            $this->writeCacheFile($cacheFile, $data);

            return $data;
        } catch (Throwable $e) {
            // Im Fehlerfall: Wenn alter Cache existiert, diesen als Notfall-Fallback nutzen
            if (file_exists($cacheFile)) {
                $staleRaw = file_get_contents($cacheFile);
                $staleData = json_decode($staleRaw, true);
                if (is_array($staleData)) {
                    $staleData['cached'] = true;
                    $staleData['stale_fallback'] = true;
                    $staleData['warning'] = 'Google API temporär nicht erreichbar. Alter Cache wird verwendet.';
                    return $staleData;
                }
            }

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'rating' => 0,
                'userRatingCount' => 0,
                'reviews' => [],
            ];
        }
    }

    /**
     * Schreibt Daten atomar in die Cache-Datei.
     */
    private function writeCacheFile(string $file, array $data): void
    {
        $temp = $file . '.' . uniqid('tmp_', true);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($temp, $json) !== false) {
            @rename($temp, $file);
            @chmod($file, 0644);
        }
    }

    /**
     * Ruft die Google Places API (New) serverseitig ab.
     */
    private function fetchFromGoogle(): array
    {
        $apiKey = trim($this->config['api_key'] ?? '');
        $placeId = trim($this->config['place_id'] ?? '');

        if (empty($apiKey) || $apiKey === 'DEIN_GOOGLE_PLACES_API_KEY_HIER') {
            throw new RuntimeException('Kein gültiger Google API-Key in config.php konfiguriert.');
        }

        if (empty($placeId)) {
            throw new RuntimeException('Keine Google Place-ID in config.php konfiguriert.');
        }

        // Google Places API (New) Endpunkt
        $url = "https://places.googleapis.com/v1/places/{$placeId}";

        // Wir verlangen nur genau die benötigten Felder:
        // rating, userRatingCount, displayName, googleMapsUri, reviews
        $fieldMask = 'displayName,rating,userRatingCount,googleMapsUri,reviews';
        $lang = $this->config['language_code'] ?? 'de';

        $headers = [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . $apiKey,
            'X-Goog-FieldMask: ' . $fieldMask,
        ];

        if (!empty($lang)) {
            $headers[] = 'Accept-Language: ' . $lang;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => (int) ($this->config['http_timeout'] ?? 8),
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("cURL-Verbindungsfehler zur Google API: {$curlError}");
        }

        if ($httpCode !== 200) {
            $decodedErr = json_decode($response, true);
            $msg = $decodedErr['error']['message'] ?? "HTTP Status {$httpCode}";
            throw new RuntimeException("Google Places API Fehler: {$msg}");
        }

        $raw = json_decode($response, true);
        if (!is_array($raw)) {
            throw new RuntimeException('Ungültige JSON-Antwort von der Google Places API erhalten.');
        }

        return $this->formatApiResponse($raw);
    }

    /**
     * Formatiert und bereinigt die Rohdaten von Google in ein sauberes, leicht nutzbares Array.
     */
    private function formatApiResponse(array $raw): array
    {
        $placeName = $raw['displayName']['text'] ?? 'Unser Google Unternehmensprofil';
        $googleMapsUri = $raw['googleMapsUri'] ?? ('https://search.google.com/local/reviews?placeid=' . ($this->config['place_id'] ?? ''));
        $rating = (float) ($raw['rating'] ?? 0.0);
        $userRatingCount = (int) ($raw['userRatingCount'] ?? 0);

        $minRating = (int) ($this->config['min_rating'] ?? 1);
        $maxReviews = (int) ($this->config['max_reviews'] ?? 5);

        $formattedReviews = [];
        $rawReviews = $raw['reviews'] ?? [];

        foreach ($rawReviews as $rev) {
            $revRating = (int) ($rev['rating'] ?? 0);

            // Filter nach Mindestbewertung
            if ($revRating < $minRating) {
                continue;
            }

            $author = $rev['authorAttribution']['displayName'] ?? 'Google-Nutzer';
            $authorUri = $rev['authorAttribution']['uri'] ?? '';
            $photoUri = $rev['authorAttribution']['photoUri'] ?? '';

            // Rezensionstext
            $text = '';
            if (isset($rev['text']['text'])) {
                $text = trim($rev['text']['text']);
            } elseif (isset($rev['originalText']['text'])) {
                $text = trim($rev['originalText']['text']);
            }

            $publishTime = $rev['publishTime'] ?? '';
            $relativeTime = $rev['relativePublishTimeDescription'] ?? '';

            $formattedReviews[] = [
                'author_name' => $author,
                'author_url' => $authorUri,
                'author_photo' => $photoUri,
                'rating' => $revRating,
                'text' => $text,
                'relative_time' => $relativeTime,
                'publish_time' => $publishTime,
            ];

            if (count($formattedReviews) >= $maxReviews) {
                break;
            }
        }

        return [
            'place_name' => $placeName,
            'place_url' => $googleMapsUri,
            'rating' => $rating,
            'user_rating_count' => $userRatingCount,
            'reviews' => $formattedReviews,
        ];
    }
}
