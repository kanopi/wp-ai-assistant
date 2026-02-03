# Accessibility Testing Guide

Complete guide for developers to test accessibility of the WP AI Assistant plugin.

## Table of Contents

- [Testing Tools](#testing-tools)
- [Automated Testing](#automated-testing)
- [Keyboard Navigation Testing](#keyboard-navigation-testing)
- [Screen Reader Testing](#screen-reader-testing)
- [Color Contrast Testing](#color-contrast-testing)
- [Testing Checklist](#testing-checklist)
- [Continuous Integration](#continuous-integration)

## Testing Tools

### Required Tools

#### Automated Testing Tools

**axe DevTools** (Recommended)
- Browser extension for Chrome, Firefox, Edge
- Download: [deque.com/axe/devtools](https://www.deque.com/axe/devtools/)
- Free version sufficient for most testing

**WAVE Browser Extension**
- Visual feedback on accessibility issues
- Download: [wave.webaim.org/extension](https://wave.webaim.org/extension/)

**Lighthouse** (Built into Chrome DevTools)
- Included in Chrome browser
- Open DevTools → Lighthouse → Accessibility audit

#### Screen Readers

**NVDA** (Windows - Free)
- Download: [nvaccess.org](https://www.nvaccess.org/)
- Most popular free screen reader
- Recommended for primary testing

**JAWS** (Windows - Commercial)
- Download: [freedomscientific.com](https://www.freedomscientific.com/products/software/jaws/)
- 40-minute demo mode available
- Industry standard for Windows

**VoiceOver** (macOS/iOS - Built-in)
- Included with macOS and iOS
- Enable in System Settings → Accessibility → VoiceOver
- Keyboard shortcut: Cmd + F5

#### Color Contrast Tools

**WebAIM Contrast Checker**
- Online tool: [webaim.org/resources/contrastchecker](https://webaim.org/resources/contrastchecker/)
- Checks WCAG AA and AAA compliance

**Colour Contrast Analyser (CCA)**
- Desktop application: [tpgi.com/color-contrast-checker](https://www.tpgi.com/color-contrast-checker/)
- Eyedropper tool for checking colors on screen

**Chrome DevTools Contrast Checker**
- Built into Chrome DevTools
- Inspect element → Styles panel → Color picker shows contrast ratio

### Optional Tools

- **Firefox Accessibility Inspector** - Built into Firefox DevTools
- **Pa11y** - Command-line accessibility testing tool
- **Accessible Name Inspector** - Chrome extension for ARIA name checking

## Automated Testing

Automated testing catches approximately 30-40% of accessibility issues. Manual testing is still required.

### Using axe DevTools

#### 1. Install Extension

Install from [Chrome Web Store](https://chrome.google.com/webstore) or [Firefox Add-ons](https://addons.mozilla.org/).

#### 2. Run Scan

Navigate to page with plugin active:
- Open DevTools (F12 or Cmd/Ctrl + Shift + I)
- Click "axe DevTools" tab
- Click "Scan ALL of my page"
- Review results

#### 3. Test Specific Components

**Chatbot:**
1. Open chatbot popup
2. Run axe scan
3. Review issues in popup context

**Search:**
1. Navigate to search page
2. Perform a search
3. Run axe scan on results

#### 4. Interpret Results

axe categorizes issues by impact:

- **Critical** - Must fix (blocks users)
- **Serious** - Should fix (major barriers)
- **Moderate** - Should fix (inconvenience)
- **Minor** - Nice to fix (best practices)

### Using WAVE Extension

#### 1. Install Extension

Install from [WAVE website](https://wave.webaim.org/extension/).

#### 2. Run Analysis

- Navigate to page with plugin
- Click WAVE extension icon
- Review visual indicators on page

#### 3. Understand Icons

- **Red (Errors)** - Accessibility failures that must be fixed
- **Yellow (Alerts)** - Potential issues requiring review
- **Green (Features)** - Accessibility features present
- **Blue (Structural)** - Structural elements (headings, landmarks)

### Using Lighthouse

#### 1. Open Chrome DevTools

- Press F12 or Cmd/Ctrl + Shift + I
- Navigate to "Lighthouse" tab

#### 2. Configure Audit

- Device: Desktop or Mobile
- Categories: Check "Accessibility"
- Click "Analyze page load"

#### 3. Review Score

- Target: 90+ score
- Review failed audits
- Click "Learn more" for remediation guidance

## Keyboard Navigation Testing

Test all interactive elements with keyboard only (no mouse).

### Setup

1. Disconnect or disable your mouse/trackpad
2. Use only keyboard for all interactions
3. Make note of any barriers

### Testing Procedure

#### General Navigation

Test the following on pages where the plugin is active:

1. **Tab Through Page**
   - Press Tab repeatedly
   - Verify focus indicator is visible at all times
   - Verify logical tab order (top-to-bottom, left-to-right)
   - Check that focus is never trapped unintentionally

2. **Reverse Navigation**
   - Press Shift + Tab
   - Verify backward navigation works correctly
   - Verify focus returns to expected elements

#### Chatbot Floating Button

Test keyboard access to chatbot:

1. **Focus the Button**
   - Tab to chatbot floating action button
   - Verify visible focus indicator (3px outline)

2. **Open Popup**
   - Press Enter or Space
   - Verify popup opens
   - Verify focus moves to popup (close button or chat input)

3. **Navigate Popup**
   - Tab through popup elements
   - Verify focus stays within popup (focus trap)
   - Tab order: Close button → Chat input → Send button → Close button (circular)

4. **Close Popup**
   - Press Escape key
   - Verify popup closes
   - Verify focus returns to floating button

5. **Alternative Close**
   - Open popup again
   - Tab to close button
   - Press Enter
   - Verify popup closes and focus returns

#### Chatbot Shortcode

Test shortcode button and popup:

1. **Activate Button**
   - Tab to shortcode button
   - Press Enter or Space
   - Verify popup opens

2. **Navigate and Close**
   - Same as floating button tests
   - Verify Escape key closes popup
   - Verify focus returns to shortcode button

#### Search Form

Test search functionality:

1. **Focus Input Field**
   - Tab to search input
   - Verify visible focus indicator
   - Type search query

2. **Submit Search**
   - Press Enter (or Tab to button and press Enter)
   - Verify results appear

3. **Navigate Results**
   - Tab through search results
   - Verify each result link is focusable
   - Verify focus indicators are visible
   - Verify logical tab order

4. **Activate Result**
   - Press Enter on focused result link
   - Verify navigation to result page

### Testing Checklist

- [ ] All interactive elements are keyboard accessible
- [ ] Focus indicators are visible (3px outline minimum)
- [ ] Tab order is logical and predictable
- [ ] Focus trap works correctly in popups
- [ ] Escape key closes popups
- [ ] Focus returns to trigger element after closing popup
- [ ] No keyboard traps (can always escape)
- [ ] No focus on non-interactive elements

### Common Issues to Watch For

- **Invisible focus** - Focus indicator blends into background
- **Illogical tab order** - Focus jumps around unexpectedly
- **Keyboard traps** - Cannot escape from component
- **Missing keyboard handlers** - Button only responds to click, not Enter
- **Focus loss** - Focus disappears after interaction

## Screen Reader Testing

Test with NVDA (Windows), JAWS (Windows), or VoiceOver (macOS).

### NVDA Setup (Windows)

#### Installation

1. Download from [nvaccess.org](https://www.nvaccess.org/)
2. Install with default settings
3. NVDA starts automatically after installation

#### Basic Commands

| Action | Keys |
|--------|------|
| Toggle NVDA on/off | Ctrl + Alt + N |
| Stop speaking | Ctrl |
| Read next item | Down Arrow |
| Read previous item | Up Arrow |
| Read all | Insert + Down Arrow |
| Click element | Enter |
| Navigate headings | H (next) / Shift + H (previous) |
| Navigate links | K (next) / Shift + K (previous) |
| Navigate buttons | B (next) / Shift + B (previous) |
| Navigate form fields | F (next) / Shift + F (previous) |
| Navigate landmarks | D (next) / Shift + D (previous) |
| Open element list | Insert + F7 |

### VoiceOver Setup (macOS)

#### Enable VoiceOver

1. System Settings → Accessibility → VoiceOver
2. Toggle on (or press Cmd + F5)
3. Complete quick start tutorial

#### Basic Commands

| Action | Keys |
|--------|------|
| Toggle VoiceOver | Cmd + F5 |
| VoiceOver modifier | Ctrl + Option (VO) |
| Read next item | VO + Right Arrow |
| Read previous item | VO + Left Arrow |
| Click element | VO + Space |
| Navigate headings | VO + Cmd + H |
| Navigate links | VO + Cmd + L |
| Navigate form controls | VO + Cmd + J |
| Navigate landmarks | VO + U → Left/Right Arrow |
| Open rotor | VO + U |

### Testing Procedure

#### Chatbot Testing

**1. Discover Chatbot Button**

- Navigate to page with chatbot enabled
- Browse page with screen reader
- Verify button is announced: "Chat button" or "AI Chat Assistant, button"
- Verify button state is announced

**2. Open Chatbot**

- Activate button (Enter or VO + Space)
- Verify popup opening is announced
- Verify focus moves to popup
- Verify popup title is announced: "AI Chat Assistant, dialog"

**3. Navigate Popup**

- Use screen reader navigation to explore popup
- Verify all elements are discoverable
- Verify close button is announced: "Close chat, button"
- Verify chat input is announced: "Message, edit text" or similar

**4. Send Message**

- Type message in chat input
- Activate send button
- Verify loading state is announced: "Searching..." or similar
- Verify response is announced when received

**5. Close Popup**

- Activate close button
- Verify popup closing is announced
- Verify focus returns to chatbot button

#### Search Testing

**1. Discover Search Form**

- Navigate to search page
- Verify search landmark is announced: "Search, region"
- Verify form is announced: "Search, form"
- Verify input label is announced
- Verify required state is announced: "required"

**2. Perform Search**

- Enter query in search input
- Activate search button
- Verify loading state is announced: "Searching..."
- Verify results count is announced: "5 results" or similar

**3. Navigate Results**

- Use heading navigation (H key in NVDA)
- Verify heading hierarchy is logical
- Verify each result is a heading (H2 or H3)
- Navigate to result links
- Verify link text is descriptive

**4. Error Handling**

- Submit empty search (if validation present)
- Verify error is announced: "Search field is required" or similar
- Verify input is marked invalid
- Verify aria-describedby links error to input

**5. No Results**

- Search for nonsense query
- Verify "No results" message is announced

### Testing Checklist

- [ ] All content is accessible via screen reader
- [ ] All interactive elements are announced with role and name
- [ ] Form fields have descriptive labels
- [ ] Required fields are announced as required
- [ ] Error messages are announced and associated with inputs
- [ ] Loading states are announced (aria-live regions work)
- [ ] Dynamic content updates are announced
- [ ] Buttons have descriptive labels
- [ ] Links have descriptive text (not "click here")
- [ ] Images have alt text (if present)
- [ ] Headings provide logical structure
- [ ] Landmarks help navigation (search, main, etc.)

### Common Issues to Watch For

- **Silent elements** - Element not announced at all
- **Wrong role** - "Link" announced but it's a button
- **Generic labels** - "Button" with no descriptive name
- **Missing labels** - Form input with no label
- **Unannounced updates** - Dynamic content changes silently
- **Confusing structure** - Illogical heading hierarchy
- **Verbose announcements** - Too much information announced

## Color Contrast Testing

Ensure all text and interactive elements meet WCAG AA contrast requirements.

### Contrast Requirements

**WCAG AA Standards:**

- **Normal text** (< 18pt or < 14pt bold): Minimum 4.5:1
- **Large text** (≥ 18pt or ≥ 14pt bold): Minimum 3:1
- **Interactive elements** (buttons, links, icons): Minimum 3:1 against adjacent colors
- **Focus indicators**: Minimum 3:1 against background

### Using WebAIM Contrast Checker

#### 1. Open Tool

Navigate to [webaim.org/resources/contrastchecker](https://webaim.org/resources/contrastchecker/)

#### 2. Get Colors

**Using Browser DevTools:**
- Right-click element → Inspect
- In Styles panel, click color swatch
- Copy hex code

**Using Colour Contrast Analyser:**
- Use eyedropper tool to sample colors from screen

#### 3. Check Contrast

- Enter foreground color (text color)
- Enter background color
- Review results:
  - ✅ Green checkmarks = Passes
  - ❌ Red X = Fails

#### 4. Test Plugin Elements

**Primary Button:**
- Foreground: #ffffff (white)
- Background: #ff7d55 (orange)
- Result: 3.5:1 (Passes for large text, fails for normal)

**Primary Text/Links:**
- Foreground: #153e35 (dark green)
- Background: #ffffff (white)
- Result: 12.23:1 (Passes AA and AAA)

**Focus Indicators:**
- Foreground: #0073aa (blue)
- Background: #ffffff (white)
- Result: 4.5:1 (Passes AA)

### Using Chrome DevTools

#### 1. Inspect Element

- Right-click element → Inspect
- Styles panel opens

#### 2. Click Color Swatch

- Click color swatch next to color value
- Color picker opens

#### 3. Check Contrast Ratio

- Contrast ratio shown at top
- AA/AAA indicators shown
- Suggested colors shown if fails

### Testing Procedure

#### 1. Test All Text

Check contrast for:

- [ ] Body text
- [ ] Heading text
- [ ] Link text (all states: default, hover, focus, visited)
- [ ] Button text
- [ ] Form labels
- [ ] Error messages
- [ ] Placeholder text (avoid using as labels)
- [ ] Success/warning/info messages

#### 2. Test Interactive Elements

Check contrast for:

- [ ] Button backgrounds (all states)
- [ ] Link underlines/indicators
- [ ] Focus indicators (outlines)
- [ ] Active/pressed states
- [ ] Disabled states (should be 3:1 minimum or removed from tab order)

#### 3. Test Plugin-Specific Elements

**Chatbot:**
- [ ] Floating button (default and hover)
- [ ] Popup header background
- [ ] Popup header text
- [ ] Close button
- [ ] Chat messages (user and AI)
- [ ] Links in chat messages
- [ ] Input field text

**Search:**
- [ ] Search button (default and hover)
- [ ] Result titles
- [ ] Result excerpts
- [ ] Result scores
- [ ] Loading message
- [ ] Error messages
- [ ] No results message

### Common Issues to Watch For

- **Insufficient hover contrast** - Color changes on hover don't meet 3:1
- **Poor focus indicators** - Outline doesn't contrast with background
- **Gray text** - Light gray text often fails (avoid #999 and lighter)
- **Placeholder-as-label** - Placeholder text is too light (disabled by default)
- **Disabled elements** - Should be 3:1 or hidden from assistive tech

## Testing Checklist

Use this comprehensive checklist for testing accessibility of new features.

### Setup

- [ ] Install axe DevTools or WAVE extension
- [ ] Install screen reader (NVDA, JAWS, or enable VoiceOver)
- [ ] Install Colour Contrast Analyser or bookmark WebAIM checker
- [ ] Disconnect mouse (for keyboard testing)

### Automated Testing

- [ ] Run axe DevTools scan on all pages with plugin
- [ ] Run WAVE analysis on all pages with plugin
- [ ] Run Lighthouse accessibility audit
- [ ] Review and document all issues
- [ ] Prioritize issues (Critical → Minor)

### Keyboard Navigation

- [ ] All interactive elements are keyboard accessible (Tab/Shift+Tab)
- [ ] Focus indicators are visible on all elements
- [ ] Tab order is logical
- [ ] Escape closes popups
- [ ] Focus trap works in modals
- [ ] Focus returns to trigger element after closing
- [ ] No keyboard traps
- [ ] Enter/Space activate buttons

### Screen Reader Testing

- [ ] All content is accessible
- [ ] Interactive elements have descriptive names
- [ ] Form fields have labels
- [ ] Required fields are announced
- [ ] Errors are announced and associated
- [ ] Loading states are announced
- [ ] Dynamic updates are announced
- [ ] Headings are logical (H1 → H2 → H3)
- [ ] Landmarks help navigation

### Color Contrast

- [ ] All text meets 4.5:1 (or 3:1 for large text)
- [ ] Links meet 3:1 against surrounding text
- [ ] Focus indicators meet 3:1 against background
- [ ] Hover states meet contrast requirements
- [ ] Buttons meet contrast requirements (all states)

### Chatbot-Specific

- [ ] Floating button is keyboard accessible
- [ ] Floating button has visible focus indicator
- [ ] Popup opens on Enter/Space
- [ ] Focus moves to popup on open
- [ ] Close button is keyboard accessible
- [ ] Escape closes popup
- [ ] Focus returns to button on close
- [ ] Chat input is labeled
- [ ] Messages are announced
- [ ] Loading states are announced
- [ ] Errors are announced

### Search-Specific

- [ ] Search form has search landmark
- [ ] Input has visible label
- [ ] Required state is indicated
- [ ] Search button is keyboard accessible
- [ ] Loading state is announced
- [ ] Results are announced (count)
- [ ] Result headings are logical
- [ ] Result links are descriptive
- [ ] Errors are announced and associated
- [ ] No results message is clear

### Mobile Testing (Optional)

- [ ] Touch targets are minimum 44x44px
- [ ] Pinch-to-zoom is enabled
- [ ] Text reflows at 200% zoom
- [ ] Content adapts to orientation changes
- [ ] VoiceOver/TalkBack work correctly

### Reduced Motion

- [ ] Animations respect prefers-reduced-motion
- [ ] Transitions are instant or near-instant
- [ ] Loading states don't rely on animation
- [ ] Transforms are disabled

## Continuous Integration

Integrate accessibility testing into your CI/CD pipeline.

### Using Pa11y

#### Install Pa11y

```bash
npm install -g pa11y
```

#### Run Pa11y

```bash
# Test single page
pa11y http://localhost:8080/search

# Test with specific standard
pa11y --standard WCAG2AA http://localhost:8080/search

# Output JSON for processing
pa11y --reporter json http://localhost:8080/search > results.json
```

#### Add to CircleCI

```yaml
# .circleci/config.yml
- run:
    name: Accessibility Testing
    command: |
      npm install -g pa11y
      pa11y --standard WCAG2AA http://localhost:8080 > pa11y-results.txt
```

### Using Axe-Core CLI

#### Install Axe CLI

```bash
npm install -g @axe-core/cli
```

#### Run Axe

```bash
# Test page
axe http://localhost:8080/search

# Save results
axe http://localhost:8080/search --save results.json
```

### Fail Build on Critical Issues

Configure CI to fail on critical accessibility issues:

```bash
# Example: Fail if critical issues found
pa11y --threshold 0 http://localhost:8080
```

## Resources

### Official Documentation

- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/)
- [WordPress Accessibility Handbook](https://make.wordpress.org/accessibility/handbook/)

### Testing Tools

- [axe DevTools](https://www.deque.com/axe/devtools/)
- [WAVE Browser Extension](https://wave.webaim.org/extension/)
- [NVDA Screen Reader](https://www.nvaccess.org/)
- [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)

### Tutorials

- [WebAIM: Using NVDA](https://webaim.org/articles/nvda/)
- [WebAIM: Using JAWS](https://webaim.org/articles/jaws/)
- [WebAIM: Keyboard Accessibility](https://webaim.org/articles/keyboard/)

### Community

- [A11y Project](https://www.a11yproject.com/)
- [WordPress Accessibility Team](https://make.wordpress.org/accessibility/)
- [WebAIM Discussion List](https://webaim.org/discussion/)

---

**Last Updated:** January 28, 2026
**Version:** 1.0.0
