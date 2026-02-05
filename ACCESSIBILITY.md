# Accessibility Statement

**Semantic Knowledge Plugin**
**Last Updated:** January 28, 2026

## Our Commitment to Accessibility

The Semantic Knowledge plugin is committed to providing an accessible experience for all users, including those who rely on assistive technologies. We strive to meet Web Content Accessibility Guidelines (WCAG) 2.1 Level AA standards.

## WCAG 2.1 Level AA Compliance Status

**Current Compliance:** 74% (In Progress)

We are actively working to achieve full WCAG 2.1 Level AA compliance. The plugin demonstrates strong accessibility foundations with proper ARIA attributes, keyboard navigation, and focus management in many areas.

### Compliance Summary

| WCAG 2.1 Principle | Level A Status | Level AA Status |
|-------------------|----------------|-----------------|
| **Perceivable** | 80% | 67% |
| **Operable** | 75% | 70% |
| **Understandable** | 88% | 86% |
| **Robust** | 80% | 80% |

## Accessibility Features Implemented

### Keyboard Navigation

The plugin is fully operable via keyboard:

- **Tab** - Navigate between interactive elements
- **Shift + Tab** - Navigate backwards
- **Enter/Space** - Activate buttons and links
- **Escape** - Close chatbot popup (floating button mode)
- **Arrow keys** - Navigate within chat interface (Deep Chat component)

### Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Escape` | Close chatbot popup |
| `Tab` | Move focus forward |
| `Shift + Tab` | Move focus backward |
| `Enter` | Submit search query or send chat message |
| `Space` | Activate focused button |

### Screen Reader Compatibility

The plugin has been designed with screen reader users in mind:

#### Implemented Features

- **ARIA Live Regions** - Dynamic content updates are announced to screen readers
- **Semantic HTML** - Proper use of headings, landmarks, and form elements
- **ARIA Labels** - All icon-only buttons have descriptive labels
- **Focus Management** - Focus is moved appropriately when opening/closing dialogs
- **Status Messages** - Loading states and errors are announced
- **Form Labels** - All form inputs have associated labels

#### Tested With

We conduct testing with the following screen readers:

- NVDA (Windows) - Primary testing
- JAWS (Windows) - Secondary testing
- VoiceOver (macOS/iOS) - Mobile and desktop testing
- TalkBack (Android) - Mobile testing (planned)

### Visual Accessibility

#### Color Contrast

All text and interactive elements meet WCAG AA color contrast requirements:

- **Normal text:** Minimum 4.5:1 contrast ratio
- **Large text:** Minimum 3:1 contrast ratio
- **Interactive elements:** Sufficient contrast for hover and focus states

**Primary Colors:**
- Primary button: #ff7d55 on white background (3.5:1)
- Primary text: #153e35 on white background (12.23:1)
- Links: #153e35 (12.23:1)
- Focus indicators: #0073aa (4.5:1)

#### Focus Indicators

All interactive elements display visible focus indicators:

- **3px solid outline** on all focusable elements
- **2px outline offset** for clear separation
- **High contrast colors** (#0073aa, #153e35)

#### Reduced Motion Support

Users who have enabled reduced motion preferences in their operating system will experience:

- Disabled animations and transitions
- Instant state changes instead of animated transitions
- No loading spinners (replaced with static text)
- No transform animations on hover

### Responsive Design

The plugin adapts to different screen sizes and orientations:

- **Mobile-first design** - Optimized for touch devices
- **Flexible layouts** - Adapts to viewport size
- **Touch targets** - Minimum 44x44px for mobile devices
- **Readable text** - Minimum 16px font size on mobile

## Known Limitations

### Deep Chat Component (Third-Party Library)

The chatbot interface uses the Deep Chat library (loaded from CDN). While we have implemented accessibility enhancements around this component, we cannot guarantee full accessibility within the component itself.

**Known Concerns:**

1. **Keyboard Navigation** - While basic keyboard navigation works, complex navigation within chat history may be limited
2. **Screen Reader Announcements** - Chat messages may not always be announced optimally
3. **Focus Management** - Internal focus management is controlled by the Deep Chat library
4. **Color Contrast** - Some Deep Chat UI elements may not meet WCAG AA contrast requirements

**Our Mitigations:**

- Added custom CSS to improve focus indicators
- Implemented external ARIA live region for announcements
- Custom color scheme with improved contrast
- Focus management wrapper around the component

**What We're Doing:**

We are actively monitoring the Deep Chat library for accessibility improvements and will update our integration accordingly. We are also evaluating alternative chat interface libraries with better accessibility support.

### Progressive Enhancement

Some features require JavaScript to function:

- AI-powered search functionality
- Chatbot interface
- Dynamic result loading

**Fallback Behavior:**

- Forms degrade gracefully without JavaScript
- Error messages are displayed if scripts fail to load
- Core WordPress search remains available as fallback

## Testing Methodology

Our accessibility testing includes:

1. **Automated Testing**
   - axe DevTools for Chrome/Firefox
   - WAVE browser extension
   - WordPress Accessibility Checker

2. **Manual Testing**
   - Keyboard navigation testing
   - Screen reader testing (NVDA, JAWS, VoiceOver)
   - Color contrast analysis
   - Browser testing (Chrome, Firefox, Safari, Edge)

3. **Code Review**
   - Manual inspection of HTML, CSS, JavaScript, and PHP
   - WCAG 2.1 checklist verification
   - ARIA validation

## Accessibility Roadmap

We are committed to continuous improvement. Our current roadmap includes:

### Phase 1: Critical Fixes (Completed)

- ✅ Color contrast improvements
- ✅ Heading hierarchy corrections
- ✅ Form error associations
- ✅ Required field indicators
- ✅ Loading state announcements
- ✅ Focus management improvements

### Phase 2: High Priority (In Progress)

- 🔄 Deep Chat accessibility audit and improvements
- 🔄 Enhanced landmark regions
- 🔄 Skip link implementation
- 🔄 Touch target size optimization
- 🔄 Improved error messaging

### Phase 3: Continuous Improvement (Ongoing)

- ⏳ Language detection for multilingual content
- ⏳ Keyboard shortcut documentation
- ⏳ Alternative chat interface evaluation
- ⏳ Enhanced mobile screen reader support

**Legend:** ✅ Completed | 🔄 In Progress | ⏳ Planned

## How to Report Accessibility Issues

We take accessibility seriously and welcome feedback from the community.

### Reporting an Issue

If you encounter an accessibility barrier while using this plugin, please report it:

**Via GitHub:**
1. Visit [github.com/kanopi/semantic-knowledge/issues](https://github.com/kanopi/semantic-knowledge/issues)
2. Click "New Issue"
3. Select "Accessibility Issue" template
4. Provide detailed information about the barrier

**Via Email:**
Send accessibility reports to: [accessibility@kanopi.com](mailto:accessibility@kanopi.com)

### What to Include in Your Report

To help us address the issue quickly, please include:

- **Description** - What accessibility barrier did you encounter?
- **Location** - Where in the plugin did this occur? (chatbot, search, settings, etc.)
- **Assistive Technology** - What tools are you using? (screen reader, keyboard only, etc.)
- **Browser & OS** - Which browser and operating system?
- **Steps to Reproduce** - How can we recreate the issue?
- **Expected Behavior** - What should happen instead?
- **WCAG Criteria** - Which WCAG success criterion is affected? (if known)

### Response Timeline

We aim to:

- **Acknowledge** all accessibility reports within 2 business days
- **Assess** the severity and impact within 1 week
- **Provide updates** on progress every 2 weeks
- **Fix critical issues** (WCAG Level A failures) within 30 days
- **Fix high priority issues** (WCAG Level AA failures) within 90 days

## Contact Information

### Accessibility Coordinator

**Team:** Kanopi Studios Development Team
**Email:** [accessibility@kanopi.com](mailto:accessibility@kanopi.com)
**GitHub:** [github.com/kanopi/semantic-knowledge](https://github.com/kanopi/semantic-knowledge)

### Support Channels

- **GitHub Issues:** [Report bugs and accessibility issues](https://github.com/kanopi/semantic-knowledge/issues)
- **Documentation:** [Read detailed developer docs](https://github.com/kanopi/semantic-knowledge#readme)
- **Email Support:** [support@kanopi.com](mailto:support@kanopi.com)

## Technical Specifications

### Assistive Technology Compatibility

| Technology | Status | Notes |
|-----------|--------|-------|
| NVDA (Windows) | ✅ Compatible | Tested with latest version |
| JAWS (Windows) | ⚠️ Mostly Compatible | Some Deep Chat limitations |
| VoiceOver (macOS) | ✅ Compatible | Tested with latest macOS |
| VoiceOver (iOS) | ⚠️ Mostly Compatible | Mobile testing in progress |
| TalkBack (Android) | ⏳ Testing Planned | Not yet tested |
| Windows Narrator | ⏳ Testing Planned | Not yet tested |
| Keyboard Only | ✅ Fully Compatible | All features accessible |
| Voice Control | ⚠️ Partially Compatible | Button labels need improvement |

**Legend:** ✅ Fully Compatible | ⚠️ Mostly Compatible (known issues) | ⏳ Testing Planned | ❌ Not Compatible

### Browser Support

The plugin supports modern browsers with accessibility features:

- Chrome 90+ (with NVDA, JAWS)
- Firefox 88+ (with NVDA, JAWS)
- Safari 14+ (with VoiceOver)
- Edge 90+ (with NVDA, JAWS)

### Standards Compliance

- **WCAG 2.1 Level AA** (Target: Full compliance by Q2 2026)
- **ARIA 1.2** Authoring Practices
- **Section 508** (US Federal accessibility standard)
- **EN 301 549** (EU accessibility standard)

## Additional Resources

### For Users

- [WebAIM: Using NVDA to Evaluate Web Accessibility](https://webaim.org/articles/nvda/)
- [Using JAWS to Evaluate Web Accessibility](https://webaim.org/articles/jaws/)
- [VoiceOver User Guide (Apple)](https://support.apple.com/guide/voiceover/welcome/mac)

### For Developers

- [WCAG 2.1 Quick Reference](https://www.w3.org/WAI/WCAG21/quickref/)
- [WordPress Accessibility Handbook](https://make.wordpress.org/accessibility/handbook/)
- [ARIA Authoring Practices Guide](https://www.w3.org/WAI/ARIA/apg/)
- [Testing Guide for Developers](docs/accessibility-testing-guide.md)

## Disclaimer

While we strive for full accessibility, we acknowledge that some accessibility barriers may exist, particularly within third-party components. We are committed to addressing these issues and welcome feedback from the community.

This accessibility statement is reviewed and updated quarterly or when significant changes are made to the plugin.

---

**Last Reviewed:** January 28, 2026
**Next Review:** April 28, 2026
**Version:** 1.0.0
