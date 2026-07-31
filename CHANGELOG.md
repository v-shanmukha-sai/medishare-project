# MedShare - Change Log

Records every file creation, edit, and deletion. Append to this file on every change.

## 2026-07-31 - Initial website build

### Created
- `index.html` - Landing page: navbar, hero, feature cards, how-it-works, footer.
- `login.html` - Login form + demo quick-login buttons (admin/distributor/NGO).
- `register.html` - Registration form with account type toggle (Distributor/Organization).
- `dashboard.html` - Role-aware dashboard shell: sidebar, mobile nav, page area, order modal.
- `css/style.css` - Custom styles: auth background, hero gradient, sidebar, stat cards.
- `js/data.js` - Mock data: users, medicines, orders, notifications, audit logs, pending verifications.
- `js/app.js` - Client-side logic: role routing, page rendering, medicine search/filter, order modal, canvas chart, verification buttons.

### Edited
- `js/app.js` (after creation)
  - Fixed medicine table rendering on page navigation (added `renderMedTable()` call in `show()`).
  - Refactored `renderMedTable()` into a named function bound to the `change` event.
  - Added login/register form wiring; auth redirect now guarded to `dashboard.html` only.
  - Removed dead `input` listener.
  - Fixed syntax error (stray `)` from refactor) - verified with `node --check`.
  - Added `drawChart()` call on reports page render.

### Deleted
- None.

### Verified
- `node --check` passes on `js/app.js` and `js/data.js`.

## Future entries

Use this format:

```
## YYYY-MM-DD - Short description

### Created / Edited / Deleted
- `path` - What changed and why.
```
