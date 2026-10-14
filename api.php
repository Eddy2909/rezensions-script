<?php
/**
 * JSON-API Endpunkt für statische Seiten (HTML/JS)
 *
 * Kann per fetch('/api.php') aus JavaScript aufgerufen werden.
 * Liefert die gecachten Google Rezensionen als JSON aus.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Optional: CORS-Header, falls API und statische HTML-Seite auf getrennten Subdomains liegen
// header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/GoogleReviewsService.php';

try {
    $force = isset($_GET['refresh']) && $_GET['refresh'] === '1';
    $service = new GoogleReviewsService();
    $data = $service->getReviews($force);

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Fehler beim Laden der Rezensionen: ' . $e->getMessage(),
        'reviews' => []
    ], JSON_UNESCAPED_UNICODE);
}
