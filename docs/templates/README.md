# Mootimeter Template System Documentation

**Plugin:** mod_mootimeter
**Moodle Version:** 4.5+
**Last Updated:** October 2025

---

## Overview

The Mootimeter plugin uses self-contained Mustache templates that include all necessary HTML inline, without external snippet dependencies. This approach ensures:

- ✅ **Complete HTML structure visible at a glance**
- ✅ **Easy maintenance** - no need to trace through multiple files
- ✅ **Comprehensive inline documentation** using PHPDoc-style comments
- ✅ **Better performance** - no nested template includes

---

## Template Structure

### Quiz Tool Templates

Located in: `tools/quiz/templates/`

#### view_content.mustache
Student-facing view for answering quiz questions.

**Features:**
- Multiple choice (checkboxes) and single choice (radio buttons)
- Custom-styled inputs with FontAwesome icons
- Answer correction mode with visual indicators
- Responsive layout with flexbox alignment

**Key CSS Classes:**
- `.mootimeter-checkbox` - Wrapper for checkbox inputs with custom styling
- `.mootimeter-radio-btn` - Wrapper for radio button inputs with custom styling
- `.checkbox-icon-wrapper` / `.radio-btn-icon-wrapper` - Custom icon containers
- `.mootimeter-answeroption` - Individual answer option container
- `.mootimeter-answeroption-text` - Answer text label

**JavaScript Integration:**
- Uses `mootimetertool_quiz/store_answer` AMD module
- Input name must be: `multipleanswers[]` (for both checkboxes and radios)
- Submit button requires: `data-pageid` attribute

#### view_settings.mustache
Teacher-facing view for configuring quiz questions.

**Features:**
- Question editor with live updates
- Answer option management (add/remove/edit)
- Correct answer marking
- Multiple choice toggle
- Anonymous mode toggle

#### view_overview.mustache
Quick statistics overview with answer distribution.

**Features:**
- Total answers count
- Answer distribution bars
- Correct answer indicators
- Conditional visibility based on correction mode

#### view_results.mustache
Detailed results visualization with Chart.js.

**Features:**
- Horizontal bar chart showing answer distribution
- Color-coded correct/incorrect answers
- Responsive chart sizing
- Chart.js integration

---

### Wordcloud Tool Templates

Located in: `tools/wordcloud/templates/`

#### view_content.mustache
Student-facing view for submitting wordcloud entries.

**Features:**
- Text input for word/phrase submission
- Character limit with live counter
- Submit button with AJAX handling
- Success/error notifications

#### view_settings.mustache
Teacher-facing configuration view.

**Features:**
- Question editor
- Character limit setting
- Anonymous mode toggle
- Wordlist management

#### view_overview.mustache
Quick statistics overview.

**Features:**
- Total submissions count
- Recent submissions list
- Conditional visibility

#### view_results.mustache
Live wordcloud visualization.

**Features:**
- Dynamic wordcloud generation
- Word frequency-based sizing
- Real-time updates
- Responsive canvas rendering

---

## Shared Template Elements

### Common Patterns

All tool templates follow these conventions:

1. **Wrapper Structure**
   ```mustache
   {{#withwrapper}}
   <div class="mootimetercontainer {{{containerclasses}}}">
   {{/withwrapper}}
       <!-- Content -->
   {{#withwrapper}}
   </div>
   {{/withwrapper}}
   ```

2. **Header Section**
   ```mustache
   <div class="mootimeter-colcontent-preview-header">
       {{#toolname}}
       <span class="mootimeter-pill">{{.}}</span>
       {{/toolname}}
       <h4>{{{question}}}</h4>
   </div>
   ```

3. **JavaScript Initialization**
   ```mustache
   {{#js}}
   require(['module/name'], (module) => module.init("element_id"));
   {{/js}}
   ```

### CSS Classes

**Layout:**
- `.mootimetercontainer` - Main container
- `.mootimeter-colcontent-preview` - Content area
- `.mootimeter-colcontent-preview-header` - Header section
- `.mootimeter-colcontent-preview-options` - Options/answers area
- `.mootimeter-colcontent-preview-send` - Submit button area

**Components:**
- `.mootimeter-btn` - Standard button
- `.mootimeter-btn-full` - Full-width button
- `.mootimeter-pill` - Tool name badge
- `.mootimeter-input` - Text input field
- `.mootimeter-checkbox` - Checkbox wrapper with custom styling
- `.mootimeter-radio-btn` - Radio button wrapper with custom styling

**Icons:**
- `.checkbox-icon-wrapper` - Custom checkbox icon container
- `.radio-btn-icon-wrapper` - Custom radio icon container
- `.checkbox-icon` / `.radio-btn-icon` - FontAwesome check icons

---

## JavaScript Integration

### Quiz Tool

**Module:** `mootimetertool_quiz/store_answer`

**Requirements:**
- Submit button must have unique `id` attribute
- Submit button must have `data-pageid` attribute
- All inputs must use `name="multipleanswers[]"`
- Module initialized with button ID: `module.init("button_id_{{uniqid}}")`

**Event Flow:**
1. User clicks submit button
2. JavaScript collects all checked inputs with `name="multipleanswers[]"`
3. Sends array of answer IDs via AJAX
4. Displays success/error notification

### Wordcloud Tool

**Module:** `mootimetertool_wordcloud/store_answer`

**Requirements:**
- Input field must have unique `id` attribute
- Submit button linked to input field
- Character counter updates on input change

---

## Template Context Data

### Quiz view_content.mustache

```php
[
    'uniqid' => '12345',
    'pageid' => 42,
    'question' => 'What is the capital of France?',
    'toolname' => 'Quiz',
    'containerclasses' => 'custom-class',
    'ismultiplechoice' => true,
    'showanswercorrection' => false,
    'answeroptions' => [
        [
            'id' => 1,
            'text' => 'Paris',
            'ischecked' => false,
            'isdisabled' => false,
            'iscorrect' => true,
            'highlightclass' => ''
        ],
        // ... more options
    ],
    'sendbutton' => [
        'id' => 'submit_btn',
        'text' => 'Submit Answer',
        'context' => [
            'text' => 'Choose one or more answers'
        ]
    ],
    'withwrapper' => true
]
```

### Wordcloud view_content.mustache

```php
[
    'uniqid' => '12345',
    'pageid' => 42,
    'question' => 'What comes to mind?',
    'toolname' => 'Wordcloud',
    'containerclasses' => 'custom-class',
    'input' => [
        'id' => 'wordcloud_input',
        'placeholder' => 'Enter your word...',
        'maxlength' => 100
    ],
    'sendbutton' => [
        'id' => 'submit_btn',
        'text' => 'Submit'
    ],
    'withwrapper' => true
]
```

---

## Styling Guidelines

### Custom Input Styling

The plugin uses custom-styled checkboxes and radio buttons:

**HTML Structure:**
```html
<div class="mootimeter-checkbox">
    <label>
        <input type="checkbox" name="multipleanswers[]" value="1"/>
        <span class="checkbox-icon-wrapper">
            <i class="icon fa fa-check fa-light checkbox-icon"></i>
        </span>
        <span class="mootimeter-answeroption-text">Answer text</span>
    </label>
</div>
```

**CSS Pattern:**
- Native input is hidden with `display: none`
- Icon wrapper displays custom FontAwesome icon
- Label uses flexbox for vertical alignment
- `:checked` state changes icon wrapper background color

**Color Scheme:**
- Primary: `#d33f01` (orange/red)
- Background: `#fff` (white)
- Border: `#d33f01`
- Checked background: `#d33f01`
- Checked text: `#fff`

---

## Best Practices

### Template Development

1. **Documentation**: Add comprehensive PHPDoc-style comments
2. **Structure**: Keep HTML structure flat and readable
3. **Classes**: Use semantic CSS class names
4. **IDs**: Always append `_{{uniqid}}` to ensure uniqueness
5. **Inline**: Include all HTML inline, no external snippets

### Context Data

1. **Naming**: Use clear, descriptive variable names
2. **Types**: Document expected data types in comments
3. **Defaults**: Provide sensible defaults in PHP
4. **Validation**: Validate all input data before passing to template

### JavaScript

1. **AMD Modules**: Always use AMD module pattern
2. **Event Delegation**: Prefer event delegation over direct binding
3. **Error Handling**: Always catch and display exceptions
4. **AJAX**: Use Moodle's core/ajax API

---

## Testing

### Template Validation

```bash
# Check Mustache syntax
php admin/cli/mustache.php --lint

# Purge template cache
php admin/cli/purge_caches.php
```

### Code Standards

```bash
# Check Moodle coding standards
./bindev/codechecker.sh

# Auto-fix coding standards
./bindev/codechecker_autofix.sh

# Check PHPDoc standards
./bindev/moodlecheck.sh
```

---

## Troubleshooting

### Common Issues

**Templates not updating?**
- Run: `./bindev/purge_caches.sh`
- Check file permissions
- Verify template path is correct

**JavaScript not working?**
- Check browser console for errors
- Verify AMD module is built: `npx grunt amd --force`
- Check element IDs match between template and JS

**Custom inputs not displaying?**
- Verify CSS classes are correct
- Check icon wrapper structure
- Ensure FontAwesome is loaded

**AJAX requests failing?**
- Check `data-pageid` attribute exists
- Verify input names are correct
- Check network tab for error details

---

## Further Reading

- [Moodle Templates Documentation](https://moodledev.io/docs/guides/templates)
- [Mustache Syntax](https://mustache.github.io/mustache.5.html)
- [Moodle Coding Style](https://moodledev.io/general/development/policies/codingstyle)
- [AMD Module Pattern](https://moodledev.io/docs/guides/javascript/modules)

