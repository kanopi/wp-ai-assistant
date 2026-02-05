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

    const container = form.closest('.sk-search');
    const input = form.querySelector('.sk-search__input');
    const resultsContainer = container.querySelector('.sk-search__results');
    const resultsList = container.querySelector('.sk-search__results-list');
    const query = input.value.trim();

    if (!query) {
      return;
    }

    // Show loading state with proper aria announcement using DOM methods
    const loadingPara = document.createElement('p');
    loadingPara.className = 'wp-ai-search__loading';
    loadingPara.setAttribute('role', 'status');
    loadingPara.setAttribute('aria-live', 'assertive');
    loadingPara.textContent = 'Searching...';
    resultsList.textContent = '';
    resultsList.appendChild(loadingPara);
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

        // Create error region with proper ARIA attributes using DOM methods
        const errorId = 'wp-ai-search-error-' + Date.now();
        const errorDiv = document.createElement('div');
        errorDiv.setAttribute('role', 'alert');
        errorDiv.setAttribute('aria-live', 'assertive');
        errorDiv.setAttribute('id', errorId);
        errorDiv.className = 'wp-ai-search__error';

        const errorPara = document.createElement('p');
        const errorStrong = document.createElement('strong');
        errorStrong.textContent = errorMessage;
        errorPara.appendChild(errorStrong);

        const suggestionPara = document.createElement('p');
        suggestionPara.textContent = suggestion;

        errorDiv.appendChild(errorPara);
        errorDiv.appendChild(suggestionPara);

        // Clear and append using DOM methods
        resultsList.textContent = '';
        resultsList.appendChild(errorDiv);

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
      const noResults = document.createElement('p');
      noResults.className = 'wp-ai-search__no-results';
      noResults.textContent = 'No results found.';
      container.textContent = '';
      container.appendChild(noResults);
      // Announce to screen readers
      container.setAttribute('aria-label', 'No results found');
      return;
    }

    const resultsCount = results.length;
    const resultsCountText = resultsCount === 1 ? '1 result' : `${resultsCount} results`;

    // Clear container using textContent (safe)
    container.textContent = '';

    // Build results using DOM methods
    results.forEach((result) => {
      // Convert score to category for better user understanding
      const scoreCategory = result.score >= 0.8 ? 'Highly relevant' :
                           result.score >= 0.6 ? 'Relevant' :
                           result.score >= 0.4 ? 'Somewhat relevant' :
                           'May be relevant';

      // Create result container
      const resultDiv = document.createElement('div');
      resultDiv.className = 'wp-ai-search__result';

      // Create title heading with link
      const titleHeading = document.createElement('h3');
      titleHeading.className = 'wp-ai-search__result-title';
      const titleLink = document.createElement('a');
      titleLink.href = result.url; // Browser automatically sanitizes href
      titleLink.textContent = result.title; // textContent prevents XSS
      titleHeading.appendChild(titleLink);

      // Create excerpt paragraph
      const excerptPara = document.createElement('p');
      excerptPara.className = 'wp-ai-search__result-excerpt';
      excerptPara.textContent = result.excerpt;

      // Create meta paragraph with score
      const metaPara = document.createElement('p');
      metaPara.className = 'wp-ai-search__result-meta';
      const scoreSpan = document.createElement('span');
      scoreSpan.className = 'wp-ai-search__result-score';
      scoreSpan.setAttribute('aria-label', 'Relevance score');
      scoreSpan.textContent = scoreCategory;
      metaPara.appendChild(scoreSpan);

      // Assemble the result
      resultDiv.appendChild(titleHeading);
      resultDiv.appendChild(excerptPara);
      resultDiv.appendChild(metaPara);

      // Add to container
      container.appendChild(resultDiv);
    });

    // Announce to screen readers
    container.setAttribute('aria-label', `${resultsCountText} found`);
  }

  /**
   * Initialize search forms
   */
  function init() {
    document.querySelectorAll('.sk-search__form').forEach((form) => {
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
