/**
 * Google Reviews Frontend Component (Vanilla JavaScript)
 * Keine externen Bibliotheken nötig.
 * Lädt Daten DSGVO-konform von der lokalen api.php und rendert die Karten.
 */

(function () {
  'use strict';

  class GoogleReviewsWidget {
    /**
     * @param {Object} options Konfigurationsoptionen
     * @param {string|HTMLElement} options.container Selektor oder DOM-Element des Zielcontainers
     * @param {string} [options.apiUrl='api.php'] Pfad zum lokalen PHP-Endpunkt
     * @param {number} [options.excerptLength=160] Max. Zeichenlänge für den Textauszug
     * @param {string} [options.fallbackAvatar='assets/avatar-placeholder.svg'] Fallback-Avatar
     */
    constructor(options = {}) {
      this.container = typeof options.container === 'string'
        ? document.querySelector(options.container)
        : options.container;

      if (!this.container) {
        console.error('GoogleReviewsWidget: Zielcontainer nicht gefunden.');
        return;
      }

      this.apiUrl = options.apiUrl || 'api.php';
      this.excerptLength = options.excerptLength || 160;
      this.fallbackAvatar = options.fallbackAvatar || 'assets/avatar-placeholder.svg';

      this.init();
    }

    async init() {
      this.renderLoading();
      try {
        const response = await fetch(this.apiUrl, {
          headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
          throw new Error(`HTTP-Fehler ${response.status}`);
        }

        const data = await response.json();

        if (data.status === 'error' && (!data.reviews || data.reviews.length === 0)) {
          this.renderError(data.message || 'Rezensionen konnten nicht geladen werden.');
          return;
        }

        this.render(data);
      } catch (err) {
        console.error('GoogleReviewsWidget Fehler:', err);
        this.renderError('Bewertungen konnten momentan nicht geladen werden.');
      }
    }

    renderLoading() {
      this.container.innerHTML = `
        <div class="gr-container">
          <div class="gr-loading">
            <div class="gr-spinner" aria-hidden="true"></div>
            <p>Bewertungen werden geladen...</p>
          </div>
        </div>
      `;
    }

    renderError(msg) {
      this.container.innerHTML = `
        <div class="gr-container">
          <div class="gr-error">
            <p>${this.escapeHtml(msg)}</p>
          </div>
        </div>
      `;
    }

    render(data) {
      const reviews = data.reviews || [];
      const rating = Number(data.rating || 0).toFixed(1);
      const userRatingCount = data.user_rating_count || 0;
      const placeUrl = data.place_url || 'https://www.google.com/maps';
      const placeName = data.place_name || 'Kundenbewertungen';

      if (reviews.length === 0) {
        this.container.innerHTML = `
          <div class="gr-container">
            <div class="gr-empty">
              <p>Aktuell liegen noch keine Rezensionen vor.</p>
            </div>
          </div>
        `;
        return;
      }

      const cardsHtml = reviews.map(rev => this.renderCard(rev)).join('');

      this.container.innerHTML = `
        <section class="gr-container" aria-label="Google Kundenrezensionen">
          <header class="gr-header">
            <div>
              <h2 class="gr-header-title">Das sagen unsere Kunden</h2>
              <div class="gr-count">${userRatingCount} Google-Bewertungen insgesamt</div>
            </div>
            <a href="${this.escapeHtml(placeUrl)}" target="_blank" rel="noopener noreferrer" class="gr-badge-summary" title="Alle Google-Rezensionen anzeigen">
              <span class="gr-score-number">${rating}</span>
              <span class="gr-stars" aria-label="${rating} von 5 Sternen">
                ${this.renderStars(Math.round(data.rating || 5))}
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
            ${cardsHtml}
          </div>

          <footer class="gr-footer">
            <span>Verifizierte Bewertungen über </span>
            <a href="${this.escapeHtml(placeUrl)}" target="_blank" rel="noopener noreferrer">Google Profil aufrufen</a>
          </footer>
        </section>
      `;

      // Event Listener für "Mehr lesen / Weniger"
      this.attachToggleListeners();
    }

    renderCard(review) {
      const authorName = review.author_name || 'Google-Nutzer';
      const authorUrl = review.author_url || '';
      const authorPhoto = review.author_photo || this.fallbackAvatar;
      const rating = Number(review.rating || 5);
      const relativeTime = review.relative_time || '';
      const fullText = (review.text || '').trim();

      const isLongText = fullText.length > this.excerptLength;

      const authorLinkHtml = authorUrl
        ? `<a href="${this.escapeHtml(authorUrl)}" target="_blank" rel="noopener noreferrer">${this.escapeHtml(authorName)}</a>`
        : `<span>${this.escapeHtml(authorName)}</span>`;

      return `
        <article class="gr-card">
          <div class="gr-card-header">
            <img
              src="${this.escapeHtml(authorPhoto)}"
              alt="${this.escapeHtml(authorName)}"
              class="gr-avatar"
              loading="lazy"
              referrerpolicy="no-referrer"
              onerror="this.onerror=null;this.src='${this.escapeHtml(this.fallbackAvatar)}';"
            />
            <div class="gr-author-meta">
              <h3 class="gr-author-name">${authorLinkHtml}</h3>
              ${relativeTime ? `<div class="gr-review-date">${this.escapeHtml(relativeTime)}</div>` : ''}
            </div>
          </div>

          <div class="gr-card-stars" aria-label="${rating} von 5 Sternen">
            <span class="gr-stars">${this.renderStars(rating)}</span>
          </div>

          <div class="gr-card-body">
            <div class="gr-text-wrapper ${isLongText ? 'is-collapsed' : ''}">
              <p class="gr-text">${this.escapeHtml(fullText || 'Kein Text eingegeben.')}</p>
            </div>
            ${isLongText ? '<button type="button" class="gr-toggle-btn" aria-expanded="false">Mehr lesen</button>' : ''}
          </div>
        </article>
      `;
    }

    renderStars(count) {
      let html = '';
      for (let i = 1; i <= 5; i++) {
        if (i <= count) {
          html += '<span aria-hidden="true">★</span>';
        } else {
          html += '<span class="gr-star-empty" aria-hidden="true">★</span>';
        }
      }
      return html;
    }

    attachToggleListeners() {
      const buttons = this.container.querySelectorAll('.gr-toggle-btn');
      buttons.forEach(btn => {
        btn.addEventListener('click', () => {
          const wrapper = btn.previousElementSibling;
          if (!wrapper) return;

          const isCollapsed = wrapper.classList.contains('is-collapsed');
          if (isCollapsed) {
            wrapper.classList.remove('is-collapsed');
            btn.textContent = 'Weniger anzeigen';
            btn.setAttribute('aria-expanded', 'true');
          } else {
            wrapper.classList.add('is-collapsed');
            btn.textContent = 'Mehr lesen';
            btn.setAttribute('aria-expanded', 'false');
          }
        });
      });
    }

    escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }
  }

  // Global verfügbar machen
  window.GoogleReviewsWidget = GoogleReviewsWidget;

  // Auto-Initialisierung, falls Container mit data-google-reviews Attribut vorhanden ist
  document.addEventListener('DOMContentLoaded', () => {
    const autoEl = document.querySelector('[data-google-reviews]');
    if (autoEl) {
      new GoogleReviewsWidget({ container: autoEl });
    }
  });
})();
