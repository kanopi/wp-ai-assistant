(function ($) {
  'use strict';

  const settings = window.wpAiAssistantSearch;

  if (!settings || !settings.endpoint) {
    return;
  }

  /**
   * Handle AI search form submission
   */
  function handleSearchSubmit(event, form) {
    event.preventDefault();

    const container = form.closest('.wp-ai-search');
    const input = form.querySelector('.wp-ai-search__input');
    const resultsContainer = container.querySelector('.wp-ai-search__results');
    const resultsList = container.querySelector('.wp-ai-search__results-list');
    const query = input.value.trim();

    if (!query) {
      return;
    }

    // Show loading state with proper aria announcement
    resultsList.innerHTML = '<p class="wp-ai-search__loading" role="status" aria-live="assertive">Searching...</p>';
    resultsContainer.style.display = 'block';

    // Perform AJAX search
    fetch(settings.endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': settings.nonce,
      },
      body: JSON.stringify({
        query: query,
        top_k: settings.topK,
      }),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Search request failed');
        }
        return response.json();
      })
      .then((data) => {
        displayResults(resultsList, data.results);
      })
      .catch((error) => {
        // Generate helpful error message with recovery instructions
        let errorMessage = 'Sorry, there was an error performing your search.';
        let suggestion = 'Please check your connection and try again.';

        if (error.message && error.message.includes('network')) {
          suggestion = 'Please check your internet connection and try again in a moment.';
        } else if (error.message && error.message.includes('timeout')) {
          suggestion = 'The search is taking longer than expected. Please try a shorter query.';
        }

        // Create error region with proper ARIA attributes
        const errorId = 'wp-ai-search-error-' + Date.now();
        resultsList.innerHTML = `
          <div role="alert" aria-live="assertive" id="${errorId}" class="wp-ai-search__error">
            <p><strong>${errorMessage}</strong></p>
            <p>${suggestion}</p>
          </div>
        `;

        // Associate error with input
        input.setAttribute('aria-describedby', errorId);
        input.setAttribute('aria-invalid', 'true');

        console.error('AI Search error:', error);
      });
  }

  /**
   * Display search results
   */
  function displayResults(container, results) {
    if (!results || results.length === 0) {
      container.innerHTML = '<p class="wp-ai-search__no-results">No results found.</p>';
      // Announce to screen readers
      container.setAttribute('aria-label', 'No results found');
      return;
    }

    const resultsCount = results.length;
    const resultsCountText = resultsCount === 1 ? '1 result' : `${resultsCount} results`;

    const html = results
      .map((result) => {
        // Convert score to category for better user understanding
        const scoreCategory = result.score >= 0.8 ? 'Highly relevant' :
                             result.score >= 0.6 ? 'Relevant' :
                             result.score >= 0.4 ? 'Somewhat relevant' :
                             'May be relevant';

        return `
          <div class="wp-ai-search__result">
            <h3 class="wp-ai-search__result-title">
              <a href="${escapeHtml(result.url)}">${escapeHtml(result.title)}</a>
            </h3>
            <p class="wp-ai-search__result-excerpt">${escapeHtml(result.excerpt)}</p>
            <p class="wp-ai-search__result-meta">
              <span class="wp-ai-search__result-score" aria-label="Relevance score">${scoreCategory}</span>
            </p>
          </div>
        `;
      })
      .join('');

    container.innerHTML = html;
    // Announce to screen readers
    container.setAttribute('aria-label', `${resultsCountText} found`);
  }

  /**
   * Escape HTML to prevent XSS
   */
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  /**
   * Initialize search forms
   */
  function init() {
    document.querySelectorAll('.wp-ai-search__form').forEach((form) => {
      form.addEventListener('submit', (event) => {
        handleSearchSubmit(event, form);
      });
    });
  }

  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(jQuery);
