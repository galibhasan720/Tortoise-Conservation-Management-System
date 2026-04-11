# Quick Reference: Modern Lavender Theme
**Ready-to-use code snippets and color values**

---

## Color Palette (Copy-Paste Ready)

```css
/* Modern Lavender Color Variables */
--color-primary: #6367FF;      /* Dark Purple - Use for buttons, headers, primary text */
--color-secondary: #8494FF;    /* Light Purple - Use for hover states */
--color-accent: #C9BEFF;       /* Lavender - Use for borders, badges */
--color-bg: #FFDBFD;           /* Lightest Pink - Page background */
--color-white: #ffffff;        /* White - Card backgrounds */
```

---

## HTML Snippets

### Page Header
```html
<div class="page-header" data-aos="fade-up">
    <div class="container">
        <h1>📊 Your Page Title</h1>
        <p>Your subtitle or description</p>
    </div>
</div>
```

### Card Container
```html
<div class="card" data-aos="fade-up">
    <div class="card-body">
        Your content here
    </div>
</div>
```

### Animated Section
```html
<div data-aos="fade-up">
    Your section content
</div>
```

### Primary Button
```html
<button class="btn btn-primary">Click Me</button>
```

### Table with Animations
```html
<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>Header</th>
        </tr>
    </thead>
    <tbody>
        <tr data-aos="fade-up">
            <td>Content</td>
        </tr>
    </tbody>
</table>
```

### Status Badge
```html
<span class="badge" style="background-color: #6367FF;">Status</span>
```

### Form Group
```html
<div class="mb-3" data-aos="fade-up">
    <label for="input" class="form-label">Label</label>
    <input type="text" class="form-control" id="input">
</div>
```

### Alert
```html
<div class="alert alert-success" data-aos="fade-up">
    Success message here
</div>
```

---

## CSS Usage in Templates

### In PHP Files
```php
<head>
    <title>Page Title</title>
    <?php include 'includes/head_template.php'; ?>
</head>

<body>
    <!-- Your content -->
    
    <?php include 'includes/footer_script.php'; ?>
</body>
```

### Direct Style Examples
```html
<!-- Using primary color -->
<h2 style="color: #6367FF;">Heading</h2>

<!-- Using accent color background -->
<div style="background-color: #C9BEFF; padding: 1rem;">Content</div>

<!-- Using primary with transparency -->
<div style="background-color: rgba(99, 103, 255, 0.1); padding: 1rem;">Content</div>
```

---

## Common Patterns

### Centered Card Form
```html
<div class="card" style="max-width: 600px; margin: 0 auto; padding: 2rem;" data-aos="fade-up">
    <form method="POST">
        <div class="mb-3" data-aos="fade-up">
            <label for="field" class="form-label">Field Label</label>
            <input type="text" class="form-control" id="field" name="field" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Submit</button>
    </form>
</div>
```

### Grid Layout with Cards
```html
<div class="row">
    <?php foreach ($items as $item): ?>
        <div class="col-md-6 col-lg-4" data-aos="fade-up">
            <div class="card h-100">
                <div class="card-body">
                    <!-- Item content -->
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
```

### Header with Subtitle
```html
<div class="page-header" data-aos="fade-up">
    <div class="container">
        <h1>🎯 Page Title with Icon</h1>
        <p>Subtitle or description text here</p>
    </div>
</div>
```

### Button Group
```html
<div style="display: flex; gap: 0.75rem;">
    <button class="btn btn-secondary flex-fill">Cancel</button>
    <button class="btn btn-primary flex-fill">Submit</button>
</div>
```

### Table with Status
```html
<tr data-aos="fade-up">
    <td><?= $item['name'] ?></td>
    <td>
        <span class="badge" style="background-color: #6367FF; color: white;">
            <?= $item['status'] ?>
        </span>
    </td>
</tr>
```

---

## Animation Options (AOS)

Replace `fade-up` in `data-aos="fade-up"` with:

```html
<!-- Fading Effects -->
data-aos="fade"
data-aos="fade-up"
data-aos="fade-down"
data-aos="fade-left"
data-aos="fade-right"

<!-- Sliding Effects -->
data-aos="slide-up"
data-aos="slide-down"
data-aos="slide-left"
data-aos="slide-right"

<!-- Zoom Effects -->
data-aos="zoom-in"
data-aos="zoom-in-up"
data-aos="zoom-out"

<!-- Other Effects -->
data-aos="flip-left"
data-aos="flip-right"
data-aos="bounce"
```

Example:
```html
<div data-aos="zoom-in">Zooms in on scroll</div>
<div data-aos="slide-right">Slides in from left</div>
```

---

## Bootstrap Classes Ready to Use

```html
<!-- Spacing -->
.mb-3     <!-- Margin bottom -->
.mt-3     <!-- Margin top -->
.p-3      <!-- Padding all sides -->
.px-3     <!-- Padding horizontal -->
.py-3     <!-- Padding vertical -->

<!-- Text -->
.text-primary      <!-- Uses #6367FF -->
.text-muted        <!-- Muted gray text -->
.fw-bold           <!-- Font weight bold -->
.text-center       <!-- Center aligned text -->

<!-- Display -->
.d-flex            <!-- Flexbox -->
.flex-fill         <!-- Flex grow 1 -->
.justify-content-between
.align-items-center
.gap-3             <!-- Gap between flex items -->

<!-- Sizing -->
.w-100             <!-- Width 100% -->
.h-100             <!-- Height 100% -->

<!-- Colors -->
.bg-light          <!-- Light background -->
.border            <!-- Add border -->
.rounded           <!-- Rounded corners -->
.shadow            <!-- Box shadow -->
```

---

## Configuration Reference

### AOS Library Settings (in footer_script.php)
```javascript
AOS.init({
    duration: 600,      // Animation duration in ms
    easing: 'ease-out', // Easing function
    once: false,        // Animation plays multiple times
    mirror: true,       // Plays on scroll down and up
    offset: 100         // Trigger 100px before element visible
});
```

### Transition Speed (CSS)
```css
transition: all 0.3s ease;  /* 300ms - Standard speed */
```

---

## Color Combinations (Pre-tested)

### Dark Text on Light Background
```css
color: #6367FF;                    /* Primary text */
background-color: #FFDBFD;         /* Page background */
```

### Buttons
```css
background-color: #6367FF;         /* Normal state */
background-color: #8494FF;         /* Hover state */
color: #ffffff;                    /* Text */
```

### Badges
```css
background-color: #C9BEFF;         /* Accent color */
color: #6367FF;                    /* Primary text */
```

### Borders
```css
border-color: #C9BEFF;             /* Accent border */
box-shadow: 0 10px 30px rgba(99, 103, 255, 0.1);  /* Soft shadow */
```

---

## Troubleshooting Quick Tips

| Issue | Solution |
|-------|----------|
| Colors look different | Make sure you're using exact hex codes: #6367FF, #8494FF, #C9BEFF, #FFDBFD |
| Animations not working | Ensure both `includes/head_template.php` and `includes/footer_script.php` are included |
| Buttons don't have proper colors | Use `.btn-primary` class, not custom background colors |
| Page header appears wrong | Use `.page-header` class in a wrapping div |
| Tables don't look themed | Use `.table`, `.table-striped`, `.table-hover` classes together |

---

## File Locations Reference

```
Tortoise Management/
├── assets/css/
│   └── style.css                 ← Main theme CSS
├── includes/
│   ├── head_template.php         ← Include in <head>
│   └── footer_script.php         ← Include before </body>
├── header.php                    ← Navigation bar
├── index.php                     ← Dashboard (example)
└── [other pages with same structure]
```

---

## Typography

```css
font-family: 'Poppins', sans-serif;  /* Applied globally */

/* Font weights available: 300, 400, 600, 700 */
font-weight: 300;  /* Light */
font-weight: 400;  /* Regular (default) */
font-weight: 600;  /* Semibold */
font-weight: 700;  /* Bold */
```

---

**Tip:** Bookmark this page for quick reference while developing!
