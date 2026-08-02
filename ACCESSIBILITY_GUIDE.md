# Aurora Platform - Accessibility Guide

## Overview

Aurora Platform is built with WCAG 2.1 AA compliance in mind. This guide documents the accessibility features implemented and how to maintain them as the platform evolves.

---

## ✅ WCAG 2.1 AA Compliance Features

### 1. Perceivable Content
- **Color Contrast**: All text meets minimum contrast ratios (4.5:1 for normal text)
- **Text Alternatives**: Images have alt text; icons have aria-labels
- **Distinguishable**: Text is readable; focus indicators are visible
- **Adaptable**: Content adapts to different screen sizes and zoom levels (up to 200%)

### 2. Operable Interface
- **Keyboard Accessible**: All functionality available via keyboard
- **Keyboard Shortcuts**:
  - `Alt + D` - Go to Dashboard
  - `Alt + M` - Toggle Mobile Menu
  - `Alt + S` - Focus Search Box
  - `Alt + H` - Show keyboard shortcuts help
  - `Tab` - Navigate forward through interactive elements
  - `Shift + Tab` - Navigate backward
  - `Enter` - Activate buttons and links
  - `Space` - Toggle checkboxes
  - `Escape` - Close modals and menus

- **Skip Links**: "Skip to main content" link for screen readers
- **Focus Management**: Visible focus indicators (3px blue outline)
- **Touch Targets**: Minimum 44x44px for all interactive elements

### 3. Understandable Content
- **Readable**: Clear language, proper heading hierarchy
- **Predictable**: Consistent navigation and behavior
- **Input Assistance**: 
  - Form labels clearly associated with inputs
  - Error messages are specific and helpful
  - Required fields are marked with asterisk (*)
  - Validation feedback uses color + text

### 4. Robust Code
- **Semantic HTML**: Proper heading levels, list structures, form elements
- **ARIA Support**: 
  - Live regions for dynamic announcements
  - Proper roles and aria-labels
  - aria-required for form validation
  - aria-invalid for error states
- **Screen Reader Support**: Content is announced properly

---

## 📁 Accessibility Files

### CSS Files
- **public/css/accessibility.css** (900+ lines)
  - Focus indicators and keyboard navigation styles
  - Color contrast and readability enhancements
  - Semantic HTML styling
  - Form accessibility improvements
  - Table accessibility enhancements
  - Modal accessibility
  - Print stylesheet
  - Dark mode support
  - Motion/animation preferences

### JavaScript Files
- **public/js/utils/accessibility.js** (450+ lines)
  - ARIA live region management
  - Focus management and modal handling
  - Keyboard shortcut implementation
  - Form accessibility enhancements
  - Table accessibility improvements
  - Link accessibility improvements
  - ARIA label management

---

## 🎯 Implementation Details

### Focus Indicators
All interactive elements have visible focus indicators:
```css
:focus-visible {
    outline: 3px solid #3498db;
    outline-offset: 2px;
}
```

### Form Labels
All form inputs must have associated labels:
```html
<label for="email">Email Address</label>
<input type="email" id="email" name="email">
```

### ARIA Live Regions
Dynamic content updates are announced to screen readers:
```javascript
window.a11y.announce('Item saved successfully');
```

### Skip Links
A "Skip to main content" link appears on focus:
```html
<a href="#main-content" class="skip-to-main">Skip to main content</a>
<div id="main-content" class="content">...</div>
```

### Keyboard Shortcuts
Alt key combined with letter provides quick navigation:
```javascript
// Alt + D goes to Dashboard
document.addEventListener('keydown', (e) => {
    if (e.altKey && e.key === 'd') {
        navigateTo('dashboard');
    }
});
```

---

## 🧪 Testing Accessibility

### Automated Testing
Run the accessibility test in the browser console:
```javascript
window.a11y.testAccessibility();
```

This returns an object with:
- Skip links present
- ARIA live region present
- Forms with labels count
- Forms without labels count
- Images with alt text count
- Images without alt text count

### Manual Testing Checklist

#### Keyboard Navigation
- [ ] Tab through all interactive elements
- [ ] Shift+Tab works in reverse
- [ ] Focus indicators are visible
- [ ] Tab order makes sense (left to right, top to bottom)
- [ ] Enter/Space activate buttons and links
- [ ] Escape closes modals/menus

#### Screen Reader Testing
- [ ] Test with NVDA (Windows) or JAWS
- [ ] Test with VoiceOver (Mac)
- [ ] Test with NVDA or TalkBack (Android)
- [ ] Test with VoiceOver (iOS)

#### Color Contrast
- [ ] Use WebAIM Contrast Checker
- [ ] Check all text (normal and large)
- [ ] Check form inputs and labels
- [ ] Check buttons and links

#### Zoom Testing
- [ ] Test at 100% zoom
- [ ] Test at 150% zoom
- [ ] Test at 200% zoom
- [ ] No horizontal scrolling should occur

#### Mobile Accessibility
- [ ] Touch targets are 44x44px minimum
- [ ] Focus indicators are visible on touch
- [ ] Swipe navigation works
- [ ] Screen readers work properly

### Browser Extensions
- **axe DevTools** - Automated accessibility testing
- **WebAIM Contrast Checker** - Color contrast validation
- **WAVE** - Web Accessibility Evaluation Tool
- **Lighthouse** - Built into Chrome DevTools

---

## 📋 Maintenance Guidelines

### When Adding New Features

1. **Use Semantic HTML**
   - Use `<button>` for buttons, not `<div onclick>`
   - Use `<nav>` for navigation
   - Use proper heading hierarchy (h1, h2, h3...)
   - Use `<form>` for forms

2. **Add ARIA Labels**
   - Icon buttons need `aria-label`
   - Form inputs need `<label for="id">`
   - Dynamic content needs `aria-live` regions
   - Sections need `aria-label` or `aria-labelledby`

3. **Ensure Keyboard Support**
   - All functionality must work with keyboard
   - Tab order must be logical
   - Focus indicators must be visible
   - Consider keyboard shortcuts

4. **Check Color Contrast**
   - Text: minimum 4.5:1 contrast ratio
   - Large text: minimum 3:1 contrast ratio
   - Use WebAIM Contrast Checker

5. **Test with Screen Readers**
   - Test page structure
   - Test form labels
   - Test dynamic content
   - Test navigation

### Code Examples

#### Accessible Button
```html
<!-- ✅ Good -->
<button aria-label="Close menu">
    <i class="fas fa-times"></i>
</button>

<!-- ❌ Bad -->
<div onclick="closeMenu()">
    <i class="fas fa-times"></i>
</div>
```

#### Accessible Form
```html
<!-- ✅ Good -->
<label for="email">Email Address *</label>
<input 
    type="email" 
    id="email" 
    name="email" 
    required
    aria-required="true"
    aria-label="Email Address"
>
<div class="invalid-feedback">Please enter a valid email</div>

<!-- ❌ Bad -->
<input type="email" placeholder="Email">
```

#### Accessible Table
```html
<!-- ✅ Good -->
<table>
    <caption>Sales Report for Q1 2026</caption>
    <thead>
        <tr>
            <th id="month">Month</th>
            <th id="revenue">Revenue</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td headers="month">January</td>
            <td headers="revenue">$5,000</td>
        </tr>
    </tbody>
</table>

<!-- ❌ Bad -->
<div>
    <div>January | $5,000</div>
</div>
```

#### Accessible Modal
```html
<!-- ✅ Good -->
<div class="modal" role="dialog" aria-labelledby="modalTitle">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Edit Profile</h5>
                <button aria-label="Close modal" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form>
                <!-- form content -->
            </form>
        </div>
    </div>
</div>

<!-- ❌ Bad -->
<div class="popup">
    <h5>Edit Profile</h5>
    <div onclick="closePopup()">X</div>
</div>
```

---

## 🔄 Continuous Improvement

### Monitoring
- Run automated tests monthly
- Use analytics to identify problem areas
- Collect feedback from users with disabilities
- Monitor browser and screen reader updates

### Updates
- Update accessibility.css and accessibility.js as needed
- Test with latest screen readers and browsers
- Keep Bootstrap and Font Awesome updated
- Review WCAG updates annually

### Resources
- **WCAG 2.1 Guidelines**: https://www.w3.org/WAI/WCAG21/quickref/
- **WebAIM**: https://webaim.org/
- **MDN Accessibility**: https://developer.mozilla.org/en-US/docs/Web/Accessibility
- **A11ycasts**: https://www.youtube.com/playlist?list=PLNYkxOF6rcICWx0C9Xc-RgEJlHc3MwVHU

---

## 📞 Support

For accessibility issues or questions:
1. Check this guide first
2. Test with WAVE or axe DevTools
3. Review WCAG 2.1 AA standards
4. Consult with accessibility specialists

---

## Compliance Statement

Aurora Platform is designed and tested to meet WCAG 2.1 Level AA standards. While we strive for perfect accessibility, some third-party components may have limitations. Please report any accessibility issues for prompt attention.

**Last Updated**: 2026-08-02
**Status**: WCAG 2.1 AA Compliant
**Review Date**: 2026-11-02
