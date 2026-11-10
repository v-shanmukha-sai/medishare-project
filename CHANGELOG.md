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

## 2026-08-02 - Phase 1: Deployment, database, services

### Created
- `database.sql` - Full schema: 9 tables (users, distributors, ngos, medicines, orders, order_items, transactions, notifications, reviews, audit_logs) + seed data matching the old mock data. DB name `medshare`, InnoDB, utf8mb4. Passwords bcrypt-hashed (`admin123`).
- `.htaccess` - Blocks direct download of `database.sql`.

### Deleted
- `README.md` - Was junk (UTF-16 garbage, "# project" repeated).
- `.git/`, `pages/`, `images/` - Empty/irrelevant in the deployed copy.
- MySQL data-dir replication junk (`*relay-bin*`, `master-*.info`, `multi-master.info`) - corrupt files from an old session were aborting mysqld startup ("Failed to initialize multi master structures").

### Edited
- None (frontend untouched this phase).

### Deployed
- Project copied to `C:\xampp\htdocs\med-donation\` (Apache docroot).
- Apache + MySQL started; `database.sql` imported; verified `http://localhost/med-donation/index.html` returns 200.
- Home-dir copy `C:\Users\volla\med-donation\` now stale - source of truth is the htdocs copy.

### Login credentials (seed data)
- Admin: `admin@medshare.com` / `admin123` (verified)
- Distributor: `medplus@medshare.com` / `admin123` (verified)
- NGO: `hope@medshare.com` / `admin123` (verified)
- Unverified (for testing admin approve): `carefirst@medshare.com`, `eldercare@medshare.com`

## 2026-08-02 - Phase 2: PHP core + authentication

### Created
- `config/db.php` - PDO connection (root, localhost, db `medshare`) + helpers: `respond()` (JSON out), `body()` (JSON in), `audit_log()`.
- `auth/register.php` - POST JSON {name, email, phone, address, role, orgType, password}. Validates, rejects duplicates (409), bcrypt-hashes password, inserts user + distributors/ngos row as unverified, audit log entry.
- `auth/login.php` - POST JSON {email, password}. `password_verify()`, blocks unverified accounts (403), `session_regenerate_id()`, sets `$_SESSION['user']` {id, name, role, org_type}, audit log.
- `auth/logout.php` - Destroys session.

### Edited
- `database.sql` - Added missing `org_type` column to `ngos` table (registration for org accounts was failing with 500).
- Live DB: `ALTER TABLE medshare.ngos ADD COLUMN org_type ...` to match.

### Verified (via Apache + curl)
- login ok (200), wrong password (401), unverified account (403), register org + distributor (200), duplicate email (409), logout (200).
- Test users cleaned up after verification.

## 2026-08-02 - Phase 3: API endpoints

### Created
- `api/medicines.php` - GET: list with search (q), category (cat), shelf-life months (shelf) filters; role-scoped (distributor sees own only). POST: add medicine (distributor only), audit logged.
- `api/orders.php` - POST: place order (org only) - `SELECT ... FOR UPDATE` stock check, decrement, order + order_item + pending transaction + notifications to org & distributor, audit logged, all in a transaction. GET: role-scoped order list with items.
- `api/notifications.php` - GET: user's notifications, newest first.
- `api/audit.php` - GET: last 50 audit logs (admin only).
- `api/reports.php` - GET: dashboard stats (total, low stock, expiring <=3 months, completed revenue, stock by category, expiry risk list).
- `api/verify.php` - GET: pending unverified users (admin only). POST: approve/reject -> updates users + distributors/ngos verified flags, notification to user, audit log.

### Edited
- `config/db.php` - Added `current_user()` (session guard) and `require_role()` helpers.

### Verified (via Apache + curl, cookie sessions)
- Admin: reports, audit, pending list, approve -> user can then log in.
- Distributor: own-medicines scoping, add medicine.
- NGO: search/filter, place order (stock 600->550 confirmed in DB), insufficient stock 409.
- Notifications created for both parties on order and on approval.
- All test data (order #104, test medicine, approval side-effects) removed; DB matches seed.

## 2026-08-02 - Phase 4: Frontend rewired to live API

### Created
- `auth/session.php` - GET: returns current session user or 401 (used by dashboard on load).

### Edited
- `js/app.js` - Full rewrite: mock-data reads replaced with `fetch()` to all Phase 3 endpoints. Auth pages POST to login/register endpoints. Dashboard: session check on load (redirects to login if 401), role-scoped nav, live overview stats, server-side medicine search/filter, live orders/notifications/audit/verify pages, canvas chart from real data, add-medicine modal (distributor), order modal posts to API, notification badge count.
- `login.html` - Removed demo quick-login buttons (now real auth).
- `dashboard.html` - Added Add Medicine modal; removed `js/data.js` include.
- `api/orders.php` - Orders list now joins buyer name (users) instead of raw user id.

### Deleted
- `js/data.js` - Mock data no longer used.

### Verified (browser-flow simulation with cookie sessions)
- Unauthenticated session.php -> 401; admin/distributor/NGO logins; audit + pending list as admin; distributor sees own medicines; NGO search; order placed (stock 1200->1190 in DB, 2 notifications created), then test order removed and stock restored.

## 2026-08-02 - Phase 5: Storefront (e-commerce) + Distributor Management

### Created
- `store.html` - Public catalog: search, category, shelf-life range (1-8 months) filters, medicine cards (image/placeholder, price, shelf badge, stock, distributor link, Add to Cart).
- `medicine.html` - Public detail page: full medicine info, distributor box with rating, reviews, Add to Cart.
- `distributor.html` - Public distributor profile: license, contact, rating, their medicines.
- `cart.html` - Cart + checkout: qty edit, remove, total, payment method (Bank Transfer/UPI/Cash), reference field. Ordering restricted to logged-in verified orgs (others see notice).
- `js/cart.js` - localStorage cart + shared navbar (cart badge, login/user state).
- `js/store.js` - Storefront page logic (catalog/detail/profile/cart).
- `api/reviews.php` - GET reviews by distributor (public), POST review (org, only on own Delivered orders).
- `api/distributors.php` - GET admin list (all distributors + meds/orders counts), GET public profile, POST remove (admin; blocked with 409 if distributor has medicines/orders).
- `uploads/medicines/` - Image upload directory.

### Edited
- `api/medicines.php` - GET now public; `shelf_min`/`shelf_max` params (replaces `shelf`); `?id=` single-medicine detail with distributor info + rating; POST switched to multipart with optional image upload (JPG/PNG/WEBP, max 2MB, random filename).
- `api/orders.php` - POST accepts cart `items[]`; groups by distributor (one order per distributor, schema constraint); transaction records method + reference; GET joins buyer name.
- `api/distributors.php` - Remove flow deletes audit_logs + notifications first (FK fix).
- `database.sql` + live DB - `medicines.image`, `transactions.reference` columns.
- `dashboard.html` - Distributors nav item (admin), Review modal (star picker), image file input in Add Medicine modal.
- `js/app.js` - Distributors management page (approve/reject/remove), review modal wiring, FormData image upload, shelf_max param, orders POST via items[].

### Fixed
- Distributor removal failed with FK error on audit_logs - now cleans dependent rows first.
- Dashboard orders now shows buyer name (users JOIN).

### Verified (curl + PowerShell, cookie sessions)
- Public browse (no login) for catalog/detail/profile; multi-distributor cart checkout splits into one order per distributor (2 orders, correct totals/transactions with method+reference); review blocked on non-delivered order (403); image upload saves file + DB path; remove works (blocked 409 for distributors with meds/orders); temp distributor registered+removed cleanly.
- All test data cleaned; DB back to seed (8 meds, 3 orders, 7 users).

### Deliberately skipped
- Real payment gateway (needs merchant account/keys) - payment methods recorded manually, status stays Pending.

## 2026-08-03 - Fix: registration broken from the UI

### Root cause
`register.html` radio buttons sent `value="Distributor"` / `value="NGO"`, but `auth/register.php` only accepted lowercase `'distributor'` / `'org'`. Every registration from the browser was rejected with 422 "Invalid account type." (my earlier tests used lowercase directly, so they passed and masked it). No new users ever reached the DB.

### Edited
- `register.html` - Radio values changed to `distributor` / `org` (display labels unchanged).
- `auth/register.php` - `$role = strtolower(trim(...))` + `ngo -> org` alias so legacy/capitalized values can never break registration again.

### Verified
- Replayed exact UI payloads: role `Distributor` (200), `org` (200), legacy `NGO` (200).
- Full flow: register -> admin approve -> login as the new org (200).
- Test users (14-16) removed; DB back to seed (7 users, CareFirst pending).

## 2026-08-03 - Fix: site down + distributor profile showing wrong medicines

### Root cause 1: services dead
Apache and MySQL had been started manually (Start-Process), not as Windows services - a reboot killed them and the whole site was unreachable ("website not working").
Fix: installed both as services:
- MySQL: `mysqld --install medshare-mysql`
- Apache: `httpd.exe -k install` (service `Apache2.4`)
Both now auto-start on boot. Restarted manually-started processes and verified RUNNING + site 200.

### Root cause 2: wrong medicines on distributor profile
`api/distributors.php` public profile filtered `medicines WHERE distributor_id = users.id`, but that column references `distributors.id`. Every profile showed another distributor's medicines.
Fix: select `d.id AS dist_id` and filter on it. Verified: MedPlus (id=2) now shows its own meds (1, 2, 5).

### Also
- Restored drifted seed data: `medicines.id=2` stock 550 -> 600.
- Full-site smoke test: all 12 pages 200, admin/distributor/NGO logins, all API endpoints, public search/reviews/profile - all green.

## 2026-08-03 - Fix: medicines had no images (NGO saw placeholders)

### Root cause
All 8 seed medicines had `image = NULL`, so the store/detail pages showed only placeholder icons. (The user's own uploaded medicine worked — its file and URL were verified 200.)

### Created
- `uploads/medicines/*.svg` - 8 capsule images (category-colored, name label) generated as SVG text (GD extension not available). Written without UTF-8 BOM (browsers can reject BOM'd SVG).

### Edited
- Live DB: `UPDATE medicines SET image=...` for ids 1-8.
- `database.sql` - Seed now includes image paths (note: `uploads/medicines/*.svg` must be copied with the project for a fresh install).

### Verified
- SVG URLs return 200, first bytes clean (`<sv`), API returns image paths for all 9 medicines (8 seed + user's uploaded one).

## 2026-08-03 - Fix: Review modal never opened (ReferenceError)

### Root cause
`js/app.js` review-button handler used `forEach(s => ..., s.classList.add(...))` - the second argument is `forEach`'s `thisArg`, evaluated in a scope with no `s`, throwing `ReferenceError`. The handler died before `Modal.show()`, so clicking "Review" did nothing.

### Edited
- `js/app.js` - Star reset changed to a proper block `{ s.textContent = '☆'; s.classList.remove('text-warning'); s.classList.add('text-secondary'); }`. Also clears stale `text-warning` class so stars reopen clean.

### Verified
- `node --check` passes.

## 2026-10-16 - UI redesign to match "UI reference.mp4" (green + cream MediShare theme)

Video shows: cream background, deep green (#0B5C4E) primary, Inter-style font, bold display headings, pill buttons/inputs, rounded cards with light borders, shelf-life pills (orange/green), segmented Organization|Distributor register control, footer with 3 columns, brand renamed "MediShare", prices in ₹.

### Created
- None (no new files; design tokens live in `css/style.css`).

### Edited
- `css/style.css` - Full rewrite: design system (colors, Inter, pills, cards, badges), Bootstrap 5.3 var overrides (btn/form/card/badge/table/alert/modal), site nav, hero, med-card, segmented control, auth layout, detail pages, cart, dashboard sidebar/stat cards, footer.
- `index.html` - New hero ("Good medicine shouldn't go to waste." + badge + CTAs + inline SVG donation-box art), "Expiring soon, still safe" featured grid (JS), restyled stats/features (id=about)/how-it-works (id=how), JS nav + footer placeholders.
- `login.html` / `register.html` - Cream pages with big headings, pill inputs, segmented role toggle (Organization default; `?role=distributor` preselects Distributor), shared nav + footer.
- `store.html` / `medicine.html` / `distributor.html` / `cart.html` - Restyled wrappers/layouts, `#nav` + `#footer` placeholders, Inter font.
- `dashboard.html` - Dark-green sidebar, brand mark logo, restyled modals (centered), page-title; sidebar "Sign out" now hits `auth/logout.php` (previously linked to index.html without destroying the session).
- `js/cart.js` - New nav markup (MediShare brand, links to store/#how/register?role=distributor/#about, Dashboard/Sign out when signed in, Cart pill) + `renderFooter()` on all storefront pages; `money()` helper (₹).
- `js/store.js` - New `cardHTML()` (uppercase category + shelf pill + name + batch + stock + price + Add pill), used by store, home featured (top 4 by shelf life), distributor profile; restyled medicine detail/distributor profile/cart templates; ₹ prices.
- `js/app.js` - ₹ money() in dashboard tables/modals/stat cards; register `?role=` preselect + orgType visibility sync on load; canvas chart recolored to theme green/gray.

### Deleted
- None.

### Verified
- `node --check` passes on all 3 JS files.
- Playwright pass: all pages load, all internal links return 200 (home/store/login/register/medicine/cart/distributor/dashboard), no JS page errors (401s are guest `auth/session.php`, expected).
- Screenshots reviewed for home, store, login, register (org+dist), medicine detail, cart, distributor profile, admin dashboard (overview + medicines).

### Notes
- Prices now display as ₹ (matches reference; seed data values like ₹20 are rupee-scale). Revert one helper (`money()` in `cart.js`/`app.js`) for $ display.
- Struck-through MRP + "% off" badge from the reference video skipped: `medicines` table has no MRP column; add one (schema + form + API) if wanted.

## 2026-10-16 - Store redesign per "med display.png" + camera capture in Add Medicine

Reference image shows: "Medicines" heading, search + "Expiring soonest" sort, category chips (All active = dark green pill), 2-column cards with day-precision shelf pills ("1 mo 15 d left") and MRP strike-through + "% off".

### Created
- None.

### Edited
- `database.sql` - `medicines.mrp DECIMAL(10,2) NULL` column added (after price); seed INSERT updated with MRP values (price x3).
- Live DB - `ALTER TABLE medicines ADD COLUMN mrp ... AFTER price`; `UPDATE ... SET mrp = ROUND(price*3,2)`.
- `api/medicines.php` - POST accepts optional `mrp` (ignored if <= price); INSERT includes mrp. (GET uses `m.*`, so mrp is returned automatically.)
- `api/distributors.php` - profile medicines SELECT now returns `mrp` and `batch_no` (cards need both).
- `store.html` - New layout: "Medicines" heading, search input + sort select (Expiring soonest / Price low-high / high-low), category chips row; old category/shelf min-max selects and Clear button removed.
- `js/store.js` - `cardHTML()` 2-col (`col-md-6`), MRP strike + calculated % off, day-precision shelf pill from expiry (months < 3 = orange), chips render/filter, client-side sort; detail page shows % off; dead `shelfMonths` fallback removed.
- `dashboard.html` - Add Medicine: MRP field (Price/MRP/Stock/Batch row), "Take photo" button + thumbnail preview, new `#cameraModal` (live `<video>` + Capture).
- `js/app.js` - Camera capture via `getUserMedia({ facingMode: 'environment' })` -> canvas -> JPEG blob; captured photo overrides the file input on submit; stream stopped on modal close; camera state reset when reopening Add Medicine; `mrp` appended to FormData.
- `css/style.css` - `.chip`/`.chips`, `.sort-select`, `.med-off`, `.cam-preview`, `#camVideo` styles.

### Deleted
- Live DB: test row "Camera Test Syrup" (created during verification) and its uploaded image.

### Verified
- `node --check` passes (app.js, store.js).
- Playwright (fake webcam flags): chips = 8 (All + 7 categories), chip filter works (Antibiotic -> 2 cards), price-desc sort correct, % off labels render ("₹30 67% off"), fake camera streamed (`videoWidth > 0`), capture -> thumbnail + note, medicine saved with camera photo into table, no JS errors.
- Screenshots reviewed vs reference image (store grid, camera modal, photo-attached state).

## 2026-10-20 - Folder cleanup + Profile page, icon nav, cart empty state

References: `cart_page.jpg`, `medicine_display.jpg`, `profile.jpg` (placed in project root).

### Created
- `api/profile.php` - GET: full profile (phone/address/verified/member-since), distributor `license_no`, role-scoped order count; POST: update name/phone/address, refresh session name, audit log.
- `profile.html` - "My profile" page: role + verification pills, Orders placed card (links `dashboard.html?page=orders`), Account type card, Details list + Edit modal, Sign out.

### Edited
- `js/cart.js` - Nav per reference: box icon -> store, dashboard icon (logged in, md+), person + Profile -> `profile.html`, Cart pill; guests still get Sign in/Register. Nav "Sign out" button removed (logout now on profile page).
- `js/store.js` - Profile page load/render/edit-modal logic; cart page toggles inline "Your cart is empty. Browse medicines" and hides table/checkout when empty.
- `cart.html` - Heading "Cart"; added `#cartEmpty`, wrapped grid in `#cartFull`.
- `css/style.css` - Sticky footer (body flex column, footer pushed down), `.icon-link`, `.pill` variants, profile card/details styles, `.cart-empty`, mobile nav tweaks (Profile label hidden <480px).
- `dashboard.html` - "My profile" links in sidebar + user dropdown.
- `js/app.js` - Dashboard honors `?page=` query (e.g. `?page=orders`).

### Deleted
- 8 obsolete presentation helper scripts (`check_ppt.ps1`, `verify_ppt.ps1`, `verify_ppt_content.ps1`, `create_presentation*.ps1`).
- Moved to `C:\Users\volla\med-donation-archive\` (archived, not deleted): `MedShare_Presentation*.pptx`, `PRESENTATION_CONTENT.md`, `UI reference.mp4`, `med display.png`.

### Verified
- `php -l api/profile.php` clean; `node --check` on cart.js, store.js, app.js.
- Playwright: guest cart empty state; login (hope@medshare.com) -> profile email/role/verified/orders=2/details render, edit phone saved and restored via modal, Sign out -> `auth/logout.php`; `dashboard.html?page=orders` opens Orders; nav hrefs resolve; no JS page errors.
- Screenshots reviewed vs references: profile, cart (desktop + mobile), store mobile, dashboard (sidebar intact, footer pinned).

### Notes
- Reference profile shows "Organization, Distributor"; schema stores one role per account, so the pill shows the user's actual role/org type.
- Reference catalog prices/batches not imported - live DB data kept.
- `cart_page.jpg`, `medicine_display.jpg`, `profile.jpg` left in project root as reference material.

## 2026-10-20 - Fix: empty cart, stale UI, dead profile Edit

Reported: (1) added items not listed in cart, (2) new UI only visible on profile page, (3) profile Edit button dead. Root causes: browsers cached the pre-profile `store.js`/`cart.js`/`style.css` (old cart code never un-hides the new `#cartFull` markup; no profile handlers), and the cart fetched medicines via the list endpoint, which is distributor-scoped.

### Edited
- `js/cart.js` - `getCart()` normalizes legacy string ids/qtys to numbers.
- `js/store.js` - Cart now fetches each item via `api/medicines.php?id=` (list endpoint only returns a distributor's own medicines, so mixed carts rendered blank); profile Edit/Save listeners bound unconditionally (no longer depend on profile fetch completing).
- All 9 HTML pages - local assets versioned (`css/style.css?v=20261020`, `js/cart|store|app.js?v=20261020`) to bust stale browser caches.

### Verified
- `node --check` cart.js/store.js; Playwright: guest adds 2 -> cart shows 2 rows ₹11.90; distributor with guest-added mixed cart sees both rows (previously invisible); Edit modal opens, saves, restores; new nav renders on index/store/medicine/cart/profile/distributor; no page errors.

## 2026-10-20 - Fix: logout page, distributor order acceptance

Reported: clicking Sign out showed a raw JSON page; distributors had no way to accept an order (only the invoice button).

### Edited
- `auth/logout.php` - Now clears the session and redirects to `index.html` (previously echoed `{"ok":true}`).
- `api/orders.php` - POST accepts `action: accept|reject` + `order_id` for the owning distributor: only `Pending` orders; accept -> `Accepted` + notification to the org; reject -> `Cancelled`, restores reserved stock, cancels the transaction, notifies the org. Order creation unchanged (still org-only).
- `js/app.js` - Orders table: distributor sees **Accept** / **Reject** buttons on `Pending` orders (reject asks for confirm); badges now colour `Accepted` (info), `Cancelled` (danger), `Delivered` (success).
- `database.sql` + live DB - `orders.status` ENUM gains `Accepted`; `transactions.status` ENUM gains `Cancelled` (writes to undefined ENUM values were silently storing empty strings, so accepted orders had a blank status).

### Verified
- `php -l api/orders.php`, `node --check js/app.js`.
- Playwright (hope -> order, medplus -> accept): status shows **Accepted**; reject path shows **Cancelled**; stock before/after reject 1199 -> 1197 -> 1199 (qty 2 restored exactly); logout redirects to `index.html` and dashboard then bounces to `login.html`; test orders removed afterwards (remaining orders: seed + user's #108 pending).

## 2026-10-20 - Registration credentials + city (distributor license / NGO registration no.)

Registration now requires identifying credentials per role: distributors supply a drug license number (Form 20B/21B); organizations supply an NGO/Trust registration number (Darpan ID / society / trust deed). Contact phone and city are required too.

### Edited
- `register.html` - Added required city and credential fields; role toggle swaps placeholders + hint text (business name / organization name, drug license / NGO-trust registration); phone now required.
- `js/app.js` - `syncRoleFields()` updates name/credential placeholders and hint by role; registration payload sends `city` and `credential_no`; reset re-syncs labels.
- `auth/register.php` - Requires phone, city, credential (<=60 chars); stores `city` on `users`, `license_no` on `distributors`, `registration_no` on `ngos`; validates `orgType` against allowed enum values.
- `database.sql` + live DB - `users.city VARCHAR(80)` added after `address`.
- `api/profile.php` - Returns `city` and `registration_no` (orgs); profile shows City row and falls back to the registration number for the "License / reg. no." row.
- `api/verify.php` + `js/app.js` - Admin verification queue now shows contact phone, city, and license/registration number for each pending account.
- All HTML - asset version `?v=20261022`.

### Verified
- `php -l` (register, profile, verify), `node --check` (app.js, store.js).
- Playwright: distributor registration (placeholders/hints, orgType hidden) -> saved license `TS-20B-21B-778899` + city Warangal; org registration -> saved Darpan `TS-DARPAN-554433` + city Hyderabad; missing credential rejected by API; pending accounts cannot log in; admin verify page shows both credentials + cities; hope's profile shows `NGO-HOPE-2023`; test users removed afterwards.

## 2026-10-20 - Org/distributor land on Medicines; guest nav hides Cart

### Edited
- `js/app.js` - `rolePages()` drops `overview` for org + distributor; dashboard default page is now **Medicines** for those roles (`?page=` still honored; admin keeps Overview default).
- `js/cart.js` - Cart pill only rendered for signed-in users; guests see Sign in / Register (and the store icon).
- All HTML - asset version `?v=20261023`.

### Verified
- `node --check` app.js/cart.js; Playwright: guest store nav has no cart pill; org (hope) and distributor (medplus) land on Medicines with no Overview nav item; admin lands on Overview and still uses `?page=orders`; signed-in storefront shows the cart pill; no JS errors.

## 2026-10-20 - Sign-in lands on store medicines; distributors get no add-to-cart/cart

### Edited
- `js/app.js` - Successful login redirects org + distributor to `store.html` (the store-format Medicines list); admin still lands on `dashboard.html`.
- `js/cart.js` - Cart pill rendered only for admin + org (hidden for guests and distributors); `body.is-distributor` class set from session.
- `css/style.css` - `.is-distributor .add-btn` hides all add-to-cart buttons (cards, detail page, featured) for distributors.
- All HTML - asset version `?v=20261024`.

### Verified
- `node --check` app.js/cart.js; Playwright: hope lands on store with cart + 9 add buttons; medplus lands on store with 0 cart pill and 0 visible add buttons (dashboard still opens Medicines with the Add-medicine tool); admin lands on dashboard; guest has no cart; no JS errors.

## 2026-10-20 - Bottom mini-cart bar (org only)

### Edited
- `js/cart.js` - New fixed bottom bar, created only for signed-in orgs: shows the added medicine name, minus/plus quantity stepper, "Go to cart" button, close X. Qty stays in sync with localStorage; hitting 0 removes the item and hides the bar.
- `js/store.js` - Add buttons (store grid, home featured, medicine detail) now call `showMiniCart(id, name)`.
- `css/style.css` - `.mini-cart` fixed bottom bar styles + mobile wrapping.
- All HTML - asset version `?v=20261025`.

### Verified
- `node --check` cart.js/store.js; Playwright (org hope, mobile viewport): bar shows name + qty 1, plus -> 2, minus -> 1, badge consistent, "Go to cart" -> cart.html, detail-page add also shows bar, minus to 0 removes item and hides bar; guests and distributors never get the bar (distributor has no add buttons).

## 2026-10-20 - Order success returns to the store

### Edited
- `js/store.js` - After placing an order (cart checkout), redirect goes to `store.html` instead of `dashboard.html`; success message says it's redirecting to medicines.
- All HTML - asset version `?v=20261026`.

### Verified
- `node --check js/store.js`; Playwright: hope places an order -> lands on `store.html`; test order removed after verification.

## 2026-10-20 - Expired medicines leave stock + distributor notified

### Edited
- `api/medicines.php` - GET now runs an expiry sweep: for every medicine past its expiry with stock left, stock is set to 0 and a notification is sent once to the distributor who listed it ("Your medicine 'X' (Batch ...) expired on ... and was removed from stock."). Public listing only returns non-expired medicines. POST rejects an expiry date in the past.
- `api/orders.php` - Order placement re-checks expiry (`FOR UPDATE`) and rejects expired medicines even from a stale cart/API call.

### Verified
- `php -l` both files; Playwright/API: previously expired seed medicines (Amoxicillin #7, paracetamol #11) were zeroed and hidden (store now 7 cards), one notification per distributor (no duplicates on repeat sweeps), direct order for an expired id returns "Amoxicillin 250mg has expired and is no longer available."; no JS errors.

## 2026-10-20 - Dashboard restyled to match index.html (storefront look)

### Edited
- `dashboard.html` - Dark sidebar shell replaced with the storefront layout: site nav + footer (via `js/cart.js`), cream page with "My dashboard" eyebrow, large title, "signed in as" line, Profile/Sign out actions, and a horizontal pill tab bar (Overview/Medicines/Orders/Notifications/Reports/Distributors/Audit/Verification) replacing sidebar links. Modals unchanged.
- `css/style.css` - Removed dead `.sidebar`/`.page-title` styles; added `.dash-eyebrow`, `.dash-title`, `.dash-tabs`/`.dash-tab` pills (active = green, badge styling, horizontal scroll on phones).
- `js/app.js` - Dropped the removed mobile `#userNameM` element reference.
- All HTML - asset version `?v=20261027`.

### Verified
- `node --check js/app.js`; Playwright: admin lands on Overview with 8 tabs, tab clicks switch title/active state and render content; org and distributor see only their 3-4 tabs (Overview hidden) and land on Medicines; distributor keeps "+ Add"; nav/footer render on desktop and mobile; no JS errors.

## 2026-10-21 - Cart quantity 0 keeps the item

### Edited
- `js/cart.js` - `setQty` clamps at 0 instead of removing the item; reducing to 0 leaves the medicine in the cart with count 0 (mini cart bar stays visible showing 0; trash button still removes).
- `js/store.js` - Cart quantity input `min` 0; "Place Order" only submits rows with qty > 0 and shows "Set a quantity above 0 for at least one medicine." when all are 0.
- All HTML - asset version `?v=20261028`.

### Verified
- Playwright (org): mini cart minus to 0 keeps bar with qty 0; cart row persists at qty 0, total ₹0, badge 0; order blocked with warning; back to qty 1 restores total. No JS errors.

## 2026-10-21 - Remove cart pill from admin

### Edited
- `js/cart.js` - Cart pill now renders only for organization accounts; admin and distributor navs no longer show it.
- All HTML - asset version `?v=20261029`.

### Verified
- Playwright: admin lands on dashboard with no Cart pill; org still sees Cart. No JS errors.

## 2026-10-21 - Unread notification count clears on visit

### Edited
- `api/notifications.php` - POST `{action:'read_all'}` marks the user's unread notifications as read and returns the count marked.
- `js/app.js` - Bell badge now counts unread notifications only and hides at 0; opening the Notifications tab marks everything read and clears the badge immediately.
- All HTML - asset version `?v=20261030`.

### Verified
- Playwright (admin): badge showed 2 unread; opening Notifications hid the badge and DB `is_read` became 1; reload kept badge hidden. Test notification removed.

## 2026-10-21 - New home hero image (redistribution / low-cost medicines)

### Added
- `img/hero-redistribution.jpg` - Photo of volunteers handling boxes labelled "Medicine" and "Food" during charity packing (Pexels, photo 6646880 by RDNE Stock project, free license), 1200x900.

### Edited
- `index.html` - Inline SVG donation-box art replaced with the photo (`img/hero-redistribution.jpg`, descriptive alt, width/height, `fetchpriority=high`).
- `css/style.css` - `.hero-art`: 4:3 aspect ratio, `object-fit: cover`, `height: auto` so the image fills the hero column without distortion.
- All HTML - asset version `?v=20261031`.

### Verified
- Playwright: image loads 1200x900 into a 624x468 box on desktop, no console or 404 errors; hero screenshot checked.

## 2026-10-21 - Dashboard icon visible on mobile

### Edited
- `js/cart.js` - Removed `d-none d-md-inline-flex` from the dashboard icon link so it shows in the nav on phones for every signed-in role.
- All HTML - asset version `?v=20261032`.

### Verified
- Playwright (distributor, 390x844): dashboard (speedometer) icon visible in mobile nav, click opens dashboard; no JS errors.

## 2026-10-21 - Toast feedback on actions

### Edited
- `js/cart.js` - Added shared `toast(msg, type)` helper: Bootstrap toast stack (top-right desktop, full-width top on phones), auto-dismiss ~2.6s, closes on X, safe text rendering.
- `js/store.js` - Toasts for add to cart (with medicine name), remove from cart, profile updated, order placed / zero-quantity warning / order errors. Replaced native `alert()`s.
- `js/app.js` - Toasts for approve/reject account, remove distributor, accept/reject order, dashboard order placed, medicine added, review submitted, plus validation/auth errors. Removed remaining `alert()` calls.
- `css/style.css` - `.toast-stack` fixed positioning above nav/modal backdrop; mobile full-width.
- All HTML - asset version `?v=20261033`.

### Verified
- Playwright: add-to-cart, remove-from-cart and profile-save toasts show correct text; mobile toast spans 16px margins; no JS errors.

## 2026-10-21 - Guest add-to-cart guard + responsiveness fixes

### Edited
- `js/store.js` - Clicking Add while signed out now shows the toast "Sign in to add medicines" and leaves the cart untouched (store grid, home featured, medicine detail, distributor page all covered).
- `js/store.js` - Cart quantity edits no longer call `init()` (which re-fetched every medicine and rebuilt the table): row total, cart total and nav badge update in place, quantity is clamped to stock, and the input keeps focus.
- `js/cart.js` - `loadSession()` was fetching `auth/session.php` twice (and re-fetching for guests on every Add click); now fetches once and caches the result.
- All HTML - asset version `?v=20261034`.

### Verified
- Playwright: guest Add shows "Sign in to add medicines" (cart stays empty); signed-in org Add shows the added toast; typing 0/2 in cart updates row total, cart total and badge with 0 API calls and keeps focus.
- Performance: cold page load ~1.3s dominated by external Bootstrap/fonts CDN (500ms), warm loads ~60-80ms; PHP endpoints 4-30ms. No JS errors.

## 2026-10-21 - Reviews & ratings page for organizations

### Added
- `api/reviews.php` - `GET ?mine=1` (org only): delivered orders awaiting a review (items, distributor, total), the org's submitted reviews (stars, comment, order, distributor, date) and review stats (average, count).
- `dashboard.html` - New "Reviews" dashboard tab.
- `js/app.js` - Org-only Reviews page: avg rating / submitted / awaiting cards, pending-review table with "Rate & review" (opens existing review modal), and "My reviews" list. Duplicate reviews are blocked server-side; after submitting, the Reviews page refreshes in place. Reviews tab hidden for admin and distributor roles.

### Fixed
- `api/reviews.php` - POST rejects a second review for the same order ("You already reviewed this order.").

### Verified
- Playwright (org): Reviews tab visible; stats 5.0/1/1 with one delivered order; submitting a 4-star review shows toast, stats update to 4.5/2/0, review appears in My reviews and persists after reload. Admin/distributor tabs hidden. Test order/review removed from DB.

## 2026-11-07 - Database moved to Supabase Postgres

### Edited
- `database.sql` - Converted from MySQL to Supabase Postgres: identity columns, CHECK constraints instead of ENUM, smallint flags, Postgres seed, added the missing transaction row for order #103.
- `config/db.php` - DSN switched from local MySQL to the Supabase session pooler (Postgres, sslmode=require).
- `api/orders.php` - `GROUP_CONCAT` -> `string_agg`; explicit GROUP BY list for Postgres; map coords fall back from order to user profile (`COALESCE(o.latitude, u.latitude)`).
- `api/reviews.php` - `GROUP_CONCAT` -> `string_agg`.
- `api/reports.php` - `CURDATE()/DATE_ADD` -> `CURRENT_DATE + INTERVAL '3 months'`.
- `api/medicines.php` - `CURDATE()` -> `CURRENT_DATE`; GROUP BY includes joined user columns.
- `api/audit.php` - `"system"` identifier -> `'system'` string literal.
- `js/store.js` - Checkout accepts optional latitude/longitude and posts them with the order.
- `cart.html`, `distributor.html`, `index.html`, `medicine.html`, `profile.html`, `store.html` - store.js cache version bumped to 20261043.
- `C:\xampp\php\php.ini` (outside project) - Enabled `pdo_pgsql` and `pgsql`; Apache restarted.

### Verified
- PHP CLI connected to Supabase Postgres 17.11; schema + seed created (10 tables).
- HTTP smoke test: distributor login, `api/orders.php` (orders with items and coords), `api/reports.php`, `api/medicines.php` all return ok.

## 2026-11-07 - Recovered legacy MySQL data for two accounts

### Edited
- Supabase `users` - Restored `shanmukha` (vollalashanmukhasai@gmail.com, distributor, id 9) and `abhi` (abhi@medishare.com, org/Orphanage, id 10) with their original bcrypt password hashes, so old passwords still work.
- Supabase `distributors` / `ngos` - Restored their verification rows (distributor id 5, ngo id 4).
- Supabase `medicines` - Restored shanmukha's 2 medicines (paracetamol, dolo 650mg).
- Supabase `orders` / `order_items` / `transactions` / `reviews` / `notifications` / `audit_logs` - Restored 6 shared orders (#117/119/120/122/123/124), 1 review, 24 notifications, 84 audit entries. Old MySQL data dir backed up to `%TEMP%\opencode\mysql_backup`.
- `auth/register.php` - Fixed license upload path (`auth/uploads/...` -> `uploads/...`); registration was failing with "Image save failed."

### Verified
- Old and new password hashes match exactly; live `api/orders.php` shows the recovered orders; medicines list includes dolo 650mg.
- Skipped orders #108/114/115/116 (they belong to `sai@medishare.com`, not in the requested recovery list).

## 2026-11-07 - Delivery workflow, Google Maps, minimum order, distributor storefront, speed

### Added
- `delivery-map.html` - Google Maps (keyless iframe embed, no API key) showing the buyer's location + "Navigate with Google Maps" turn-by-turn directions link.
- `dashboard.html` - "Track" modal with an Amazon-style order timeline (Order placed -> Accepted -> Packed -> Shipped -> Out for delivery -> Delivered).
- `api/orders.php` - New `pack` and `out_for_delivery` actions; workflow is now Pending -> Accepted -> Packed -> Shipped -> Out for Delivery -> Delivered (+ Cancelled). Ship/deliver now require the previous step.
- `api/distributors.php` - Public `GET ?list=1` (verified distributors) for the store filter.
- `store.html` / `js/store.js` - Distributor filter that opens the distributor's storefront; distributor names on medicine cards are now links.
- Minimum order: ₹500 per distributor, enforced server-side in `api/orders.php` and shown/blocked in the cart and dashboard order modal.

### Edited
- `database.sql` + live Supabase `orders_status_check` - Added `Packed` and `Out for Delivery` statuses.
- `js/app.js` - Status badges/buttons for the new workflow; Track modal; order actions now update the row locally (no full orders re-fetch); redundant notification re-fetch removed from distributor actions; rapid tab-switch null guard; dashboard map popup directions now open Google Maps.
- `js/store.js` - Cart hardening: stale/deleted medicines are removed on load, items whose request failed are kept (a network blip no longer blanks/wipes the cart), empty cart always shows ₹0, qty 0 removes the line, and `setCart([])` after checkout updates the nav badge immediately.
- `config/db.php` - `MEDISHARE_EMAIL` flag (email off by default; `mail()` blocked every request for seconds without SMTP); `notify()` now inserts the notification and fetches the recipient in one query; persistent PDO connections removed (Supabase's pooler drops idle sessions and stale handles caused fatal 500s).
- `api/orders.php` - Order placement joins the distributor's user id (one less query); update + audit log batched into one statement.
- `delivery-map.html` - Leaflet/OpenStreetMap replaced with Google Maps.
- `dashboard.html`, `store.html` - `js/app.js?v=20261045`; all storefront HTML - `js/store.js?v=20261045`.

### Verified
- Playwright: every page (home, store, medicine, distributor, cart, login, register, map, delivery-map, profile) and every dashboard tab for admin/distributor/org render with no JS errors.
- Full workflow through the real UI buttons: place order -> accept -> pack -> ship -> out for delivery -> deliver with tracking; Track modal shows all 6 steps done.
- Minimum order: ₹1.25 cart rejected with "Minimum order is ₹500 per distributor"; ₹500 order accepted.
- Cart: empty = ₹0; stale cart id 11 removed automatically; qty 0 clears the row; badge resets after checkout.
- Speed: login ~0.6s, orders GET ~0.9s, accept/pack/ship ~1.1-1.4s (previously ~2-3s); dashboard actions no longer wait for an extra orders fetch.
- `php -l` on all changed PHP, `node --check` on `js/app.js` and `js/store.js` pass. Test orders removed and stock restored (9 orders, medicine 1 stock 1200).

## 2026-11-08 - Automatic location sharing + dashboard quick-order fixes

### Added
- `js/cart.js` - Browser location helpers (`geoStored`/`geoFresh`/`geoRequest`/`geoEnsure`, localStorage `medshare_geo`, 30-min freshness, 10s timeout) and auto-request on sign-in for organizations.
- `js/store.js` - Checkout shows the captured location (`#geoStatus`) with an Enable button (`#geoEnable`) when unavailable; order POST sends `latitude`/`longitude`; warning toast when location was not shared. Manual lat/lng inputs removed.
- `js/app.js` - Dashboard order modal shows the captured location, sends it with the order, and auto-requests location for organizations on load.

### Edited
- `api/orders.php` - Shipping address falls back to the buyer's saved profile address (dashboard quick-order); error message points to the profile when neither exists.
- `auth/login.php` - Session now includes the user's saved `address` (used to prefill the dashboard delivery address).
- `js/app.js` - Dashboard order modal now has a delivery address field (prefilled from the profile) and sends it; fixed the modal's Place Order button never firing (listener was bound before the button existed, now delegated).
- Cache-busting bumped to `?v=20261048` for `js/app.js` (dashboard/login/register).

### Verified
- Playwright with granted geolocation: coords captured automatically on sign-in, checkout shows the coords (no manual inputs), order saved with lat/lng; dashboard modal order also saved with lat/lng; zero JS errors.
- Playwright with denied geolocation: "Not enabled" + Enable button, order still places with null coords and a warning toast.
- `php -l` and `node --check` pass. Test orders removed and stock restored (10 orders, medicine 1 stock 1200).

## 2026-11-08 - Razorpay payments, order-then-pay checkout, medicine editing, delivery cookie, speed

### Added
- `config/db.php` - Razorpay test credentials (`RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`).
- `api/payment.php` - `action=create` creates a Razorpay order server-side (verifies the org owns the pending orders, computes the total from the DB, stores the Razorpay order id in `transactions.reference`, returns `key_id`/`razorpay_order_id`/amount in paise); `action=verify` checks the HMAC-SHA256 signature (`order_id|payment_id`) with `hash_equals` and marks the transactions `Completed` with the payment id as reference. Legacy distributor manual confirm kept for Cash/Bank/UPI/Card but blocked for Razorpay.
- `js/cart.js` - `payWithRazorpay(orderIds, user)` (create -> load checkout.js -> open Razorpay checkout -> verify; dismiss resolves false), `loadScript`, `cachedGet` (sessionStorage, 30s), delivery-address cookie helpers (`medshare_delivery`, 1 year, SameSite=Lax), `geoQuick` (max 2s location wait).
- `api/medicines.php` - `action=update` for distributors: stock/expiry/price/image edit with ownership check; replaced images delete the old file; `low_stock_notified` resets when stock returns to >= 250.
- `js/app.js` - Medicine Edit button + modal (reuses the add form, `window._editMedId`), "Pay now" retry button for unpaid Razorpay orders, `GET_CACHE` (15s) with prefetch of the orders tab.
- `js/store.js` - Payment method select (`Razorpay`/`Cash`) at checkout; Razorpay checkout after the order is created.

### Edited
- `database.sql` + live DB - `orders.shipping_address` renamed to `delivery_address`; `transactions` method check now includes `Razorpay`.
- `api/orders.php` - Accepts `delivery_address`; method whitelist includes `Razorpay`; delivering a Cash order auto-completes its pending transaction.
- `config/db.php` - `current_user()` releases the PHP session file lock after reading (API endpoints are read-only on the session) so parallel page requests no longer serialize.
- `api/reports.php` - Scalar stats combined into one query (4 -> 1) to cut round trips.
- `js/app.js`, `js/store.js`, `dashboard.html`, all HTML - "Shipping Address" renamed to "Delivery Address" (`#delAddr`), delivery address cookie prefill, cached fetches, `?v=20261050` cache-busting.
- `api/medicines.php` - Fixed add returning the wrong id (PDO pgsql `lastInsertId()` returned the audit_logs sequence; now `INSERT ... RETURNING id`).

### Verified
- `api_flow_test.php`: Razorpay order create 200, signature verify 200, bad signature 400, COD order with Razorpay method 409, COD delivered -> transaction Completed, `delivery_address` returned.
- `ui_test.js` / `ui_test2.js`: Razorpay checkout (stubbed checkout.js + real HMAC) marks the order Completed with the payment id; dismissed payment leaves the order Pending and "Pay now" retries to Completed; cookie prefills checkout and dashboard; medicine edit stock 800 -> 1300 and expiry, then restored; tab switches 58ms/36ms with prefetch/cache; zero JS errors.
- `smoke.js`: all guest/org/distributor/admin pages and tabs render; full order workflow (place -> accept -> pack -> ship -> out for delivery -> deliver) passes with the new payment flow.
- `php -l` and `node --check` pass. Test orders removed; 11 orders left (9 seed + #119 + #133), medicine 1 stock 1200.

## 2026-11-08 - UPI checkout, payment-gated orders, medicine removal, nav badge, license review, latency

### Added
- `js/store.js`, `js/app.js`, `js/cart.js` - Payment methods: UPI (Razorpay, pre-selected with `prefill.method = 'upi'`), Card/Netbanking (Razorpay), Cash on Delivery.
- `api/orders.php` - Razorpay orders are created as `Pending Payment` (not confirmed until the payment is verified); Cash orders are confirmed immediately. Ordering the last units of a medicine removes it and notifies its distributor.
- `api/payment.php` - A verified Razorpay signature moves the orders from `Pending Payment` to `Pending` and notifies the distributor that the order is confirmed.
- `api/medicines.php` - `action=delete` lets a distributor remove a medicine; expired and out-of-stock medicines are removed automatically (soft delete via `deleted`) with a notification to the distributor.
- `js/app.js` - Remove (trash) button on the distributor medicine table; the admin Verification page shows the uploaded license/registration document thumbnail; "Pay now" also updates the order status to `Pending`.
- `js/cart.js`, `css/style.css` - The navbar dashboard icon shows an unread-notification count badge, refreshed every 60 seconds.
- `config/db.php` - Small file cache (`cache_get`/`cache_put`/`cache_clear`) for GET endpoints; every POST clears it. GET endpoints now check the cache before opening a DB connection.

### Edited
- `database.sql` + live DB - Added `medicines.deleted`; orders status check now includes `Pending Payment`.
- `api/verify.php` - Returns `license_image` so admins can review the document before approving.
- `api/notifications.php`, `api/reports.php`, `api/orders.php`, `api/medicines.php` - Cached GET responses (10-20s) and lazy DB connections, so cache hits skip Supabase entirely.
- `js/app.js` - The organization dashboard no longer shows the Medicines tab (organizations order from the store); its default page is Orders.
- `api/orders.php` - Medicine lookup ignores removed items.

### Verified
- API: unpaid Razorpay order stays `Pending Payment` and the distributor cannot accept it (409); real Razorpay order create + HMAC verify -> order `Pending`, transaction `Completed`; Cash order `Pending` immediately; delete action hides the medicine; ordering the last 2 units removes the medicine and notifies the distributor; admin verify list returns the license image file; all test data cleaned.
- UI (Playwright): organization dashboard has no Medicines tab and shows the nav badge; UPI is preselected in the Razorpay checkout; "Pay now" completes the payment and the button disappears; distributor Edit and Remove buttons work; admin sees the license thumbnail; zero JS errors.
- Cache latency: orders GET 916ms -> 5-32ms, medicines 1403ms -> 3-4ms, reports 1585ms -> 12-27ms on cache hits.
- Smoke test passes for all roles/tabs and the full order workflow. `php -l` and `node --check` pass. Test data cleaned: 12 orders left (9 seed + real #119, #133, #143), 11 medicines, medicine 1 stock 1200.

## 2026-11-09 - Forgot password with email OTP

### Added
- `forgot-password.html` - Four-step reset flow: registered email -> 6-digit OTP -> new password + confirmation -> "Password changed successfully." Linked from the login page ("Forgot password?").
- `auth/forgot.php` - `request` / `verify` / `reset` actions. Sends and verifies the OTP through Supabase Auth when `SUPABASE_ANON_KEY` is set; otherwise uses a local development fallback that writes the OTP to the system temp file `medshare_otp.log`. Issues a 10-minute HMAC-signed reset token, limits OTP guesses to 5, hashes the new password, and writes an audit log entry.
- `config/db.php` - `SUPABASE_URL`, `SUPABASE_ANON_KEY`, `RESET_SECRET` constants.

### Setup required for live email
1. Supabase dashboard -> Project Settings -> API -> copy the **anon public** key into `SUPABASE_ANON_KEY`.
2. Authentication -> Email Templates -> **Magic Link** -> add `{{ .Token }}` so the email contains the 6-digit code (the default template only has a link).
3. Optional: set `RESET_SECRET` to a random value before deploying.

### Verified
- API: unknown email gets a generic response (no account leak), OTP written/verified, wrong/used OTP rejected, reset token updates the password hash, login works with the new password, invalid token and short password rejected, original password restored. All pass.
- UI (Playwright): login -> Forgot password? -> email -> OTP -> new password -> success message -> login with the new password. No JS errors.

## Future entries

Use this format:

```
## YYYY-MM-DD - Short description

### Created / Edited / Deleted
- `path` - What changed and why.
```
