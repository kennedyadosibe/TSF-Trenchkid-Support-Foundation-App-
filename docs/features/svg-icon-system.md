# SVG Icon System

## Summary

Public pages now use local SVG icons instead of emoji characters. This keeps the interface consistent across browsers, devices, and hosting environments.

## Files

- `css/style.css` defines the shared `.tsf-icon` styling.
- `js/components.js` stores the local SVG icon registry and hydrates icon placeholders.
- Public pages use `<span class="tsf-icon" data-icon="..."></span>` where icons are needed.

## Notes

- Icons are local inline SVGs, so no external icon CDN is required.
- Shared header and footer icons are rendered through `js/components.js`.
- Dynamic impact content from `js/page-content.js` also hydrates SVG icons after it updates the DOM.
