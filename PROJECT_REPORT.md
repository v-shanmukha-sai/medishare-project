# MEDRED

## A Project Report on

# Medicine Donation and Distribution Platform

> *Don't waste medicines, redistribute hope.*

Submitted in partial fulfilment of the requirements for the award of the degree of

**Bachelor of Technology**

in

**Department of Computer Science and Engineering**

**[College Name]**

**Academic Year 2026–2027**

---

### Team Members

| Roll Number | Name |
|---|---|
| 24004-CS-098 | MANADI RUPA |
| 24004-CS-080 | DASI CHARAN KUMAR |
| 24004-CS-117 | VOLLALA SHANMUKHA SAI |
| 24004-CS-105 | BEJUGAM RAGHAVI |
| 24004-CS-101 | MEDIPELLI SHIVANI |
| 24004-CS-123 | DARAVATH BASANTHI |
| 24004-CS-068 | BUDDA CHAITANYA |
| 24004-CS-082 | NALABOLU SATWIK REDDY |
| 24004-CS-121 | BIJILI SAI CHARAN |

**Project Guide:** S. Ravinder  
**Principal:** P. Srinivas

---

## Declaration

We hereby declare that the project report entitled **"MEDRED – Medicine Donation and Distribution Platform"** is the result of our own work carried out during the academic year. The work has not been copied from any other project or report. All sources of information used have been acknowledged in the references.

Place: [Place]  
Date: [Date]

Signatures of the team members:

1. ______________________
2. ______________________
3. ______________________
4. ______________________
5. ______________________
6. ______________________
7. ______________________
8. ______________________
9. ______________________

---

## Certificate

This is to certify that the project report entitled **"MEDRED – Medicine Donation and Distribution Platform"** submitted by the team listed above is a bonafide record of the work carried out under my guidance.

**Guide:** S. Ravinder  
**Head of the Department:** [Name]  
**Principal:** P. Srinivas

---

## Abstract

MEDRED is a web-based medicine donation and distribution platform that connects verified medicine distributors and pharmacies with non-governmental organizations (NGOs), medical shops, old-age homes, orphanages and charitable hospitals. A large quantity of usable medicine is wasted every year because distributors and pharmacies hold surplus or near-expiry stock while social organizations struggle to obtain medicines for the people they serve. MEDRED addresses this gap with a single, verified marketplace.

The application provides role-based access for administrators, distributors and organizations. Distributors publish their available stock with batch number, expiry date, price and quantity. Organizations search the store, add medicines to a cart, choose a delivery address, enter a delivery mobile number and pay online through Razorpay (UPI, card or net banking) or select cash on delivery. Every order passes through a controlled lifecycle — Pending Payment, Pending, Accepted, Packed, Shipped, Out for Delivery and Delivered — with cancellation and rejection supported before dispatch. Stock is reserved when an order is placed and restored when an order is cancelled or rejected. Expired and out-of-stock medicines are removed from the store automatically and the distributor is notified.

The system is implemented with PHP 8 on Apache, PostgreSQL hosted on Supabase, Supabase Storage for documents, Bootstrap 5 and vanilla JavaScript, and packaged as a Docker image for deployment on Render. It includes admin verification with complete cleanup of rejected registrations, in-app notifications with a live unread badge, reviews, reports with CSV export that exclude soft-deleted medicines, audit logs, a thirty-day persistent sign-in, a password-reset flow using SMTP email OTP, address-first delivery navigation, and several performance optimizations. The platform was tested at the API, browser and live-deployment levels; all functional tests passed and the cached read paths respond in milliseconds.

**Keywords:** medicine donation, medicine redistribution, e-commerce, order management, Razorpay, Supabase, PHP, PostgreSQL.

---

## Table of Contents

1. Introduction
2. Literature Survey and Existing Systems
3. System Analysis
4. System Design
5. Implementation
6. Testing
7. Results and Discussion
8. Limitations and Future Scope
9. Conclusion
10. References
11. Appendix

---

## 1. Introduction

### 1.1 Background

Medicines are essential, time-sensitive products. Distributors and pharmacies routinely carry surplus stock, and a portion of it approaches expiry before it can be sold. At the same time, NGOs, old-age homes, orphanages, charitable hospitals and small medical shops often lack the funds and supply network to obtain even basic medicines. Much of the surplus is discarded while genuine demand remains unmet.

Most redistribution today happens through phone calls, WhatsApp messages and personal contacts. These channels do not provide verified participants, live stock information, price transparency, order tracking, payment records or an audit trail. MEDRED was built to replace this informal process with a structured digital platform.

### 1.2 Problem Statement

There is no common, trustworthy platform where verified distributors can list surplus or near-expiry medicines and verified social organizations can order them. The absence of such a platform causes:

- avoidable wastage of usable medicines;
- difficulty for organizations in finding suppliers;
- no verification of the parties involved;
- no record of donations, payments or deliveries;
- no visibility of expiry dates and stock levels;
- manual, error-prone order handling.

### 1.3 Objectives

The objectives of the project are:

1. To build a verified network of medicine distributors and social organizations.
2. To provide real-time visibility of medicine stock, price, batch number and expiry date.
3. To allow organizations to search, filter, order and pay for medicines online.
4. To track every order through a clear lifecycle with cancellation and rejection support.
5. To notify distributors and organizations about orders, payments, stock and expiry events.
6. To give administrators tools to verify documents, manage users, view reports and audit activity.
7. To protect user accounts and business data with authentication, authorization and secure coding practices.
8. To keep the application responsive despite a hosted database by caching read-heavy responses.

### 1.4 Scope

The project covers three roles — administrator, distributor and organization — and the complete flow from registration and verification to ordering, payment, delivery and review. It does not include direct patient prescriptions, clinical advice, courier integration, a live SMS gateway or a native mobile application. These are identified as future enhancements.

---

## 2. Literature Survey and Existing Systems

Online pharmacy and medicine-donation systems have been studied in several forms. Commercial e-pharmacies focus on selling medicines to individual consumers and do not address surplus redistribution. Donation portals usually publish requests or contact details but do not maintain verified stock, prices or order records. Hospital and NGO inventory systems are internal and do not connect different organizations.

The common limitations observed in existing solutions are:

- no verification of distributors or recipient organizations;
- no expiry-aware inventory;
- no integrated payment and order tracking;
- no notification mechanism for stock, expiry or order events;
- limited reporting and auditability;
- poor usability on low-bandwidth connections.

MEDRED combines the useful ideas of a marketplace, an inventory system and a donation network, while adding verification, expiry handling, payment-gated order confirmation and audit logging.

---

## 3. System Analysis

### 3.1 Existing System and Drawbacks

In the existing manual process, a distributor informs known organizations about available stock through calls or messages. Orders are recorded in notebooks or spreadsheets. Payments are collected separately, and delivery status is communicated informally. The main drawbacks are:

- stock information is outdated and incomplete;
- there is no way to confirm whether a distributor or organization is genuine;
- expiry dates are not visible at the time of ordering;
- payment and delivery records are difficult to reconcile;
- there is no audit trail when something goes wrong;
- cancelled or rejected orders are not tracked properly.

### 3.2 Proposed System

MEDRED is a centralized web application with three authenticated roles. Distributors manage their medicine inventory. Organizations browse the store, place orders and pay online or choose cash on delivery. Administrators verify accounts and monitor activity. The system enforces the order lifecycle, reserves stock, restores stock on cancellation or rejection, removes expired and out-of-stock items automatically, and notifies the affected users.

### 3.3 Functional Requirements

| ID | Requirement |
|---|---|
| FR1 | Users can register as a distributor or an organization with the required documents. |
| FR2 | Administrators can view uploaded license or registration documents and approve or reject accounts. |
| FR3 | Distributors can add, edit and remove medicines with name, category, price, MRP, stock, batch number, expiry and image. |
| FR4 | Organizations can search and filter medicines by name, category, shelf life and distributor. |
| FR5 | Organizations can add medicines to a cart and place orders with a delivery address. |
| FR6 | The system groups order items by distributor and enforces a minimum order value per distributor. |
| FR7 | Organizations can pay through Razorpay (UPI, card, net banking) or choose cash on delivery. |
| FR8 | Razorpay orders remain in Pending Payment until the payment signature is verified. |
| FR9 | Distributors can accept, pack, ship, mark out for delivery and deliver orders. |
| FR10 | Organizations can cancel orders before acceptance; distributors can reject pending orders. |
| FR11 | Stock is reserved on order creation and restored on cancellation or rejection. |
| FR12 | Expired and out-of-stock medicines are removed from the store and the distributor is notified. |
| FR13 | Users receive in-app notifications with an unread count badge. |
| FR14 | Organizations can review delivered orders. |
| FR15 | Administrators can view reports and export CSV files and inspect audit logs. |
| FR16 | Users can reset a forgotten password using an email OTP. |
| FR17 | Users can view and update their profile and delivery location. |

### 3.4 Non-Functional Requirements

| Category | Requirement |
|---|---|
| Security | Passwords are hashed; all database access uses prepared statements; roles are checked on every protected endpoint; payment signatures are verified; secrets are kept out of version control. |
| Performance | Frequently read responses are cached; database connections are opened lazily; cached reads should complete in milliseconds. |
| Usability | Responsive Bootstrap interface; clear status badges; inline validation messages; mobile-friendly layouts. |
| Reliability | Transactions are used for order creation and cancellation; stock restoration is transactional. |
| Maintainability | Modular API endpoints, shared configuration and helpers, documented setup steps. |
| Scalability | Stateless PHP endpoints and a hosted PostgreSQL database; read caching reduces database load. |

### 3.5 Feasibility Study

**Technical feasibility:** The chosen stack — PHP 8, Apache, PostgreSQL, Bootstrap and vanilla JavaScript — is widely available and well documented. Supabase provides a managed PostgreSQL instance, and Razorpay provides a test-mode payment gateway.

**Economic feasibility:** All core technologies are open source. Supabase and Razorpay offer free or test tiers suitable for a college project. No licensed software is required.

**Operational feasibility:** The interface follows familiar e-commerce patterns. Distributors manage stock in a table, organizations shop in a store and administrators approve accounts from a dashboard, so only basic computer literacy is needed.

---

## 4. System Design

### 4.1 Architecture

MEDRED uses a three-tier architecture:

```
Browser (HTML + Bootstrap + JavaScript)
        |
        |  JSON over HTTP
        v
Apache + PHP 8 API (Docker on Render) --->  Razorpay API (orders, payments)
        |                             --->  SMTP server (password-reset OTP)
        |                             --->  Supabase Storage (documents)
        |
        |  PDO (prepared statements)
        v
PostgreSQL database (Supabase)
```

The browser loads static HTML pages and JavaScript modules. JavaScript calls PHP endpoints under `api/` and `auth/`, which return JSON. PHP uses PDO to talk to PostgreSQL. Razorpay is used for online payments, a server-side SMTP client sends the password-reset OTP, and Supabase Storage keeps uploaded documents in production. The application runs in a Docker container on Render; XAMPP is used locally. A file-based cache stores short-lived JSON responses to avoid repeated database round trips.

### 4.2 Modules

1. **Authentication and verification** — registration, login, logout, thirty-day persistent sessions, admin approval and full removal of rejected registrations.
2. **Medicine inventory** — add, edit, remove, stock, expiry, batch, category, price and images.
3. **Store and cart** — search, filters, cart persistence, delivery address, delivery mobile number and geolocation.
4. **Order management** — order creation, grouping by distributor, status transitions, cancellation, rejection, delivery navigation and stock handling.
5. **Payments** — Razorpay order creation and signature verification, cash on delivery, payment status.
6. **Notifications** — in-app notifications, unread badge, 60-second navigation refresh and 30-second dashboard refresh.
7. **Reviews** — one review per delivered order with rating and comment.
8. **Reports and audit** — revenue and order statistics that ignore soft-deleted medicines, CSV export, activity log.
9. **Password reset** — SMTP email OTP, reset token, password update.
10. **Profile and map** — profile updates, delivery location, public map page and address-first directions.

### 4.3 Order Lifecycle

```
Razorpay:  Pending Payment --(verified payment)--> Pending
Cash:      Pending (immediately)
Pending --(distributor accepts)--> Accepted --> Packed --> Shipped
        --> Out for Delivery --> Delivered
Pending / Pending Payment --(organization cancels)--> Cancelled
Pending --(distributor rejects)--> Cancelled
```

Stock is deducted when the order is created. If an order is cancelled or rejected, the stock is restored. When the last units of a medicine are ordered, the medicine is marked removed and the distributor is notified. Expired and out-of-stock medicines are also removed automatically when the store is loaded.

### 4.4 Database Design

The database contains ten tables:

| Table | Purpose |
|---|---|
| `users` | Common account data: name, email, password hash, phone, address, city, latitude, longitude, role, organization type, verification status. |
| `distributors` | Distributor-specific data: license number and license image. |
| `ngos` | Organization-specific data: registration number and registration image. |
| `medicines` | Inventory: name, category, price, MRP, stock, batch number, expiry, shelf life, image, distributor, soft-delete flag, low-stock flag. |
| `orders` | Order header: organization, distributor, delivery address, delivery mobile, latitude, longitude, total, status, tracking number, timestamps. |
| `order_items` | Line items: order, medicine, quantity and price at the time of order. |
| `transactions` | Payment record: order, amount, method, status and gateway reference. |
| `notifications` | Per-user notification: type, text, read status, timestamp. |
| `reviews` | One rating and comment per delivered order. |
| `audit_logs` | Who performed which action and when. |

Relationships:

- one user has at most one distributor profile or one organization profile;
- one distributor owns many medicines;
- one organization places many orders;
- one order belongs to one distributor and contains many order items;
- one order has one transaction;
- one delivered order can have one review.

The delivery mobile number is stored as `orders.delivery_phone VARCHAR(15)`. It was added with an additive migration (`ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_phone VARCHAR(15)`) so existing orders and data remain intact. New orders require exactly ten digits; historical orders may have no value. Report and dashboard queries filter `deleted = 0`, so soft-deleted medicines do not inflate live counts.

### 4.5 User Interface

The application has the following pages:

- `index.html` — landing page;
- `login.html`, `register.html`, `forgot-password.html` — authentication;
- `store.html`, `medicine.html` — store and medicine details;
- `cart.html` — cart and checkout;
- `dashboard.html` — role-based dashboard (admin, distributor, organization);
- `profile.html` — profile and delivery location;
- `map.html`, `delivery-map.html` — public map and delivery route.

The dashboard shows only the tabs allowed for the signed-in role. Administrators see overview, medicines, orders, notifications, reports, distributors, audit, verification and map. Distributors see medicines, orders, notifications, reports and order history. Organizations see orders, reviews, notifications and order history.

---

## 5. Implementation

### 5.1 Technologies Used

| Layer | Technology |
|---|---|
| Front end | HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons, vanilla JavaScript |
| Back end | PHP 8.2 |
| Database | PostgreSQL 15 hosted on Supabase |
| Database access | PDO with prepared statements |
| File storage | Supabase Storage (production), local filesystem (development) |
| Payments | Razorpay (test mode) |
| OTP email | SMTP through a built-in PHP cURL client |
| Maps | OpenStreetMap tiles, Leaflet, address-first Google Maps directions links |
| Web server | Apache in Docker on Render; XAMPP locally |
| Hosting | Render Docker web service with Supabase PostgreSQL and Storage |
| Version control | Git and GitHub |
| Testing | PHP cURL API tests, Playwright browser tests, live API checks |

### 5.2 Authentication and Role Management

Registration collects the user's name, email, password, phone, address, city, coordinates, role and, for distributors and organizations, license or registration details and an uploaded document. Phone numbers are normalised to digits and must contain exactly ten digits. Passwords are stored with `password_hash()` and verified with `password_verify()`. A successful login regenerates the session ID and stores the user's identity in the PHP session. Protected endpoints call a shared role check, and the dashboard hides tabs that are not allowed for the current role.

Sign-in is persistent for thirty days. A signed `medred_remember` cookie stores the user, expiry and a password-hash fingerprint, protected by an HMAC derived from `RESET_SECRET`. The cookie is HttpOnly and SameSite=Lax, and becomes Secure over HTTPS. `current_user()` restores the session from it when the PHP session has expired; logout, expiry or a password change invalidates it.

The application releases the PHP session file lock after reading the session. This allows parallel API requests from the same browser to run concurrently instead of being serialized.

### 5.3 Admin Verification and Documents

New distributor and organization accounts remain unverified until an administrator approves them. The verification page lists pending accounts and shows a thumbnail of the uploaded license or registration document, which opens the full image in a new tab. The administrator can approve or reject the account, and the action is written to the audit log.

Rejection is destructive and confirmed in the interface. Once confirmed, the server removes the pending user's notifications, audit entries, distributor or organization profile and user row, then deletes the uploaded document through the storage helper. The rejected account no longer appears in the pending queue and cannot be approved accidentally later.

### 5.4 Medicine Inventory

Distributors can add a medicine with its name, category, price, MRP, stock, batch number, expiry date, shelf life and image. Existing medicines can be edited. Removal uses a soft-delete flag so that past orders and invoices remain intact. When stock is set to zero, the medicine is removed from the store and the distributor receives a notification.

The store load process also checks expiry dates. Expired medicines are removed and the distributor is notified. Out-of-stock medicines are removed in the same way. A low-stock notification is generated when stock falls below the configured threshold.

### 5.5 Store, Cart and Geolocation

The store displays available medicines with distributor name, category, price, stock, expiry and shelf life. Users can search by name and filter by category, remaining shelf life and distributor. Organizations can order directly from the store or from the dashboard. The cart is stored in the browser so it survives page reloads.

Checkout now captures a delivery mobile number directly after the delivery address. The field accepts digits only, enforces ten digits on the client and server, and remembers the last value in a cookie. Delivery location is captured through the browser's geolocation API and stored in a cookie. If the user does not grant permission, checkout proceeds with the delivery address and mobile number; coordinates are optional. A public map page shows verified organizations and distributors, and the delivery-map page provides address-first directions to a selected destination, using stored coordinates only when no address is available.

### 5.6 Order Management and Cancellation

When an order is placed, the server validates each medicine, checks expiry and stock, deducts the ordered quantity, groups the items by distributor and creates one order per distributor. The delivery mobile number is validated and stored in `orders.delivery_phone`; invalid numbers are rejected before stock changes. The minimum order value is ₹500 per distributor. The order and its line items are written inside a database transaction.

Razorpay orders start in **Pending Payment**; cash orders start in **Pending**. Distributors can accept only orders that are in **Pending**, so an unpaid Razorpay order cannot be accepted. After acceptance, the distributor marks the order as Packed, Shipped, Out for Delivery and finally Delivered with a tracking number.

Organizations can cancel an order while it is still **Pending** or **Pending Payment**. The cancellation restores the reserved stock, marks the transaction as Cancelled and notifies the distributor. Distributors can reject a pending order, which also restores stock. Both actions are recorded in the audit log.

Order rows and the delivery map display the saved mobile number. The Navigate action opens Google Maps with the entered delivery address as the destination; if the address is missing, it falls back to the stored coordinates. This avoids a geocoding dependency and respects the address the organization actually supplied.

### 5.7 Payments

Online payments use Razorpay. The server creates a Razorpay order with the server-computed total, stores the gateway order reference in the transaction row and returns the public key and order details to the browser. The Razorpay checkout supports UPI, cards and net banking. UPI can be pre-selected so that the UPI section opens first.

After payment, the browser sends the Razorpay order ID, payment ID and signature to the server. The server recomputes the HMAC-SHA256 signature with the secret key and compares it using a timing-safe comparison. Only a valid signature moves the orders from **Pending Payment** to **Pending** and marks the transaction as Completed. Cash on delivery orders are confirmed immediately with a Pending transaction.

### 5.8 Notifications

Notifications are stored in the database with a type, text and read status. They are generated for new orders, payment confirmation, order acceptance, rejection, cancellation, delivery, low stock and expiry. The navigation bar shows an unread-count badge on the dashboard icon, refreshed every 60 seconds, while the dashboard refreshes its own badge every 30 seconds. Users can mark all notifications as read.

Email and SMS notifications are present as optional integrations. Email uses SMTP when configured; otherwise notification email is disabled by default. SMS is logged to a file as a stub until a gateway is connected.

### 5.9 Reviews

Organizations can review an order only after it has been delivered, and only once per order. Reviews contain a 1–5 star rating and an optional comment. The store and distributor profile show the average rating and recent reviews.

### 5.10 Reports and Audit

The reports page shows revenue, order counts, medicine counts and stock distribution. All totals, category breakdowns and low-stock or expiry-risk counts filter on `deleted = 0`, so soft-deleted medicines no longer inflate live figures; an all-time total remains available for comparison. The statistics are computed with a small number of aggregate queries, and the data can be exported as CSV. The audit log records logins, account approvals, medicine changes, orders, payments and password resets, together with the user and timestamp.

### 5.11 Password Reset with Email OTP

The password-reset flow follows the steps below:

1. The user opens the login page and clicks **Forgot password?**.
2. The user enters the registered email address.
3. The server checks that the email belongs to an account and generates a six-digit OTP, stored as a short-lived hash.
4. The built-in SMTP client delivers the OTP to the user's email.
5. The user enters the OTP on the website.
6. The server verifies the OTP and allows at most five attempts.
7. On success, the server issues a short-lived, HMAC-signed reset token.
8. The user enters and confirms a new password.
9. The server validates the token, hashes the new password, updates the account and writes an audit entry.
10. The page displays **Password changed successfully.**

The OTP expires after ten minutes and is single-use. In development, when SMTP is not configured, the OTP is written to `%TEMP%/medred_otp.log` so the flow can be tested without sending email. In production, a missing SMTP configuration returns a clear service-unavailable response instead of exposing the OTP or silently failing. SMTP credentials are supplied through environment variables or `config/secrets.php`.

### 5.12 Performance Optimization

Because the database is hosted remotely, a cold database round trip costs hundreds of milliseconds to several seconds. The following optimizations were applied:

- **Short-lived file cache:** read-heavy GET endpoints cache JSON responses for 10–20 seconds. Every POST clears the cache, so writes are visible immediately.
- **Lazy database connection:** endpoints check the cache before opening a database connection, so cache hits avoid the database entirely.
- **Session lock release:** the PHP session is closed after reading the current user, allowing parallel requests.
- **Combined queries:** report statistics were reduced from four queries to one.
- **Prefetching:** the dashboard warms the APIs for all tabs after login, so tab switches hit the client or server cache.
- **Client-side cache:** the dashboard keeps GET responses for 30 seconds and the store keeps them longer.

Measured on the local development setup, cached reads fell from roughly 0.8–1.6 seconds to 3–32 milliseconds. Cold reads are still limited by the hosted database.

### 5.13 Security Measures

- passwords hashed with `password_hash()`;
- prepared statements for every query;
- role checks on all protected endpoints;
- session ID regeneration on login;
- HMAC verification of Razorpay payments;
- HMAC-signed, expiring password-reset tokens;
- signed, HttpOnly, SameSite=Lax, Secure-on-HTTPS remember cookies whose signature covers the user, expiry and password-hash fingerprint;
- generic responses for unknown emails to prevent account enumeration;
- OTP attempt limit and single-use OTP records;
- SMTP credentials supplied only through server-side configuration;
- rejected-registration cleanup that removes the user, role profile, notifications and audit rows and deletes the uploaded document;
- `.htaccess` denies for the configuration and log folders;
- PHP execution blocked inside the uploads folder;
- credentials moved to environment variables or `config/secrets.php`, both excluded from Git;
- user-uploaded license documents and logs excluded from Git;
- audit logging of sensitive actions.

### 5.14 Deployment and Version Control

The repository includes a `Dockerfile` based on `php:8.2-apache` with the PDO PostgreSQL, cURL and fileinfo extensions enabled, plus Docker Compose for local runs. Production is a Docker web service on Render, connected to Supabase PostgreSQL and Storage. Configuration is supplied through environment variables; `config/secrets.php` remains a local-only fallback. A `health.php` endpoint verifies database connectivity for the platform health check.

The source code is maintained in Git and published to GitHub. The repository contains a `config/secrets.example.php` template, an `.env.example`, a `.gitignore` that excludes real secrets, uploads and logs, and a README with setup and deployment instructions. Render supplies HTTPS, so session and remember cookies are issued as Secure automatically.

---

## 6. Testing

### 6.1 Testing Approach

Testing was performed at three levels:

1. **API tests** — PHP scripts using cURL logged in as each role and exercised the endpoints: registration, verification, medicine CRUD, order creation, payment creation and verification, acceptance, cancellation, rejection, notifications, reviews, reports and password reset.
2. **UI tests** — Playwright scripts drove a real Chromium browser: login, role-based tabs, store, cart, Razorpay checkout (with a stubbed checkout script and a real HMAC signature), order actions, license document display, notifications badge, cancellation and password reset.
3. **Smoke tests** — a script visited every page for every role and verified that each tab rendered without JavaScript errors.

### 6.2 Sample Test Cases

| # | Test | Expected Result | Status |
|---|---|---|---|
| 1 | Login with valid credentials | Session created and dashboard opens | Pass |
| 2 | Login with wrong password | 401 Invalid email or password | Pass |
| 3 | Unverified account login | 403 Account pending verification | Pass |
| 4 | Admin approves a distributor with a license image | Account verified; document visible | Pass |
| 5 | Distributor adds a medicine | Medicine appears in the store | Pass |
| 6 | Distributor removes a medicine | Medicine disappears; order history remains | Pass |
| 7 | Organization places a cash order | Order in Pending; stock reduced | Pass |
| 8 | Organization places a Razorpay order | Order in Pending Payment; distributor cannot accept | Pass |
| 9 | Razorpay payment with a valid signature | Order becomes Pending; transaction Completed | Pass |
| 10 | Razorpay payment with an invalid signature | Payment rejected | Pass |
| 11 | Organization cancels a pending order | Order Cancelled; stock restored; transaction Cancelled | Pass |
| 12 | Organization tries to cancel an accepted order | 409 error | Pass |
| 13 | Ordering the last units of a medicine | Medicine removed; distributor notified | Pass |
| 14 | Expired medicine in the store | Medicine removed; distributor notified | Pass |
| 15 | Unread notifications | Badge shows the count | Pass |
| 16 | Review on a delivered order | Review saved; second review rejected | Pass |
| 17 | Forgot password with a registered email | OTP generated and verified through the development log fallback | Pass |
| 18 | Password reset with a valid token | Password updated; login works | Pass |
| 19 | Reuse of a used OTP | Rejected | Pass |
| 20 | Tab access outside the user's role | Tab hidden; API returns 403 | Pass |
| 21 | Admin dashboard after soft-deleting medicines | Active totals exclude deleted rows; all-time total remains | Pass |
| 22 | Admin rejects a pending registration | User, profile, notifications, audit rows and document removed | Pass |
| 23 | Checkout with a nine-digit or alphabetic mobile number | 422 response; no order or stock change | Pass |
| 24 | Checkout with a valid ten-digit mobile number | Number stored and shown in dashboard and map | Pass |
| 25 | Navigate to an order with an address and coordinates | Google Maps opens with the address as destination | Pass |
| 26 | Return after the browser session ends | Remember cookie restores the session; logout invalidates it | Pass |
| 27 | Live Render health and API checks | Health endpoint and database connection succeed; report totals correct | Pass |

### 6.3 Test Results

All API and UI test suites passed. The smoke test rendered every role's tabs with zero JavaScript errors (the only console messages were expected 401 responses for guests). Test data was removed after the runs. The password-reset tests confirmed that the original password could be restored and that login worked with the new password. Live checks on Render confirmed the health endpoint, database connection, authentication and report totals. Production email delivery remains dependent on SMTP credentials being configured in the hosting environment.

---

## 7. Results and Discussion

The completed system provides a working end-to-end flow:

- a distributor registers, uploads a license, waits for approval and publishes medicines;
- an organization registers, is approved, searches the store, adds medicines to the cart and places an order;
- online payment is verified on the server before the order is confirmed;
- the distributor accepts, packs, ships and delivers the order;
- both parties see notifications and order history;
- the organization can review the delivered order;
- the administrator can inspect documents, reports and audit logs.

The dashboard is role-aware: organizations no longer see the medicine-management tab, and the delivery-map tab on the organization and distributor dashboards has been replaced by an order-history page that lists completed and cancelled orders. The administrator retains the map.

Performance results from the local test environment:

| Endpoint | Cold response | Cached response |
|---|---|---|
| Orders | ~916 ms | 5–32 ms |
| Medicines | ~1403 ms | 3–4 ms |
| Notifications | ~797 ms | 27–28 ms |
| Reports | ~1585 ms | 12–27 ms |

The results show that the caching and lazy-connection strategy removes most of the repeated database latency while keeping data fresh through short TTLs and write invalidation.

---

## 8. Limitations and Future Scope

### 8.1 Limitations

- Email delivery requires SMTP credentials in the hosting environment; SMS remains a stub.
- District, state and PIN code are not yet stored as separate structured fields.
- Razorpay is used in test mode; live payments require live keys and compliance checks.
- There is no integration with courier or delivery-partner APIs.
- Geolocation depends on browser permission and may be inaccurate; navigation therefore prefers the typed address.
- The application is a responsive web app, not a native mobile app.
- The hosted database and free Render instance add latency to cold requests and may spin down when idle.
- Admin verification is manual.
- The platform is available in English only.

### 8.2 Future Scope

- Provide production SMTP credentials and enable live Razorpay payments.
- Integrate an SMS gateway such as Twilio or MSG91.
- Add delivery-partner tracking and route optimization.
- Add barcode or QR scanning for medicine batches.
- Use OCR to assist license verification.
- Add demand forecasting and expiry alerts before stock becomes critical.
- Build a mobile application for distributors and field staff.
- Add multi-language support and accessibility improvements.
- Add automated database backups and monitoring.
- Add analytics dashboards for donation impact and regional demand.

---

## 9. Conclusion

MEDRED demonstrates how a focused web application can reduce medicine wastage and improve access for social organizations. The project covers the complete operational cycle — registration, verification, inventory, store, cart, delivery details, payment, order tracking, cancellation, notifications, reviews, reports, persistent sign-in and password recovery — using a practical, low-cost technology stack.

The implementation emphasizes correctness at the trust boundaries: verified accounts, complete cleanup of rejected registrations, server-side payment verification, transactional stock handling, role-based authorization, signed sessions and audit logging. Reports reflect active inventory only, delivery navigation follows the address the organization entered, and the Docker image runs consistently on Render. Performance work on caching and database access keeps the interface responsive despite a hosted database. The system is ready for further extension with production SMTP credentials, live payment credentials and delivery-partner integration.

---

## 10. References

1. PHP Documentation — https://www.php.net/docs.php
2. PostgreSQL Documentation — https://www.postgresql.org/docs/
3. Supabase Documentation — https://supabase.com/docs
4. Razorpay Payments Documentation — https://razorpay.com/docs/
5. Bootstrap 5 Documentation — https://getbootstrap.com/docs/5.3/
6. MDN Web Docs — https://developer.mozilla.org/
7. OWASP Application Security Verification Standard — https://owasp.org/www-project-application-security-verification-standard/
8. Playwright Documentation — https://playwright.dev/
9. Leaflet Documentation — https://leafletjs.com/
10. OpenStreetMap — https://www.openstreetmap.org/

---

## 11. Appendix

### A. Setup Instructions

1. Install XAMPP with PHP 8.2 and enable the `pdo_pgsql`, `curl` and `fileinfo` extensions.
2. Import `database.sql` into a PostgreSQL or Supabase database. Existing databases need `ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_phone VARCHAR(15);`.
3. Copy `config/secrets.example.php` to `config/secrets.php` and fill in the database, Razorpay, Supabase and SMTP values.
4. Place the project under the Apache document root and open `index.html`.
5. For password-reset email, set `SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_USER`, `SMTP_PASS` and `SMTP_FROM`. Without SMTP, development writes the OTP to `%TEMP%/medred_otp.log`; production returns a configuration error.
6. For Docker, run `docker compose up --build` with `.env` present.
7. For Render, create a Docker web service from the repository, add the environment variables, set the health check path to `/health.php`, and deploy.
8. Verify the live service with `/health.php`, login and an API read such as `api/reports.php`.

### B. Seed Accounts

All seed accounts use the password `admin123`.

| Role | Email |
|---|---|
| Administrator | admin@medshare.com |
| Distributor | medplus@medshare.com |
| Distributor | pharmalink@medshare.com |
| Organization | hope@medshare.com |
| Organization | citycare@medshare.com |

### C. API Endpoints

| Endpoint | Purpose |
|---|---|
| `auth/login.php`, `auth/logout.php`, `auth/session.php` | Authentication |
| `auth/register.php` | Registration with document upload |
| `auth/forgot.php` | Password-reset OTP request, verification and reset |
| `api/medicines.php` | Medicine list, details, add, edit, remove |
| `api/orders.php` | Order creation with delivery mobile, status actions, cancellation, list |
| `api/payment.php` | Razorpay order creation and verification |
| `api/notifications.php` | List and mark notifications as read |
| `api/verify.php` | Admin approval, or rejection with complete registration cleanup |
| `api/distributors.php` | Distributor profiles and admin list |
| `api/reviews.php` | Reviews for delivered orders |
| `api/reports.php` | Statistics and CSV export |
| `api/audit.php` | Audit log |
| `api/profile.php` | Profile update with ten-digit phone validation |
| `api/locations.php` | Verified locations for the map |
| `health.php` | Deployment health check with database connectivity |

---

*End of Report*
