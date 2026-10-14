<?php
/**
 * PHP-Helper-Funktion zur direkten serverseitigen Einbindung (ohne JavaScript zwingend zu benötigen).
 * Perfekt für SEO und sofortige Auslieferung ohne Ladezeiten.
 */

declare(strict_types=1);

require_once __DIR__ . '/GoogleReviewsService.php';

function renderGoogleReviews(?array $config = null): string
{
    $service = new GoogleReviewsService($config);
    $data = $service->getReviews();

    $reviews = $data['reviews'] ?? [];
    $rating = number_format((float) ($data['rating'] ?? 0), 1, ',', '.');
    $rawRating = (float) ($data['rating'] ?? 0);
    $userRatingCount = (int) ($data['user_rating_count'] ?? 0);
    $placeUrl = htmlspecialchars($data['place_url'] ?? 'https://www.google.com/maps', ENT_QUOTES, 'UTF-8');
    $fallbackAvatar = 'assets/avatar-placeholder.svg';

    if (empty($reviews)) {
        return '<div class="gr-container"><div class="gr-empty"><p>Aktuell liegen keine Bewertungen vor.</p></div></div>';
    }

    $cardsHtml = '';
    foreach ($reviews as $rev) {
        $authorName = htmlspecialchars($rev['author_name'] ?? 'Google-Nutzer', ENT_QUOTES, 'UTF-8');
        $authorUrl = !empty($rev['author_url']) ? htmlspecialchars($rev['author_url'], ENT_QUOTES, 'UTF-8') : null;
        $authorPhoto = !empty($rev['author_photo']) ? htmlspecialchars($rev['author_photo'], ENT_QUOTES, 'UTF-8') : $fallbackAvatar;
        $revRating = (int) ($rev['rating'] ?? 5);
        $relativeTime = htmlspecialchars($rev['relative_time'] ?? '', ENT_QUOTES, 'UTF-8');
        $text = trim($rev['text'] ?? '');
        $isLong = mb_strlen($text) > 160;

        $starsHtml = '';
        for ($i = 1; $i <= 5; $i++) {
            $starsHtml .= ($i <= $revRating)
                ? '<span aria-hidden="true">★</span>'
                : '<span class="gr-star-empty" aria-hidden="true">★</span>';
        }

        $authorHeader = $authorUrl
            ? "<a href=\"{$authorUrl}\" target=\"_blank\" rel=\"noopener noreferrer\">{$authorName}</a>"
            : "<span>{$authorName}</span>";

        $escapedText = nl2br(htmlspecialchars($text ?: 'Kein Text eingegeben.', ENT_QUOTES, 'UTF-8'));
        $collapsedClass = $isLong ? 'is-collapsed' : '';
        $toggleBtn = $isLong ? '<button type="button" class="gr-toggle-btn" aria-expanded="false">Mehr lesen</button>' : '';

        $cardsHtml .= <<<HTML
        <article class="gr-card">
          <div class="gr-card-header">
            <img
              src="{$authorPhoto}"
              alt="{$authorName}"
              class="gr-avatar"
              loading="lazy"
              referrerpolicy="no-referrer"
              onerror="this.onerror=null;this.src='{$fallbackAvatar}';"
            />
            <div class="gr-author-meta">
              <h3 class="gr-author-name">{$authorHeader}</h3>
              <div class="gr-review-date">{$relativeTime}</div>
            </div>
          </div>
          <div class="gr-card-stars" aria-label="{$revRating} von 5 Sternen">
            <span class="gr-stars">{$starsHtml}</span>
          </div>
          <div class="gr-card-body">
            <div class="gr-text-wrapper {$collapsedClass}">
              <p class="gr-text">{$escapedText}</p>
            </div>
            {$toggleBtn}
          </div>
        </article>
HTML;
    }

    $summaryStars = '';
    $roundedRating = (int) round($rawRating ?: 5);
    for ($i = 1; $i <= 5; $i++) {
        $summaryStars .= ($i <= $roundedRating)
            ? '<span aria-hidden="true">★</span>'
            : '<span class="gr-star-empty" aria-hidden="true">★</span>';
    }

    return <<<HTML
    <section class="gr-container" aria-label="Google Kundenrezensionen">
      <header class="gr-header">
        <div>
          <h2 class="gr-header-title">Das sagen unsere Kunden</h2>
          <div class="gr-count">{$userRatingCount} Google-Bewertungen insgesamt</div>
        </div>
        <a href="{$placeUrl}" target="_blank" rel="noopener noreferrer" class="gr-badge-summary" title="Alle Google-Rezensionen anzeigen">
          <span class="gr-score-number">{$rating}</span>
          <span class="gr-stars" aria-label="{$rating} von 5 Sternen">
            {$summaryStars}
          </span>
          <span class="gr-google-logo" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
          </span>
        </a>
      </header>

      <div class="gr-grid">
        {$cardsHtml}
      </div>

      <footer class="gr-footer">
        <span>Verifizierte Bewertungen über </span>
        <a href="{$placeUrl}" target="_blank" rel="noopener noreferrer">Google Profil aufrufen</a>
      </footer>
    </section>
HTML;
}
