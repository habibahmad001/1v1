# Plaid Navigation Child Theme

A professional WordPress child theme featuring a Plaid-inspired navigation system with mega menus, responsive design, and accessibility features.

## Table of Contents

1. [Features](#features)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Menu Setup](#menu-setup)
5. [Customization](#customization)
6. [File Structure](#file-structure)
7. [Behavior Explanations](#behavior-explanations)
8. [Testing Checklist](#testing-checklist)
9. [Troubleshooting](#troubleshooting)
10. [Browser Support](#browser-support)

## Features

### Desktop Navigation
- Hover-activated mega menus with configurable columns
- Smooth animations with proper timing curves
- Keyboard navigation support
- Focus management for accessibility
- Scroll-aware header with shadow effect

### Mobile Navigation
- Slide-in menu overlay
- Accordion-style submenu expansion
- Touch-optimized interactions
- Backdrop overlay with tap-to-close
- Smooth slide animations

### Accessibility
- ARIA attributes for all interactive elements
- Skip to main content link
- Full keyboard navigation
- Focus indicators
- Screen reader support
- Reduced motion support

### Technical Features
- Modular CSS architecture
- Efficient JavaScript with no dependencies
- WordPress best practices compliance
- Child theme structure for safe updates
- Translation-ready

## Installation

### Method 1: WordPress Admin

1. Upload the `plaid-nav-child` folder to `wp-content/themes/`
2. Navigate to Appearance > Themes
3. Activate "Plaid Navigation Child"
4. Configure menus (see [Menu Setup](#menu-setup))

### Method 2: FTP/SFTP

1. Upload the `plaid-nav-child` folder to `wp-content/themes/`
2. Log in to WordPress admin
3. Navigate to Appearance > Themes
4. Activate the theme

### Method 3: Direct Deployment

```bash
cd wp-content/themes/
git clone <repository-url> plaid-nav-child
```

## Configuration

### Theme Settings

Access navigation settings via Appearance > Customize > Site Identity:

1. **Login URL**: Configure the login page URL
2. **Login Button Text**: Customize login button text
3. **Contact URL**: Configure the contact page URL
4. **Contact Button Text**: Customize contact button text

### Menu Locations

The theme registers three menu locations:

- **Primary Navigation**: Main desktop and mobile navigation
- **Mobile Navigation**: Separate mobile menu (optional)
- **Footer Navigation**: Footer links (optional)

## Menu Setup

### Step 1: Create Menus

1. Navigate to Appearance > Menus
2. Create a new menu named "Primary Navigation"
3. Check "Primary Navigation" location
4. Save menu

### Step 2: Add Menu Items

1. Add pages, posts, or custom links
2. Organize items with drag-and-drop
3. Create nested items for submenus

### Step 3: Add Descriptions

For mega menu items with descriptions:

1. Click the arrow next to a menu item to expand options
2. Find the "Description" field
3. Add a brief description (up to 100 characters)
4. Save menu

### Step 4: Configure Mega Menu

1. Expand menu item options
2. Check "Enable mega menu" for items that should display as mega menus
3. Save menu

### Menu Structure Example

```
Primary Navigation
├── Products (Enable mega menu)
│   ├── Category 1
│   │   ├── Product A (with description)
│   │   └── Product B (with description)
│   └── Category 2
│       ├── Product C (with description)
│       └── Product D (with description)
├── Solutions (Enable mega menu)
│   ├── Use Case 1
│   └── Use Case 2
├── About
└── Contact
```

## Customization

### Colors

Modify CSS variables in `style.css`:

```css
:root {
	--nav-height: 72px;
	--nav-bg: #ffffff;
	--nav-text: #1a1a1a;
	--nav-accent: #0052ff;
	--nav-border: #e8e8e8;
}
```

### Animation Timing

Adjust in `assets/js/navigation.js`:

```javascript
config: {
	hoverDelay: 180,
	closeDelay: 320,
}
```

### Logo

1. Navigate to Appearance > Customize > Site Identity
2. Upload a logo image
3. Recommended size: 32x32px

## File Structure

```
plaid-nav-child/
├── style.css                    # Main stylesheet with CSS variables
├── functions.php                # Theme setup and navigation functions
├── screenshot.png               # Theme preview image
├── README.md                    # This file
├── assets/
│   ├── css/
│   │   └── navigation.css       # Additional navigation styles
│   ├── js/
│   │   └── navigation.js        # Navigation JavaScript module
│   └── images/                 # Image assets
├── inc/
│   ├── class-plaid-nav-walker.php      # Desktop menu walker
│   ├── class-plaid-mobile-walker.php  # Mobile menu walker
│   └── navigation-helpers.php          # Helper functions
├── parts/
│   └── header.html              # Header template part
├── templates/
│   ├── index.html               # Index template
│   ├── page.html                # Page template
│   └── single.html              # Single post template
└── template-parts/              # Additional template parts
```

## Behavior Explanations

### Desktop Behavior

#### Hover States
- When hovering over a menu item with children, the system waits 180ms (hover intent delay)
- After the delay, the mega menu fades in and slides down
- The menu remains open while the mouse is over the parent link or the mega menu
- When the mouse leaves, there's a 320ms delay before closing to allow for accidental movements

#### Click States
- Clicking a parent item on mobile toggles the submenu
- On desktop, clicks navigate to the parent page (if URL exists)

#### Keyboard Navigation
- Tab: Move through menu items
- Enter/Space: Activate link or toggle menu
- Escape: Close all open menus
- Arrow keys: Navigate within open menus

### Mobile Behavior

#### Menu Opening
- Clicking the hamburger button slides the menu in from the right
- The backdrop fades in simultaneously
- Body scroll is locked while menu is open

#### Submenu Behavior
- Clicking an arrow button expands the submenu with a slide-down animation
- The arrow rotates to indicate open state
- Multiple submenus can be open simultaneously

#### Menu Closing
- Clicking the backdrop closes the menu
- Pressing Escape closes the menu and returns focus to toggle button
- Clicking any navigation link closes the menu after a brief delay

### Animation Logic

#### Desktop Animations
- Transform: translateY(-8px) to translateY(0)
- Opacity: 0 to 1
- Duration: 200ms
- Easing: ease-out

#### Mobile Animations
- Menu slide: translateX(100%) to translateX(0)
- Submenu: max-height 0 to calculated content height
- Duration: 300ms for menu, 200ms for submenus
- Easing: ease-out

#### Reduced Motion
- All animations respect `prefers-reduced-motion` media query
- When reduced motion is preferred, transitions are disabled

## Rendering Architecture

### Centralized Rendering Function

The navigation system uses a single entry point:

```php
<?php render_custom_navigation(); ?>
```

This function:
1. Renders the fixed header with logo and navigation
2. Handles desktop navigation output
3. Handles mobile navigation overlay
4. Manages state cookies for mobile menu

### Walker Classes

#### Plaid_Nav_Walker
- Extends WordPress Walker_Nav_Menu
- Handles mega menu HTML structure
- Adds ARIA attributes dynamically
- Calculates column counts based on child items

#### Plaid_Mobile_Walker
- Extends WordPress Walker_Nav_Menu
- Handles accordion submenu structure
- Adds mobile-specific markup
- Manages expanded/collapsed states

## Testing Checklist

### Desktop Testing
- [ ] All menu items display correctly
- [ ] Hover delays feel natural
- [ ] Mega menus position correctly
- [ ] Menu columns align properly
- [ ] Descriptions display when added
- [ ] Links navigate to correct pages
- [ ] Focus states are visible
- [ ] Keyboard navigation works
- [ ] Escape closes all menus
- [ ] Scroll adds shadow to header

### Mobile Testing
- [ ] Hamburger button opens menu
- [ ] Menu slides in smoothly
- [ ] Backdrop appears correctly
- [ ] Submenu toggles work
- [ ] Multiple submenus can be open
- [ ] Links navigate correctly
- [ ] Backdrop click closes menu
- [ ] Escape key closes menu
- [ ] Body scroll is locked
- [ ] CTAs display in footer

### Accessibility Testing
- [ ] Skip link appears on focus
- [ ] All interactive elements are focusable
- [ ] Focus indicators are visible
- [ ] ARIA labels are present
- [ ] Screen reader announces menu states
- [ ] Keyboard can access all features
- [ ] Reduced motion is respected
- [ ] Color contrast meets WCAG AA

### Cross-Browser Testing
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

## Troubleshooting

### Menu Not Displaying

**Problem**: Navigation menu doesn't appear on the site.

**Solutions**:
1. Ensure menu is assigned to "Primary Navigation" location
2. Check that menu items are published
3. Verify the theme is properly activated
4. Clear browser and server cache

### Mega Menu Not Working

**Problem**: Mega menus don't appear on hover.

**Solutions**:
1. Check "Enable mega menu" is checked for parent items
2. Verify child items exist under parent
3. Ensure JavaScript is loading (check browser console)
4. Check for CSS conflicts with other plugins

### Mobile Menu Won't Close

**Problem**: Mobile menu stays open after clicking links.

**Solutions**:
1. Check if JavaScript is loading properly
2. Look for console errors
3. Verify no other plugins are hijacking click events
4. Clear browser cache

### Styling Issues

**Problem**: Navigation looks broken or misaligned.

**Solutions**:
1. Check for CSS conflicts with other plugins
2. Verify parent theme is Twenty Twenty-Five
3. Check browser console for CSS errors
4. Test with all plugins disabled

### Menu Items Not Saving

**Problem**: Menu configuration changes don't save.

**Solutions**:
1. Check file permissions on theme folder
2. Verify WordPress has write access
3. Increase PHP memory limit
4. Check for plugin conflicts

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- iOS Safari 14+
- Chrome Mobile 90+

## Performance Considerations

### Optimization Features
- CSS transforms for GPU-accelerated animations
- Debounced hover events to reduce reflows
- Passive event listeners where applicable
- Minimal DOM manipulation

### Recommended Practices
- Limit menu depth to 3 levels
- Keep descriptions under 100 characters
- Use optimized images for logos
- Enable caching for static assets

## Support

For issues, questions, or contributions, please refer to the theme documentation or contact the development team.

## Changelog

### Version 1.0.0
- Initial release
- Plaid-inspired navigation system
- Desktop mega menus
- Mobile slide-in navigation
- Full accessibility support
- WordPress best practices compliance
