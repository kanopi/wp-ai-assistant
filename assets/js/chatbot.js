(function () {
  const settings = window.wpAiAssistantChatbot;

  if (!settings || !settings.endpoint) {
    return;
  }

  // Track if Deep Chat is loaded
  let deepChatLoaded = false;
  let deepChatLoading = false;
  const loadCallbacks = [];

  /**
   * Lazy load Deep Chat library
   */
  function loadDeepChat(callback) {
    // If already loaded, call callback immediately
    if (deepChatLoaded) {
      callback();
      return;
    }

    // Add callback to queue
    loadCallbacks.push(callback);

    // If already loading, don't load again
    if (deepChatLoading) {
      return;
    }

    deepChatLoading = true;

    // Create script tag for Deep Chat
    const script = document.createElement('script');
    script.type = 'module';
    script.src = settings.deepChatUrl || 'https://cdn.jsdelivr.net/npm/deep-chat@2.3.0/dist/deepChat.bundle.js';

    script.onload = function() {
      deepChatLoaded = true;
      deepChatLoading = false;

      // Execute all queued callbacks
      loadCallbacks.forEach(cb => cb());
      loadCallbacks.length = 0;
    };

    script.onerror = function() {
      console.error('Failed to load Deep Chat library');
      deepChatLoading = false;

      // Show user-friendly error
      const errorMsg = document.createElement('div');
      errorMsg.className = 'wp-ai-chat-error';
      errorMsg.setAttribute('role', 'alert');
      errorMsg.setAttribute('aria-live', 'assertive');
      errorMsg.innerHTML = '<p style="padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px;">Unable to load chat interface. Please refresh the page or try again later.</p>';
      document.body.appendChild(errorMsg);

      // Remove error after 10 seconds
      setTimeout(() => {
        if (errorMsg.parentNode) {
          errorMsg.parentNode.removeChild(errorMsg);
        }
      }, 10000);
    };

    document.head.appendChild(script);
  }

  /**
   * Announce message to screen readers
   */
  function announceMessage(message, isError = false) {
    const announcer = document.getElementById('wp-ai-chat-announcements');
    if (announcer) {
      // Strip HTML tags for screen reader announcement
      const textContent = message.replace(/<[^>]*>/g, '');
      const prefix = isError ? 'Error: ' : 'Assistant response: ';
      announcer.textContent = prefix + textContent;

      // Calculate appropriate delay based on message length
      const wordsCount = textContent.split(/\s+/).length;
      const readingTime = Math.max(2000, wordsCount * 300); // 300ms per word, minimum 2s

      // Clear after announcement to allow repeated messages
      setTimeout(() => {
        announcer.textContent = '';
      }, readingTime);
    }
  }

  /**
   * Shared handler for Deep Chat API requests
   */
  async function handleChatRequest(body, signals) {
    try {
      const messages = body.messages || [];
      const lastMessage = messages[messages.length - 1];
      const question = lastMessage?.text || lastMessage?.content || '';

      if (!question) {
        const errorMsg = 'Please enter a question.';
        signals.onResponse({ error: errorMsg });
        announceMessage(errorMsg, true);
        return;
      }

      const response = await fetch(settings.endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': settings.nonce,
        },
        body: JSON.stringify({
          question: question,
          top_k: settings.topK,
        }),
      });

      if (!response.ok) {
        const errorData = await response.json();
        const errorMsg = errorData.message || 'Failed to get response from server.';
        signals.onResponse({ error: errorMsg });
        announceMessage(errorMsg, true);
        return;
      }

      const data = await response.json();

      if (data.answer) {
        signals.onResponse({ html: data.answer });
        announceMessage(data.answer);
        return;
      }

      if (data.message || data.error || data.code) {
        const errorMsg = data.message || data.error || 'Sorry, something went wrong.';
        signals.onResponse({ error: errorMsg });
        announceMessage(errorMsg, true);
        return;
      }

      const fallbackError = 'Sorry, something went wrong.';
      signals.onResponse({ error: fallbackError });
      announceMessage(fallbackError, true);
    } catch (error) {
      const errorMsg = error.message || 'Sorry, something went wrong.';
      signals.onResponse({ error: errorMsg });
      announceMessage(errorMsg, true);
    }
  }

  /**
   * Configure Deep Chat element with standard settings
   */
  function configureDeepChat(element, isPopup = false) {
    // Detect and set page language for screen readers
    const lang = document.documentElement.lang || 'en';
    if (element.setAttribute) {
      element.setAttribute('lang', lang);
    }

    element.introMessage = {
      html: settings.introMessage || '<p><strong>Hi!</strong> I can help you explore this website, including services, work, and team information.</p><p>Ask me a question like "What services do you offer?"</p>'
    };

    element.textInput = {
      placeholder: {
        text: settings.inputPlaceholder || "Ask a question..."
      },
      styles: {
        fontFamily: 'Montserrat, sans-serif'
      },
      characterLimit: undefined
    };

    element.messageStyles = {
      default: {
        shared: {
          bubble: {
            maxWidth: '100%',
            overflowWrap: 'break-word',
            fontFamily: 'Montserrat, sans-serif'
          }
        },
        user: {
          bubble: {
            backgroundColor: '#153e35'
          }
        },
        ai: {
          bubble: {
            color: '#000000'
          }
        }
      }
    };

    element.htmlClassUtilities = {
      'strong, b': {
        styles: {
          fontFamily: '"Playfair Display", serif',
          fontWeight: '700',
          lineHeight: '1.5'
        }
      },
      'a': {
        styles: {
          color: '#153e35'
        }
      }
    };

    element.chatStyle = {
      overflowY: 'auto'
    };

    element.connect = {
      url: settings.endpoint,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': settings.nonce,
      },
      handler: handleChatRequest,
    };

    // Add submit button styles for popup
    if (isPopup) {
      element.submitButtonStyles = {
        position: "outside-right"
      };
    }
  }

  /**
   * Create popup container
   */
  function createPopup() {
    const popup = document.createElement('div');
    popup.className = 'wp-ai-chatbot-popup';
    popup.setAttribute('role', 'dialog');
    popup.setAttribute('aria-modal', 'true');
    popup.setAttribute('aria-labelledby', 'wp-ai-chatbot-title');
    popup.innerHTML = `
      <div class="wp-ai-chatbot-popup__header">
        <span class="wp-ai-chatbot-popup__title" id="wp-ai-chatbot-title">AI Chat Assistant</span>
        <button class="wp-ai-chatbot-popup__close" aria-label="Close chat">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" role="img">
            <title>Close icon</title>
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="wp-ai-chatbot-popup__body">
        <div aria-live="polite" aria-atomic="true" class="sr-only" id="wp-ai-chat-announcements"></div>
        <deep-chat style="width: 100%; height: 100%; display: flex; flex-direction: column;"></deep-chat>
      </div>
    `;

    document.body.appendChild(popup);

    // Don't configure Deep Chat here - will be done after lazy loading

    const closeBtn = popup.querySelector('.wp-ai-chatbot-popup__close');
    closeBtn.addEventListener('click', () => {
      closePopup(popup);
    });

    // Implement focus trap
    popup.addEventListener('keydown', (e) => {
      if (e.key === 'Tab' && popup.classList.contains('wp-ai-chatbot-popup--open')) {
        trapFocus(e, popup);
      }
    });

    return popup;
  }

  /**
   * Close popup and restore focus
   */
  function closePopup(popup) {
    popup.classList.remove('wp-ai-chatbot-popup--open');

    // Restore focus to the element that opened the modal
    if (popup.lastFocusedElement) {
      popup.lastFocusedElement.focus();
      popup.lastFocusedElement = null;
    }
  }

  /**
   * Trap focus within the modal
   */
  function trapFocus(event, popup) {
    const focusableElements = popup.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const focusableArray = Array.from(focusableElements);
    const firstFocusable = focusableArray[0];
    const lastFocusable = focusableArray[focusableArray.length - 1];

    if (event.shiftKey) {
      // Shift + Tab
      if (document.activeElement === firstFocusable) {
        event.preventDefault();
        lastFocusable.focus();
      }
    } else {
      // Tab
      if (document.activeElement === lastFocusable) {
        event.preventDefault();
        firstFocusable.focus();
      }
    }
  }

  /**
   * Create floating action button
   */
  function createFloatingButton() {
    const button = document.createElement('button');
    button.className = 'wp-ai-chatbot-fab';
    button.setAttribute('aria-label', 'Open AI Chat');
    button.innerHTML = `
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" role="img">
        <title>Chat icon</title>
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
      </svg>
    `;
    document.body.appendChild(button);
    return button;
  }

  /**
   * Initialize floating button (site-wide)
   */
  function initFloatingButton() {
    const popup = createPopup();
    const fab = createFloatingButton();
    let deepChatInitialized = false;

    fab.addEventListener('click', () => {
      const isOpen = popup.classList.contains('wp-ai-chatbot-popup--open');

      if (!isOpen) {
        // Store the element that opened the modal for focus restoration
        popup.lastFocusedElement = document.activeElement;

        // Lazy load Deep Chat on first open
        if (!deepChatInitialized) {
          loadDeepChat(() => {
            deepChatInitialized = true;
            const deepChat = popup.querySelector('deep-chat');
            if (deepChat) {
              configureDeepChat(deepChat, true);
            }
            popup.classList.add('wp-ai-chatbot-popup--open');

            // Focus management with proper error handling
            if (deepChat && typeof deepChat.focusInput === 'function') {
              setTimeout(() => {
                try {
                  deepChat.focusInput();

                  // Verify focus moved
                  setTimeout(() => {
                    if (!popup.contains(document.activeElement)) {
                      // Fallback: focus the close button
                      const closeBtn = popup.querySelector('.wp-ai-chatbot-popup__close');
                      if (closeBtn) {
                        closeBtn.focus();
                      }
                    }
                  }, 50);
                } catch (error) {
                  console.error('Failed to focus chat input:', error);
                  // Fallback to close button
                  const closeBtn = popup.querySelector('.wp-ai-chatbot-popup__close');
                  if (closeBtn) {
                    closeBtn.focus();
                  }
                }
              }, 100);
            } else {
              // No focusInput method - focus close button as fallback
              const closeBtn = popup.querySelector('.wp-ai-chatbot-popup__close');
              if (closeBtn) {
                closeBtn.focus();
              }
            }
          });
        } else {
          popup.classList.add('wp-ai-chatbot-popup--open');

          const deepChat = popup.querySelector('deep-chat');
          // Focus management with proper error handling
          if (deepChat && typeof deepChat.focusInput === 'function') {
            setTimeout(() => {
              try {
                deepChat.focusInput();

                // Verify focus moved
                setTimeout(() => {
                  if (!popup.contains(document.activeElement)) {
                    // Fallback: focus the close button
                    const closeBtn = popup.querySelector('.wp-ai-chatbot-popup__close');
                    if (closeBtn) {
                      closeBtn.focus();
                    }
                  }
                }, 50);
              } catch (error) {
                console.error('Failed to focus chat input:', error);
                // Fallback to close button
                const closeBtn = popup.querySelector('.wp-ai-chatbot-popup__close');
                if (closeBtn) {
                  closeBtn.focus();
                }
              }
            }, 100);
          } else {
            // No focusInput method - focus close button as fallback
            const closeBtn = popup.querySelector('.wp-ai-chatbot-popup__close');
            if (closeBtn) {
              closeBtn.focus();
            }
          }
        }
      } else {
        closePopup(popup);
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && popup.classList.contains('wp-ai-chatbot-popup--open')) {
        closePopup(popup);
      }
    });
  }

  /**
   * Initialize shortcode chat (inline or popup based on attribute)
   */
  function initShortcodeChat(container) {
    const isPopup = container.dataset.popup === 'true';

    if (isPopup) {
      const button = document.createElement('button');
      button.className = 'wp-ai-chatbot-button';
      button.textContent = container.dataset.buttonText || 'Chat with AI';
      container.appendChild(button);

      const popup = createPopup();
      let deepChatInitialized = false;
      let escapeHandler = null;

      button.addEventListener('click', () => {
        // Store the element that opened the modal for focus restoration
        popup.lastFocusedElement = document.activeElement;

        // Add escape key handler specific to this popup
        if (!escapeHandler) {
          escapeHandler = (e) => {
            if (e.key === 'Escape' && popup.classList.contains('wp-ai-chatbot-popup--open')) {
              closePopup(popup);
              button.focus(); // Return focus to button
            }
          };
          document.addEventListener('keydown', escapeHandler);
        }

        // Lazy load Deep Chat on first open
        if (!deepChatInitialized) {
          loadDeepChat(() => {
            deepChatInitialized = true;
            const deepChat = popup.querySelector('deep-chat');
            if (deepChat) {
              configureDeepChat(deepChat, true);
            }
            popup.classList.add('wp-ai-chatbot-popup--open');

            // Focus management with proper error handling
            if (deepChat && typeof deepChat.focusInput === 'function') {
              setTimeout(() => {
                try {
                  deepChat.focusInput();

                  // Verify focus moved
                  setTimeout(() => {
                    if (!popup.contains(document.activeElement)) {
                      // Fallback: focus the close button
                      const closeBtn = popup.querySelector('.wp-ai-chatbot-popup__close');
                      if (closeBtn) {
                        closeBtn.focus();
                      }
                    }
                  }, 50);
                } catch (error) {
                  console.error('Failed to focus chat input:', error);
                  // Fallback to close button
                  const closeBtn = popup.querySelector('.wp-ai-chatbot-popup__close');
                  if (closeBtn) {
                    closeBtn.focus();
                  }
                }
              }, 100);
            } else {
              // No focusInput method - focus close button as fallback
              const closeBtn = popup.querySelector('.wp-ai-chatbot-popup__close');
              if (closeBtn) {
                closeBtn.focus();
              }
            }
          });
        } else {
          popup.classList.add('wp-ai-chatbot-popup--open');
          const deepChat = popup.querySelector('deep-chat');
          if (deepChat && deepChat.focusInput) {
            setTimeout(() => deepChat.focusInput(), 100);
          }
        }
      });
    } else {
      // Inline chatbot - load Deep Chat immediately
      loadDeepChat(() => {
        const deepChatElement = document.createElement('deep-chat');
        deepChatElement.style.width = '100%';
        deepChatElement.style.height = '500px';

        container.appendChild(deepChatElement);
        configureDeepChat(deepChatElement);
      });
    }
  }

  /**
   * Bootstrap the chatbot
   */
  function bootstrap() {
    if (settings.enableFloatingButton) {
      initFloatingButton();
    }

    document.querySelectorAll('.wp-ai-chatbot').forEach((container) => {
      initShortcodeChat(container);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap);
  } else {
    bootstrap();
  }
})();
