# Mootimeter Plugin Documentation

**Plugin:** mod_mootimeter
**Moodle Version:** 4.5+
**Last Updated:** October 2025

---

## Overview

This documentation covers the styling, SCSS compilation, and general structure of the Mootimeter plugin.

For **template-specific documentation**, see: [docs/templates/README.md](templates/README.md)

---

## SCSS Compilation

### Recommended Setup

For SCSS compilation in this Moodle plugin, we recommend using:
- [VS Code](https://code.visualstudio.com/)
- [Live Sass Compiler](https://marketplace.visualstudio.com/items?itemName=ritwickdey.live-sass) extension

### Configuration

Add the following settings to your VS Code `settings.json`:

```json
"liveSassCompile.settings.includeItems": [
    "/**/mootimeter/styles.s[ac]ss"
],
"liveSassCompile.settings.generateMap": false,
"liveSassCompile.settings.partialsList": [
    "/**/mootimeter/scss/**/*.s[ac]ss"
]
```

This configuration:
- Only compiles the main `styles.scss` file
- Watches for changes in all SCSS partials in the `scss/` directory
- Does not generate source maps

### File Structure

```
mod/mootimeter/
├── styles.scss          # Main SCSS file (compiled to styles.css)
└── scss/
    ├── fonts.scss       # Typography styles
    ├── colors.scss      # Color variables
    ├── layout.scss      # Layout components
    └── components.scss  # UI components
```

---

## Typography

The following HTML tags are automatically styled within the `.mootimetercontainer` class (via `fonts.scss`):

- `h1` - `h6` (Headings)
- `p` (Paragraphs)
- `small` (Small text)

**No additional classes needed** for basic typography within Mootimeter containers.

---

## Layout Structure

### Main Container

All Mootimeter content should be wrapped in the main container:

```html
<div class="mootimetercontainer">
    <!-- Content -->
</div>
```

**Additional classes:**
- `.fullscreen` - Expands container to full viewport
- Custom classes can be added via `{{{containerclasses}}}`

### Column Layout

Mootimeter uses a three-column layout:

```html
<div class="mootimetercontainer">
    <div class="row">
        <!-- Pages Column -->
        <div class="mootimetercolpages">
            <!-- Page list -->
        </div>

        <!-- Settings Column -->
        <div class="mootimetercoledit">
            <!-- Teacher settings -->
        </div>

        <!-- Content Column -->
        <div class="mootimetercolcontent">
            <!-- Student view / Results -->
        </div>
    </div>
</div>
```

### Responsive Behavior

- **Desktop (≥680px)**: Three-column layout with min/max heights
- **Mobile (<680px)**: Stacked single-column layout

---

## Color Scheme

### Primary Colors

```scss
$primary-color: #d33f01;      // Orange/Red (main accent)
$background-light: #fff;       // White backgrounds
$background-dark: #2c3e50;     // Dark mode backgrounds
$text-primary: #333;           // Main text color
$text-secondary: #666;         // Secondary text color
```

### Component Colors

- **Buttons**: `$primary-color` background, white text
- **Inputs**: Light background with `$primary-color` borders on focus
- **Checkboxes/Radios**: `$primary-color` when checked
- **Pills/Badges**: Light gray background with dark text

---

## Component Styling

### Buttons

**Classes:**
- `.mootimeter-btn` - Standard button
- `.mootimeter-btn-full` - Full-width button
- `.mootimeter-btn-icon` - Icon-only button
- `.mootimeter-btn-transparent` - Transparent background

**HTML Structure:**
```html
<button class="mootimeter-btn">
    Submit Answer
</button>
```

### Input Fields

**Classes:**
- `.mootimeter-input` - Standard text input
- `.mootimeter-input-with-icon` - Input with icon
- `.light-background` - For use on light backgrounds

**HTML Structure:**
```html
<input type="text" class="mootimeter-input" placeholder="Enter text...">
```

### Custom Checkboxes

**HTML Structure:**
```html
<div class="mootimeter-checkbox">
    <label>
        <input type="checkbox" name="multipleanswers[]" value="1"/>
        <span class="checkbox-icon-wrapper">
            <i class="icon fa fa-check fa-light checkbox-icon"></i>
        </span>
        <span>Label text</span>
    </label>
</div>
```

**Behavior:**
- Native checkbox is hidden
- Custom icon wrapper displays FontAwesome icon
- Label uses flexbox for alignment
- Icon background changes to `$primary-color` when checked

### Custom Radio Buttons

**HTML Structure:**
```html
<div class="mootimeter-radio-btn">
    <label>
        <input type="radio" name="multipleanswers[]" value="1"/>
        <span class="radio-btn-icon-wrapper">
            <i class="icon fa fa-check fa-light radio-btn-icon"></i>
        </span>
        <span>Label text</span>
    </label>
</div>
```

**Behavior:**
- Same as checkboxes but for radio button groups
- Only one option can be selected per group

### Pills/Badges

**Classes:**
- `.mootimeter-pill` - Small label badge

**HTML Structure:**
```html
<span class="mootimeter-pill">Quiz</span>
```

### Notifications

**Classes:**
- `.mootimeter-notification` - Base notification
- `.notification-success` - Success message
- `.notification-warning` - Warning message
- `.notification-error` - Error message

**HTML Structure:**
```html
<div class="mootimeter-notification notification-success">
    <i class="icon fa fa-check"></i>
    <span>Answer submitted successfully!</span>
</div>
```

---

## Dark Mode Support

The plugin includes dark mode styles that are automatically applied based on Moodle's theme settings.

**Dark mode classes:**
- Applied automatically via `.theme-dark` or similar theme classes
- Colors are inverted for better readability
- Input fields use darker backgrounds with lighter text

---

## Mobile Responsiveness

### Breakpoints

```scss
// Mobile
@media screen and (max-width: 679px) {
    // Stacked layout
    // Full-width components
}

// Desktop
@media screen and (min-width: 680px) {
    // Column layout
    // Min/max heights applied
}
```

### Mobile Optimizations

- Touch-friendly button sizes (min 44x44px)
- Larger font sizes for readability
- Simplified layouts with reduced whitespace
- Full-width inputs and buttons

---

## Accessibility

### ARIA Attributes

- All icon-only buttons have `aria-label` attributes
- Decorative icons have `aria-hidden="true"`
- Form inputs have associated `<label>` elements
- Color contrast meets WCAG AA standards

### Keyboard Navigation

- All interactive elements are keyboard accessible
- Focus states are clearly visible
- Tab order follows logical flow

---

## Browser Support

The plugin is tested and supported on:

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

---

## Development Workflow

### Making Style Changes

1. Edit SCSS files in `scss/` directory
2. Save file (Live Sass Compiler auto-compiles)
3. Purge Moodle caches: `./bindev/purge_caches.sh`
4. Refresh browser to see changes

### Testing Styles

1. Test on multiple screen sizes (mobile, tablet, desktop)
2. Test in both light and dark modes
3. Test with browser zoom (150%, 200%)
4. Validate color contrast ratios

---

## Performance Considerations

### CSS Size

- Current compiled CSS size: ~50KB
- Minified for production deployment
- No external CSS dependencies (except Moodle core)

### Best Practices

1. Use existing classes when possible
2. Avoid inline styles in templates
3. Use CSS variables for repeated values
4. Minimize use of `!important`
5. Keep selectors specific but not overly nested

---

## Troubleshooting

### Styles not applying?

1. Check SCSS compilation worked (check for errors in VS Code)
2. Purge Moodle caches: `./bindev/purge_caches.sh`
3. Hard refresh browser (Ctrl+Shift+R / Cmd+Shift+R)
4. Check browser console for CSS loading errors

### Dark mode issues?

1. Verify theme supports dark mode
2. Check if theme overrides Mootimeter styles
3. Test with different themes

### Mobile layout broken?

1. Check responsive breakpoints are correct
2. Test in device mode in browser DevTools
3. Verify no fixed widths are preventing responsive behavior

---

## Further Reading

- [Template Documentation](templates/README.md) - Mustache templates
- [Moodle Coding Style](https://moodledev.io/general/development/policies/codingstyle)
- [Moodle Theme Development](https://moodledev.io/docs/guides/themes)
- [SCSS Documentation](https://sass-lang.com/documentation)

