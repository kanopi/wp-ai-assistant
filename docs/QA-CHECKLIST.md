# WP AI Assistant - Comprehensive QA Checklist

**Version:** 1.0
**Date:** January 28, 2026
**Purpose:** Complete quality assurance checklist before production deployment

---

## Overview

This checklist ensures the WP AI Assistant plugin meets all quality, performance, security, and accessibility standards before production deployment. All items must be verified and signed off.

---

## Pre-Deployment Checklist

### Environment Preparation

- [ ] **Development environment** is up to date with latest code
- [ ] **Staging environment** mirrors production configuration
- [ ] **Production environment** backup created and verified
- [ ] **API keys** (OpenAI, Pinecone) are configured in Pantheon Secrets
- [ ] **Environment variables** are set correctly in all environments
- [ ] **Node.js indexer** package is built and deployed
- [ ] **Composer dependencies** are installed with `--no-dev` for production

---

## Functional Testing

### Chatbot Functionality

#### Basic Functionality
- [ ] **Floating button** appears on all pages (if enabled)
- [ ] **Clicking floating button** opens chatbot popup
- [ ] **Popup displays** intro message correctly
- [ ] **Text input** accepts user questions
- [ ] **Submit button** sends questions to API
- [ ] **AI responses** display correctly with formatting
- [ ] **Close button** closes the popup
- [ ] **ESC key** closes the popup
- [ ] **Focus returns** to floating button after closing

#### Shortcode Mode
- [ ] **`[ai_chatbot]`** shortcode renders inline chatbot
- [ ] **`[ai_chatbot popup="true"]`** renders popup trigger button
- [ ] **Button text** can be customized via shortcode attribute
- [ ] **Multiple shortcodes** on same page work independently

#### Error Handling
- [ ] **Invalid API key** shows user-friendly error message
- [ ] **Network timeout** shows retry prompt
- [ ] **Empty question** prevents submission
- [ ] **Rate limit exceeded** shows appropriate message
- [ ] **API unavailable** gracefully degrades

#### Content Quality
- [ ] **Responses are accurate** based on indexed content
- [ ] **Links in responses** are formatted correctly and clickable
- [ ] **Bold text** (strong tags) renders correctly
- [ ] **Lists** (ul/ol) render correctly
- [ ] **Code blocks** (if any) render correctly
- [ ] **Citations/sources** are included when appropriate

### Search Functionality

#### Basic Functionality
- [ ] **`[ai_search]`** shortcode renders search form
- [ ] **Placeholder text** displays correctly
- [ ] **Search input** accepts queries
- [ ] **Submit button** triggers search
- [ ] **Loading state** displays during search
- [ ] **Results display** with title, excerpt, and relevance score
- [ ] **No results** message displays when appropriate
- [ ] **Error messages** are user-friendly

#### Results Quality
- [ ] **Results are relevant** to search query
- [ ] **Relevance scores** make sense (highly relevant first)
- [ ] **Excerpts** contain query terms when possible
- [ ] **Links** navigate to correct pages
- [ ] **Result count** is accurate
- [ ] **Results pagination** works (if implemented)

#### Search Replacement (if enabled)
- [ ] **Default WordPress search** is replaced with AI search
- [ ] **Search results page** displays AI results
- [ ] **AI summary** displays at top of results (if enabled)
- [ ] **Fall back** to standard search if AI search fails

### Admin Panel

#### Settings Page
- [ ] **Settings page** accessible at "AI Assistant" menu
- [ ] **General tab** displays and saves correctly
- [ ] **Chatbot tab** displays and saves correctly
- [ ] **Search tab** displays and saves correctly
- [ ] **Indexer tab** displays and saves correctly
- [ ] **Settings validate** input before saving
- [ ] **Error messages** display for invalid settings
- [ ] **Success messages** display after saving

#### Chat Logs
- [ ] **Chat logs** post type is accessible
- [ ] **Logs display** user questions and AI responses
- [ ] **Logs include** metadata (timestamp, IP, user ID if logged in)
- [ ] **Logs can be filtered** by date or user
- [ ] **Logs can be deleted** individually or in bulk

#### Search Logs
- [ ] **Search logs** post type is accessible
- [ ] **Logs display** search queries and result counts
- [ ] **Logs include** metadata (timestamp, IP, results count)
- [ ] **Logs can be filtered** by date
- [ ] **Logs can be deleted** individually or in bulk

### Indexer

#### WP-CLI Command
- [ ] **`wp ai-indexer run`** executes without errors
- [ ] **Progress output** displays during indexing
- [ ] **Success message** displays when complete
- [ ] **Error messages** are helpful for debugging
- [ ] **Dry run** mode works (`--dry-run` flag)
- [ ] **Specific post types** can be targeted

#### Indexing Process
- [ ] **All published posts** are indexed
- [ ] **All published pages** are indexed
- [ ] **Custom post types** are indexed (if configured)
- [ ] **Draft posts** are skipped
- [ ] **Trashed posts** are skipped
- [ ] **Password-protected posts** are handled correctly
- [ ] **Post updates** trigger reindexing (if configured)
- [ ] **Post deletions** remove from index (if configured)

#### Content Chunking
- [ ] **Long content** is split into chunks correctly
- [ ] **Chunk size** respects token limits
- [ ] **Chunk overlap** preserves context
- [ ] **Headings** are preserved in metadata
- [ ] **Images** are handled appropriately

#### Vector Storage
- [ ] **Embeddings** are created successfully
- [ ] **Vectors** are stored in Pinecone
- [ ] **Metadata** includes all required fields (post_id, title, url, domain)
- [ ] **Domain filter** is set correctly
- [ ] **Duplicate vectors** are handled appropriately

---

## Performance Testing

### Page Load Performance

#### Homepage (without chatbot)
- [ ] **Time to First Byte (TTFB):** < 200ms
- [ ] **First Contentful Paint (FCP):** < 1.8s
- [ ] **Largest Contentful Paint (LCP):** < 2.5s
- [ ] **Total Blocking Time (TBT):** < 200ms
- [ ] **Cumulative Layout Shift (CLS):** < 0.1

#### Page with Chatbot Floating Button
- [ ] **Additional load time:** < 100ms
- [ ] **JavaScript bundle size:** < 50KB (compressed)
- [ ] **CSS bundle size:** < 10KB (compressed)
- [ ] **No layout shift** when button loads

#### Page with Search Shortcode
- [ ] **Additional load time:** < 100ms
- [ ] **JavaScript bundle size:** < 30KB (compressed)
- [ ] **CSS bundle size:** < 15KB (compressed)
- [ ] **Form renders** immediately

### Chatbot Performance

#### First Interaction
- [ ] **Popup opens** in < 200ms
- [ ] **Deep Chat loads** in < 1s
- [ ] **Intro message displays** in < 500ms
- [ ] **Input is focusable** immediately

#### Response Time
- [ ] **Average response time:** 2-4 seconds
- [ ] **P95 response time:** < 6 seconds
- [ ] **P99 response time:** < 10 seconds
- [ ] **Timeout occurs** after 30 seconds

#### Concurrent Users
- [ ] **10 concurrent users:** No degradation
- [ ] **50 concurrent users:** Acceptable performance (< 8s responses)
- [ ] **100 concurrent users:** Graceful degradation
- [ ] **Rate limiting** prevents abuse

### Search Performance

#### Search Execution
- [ ] **Average search time:** 1-3 seconds
- [ ] **P95 search time:** < 5 seconds
- [ ] **P99 search time:** < 8 seconds
- [ ] **Results render** immediately after API response

#### Concurrent Searches
- [ ] **10 concurrent searches:** No degradation
- [ ] **50 concurrent searches:** Acceptable performance
- [ ] **100 concurrent searches:** Graceful degradation

### API Performance

#### OpenAI API
- [ ] **Embedding creation:** < 1 second
- [ ] **Chat completion:** 2-4 seconds
- [ ] **Error rate:** < 1%
- [ ] **Rate limit:** Not exceeded
- [ ] **Retry logic:** Works correctly

#### Pinecone API
- [ ] **Vector query:** < 500ms
- [ ] **Vector upsert:** < 200ms per vector
- [ ] **Batch operations:** Work efficiently
- [ ] **Error rate:** < 0.5%
- [ ] **Rate limit:** Not exceeded

### Caching Performance

#### Cache Hit Rates
- [ ] **Embedding cache hit rate:** > 50%
- [ ] **Query cache hit rate:** > 70%
- [ ] **Settings cache hit rate:** > 90%
- [ ] **Cache invalidation:** Works correctly

#### Cache Storage
- [ ] **Redis connection:** Stable
- [ ] **Memory usage:** < 500MB
- [ ] **Eviction rate:** < 10%
- [ ] **TTL settings:** Appropriate

---

## Security Testing

### Authentication & Authorization

- [ ] **REST API endpoints** require nonce verification
- [ ] **Admin settings** require `manage_options` capability
- [ ] **Chat logs** only accessible to administrators
- [ ] **Search logs** only accessible to administrators
- [ ] **WP-CLI commands** require appropriate permissions

### Input Validation

- [ ] **Search queries** sanitized correctly
- [ ] **Chat questions** sanitized correctly
- [ ] **Settings inputs** validated before saving
- [ ] **SQL injection** prevented in all queries
- [ ] **XSS attacks** prevented in all outputs
- [ ] **CSRF attacks** prevented with nonces

### API Security

- [ ] **API keys** stored in environment variables (not database)
- [ ] **API keys** never exposed in frontend code
- [ ] **API keys** never logged in error messages
- [ ] **API requests** use HTTPS only
- [ ] **API responses** sanitized before display

### Rate Limiting

- [ ] **Chatbot rate limit:** 10 requests per minute per IP
- [ ] **Search rate limit:** 10 requests per minute per IP
- [ ] **Rate limit bypass** prevented (X-Forwarded-For validation)
- [ ] **Rate limit messages** are user-friendly

### Content Security Policy (CSP)

- [ ] **CSP headers** are present
- [ ] **Inline scripts** use nonces
- [ ] **External scripts** (Deep Chat CDN) are allowed
- [ ] **No CSP violations** in browser console
- [ ] **CSP reports** are monitored (if configured)

### Data Privacy

- [ ] **Chat logs** can be disabled via setting
- [ ] **Search logs** can be disabled via setting
- [ ] **IP addresses** are stored securely (if at all)
- [ ] **User data** is anonymized appropriately
- [ ] **GDPR compliance** verified (if applicable)
- [ ] **Privacy policy** updated with AI features

---

## Accessibility Testing (WCAG 2.1 Level AA)

### Keyboard Navigation

- [ ] **All interactive elements** are keyboard accessible
- [ ] **Tab order** is logical
- [ ] **Enter key** activates buttons
- [ ] **Space key** activates buttons
- [ ] **Escape key** closes modals
- [ ] **Focus indicators** are visible (3px outline)
- [ ] **Focus trap** works in chatbot popup
- [ ] **Focus restoration** works after closing popup

### Screen Reader Compatibility

#### NVDA (Windows)
- [ ] **Floating button** announced correctly
- [ ] **Popup** announced as dialog
- [ ] **Messages** announced as they arrive
- [ ] **Form labels** announced correctly
- [ ] **Error messages** announced
- [ ] **Loading states** announced

#### JAWS (Windows)
- [ ] **All NVDA tests** also pass with JAWS

#### VoiceOver (macOS/iOS)
- [ ] **All NVDA tests** also pass with VoiceOver

### Visual Accessibility

#### Color Contrast
- [ ] **Normal text:** Minimum 4.5:1 contrast ratio
- [ ] **Large text:** Minimum 3:1 contrast ratio
- [ ] **UI components:** Minimum 3:1 contrast ratio
- [ ] **Focus indicators:** Visible against all backgrounds

#### Reduced Motion
- [ ] **`prefers-reduced-motion`** respected
- [ ] **Animations** disabled or reduced
- [ ] **Transitions** are instant or very fast (0.01ms)

#### Zoom and Scaling
- [ ] **200% zoom:** Content remains usable
- [ ] **400% zoom:** Content remains readable (reflow OK)
- [ ] **No horizontal scrolling** at 200% zoom
- [ ] **Touch targets:** Minimum 44×44px on mobile

### Semantic HTML

- [ ] **Headings** follow logical hierarchy (h1 → h2 → h3)
- [ ] **Landmarks** used correctly (search, navigation, main)
- [ ] **Lists** used for groups of items
- [ ] **Buttons** use `<button>` tags (not divs)
- [ ] **Links** use `<a>` tags with href
- [ ] **Forms** have associated labels

### ARIA Attributes

- [ ] **`aria-label`** used on icon-only buttons
- [ ] **`aria-live`** used for dynamic content
- [ ] **`aria-describedby`** used for error messages
- [ ] **`aria-required`** used on required fields
- [ ] **`aria-invalid`** used on invalid inputs
- [ ] **`aria-modal="true"`** used on popup dialogs
- [ ] **`role="dialog"`** used on popup dialogs
- [ ] **`role="alert"`** used on error messages

---

## Browser & Device Testing

### Desktop Browsers

#### Chrome (Latest)
- [ ] **All functionality** works correctly
- [ ] **Performance** is acceptable
- [ ] **No console errors**
- [ ] **Responsive design** works at all sizes

#### Firefox (Latest)
- [ ] **All Chrome tests** also pass in Firefox

#### Safari (Latest)
- [ ] **All Chrome tests** also pass in Safari

#### Edge (Latest)
- [ ] **All Chrome tests** also pass in Edge

### Mobile Browsers

#### iOS Safari (Latest)
- [ ] **Floating button** is accessible and usable
- [ ] **Chatbot popup** displays full-screen on mobile
- [ ] **Keyboard** opens when input focused
- [ ] **Scrolling** works smoothly
- [ ] **Touch targets** are appropriately sized
- [ ] **No layout issues**

#### Android Chrome (Latest)
- [ ] **All iOS Safari tests** also pass on Android Chrome

#### Responsive Breakpoints
- [ ] **320px (Mobile S):** Usable
- [ ] **375px (Mobile M):** Good
- [ ] **425px (Mobile L):** Good
- [ ] **768px (Tablet):** Good
- [ ] **1024px (Laptop):** Good
- [ ] **1440px (Desktop):** Good

---

## Load Testing

### Test Scenarios

#### Scenario 1: Normal Load
- **Users:** 50 concurrent
- **Duration:** 30 minutes
- **Mix:** 70% chatbot, 30% search
- **Target:** < 5s average response time
- **Result:** [ ] PASS [ ] FAIL

#### Scenario 2: Peak Load
- **Users:** 100 concurrent
- **Duration:** 15 minutes
- **Mix:** 70% chatbot, 30% search
- **Target:** < 8s average response time, < 1% error rate
- **Result:** [ ] PASS [ ] FAIL

#### Scenario 3: Spike Load
- **Users:** 0 → 200 → 0 over 10 minutes
- **Duration:** 10 minutes
- **Mix:** 50% chatbot, 50% search
- **Target:** Graceful degradation, no crashes
- **Result:** [ ] PASS [ ] FAIL

#### Scenario 4: Sustained Load
- **Users:** 30 concurrent
- **Duration:** 2 hours
- **Mix:** 60% chatbot, 40% search
- **Target:** Stable performance, no memory leaks
- **Result:** [ ] PASS [ ] FAIL

### Load Testing Tools

- [ ] **k6** or **JMeter** configured
- [ ] **Test scripts** written and validated
- [ ] **Monitoring** active during tests (New Relic, Datadog)
- [ ] **Logs** reviewed after tests
- [ ] **Errors** analyzed and documented

### Metrics to Monitor

- [ ] **Response time** (average, P95, P99)
- [ ] **Error rate** (< 1%)
- [ ] **Throughput** (requests per second)
- [ ] **CPU usage** (< 80%)
- [ ] **Memory usage** (stable, no leaks)
- [ ] **Database queries** (< 50 per request)
- [ ] **API calls** (within rate limits)
- [ ] **Cache hit rate** (> 70%)

---

## Integration Testing

### WordPress Integration

- [ ] **Plugin activation** succeeds without errors
- [ ] **Plugin deactivation** cleans up correctly
- [ ] **Plugin updates** preserve settings
- [ ] **Multisite compatibility** verified (if applicable)
- [ ] **Theme compatibility** verified with default themes
- [ ] **No conflicts** with other popular plugins

### External Services

#### OpenAI Integration
- [ ] **API key** validates successfully
- [ ] **Embeddings** created correctly
- [ ] **Chat completions** work reliably
- [ ] **Error handling** graceful on API failures
- [ ] **Retry logic** works correctly

#### Pinecone Integration
- [ ] **API key** validates successfully
- [ ] **Index** configured correctly
- [ ] **Vectors** stored successfully
- [ ] **Queries** return relevant results
- [ ] **Error handling** graceful on API failures

#### Deep Chat CDN
- [ ] **Library loads** from CDN
- [ ] **Fallback** works if CDN unavailable
- [ ] **SRI hash** validates correctly (if used)
- [ ] **Version** is stable and production-ready

---

## Monitoring & Logging

### Application Monitoring

- [ ] **Error tracking** (Sentry) is configured
- [ ] **Errors** are captured and reported
- [ ] **Source maps** work for JavaScript errors
- [ ] **Stack traces** are complete and useful

### Performance Monitoring

- [ ] **APM** (New Relic, Datadog) is configured
- [ ] **Response times** are tracked
- [ ] **Slow queries** are identified
- [ ] **API latency** is monitored
- [ ] **Dashboard** shows key metrics

### Log Management

- [ ] **Application logs** are accessible
- [ ] **Chat logs** are storing correctly
- [ ] **Search logs** are storing correctly
- [ ] **Indexer logs** are accessible
- [ ] **Log rotation** is configured
- [ ] **Log retention** policy is defined

### Alerts

- [ ] **High error rate** alert configured
- [ ] **Slow response time** alert configured
- [ ] **API failures** alert configured
- [ ] **Rate limit** threshold alert configured
- [ ] **Disk space** alert configured

---

## Documentation Review

- [ ] **README.md** is up to date
- [ ] **USER-GUIDE.md** is accurate
- [ ] **API.md** documents all endpoints
- [ ] **ARCHITECTURE.md** reflects current design
- [ ] **CONFIGURATION.md** is complete
- [ ] **TROUBLESHOOTING.md** has common issues
- [ ] **DEPLOYMENT.md** has current procedures
- [ ] **ACCESSIBILITY.md** is accurate
- [ ] **CONTRIBUTING.md** is clear
- [ ] **CHANGELOG.md** is updated

---

## Stakeholder Sign-Off

### Technical Review

- [ ] **Lead Developer:** _______________________ Date: __________
- [ ] **DevOps Engineer:** _______________________ Date: __________
- [ ] **Security Engineer:** _______________________ Date: __________
- [ ] **QA Engineer:** _______________________ Date: __________

### Business Review

- [ ] **Product Owner:** _______________________ Date: __________
- [ ] **Project Manager:** _______________________ Date: __________
- [ ] **Accessibility Lead:** _______________________ Date: __________

### Final Approval

- [ ] **CTO/Technical Director:** _______________________ Date: __________

---

## Post-Deployment Verification

### Immediate (Within 1 Hour)

- [ ] **Health checks** pass
- [ ] **Homepage** loads correctly
- [ ] **Chatbot** works on production
- [ ] **Search** works on production
- [ ] **No critical errors** in logs
- [ ] **Monitoring** shows green status

### Short-term (Within 24 Hours)

- [ ] **Response times** stable
- [ ] **Error rate** < 1%
- [ ] **Cache hit rate** > 60%
- [ ] **No user complaints**
- [ ] **Analytics** show expected usage

### Medium-term (Within 1 Week)

- [ ] **Performance metrics** meet targets
- [ ] **User feedback** is positive
- [ ] **Support tickets** are manageable
- [ ] **Costs** are within budget
- [ ] **No regressions** detected

---

## Rollback Plan

### Rollback Triggers

- [ ] **Critical security vulnerability** discovered
- [ ] **Data integrity** issues
- [ ] **Performance degradation** > 50%
- [ ] **Error rate** > 5%
- [ ] **Service outage** > 15 minutes

### Rollback Procedure

1. [ ] Initiate rollback via CircleCI or manual script
2. [ ] Notify stakeholders via Slack
3. [ ] Execute automated rollback script
4. [ ] Verify previous deployment restored
5. [ ] Run health checks
6. [ ] Confirm site functionality
7. [ ] Document incident
8. [ ] Schedule post-mortem

---

## QA Test Results Summary

| Category | Tests | Passed | Failed | Notes |
|----------|-------|--------|--------|-------|
| Functional | | | | |
| Performance | | | | |
| Security | | | | |
| Accessibility | | | | |
| Browser/Device | | | | |
| Load Testing | | | | |
| Integration | | | | |
| Monitoring | | | | |
| **TOTAL** | | | | |

**QA Completion Date:** ____________________

**QA Lead Signature:** ____________________

**Production Deployment Approved:** [ ] YES [ ] NO

**Approval Date:** ____________________

---

## Notes & Issues

**Critical Issues (Must Fix Before Deploy):**
1.
2.
3.

**Minor Issues (Can Fix Post-Deploy):**
1.
2.
3.

**Future Enhancements:**
1.
2.
3.

---

## Version History

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| 1.0 | 2026-01-28 | Initial checklist created | QA Team |

