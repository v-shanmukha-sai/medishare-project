# MEDRED — Complete Source Code

> Medicine Donation and Distribution Platform

This document contains the complete source code of the MEDRED web application, reproduced verbatim from the working tree at `C:\xampp\htdocs\med-donation`.

**Generated:** 2026-11-11  
**Stack:** PHP 8.2, Apache, PostgreSQL (Supabase), Supabase Storage, Bootstrap 5.3, vanilla JavaScript  
**Files included:** 45

---

## Contents

| Group | File | Purpose |
|---|---|---|
| Root Pages & Entry Points | `index.html` | Landing page: hero, featured medicines, how it works, footer shell. |
| Root Pages & Entry Points | `login.html` | Sign-in form and client-side login handling. |
| Root Pages & Entry Points | `register.html` | Distributor/organization registration form with license document upload. |
| Root Pages & Entry Points | `forgot-password.html` | Three-step email OTP password reset interface. |
| Root Pages & Entry Points | `store.html` | Public medicine catalogue with search, filters and sorting. |
| Root Pages & Entry Points | `medicine.html` | Medicine detail page shell. |
| Root Pages & Entry Points | `distributor.html` | Public distributor profile page shell. |
| Root Pages & Entry Points | `cart.html` | Cart, quantity controls and checkout panel. |
| Root Pages & Entry Points | `profile.html` | Profile view, account details and edit modal. |
| Root Pages & Entry Points | `dashboard.html` | Role-aware dashboard shell with order, medicine, review, tracking and camera modals. |
| Root Pages & Entry Points | `map.html` | Leaflet map of verified organizations and distributors. |
| Root Pages & Entry Points | `delivery-map.html` | Google Maps navigation view for a selected delivery order. |
| Root Pages & Entry Points | `health.php` | Render health check that verifies the PostgreSQL connection. |
| Root Pages & Entry Points | `.htaccess` | Blocks direct download of the database schema file. |
| Authentication | `auth/login.php` | Credential validation, session creation, persistent-login cookie and audit entry. |
| Authentication | `auth/register.php` | Validated registration, license document upload and transactional profile creation. |
| Authentication | `auth/logout.php` | Destroys the session and clears the remember cookie. |
| Authentication | `auth/session.php` | Returns the currently signed-in user. |
| Authentication | `auth/forgot.php` | OTP request/verify and signed-token password reset flow. |
| API Endpoints | `api/medicines.php` | Inventory add/update/delete, public catalogue, expiry and stock sweeps. |
| API Endpoints | `api/orders.php` | Checkout, role-scoped order listing and the complete order state machine. |
| API Endpoints | `api/payment.php` | Razorpay order creation, signature verification and manual payment confirmation. |
| API Endpoints | `api/distributors.php` | Public distributor profiles, verified list and admin removal. |
| API Endpoints | `api/verify.php` | Admin approval and rejection of pending accounts with document cleanup. |
| API Endpoints | `api/reviews.php` | Delivered-order reviews, pending reviews and rating aggregates. |
| API Endpoints | `api/reports.php` | Platform statistics, category stock, expiry risk and CSV export. |
| API Endpoints | `api/notifications.php` | Notification list and mark-all-read action. |
| API Endpoints | `api/locations.php` | Verified users with coordinates for delivery maps. |
| API Endpoints | `api/profile.php` | Profile read/update and role-specific order count. |
| API Endpoints | `api/audit.php` | Latest audit log entries for administrators. |
| Configuration | `config/db.php` | Environment/secrets loading, PDO, sessions, cache, notifications, SMTP and shared helpers. |
| Configuration | `config/storage.php` | Supabase Storage integration, bucket management and upload validation. |
| Configuration | `config/secrets.example.php` | Template for the gitignored local secrets file. |
| Configuration | `.env.example` | Environment variable template for Docker and Render. |
| Client-Side JavaScript | `js/cart.js` | Shared cart storage, API helpers, Razorpay Checkout, nav/footer and geolocation. |
| Client-Side JavaScript | `js/store.js` | Storefront rendering, medicine details, distributor profile, cart and checkout logic. |
| Client-Side JavaScript | `js/app.js` | Login/register handling and all role-aware dashboard rendering and actions. |
| Stylesheet | `css/style.css` | Complete custom theme layered over Bootstrap 5. |
| Database | `database.sql` | PostgreSQL schema, constraints, indexes and demonstration seed data. |
| Deployment & Container | `Dockerfile` | PHP 8.2 Apache image with PostgreSQL extensions, opcache and security configuration. |
| Deployment & Container | `docker/apache-security.conf` | Directory denials, index protection and HTTP security headers. |
| Deployment & Container | `docker/php.ini` | Production PHP, upload, session and opcache settings. |
| Deployment & Container | `docker/entrypoint.sh` | Adapts Apache to the Render PORT and starts the server. |
| Deployment & Container | `.dockerignore` | Files excluded from the Docker build context. |
| Utility Scripts | `scripts/migrate-uploads.php` | One-time migration of local uploads to Supabase Storage. |

**Intentionally excluded:** `config/secrets.php`, `.env`, user uploads, logs, images, PDF reports, the `reference/` directory, Git metadata and operating-system files. No credentials are included in this document.

---

## Source Code

## Root Pages & Entry Points

### `index.html`

> Landing page: hero, featured medicines, how it works, footer shell.  
> Lines: 90 &middot; Size: 5.5 KB

````html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MEDRED - Don't waste medicines, redistribute hope</title>
  <link rel="icon" type="image/png" href="img/logo.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="css/style.css?v=20261111" rel="stylesheet">
</head>
<body>
  <div id="nav"></div>

  <header class="hero">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <span class="hero-badge">Verified distributors &middot; Approved organizations</span>
          <h1>Don't waste<br>medicines,<br>redistribute hope.</h1>
          <p class="hero-sub">Near-expiry stock from licensed distributors, at reduced prices, for NGOs, orphanages and old-age homes.</p>
          <div class="d-flex flex-wrap gap-3 mt-4">
            <a href="store.html" class="btn btn-primary btn-lg">Browse medicines</a>
            <a href="register.html?role=distributor" class="btn btn-white-pill btn-lg">List your stock</a>
          </div>
        </div>
        <div class="col-lg-6">
          <img src="img/image1.png" class="hero-art" alt="MEDRED medicine redistribution" width="1200" height="900" fetchpriority="high">
        </div>
      </div>
    </div>
  </header>

  <section class="section pt-0" id="featuredSection">
    <div class="container">
      <div class="section-head">
        <h2>Expiring soon, still safe</h2>
        <a class="view-all" href="store.html">View all &rarr;</a>
      </div>
      <div class="row g-3" id="featured"></div>
    </div>
  </section>

  <section class="section pt-0">
    <div class="container">
      <div class="row g-4">
        <div class="col-6 col-md-3"><div class="stat-chip"><div class="ico"><i class="bi bi-calendar-check"></i></div><div class="num">3-6</div><div class="lbl">months shelf life</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-chip"><div class="ico"><i class="bi bi-tags"></i></div><div class="num">70%</div><div class="lbl">average discount</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-chip"><div class="ico"><i class="bi bi-shield-check"></i></div><div class="num">100%</div><div class="lbl">verified partners</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-chip"><div class="ico"><i class="bi bi-journal-check"></i></div><div class="num">Full</div><div class="lbl">audit transparency</div></div></div>
      </div>
    </div>
  </section>

  <section id="about" class="section pt-0">
    <div class="container">
      <div class="section-head"><h2>Why MEDRED</h2></div>
      <div class="row g-3">
        <div class="col-md-4"><div class="card feature-card"><div class="ico"><i class="bi bi-search"></i></div><h5>Smart medicine search</h5><p>Filter by name, category, price and remaining shelf life.</p></div></div>
        <div class="col-md-4"><div class="card feature-card"><div class="ico"><i class="bi bi-bell"></i></div><h5>Expiry &amp; order alerts</h5><p>Automated notifications for near-expiry stock and order updates.</p></div></div>
        <div class="col-md-4"><div class="card feature-card"><div class="ico"><i class="bi bi-clipboard-data"></i></div><h5>Reports &amp; analytics</h5><p>Inventory, sales and wastage reports for admins and distributors.</p></div></div>
        <div class="col-md-4"><div class="card feature-card"><div class="ico"><i class="bi bi-cart"></i></div><h5>Order management</h5><p>Place orders, track status, with automatic inventory updates.</p></div></div>
        <div class="col-md-4"><div class="card feature-card"><div class="ico"><i class="bi bi-file-earmark-text"></i></div><h5>Invoices &amp; transactions</h5><p>Digital records of every transaction with printable invoices.</p></div></div>
        <div class="col-md-4"><div class="card feature-card"><div class="ico"><i class="bi bi-journal-check"></i></div><h5>Audit logs</h5><p>Every action tracked for accountability and compliance.</p></div></div>
      </div>
    </div>
  </section>

  <section id="how" class="section pt-0">
    <div class="container">
      <div class="section-head"><h2>How it works</h2></div>
      <div class="row g-3">
        <div class="col-6 col-md-3"><div class="step"><div class="num">1</div><h5>Register</h5><p>Sign up as a distributor or organization and submit details for verification.</p></div></div>
        <div class="col-6 col-md-3"><div class="step"><div class="num">2</div><h5>Get verified</h5><p>Admin reviews and approves your account.</p></div></div>
        <div class="col-6 col-md-3"><div class="step"><div class="num">3</div><h5>List or browse</h5><p>Distributors upload medicines; organizations search and compare.</p></div></div>
        <div class="col-6 col-md-3"><div class="step"><div class="num">4</div><h5>Order &amp; track</h5><p>Place orders, receive notifications, download invoices.</p></div></div>
      </div>
    </div>
  </section>

  <div id="footer"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/cart.js?v=20261111"></script>
  <script src="js/store.js?v=20261111"></script>
</body>
</html>
````

### `login.html`

> Sign-in form and client-side login handling.  
> Lines: 39 &middot; Size: 1.7 KB

````html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign in - MEDRED</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="css/style.css?v=20261111" rel="stylesheet">
</head>
<body>
  <div id="nav"></div>

  <main class="auth-wrap">
    <h1>Sign in</h1>
    <div id="alertBox" class="mb-3"></div>
    <form id="loginForm" novalidate>
      <div class="mb-3">
        <input type="email" class="form-control form-control-lg" id="email" placeholder="Email" required>
      </div>
      <div class="mb-3">
        <input type="password" class="form-control form-control-lg" id="password" placeholder="Password" required>
      </div>
      <button class="btn btn-primary btn-lg w-100" type="submit">Sign in</button>
    </form>
    <p class="text-center mt-3"><a href="forgot-password.html" class="text-decoration-none">Forgot password?</a></p>
    <p class="text-center mt-2">New here? <a href="register.html" class="fw-bold text-decoration-none">Register</a></p>
  </main>

  <div id="footer"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/cart.js?v=20261111"></script>
  <script src="js/app.js?v=20261111"></script>
</body>
</html>
````

### `register.html`

> Distributor/organization registration form with license document upload.  
> Lines: 87 &middot; Size: 4.3 KB

````html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - MEDRED</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="css/style.css?v=20261111" rel="stylesheet">
</head>
<body>
  <div id="nav"></div>

  <main class="auth-wrap">
    <h1>Register</h1>
    <div id="alertBox" class="mb-3"></div>
    <form id="registerForm" novalidate>
      <div class="seg mb-3">
        <input type="radio" class="seg-input" name="role" id="r-org" value="org" checked>
        <label class="seg-label" for="r-org">Organization</label>
        <input type="radio" class="seg-input" name="role" id="r-dist" value="distributor">
        <label class="seg-label" for="r-dist">Distributor</label>
      </div>
      <div class="mb-3">
        <input type="text" class="form-control form-control-lg" id="fullname" placeholder="Organization name" required>
      </div>
      <div class="mb-3">
        <input type="email" class="form-control form-control-lg" id="email" placeholder="Email" required>
      </div>
      <div class="mb-3">
        <input type="password" class="form-control form-control-lg" id="password" minlength="6" placeholder="Password (min 6 characters)" required>
      </div>
      <div class="mb-3">
        <input type="tel" class="form-control form-control-lg" id="phone" placeholder="10-digit contact phone" inputmode="numeric" maxlength="10" minlength="10" pattern="[0-9]{10}" required>
      </div>
      <div class="mb-3">
        <input type="text" class="form-control form-control-lg" id="city" placeholder="City" required>
      </div>
      <div class="mb-1">
        <input type="text" class="form-control form-control-lg" id="credNo" maxlength="60" placeholder="NGO / Trust registration no. *" required>
      </div>
      <div class="form-text mb-3" id="credHint">Darpan ID, society registration or trust deed number.</div>
      <div class="mb-3">
        <label class="form-label small text-secondary">License / Registration Document (image) *</label>
        <input type="file" class="form-control form-control-lg" id="licenseImage" accept="image/*" required>
      </div>
      <div class="mb-3">
        <select class="form-select form-select-lg" id="orgType">
          <option value="NGO">NGO</option>
          <option value="Old-Age Home">Old-Age Home</option>
          <option value="Orphanage">Orphanage</option>
          <option value="Charitable Hospital">Charitable Hospital</option>
          <option value="Medical Shop">Medical Shop</option>
        </select>
      </div>
      <div class="mb-3">
        <textarea class="form-control" id="address" rows="2" placeholder="Address (optional)"></textarea>
      </div>
      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label small">Latitude</label>
          <input type="number" step="any" class="form-control" id="regLat" placeholder="e.g. 17.9736">
        </div>
        <div class="col-6">
          <label class="form-label small">Longitude</label>
          <input type="number" step="any" class="form-control" id="regLng" placeholder="e.g. 79.5962">
        </div>
      </div>
      <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="agree" required>
        <label class="form-check-label small" for="agree">I agree to the terms and verification process</label>
      </div>
      <button class="btn btn-primary btn-lg w-100" type="submit">Submit registration</button>
    </form>
    <p class="text-center mt-4">Already registered? <a href="login.html" class="fw-bold text-decoration-none">Sign in</a></p>
  </main>

  <div id="footer"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/cart.js?v=20261111"></script>
  <script src="js/app.js?v=20261111"></script>
</body>
</html>
````

### `forgot-password.html`

> Three-step email OTP password reset interface.  
> Lines: 121 &middot; Size: 5.4 KB

````html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot password - MEDRED</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="css/style.css?v=20261111" rel="stylesheet">
</head>
<body>
  <div id="nav"></div>

  <main class="auth-wrap">
    <h1>Reset password</h1>
    <div id="alertBox" class="mb-3"></div>

    <form id="emailForm" novalidate>
      <p class="text-secondary small mb-3">Enter the email address you registered with. We will send a 6-digit OTP to it.</p>
      <div class="mb-3">
        <input type="email" class="form-control form-control-lg" id="email" placeholder="Email" required>
      </div>
      <button class="btn btn-primary btn-lg w-100" type="submit">Send OTP</button>
      <p class="text-center mt-4"><a href="login.html" class="fw-bold text-decoration-none">Back to sign in</a></p>
    </form>

    <form id="otpForm" class="d-none" novalidate>
      <p class="text-secondary small mb-3">Enter the 6-digit code sent to <strong id="otpEmail"></strong>.</p>
      <div class="mb-3">
        <input type="text" class="form-control form-control-lg text-center" id="otp" placeholder="000000" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required>
      </div>
      <button class="btn btn-primary btn-lg w-100" type="submit">Verify OTP</button>
      <p class="text-center mt-4"><a href="#" id="restart" class="fw-bold text-decoration-none">Use a different email</a></p>
    </form>

    <form id="resetForm" class="d-none" novalidate>
      <p class="text-secondary small mb-3">OTP verified. Choose a new password.</p>
      <div class="mb-3">
        <input type="password" class="form-control form-control-lg" id="newPassword" placeholder="New password (min 6 characters)" required>
      </div>
      <div class="mb-3">
        <input type="password" class="form-control form-control-lg" id="confirmPassword" placeholder="Confirm new password" required>
      </div>
      <button class="btn btn-primary btn-lg w-100" type="submit">Change password</button>
    </form>

    <div id="doneBox" class="d-none text-center">
      <i class="bi bi-check-circle-fill text-success" style="font-size:3rem"></i>
      <h4 class="mt-3">Password changed successfully.</h4>
      <a href="login.html" class="btn btn-primary btn-lg mt-3">Sign in</a>
    </div>
  </main>

  <div id="footer"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/cart.js?v=20261111"></script>
  <script src="js/app.js?v=20261111"></script>
  <script>
  (function () {
    const $ = s => document.querySelector(s);
    const alertBox = $('#alertBox');
    const showAlert = (msg, type) => alertBox.innerHTML = `<div class="alert alert-${type} py-2 small mb-0">${msg}</div>`;
    let email = '', resetToken = '';
    const post = async body => (await fetch('auth/forgot.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body)
    })).json();

    $('#emailForm').addEventListener('submit', async e => {
      e.preventDefault();
      email = $('#email').value.trim();
      const btn = e.submitter; btn.disabled = true;
      const res = await post({ action: 'request', email });
      btn.disabled = false;
      if (!res.ok) return showAlert(res.error, 'danger');
      showAlert(res.message, res.dev ? 'warning' : 'success');
      $('#otpEmail').textContent = email;
      $('#emailForm').classList.add('d-none');
      $('#otpForm').classList.remove('d-none');
      $('#otp').focus();
    });

    $('#otpForm').addEventListener('submit', async e => {
      e.preventDefault();
      const btn = e.submitter; btn.disabled = true;
      const res = await post({ action: 'verify', email, otp: $('#otp').value.trim() });
      btn.disabled = false;
      if (!res.ok) return showAlert(res.error, 'danger');
      resetToken = res.reset_token;
      alertBox.innerHTML = '';
      $('#otpForm').classList.add('d-none');
      $('#resetForm').classList.remove('d-none');
      $('#newPassword').focus();
    });

    $('#resetForm').addEventListener('submit', async e => {
      e.preventDefault();
      const pass = $('#newPassword').value, confirm = $('#confirmPassword').value;
      if (pass.length < 6) return showAlert('Password must be at least 6 characters.', 'danger');
      if (pass !== confirm) return showAlert('Passwords do not match.', 'danger');
      const btn = e.submitter; btn.disabled = true;
      const res = await post({ action: 'reset', token: resetToken, password: pass });
      btn.disabled = false;
      if (!res.ok) return showAlert(res.error, 'danger');
      $('#resetForm').classList.add('d-none');
      $('#doneBox').classList.remove('d-none');
    });

    $('#restart').addEventListener('click', e => {
      e.preventDefault();
      alertBox.innerHTML = '';
      $('#otpForm').classList.add('d-none');
      $('#emailForm').classList.remove('d-none');
    });
  })();
  </script>
</body>
</html>
````

### `store.html`

> Public medicine catalogue with search, filters and sorting.  
> Lines: 40 &middot; Size: 1.8 KB

````html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Medicines - MEDRED</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="css/style.css?v=20261111" rel="stylesheet">
</head>
<body>
  <div id="nav"></div>

  <main class="container py-5">
    <div class="section-head"><h2>Medicines</h2></div>
    <div class="d-flex flex-column flex-md-row gap-3 mb-3">
      <input class="form-control form-control-lg flex-grow-1" id="fSearch" placeholder="Search by name...">
      <select class="form-select form-select-lg sort-select" id="fSort">
        <option value="expiry">Expiring soonest</option>
        <option value="price_asc">Price: low to high</option>
        <option value="price_desc">Price: high to low</option>
      </select>
      <select class="form-select form-select-lg sort-select" id="fDist">
        <option value="">All distributors</option>
      </select>
    </div>
    <div class="chips mb-4" id="chips"></div>
    <div class="row g-3" id="cards"><div class="text-center text-secondary py-5 col-12"><div class="spinner-border text-primary"></div></div></div>
  </main>

  <div id="footer"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/cart.js?v=20261111"></script>
  <script src="js/store.js?v=20261111"></script>
</body>
</html>
````

### `medicine.html`

> Medicine detail page shell.  
> Lines: 22 &middot; Size: 1 KB

````html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Medicine - MEDRED</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="css/style.css?v=20261111" rel="stylesheet">
</head>
<body>
  <div id="nav"></div>
  <main class="container py-5"><div id="page"></div></main>
  <div id="footer"></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/cart.js?v=20261111"></script>
  <script src="js/store.js?v=20261111"></script>
</body>
</html>
````

### `distributor.html`

> Public distributor profile page shell.  
> Lines: 22 &middot; Size: 1 KB

````html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Distributor - MEDRED</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="css/style.css?v=20261111" rel="stylesheet">
</head>
<body>
  <div id="nav"></div>
  <main class="container py-5"><div id="page"></div></main>
  <div id="footer"></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/cart.js?v=20261111"></script>
  <script src="js/store.js?v=20261111"></script>
</body>
</html>
````

### `cart.html`

> Cart, quantity controls and checkout panel.  
> Lines: 45 &middot; Size: 2 KB

````html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cart - MEDRED</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="css/style.css?v=20261111" rel="stylesheet">
</head>
<body>
  <div id="nav"></div>

  <main class="container py-5">
    <div class="section-head"><h2>Cart</h2></div>
    <p class="cart-empty d-none" id="cartEmpty">Your cart is empty. <a href="store.html">Browse medicines</a></p>
    <div class="row g-4 d-none" id="cartFull">
      <div class="col-lg-8">
        <div class="card"><div class="card-body">
          <div class="table-responsive"><table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Medicine</th><th>Distributor</th><th>Price</th><th style="width:130px">Qty</th><th>Total</th><th></th></tr></thead>
            <tbody id="cartBody"></tbody>
          </table></div>
        </div></div>
      </div>
      <div class="col-lg-4">
        <div class="card"><div class="card-body p-4">
          <h6 class="mb-3">Checkout</h6>
          <div class="d-flex justify-content-between align-items-center mb-3"><span class="text-secondary">Total</span><span class="h4 mb-0 fw-bold" id="cartTotal">&#8377;0</span></div>
          <div id="checkoutBox"></div>
        </div></div>
      </div>
    </div>
  </main>

  <div id="footer"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/cart.js?v=20261111"></script>
  <script src="js/store.js?v=20261111"></script>
</body>
</html>
````

### `profile.html`

> Profile view, account details and edit modal.  
> Lines: 80 &middot; Size: 3.7 KB

````html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My profile - MEDRED</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="css/style.css?v=20261111" rel="stylesheet">
</head>
<body>
  <div id="nav"></div>

  <main class="container py-5 profile-wrap">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <h1 class="profile-title mb-1">My profile</h1>
        <div class="text-secondary" id="profEmail">&nbsp;</div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <span class="pill pill-green" id="profRole">&nbsp;</span>
        <span class="pill pill-gray" id="profVerified">&nbsp;</span>
      </div>
    </div>

    <div class="row g-3 mt-4">
      <div class="col-md-6">
        <div class="card profile-card h-100"><div class="card-body p-4">
          <div class="profile-lbl">Orders placed</div>
          <div class="profile-num" id="profOrders">0</div>
          <a class="profile-link" href="dashboard.html?page=orders">View order history &rarr;</a>
        </div></div>
      </div>
      <div class="col-md-6">
        <div class="card profile-card h-100"><div class="card-body p-4">
          <div class="profile-lbl">Account type</div>
          <div class="profile-account" id="profAccount">&nbsp;</div>
        </div></div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-5 mb-1">
      <h2 class="profile-h2 mb-0">Details</h2>
      <button class="btn btn-white-pill btn-sm" id="editProfileBtn">Edit</button>
    </div>
    <dl class="profile-facts" id="profDetails"></dl>

    <a class="btn btn-white-pill w-100 mt-3" href="auth/logout.php">Sign out</a>
  </main>

  <div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0"><h5 class="modal-title">Edit details</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <form id="editProfileForm" novalidate>
            <div class="mb-2"><label class="form-label small">Full name *</label><input class="form-control" id="epName" required></div>
            <div class="mb-2"><label class="form-label small">Phone</label><input class="form-control" id="epPhone" inputmode="numeric" maxlength="10"></div>
            <div class="mb-3"><label class="form-label small">Address</label><textarea class="form-control" id="epAddress" rows="2"></textarea></div>
            <div class="row g-2 mb-3">
              <div class="col-6"><label class="form-label small">Latitude</label><input type="number" step="any" class="form-control" id="epLat"></div>
              <div class="col-6"><label class="form-label small">Longitude</label><input type="number" step="any" class="form-control" id="epLng"></div>
            </div>
            <button class="btn btn-primary w-100" type="submit">Save changes</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div id="footer"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/cart.js?v=20261111"></script>
  <script src="js/store.js?v=20261111"></script>
</body>
</html>
````

### `dashboard.html`

> Role-aware dashboard shell with order, medicine, review, tracking and camera modals.  
> Lines: 139 &middot; Size: 8.3 KB

````html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - MEDRED</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="css/style.css?v=20261111" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
  <style>
    .track-timeline { list-style: none; padding: 0; margin: 0; }
    .track-timeline li { position: relative; padding: 0 0 22px 30px; color: #6F7A74; font-weight: 600; }
    .track-timeline li::before { content: ''; position: absolute; left: 9px; top: 16px; bottom: -4px; width: 2px; background: #E3E7E4; }
    .track-timeline li:last-child::before { display: none; }
    .track-timeline .dot { position: absolute; left: 3px; top: 2px; width: 14px; height: 14px; border-radius: 50%; border: 2px solid #C9D1CC; background: #fff; }
    .track-timeline li.done { color: #0B5C4E; }
    .track-timeline li.done .dot { background: #0B5C4E; border-color: #0B5C4E; }
  </style>
</head>
<body>
  <div id="nav"></div>

  <main class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <div class="dash-eyebrow">My dashboard</div>
        <h1 id="pageTitle" class="dash-title">Overview</h1>
      </div>
      <div class="d-flex align-items-center gap-3">
        <a class="icon-link" href="profile.html"><i class="bi bi-person"></i> <span>Profile</span></a>
        <a class="btn btn-white-pill btn-sm" href="auth/logout.php"><i class="bi bi-box-arrow-right"></i> Sign out</a>
      </div>
    </div>
    <div class="text-secondary mb-4">Signed in as <b class="text-dark" id="userName">User</b> Â· <span id="userRole">-</span></div>

    <div class="dash-tabs mb-4" id="dashTabs">
      <a class="dash-tab" data-page="overview"><i class="bi bi-speedometer2"></i> Overview</a>
      <a class="dash-tab" data-page="medicines"><i class="bi bi-capsule"></i> Medicines</a>
      <a class="dash-tab" data-page="orders"><i class="bi bi-cart"></i> Orders</a>
<a class="dash-tab" data-page="history"><i class="bi bi-clock-history"></i> Order History</a>
      <a class="dash-tab" data-page="reviews"><i class="bi bi-star"></i> Reviews</a>
      <a class="dash-tab" data-page="notifications"><i class="bi bi-bell"></i> Notifications <span class="badge" id="notifCount" style="display:none">0</span></a>
      <a class="dash-tab" data-page="reports"><i class="bi bi-clipboard-data"></i> Reports</a>
      <a class="dash-tab" data-page="distributors"><i class="bi bi-truck"></i> Distributors</a>
      <a class="dash-tab" data-page="audit"><i class="bi bi-journal-check"></i> Audit Logs</a>
      <a class="dash-tab" data-page="verify"><i class="bi bi-patch-check"></i> Verification</a>
      <a class="dash-tab" data-page="map"><i class="bi bi-map"></i> Delivery Map</a>
    </div>

    <div id="pageContent"><!-- JS renders here --></div>
  </main>

  <div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0"><h5 class="modal-title">Place Order</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="orderModalBody"></div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addMedModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0"><h5 class="modal-title">Add Medicine</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <form id="addMedForm" novalidate>
            <div class="mb-2"><label class="form-label small">Name *</label><input class="form-control" id="mName" required></div>
            <div class="mb-2"><label class="form-label small">Category *</label><input class="form-control" id="mCat" required></div>
            <div class="row g-2 mb-2">
              <div class="col-3"><label class="form-label small">Price *</label><input type="number" step="0.01" min="0.01" class="form-control" id="mPrice" required></div>
              <div class="col-3"><label class="form-label small">MRP</label><input type="number" step="0.01" min="0.01" class="form-control" id="mMrp" placeholder="optional"></div>
              <div class="col-3"><label class="form-label small">Stock *</label><input type="number" min="0" class="form-control" id="mStock" required></div>
              <div class="col-3"><label class="form-label small">Batch No</label><input class="form-control" id="mBatch"></div>
            </div>
            <div class="mb-2">
              <label class="form-label small">Photo (JPG/PNG/WEBP, max 2MB)</label>
              <input type="file" class="form-control" id="mImage" accept=".jpg,.jpeg,.png,.webp">
              <div class="d-flex align-items-center gap-2 mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="takePhotoBtn"><i class="bi bi-camera"></i> Take photo</button>
                <img id="camPreview" class="cam-preview d-none" alt="Captured photo">
                <span id="camNote" class="small text-secondary d-none">Camera photo attached</span>
              </div>
            </div>
            <div class="mb-3"><label class="form-label small">Expiry Date *</label><input type="date" class="form-control" id="mExpiry" required></div>
            <button class="btn btn-primary w-100" type="submit">Save Medicine</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0"><h5 class="modal-title">Review Order</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" id="revOrderId">
          <div class="text-center fs-3 mb-2" id="revStars"><span class="star-btn text-secondary" data-v="1">â˜†</span><span class="star-btn text-secondary" data-v="2">â˜†</span><span class="star-btn text-secondary" data-v="3">â˜†</span><span class="star-btn text-secondary" data-v="4">â˜†</span><span class="star-btn text-secondary" data-v="5">â˜†</span></div>
          <textarea class="form-control mb-3" id="revComment" rows="3" placeholder="Your feedback about this distributor..."></textarea>
          <button class="btn btn-primary w-100" id="submitReview">Submit Review</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="trackModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0"><h5 class="modal-title" id="trackTitle">Order</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="trackBody"></div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="cameraModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0"><h5 class="modal-title">Take photo</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="alert alert-warning small d-none" id="camError">Camera not available or permission denied. Use the file upload instead.</div>
          <video id="camVideo" class="w-100 rounded-3" autoplay muted playsinline></video>
          <button class="btn btn-primary w-100 mt-3" id="camShoot"><i class="bi bi-camera"></i> Capture photo</button>
        </div>
      </div>
    </div>
  </div>

  <div id="footer"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/cart.js?v=20261111"></script>
  <script src="js/app.js?v=20261111"></script>
</body>
</html>
````

### `map.html`

> Leaflet map of verified organizations and distributors.  
> Lines: 88 &middot; Size: 4 KB

````html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delivery Map - MEDRED</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
  <link href="css/style.css?v=20261111" rel="stylesheet">
  <style>
    #map { height: 70vh; width: 100%; border: 1px solid #ddd; border-radius: 12px; }
    .leaflet-popup-content-wrapper { font-family: Inter, sans-serif; }
    .map-actions { display: flex; gap: 8px; margin-bottom: 16px; }
  </style>
</head>
<body>
  <div id="nav"></div>

  <main class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
      <div>
        <div class="dash-eyebrow">Delivery Map</div>
        <h1 class="dash-title">Delivery Destinations</h1>
      </div>
      <div class="map-actions">
        <a class="btn btn-white-pill btn-sm" href="dashboard.html"><i class="bi bi-arrow-left"></i> Back</a>
      </div>
    </div>
    <div class="text-secondary mb-3 small">Verified organizations and distributors with a saved location. Click a marker for address and directions.</div>
    <div id="map"></div>
  </main>

  <div id="footer"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="js/cart.js?v=20261111"></script>
  <script>
    (function () {
      const map = L.map('map', { scrollWheelZoom: false });
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      const markers = {};
      const defaultCenter = [17.3850, 78.4867];
      map.setView(defaultCenter, 11);

      async function load() {
        const res = await fetch('api/locations.php');
        const data = await res.json();
        if (!data.ok) return;
        const pts = data.locations;
        if (!pts.length) {
          document.querySelector('main .text-secondary').textContent = 'No locations available yet. Ask verified organizations to add their location in My Profile.';
          return;
        }
        const group = L.featureGroup().addTo(map);
        pts.forEach(p => {
          const iconColor = p.role === 'distributor' ? '#0B5C4E' : '#198754';
          const marker = L.marker([p.lat, p.lng]).addTo(group);
          const roleBadge = p.role === 'distributor' ? '<span class="badge bg-dark">Distributor</span>' : '<span class="badge bg-success">Organization</span>';
          const extra = (p.role === 'distributor' && p.license_no ? `<div class="small text-muted">License: ${p.license_no}</div>` : (p.registration_no ? `<div class="small text-muted">Reg: ${p.registration_no}</div>` : ''));
          const dest = p.address ? `${p.address}${p.city ? ', ' + p.city : ''}` : `${p.lat},${p.lng}`;
          marker.bindPopup(`
            <div style="min-width:220px">
              <div class="fw-bold">${p.name}</div>
              ${roleBadge}
              <div class="small text-muted mt-1">${p.address || 'Address not provided'}</div>
              <div class="small text-muted">${p.city || ''}</div>
              ${extra}
              <div class="mt-2">
                <a href="https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(dest)}" target="_blank" class="btn btn-sm btn-primary w-100"><i class="bi bi-geo-alt"></i> Get Directions</a>
              </div>
            </div>
          `);
          markers[p.id] = marker;
        });
        map.fitBounds(group.getBounds().pad(0.15));
      }
      load();
    })();
  </script>
</body>
</html>
````

### `delivery-map.html`

> Google Maps navigation view for a selected delivery order.  
> Lines: 86 &middot; Size: 4 KB

````html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delivery Location - MEDRED</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="css/style.css?v=20261111" rel="stylesheet">
  <style>
    #gmap { height: 65vh; width: 100%; border: 1px solid #ddd; border-radius: 12px; }
  </style>
</head>
<body>
  <div id="nav"></div>

  <main class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
      <div>
        <div class="dash-eyebrow">Delivery Map</div>
        <h1 class="dash-title">Customer Location</h1>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a class="btn btn-white-pill btn-sm" href="dashboard.html?page=orders"><i class="bi bi-arrow-left"></i> Back to Orders</a>
      </div>
    </div>

    <div id="mapInfo" class="row g-3 mb-4"></div>
    <iframe id="gmap" style="border:0" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
  </main>

  <div id="footer"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/cart.js?v=20261111"></script>
  <script>
    (function () {
      const data = JSON.parse(localStorage.getItem('deliveryMap') || 'null');
      const info = document.getElementById('mapInfo');
      const frame = document.getElementById('gmap');

      const lat = parseFloat(data?.lat), lng = parseFloat(data?.lng);
      const hasCoords = Number.isFinite(lat) && Number.isFinite(lng) && lat !== 0 && lng !== 0;
      const addr = (data?.addr || '').trim();
      const hasAddr = addr !== '' && addr !== 'Address not provided';

      if (!hasAddr && !hasCoords) {
        info.innerHTML = '<div class="col-12 alert alert-warning">No delivery address or location available for this order.</div>';
        frame.src = 'https://www.google.com/maps?q=17.3850,78.4867&z=11&output=embed';
        return;
      }

      const name = data.name || 'Customer';
      const phone = data.phone || '';
      /* address first; coordinates only when the buyer did not enter an address */
      const dest = hasAddr ? addr : `${lat},${lng}`;
      const embed = `https://www.google.com/maps?q=${encodeURIComponent(dest)}&z=16&output=embed`;
      const directions = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(dest)}`;

      info.innerHTML = `
        <div class="col-md-6">
          <div class="card"><div class="card-body">
            <h6 class="mb-1">${name}</h6>
            <div class="small text-muted mb-2">${hasAddr ? addr : 'Address not provided'}</div>
            ${phone ? `<div class="small mb-2"><i class="bi bi-telephone"></i> ${phone}</div>` : ''}
            ${hasCoords ? `<div class="small">Location: ${lat.toFixed(6)}, ${lng.toFixed(6)}</div>` : ''}
            <a href="${directions}" target="_blank" rel="noopener" class="btn btn-primary btn-sm mt-2"><i class="bi bi-geo-alt"></i> Navigate with Google Maps</a>
          </div></div>
        </div>
        <div class="col-md-6">
          <div class="card"><div class="card-body">
            <h6 class="mb-1">How to use</h6>
            <ul class="list-unstyled small mb-0">
              <li class="mb-2"><i class="bi bi-geo-alt-fill text-primary"></i> Click <b>Navigate with Google Maps</b> to start turn-by-turn directions from your current location.</li>
              <li class="mb-2"><i class="bi bi-truck text-success"></i> Deliver the medicines to the customer's address shown on the map.</li>
            </ul>
          </div></div>
        </div>`;

      frame.src = embed;
    })();
  </script>
</body>
</html>
````

### `health.php`

> Render health check that verifies the PostgreSQL connection.  
> Lines: 15 &middot; Size: 0.4 KB

````php
<?php
/* Render health check: verifies the app can reach Supabase PostgreSQL. */
require __DIR__ . '/config/db.php';

$ok = false;
try {
  db()->query('SELECT 1');
  $ok = true;
} catch (Throwable $e) {
    error_log('MEDRED health check: ' . $e->getMessage());
}

http_response_code($ok ? 200 : 503);
header('Content-Type: application/json');
echo json_encode(['ok' => $ok]);
````

### `.htaccess`

> Blocks direct download of the database schema file.  
> Lines: 4 &middot; Size: 0.1 KB

````apache
# block direct download of the SQL schema
<Files "database.sql">
  Require all denied
</Files>
````

## Authentication

### `auth/login.php`

> Credential validation, session creation, persistent-login cookie and audit entry.  
> Lines: 33 &middot; Size: 1 KB

````php
<?php
require __DIR__ . '/../config/db.php';

ms_session_start();

$d = body();
$email = strtolower(trim($d['email'] ?? ''));
$password = $d['password'] ?? '';

$st = db()->prepare('SELECT id, name, email, password, role, org_type, verified, address, phone FROM users WHERE email = ?');
$st->execute([$email]);
$user = $st->fetch();

if (!$user || !password_verify($password, $user['password'])) {
  respond(['ok' => false, 'error' => 'Invalid email or password.'], 401);
}
if (!$user['verified']) {
  respond(['ok' => false, 'error' => 'Account pending admin verification.'], 403);
}

session_regenerate_id(true);
$_SESSION['user'] = [
  'id' => (int)$user['id'],
  'name' => $user['name'],
  'email' => $user['email'],
  'role' => $user['role'],
  'org_type' => $user['org_type'],
  'address' => $user['address'],
  'phone' => $user['phone'],
];
remember_login($user);
audit_log(db(), (int)$user['id'], 'Logged in');
respond(['ok' => true, 'user' => $_SESSION['user']]);
````

### `auth/register.php`

> Validated registration, license document upload and transactional profile creation.  
> Lines: 76 &middot; Size: 3.3 KB

````php
<?php
require __DIR__ . '/../config/db.php';

$name = trim($_POST['name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$role = strtolower(trim($_POST['role'] ?? ''));
if ($role === 'ngo') $role = 'org';
$orgType = $_POST['orgType'] ?? null;
$phone = preg_replace('/\D/', '', (string)($_POST['phone'] ?? ''));
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$credential = trim($_POST['credential_no'] ?? '');
if (!in_array($orgType, ['NGO', 'Medical Shop', 'Old-Age Home', 'Orphanage', 'Charitable Hospital'], true)) $orgType = 'NGO';

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
  respond(['ok' => false, 'error' => 'Invalid name, email, or password (min 6 chars).'], 422);
}
if (!in_array($role, ['distributor', 'org'], true)) {
  respond(['ok' => false, 'error' => 'Invalid account type.'], 422);
}
if (!preg_match('/^\d{10}$/', $phone) || $city === '') {
  respond(['ok' => false, 'error' => 'A valid 10-digit contact phone and city are required.'], 422);
}
if ($credential === '' || strlen($credential) > 60) {
  respond(['ok' => false, 'error' => 'A valid license / registration number is required (max 60 characters).'], 422);
}

$pdo = db();
$st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$st->execute([$email]);
if ($st->fetch()) {
  respond(['ok' => false, 'error' => 'Email already registered.'], 409);
}

/* license/registration document image (required for both roles) */
$image = null;
if (!empty($_FILES['license_image']['name'])) {
  $f = $_FILES['license_image'];
  [$ext, $mime, $err] = upload_validate($f, ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
  if ($err) respond(['ok' => false, 'error' => $err], 422);
  $stored = storage_store_document($f['tmp_name'], $ext, $mime, $role);
  if ($stored['error']) respond(['ok' => false, 'error' => $stored['error']], 500);
  $image = $stored['path'];
} else {
  respond(['ok' => false, 'error' => 'License / registration document image is required.'], 422);
}

$pdo->beginTransaction();
try {
  $st = $pdo->prepare('INSERT INTO users (name, email, password, phone, address, city, latitude, longitude, role, org_type, verified)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)');
  $st->execute([
    $name, $email, password_hash($password, PASSWORD_DEFAULT),
    $phone, $address, $city,
    $_POST['latitude'] ?? null,
    $_POST['longitude'] ?? null,
    $role, $role === 'org' ? ($orgType ?: 'NGO') : null
  ]);
  $userId = (int)$pdo->lastInsertId();
  if ($role === 'distributor') {
    $pdo->prepare('INSERT INTO distributors (user_id, license_no, license_image, verified) VALUES (?, ?, ?, 0)')
        ->execute([$userId, $credential, $image]);
  } else {
    $pdo->prepare('INSERT INTO ngos (user_id, org_type, registration_no, license_image, verified) VALUES (?, ?, ?, ?, 0)')
        ->execute([$userId, $orgType ?: 'NGO', $credential, $image]);
  }
  audit_log($pdo, $userId, 'Registered account (pending verification)');
  $pdo->commit();
} catch (Exception $e) {
  $pdo->rollBack();
  if ($image) storage_delete_upload($image, $role);
  respond(['ok' => false, 'error' => 'Registration failed.'], 500);
}

respond(['ok' => true, 'message' => 'Registration submitted. Await admin verification before login.']);
````

### `auth/logout.php`

> Destroys the session and clears the remember cookie.  
> Lines: 9 &middot; Size: 0.2 KB

````php
<?php
require __DIR__ . '/../config/db.php';
ms_session_start();
$_SESSION = [];
forget_login();
setcookie(session_name(), '', ms_cookie_options(time() - 3600));
session_destroy();
header('Location: ../index.html');
exit;
````

### `auth/session.php`

> Returns the currently signed-in user.  
> Lines: 5 &middot; Size: 0.2 KB

````php
<?php
require __DIR__ . '/../config/db.php';
$u = current_user();
if (!$u) respond(['ok' => false, 'error' => 'Not logged in.'], 401);
respond(['ok' => true, 'user' => $u]);
````

### `auth/forgot.php`

> OTP request/verify and signed-token password reset flow.  
> Lines: 114 &middot; Size: 4.9 KB

````php
<?php
/* Password reset: email OTP over SMTP -> short-lived reset token -> new password. */
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['ok' => false, 'error' => 'Method not allowed.'], 405);

$d = body();
$action = $d['action'] ?? '';
$email = strtolower(trim($d['email'] ?? ''));

if (in_array($action, ['verify', 'reset'], true) && RESET_SECRET === '') {
  respond(['ok' => false, 'error' => 'Password reset is not configured.'], 503);
}

function otp_file(string $email): string {
  return sys_get_temp_dir() . '/medred_otp/' . md5($email) . '.json';
}

function otp_save(string $email, string $otp): void {
  $f = otp_file($email);
  if (!is_dir(dirname($f))) @mkdir(dirname($f), 0755, true);
  file_put_contents($f, json_encode(['hash' => password_hash($otp, PASSWORD_DEFAULT), 'expires' => time() + 600, 'tries' => 0]), LOCK_EX);
}

function otp_read(string $email): ?array {
  $f = otp_file($email);
  return is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
}

function otp_mail(string $email, string $otp): bool {
  $body = "Hello,\n\nYour MEDRED password reset OTP is: $otp\n\n"
        . "This code expires in 10 minutes. If you did not request it, ignore this email.\n\n-- MEDRED Team";
  return smtp_send($email, 'MEDRED password reset OTP', $body);
}

if ($action === 'request') {
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(['ok' => false, 'error' => 'Enter a valid email address.'], 422);
  $st = db()->prepare('SELECT id FROM users WHERE lower(email) = ?');
  $st->execute([$email]);
  if (!$st->fetch()) {
    respond(['ok' => true, 'message' => 'If that email is registered, a 6-digit OTP has been sent.']);
  }

  /* direct SMTP delivery is preferred: it does not depend on Supabase's
     magic-link template containing the {{ .Token }} placeholder. */
  if (SMTP_HOST !== '' && SMTP_USER !== '') {
    $otp = (string)random_int(100000, 999999);
    otp_save($email, $otp);
    if (otp_mail($email, $otp)) {
      respond(['ok' => true, 'message' => 'A 6-digit OTP has been sent to your email.']);
    }
    @unlink(otp_file($email));
    error_log('MEDRED: SMTP OTP delivery failed.');
  }

  if (APP_ENV === 'production') {
    respond(['ok' => false, 'error' => 'Password reset email is not configured. Contact the administrator.'], 503);
  }
  $otp = (string)random_int(100000, 999999);
  otp_save($email, $otp);
  file_put_contents(sys_get_temp_dir() . '/medred_otp.log', '[' . date('Y-m-d H:i:s') . "] $email OTP: $otp\n", FILE_APPEND | LOCK_EX);
  respond(['ok' => true, 'dev' => true, 'message' => 'Development mode: the OTP was written to ' . sys_get_temp_dir() . '/medred_otp.log']);
}

if ($action === 'verify') {
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(['ok' => false, 'error' => 'Enter a valid email address.'], 422);
  $otp = preg_replace('/\D/', '', (string)($d['otp'] ?? ''));
  if (strlen($otp) !== 6) respond(['ok' => false, 'error' => 'Enter the 6-digit OTP.'], 422);

  $f = otp_file($email);
  $rec = otp_read($email);
  if ($rec) {
    if ($rec['expires'] < time()) {
      @unlink($f);
      respond(['ok' => false, 'error' => 'OTP expired. Request a new one.'], 422);
    }
    if (!password_verify($otp, $rec['hash'])) {
      $rec['tries'] = (int)($rec['tries'] ?? 0) + 1;
      if ($rec['tries'] >= 5) @unlink($f); else file_put_contents($f, json_encode($rec), LOCK_EX);
      respond(['ok' => false, 'error' => 'Invalid OTP.'], 422);
    }
    @unlink($f);
  } else {
    if (APP_ENV === 'production') {
      respond(['ok' => false, 'error' => 'Password reset email is not configured. Contact the administrator.'], 503);
    }
    respond(['ok' => false, 'error' => 'OTP expired. Request a new one.'], 422);
  }

  $exp = time() + 600;
  $sig = hash_hmac('sha256', $email . '|' . $exp, RESET_SECRET);
  respond(['ok' => true, 'reset_token' => base64_encode($email . '|' . $exp . '|' . $sig)]);
}

if ($action === 'reset') {
  $token = (string)($d['token'] ?? '');
  $pass = (string)($d['password'] ?? '');
  if (strlen($pass) < 6) respond(['ok' => false, 'error' => 'Password must be at least 6 characters.'], 422);
  $raw = base64_decode($token, true);
  $parts = $raw ? explode('|', $raw) : [];
  if (count($parts) !== 3) respond(['ok' => false, 'error' => 'Invalid reset request.'], 422);
  [$tokEmail, $exp, $sig] = $parts;
  if ((int)$exp < time() || !hash_equals(hash_hmac('sha256', $tokEmail . '|' . $exp, RESET_SECRET), $sig)) {
    respond(['ok' => false, 'error' => 'Reset expired. Start again.'], 422);
  }
  $st = db()->prepare('UPDATE users SET password = ? WHERE lower(email) = ? RETURNING id');
  $st->execute([password_hash($pass, PASSWORD_DEFAULT), $tokEmail]);
  $uid = (int)$st->fetchColumn();
  if (!$uid) respond(['ok' => false, 'error' => 'Account not found.'], 404);
  audit_log(db(), $uid, "Password reset for $tokEmail");
  respond(['ok' => true, 'message' => 'Password changed successfully.']);
}

respond(['ok' => false, 'error' => 'Invalid action.'], 422);
````

## API Endpoints

### `api/medicines.php`

> Inventory add/update/delete, public catalogue, expiry and stock sweeps.  
> Lines: 174 &middot; Size: 8 KB

````php
<?php
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_role('distributor');
  $pdo = db();
  $u = current_user();
  $action = in_array($_POST['action'] ?? '', ['add', 'update', 'delete'], true) ? $_POST['action'] : 'add';

  $st = $pdo->prepare('SELECT id FROM distributors WHERE user_id = ?');
  $st->execute([$u['id']]);
  $distId = (int)$st->fetchColumn();

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare('SELECT name FROM medicines WHERE id = ? AND distributor_id = ? AND deleted = 0');
    $st->execute([$id, $distId]);
    $name = $st->fetchColumn();
    if ($name === false) respond(['ok' => false, 'error' => 'Medicine not found.'], 404);
    $pdo->prepare('UPDATE medicines SET deleted = 1, stock = 0 WHERE id = ?')->execute([$id]);
    audit_log($pdo, $u['id'], "Removed medicine: $name");
    respond(['ok' => true, 'id' => $id]);
  }

  $name = trim($_POST['name'] ?? '');
  $cat = trim($_POST['category'] ?? '');
  $price = (float)($_POST['price'] ?? 0);
  $mrp = (float)($_POST['mrp'] ?? 0);
  if ($mrp <= $price) $mrp = null;
  $stock = (int)($_POST['stock'] ?? 0);
  $batch = trim($_POST['batch_no'] ?? '');
  $expiry = $_POST['expiry'] ?? '';
  if ($name === '' || $cat === '' || $price <= 0 || $stock < 0 || !strtotime($expiry) || $expiry < date('Y-m-d')) {
    respond(['ok' => false, 'error' => 'Invalid medicine details (expiry must be a future date).'], 422);
  }

  $image = null;
  if (!empty($_FILES['image']['name'])) {
    $f = $_FILES['image'];
    [$ext, $mime, $err] = upload_validate($f, ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
    if ($err) respond(['ok' => false, 'error' => $err], 422);
    $stored = storage_store_medicine($f['tmp_name'], $ext, $mime);
    if ($stored['error']) respond(['ok' => false, 'error' => $stored['error']], 500);
    $image = $stored['path'];
  }

  if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare('SELECT id, image FROM medicines WHERE id = ? AND distributor_id = ? AND deleted = 0');
    $st->execute([$id, $distId]);
    $old = $st->fetch();
    if (!$old) respond(['ok' => false, 'error' => 'Medicine not found.'], 404);
    $pdo->prepare('UPDATE medicines SET name=?, category=?, price=?, mrp=?, stock=?, batch_no=?, image=?, expiry=?,
                   low_stock_notified = CASE WHEN ? >= 250 THEN 0 ELSE low_stock_notified END,
                   deleted = CASE WHEN ? = 0 THEN 1 ELSE 0 END WHERE id=?')
        ->execute([$name, $cat, $price, $mrp, $stock, $batch, $image ?: $old['image'], $expiry, $stock, $stock, $id]);
    if ($image && $old['image']) storage_delete_upload($old['image']);
    if ($stock === 0) {
      notify($pdo, $u['id'], 'low_stock', "Your medicine '$name' is out of stock and was removed from the store.");
    }
    audit_log($pdo, $u['id'], "Updated medicine: $name");
    respond(['ok' => true, 'id' => $id]);
  }

  $st = $pdo->prepare('INSERT INTO medicines (distributor_id, name, category, price, mrp, stock, batch_no, image, expiry) VALUES (?,?,?,?,?,?,?,?,?) RETURNING id');
  $st->execute([$distId, $name, $cat, $price, $mrp, $stock, $batch, $image, $expiry]);
  $newId = (int)$st->fetchColumn();
  audit_log($pdo, $u['id'], "Added medicine: $name");
  respond(['ok' => true, 'id' => $newId]);
}

/* ---------- GET (public) ---------- */
if (isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $cached = cache_get("medicines:item:$id", 20);
  if ($cached !== null) respond($cached);
  $pdo = db();
  $st = $pdo->prepare('SELECT m.*, u.name AS distributor, u.phone, u.address, u.id AS distributor_user_id,
                              COALESCE(AVG(r.rating), 0) AS rating
                       FROM medicines m
                       JOIN distributors d ON d.id = m.distributor_id
                       JOIN users u ON u.id = d.user_id
                       LEFT JOIN orders o ON o.distributor_id = d.id
                       LEFT JOIN reviews r ON r.order_id = o.id
                       WHERE m.id = ? AND m.deleted = 0 GROUP BY m.id, u.id, u.name, u.phone, u.address');
  $st->execute([$id]);
  $m = $st->fetch();
  if (!$m) respond(['ok' => false, 'error' => 'Medicine not found.'], 404);
  $m['shelf_months'] = max(0, (int)((strtotime($m['expiry']) - time()) / 2592000));
  $m['image'] = storage_medicine_url($m['image']);
  $out = ['ok' => true, 'medicine' => $m];
  cache_put("medicines:item:$id", $out);
  respond($out);
}

$u = current_user();
$where = ['m.expiry >= CURRENT_DATE', 'm.deleted = 0'];
$args = [];

$q = strtolower(trim($_GET['q'] ?? ''));
$cat = $_GET['cat'] ?? '';
$min = isset($_GET['shelf_min']) ? (int)$_GET['shelf_min'] : 0;
$max = isset($_GET['shelf_max']) ? (int)$_GET['shelf_max'] : 0;

$cacheKey = 'medicines:list:' . ($u['id'] ?? 0) . ':' . md5(json_encode([$q, $cat, $min, $max]));
$cached = cache_get($cacheKey, 20);
if ($cached !== null) respond($cached);

$pdo = db();

/* sweep: expired medicines are removed from the store, distributor notified once */
$expired = $pdo->query("SELECT m.id, m.name, m.batch_no, m.expiry, d.user_id
                        FROM medicines m JOIN distributors d ON d.id = m.distributor_id
                        WHERE m.expiry < CURRENT_DATE AND m.deleted = 0")->fetchAll();
if ($expired) {
  $hide = $pdo->prepare('UPDATE medicines SET stock = 0, deleted = 1 WHERE id = ?');
  foreach ($expired as $m) {
    $hide->execute([(int)$m['id']]);
    notify($pdo, (int)$m['user_id'], 'expiry', "Your medicine '{$m['name']}' (Batch " . ($m['batch_no'] ?: '-') . ") expired on {$m['expiry']} and was removed from the store.");
  }
}

/* sweep: out-of-stock medicines are removed directly, distributor notified once */
$zero = $pdo->query("SELECT m.id, m.name, d.user_id
                     FROM medicines m JOIN distributors d ON d.id = m.distributor_id
                     WHERE m.stock <= 0 AND m.deleted = 0")->fetchAll();
if ($zero) {
  $hide = $pdo->prepare('UPDATE medicines SET deleted = 1 WHERE id = ?');
  foreach ($zero as $m) {
    $hide->execute([(int)$m['id']]);
    notify($pdo, (int)$m['user_id'], 'low_stock', "Your medicine '{$m['name']}' is out of stock and was removed from the store.");
  }
}

/* sweep: low stock alert (stock < 250) — notify distributor once per medicine */
$low = $pdo->query("SELECT m.id, m.name, m.batch_no, m.stock, d.user_id
                    FROM medicines m JOIN distributors d ON d.id = m.distributor_id
                    WHERE m.stock > 0 AND m.stock < 250 AND m.low_stock_notified = 0 AND m.deleted = 0")->fetchAll();
if ($low) {
  $mark = $pdo->prepare('UPDATE medicines SET low_stock_notified = 1 WHERE id = ?');
  foreach ($low as $m) {
    $mark->execute([(int)$m['id']]);
    notify($pdo, (int)$m['user_id'], 'low_stock', "Your medicine '{$m['name']}' (Batch " . ($m['batch_no'] ?: '-') . ") is low on stock ({$m['stock']} units left).");
  }
}

if ($u && $u['role'] === 'distributor') {
  $st = $pdo->prepare('SELECT id FROM distributors WHERE user_id = ?');
  $st->execute([$u['id']]);
  $where[] = 'm.distributor_id = ?';
  $args[] = (int)$st->fetchColumn();
}

$sql = 'SELECT m.*, u.name AS distributor, u.phone, u.address, u.id AS distributor_user_id
        FROM medicines m
        JOIN distributors d ON d.id = m.distributor_id
        JOIN users u ON u.id = d.user_id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY m.expiry';
$st = $pdo->prepare($sql);
$st->execute($args);
$medicines = $st->fetchAll();

foreach ($medicines as &$m) {
  $m['shelf_months'] = max(0, (int)((strtotime($m['expiry']) - time()) / 2592000));
  $m['image'] = storage_medicine_url($m['image']);
  $skip = ($q && !str_contains(strtolower($m['name']), $q)) || ($cat && $m['category'] !== $cat);
  if ($min && $m['shelf_months'] < $min) $skip = true;
  if ($max && $m['shelf_months'] > $max) $skip = true;
  if ($skip) $m = null;
}
$out = ['ok' => true, 'medicines' => array_values(array_filter($medicines))];
cache_put($cacheKey, $out);
respond($out);
````

### `api/orders.php`

> Checkout, role-scoped order listing and the complete order state machine.  
> Lines: 276 &middot; Size: 14.6 KB

````php
<?php
require __DIR__ . '/../config/db.php';
$u = current_user();
if (!$u) respond(['ok' => false, 'error' => 'Not logged in.'], 401);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pdo = db();
  $d = body();
  $action = $d['action'] ?? '';

  /* distributor accepts/rejects a pending order */
  if ($action === 'accept' || $action === 'reject') {
    require_role('distributor');
    $orderId = (int)($d['order_id'] ?? 0);
    $st = $pdo->prepare('SELECT o.id, o.status, o.org_user_id FROM orders o
                         JOIN distributors dd ON dd.id = o.distributor_id
                         WHERE o.id = ? AND dd.user_id = ?');
    $st->execute([$orderId, $u['id']]);
    $order = $st->fetch();
    if (!$order) respond(['ok' => false, 'error' => 'Order not found.'], 404);
    if ($order['status'] !== 'Pending') respond(['ok' => false, 'error' => "Order is already " . strtolower($order['status']) . '.'], 409);

    if ($action === 'accept') {
      $pdo->prepare("WITH upd AS (UPDATE orders SET status = 'Accepted' WHERE id = ? RETURNING id)
                     INSERT INTO audit_logs (user_id, action) SELECT ?, 'Accepted order #' || id FROM upd")
          ->execute([$orderId, $u['id']]);
      notify($pdo, (int)$order['org_user_id'], 'order', "Order #$orderId was accepted by the distributor.");
      respond(['ok' => true, 'status' => 'Accepted']);
    }

    $pdo->beginTransaction();
    try {
      $st = $pdo->prepare('SELECT medicine_id, qty FROM order_items WHERE order_id = ?');
      $st->execute([$orderId]);
      foreach ($st->fetchAll() as $it) {
        $pdo->prepare('UPDATE medicines SET stock = stock + ? WHERE id = ?')->execute([(int)$it['qty'], (int)$it['medicine_id']]);
      }
      $pdo->prepare('UPDATE medicines SET low_stock_notified = 0 WHERE stock >= 250')->execute();
      $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ?")->execute([$orderId]);
      $pdo->prepare("UPDATE transactions SET status = 'Cancelled' WHERE order_id = ?")->execute([$orderId]);
      notify($pdo, (int)$order['org_user_id'], 'order', "Order #$orderId was rejected by the distributor. Reserved stock restored.");
      audit_log($pdo, $u['id'], "Rejected order #$orderId");
      $pdo->commit();
      respond(['ok' => true, 'status' => 'Cancelled']);
    } catch (Exception $e) {
      $pdo->rollBack();
      respond(['ok' => false, 'error' => 'Could not reject the order.'], 500);
    }
  }

  /* distributor packs an accepted order */
  if ($action === 'pack') {
    require_role('distributor');
    $orderId = (int)($d['order_id'] ?? 0);
    $st = $pdo->prepare('SELECT o.id, o.status, o.org_user_id FROM orders o
                         JOIN distributors dd ON dd.id = o.distributor_id
                         WHERE o.id = ? AND dd.user_id = ?');
    $st->execute([$orderId, $u['id']]);
    $order = $st->fetch();
    if (!$order) respond(['ok' => false, 'error' => 'Order not found.'], 404);
    if ($order['status'] !== 'Accepted') respond(['ok' => false, 'error' => 'Only accepted orders can be packed.'], 409);
    $pdo->prepare("WITH upd AS (UPDATE orders SET status = 'Packed' WHERE id = ? RETURNING id)
                   INSERT INTO audit_logs (user_id, action) SELECT ?, 'Packed order #' || id FROM upd")
        ->execute([$orderId, $u['id']]);
    notify($pdo, (int)$order['org_user_id'], 'order', "Order #$orderId has been packed and is ready to ship.");
    respond(['ok' => true, 'status' => 'Packed']);
  }

  /* distributor ships a packed order */
  if ($action === 'ship') {
    require_role('distributor');
    $orderId = (int)($d['order_id'] ?? 0);
    $st = $pdo->prepare('SELECT o.id, o.status, o.org_user_id FROM orders o
                         JOIN distributors dd ON dd.id = o.distributor_id
                         WHERE o.id = ? AND dd.user_id = ?');
    $st->execute([$orderId, $u['id']]);
    $order = $st->fetch();
    if (!$order) respond(['ok' => false, 'error' => 'Order not found.'], 404);
    if ($order['status'] !== 'Packed') {
      respond(['ok' => false, 'error' => 'Only packed orders can be shipped.'], 409);
    }
    $pdo->prepare("WITH upd AS (UPDATE orders SET status = 'Shipped' WHERE id = ? RETURNING id)
                   INSERT INTO audit_logs (user_id, action) SELECT ?, 'Shipped order #' || id FROM upd")
        ->execute([$orderId, $u['id']]);
    notify($pdo, (int)$order['org_user_id'], 'order', "Order #$orderId has been shipped.");
    respond(['ok' => true, 'status' => 'Shipped']);
  }

  /* distributor hands the parcel to the delivery agent */
  if ($action === 'out_for_delivery') {
    require_role('distributor');
    $orderId = (int)($d['order_id'] ?? 0);
    $st = $pdo->prepare('SELECT o.id, o.status, o.org_user_id FROM orders o
                         JOIN distributors dd ON dd.id = o.distributor_id
                         WHERE o.id = ? AND dd.user_id = ?');
    $st->execute([$orderId, $u['id']]);
    $order = $st->fetch();
    if (!$order) respond(['ok' => false, 'error' => 'Order not found.'], 404);
    if ($order['status'] !== 'Shipped') respond(['ok' => false, 'error' => 'Only shipped orders can go out for delivery.'], 409);
    $pdo->prepare("WITH upd AS (UPDATE orders SET status = 'Out for Delivery' WHERE id = ? RETURNING id)
                   INSERT INTO audit_logs (user_id, action) SELECT ?, 'Out for delivery: order #' || id FROM upd")
        ->execute([$orderId, $u['id']]);
    notify($pdo, (int)$order['org_user_id'], 'order', "Order #$orderId is out for delivery.");
    respond(['ok' => true, 'status' => 'Out for Delivery']);
  }

  /* distributor delivers a shipped order (with tracking number) */
  if ($action === 'deliver') {
    require_role('distributor');
    $orderId = (int)($d['order_id'] ?? 0);
    $tracking = trim($d['tracking_number'] ?? '');
    $st = $pdo->prepare('SELECT o.id, o.status, o.org_user_id FROM orders o
                         JOIN distributors dd ON dd.id = o.distributor_id
                         WHERE o.id = ? AND dd.user_id = ?');
    $st->execute([$orderId, $u['id']]);
    $order = $st->fetch();
    if (!$order) respond(['ok' => false, 'error' => 'Order not found.'], 404);
    if ($order['status'] !== 'Out for Delivery') respond(['ok' => false, 'error' => 'Order must be out for delivery first.'], 409);
    $audit = "Delivered order #$orderId" . ($tracking ? " (tracking $tracking)" : '');
    $pdo->prepare("WITH upd AS (UPDATE orders SET status = 'Delivered', tracking_number = ?, delivered_at = NOW() WHERE id = ? RETURNING id)
                   INSERT INTO audit_logs (user_id, action) SELECT ?, ? FROM upd")
        ->execute([$tracking ?: null, $orderId, $u['id'], $audit]);
    /* cash on delivery: money is collected when the parcel is handed over */
    $pdo->prepare("UPDATE transactions SET status = 'Completed' WHERE order_id = ? AND method = 'Cash' AND status = 'Pending'")
        ->execute([$orderId]);
    notify($pdo, (int)$order['org_user_id'], 'order', "Order #$orderId was delivered." . ($tracking ? " Tracking: $tracking." : ''));
    respond(['ok' => true, 'status' => 'Delivered']);
  }

  /* organization cancels an order that has not been accepted yet */
  if ($action === 'cancel') {
    require_role('org');
    $orderId = (int)($d['order_id'] ?? 0);
    $st = $pdo->prepare('SELECT o.id, o.status, d.user_id AS dist_user_id
                         FROM orders o JOIN distributors d ON d.id = o.distributor_id
                         WHERE o.id = ? AND o.org_user_id = ?');
    $st->execute([$orderId, $u['id']]);
    $order = $st->fetch();
    if (!$order) respond(['ok' => false, 'error' => 'Order not found.'], 404);
    if (!in_array($order['status'], ['Pending', 'Pending Payment'], true)) {
      respond(['ok' => false, 'error' => 'This order can no longer be cancelled.'], 409);
    }
    $pdo->beginTransaction();
    try {
      $st = $pdo->prepare('SELECT medicine_id, qty FROM order_items WHERE order_id = ?');
      $st->execute([$orderId]);
      foreach ($st->fetchAll() as $it) {
        $pdo->prepare('UPDATE medicines SET stock = stock + ? WHERE id = ?')->execute([(int)$it['qty'], (int)$it['medicine_id']]);
      }
      $pdo->prepare('UPDATE medicines SET low_stock_notified = 0 WHERE stock >= 250')->execute();
      $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ?")->execute([$orderId]);
      $pdo->prepare("UPDATE transactions SET status = 'Cancelled' WHERE order_id = ?")->execute([$orderId]);
      notify($pdo, (int)$order['dist_user_id'], 'order', "Order #$orderId was cancelled by the buyer. Reserved stock restored.");
      audit_log($pdo, $u['id'], "Cancelled order #$orderId");
      $pdo->commit();
      respond(['ok' => true, 'status' => 'Cancelled']);
    } catch (Exception $e) {
      $pdo->rollBack();
      respond(['ok' => false, 'error' => 'Could not cancel the order.'], 500);
    }
  }

  require_role('org');
  $items = $d['items'] ?? [];
  if (empty($items) || !is_array($items)) respond(['ok' => false, 'error' => 'Cart is empty.'], 422);

  $delivery = trim($d['delivery_address'] ?? '');
  if ($delivery === '') {
    /* dashboard quick-order: fall back to the buyer's saved profile address */
    $st = $pdo->prepare('SELECT address FROM users WHERE id = ?');
    $st->execute([$u['id']]);
    $delivery = trim((string)$st->fetchColumn());
  }
  if ($delivery === '') respond(['ok' => false, 'error' => 'Delivery address is required. Add one in your profile.'], 422);

  $phone = preg_replace('/\D/', '', (string)($d['delivery_phone'] ?? ''));
  if (!preg_match('/^\d{10}$/', $phone)) respond(['ok' => false, 'error' => 'Enter a valid 10-digit mobile number for delivery.'], 422);

  $lat = trim($d['latitude'] ?? '');
  $lng = trim($d['longitude'] ?? '');

  $method = in_array($d['method'] ?? '', ['Cash', 'Bank Transfer', 'UPI', 'Card', 'Razorpay'], true) ? $d['method'] : 'Bank Transfer';
  $reference = trim($d['reference'] ?? '');

  /* group items by distributor so each order has one distributor_id */
  $pdo->beginTransaction();
  try {
    $orderIds = [];
    $ordersByDist = [];
    foreach ($items as $it) {
      $medId = (int)($it['medicine_id'] ?? 0);
      $qty = (int)($it['qty'] ?? 0);
      if ($qty <= 0) throw new Exception('Invalid quantity.');
      $st = $pdo->prepare('SELECT m.id, m.name, m.price, m.stock, m.distributor_id, m.expiry, d.user_id AS dist_user_id
                           FROM medicines m JOIN distributors d ON d.id = m.distributor_id
                           WHERE m.id = ? AND m.deleted = 0 FOR UPDATE');
      $st->execute([$medId]);
      $med = $st->fetch();
      if (!$med) throw new Exception('Medicine not found.');
      if ($med['expiry'] < date('Y-m-d')) throw new Exception("{$med['name']} has expired and is no longer available.");
      if ($med['stock'] < $qty) throw new Exception("Insufficient stock for {$med['name']}.");

      $pdo->prepare('UPDATE medicines SET stock = stock - ? WHERE id = ?')->execute([$qty, $medId]);
      if ((int)$med['stock'] - $qty <= 0) {
        /* sold out: remove it from the store and tell the distributor */
        $pdo->prepare('UPDATE medicines SET deleted = 1 WHERE id = ?')->execute([$medId]);
        notify($pdo, (int)$med['dist_user_id'], 'low_stock', "{$med['name']} is now out of stock and was removed from the store.");
      }
      if (!isset($ordersByDist[$med['distributor_id']])) {
        $ordersByDist[$med['distributor_id']] = ['user_id' => (int)$med['dist_user_id'], 'items' => []];
      }
      $ordersByDist[$med['distributor_id']]['items'][] = [$med, $qty];
    }

    foreach ($ordersByDist as $distId => $group) {
      $list = $group['items'];
      $total = 0;
      foreach ($list as [$med, $qty]) $total += $med['price'] * $qty;
      if ($total < 500) throw new Exception('Minimum order is ₹500 per distributor. Please add more medicines from this distributor.');
      $status = $method === 'Razorpay' ? 'Pending Payment' : 'Pending';
      $pdo->prepare('INSERT INTO orders (org_user_id, distributor_id, delivery_address, delivery_phone, latitude, longitude, total, status) VALUES (?,?,?,?,?,?,?,?)')
          ->execute([$u['id'], $distId, $delivery, $phone, $lat ?: null, $lng ?: null, $total, $status]);
      $orderId = (int)$pdo->lastInsertId();
      foreach ($list as [$med, $qty]) {
        $pdo->prepare('INSERT INTO order_items (order_id, medicine_id, qty, price) VALUES (?,?,?,?)')
            ->execute([$orderId, $med['id'], $qty, $med['price']]);
      }
      $pdo->prepare('INSERT INTO transactions (order_id, amount, method, status, reference) VALUES (?,?,?,?,?)')
          ->execute([$orderId, $total, $method, 'Pending', $reference]);
      $names = implode(', ', array_map(fn($x) => "{$x[0]['name']} x{$x[1]}", $list));
      notify($pdo, $u['id'], 'order', "Order #$orderId placed: $names");
      notify($pdo, (int)$group['user_id'], 'order', "New order #$orderId: $names");
      audit_log($pdo, $u['id'], "Placed order #$orderId (₹$total)");
      $orderIds[] = $orderId;
    }
    $pdo->commit();
    respond(['ok' => true, 'order_ids' => $orderIds]);
  } catch (Exception $e) {
    $pdo->rollBack();
    respond(['ok' => false, 'error' => $e->getMessage()], 409);
  }
}

/* ---------- GET ---------- */
$cacheKey = 'orders:' . $u['role'] . ':' . $u['id'];
$cached = cache_get($cacheKey, 10);
if ($cached !== null) respond($cached);

$pdo = db();
$sql = 'SELECT o.*, u.name AS buyer, COALESCE(o.latitude, u.latitude) AS org_lat, COALESCE(o.longitude, u.longitude) AS org_lng,
               t.status AS payment_status, t.method, t.reference,
               du.name AS distributor_name, du.latitude AS dist_lat, du.longitude AS dist_lng,
               string_agg(m.name || \' x\' || oi.qty, \', \') AS items
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN medicines m ON m.id = oi.medicine_id
        JOIN users u ON u.id = o.org_user_id
        JOIN distributors d ON d.id = o.distributor_id
        JOIN users du ON du.id = d.user_id
        JOIN transactions t ON t.order_id = o.id';
$args = [];
if ($u['role'] === 'org') {
  $sql .= ' WHERE o.org_user_id = ?';
  $args[] = $u['id'];
} elseif ($u['role'] === 'distributor') {
  $st = $pdo->prepare('SELECT id FROM distributors WHERE user_id = ?');
  $st->execute([$u['id']]);
  $sql .= ' WHERE o.distributor_id = ?';
  $args[] = (int)$st->fetchColumn();
}
$sql .= ' GROUP BY o.id, u.name, u.latitude, u.longitude, t.status, t.method, t.reference, du.name, du.latitude, du.longitude ORDER BY o.id DESC';
$st = $pdo->prepare($sql);
$st->execute($args);
$out = ['ok' => true, 'orders' => $st->fetchAll()];
cache_put($cacheKey, $out);
respond($out);
````

### `api/payment.php`

> Razorpay order creation, signature verification and manual payment confirmation.  
> Lines: 137 &middot; Size: 6.5 KB

````php
<?php
/* MEDRED payments: Razorpay online payments + manual confirmation (cash/bank). */
require __DIR__ . '/../config/db.php';
$u = current_user();
if (!$u) respond(['ok' => false, 'error' => 'Not logged in.'], 401);

$d = body();
$action = $d['action'] ?? 'confirm';
$pdo = db();

/* minimal Razorpay REST client (Basic auth, JSON) */
function rzp_request(string $method, string $path, array $data = []): array {
  $ch = curl_init('https://api.razorpay.com/v1/' . $path);
  $opts = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 20,
  ];
  if ($method === 'POST') { $opts[CURLOPT_POST] = true; $opts[CURLOPT_POSTFIELDS] = json_encode($data); }
  curl_setopt_array($ch, $opts);
  $res = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($res === false) return ['ok' => false, 'error' => 'Payment gateway unreachable. Try again.'];
  $json = json_decode($res, true);
  if ($code >= 400) return ['ok' => false, 'error' => $json['error']['description'] ?? 'Payment gateway error.'];
  return ['ok' => true, 'data' => $json];
}

/* buyer starts an online payment for one or more unpaid orders */
if ($action === 'create') {
  require_role('org');
  $ids = array_values(array_unique(array_filter(array_map('intval', (array)($d['order_ids'] ?? [])))));
  if (!$ids) respond(['ok' => false, 'error' => 'order_ids required.'], 422);

  $in = implode(',', array_fill(0, count($ids), '?'));
  $st = $pdo->prepare("SELECT o.id, o.total, t.status AS txn_status, t.method
                       FROM orders o JOIN transactions t ON t.order_id = o.id
                       WHERE o.id IN ($in) AND o.org_user_id = ?");
  $st->execute([...$ids, $u['id']]);
  $rows = $st->fetchAll();
  if (count($rows) !== count($ids)) respond(['ok' => false, 'error' => 'Order not found.'], 404);

  $total = 0;
  foreach ($rows as $r) {
    if ($r['txn_status'] !== 'Pending') respond(['ok' => false, 'error' => "Order #{$r['id']} is already paid."], 409);
    if ($r['method'] !== 'Razorpay') respond(['ok' => false, 'error' => "Order #{$r['id']} is a cash-on-delivery order."], 409);
    $total += (float)$r['total'];
  }

  $rzp = rzp_request('POST', 'orders', [
    'amount' => (int)round($total * 100),
    'currency' => 'INR',
    'receipt' => 'ms_' . $ids[0] . '_' . time(),
    'notes' => ['order_ids' => implode(',', $ids)],
  ]);
  if (!$rzp['ok']) respond(['ok' => false, 'error' => $rzp['error']], 502);

  $rzpOrderId = $rzp['data']['id'];
  $pdo->prepare("UPDATE transactions SET reference = ? WHERE order_id IN ($in) AND status = 'Pending'")
      ->execute([$rzpOrderId, ...$ids]);
  respond([
    'ok' => true,
    'key_id' => RAZORPAY_KEY_ID,
    'razorpay_order_id' => $rzpOrderId,
    'amount' => (int)round($total * 100),
    'total' => $total,
  ]);
}

/* buyer returns from Razorpay Checkout: verify the signature server-side */
if ($action === 'verify') {
  require_role('org');
  $rzpOrderId = trim($d['razorpay_order_id'] ?? '');
  $paymentId = trim($d['razorpay_payment_id'] ?? '');
  $signature = trim($d['razorpay_signature'] ?? '');
  if ($rzpOrderId === '' || $paymentId === '' || $signature === '') {
    respond(['ok' => false, 'error' => 'Payment details missing.'], 422);
  }

  $expected = hash_hmac('sha256', $rzpOrderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);
  if (!hash_equals($expected, $signature)) respond(['ok' => false, 'error' => 'Payment verification failed.'], 400);

  $st = $pdo->prepare("SELECT t.order_id FROM transactions t JOIN orders o ON o.id = t.order_id
                       WHERE t.reference = ? AND o.org_user_id = ?");
  $st->execute([$rzpOrderId, $u['id']]);
  $orderIds = $st->fetchAll(PDO::FETCH_COLUMN);
  if (!$orderIds) respond(['ok' => false, 'error' => 'Order not found.'], 404);

  $in = implode(',', array_fill(0, count($orderIds), '?'));
  $pdo->prepare("UPDATE transactions SET status = 'Completed', reference = ? WHERE order_id IN ($in)")
      ->execute([$paymentId, ...$orderIds]);
  /* the order is confirmed only once the online payment is verified */
  $pdo->prepare("UPDATE orders SET status = 'Pending' WHERE id IN ($in) AND status = 'Pending Payment'")
      ->execute($orderIds);
  foreach ($orderIds as $id) {
    audit_log($pdo, $u['id'], "Paid online for order #$id (Razorpay $paymentId)");
    $st = $pdo->prepare('SELECT d.user_id FROM orders o JOIN distributors d ON d.id = o.distributor_id WHERE o.id = ?');
    $st->execute([$id]);
    $distUser = (int)$st->fetchColumn();
    if ($distUser) notify($pdo, $distUser, 'order', "Payment received for order #$id. The order is now confirmed.");
  }
  respond(['ok' => true, 'order_ids' => $orderIds, 'status' => 'Completed']);
}

/* distributor manually confirms cash/bank payments */
require_role('distributor');
$orderId = (int)($d['order_id'] ?? 0);
$status = in_array($d['status'] ?? '', ['Completed', 'Failed'], true) ? $d['status'] : '';
if (!$orderId || !$status) respond(['ok' => false, 'error' => 'order_id and status (Completed/Failed) required.'], 422);

$st = $pdo->prepare('SELECT o.id, o.status, o.org_user_id, t.status AS txn_status, t.amount, t.method
                     FROM orders o
                     JOIN distributors dd ON dd.id = o.distributor_id
                     JOIN transactions t ON t.order_id = o.id
                     WHERE o.id = ? AND dd.user_id = ?');
$st->execute([$orderId, $u['id']]);
$order = $st->fetch();
if (!$order) respond(['ok' => false, 'error' => 'Order not found.'], 404);
if ($order['method'] === 'Razorpay') respond(['ok' => false, 'error' => 'Online payments are verified automatically.'], 409);
if ($order['txn_status'] !== 'Pending') respond(['ok' => false, 'error' => "Payment already {$order['txn_status']}."], 409);

$pdo->beginTransaction();
try {
  $pdo->prepare('UPDATE transactions SET status = ? WHERE order_id = ?')->execute([$status, $orderId]);
  notify($pdo, (int)$order['org_user_id'], 'order',
    $status === 'Completed'
      ? "Payment confirmed for Order #$orderId ({$order['method']} {$order['amount']})."
      : "Payment failed for Order #$orderId.");
  audit_log($pdo, $u['id'], "Payment {$status} for order #$orderId");
  $pdo->commit();
  respond(['ok' => true, 'order_id' => $orderId, 'status' => $status]);
} catch (Exception $e) {
  $pdo->rollBack();
  respond(['ok' => false, 'error' => 'Could not update payment.'], 500);
}
````

### `api/distributors.php`

> Public distributor profiles, verified list and admin removal.  
> Lines: 67 &middot; Size: 3.3 KB

````php
<?php
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_role('admin');
  $pdo = db();
  $d = body();
  $userId = (int)($d['user_id'] ?? 0);
  $action = $d['action'] ?? '';
  if ($action !== 'remove' || !$userId) respond(['ok' => false, 'error' => 'Invalid request.'], 422);

  $st = $pdo->prepare('SELECT d.id, (SELECT COUNT(*) FROM medicines m WHERE m.distributor_id = d.id) AS meds,
                       (SELECT COUNT(*) FROM orders o WHERE o.distributor_id = d.id) AS orders
                       FROM distributors d WHERE d.user_id = ?');
  $st->execute([$userId]);
  $dist = $st->fetch();
  if (!$dist) respond(['ok' => false, 'error' => 'Distributor not found.'], 404);
  if ($dist['meds'] > 0 || $dist['orders'] > 0) {
    respond(['ok' => false, 'error' => "Cannot remove: distributor has {$dist['meds']} medicine(s) and {$dist['orders']} order(s). Delete those first."], 409);
  }
  $st = $pdo->prepare('SELECT name FROM users WHERE id = ?');
  $st->execute([$userId]);
  $name = $st->fetchColumn();
  $pdo->prepare('DELETE FROM audit_logs WHERE user_id = ?')->execute([$userId]);
  $pdo->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$userId]);
  $pdo->prepare('DELETE FROM distributors WHERE user_id = ?')->execute([$userId]);
  $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
  audit_log($pdo, $_SESSION['user']['id'], "Removed distributor: $name");
  respond(['ok' => true]);
}

/* GET: public profile (?id=N) or admin list (no params) */
if (isset($_GET['id'])) {
  $pdo = db();
  $st = $pdo->prepare('SELECT u.id, u.name, u.phone, u.address, u.email, u.verified, d.license_no, d.id AS dist_id, u.created_at
                       FROM users u JOIN distributors d ON d.user_id = u.id WHERE u.id = ?');
  $st->execute([(int)$_GET['id']]);
  $prof = $st->fetch();
  if (!$prof) respond(['ok' => false, 'error' => 'Distributor not found.'], 404);
  $st = $pdo->prepare('SELECT id, name, category, price, mrp, stock, batch_no, expiry FROM medicines WHERE distributor_id = ?
                       ORDER BY expiry');
  $st->execute([$prof['dist_id']]);
  $prof['medicines'] = $st->fetchAll();
  respond(['ok' => true, 'profile' => $prof]);
}

/* GET: public list of verified distributors (store filter) */
if (isset($_GET['list'])) {
  $cached = cache_get('distributors:list', 60);
  if ($cached !== null) respond($cached);
  $st = db()->query('SELECT u.id, u.name, u.city FROM distributors d JOIN users u ON u.id = d.user_id
                     WHERE d.verified = 1 AND u.verified = 1 ORDER BY u.name');
  $out = ['ok' => true, 'distributors' => $st->fetchAll()];
  cache_put('distributors:list', $out);
  respond($out);
}

require_role('admin');
$cached = cache_get('distributors:admin', 30);
if ($cached !== null) respond($cached);
$st = db()->query('SELECT u.id, u.name, u.email, u.phone, u.verified, u.created_at, d.license_no,
                   (SELECT COUNT(*) FROM medicines m WHERE m.distributor_id = d.id) AS meds,
                   (SELECT COUNT(*) FROM orders o WHERE o.distributor_id = d.id) AS orders
                   FROM distributors d JOIN users u ON u.id = d.user_id ORDER BY u.id');
$out = ['ok' => true, 'distributors' => $st->fetchAll()];
cache_put('distributors:admin', $out);
respond($out);
````

### `api/verify.php`

> Admin approval and rejection of pending accounts with document cleanup.  
> Lines: 67 &middot; Size: 3.2 KB

````php
<?php
require __DIR__ . '/../config/db.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pdo = db();
  $d = body();
  $userId = (int)($d['user_id'] ?? 0);
  $action = $d['action'] ?? '';
  if (!in_array($action, ['approve', 'reject'], true) || !$userId) {
    respond(['ok' => false, 'error' => 'Invalid request.'], 422);
  }
  $st = $pdo->prepare("SELECT u.id, u.name, u.role, u.verified,
                              COALESCE(d.license_image, n.license_image) AS license_image
                       FROM users u
                       LEFT JOIN distributors d ON d.user_id = u.id
                       LEFT JOIN ngos n ON n.user_id = u.id
                       WHERE u.id = ? AND u.role <> 'admin'");
  $st->execute([$userId]);
  $account = $st->fetch();
  if (!$account) respond(['ok' => false, 'error' => 'Account not found.'], 404);
  if ((int)$account['verified'] !== 0) respond(['ok' => false, 'error' => 'This account is already verified.'], 409);

  $pdo->beginTransaction();
  try {
    if ($action === 'approve') {
      $pdo->prepare('UPDATE users SET verified = 1 WHERE id = ?')->execute([$userId]);
      $pdo->prepare('UPDATE distributors SET verified = 1 WHERE user_id = ?')->execute([$userId]);
      $pdo->prepare('UPDATE ngos SET verified = 1 WHERE user_id = ?')->execute([$userId]);
      notify($pdo, $userId, 'verify', 'Your account was approved. You can now log in.');
    } else {
      /* reject = remove the pending registration completely, so it leaves the
         verification list and the email can be used to register again. */
      $pdo->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$userId]);
      $pdo->prepare('DELETE FROM audit_logs WHERE user_id = ?')->execute([$userId]);
      $pdo->prepare('DELETE FROM distributors WHERE user_id = ?')->execute([$userId]);
      $pdo->prepare('DELETE FROM ngos WHERE user_id = ?')->execute([$userId]);
      $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
    }
    audit_log($pdo, (int)$_SESSION['user']['id'], ucfirst($action) . 'd account: ' . $account['name']);
    $pdo->commit();
    if ($action === 'reject') storage_delete_upload($account['license_image'], $account['role']);
    respond(['ok' => true]);
  } catch (Exception $e) {
    $pdo->rollBack();
    respond(['ok' => false, 'error' => 'Update failed.'], 500);
  }
}

$cached = cache_get('verify:pending', 10);
if ($cached !== null) respond($cached);
$pdo = db();
$st = $pdo->query("SELECT u.id, u.name, u.role, u.org_type, u.created_at, u.email, u.phone, u.city,
                          d.license_no, n.registration_no,
                          COALESCE(d.license_image, n.license_image) AS license_image
                   FROM users u
                   LEFT JOIN distributors d ON d.user_id = u.id
                   LEFT JOIN ngos n ON n.user_id = u.id
                   WHERE u.verified = 0 AND u.role <> 'admin' ORDER BY u.id");
$pending = $st->fetchAll();
foreach ($pending as &$p) {
  $p['license_image'] = storage_document_url($p['license_image'], $p['role']);
}
unset($p);
$out = ['ok' => true, 'pending' => $pending];
cache_put('verify:pending', $out);
respond($out);
````

### `api/reviews.php`

> Delivered-order reviews, pending reviews and rating aggregates.  
> Lines: 81 &middot; Size: 3.6 KB

````php
<?php
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_role('org');
  $pdo = db();
  $u = current_user();
  $d = body();
  $orderId = (int)($d['order_id'] ?? 0);
  $rating = (int)($d['rating'] ?? 0);
  if ($rating < 1 || $rating > 5) respond(['ok' => false, 'error' => 'Rating must be 1-5.'], 422);

  $st = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND org_user_id = ? AND status = 'Delivered'");
  $st->execute([$orderId, $u['id']]);
  if (!$st->fetch()) respond(['ok' => false, 'error' => 'Order not found or not delivered.'], 403);

  $st = $pdo->prepare('SELECT id FROM reviews WHERE order_id = ? AND user_id = ?');
  $st->execute([$orderId, $u['id']]);
  if ($st->fetch()) respond(['ok' => false, 'error' => 'You already reviewed this order.'], 409);

  $st = $pdo->prepare('INSERT INTO reviews (order_id, user_id, rating, comment) VALUES (?,?,?,?)');
  $st->execute([$orderId, $u['id'], $rating, trim($d['comment'] ?? '')]);
  audit_log($pdo, $u['id'], "Reviewed order #$orderId ($rating stars)");
  respond(['ok' => true]);
}

/* GET: the signed-in organization's own reviews + delivered orders awaiting a review */
if (($_GET['mine'] ?? '') !== '') {
  require_role('org');
  $u = current_user();
  $cacheKey = 'reviews:mine:' . $u['id'];
  $cached = cache_get($cacheKey, 15);
  if ($cached !== null) respond($cached);
  $pdo = db();
  $pending = $pdo->prepare(
    "SELECT o.id, o.total, o.created_at, u2.name AS distributor,
            (SELECT string_agg(m.name || ' x' || oi.qty, ', ')
             FROM order_items oi JOIN medicines m ON m.id = oi.medicine_id
             WHERE oi.order_id = o.id) AS items
     FROM orders o
     JOIN distributors d ON d.id = o.distributor_id
     JOIN users u2 ON u2.id = d.user_id
     WHERE o.org_user_id = ? AND o.status = 'Delivered'
       AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.order_id = o.id AND r.user_id = ?)
     ORDER BY o.id DESC"
  );
  $pending->execute([$u['id'], $u['id']]);
  $mine = $pdo->prepare(
    'SELECT r.id, r.order_id, r.rating, r.comment, r.created_at, u2.name AS distributor
     FROM reviews r
     JOIN orders o ON o.id = r.order_id
     JOIN distributors d ON d.id = o.distributor_id
     JOIN users u2 ON u2.id = d.user_id
     WHERE r.user_id = ? ORDER BY r.id DESC'
  );
  $mine->execute([$u['id']]);
  $avg = $pdo->prepare('SELECT COALESCE(AVG(rating), 0) AS avg, COUNT(*) AS count FROM reviews WHERE user_id = ?');
  $avg->execute([$u['id']]);
  $out = ['ok' => true, 'pending' => $pending->fetchAll(), 'reviews' => $mine->fetchAll(), 'stats' => $avg->fetch()];
  cache_put($cacheKey, $out);
  respond($out);
}

/* GET: reviews for a distributor (public) */
$distId = (int)($_GET['distributor_id'] ?? 0);
if (!$distId) respond(['ok' => false, 'error' => 'distributor_id required.'], 422);
$cached = cache_get("reviews:dist:$distId", 20);
if ($cached !== null) respond($cached);
$pdo = db();
$st = $pdo->prepare('SELECT r.rating, r.comment, r.created_at, u.name AS reviewer
                     FROM reviews r
                     JOIN orders o ON o.id = r.order_id
                     JOIN users u ON u.id = r.user_id
                     WHERE o.distributor_id = ? ORDER BY r.id DESC LIMIT 50');
$st->execute([$distId]);
$avg = $pdo->prepare('SELECT COALESCE(AVG(r.rating), 0) AS avg, COUNT(*) AS count
                      FROM reviews r JOIN orders o ON o.id = r.order_id WHERE o.distributor_id = ?');
$avg->execute([$distId]);
$out = ['ok' => true, 'avg' => $avg->fetch(), 'reviews' => $st->fetchAll()];
cache_put("reviews:dist:$distId", $out);
respond($out);
````

### `api/reports.php`

> Platform statistics, category stock, expiry risk and CSV export.  
> Lines: 47 &middot; Size: 2.3 KB

````php
<?php
require __DIR__ . '/../config/db.php';
current_user() ?? respond(['ok' => false, 'error' => 'Not logged in.'], 401);

$cached = cache_get('reports:stats', 20);
if ($cached !== null && ($_GET['format'] ?? '') !== 'csv') respond($cached);

$pdo = db();
$s = $pdo->query("SELECT
  (SELECT COUNT(*) FROM medicines WHERE deleted = 0) AS total,
  (SELECT COUNT(*) FROM medicines WHERE deleted = 0 AND stock > 0 AND stock < 250) AS low,
  (SELECT COUNT(*) FROM medicines WHERE deleted = 0 AND expiry BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '3 months') AS expiring,
  (SELECT COALESCE(SUM(amount),0) FROM transactions WHERE status = 'Completed') AS revenue")->fetch();
$total = (int)$s['total']; $low = (int)$s['low']; $expiring = (int)$s['expiring']; $revenue = (float)$s['revenue'];

$byCat = [];
foreach ($pdo->query('SELECT category, SUM(stock) AS s FROM medicines WHERE deleted = 0 GROUP BY category') as $row) {
  $byCat[$row['category']] = (int)$row['s'];
}
$risk = $pdo->query('SELECT m.name, u.name AS distributor, m.expiry
                     FROM medicines m JOIN distributors d ON d.id = m.distributor_id
                     JOIN users u ON u.id = d.user_id
                     WHERE m.deleted = 0 AND m.expiry BETWEEN CURRENT_DATE AND (CURRENT_DATE + INTERVAL \'3 months\')
                     ORDER BY m.expiry')->fetchAll();

if (($_GET['format'] ?? '') === 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="medshare_reports_' . date('Y-m-d') . '.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Metric', 'Value']);
  fputcsv($out, ['Medicines Listed', $total]);
  fputcsv($out, ['Low Stock', $low]);
  fputcsv($out, ['Expiring <= 3 Months', $expiring]);
  fputcsv($out, ['Revenue (Completed)', $revenue]);
  fputcsv($out, []);
  fputcsv($out, ['Category', 'Stock']);
  foreach ($byCat as $cat => $stock) fputcsv($out, [$cat, $stock]);
  fputcsv($out, []);
  fputcsv($out, ['Medicine', 'Distributor', 'Expiry']);
  foreach ($risk as $m) fputcsv($out, [$m['name'], $m['distributor'], $m['expiry']]);
  fclose($out);
  exit;
}

$out = ['ok' => true, 'stats' => compact('total', 'low', 'expiring', 'revenue', 'byCat', 'risk')];
cache_put('reports:stats', $out);
respond($out);
````

### `api/notifications.php`

> Notification list and mark-all-read action.  
> Lines: 25 &middot; Size: 0.9 KB

````php
<?php
require __DIR__ . '/../config/db.php';
$u = current_user();
if (!$u) respond(['ok' => false, 'error' => 'Not logged in.'], 401);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pdo = db();
  $d = body();
  if (($d['action'] ?? '') !== 'read_all') {
    respond(['ok' => false, 'error' => 'Invalid action.'], 422);
  }
  $st = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
  $st->execute([$u['id']]);
  respond(['ok' => true, 'marked' => $st->rowCount()]);
}

$cacheKey = 'notifications:' . $u['id'];
$cached = cache_get($cacheKey, 10);
if ($cached !== null) respond($cached);
$pdo = db();
$st = $pdo->prepare('SELECT type, text, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$st->execute([$u['id']]);
$out = ['ok' => true, 'notifications' => $st->fetchAll()];
cache_put($cacheKey, $out);
respond($out);
````

### `api/locations.php`

> Verified users with coordinates for delivery maps.  
> Lines: 46 &middot; Size: 1.7 KB

````php
<?php
/* MEDRED location API - returns verified orgs/distributors with lat/lng for the delivery map. */
require __DIR__ . '/../config/db.php';
current_user() ?? respond(['ok' => false, 'error' => 'Not logged in.'], 401);

/* show verified orgs (delivery destinations); admin/distributor can see all */
$role = current_user()['role'];
$cacheKey = 'locations:' . current_user()['id'];
$cached = cache_get($cacheKey, 30);
if ($cached !== null) respond($cached);
$pdo = db();
$sql = 'SELECT u.id, u.name, u.role, u.org_type, u.city, u.latitude, u.longitude, u.address,
               d.license_no, n.registration_no
        FROM users u
        LEFT JOIN distributors d ON d.user_id = u.id
        LEFT JOIN ngos n ON n.user_id = u.id
        WHERE u.verified = 1 AND u.latitude IS NOT NULL AND u.longitude IS NOT NULL';
$args = [];
if ($role === 'distributor') {
  $st = $pdo->prepare('SELECT id FROM distributors WHERE user_id = ?');
  $st->execute([current_user()['id']]);
  $myDistId = (int)$st->fetchColumn();
  $sql .= ' AND u.role <> ?';
  $args[] = 'distributor';
}
$sql .= ' ORDER BY u.name';
$st = $pdo->prepare($sql);
$st->execute($args);
$locations = [];
foreach ($st->fetchAll() as $row) {
  $locations[] = [
    'id' => (int)$row['id'],
    'name' => $row['name'],
    'role' => $row['role'],
    'org_type' => $row['org_type'],
    'city' => $row['city'],
    'address' => $row['address'],
    'lat' => (float)$row['latitude'],
    'lng' => (float)$row['longitude'],
    'license_no' => $row['license_no'],
    'registration_no' => $row['registration_no'],
  ];
}
$out = ['ok' => true, 'locations' => $locations, 'my_role' => $role];
cache_put($cacheKey, $out);
respond($out);
````

### `api/profile.php`

> Profile read/update and role-specific order count.  
> Lines: 54 &middot; Size: 2.3 KB

````php
<?php
require __DIR__ . '/../config/db.php';
$pdo = db();
$u = current_user();
if (!$u) respond(['ok' => false, 'error' => 'Not logged in.'], 401);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $b = body();
  $name = trim($b['name'] ?? '');
  $phone = preg_replace('/\D/', '', (string)($b['phone'] ?? ''));
  $address = trim($b['address'] ?? '');
  $lat = trim($b['latitude'] ?? '');
  $lng = trim($b['longitude'] ?? '');
  if ($name === '') respond(['ok' => false, 'error' => 'Full name is required.'], 422);
  if ($phone !== '' && !preg_match('/^\d{10}$/', $phone)) respond(['ok' => false, 'error' => 'Phone must be a 10-digit mobile number.'], 422);
  $st = $pdo->prepare('UPDATE users SET name = ?, phone = ?, address = ?, latitude = ?, longitude = ? WHERE id = ?');
  $st->execute([$name, $phone ?: null, $address, $lat ?: null, $lng ?: null, $u['id']]);
  ms_session_start();
  $_SESSION['user']['name'] = $name;
  $_SESSION['user']['phone'] = $phone ?: null;
  session_write_close();
  audit_log($pdo, (int)$u['id'], 'Updated profile');
  respond(['ok' => true]);
}

$st = $pdo->prepare('SELECT id, name, email, phone, address, city, latitude, longitude, role, org_type, verified, created_at FROM users WHERE id = ?');
$st->execute([$u['id']]);
$p = $st->fetch();
if (!$p) respond(['ok' => false, 'error' => 'User not found.'], 404);

$p['license_no'] = null;
$p['registration_no'] = null;
if ($p['role'] === 'distributor') {
  $st = $pdo->prepare('SELECT license_no FROM distributors WHERE user_id = ?');
  $st->execute([$u['id']]);
  $p['license_no'] = $st->fetchColumn() ?: null;
} elseif ($p['role'] === 'org') {
  $st = $pdo->prepare('SELECT registration_no FROM ngos WHERE user_id = ?');
  $st->execute([$u['id']]);
  $p['registration_no'] = $st->fetchColumn() ?: null;
}

if ($p['role'] === 'org') {
  $st = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE org_user_id = ?');
  $st->execute([$u['id']]);
} elseif ($p['role'] === 'distributor') {
  $st = $pdo->prepare('SELECT COUNT(*) FROM orders o JOIN distributors d ON d.id = o.distributor_id WHERE d.user_id = ?');
  $st->execute([$u['id']]);
} else {
  $st = $pdo->query('SELECT COUNT(*) FROM orders');
}
$p['orders_count'] = (int)$st->fetchColumn();

respond(['ok' => true, 'profile' => $p]);
````

### `api/audit.php`

> Latest audit log entries for administrators.  
> Lines: 12 &middot; Size: 0.5 KB

````php
<?php
require __DIR__ . '/../config/db.php';
require_role('admin');
$cached = cache_get('audit:logs', 15);
if ($cached !== null) respond($cached);
$st = db()->prepare('SELECT a.action, a.created_at, COALESCE(u.name, \'system\') AS user
                     FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
                     ORDER BY a.id DESC LIMIT 50');
$st->execute();
$out = ['ok' => true, 'logs' => $st->fetchAll()];
cache_put('audit:logs', $out);
respond($out);
````

## Configuration

### `config/db.php`

> Environment/secrets loading, PDO, sessions, cache, notifications, SMTP and shared helpers.  
> Lines: 291 &middot; Size: 11.1 KB

````php
<?php
/* MEDRED configuration + JSON helpers. Supabase Postgres + Storage.
   Values come from environment variables first (Render), then from the
   gitignored config/secrets.php for local XAMPP use. */

function env_value(string $key, string $default = ''): string {
  $v = getenv($key);
  return ($v === false || $v === '') ? $default : $v;
}

function db_dsn_from_url(string $url): ?array {
  $p = parse_url($url);
  if (!$p || empty($p['host'])) return null;
  $db = ltrim($p['path'] ?? '', '/');
  if ($db === '') $db = 'postgres';
  $ssl = 'require';
  if (!empty($p['query'])) {
    parse_str($p['query'], $q);
    if (!empty($q['sslmode'])) $ssl = $q['sslmode'];
  }
  return [
    'dsn' => sprintf('pgsql:host=%s;port=%d;dbname=%s;sslmode=%s', $p['host'], $p['port'] ?? 5432, $db, $ssl),
    'user' => urldecode($p['user'] ?? ''),
    'pass' => urldecode($p['pass'] ?? ''),
  ];
}

$secretsFile = __DIR__ . '/secrets.php';
$SECRETS = is_file($secretsFile) ? (require $secretsFile) : [];

$dbDsn = env_value('DB_DSN', $SECRETS['db_dsn'] ?? '');
$dbUser = env_value('DB_USER', $SECRETS['db_user'] ?? '');
$dbPass = env_value('DB_PASS', $SECRETS['db_pass'] ?? '');
$databaseUrl = env_value('DATABASE_URL', $SECRETS['database_url'] ?? '');
if ($databaseUrl !== '') {
  $url = db_dsn_from_url($databaseUrl);
  if ($url) {
    if ($dbDsn === '') $dbDsn = $url['dsn'];
    if ($dbUser === '') $dbUser = $url['user'];
    if ($dbPass === '') $dbPass = $url['pass'];
  }
}
if ($dbDsn !== '' && !str_contains($dbDsn, 'connect_timeout')) {
  $dbDsn = rtrim($dbDsn, ';') . ';connect_timeout=5';
}

define('DB_DSN', $dbDsn);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('RAZORPAY_KEY_ID', env_value('RAZORPAY_KEY_ID', $SECRETS['razorpay_key_id'] ?? ''));
define('RAZORPAY_KEY_SECRET', env_value('RAZORPAY_KEY_SECRET', $SECRETS['razorpay_key_secret'] ?? ''));
define('SUPABASE_URL', env_value('SUPABASE_URL', $SECRETS['supabase_url'] ?? ''));
define('SUPABASE_ANON_KEY', env_value('SUPABASE_ANON_KEY', $SECRETS['supabase_anon_key'] ?? ''));
define('SUPABASE_SERVICE_ROLE_KEY', env_value('SUPABASE_SERVICE_ROLE_KEY', $SECRETS['supabase_service_role_key'] ?? ''));
define('RESET_SECRET', env_value('RESET_SECRET', $SECRETS['reset_secret'] ?? ''));
define('APP_ENV', env_value('APP_ENV', getenv('PORT') !== false ? 'production' : 'development'));
define('MEDRED_EMAIL', filter_var(env_value('MEDRED_EMAIL', env_value('MEDISHARE_EMAIL', 'false')), FILTER_VALIDATE_BOOLEAN));
define('SMTP_HOST', env_value('SMTP_HOST', $SECRETS['smtp_host'] ?? ''));
define('SMTP_PORT', env_value('SMTP_PORT', $SECRETS['smtp_port'] ?? '587'));
define('SMTP_SECURE', env_value('SMTP_SECURE', $SECRETS['smtp_secure'] ?? 'tls'));
define('SMTP_USER', env_value('SMTP_USER', $SECRETS['smtp_user'] ?? ''));
define('SMTP_PASS', env_value('SMTP_PASS', $SECRETS['smtp_pass'] ?? ''));
define('SMTP_FROM', env_value('SMTP_FROM', $SECRETS['smtp_from'] ?? 'noreply@medred.local'));

/* ---------- session / persistent login ---------- */
function ms_is_https(): bool {
  if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') return true;
  return str_contains(strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')), 'https');
}

function ms_cookie_options(int $expires): array {
  return ['expires' => $expires, 'path' => '/', 'secure' => ms_is_https(), 'httponly' => true, 'samesite' => 'Lax'];
}

function ms_session_start(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  $life = 60 * 60 * 24 * 30; /* 30 days, cleared on sign out */
  ini_set('session.gc_maxlifetime', (string)$life);
  ini_set('session.cookie_lifetime', (string)$life);
  session_set_cookie_params([
    'lifetime' => $life,
    'path' => '/',
    'secure' => ms_is_https(),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

/* Signed remember cookie so a login survives browser restarts and Render
   restarts (which lose PHP session files). Invalidated by password reset. */
function remember_login(array $user): void {
  if (RESET_SECRET === '') return;
  $exp = time() + 60 * 60 * 24 * 30;
  $sig = hash_hmac('sha256', $user['id'] . '|' . $exp . '|' . $user['password'], RESET_SECRET);
  setcookie('medred_remember', base64_encode($user['id'] . '|' . $exp . '|' . $sig), ms_cookie_options($exp));
}

function forget_login(): void {
  setcookie('medred_remember', '', ms_cookie_options(time() - 3600));
}

function remember_user(): ?array {
  if (RESET_SECRET === '' || empty($_COOKIE['medred_remember'])) return null;
  $raw = base64_decode((string)$_COOKIE['medred_remember'], true);
  $parts = $raw ? explode('|', $raw) : [];
  if (count($parts) !== 3) { forget_login(); return null; }
  [$id, $exp, $sig] = $parts;
  if ((int)$exp < time()) { forget_login(); return null; }
  $st = db()->prepare('SELECT id, name, email, role, org_type, address, phone, password, verified FROM users WHERE id = ?');
  $st->execute([(int)$id]);
  $user = $st->fetch();
  if (!$user || !(int)$user['verified']) { forget_login(); return null; }
  $expected = hash_hmac('sha256', $user['id'] . '|' . $exp . '|' . $user['password'], RESET_SECRET);
  if (!hash_equals($expected, $sig)) { forget_login(); return null; }
  return [
    'id' => (int)$user['id'], 'name' => $user['name'], 'email' => $user['email'],
    'role' => $user['role'], 'org_type' => $user['org_type'],
    'address' => $user['address'], 'phone' => $user['phone'],
  ];
}

require __DIR__ . '/storage.php';

if (DB_DSN === '') {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'error' => 'Server not configured: set DB_DSN or DATABASE_URL.']);
  exit;
}

function db(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    try {
      $pdo = new PDO(
        DB_DSN,
        DB_USER,
        DB_PASS,
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          /* ponytail: no persistent connections - the Supabase pooler drops idle
             sessions and a reused dead handle causes fatal 500s. */
        ]
      );
    } catch (PDOException $e) {
      error_log('MEDRED DB connection failed: ' . $e->getMessage());
      respond(['ok' => false, 'error' => 'Database unavailable. Check the Supabase connection and try again.'], 503);
    }
  }
  return $pdo;
}

function respond(array $data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

function body(): array {
  return json_decode(file_get_contents('php://input'), true) ?? [];
}

/* Tiny file cache for GET endpoints: a Supabase round trip costs 0.5-3s, a
   local JSON file costs ~1ms. TTLs are short and every POST clears it, so the
   worst case is a few seconds of staleness for other users. */
function cache_file(string $key): string {
  [$ns] = explode(':', $key, 2);
  return sys_get_temp_dir() . '/medshare_cache/' . preg_replace('/[^a-z0-9_]/i', '_', $ns) . '/' . md5($key) . '.json';
}
function cache_get(string $key, int $ttl) {
  $f = cache_file($key);
  if (is_file($f) && time() - filemtime($f) < $ttl) {
    $d = json_decode((string)file_get_contents($f), true);
    if (is_array($d)) return $d;
  }
  return null;
}
function cache_put(string $key, array $data): void {
  $f = cache_file($key);
  if (!is_dir(dirname($f))) @mkdir(dirname($f), 0755, true);
  @file_put_contents($f, json_encode($data), LOCK_EX);
}
function cache_clear(): void {
  foreach (glob(sys_get_temp_dir() . '/medshare_cache/*/*.json') ?: [] as $f) @unlink($f);
}

function audit_log(PDO $pdo, ?int $userId, string $action): void {
  $st = $pdo->prepare('INSERT INTO audit_logs (user_id, action) VALUES (?, ?)');
  $st->execute([$userId ?? 0, $action]);
}

/* Minimal SMTP sender over cURL. Used only when MEDRED_EMAIL is true and
   SMTP_HOST is set; otherwise falls back to PHP mail(). */
function smtp_send(string $to, string $subject, string $body): bool {
  $secure = strtolower(SMTP_SECURE);
  $scheme = $secure === 'ssl' ? 'smtps' : 'smtp';
  $port = (int)(SMTP_PORT ?: ($secure === 'ssl' ? 465 : 587));
  $from = SMTP_FROM;
  $headers = [
    'To: <' . $to . '>',
    'From: MEDRED <' . $from . '>',
    'Subject: ' . $subject,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
  ];
  $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
  $fp = fopen('php://temp', 'r+');
  fwrite($fp, $message);
  rewind($fp);
  $ch = curl_init($scheme . '://' . SMTP_HOST . ':' . $port);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_MAIL_FROM => '<' . $from . '>',
    CURLOPT_MAIL_RCPT => ['<' . $to . '>'],
    CURLOPT_USERNAME => SMTP_USER,
    CURLOPT_PASSWORD => SMTP_PASS,
    CURLOPT_USE_SSL => $scheme === 'smtps' ? CURLUSESSL_SSL : CURLUSESSL_TRY,
    CURLOPT_UPLOAD => true,
    CURLOPT_INFILE => $fp,
    CURLOPT_INFILESIZE => strlen($message),
    CURLOPT_TIMEOUT => 15,
  ]);
  $ok = curl_exec($ch) !== false;
  if (!$ok) error_log('MEDRED SMTP: ' . curl_error($ch));
  curl_close($ch);
  fclose($fp);
  return $ok;
}

/* Notify user: DB record + email + SMS stub.
   Email uses SMTP when configured, otherwise PHP mail().
   SMS is a stub writing to logs/sms.log — add a gateway (Twilio/etc.) with real
   credentials when you want live SMS. */
function notify(PDO $pdo, int $userId, string $type, string $text): void {
  /* one round trip: insert the notification and fetch the recipient */
  $st = $pdo->prepare('WITH ins AS (
                         INSERT INTO notifications (user_id, type, text) VALUES (?, ?, ?) RETURNING user_id
                       )
                       SELECT u.name, u.email, u.phone FROM users u JOIN ins i ON i.user_id = u.id');
  $st->execute([$userId, $type, $text]);
  $user = $st->fetch();
  if (!$user) return;

  if (MEDRED_EMAIL) {
    $to = $user['email'];
    $subject = 'MEDRED - ' . ucfirst($type);
    $body = "Hello {$user['name']},\n\n$text\n\n-- MEDRED Team";
    $headers = "From: " . SMTP_FROM . "\r\n";
    $sent = SMTP_HOST !== ''
      ? smtp_send($to, $subject, $body)
      : @mail($to, $subject, $body, $headers);
    if (!$sent) error_log("MEDRED: could not send email to $to");
  }

  if ($user['phone']) {
    $smsLog = __DIR__ . '/../logs/sms.log';
    $dir = dirname($smsLog);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    file_put_contents($smsLog, "[" . date('Y-m-d H:i:s') . "] SMS to {$user['phone']}: $text\n", FILE_APPEND);
  }
}

function current_user(): ?array {
  static $loaded = false, $user = null;
  if (!$loaded) {
    ms_session_start();
    if (!isset($_SESSION['user'])) {
      $remembered = remember_user();
      if ($remembered) $_SESSION['user'] = $remembered;
    }
    $user = $_SESSION['user'] ?? null;
    /* ponytail: release the session file lock - API endpoints only read the
       session, and holding the lock serialized every parallel request. */
    session_write_close();
    $loaded = true;
  }
  return $user;
}

function require_role(string $role): array {
  $u = current_user();
  if (!$u) respond(['ok' => false, 'error' => 'Not logged in.'], 401);
  if ($u['role'] !== $role) respond(['ok' => false, 'error' => 'Forbidden.'], 403);
  return $u;
}

/* writes invalidate every cached GET response */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') cache_clear();
````

### `config/storage.php`

> Supabase Storage integration, bucket management and upload validation.  
> Lines: 207 &middot; Size: 8.3 KB

````php
<?php
/* Supabase Storage integration (server-side only, service role key).
   Buckets: medicine-images (public), distributor-documents (private),
   ngo-documents (private), invoices (private), qr-codes (private).

   When SUPABASE_SERVICE_ROLE_KEY is not set (local XAMPP), uploads fall back
   to the existing local folders so development keeps working unchanged. */

const STORAGE_BUCKET_MEDICINES = 'medicine-images';
const STORAGE_BUCKET_DISTRIBUTOR_DOCS = 'distributor-documents';
const STORAGE_BUCKET_NGO_DOCS = 'ngo-documents';
const STORAGE_BUCKET_INVOICES = 'invoices';
const STORAGE_BUCKET_QR = 'qr-codes';

function storage_enabled(): bool {
  return SUPABASE_URL !== '' && SUPABASE_SERVICE_ROLE_KEY !== '';
}

function storage_encode_path(string $path): string {
  return implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/'))));
}

function storage_request(string $method, string $path, ?string $body = null, string $contentType = 'application/json', array $extraHeaders = []): array {
  if (!storage_enabled()) return [0, [], 'Storage is not configured.'];
  $headers = [
    'apikey: ' . SUPABASE_SERVICE_ROLE_KEY,
    'Authorization: Bearer ' . SUPABASE_SERVICE_ROLE_KEY,
    'Content-Type: ' . $contentType,
  ];
  foreach ($extraHeaders as $h) $headers[] = $h;
  $ch = curl_init(rtrim(SUPABASE_URL, '/') . '/storage/v1' . $path);
  $opts = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 30,
  ];
  if ($body !== null) $opts[CURLOPT_POSTFIELDS] = $body;
  curl_setopt_array($ch, $opts);
  $raw = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err = $raw === false ? curl_error($ch) : '';
  curl_close($ch);
  return [$code, json_decode((string)$raw, true) ?: [], $err];
}

/* Create a bucket if it does not exist yet. Used by the migration script and
   as a one-time retry when an upload hits a missing bucket. */
function storage_ensure_bucket(string $bucket, bool $public): array {
  [$code] = storage_request('GET', '/bucket/' . rawurlencode($bucket));
  if ($code === 200) return [true, null];
  [$code, $json, $err] = storage_request('POST', '/bucket', json_encode([
    'id' => $bucket,
    'name' => $bucket,
    'public' => $public,
    'file_size_limit' => 5 * 1024 * 1024,
    'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'application/pdf'],
  ]));
  if ($code >= 200 && $code < 300) return [true, null];
  return [false, $json['message'] ?? $json['error'] ?? ($err ?: "HTTP $code")];
}

function storage_upload_object(string $bucket, string $object, string $tmp, string $mime): array {
  $bytes = @file_get_contents($tmp);
  if ($bytes === false) return [false, 'Could not read the uploaded file.'];
  [$code, $json, $err] = storage_request(
    'POST',
    '/object/' . rawurlencode($bucket) . '/' . storage_encode_path($object),
    $bytes,
    $mime,
    ['x-upsert: true']
  );
  if ($code >= 200 && $code < 300) return [true, null];
  return [false, $json['message'] ?? $json['error'] ?? ($err ?: "HTTP $code")];
}

function storage_delete_object(string $bucket, string $object): bool {
  [$code] = storage_request('DELETE', '/object/' . rawurlencode($bucket) . '/' . storage_encode_path($object));
  return $code >= 200 && $code < 300;
}

function storage_public_url(string $bucket, string $object): string {
  return rtrim(SUPABASE_URL, '/') . '/storage/v1/object/public/' . rawurlencode($bucket) . '/' . storage_encode_path($object);
}

function storage_signed_url(string $bucket, string $object, int $expires = 3600): ?string {
  [$code, $json, $err] = storage_request(
    'POST',
    '/object/sign/' . rawurlencode($bucket) . '/' . storage_encode_path($object),
    json_encode(['expiresIn' => $expires])
  );
  if ($code >= 200 && $code < 300 && !empty($json['signedURL'])) {
    $signed = $json['signedURL'];
    if (str_starts_with($signed, 'http')) return $signed;
    return rtrim(SUPABASE_URL, '/') . '/storage/v1' . $signed;
  }
  if ($err) error_log('MEDRED storage sign: ' . $err);
  return null;
}

/* Map the path stored in PostgreSQL (uploads/medicines/x.jpg,
   uploads/licenses/x.jpg) to a Storage object path. */
function storage_object_path(string $rel): string {
  $rel = ltrim($rel, '/');
  if (str_starts_with($rel, 'uploads/medicines/')) {
    return 'medicines/' . substr($rel, strlen('uploads/medicines/'));
  }
  if (str_starts_with($rel, 'uploads/licenses/')) {
    return 'licenses/' . substr($rel, strlen('uploads/licenses/'));
  }
  return $rel;
}

function storage_local_put(string $rel, string $tmp): bool {
  $dest = dirname(__DIR__) . '/' . ltrim($rel, '/');
  if (!is_dir(dirname($dest))) @mkdir(dirname($dest), 0755, true);
  /* copy() keeps CLI tests working; HTTP uploads use move_uploaded_file(). */
  return is_uploaded_file($tmp) ? move_uploaded_file($tmp, $dest) : @copy($tmp, $dest);
}

function storage_store_file(string $bucket, string $object, string $rel, string $tmp, string $mime, bool $public): array {
  if (!storage_enabled()) {
    return storage_local_put($rel, $tmp)
      ? ['path' => $rel, 'error' => null]
      : ['path' => null, 'error' => 'File save failed.'];
  }
  [$ok, $error] = storage_upload_object($bucket, $object, $tmp, $mime);
  if (!$ok) {
    /* First deployment: create the bucket and retry once. */
    [$created] = storage_ensure_bucket($bucket, $public);
    if ($created) [$ok, $error] = storage_upload_object($bucket, $object, $tmp, $mime);
  }
  if (!$ok) error_log("MEDRED storage upload ($bucket/$object): $error");
  return $ok
    ? ['path' => $rel, 'error' => null]
    : ['path' => null, 'error' => 'File upload failed. Try again.'];
}

function storage_store_medicine(string $tmp, string $ext, string $mime): array {
  $name = bin2hex(random_bytes(8)) . '.' . $ext;
  return storage_store_file(
    STORAGE_BUCKET_MEDICINES,
    'medicines/' . $name,
    'uploads/medicines/' . $name,
    $tmp,
    $mime,
    true
  );
}

function storage_store_document(string $tmp, string $ext, string $mime, string $role): array {
  $name = bin2hex(random_bytes(8)) . '.' . $ext;
  $bucket = $role === 'org' ? STORAGE_BUCKET_NGO_DOCS : STORAGE_BUCKET_DISTRIBUTOR_DOCS;
  return storage_store_file($bucket, 'licenses/' . $name, 'uploads/licenses/' . $name, $tmp, $mime, false);
}

function storage_delete_upload(?string $rel, ?string $role = null): void {
  if (!$rel || preg_match('#^https?://#', $rel)) return;
  if (storage_enabled()) {
    $bucket = null;
    if (str_starts_with($rel, 'uploads/medicines/')) $bucket = STORAGE_BUCKET_MEDICINES;
    elseif (str_starts_with($rel, 'uploads/licenses/')) {
      $bucket = $role === 'org' ? STORAGE_BUCKET_NGO_DOCS : STORAGE_BUCKET_DISTRIBUTOR_DOCS;
    }
    if ($bucket) {
      storage_delete_object($bucket, storage_object_path($rel));
      return;
    }
  }
  $local = dirname(__DIR__) . '/' . ltrim($rel, '/');
  if (is_file($local)) @unlink($local);
}

function storage_medicine_url(?string $rel): ?string {
  if (!$rel) return null;
  if (preg_match('#^https?://#', $rel)) return $rel;
  if (!storage_enabled()) return $rel;
  return storage_public_url(STORAGE_BUCKET_MEDICINES, storage_object_path($rel));
}

function storage_document_url(?string $rel, string $role): ?string {
  if (!$rel) return null;
  if (preg_match('#^https?://#', $rel)) return $rel;
  if (!storage_enabled()) return $rel;
  $bucket = $role === 'org' ? STORAGE_BUCKET_NGO_DOCS : STORAGE_BUCKET_DISTRIBUTOR_DOCS;
  return storage_signed_url($bucket, storage_object_path($rel)) ?: '';
}

/* Validate extension, size and real MIME type. Returns [ext, mime, error]. */
function upload_validate(array $f, array $exts, int $maxBytes): array {
  if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    return [null, null, 'Image upload failed.'];
  }
  if (($f['size'] ?? 0) > $maxBytes) {
    return [null, null, 'Image must be under ' . round($maxBytes / 1048576) . 'MB.'];
  }
  $ext = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
  if (!in_array($ext, $exts, true)) {
    return [null, null, 'Only JPG/PNG/WEBP allowed.'];
  }
  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: '';
  $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
  if (($allowed[$ext] ?? '') !== $mime) {
    return [null, null, 'File content does not match its extension.'];
  }
  return [$ext, $mime, null];
}
````

### `config/secrets.example.php`

> Template for the gitignored local secrets file.  
> Lines: 21 &middot; Size: 0.9 KB

````php
<?php
/* Copy this file to config/secrets.php and fill in your own credentials.
   config/secrets.php is gitignored - never commit real keys.
   Environment variables (Render) take precedence over these values. */
return [
  'db_dsn' => 'pgsql:host=YOUR_POOLER_HOST;port=5432;dbname=postgres;sslmode=require',
  'db_user' => 'postgres.YOUR_PROJECT_REF',
  'db_pass' => 'YOUR_DB_PASSWORD',
  'razorpay_key_id' => 'rzp_test_XXXXXXXX',
  'razorpay_key_secret' => 'YOUR_RAZORPAY_KEY_SECRET',
  'supabase_url' => 'https://YOUR_PROJECT_REF.supabase.co',
  'supabase_anon_key' => 'YOUR_SUPABASE_ANON_KEY',
  'supabase_service_role_key' => 'YOUR_SUPABASE_SERVICE_ROLE_KEY',
  'reset_secret' => 'CHANGE_TO_A_LONG_RANDOM_STRING',
  'smtp_host' => '',
  'smtp_port' => '587',
  'smtp_secure' => 'tls',
  'smtp_user' => '',
  'smtp_pass' => '',
  'smtp_from' => 'noreply@example.com',
];
````

### `.env.example`

> Environment variable template for Docker and Render.  
> Lines: 33 &middot; Size: 1.1 KB

````ini
# Copy to .env for local Docker runs, or paste these into Render's
# Environment tab. Never commit .env or config/secrets.php.

# production on Render; development enables the OTP file fallback locally.
APP_ENV=production

# Supabase PostgreSQL (session pooler). Use either DB_DSN or DATABASE_URL.
DB_DSN=pgsql:host=YOUR_POOLER_HOST;port=5432;dbname=postgres;sslmode=require
DB_USER=postgres.YOUR_PROJECT_REF
DB_PASS=YOUR_DATABASE_PASSWORD
# DATABASE_URL=postgresql://postgres.YOUR_PROJECT_REF:YOUR_PASSWORD@YOUR_POOLER_HOST:5432/postgres?sslmode=require

# Supabase Storage + Auth
SUPABASE_URL=https://YOUR_PROJECT_REF.supabase.co
SUPABASE_ANON_KEY=YOUR_ANON_KEY
SUPABASE_SERVICE_ROLE_KEY=YOUR_SERVICE_ROLE_KEY

# Razorpay
RAZORPAY_KEY_ID=rzp_test_xxxxxxxxxxxx
RAZORPAY_KEY_SECRET=YOUR_RAZORPAY_SECRET

# Password-reset token signing (long random string)
RESET_SECRET=CHANGE_ME_TO_A_LONG_RANDOM_STRING

# Password-reset OTP delivery (required in production).
# Gmail: smtp.gmail.com:587, tls, and an App Password (not the account password).
MEDRED_EMAIL=false
SMTP_HOST=
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=
SMTP_PASS=
SMTP_FROM=noreply@example.com
````

## Client-Side JavaScript

### `js/cart.js`

> Shared cart storage, API helpers, Razorpay Checkout, nav/footer and geolocation.  
> Lines: 265 &middot; Size: 11.6 KB

````javascript
/* MEDRED storefront shared: cart storage + navbar + footer. */
const CART_KEY = 'medshare_cart';
const getCart = () => JSON.parse(localStorage.getItem(CART_KEY) || '[]').map(i => ({ id: +i.id, qty: +i.qty }));
const setCart = c => { localStorage.setItem(CART_KEY, JSON.stringify(c)); window.dispatchEvent(new Event('cart')); };
const cartCount = () => getCart().reduce((s, i) => s + i.qty, 0);
const addToCart = (id, qty = 1) => {
  const c = getCart();
  const hit = c.find(i => i.id === id);
  hit ? hit.qty += qty : c.push({ id, qty });
  setCart(c);
};
const setQty = (id, qty) => {
  const c = getCart();
  const hit = c.find(i => i.id === id);
  if (!hit) return;
  hit.qty = Math.max(0, qty);
  setCart(c);
};
const removeFromCart = id => setCart(getCart().filter(i => i.id !== id));

const apiGet = async url => (await fetch(url)).json();
const apiPost = async (url, body, multipart = false) => (await fetch(url, multipart ? { method: 'POST', body } : {
  method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body)
})).json();

/* ---------- delivery address cookie (remembered across visits) ---------- */
const DELIVERY_COOKIE = 'medshare_delivery';
const DELIVERY_PHONE_COOKIE = 'medshare_delivery_phone';
const cookieGet = name => {
  const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.*+?^${}()|[\]\\])/g, '\\$1') + '=([^;]*)'));
  return m ? decodeURIComponent(m[1]) : '';
};
const cookieSet = (name, value, days = 365) => {
  document.cookie = `${name}=${encodeURIComponent(value)}; path=/; max-age=${days * 86400}; SameSite=Lax`;
};
const savedAddress = fallback => cookieGet(DELIVERY_COOKIE) || fallback || '';
const savedPhone = fallback => cookieGet(DELIVERY_PHONE_COOKIE) || fallback || '';
const digitsOnly = value => String(value).replace(/\D/g, '').slice(0, 10);

/* ---------- small shared helpers ---------- */
const loadScript = src => new Promise((res, rej) => {
  const s = document.createElement('script');
  s.src = src;
  s.onload = res;
  s.onerror = () => rej(new Error('Could not load ' + src));
  document.head.appendChild(s);
});
const cachedGet = async (url, ttl = 60000) => {
  const key = 'medshare_cache:' + url;
  try { const c = JSON.parse(sessionStorage.getItem(key) || 'null'); if (c && Date.now() - c.at < ttl) return c.data; } catch {}
  const data = await apiGet(url);
  try { sessionStorage.setItem(key, JSON.stringify({ at: Date.now(), data })); } catch {}
  return data;
};

/* ---------- Razorpay checkout (Amazon-style: order first, then pay) ---------- */
async function payWithRazorpay(orderIds, user, upi = false) {
  const create = await apiPost('api/payment.php', { action: 'create', order_ids: orderIds });
  if (!create.ok) { toast(create.error, 'danger'); return false; }
  try { await loadScript('https://checkout.razorpay.com/v1/checkout.js'); }
  catch { toast('Payment gateway could not load. Check your connection.', 'danger'); return false; }
  return new Promise(resolve => {
    let finished = false;
    let rz;
    try {
      rz = new Razorpay({
        key: create.key_id,
        order_id: create.razorpay_order_id,
        amount: create.amount,
        currency: 'INR',
        name: 'MEDRED',
        description: 'Order' + (orderIds.length > 1 ? 's' : '') + ' #' + orderIds.join(', #'),
        prefill: { name: user?.name || '', email: user?.email || '', ...(upi ? { method: 'upi' } : {}) },
        theme: { color: '#0B5C4E' },
        handler: async resp => {
          finished = true;
          const v = await apiPost('api/payment.php', { action: 'verify', ...resp });
          if (v.ok) { toast('Payment successful'); resolve(true); }
          else { toast(v.error, 'danger'); resolve(false); }
        },
        modal: { ondismiss: () => { if (!finished) resolve(false); } }
      });
    } catch {
      toast('Payment gateway could not start.', 'danger');
      return resolve(false);
    }
    rz.open();
  });
}

const stars = r => '★'.repeat(Math.round(r)) + '☆'.repeat(5 - Math.round(r));
const money = n => '\u20B9' + (Number(n) % 1 ? Number(n).toFixed(2) : Number(n));

function toast(msg, type = 'success') {
  let box = document.getElementById('toastStack');
  if (!box) {
    box = document.createElement('div');
    box.id = 'toastStack';
    box.className = 'toast-stack';
    document.body.appendChild(box);
  }
  const el = document.createElement('div');
  el.className = `toast align-items-center border-0 text-bg-${type}`;
  el.setAttribute('role', 'status');
  el.setAttribute('aria-live', 'polite');
  el.innerHTML = '<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
  el.querySelector('.toast-body').textContent = msg;
  box.appendChild(el);
  el.addEventListener('hidden.bs.toast', () => el.remove());
  bootstrap.Toast.getOrCreateInstance(el, { delay: 2600 }).show();
}

let SESSION;
async function loadSession() {
  if (SESSION !== undefined) return SESSION;
  const s = await apiGet('auth/session.php');
  SESSION = s.ok ? s.user : null;
  /* buyers: ask for location as soon as they are signed in */
  if (SESSION && SESSION.role === 'org' && !geoFresh()) geoRequest();
  return SESSION;
}

/* ---------- delivery location (browser geolocation) ---------- */
const GEO_KEY = 'medshare_geo';
const geoStored = () => { try { return JSON.parse(localStorage.getItem(GEO_KEY) || 'null'); } catch { return null; } };
const geoFresh = () => {
  const g = geoStored();
  return g && Date.now() - (+g.at || 0) < 30 * 60 * 1000 ? g : null;
};
const geoRequest = () => new Promise(resolve => {
  if (!navigator.geolocation) return resolve(null);
  navigator.geolocation.getCurrentPosition(
    pos => {
      const g = { lat: +pos.coords.latitude.toFixed(6), lng: +pos.coords.longitude.toFixed(6), at: Date.now() };
      localStorage.setItem(GEO_KEY, JSON.stringify(g));
      window.dispatchEvent(new Event('geo'));
      resolve(g);
    },
    () => resolve(null),
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
  );
});
const geoEnsure = async () => geoFresh() || await geoRequest();
/* never make an order wait 10s for a location that isn't coming */
const geoQuick = async (wait = 2000) => geoFresh() || await Promise.race([
  geoRequest(),
  new Promise(r => setTimeout(() => r(null), wait))
]);

let miniId = null, miniName = '';
function refreshMiniCart() {
  const el = document.getElementById('miniCart');
  if (!el) return;
  const item = getCart().find(i => i.id === miniId);
  if (!item) { el.classList.add('d-none'); return; }
  el.querySelector('#miniName').textContent = miniName;
  el.querySelector('#miniQty').textContent = item.qty;
  el.classList.remove('d-none');
}
function showMiniCart(id, name) {
  miniId = +id;
  miniName = name || '';
  refreshMiniCart();
}
function initMiniCart(user) {
  if (!user || user.role !== 'org' || document.getElementById('miniCart')) return;
  document.body.insertAdjacentHTML('beforeend', `
  <div class="mini-cart d-none" id="miniCart">
    <div class="mini-cart-inner">
      <div class="mini-cart-name" id="miniName"></div>
      <div class="mini-stepper">
        <button class="mini-arrow" id="miniMinus" aria-label="Decrease quantity"><i class="bi bi-dash-lg"></i></button>
        <span class="mini-qty" id="miniQty">1</span>
        <button class="mini-arrow" id="miniPlus" aria-label="Increase quantity"><i class="bi bi-plus-lg"></i></button>
      </div>
      <a class="btn btn-primary mini-go" href="cart.html">Go to cart</a>
      <button class="mini-close" id="miniClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
  </div>`);
  const el = document.getElementById('miniCart');
  el.querySelector('#miniPlus').onclick = () => { if (miniId) addToCart(miniId, 1); };
  el.querySelector('#miniMinus').onclick = () => {
    const item = getCart().find(i => i.id === miniId);
    if (item) setQty(miniId, item.qty - 1);
    refreshMiniCart();
  };
  el.querySelector('#miniClose').onclick = () => el.classList.add('d-none');
  window.addEventListener('cart', refreshMiniCart);
}

async function renderNav() {
  const nav = document.getElementById('nav');
  if (!nav) return;
  if (!document.querySelector('link[rel="icon"]')) {
    const icon = document.createElement('link');
    icon.rel = 'icon'; icon.type = 'image/png'; icon.href = 'img/logo.png';
    document.head.appendChild(icon);
  }
  const user = await loadSession();
  document.body.classList.toggle('is-distributor', user?.role === 'distributor');
  initMiniCart(user);
  nav.innerHTML = `
  <nav class="site-nav">
    <div class="container">
      <a class="brand" href="index.html"><img src="img/logo.png" alt="" class="brand-logo"> MEDRED</a>
      <ul class="nav-links d-none d-lg-flex">
        <li><a href="store.html">Medicines</a></li>
        <li><a href="index.html#how">How it works</a></li>
        <li><a href="register.html?role=distributor">For distributors</a></li>
        <li><a href="index.html#about">About</a></li>
      </ul>
      <div class="nav-actions">
        <a class="icon-link" href="store.html" title="Browse medicines" aria-label="Browse medicines"><i class="bi bi-box-seam"></i></a>
        ${user ? `
          <a class="icon-link" href="dashboard.html" title="Dashboard" aria-label="Dashboard"><i class="bi bi-speedometer2"></i><span class="nav-dot" id="navBadge"></span></a>
          <a class="icon-link" href="profile.html" title="My profile"><i class="bi bi-person"></i> <span>Profile</span></a>`
        : `<a class="nav-signin" href="login.html">Sign in</a>
           <a class="nav-register" href="register.html">Register</a>`}
        ${user && user.role === 'org' ? `<a class="btn btn-primary cart-pill" href="cart.html">Cart · <span id="cartBadge">${cartCount()}</span></a>` : ''}
      </div>
    </div>
  </nav>`;
  window.addEventListener('cart', () => { const b = document.getElementById('cartBadge'); if (b) b.textContent = cartCount(); });
  if (user) {
    const paint = async () => {
      try {
        const d = await cachedGet('api/notifications.php', 10000);
        const n = d.notifications.filter(x => !+x.is_read).length;
        const b = document.getElementById('navBadge');
        if (b) { b.textContent = n; b.style.display = n > 0 ? 'inline-block' : 'none'; }
      } catch {}
    };
    paint();
    setInterval(paint, 60000);
  }
}

async function renderFooter() {
  const el = document.getElementById('footer');
  if (!el) return;
  el.innerHTML = `
  <footer class="site-footer">
    <div class="container">
      <div class="row g-4">
        <div class="col-md-4">
          <div class="footer-brand">MEDRED</div>
          <p class="footer-desc mb-0">Don't waste medicines, redistribute hope. Near-expiry, safe medicines for NGOs, orphanages and old-age homes.</p>
        </div>
        <div class="col-md-4">
          <ul class="footer-links">
            <li><a href="store.html">Medicines</a></li>
            <li><a href="index.html#how">How it works</a></li>
            <li><a href="register.html?role=distributor">For distributors</a></li>
            <li><a href="index.html#about">About</a></li>
          </ul>
        </div>
        <div class="col-md-4">
          <p class="footer-note mb-0">Only verified, <a href="register.html?role=distributor">licensed distributors</a> and approved organizations may trade on this platform.</p>
        </div>
      </div>
    </div>
  </footer>`;
}

document.addEventListener('DOMContentLoaded', () => { renderNav(); renderFooter(); });
````

### `js/store.js`

> Storefront rendering, medicine details, distributor profile, cart and checkout logic.  
> Lines: 454 &middot; Size: 23.6 KB

````javascript
/* MEDRED storefront pages: index / store / medicine / distributor / cart. */
(function () {
  const page = location.pathname.split('/').pop();
  const isHome = page === '' || page === 'index.html';
  const $ = s => document.querySelector(s);

  const shelfInfo = m => {
    const days = Math.max(0, Math.ceil((new Date(m.expiry + 'T23:59:59') - Date.now()) / 86400000));
    const mo = Math.floor(days / 30.44);
    const d = Math.round(days - mo * 30.44);
    return { txt: mo > 0 ? `${mo} mo ${d} d left` : `${days} d left`, cls: mo < 3 ? 'orange' : 'green' };
  };
  const shelfPill = m => { const s = shelfInfo(m); return `<span class="shelf-pill ${s.cls}">${s.txt}</span>`; };

  const offHTML = m => (+m.mrp > +m.price)
    ? `<div class="med-off"><s>${money(m.mrp)}</s> <span>${Math.round((1 - m.price / m.mrp) * 100)}% off</span></div>`
    : '';

  const cardHTML = m => `
    <div class="col-md-6">
      <div class="med-card h-100">
        <div class="med-top">
          <span class="med-cat">${m.category}</span>
          ${shelfPill(m)}
        </div>
        <a class="med-name" href="medicine.html?id=${m.id}">${m.name}</a>
        <div class="med-meta">by <a href="distributor.html?id=${m.distributor_user_id}" class="text-decoration-none">${m.distributor}</a></div>
        <div class="med-meta">Batch ${m.batch_no || '-'}</div>
        <div class="med-meta ${m.stock === 0 ? 'text-danger' : ''}">${m.stock === 0 ? 'Out of stock' : m.stock + ' units in stock'}</div>
        <div class="med-foot">
          <div>
            <div class="med-price">${money(m.price)}</div>
            ${offHTML(m)}
          </div>
          <button class="btn btn-primary btn-sm add-btn" data-id="${m.id}" data-name="${m.name}" ${m.stock === 0 ? 'disabled' : ''}>Add</button>
        </div>
      </div>
    </div>`;

  const bindAddButtons = box => box.addEventListener('click', async e => {
    const b = e.target.closest('.add-btn');
    if (!b) return;
    if (!await loadSession()) { toast('Sign in to add medicines', 'warning'); return; }
    addToCart(+b.dataset.id, 1);
    showMiniCart(+b.dataset.id, b.dataset.name);
    toast(`${b.dataset.name || 'Medicine'} added to cart`);
  });

  /* ---------- home page: featured medicines ---------- */
  if (isHome && $('#featured')) {
    (async () => {
      const d = await cachedGet('api/medicines.php');
      const top = d.medicines.slice().sort((a, b) => a.shelf_months - b.shelf_months).slice(0, 4);
      if (!top.length) { $('#featuredSection')?.classList.add('d-none'); return; }
      $('#featured').innerHTML = top.map(cardHTML).join('');
      bindAddButtons($('#featured'));
    })();
  }

  /* ---------- store catalog ---------- */
  if (page === 'store.html') {
    const qs = new URLSearchParams(location.search);
    let activeCat = qs.get('cat') || '';
    $('#fSearch').value = qs.get('q') || '';

    async function loadChips() {
      const d = await cachedGet('api/medicines.php');
      const cats = [...new Set(d.medicines.map(m => m.category))];
      $('#chips').innerHTML = ['', ...cats].map(c =>
        `<button class="chip${c === activeCat ? ' active' : ''}" data-cat="${c}">${c || 'All'}</button>`).join('');
    }

    async function render() {
      const p = new URLSearchParams({ q: $('#fSearch').value.trim(), cat: activeCat });
      const d = await cachedGet('api/medicines.php?' + p);
      const meds = d.medicines.slice();
      const sort = $('#fSort').value;
      if (sort === 'price_asc') meds.sort((a, b) => a.price - b.price);
      else if (sort === 'price_desc') meds.sort((a, b) => b.price - a.price);
      else meds.sort((a, b) => new Date(a.expiry) - new Date(b.expiry));
      $('#cards').innerHTML = meds.length
        ? meds.map(cardHTML).join('')
        : '<div class="col-12 text-secondary text-center py-5">No medicines match your filters.</div>';
    }

    $('#chips').addEventListener('click', e => {
      const c = e.target.closest('.chip');
      if (!c) return;
      activeCat = c.dataset.cat;
      $('#chips').querySelectorAll('.chip').forEach(x => x.classList.toggle('active', x === c));
      render();
    });
    $('#fSearch').addEventListener('input', render);
    $('#fSort').addEventListener('change', render);
    $('#fDist').addEventListener('change', e => { if (e.target.value) location.href = 'distributor.html?id=' + e.target.value; });
    bindAddButtons($('#cards'));
    apiGet('api/distributors.php?list=1').then(d =>
      $('#fDist').insertAdjacentHTML('beforeend', d.distributors.map(x => `<option value="${x.id}">${x.name}</option>`).join('')));
    loadChips().then(render);
  }

  /* ---------- medicine detail ---------- */
  if (page === 'medicine.html') {
    (async () => {
      const m = (await apiGet('api/medicines.php?id=' + new URLSearchParams(location.search).get('id'))).medicine;
      if (!m) { $('#page').innerHTML = '<p class="text-secondary">Medicine not found.</p>'; return; }
      const rev = await apiGet('api/reviews.php?distributor_id=' + m.distributor_id);
      const img = m.image
        ? `<img src="${m.image}" class="detail-img" alt="${m.name}">`
        : `<div class="detail-img-ph"><i class="bi bi-capsule"></i></div>`;
      $('#page').innerHTML = `
      <nav class="crumb"><a href="store.html"><i class="bi bi-arrow-left"></i> Back to store</a></nav>
      <div class="row g-4 mb-2">
        <div class="col-lg-4">${img}</div>
        <div class="col-lg-8">
          <div class="detail-card">
            <div class="med-top">
              <span class="med-cat">${m.category}</span>
              ${shelfPill(m)}
            </div>
            <h2 class="detail-name">${m.name}</h2>
            <div class="detail-price">${money(m.price)} <span class="text-secondary fs-6 fw-normal">/ unit</span></div>
            ${offHTML(m)}
            <dl class="detail-rows">
              <div><dt>Batch No.</dt><dd>${m.batch_no || '-'}</dd></div>
              <div><dt>Expiry</dt><dd class="text-danger">${m.expiry}</dd></div>
              <div><dt>Shelf life</dt><dd>${m.shelf_months} months</dd></div>
              <div><dt>Stock</dt><dd>${m.stock} units</dd></div>
              <div><dt>Listed</dt><dd>${m.created_at.slice(0, 10)}</dd></div>
            </dl>
            <div class="d-flex flex-wrap gap-2">
              <button class="btn btn-primary add-btn" data-id="${m.id}" data-name="${m.name}" ${m.stock === 0 ? 'disabled' : ''}><i class="bi bi-cart-plus"></i> Add to cart</button>
              <a class="btn btn-outline-primary" href="distributor.html?id=${m.distributor_user_id}">View distributor</a>
            </div>
          </div>
        </div>
      </div>
      <div class="row g-4">
        <div class="col-lg-4">
          <div class="detail-card">
            <h6 class="mb-3"><i class="bi bi-truck"></i> Distributor</h6>
            <a href="distributor.html?id=${m.distributor_user_id}" class="fw-bold text-decoration-none fs-5">${m.distributor}</a>
            <div class="stars mt-1">${stars(rev.avg.avg)} <span class="text-secondary small">(${rev.avg.count})</span></div>
            <div class="text-secondary small mt-3">${m.address || 'Address not provided'}<br>${m.phone || 'Phone not provided'}</div>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="detail-card">
            <h6 class="mb-2">Reviews</h6>
            ${rev.reviews.length ? rev.reviews.map(r => `
              <div class="review-item"><div class="stars small">${stars(r.rating)}</div>
              <div class="small">"${r.comment || ''}"</div><div class="text-secondary" style="font-size:.75rem">${r.reviewer} &middot; ${r.created_at.slice(0, 10)}</div></div>`).join('')
            : '<p class="small text-secondary mb-0">No reviews yet.</p>'}
          </div>
        </div>
      </div>`;
      $('#page').addEventListener('click', async e => {
        const b = e.target.closest('.add-btn');
        if (!b) return;
        if (!await loadSession()) { toast('Sign in to add medicines', 'warning'); return; }
        addToCart(+b.dataset.id, 1);
        showMiniCart(+b.dataset.id, b.dataset.name);
        toast(`${b.dataset.name || 'Medicine'} added to cart`);
      });
    })();
  }

  /* ---------- distributor profile ---------- */
  if (page === 'distributor.html') {
    (async () => {
      const p = (await apiGet('api/distributors.php?id=' + new URLSearchParams(location.search).get('id'))).profile;
      if (!p) { $('#page').innerHTML = '<p class="text-secondary">Distributor not found.</p>'; return; }
      const rev = await apiGet('api/reviews.php?distributor_id=' + p.id);
      $('#page').innerHTML = `
      <nav class="crumb"><a href="store.html"><i class="bi bi-arrow-left"></i> Back to store</a></nav>
      <div class="detail-card mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
          <div>
            <h2 class="detail-name mb-1"><i class="bi bi-truck"></i> ${p.name}</h2>
            <div class="stars">${stars(rev.avg.avg)} <span class="text-secondary small">(${rev.avg.count} reviews)</span></div>
          </div>
          <span class="badge bg-success">Verified distributor</span>
        </div>
        <dl class="detail-rows mb-0" style="max-width:640px">
          <div><dt>License No.</dt><dd>${p.license_no || '-'}</dd></div>
          <div><dt>Phone</dt><dd>${p.phone || '-'}</dd></div>
          <div><dt>Email</dt><dd>${p.email}</dd></div>
          <div><dt>Address</dt><dd>${p.address || '-'}</dd></div>
          <div><dt>Member since</dt><dd>${p.created_at.slice(0, 10)}</dd></div>
        </dl>
      </div>
      <div class="section-head"><h2>Medicines by this distributor</h2></div>
      <div class="row g-3" id="distCards">${p.medicines.map(cardHTML).join('') || '<div class="col-12 text-secondary">No medicines listed.</div>'}</div>`;
      bindAddButtons($('#distCards'));
    })();
  }

  /* ---------- profile ---------- */
  if (page === 'profile.html') {
    const roleLabel = p => p.role === 'org' ? (p.org_type || 'Organization') : p.role === 'admin' ? 'Administrator' : 'Distributor';
    let prof = null;

    async function loadProfile() {
      const d = await apiGet('api/profile.php');
      if (!d.ok) { location.href = 'login.html'; return null; }
      const p = prof = d.profile;
      $('#profEmail').textContent = p.email;
      $('#profRole').textContent = roleLabel(p);
      const v = $('#profVerified');
      v.textContent = p.verified ? 'Verified' : 'Verification pending';
      v.className = 'pill ' + (p.verified ? 'pill-green' : 'pill-gray');
      $('#profOrders').textContent = p.orders_count;
      $('#profAccount').textContent = roleLabel(p);
      const rows = [
        ['Full name', p.name],
        ['Phone', p.phone || '\u2014'],
        ['City', p.city || '\u2014'],
        ['Location', p.latitude && p.longitude ? `${p.latitude}, ${p.longitude}` : '\u2014'],
        ['License / reg. no.', p.license_no || p.registration_no || '\u2014'],
        ['Address', p.address || '\u2014'],
        ['Member since', p.created_at.slice(0, 10)]
      ];
      $('#profDetails').innerHTML = rows.map(([k, val]) => `<div><dt>${k}</dt><dd>${val}</dd></div>`).join('');
      return p;
    }

      $('#epPhone')?.addEventListener('input', e => { e.target.value = digitsOnly(e.target.value); });
      $('#editProfileBtn').addEventListener('click', () => {
        if (!prof) return;
        $('#epName').value = prof.name;
        $('#epPhone').value = prof.phone || '';
        $('#epAddress').value = prof.address || '';
        $('#epLat').value = prof.latitude || '';
        $('#epLng').value = prof.longitude || '';
        bootstrap.Modal.getOrCreateInstance($('#editProfileModal')).show();
      });
    $('#editProfileForm').addEventListener('submit', async e => {
      e.preventDefault();
      const res = await apiPost('api/profile.php', { name: $('#epName').value, phone: $('#epPhone').value, address: $('#epAddress').value, latitude: $('#epLat').value || null, longitude: $('#epLng').value || null });
      if (res.ok) { bootstrap.Modal.getInstance($('#editProfileModal')).hide(); await loadProfile(); toast('Profile updated'); }
      else toast(res.error, 'danger');
    });

    loadProfile();
  }

  /* ---------- cart + checkout ---------- */
  if (page === 'cart.html') {
    (async () => {
      const user = await loadSession();

      const updateGeoUI = () => {
        const g = geoStored();
        const st = $('#geoStatus');
        if (!st) return;
        st.textContent = g ? `\u2713 ${g.lat}, ${g.lng}` : 'Not enabled';
        st.className = 'small ' + (g ? 'text-success' : 'text-warning');
      };
      window.addEventListener('geo', updateGeoUI);

      async function init() {
        let cart = getCart();
        if (!cart.length) {
          $('#cartEmpty')?.classList.remove('d-none');
          $('#cartFull')?.classList.add('d-none');
          $('#cartTotal').textContent = money(0);
          return;
        }

        /* fetch by id: the list endpoint is distributor-scoped for distributor logins */
        const results = await Promise.all(cart.map(i =>
          cachedGet('api/medicines.php?id=' + i.id).catch(() => null)));
        const meds = results.map(r => r && r.medicine).filter(Boolean);

        /* drop stale cart entries (deleted/expired medicines); keep items whose
           request failed so a network blip never wipes the cart */
        const available = cart.filter((i, idx) => results[idx] === null || results[idx].medicine);
        if (available.length !== cart.length) {
          cart = available;
          setCart(cart);
          toast('Some cart items are no longer available and were removed.', 'warning');
        }

        const empty = !cart.length;
        $('#cartEmpty')?.classList.toggle('d-none', !empty);
        $('#cartFull')?.classList.toggle('d-none', empty);
        if (empty) { $('#cartTotal').textContent = money(0); return; }

        const rows = cart.map(i => {
          const m = meds.find(x => x.id === i.id);
          if (!m) return `<tr><td colspan="6" class="text-danger small">Could not load this item. <a href="cart.html">Reload</a></td></tr>`;
          return `<tr>
            <td><a href="medicine.html?id=${m.id}" class="text-decoration-none fw-bold text-dark">${m.name}</a></td>
            <td class="small text-secondary">${m.distributor}</td>
            <td>${money(m.price)}</td>
            <td><input type="number" class="form-control form-control-sm qty" data-id="${m.id}" data-price="${m.price}" min="0" max="${m.stock}" value="${i.qty}"></td>
            <td class="fw-bold row-total">${money(m.price * i.qty)}</td>
            <td><button class="btn btn-sm btn-outline-danger rm-btn" data-id="${m.id}"><i class="bi bi-trash"></i></button></td>
          </tr>`;
        }).join('');
        $('#cartBody').innerHTML = rows || '<tr><td colspan="6" class="text-secondary text-center py-4">Cart is empty. <a href="store.html">Browse the store</a></td></tr>';

        const total = cart.reduce((s, i) => { const m = meds.find(x => x.id === i.id); return s + (m ? m.price * i.qty : 0); }, 0);
        $('#cartTotal').textContent = money(total);

        const MIN_ORDER = 500;
        const distTotals = {};
        cart.forEach(i => {
          const m = meds.find(x => x.id === i.id);
          if (m) distTotals[m.distributor] = (distTotals[m.distributor] || 0) + m.price * i.qty;
        });
        const short = Object.entries(distTotals).filter(([, t]) => t < MIN_ORDER);
        const minNote = short.length
          ? `<div class="alert alert-warning small">Minimum order is ${money(MIN_ORDER)} per distributor. ${short.map(([d, t]) => `<b>${d}</b>: ${money(t)} (add ${money(MIN_ORDER - t)})`).join(' &middot; ')}</div>`
          : `<div class="small text-secondary mb-2">Minimum order: ${money(MIN_ORDER)} per distributor.</div>`;

        let checkout = '';
        if (!cart.length) {
          checkout = '';
        } else if (!user) {
          checkout = `<div class="alert alert-warning small">You need to <a href="login.html">sign in</a> to place orders. Orders are restricted to <b>verified organizations</b>.</div>`;
        } else if (user.role !== 'org') {
          checkout = `<div class="alert alert-warning small">Orders are restricted to <b>verified organizations</b> (NGOs, orphanages, old-age homes, charitable hospitals). Contact admin if your account type is wrong.</div>`;
        } else {
          const geo = geoStored();
          checkout = `
            <label class="form-label small text-secondary">Delivery Address</label>
            <textarea class="form-control mb-2" id="delAddr" rows="2" placeholder="Delivery address (e.g. Hope Orphanage, Warangal, Telangana)" required></textarea>
            <label class="form-label small text-secondary">Mobile Number</label>
            <input type="tel" class="form-control mb-2" id="delPhone" inputmode="numeric" maxlength="10" placeholder="10-digit mobile number" required>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
              <span class="small text-secondary">Delivery location</span>
              <span class="small ${geo ? 'text-success' : 'text-warning'}" id="geoStatus">${geo ? `\u2713 ${geo.lat}, ${geo.lng}` : 'Not enabled'}</span>
            </div>
            <button type="button" class="btn btn-sm ${geo ? 'btn-outline-secondary' : 'btn-outline-primary'} mb-2" id="geoEnable"><i class="bi bi-geo-alt"></i> ${geo ? 'Update location' : 'Enable location'}</button>
            <div class="small text-secondary mb-3">Turn on location so the distributor can navigate to you.</div>
            <label class="form-label small text-secondary">Payment Method</label>
            <select class="form-select mb-3" id="payMethod">
              <option value="Razorpay:upi">UPI (Razorpay)</option>
              <option value="Razorpay">Card / Netbanking (Razorpay)</option>
              <option value="Cash">Cash on Delivery</option>
            </select>
            ${minNote}
            <button class="btn btn-primary w-100" id="placeOrder"><i class="bi bi-bag-check"></i> Place Order</button>
            <div id="orderMsg" class="mt-2"></div>`;
        }
        $('#checkoutBox').innerHTML = checkout;
        if ($('#delAddr')) $('#delAddr').value = savedAddress(user.address);
        if ($('#delPhone')) $('#delPhone').value = savedPhone(user.phone);
        $('#delPhone')?.addEventListener('input', e => { e.target.value = digitsOnly(e.target.value); });
        $('#geoEnable')?.addEventListener('click', async () => {
          const btn = $('#geoEnable');
          btn.disabled = true;
          const g = await geoRequest();
          btn.disabled = false;
          updateGeoUI();
          toast(g ? 'Location captured' : 'Location not available. Allow location access in your browser.', g ? 'success' : 'warning');
        });
        $('#placeOrder')?.addEventListener('click', async () => {
          const btn = $('#placeOrder');
          const items = getCart().filter(i => i.qty > 0).map(i => ({ medicine_id: i.id, qty: i.qty }));
          if (!items.length) {
            $('#orderMsg').innerHTML = '<div class="alert alert-warning small mb-0">Set a quantity above 0 for at least one medicine.</div>';
            toast('Set a quantity above 0 for at least one medicine.', 'warning');
            return;
          }
          const address = $('#delAddr').value.trim();
          if (!address) {
            $('#orderMsg').innerHTML = '<div class="alert alert-warning small mb-0">Please enter a delivery address.</div>';
            toast('Please enter a delivery address.', 'warning');
            return;
          }
          const phone = digitsOnly($('#delPhone').value);
          if (phone.length !== 10) {
            $('#orderMsg').innerHTML = '<div class="alert alert-warning small mb-0">Please enter a 10-digit mobile number.</div>';
            toast('Please enter a 10-digit mobile number.', 'warning');
            return;
          }
          const distTotals = {};
          getCart().filter(i => i.qty > 0).forEach(i => {
            const m = meds.find(x => x.id === i.id);
            if (m) distTotals[m.distributor] = (distTotals[m.distributor] || 0) + m.price * i.qty;
          });
          const short = Object.entries(distTotals).filter(([, t]) => t < MIN_ORDER);
          if (short.length) {
            const msg = `Minimum order is ${money(MIN_ORDER)} per distributor: ${short.map(([d, t]) => `${d} (${money(t)})`).join(', ')}`;
            $('#orderMsg').innerHTML = `<div class="alert alert-warning small mb-0">${msg}</div>`;
            toast(msg, 'warning');
            return;
          }
          btn.disabled = true;
          const geo = await geoQuick();
          const [method, payMode] = $('#payMethod').value.split(':');
          const res = await apiPost('api/orders.php', { items, method, delivery_address: address, delivery_phone: phone, latitude: geo?.lat ?? null, longitude: geo?.lng ?? null });
          if (!res.ok) {
            btn.disabled = false;
            $('#orderMsg').innerHTML = `<div class="alert alert-danger small mb-0">${res.error}</div>`;
            toast(res.error, 'danger');
            return;
          }

          cookieSet(DELIVERY_COOKIE, address);
          cookieSet(DELIVERY_PHONE_COOKIE, phone);
          setCart([]);
          const ids = res.order_ids;
          const idText = `Order${ids.length > 1 ? 's' : ''} #${ids.join(', #')}`;

          if (method === 'Razorpay') {
            $('#orderMsg').innerHTML = `<div class="alert alert-info small mb-0">${idText} placed. Complete the payment to confirm it.</div>`;
            const paid = await payWithRazorpay(ids, user, payMode === 'upi');
            if (!paid) {
              $('#orderMsg').innerHTML = `<div class="alert alert-warning small mb-0">${idText} placed, but payment is pending. You can pay from the dashboard Orders tab.</div>`;
              toast('Payment pending. Pay any time from Orders.', 'warning');
              return;
            }
          }

          toast(`${idText} placed`);
          if (!geo) toast('Location was not shared. Enable location so the distributor can navigate to you.', 'warning');
          $('#orderMsg').innerHTML = `<div class="alert alert-success small mb-0">${idText} placed! Redirecting to medicines...</div>`;
          setTimeout(() => location.href = 'store.html', 1500);
        });
      }

      $('#cartBody').addEventListener('input', e => {
        const inp = e.target;
        if (!inp.classList.contains('qty')) return;
        const qty = Math.max(0, Math.min(parseInt(inp.value, 10) || 0, +inp.max));
        setQty(+inp.dataset.id, qty);
        inp.value = qty;
        inp.closest('tr').querySelector('.row-total').textContent = money(+inp.dataset.price * qty);
        const total = [...$('#cartBody').querySelectorAll('.qty')]
          .reduce((s, i) => s + (+i.dataset.price * (+i.value || 0)), 0);
        $('#cartTotal').textContent = money(total);
      });
      $('#cartBody').addEventListener('change', e => {
        if (e.target.classList.contains('qty') && +e.target.value === 0) {
          removeFromCart(+e.target.dataset.id);
          init();
        }
      });
      $('#cartBody').addEventListener('click', e => {
        const b = e.target.closest('.rm-btn');
        if (b) {
          const name = b.closest('tr').children[0].textContent.trim();
          removeFromCart(+b.dataset.id);
          init();
          toast(`${name} removed from cart`);
        }
      });
      init();
    })();
  }
})();
````

### `js/app.js`

> Login/register handling and all role-aware dashboard rendering and actions.  
> Lines: 743 &middot; Size: 44.8 KB

````javascript
/* MEDRED frontend - talks to the PHP API (no mock data). */
(function () {
  const isDashboard = location.pathname.endsWith('dashboard.html');
  const $ = s => document.querySelector(s);
  const money = n => '\u20B9' + (Number(n) % 1 ? Number(n).toFixed(2) : Number(n));
  const get = async url => (await fetch(url)).json();
  const post = async (url, body) => (await fetch(url, {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body)
  })).json();

  /* short-lived GET cache: tab switches feel instant, data is re-fetched after 15s */
  const GET_CACHE = new Map();
  const getCached = async (url, ttl = 30000) => {
    const hit = GET_CACHE.get(url);
    if (hit && Date.now() - hit.at < ttl) return hit.data;
    const data = await get(url);
    GET_CACHE.set(url, { data, at: Date.now() });
    return data;
  };
  const cachePut = (url, data) => GET_CACHE.set(url, { data, at: Date.now() });
  const cacheDrop = prefix => { for (const k of GET_CACHE.keys()) if (k.startsWith(prefix)) GET_CACHE.delete(k); };

  /* ---------- login / register pages ---------- */
  if (!isDashboard) {
    const loginForm = $('#loginForm'), registerForm = $('#registerForm'), alertBox = $('#alertBox');
    const showAlert = (msg, type) => alertBox.innerHTML = `<div class="alert alert-${type} py-2 small mb-0">${msg}</div>`;

    if (loginForm) loginForm.addEventListener('submit', async e => {
      e.preventDefault();
      try {
        const data = await post('auth/login.php', { email: $('#email').value, password: $('#password').value });
        if (!data.ok) { showAlert(data.error, 'danger'); toast(data.error, 'danger'); return; }
        location.href = data.user.role === 'admin' ? 'dashboard.html' : 'store.html';
      } catch (err) {
        showAlert('Login failed. Please try again.', 'danger');
        toast('Login failed. Please try again.', 'danger');
        console.error(err);
      }
    });

    if (registerForm) {
      $('#phone')?.addEventListener('input', e => { e.target.value = digitsOnly(e.target.value); });
      if (new URLSearchParams(location.search).get('role') === 'distributor') $('#r-dist').checked = true;
      const syncRoleFields = () => {
        const org = document.querySelector('input[name="role"]:checked').value === 'org';
        $('#orgType').classList.toggle('d-none', !org);
        $('#fullname').placeholder = org ? 'Organization name' : 'Business name';
        $('#credNo').placeholder = org ? 'NGO / Trust registration no. *' : 'Drug license no. (Form 20B/21B) *';
        $('#credHint').textContent = org
          ? 'Darpan ID, society registration or trust deed number.'
          : 'Wholesale drug license issued by the state drug regulatory authority.';
      };
      syncRoleFields();
      document.querySelectorAll('input[name="role"]').forEach(r => r.addEventListener('change', syncRoleFields));
      registerForm.addEventListener('submit', async e => {
        e.preventDefault();
        if (!registerForm.checkValidity()) { registerForm.classList.add('was-validated'); return; }
        const fd = new FormData();
        fd.append('name', $('#fullname').value);
        fd.append('email', $('#email').value);
        fd.append('phone', $('#phone').value);
        fd.append('address', $('#address').value);
        fd.append('city', $('#city').value);
        fd.append('credential_no', $('#credNo').value);
        fd.append('password', $('#password').value);
        fd.append('role', document.querySelector('input[name="role"]:checked').value);
        fd.append('orgType', $('#orgType').value);
        const regLat = $('#regLat').value.trim();
        const regLng = $('#regLng').value.trim();
        if (regLat) fd.append('latitude', regLat);
        if (regLng) fd.append('longitude', regLng);
        const file = $('#licenseImage').files[0];
        if (file) fd.append('license_image', file);
        const res = await (await fetch('auth/register.php', { method: 'POST', body: fd })).json();
        res.ok ? (showAlert(res.message, 'success'), toast(res.message), registerForm.reset(), syncRoleFields()) : (showAlert(res.error, 'danger'), toast(res.error, 'danger'));
      });
    }
    return;
  }

  /* ---------- dashboard ---------- */
  const pageTitle = $('#pageTitle'), pageContent = $('#pageContent');
  let user = null;

  async function loadUser() {
    const sess = await get('auth/session.php');
    if (!sess.ok) { location.href = 'login.html'; return false; }
    user = sess.user;
    $('#userName').textContent = user.name;
    $('#userRole').textContent = user.role === 'org' ? (user.org_type || 'Organization') : cap(user.role);
    return true;
  }

  const rolePages = () => ({
    admin: ['overview', 'medicines', 'orders', 'notifications', 'reports', 'distributors', 'audit', 'verify', 'map'],
    distributor: ['medicines', 'orders', 'notifications', 'reports', 'history'],
    org: ['orders', 'reviews', 'notifications', 'history']
  })[user.role];

  const cap = s => s[0].toUpperCase() + s.slice(1);

  async function show(page) {
    pageTitle.textContent = page === 'history' ? 'Order History' : cap(page);
    document.querySelectorAll('[data-page]').forEach(el => el.classList.toggle('active', el.dataset.page === page));
    pageContent.innerHTML = '<div class="text-center text-secondary py-5"><div class="spinner-border text-primary"></div></div>';
    await LOAD[page]();
  }

  const LOAD = {
    async overview() {
      const [r, n] = await Promise.all([getCached('api/reports.php'), getCached('api/notifications.php')]);
      const s = r.stats;
      pageContent.innerHTML = `
      <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card stat-card ok"><div class="card-body"><div class="stat-lbl">Medicines Listed</div><div class="stat-val">${s.total}</div></div></div></div>
        <div class="col-md-3"><div class="card stat-card warn"><div class="card-body"><div class="stat-lbl">Low Stock</div><div class="stat-val">${s.low}</div></div></div></div>
        <div class="col-md-3"><div class="card stat-card danger"><div class="card-body"><div class="stat-lbl">Expiring &le;3 Months</div><div class="stat-val">${s.expiring}</div></div></div></div>
        <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="stat-lbl">Revenue (Completed)</div><div class="stat-val">${money(s.revenue)}</div></div></div></div>
      </div>
      <div class="card"><div class="card-header"><i class="bi bi-bell"></i> Recent Notifications</div>
        <ul class="list-group list-group-flush">${n.notifications.slice(0, 3).map(nn => `<li class="list-group-item small"><span class="badge ${badgeType(nn.type)} me-2">${nn.type}</span>${nn.text} <span class="text-secondary float-end">${nn.created_at.slice(0, 10)}</span></li>`).join('') || '<li class="list-group-item small text-secondary">No notifications</li>'}</ul>
      </div>`;
    },

    async medicines() {
      pageContent.innerHTML = `
      <div class="card"><div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-4"><input class="form-control" id="medSearch" placeholder="Search medicine name..."></div>
          <div class="col-md-3"><select class="form-select" id="medCat"><option value="">All Categories</option></select></div>
          <div class="col-md-3"><select class="form-select" id="medShelf"><option value="">Any Shelf Life</option><option value="3">&lt;= 3 months</option><option value="6">4-6 months</option></select></div>
          <div class="col-md-2">${user.role === 'distributor' ? '<button class="btn btn-primary w-100" id="addMed"><i class="bi bi-plus-lg"></i> Add</button>' : ''}</div>
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Medicine</th><th>Category</th><th>Distributor</th><th>Price</th><th>Stock</th><th>Expiry</th><th>Shelf Life</th><th></th></tr></thead><tbody id="medBody"></tbody></table></div>
      </div></div>`;
      const data = await getCached('api/medicines.php');
      const catSel = $('#medCat');
      if (!catSel) return;
      catSel.insertAdjacentHTML('beforeend', [...new Set(data.medicines.map(m => m.category))].map(c => `<option>${c}</option>`).join(''));
      renderMedTable();
    },

    async orders() {
      const data = await getCached('api/orders.php');
      window._ordersData = data.orders;
      pageContent.innerHTML = `<div class="card"><div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Order</th><th>Items</th><th>Total</th><th>Buyer</th><th>Distributor</th><th>Deliver To</th><th>Tracking</th><th>Status</th><th>Payment</th><th></th></tr></thead><tbody id="ordersBody"></tbody></table></div></div></div>`;
      renderOrderRows();
    },

    async history() {
      const data = await getCached('api/orders.php');
      const done = data.orders.filter(o => o.status === 'Delivered' || o.status === 'Cancelled');
      pageContent.innerHTML = `<div class="card"><div class="card-body"><div class="table-responsive">
        <table class="table table-hover align-middle"><thead class="table-light"><tr><th>Order</th><th>Items</th><th>Total</th><th>${user.role === 'org' ? 'Distributor' : 'Buyer'}</th><th>Completed</th><th>Payment</th><th>Status</th></tr></thead>
        <tbody>${done.map(o => `<tr><td>#${o.id}</td><td>${o.items}</td><td>${money(o.total)}</td>
        <td class="small">${user.role === 'org' ? (o.distributor_name || '\u2014') : o.buyer}</td>
        <td class="small">${(o.delivered_at || o.created_at).slice(0, 10)}</td>
        <td><span class="badge ${o.payment_status === 'Completed' ? 'bg-success' : o.payment_status === 'Cancelled' ? 'bg-secondary' : 'bg-warning text-dark'}">${o.payment_status}</span></td>
        <td><span class="badge ${o.status === 'Delivered' ? 'bg-success' : 'bg-danger'}">${o.status}</span></td></tr>`).join('') || '<tr><td colspan="7" class="text-secondary">No completed orders yet.</td></tr>'}</tbody></table>
      </div></div></div>`;
    },

    async reviews() {
      const data = await get('api/reviews.php?mine=1');
      const stars = n => '★'.repeat(n) + '☆'.repeat(5 - n);
      const pending = data.pending.map(o => `
        <tr><td>#${o.id}</td><td class="small">${o.items || '-'}</td><td class="small">${o.distributor}</td>
        <td>${money(o.total)}</td><td class="small">${o.created_at.slice(0, 10)}</td>
        <td><button class="btn btn-sm btn-outline-warning btn-review" data-id="${o.id}"><i class="bi bi-star"></i> Rate &amp; review</button></td></tr>`).join('');
      pageContent.innerHTML = `
      <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card stat-card ok"><div class="card-body"><div class="stat-lbl">Your average rating</div><div class="stat-val">${Number(data.stats.avg).toFixed(1)}</div><div class="small text-secondary">${stars(Math.round(data.stats.avg))}</div></div></div></div>
        <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="stat-lbl">Reviews submitted</div><div class="stat-val">${data.stats.count}</div></div></div></div>
        <div class="col-md-4"><div class="card stat-card warn"><div class="card-body"><div class="stat-lbl">Awaiting your review</div><div class="stat-val">${data.pending.length}</div></div></div></div>
      </div>
      <div class="card mb-4"><div class="card-header"><i class="bi bi-hourglass-split"></i> Delivered orders awaiting review</div>
        <div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Order</th><th>Items</th><th>Distributor</th><th>Total</th><th>Date</th><th></th></tr></thead>
        <tbody>${pending || '<tr><td colspan="6" class="text-secondary">No delivered orders awaiting a review.</td></tr>'}</tbody></table></div></div>
      </div>
      <div class="card"><div class="card-header"><i class="bi bi-star"></i> My reviews</div>
        <ul class="list-group list-group-flush">${data.reviews.map(r => `
          <li class="list-group-item">
            <div class="d-flex flex-wrap justify-content-between gap-2">
              <div><span class="text-warning">${stars(r.rating)}</span> <b>${r.distributor}</b> <span class="text-secondary small">&middot; order #${r.order_id}</span></div>
              <span class="text-secondary small">${r.created_at.slice(0, 10)}</span>
            </div>
            ${r.comment ? `<div class="small mt-1">${r.comment}</div>` : '<div class="small text-secondary mt-1">No comment</div>'}
          </li>`).join('') || '<li class="list-group-item text-secondary small">No reviews submitted yet.</li>'}</ul>
      </div>`;
    },

    async notifications() {
      const data = await getCached('api/notifications.php');
      pageContent.innerHTML = `<div class="card"><ul class="list-group list-group-flush">${data.notifications.map(n => `
        <li class="list-group-item"><span class="badge ${badgeType(n.type)} me-2">${n.type}</span>${n.text}<span class="text-secondary float-end small">${n.created_at.slice(0, 10)}</span></li>`).join('') || '<li class="list-group-item small text-secondary">No notifications</li>'}</ul></div>`;
      await post('api/notifications.php', { action: 'read_all' });
      cacheDrop('api/notifications.php');
      try { sessionStorage.removeItem('medshare_cache:api/notifications.php'); } catch {}
      setNotifBadge(0);
    },

    async reports() {
      const data = await getCached('api/reports.php');
      const s = data.stats;
      pageContent.innerHTML = `
      <div class="row g-3 mb-3">
        <div class="col-md-6"><div class="card"><div class="card-body"><h6>Stock by Category</h6><canvas id="stockChart" height="180"></canvas></div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-body"><h6>Wastage Risk (expiring &le;3 months)</h6>
          <table class="table table-sm mt-2">${s.risk.map(m => `<tr><td>${m.name}</td><td>${m.distributor}</td><td class="text-danger fw-bold">${m.expiry}</td></tr>`).join('') || '<p class="text-secondary small mt-2">Nothing at risk.</p>'}</table>
        </div></div></div>
        <div class="col-12 d-flex justify-content-end">
          <button class="btn btn-outline-secondary btn-sm" onclick="location.href='api/reports.php?format=csv'"><i class="bi bi-download"></i> Export CSV</button>
        </div>
      </div>`;
      drawChart(s.byCat);
    },

    async audit() {
      const data = await get('api/audit.php');
      pageContent.innerHTML = `<div class="card"><div class="card-body"><table class="table table-sm"><thead class="table-light"><tr><th>User</th><th>Action</th><th>Date</th></tr></thead><tbody>${data.logs.map(l => `<tr><td>${l.user}</td><td>${l.action}</td><td>${l.created_at}</td></tr>`).join('')}</tbody></table></div></div>`;
    },

    async distributors() {
      const data = await get('api/distributors.php');
      pageContent.innerHTML = `<div class="card"><div class="card-body">
        <table class="table align-middle"><thead class="table-light"><tr><th>Distributor</th><th>Email</th><th>License</th><th>Status</th><th>Medicines</th><th>Orders</th><th>Joined</th><th></th></tr></thead>
        <tbody>${data.distributors.map(d => `<tr><td class="fw-bold">${d.name}</td><td>${d.email}</td><td>${d.license_no || '-'}</td>
        <td><span class="badge ${d.verified ? 'bg-success' : 'bg-warning text-dark'}">${d.verified ? 'Verified' : 'Pending'}</span></td>
        <td>${d.meds}</td><td>${d.orders}</td><td>${d.created_at.slice(0, 10)}</td>
        <td class="text-nowrap">
          ${d.verified ? '' : `<button class="btn btn-sm btn-success me-1 btn-verify" data-id="${d.id}" data-action="approve">Approve</button>
          <button class="btn btn-sm btn-outline-danger me-1 btn-verify" data-id="${d.id}" data-action="reject">Reject</button>`}
          <button class="btn btn-sm btn-outline-danger btn-remove" data-id="${d.id}"><i class="bi bi-trash"></i> Remove</button>
        </td></tr>`).join('')}</tbody></table>
      </div></div>`;
    },

    async verify() {
      const data = await get('api/verify.php');
      pageContent.innerHTML = `<div class="card"><div class="card-body">
        <table class="table align-middle"><thead class="table-light"><tr><th>Name</th><th>Type</th><th>Contact</th><th>City</th><th>License / Reg. no.</th><th>Document</th><th>Submitted</th><th></th></tr></thead>
        <tbody>${data.pending.map(v => `<tr><td class="fw-bold">${v.name}</td><td>${v.org_type || cap(v.role)}</td>
        <td class="small">${v.email}<br><span class="text-secondary">${v.phone || '\u2014'}</span></td>
        <td>${v.city || '\u2014'}</td><td>${v.license_no || v.registration_no || '\u2014'}</td>
        <td>${v.license_image ? `<a href="${v.license_image}" target="_blank" rel="noopener"><img src="${v.license_image}" alt="License document" style="height:46px;border-radius:6px"></a>` : '\u2014'}</td>
        <td>${v.created_at.slice(0, 10)}</td>
        <td class="text-nowrap"><button class="btn btn-sm btn-success me-1 btn-verify" data-id="${v.id}" data-action="approve">Approve</button>
        <button class="btn btn-sm btn-outline-danger btn-verify" data-id="${v.id}" data-action="reject">Reject</button></td></tr>`).join('') || '<tr><td colspan="8" class="text-secondary">No pending verifications.</td></tr>'}</tbody></table>
      </div></div>`;
    },

    async map() {
      pageContent.innerHTML = `
      <div class="card"><div class="card-body p-0">
        <div id="dashMap" style="height: 500px; width: 100%;"></div>
        <div class="p-3 text-secondary small">Verified organizations and distributors with a saved location appear as markers. Use the fullscreen Delivery Map for navigation.</div>
      </div></div>`;
      await loadScript('https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js');
      await loadCSS('https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css');
      setTimeout(() => initDashMap(), 50);
    }
  };

  function renderOrderRows() {
    const badge = s => ({
      Delivered: 'bg-success', Accepted: 'bg-info', Packed: 'bg-primary',
      Shipped: 'bg-info', 'Out for Delivery': 'bg-primary', Cancelled: 'bg-danger',
      'Pending Payment': 'bg-danger'
    })[s] || 'bg-warning text-dark';
    const rows = (window._ordersData || []).map(o => `
      <tr><td>#${o.id}</td><td>${o.items}</td><td>${money(o.total)}</td>
      <td>${o.buyer}</td><td class="text-nowrap small">${o.distributor_name || '—'}</td>
      <td class="text-nowrap small">${o.delivery_address || '—'}${o.delivery_phone ? `<br><span class="text-secondary">${o.delivery_phone}</span>` : ''}</td>
      <td class="text-nowrap small">${o.tracking_number || '—'}</td>
      <td><span class="badge ${badge(o.status)}">${o.status}</span></td>
      <td><span class="badge ${o.payment_status === 'Completed' ? 'bg-success' : o.payment_status === 'Failed' ? 'bg-danger' : 'bg-warning text-dark'}">${o.payment_status}</span></td>
      <td class="text-nowrap">
        <button class="btn btn-sm btn-outline-secondary btn-track" data-id="${o.id}" data-status="${o.status}"><i class="bi bi-signpost-split"></i> Track</button>
        <button class="btn btn-sm btn-outline-primary" onclick="window.print()"><i class="bi bi-file-earmark-text"></i> Invoice</button>
        ${user.role === 'distributor' && o.status === 'Pending' ? `
          <button class="btn btn-sm btn-success btn-accept" data-id="${o.id}"><i class="bi bi-check-lg"></i> Accept</button>
          <button class="btn btn-sm btn-outline-danger btn-reject" data-id="${o.id}">Reject</button>` : ''}
        ${user.role === 'distributor' && o.status === 'Accepted' ? `
          <button class="btn btn-sm btn-primary btn-pack" data-id="${o.id}"><i class="bi bi-box-seam"></i> Pack</button>` : ''}
        ${user.role === 'distributor' && o.status === 'Packed' ? `
          <button class="btn btn-sm btn-primary btn-ship" data-id="${o.id}"><i class="bi bi-truck"></i> Ship</button>` : ''}
        ${(user.role === 'distributor' && ['Accepted', 'Packed', 'Shipped', 'Out for Delivery'].includes(o.status) && (o.delivery_address || (o.org_lat && o.org_lng))) ? `
          <button class="btn btn-sm btn-outline-info btn-map" data-id="${o.id}" data-lat="${o.org_lat || ''}" data-lng="${o.org_lng || ''}" data-name="${o.buyer}" data-addr="${o.delivery_address || ''}" data-phone="${o.delivery_phone || ''}"><i class="bi bi-geo-alt"></i> Navigate</button>` : ''}
        ${user.role === 'distributor' && o.status === 'Shipped' ? `
          <button class="btn btn-sm btn-outline-primary btn-ofd" data-id="${o.id}"><i class="bi bi-send"></i> Out for Delivery</button>` : ''}
        ${user.role === 'distributor' && o.status === 'Out for Delivery' ? `
          <input type="text" class="form-control form-control-sm d-inline w-auto" id="track${o.id}" placeholder="Tracking no.">
          <button class="btn btn-sm btn-outline-success btn-deliver ms-1" data-id="${o.id}"><i class="bi bi-check2"></i> Delivered</button>` : ''}
        ${user.role === 'distributor' && o.payment_status === 'Pending' && o.method !== 'Razorpay' ? `
          <button class="btn btn-sm btn-outline-success btn-pay" data-id="${o.id}" data-amt="${o.total}" data-meth="${o.method}"><i class="bi bi-cash-coin"></i> Mark Paid</button>` : ''}
        ${user.role === 'org' && o.payment_status === 'Pending' && o.method === 'Razorpay' ? `
          <button class="btn btn-sm btn-success btn-paynow" data-id="${o.id}" data-amt="${o.total}"><i class="bi bi-credit-card"></i> Pay now</button>` : ''}
        ${user.role === 'org' && ['Pending', 'Pending Payment'].includes(o.status) ? `
          <button class="btn btn-sm btn-outline-danger btn-cancel" data-id="${o.id}">Cancel</button>` : ''}
        ${user.role === 'org' && o.status === 'Delivered' ? `<button class="btn btn-sm btn-outline-warning btn-review" data-id="${o.id}">Review</button>` : ''}
      </td></tr>`).join('');
    const body = $('#ordersBody');
    if (body) body.innerHTML = rows || '<tr><td colspan="10" class="text-secondary">No orders yet.</td></tr>';
  }

  function setOrderStatus(id, status, tracking) {
    const o = (window._ordersData || []).find(x => x.id === id);
    if (o) {
      o.status = status;
      if (tracking !== undefined) o.tracking_number = tracking || null;
    }
    cachePut('api/orders.php', { orders: window._ordersData });
    renderOrderRows();
  }

  /* ---------- shared render helpers ---------- */
  const badgeType = t => ({ expiry: 'bg-danger', order: 'bg-primary', verify: 'bg-warning', system: 'bg-secondary' })[t] || 'bg-secondary';

  const loadScript = src => new Promise(res => { const s = document.createElement('script'); s.src = src; s.onload = res; document.head.appendChild(s); });
  const loadCSS = href => { if (document.querySelector(`link[href="${href}"]`)) return; const l = document.createElement('link'); l.rel = 'stylesheet'; l.href = href; document.head.appendChild(l); };

  async function initDashMap() {
    const el = document.getElementById('dashMap');
    if (!el || window._dashMapInited) return;
    window._dashMapInited = true;
    const map = L.map('dashMap', { scrollWheelZoom: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
    const group = L.featureGroup().addTo(map);
    try {
      const data = await get('api/locations.php');
      data.locations.forEach(p => {
        const iconColor = p.role === 'distributor' ? '#0B5C4E' : '#198754';
        const marker = L.marker([p.lat, p.lng]).addTo(group);
        const dest = p.address ? `${p.address}${p.city ? ', ' + p.city : ''}` : `${p.lat},${p.lng}`;
        marker.bindPopup(`<b>${p.name}</b><br>${p.address || 'Address not provided'}<br>${p.city || ''}<br><a href="https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(dest)}" target="_blank" class="btn btn-sm btn-primary mt-1">Directions</a>`);
      });
      if (data.locations.length) map.fitBounds(group.getBounds().pad(0.2));
      else map.setView([17.3850, 78.4867], 11);
    } catch { map.setView([17.3850, 78.4867], 11); }
  }

  async function renderMedTable() {
    const q = ($('#medSearch')?.value || '').trim();
    const cat = $('#medCat')?.value || '';
    const shelf = $('#medShelf')?.value || '';
    const url = `api/medicines.php?q=${encodeURIComponent(q)}&cat=${encodeURIComponent(cat)}${shelf ? '&shelf_max=' + shelf : ''}`;
    const data = await getCached(url);
    window._medsData = data.medicines;
    const body = $('#medBody');
    if (!body) return;
    body.innerHTML = data.medicines.map(m => `
      <tr>
        <td class="fw-bold">${m.name}</td><td>${m.category}</td><td>${m.distributor}</td>
        <td>${money(m.price)}</td>
        <td><span class="badge ${m.stock === 0 ? 'bg-danger' : m.stock < 250 ? 'bg-warning text-dark' : 'bg-success'}">${m.stock}</span></td>
        <td>${m.expiry}</td><td>${m.shelf_months} months</td>
        <td class="text-nowrap">${user.role === 'org'
          ? `<button class="btn btn-sm btn-primary btn-order" data-id="${m.id}" ${m.stock === 0 ? 'disabled' : ''}>Order</button>`
          : user.role === 'distributor'
            ? `<button class="btn btn-sm btn-outline-secondary btn-edit-med" data-id="${m.id}"><i class="bi bi-pencil"></i> Edit</button>
               <button class="btn btn-sm btn-outline-danger btn-del-med" data-id="${m.id}" data-name="${m.name}" title="Remove"><i class="bi bi-trash"></i></button>`
            : ''}</td>
      </tr>`).join('') || '<tr><td colspan="8" class="text-secondary">No medicines match your filters.</td></tr>';
  }

  function drawChart(byCat) {
    const canvas = $('#stockChart');
    if (!canvas) return;
    const cats = Object.keys(byCat), vals = cats.map(c => byCat[c]);
    const ctx = canvas.getContext('2d');
    const w = canvas.width = canvas.clientWidth || 500, h = canvas.height = 200;
    const max = Math.max(...vals);
    ctx.clearRect(0, 0, w, h);
    ctx.fillStyle = '#0B5C4E';
    cats.forEach((c, i) => {
      const bh = vals[i] / max * (h - 40);
      const x = i * w / cats.length + w / cats.length * 0.2;
      ctx.fillRect(x, h - bh - 20, w / cats.length * 0.6, bh);
      ctx.fillStyle = '#6F7A74';
      ctx.font = '11px sans-serif';
      ctx.fillText(c, x, h - 5);
      ctx.fillText(vals[i], x + w / cats.length * 0.3 - 10, h - bh - 25);
      ctx.fillStyle = '#0B5C4E';
    });
  }

  /* ---------- actions (delegated + modal wiring) ---------- */
  pageContent.addEventListener('click', async e => {
    const orderBtn = e.target.closest('.btn-order');
    if (orderBtn) {
      const m = (await get('api/medicines.php')).medicines.find(x => x.id === +orderBtn.dataset.id);
      if (!m || m.stock === 0) return;
      window._orderMed = m;
      const geo = geoStored();
      $('#orderModalBody').innerHTML = `
        <h6>${m.name}</h6>
        <p class="small text-secondary mb-3">${m.distributor} &middot; ${money(m.price)}/unit &middot; stock: ${m.stock} &middot; expires ${m.expiry}</p>
        <label class="form-label">Delivery address</label>
        <input type="text" class="form-control mb-2" id="orderAddress" placeholder="House, street, city, PIN">
        <label class="form-label">Mobile number</label>
        <input type="tel" class="form-control mb-2" id="orderPhone" inputmode="numeric" maxlength="10" placeholder="10-digit mobile number">
        <label class="form-label">Quantity</label>
        <input type="number" class="form-control mb-2" id="orderQty" min="1" max="${m.stock}" value="1">
        <div class="small text-secondary mb-3">Total: <span id="orderTotal">${money(m.price)}</span></div>
        <div class="small text-secondary mb-3">Delivery location: <span id="orderGeo">${geo ? `\u2713 ${geo.lat}, ${geo.lng}` : 'not enabled'}</span></div>
        <label class="form-label">Payment method</label>
        <select class="form-select mb-3" id="orderPay">
          <option value="Razorpay:upi">UPI (Razorpay)</option>
          <option value="Razorpay">Card / Netbanking (Razorpay)</option>
          <option value="Cash">Cash on Delivery</option>
        </select>
        <div class="small text-secondary mb-3">Minimum order: ${money(500)} per distributor.</div>
        <button class="btn btn-primary w-100" id="confirmOrder">Place Order</button>`;
      $('#orderAddress').value = savedAddress(user.address);
      $('#orderPhone').value = savedPhone(user.phone);
      $('#orderPhone').oninput = e => { e.target.value = digitsOnly(e.target.value); };
      $('#orderQty').oninput = () => $('#orderTotal').textContent = money(m.price * Math.max(1, $('#orderQty').value));
      bootstrap.Modal.getOrCreateInstance($('#orderModal')).show();
    }
    const addBtn = e.target.closest('#addMed');
    if (addBtn) {
      resetCam();
      bootstrap.Modal.getOrCreateInstance($('#addMedModal')).show();
    }
    const editBtn = e.target.closest('.btn-edit-med');
    if (editBtn) {
      const m = (window._medsData || []).find(x => x.id === +editBtn.dataset.id);
      if (!m) return;
      resetCam();
      window._editMedId = m.id;
      $('#addMedModal .modal-title').textContent = 'Edit Medicine';
      $('#addMedForm button[type=submit]').textContent = 'Update Medicine';
      $('#mName').value = m.name;
      $('#mCat').value = m.category;
      $('#mPrice').value = m.price;
      $('#mMrp').value = m.mrp || '';
      $('#mStock').value = m.stock;
      $('#mBatch').value = m.batch_no || '';
      $('#mExpiry').value = m.expiry;
      bootstrap.Modal.getOrCreateInstance($('#addMedModal')).show();
    }
    const delMedBtn = e.target.closest('.btn-del-med');
    if (delMedBtn) {
      if (!confirm(`Remove ${delMedBtn.dataset.name} from the store?`)) return;
      delMedBtn.disabled = true;
      const fd = new FormData();
      fd.append('action', 'delete');
      fd.append('id', delMedBtn.dataset.id);
      const res = await (await fetch('api/medicines.php', { method: 'POST', body: fd })).json();
      if (res.ok) { toast('Medicine removed'); cacheDrop('api/medicines.php'); cacheDrop('api/reports.php'); renderMedTable(); }
      else { toast(res.error, 'danger'); delMedBtn.disabled = false; }
    }
    const vBtn = e.target.closest('.btn-verify');
    if (vBtn) {
      if (vBtn.dataset.action === 'reject' && !confirm('Reject and remove this registration? The user will have to register again.')) return;
      vBtn.disabled = true;
      const res = await post('api/verify.php', { user_id: +vBtn.dataset.id, action: vBtn.dataset.action });
      if (res.ok) {
        cacheDrop('api/verify.php');
        cacheDrop('api/distributors.php');
        toast(vBtn.dataset.action === 'approve' ? 'Account approved' : 'Account rejected');
        show($('#pageTitle').textContent === 'Distributors' ? 'distributors' : 'verify');
      } else { toast(res.error, 'danger'); vBtn.disabled = false; }
    }
    const rmvBtn = e.target.closest('.btn-remove');
    if (rmvBtn) {
      const name = rmvBtn.closest('tr').children[0].textContent;
      if (!confirm(`Remove distributor "${name}"? This cannot be undone.`)) return;
      rmvBtn.disabled = true;
      const res = await post('api/distributors.php', { user_id: +rmvBtn.dataset.id, action: 'remove' });
      if (res.ok) { toast('Distributor removed'); show('distributors'); } else { toast(res.error, 'danger'); rmvBtn.disabled = false; }
    }
    const revBtn = e.target.closest('.btn-review');
    if (revBtn) {
      window._reviewOrder = revBtn.dataset.id;
      $('#revOrderId').value = revBtn.dataset.id;
      window._revRating = 0;
      $('#revStars').querySelectorAll('.star-btn').forEach(s => { s.textContent = '☆'; s.classList.remove('text-warning'); s.classList.add('text-secondary'); });
      $('#revComment').value = '';
      bootstrap.Modal.getOrCreateInstance($('#reviewModal')).show();
    }
    const acBtn = e.target.closest('.btn-accept');
    if (acBtn) {
      acBtn.disabled = true;
      const res = await post('api/orders.php', { action: 'accept', order_id: +acBtn.dataset.id });
      if (res.ok) { toast('Order accepted'); setOrderStatus(+acBtn.dataset.id, res.status); } else { toast(res.error, 'danger'); acBtn.disabled = false; }
    }
    const rjBtn = e.target.closest('.btn-reject');
    if (rjBtn) {
      if (!confirm('Reject this order? The reserved stock will be restored.')) return;
      rjBtn.disabled = true;
      const res = await post('api/orders.php', { action: 'reject', order_id: +rjBtn.dataset.id });
      if (res.ok) { toast('Order rejected, stock restored'); setOrderStatus(+rjBtn.dataset.id, 'Cancelled'); } else { toast(res.error, 'danger'); rjBtn.disabled = false; }
    }
    const pkBtn = e.target.closest('.btn-pack');
    if (pkBtn) {
      pkBtn.disabled = true;
      const res = await post('api/orders.php', { action: 'pack', order_id: +pkBtn.dataset.id });
      if (res.ok) { toast('Order packed'); setOrderStatus(+pkBtn.dataset.id, res.status); } else { toast(res.error, 'danger'); pkBtn.disabled = false; }
    }
    const shBtn = e.target.closest('.btn-ship');
    if (shBtn) {
      shBtn.disabled = true;
      const res = await post('api/orders.php', { action: 'ship', order_id: +shBtn.dataset.id });
      if (res.ok) { toast('Order shipped'); setOrderStatus(+shBtn.dataset.id, res.status); } else { toast(res.error, 'danger'); shBtn.disabled = false; }
    }
    const ofdBtn = e.target.closest('.btn-ofd');
    if (ofdBtn) {
      ofdBtn.disabled = true;
      const res = await post('api/orders.php', { action: 'out_for_delivery', order_id: +ofdBtn.dataset.id });
      if (res.ok) { toast('Out for delivery'); setOrderStatus(+ofdBtn.dataset.id, res.status); } else { toast(res.error, 'danger'); ofdBtn.disabled = false; }
    }
    const trkBtn = e.target.closest('.btn-track');
    if (trkBtn) {
      const steps = ['Pending', 'Accepted', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered'];
      const labels = ['Order placed', 'Accepted', 'Packed', 'Shipped', 'Out for delivery', 'Delivered'];
      const idx = steps.indexOf(trkBtn.dataset.status);
      $('#trackTitle').textContent = `Order #${trkBtn.dataset.id}`;
      $('#trackBody').innerHTML = trkBtn.dataset.status === 'Cancelled'
        ? '<div class="alert alert-danger mb-0">This order was cancelled.</div>'
        : `<ul class="track-timeline">${steps.map((s, i) => `<li class="${i <= idx ? 'done' : ''}"><span class="dot"></span>${labels[i]}</li>`).join('')}</ul>`;
      bootstrap.Modal.getOrCreateInstance($('#trackModal')).show();
    }
    const payBtn = e.target.closest('.btn-pay');
    if (payBtn) {
      if (!confirm(`Confirm payment of ${money(payBtn.dataset.amt)} via ${payBtn.dataset.meth} for order #${payBtn.dataset.id}?`)) return;
      payBtn.disabled = true;
      const res = await post('api/payment.php', { order_id: +payBtn.dataset.id, status: 'Completed' });
      if (res.ok) {
        toast('Payment confirmed');
        cacheDrop('api/reports.php');
        const o = (window._ordersData || []).find(x => x.id === +payBtn.dataset.id);
        if (o) o.payment_status = 'Completed';
        cachePut('api/orders.php', { orders: window._ordersData });
        renderOrderRows();
      } else { toast(res.error, 'danger'); payBtn.disabled = false; }
    }
    const cancelBtn = e.target.closest('.btn-cancel');
    if (cancelBtn) {
      if (!confirm(`Cancel order #${cancelBtn.dataset.id}?`)) return;
      cancelBtn.disabled = true;
      const res = await post('api/orders.php', { action: 'cancel', order_id: +cancelBtn.dataset.id });
      if (res.ok) {
        toast('Order cancelled');
        const o = (window._ordersData || []).find(x => x.id === +cancelBtn.dataset.id);
        if (o) { o.status = 'Cancelled'; o.payment_status = 'Cancelled'; }
        cachePut('api/orders.php', { orders: window._ordersData });
        renderOrderRows();
      } else { toast(res.error, 'danger'); cancelBtn.disabled = false; }
    }
    const paynowBtn = e.target.closest('.btn-paynow');
    if (paynowBtn) {
      paynowBtn.disabled = true;
      const paid = await payWithRazorpay([+paynowBtn.dataset.id], user);
      if (paid) {
        const o = (window._ordersData || []).find(x => x.id === +paynowBtn.dataset.id);
        if (o) { o.payment_status = 'Completed'; o.status = 'Pending'; }
        cachePut('api/orders.php', { orders: window._ordersData });
        renderOrderRows();
      } else paynowBtn.disabled = false;
    }
    const delBtn = e.target.closest('.btn-deliver');
    if (delBtn) {
      const tracking = $(`#track${delBtn.dataset.id}`)?.value?.trim();
      delBtn.disabled = true;
      const res = await post('api/orders.php', { action: 'deliver', order_id: +delBtn.dataset.id, tracking_number: tracking });
      if (res.ok) { toast('Order marked delivered'); setOrderStatus(+delBtn.dataset.id, res.status, tracking); } else { toast(res.error, 'danger'); delBtn.disabled = false; }
    }
    const mapBtn = e.target.closest('.btn-map');
    if (mapBtn) {
      const lat = +mapBtn.dataset.lat, lng = +mapBtn.dataset.lng;
      const name = mapBtn.dataset.name;
      const addr = mapBtn.dataset.addr;
      const phone = mapBtn.dataset.phone;
      localStorage.setItem('deliveryMap', JSON.stringify({ lat, lng, name, addr, phone }));
      location.href = 'delivery-map.html';
    }
  });

  pageContent.addEventListener('input', e => { if (['medSearch', 'medCat', 'medShelf'].includes(e.target.id)) renderMedTable(); });
  pageContent.addEventListener('change', e => { if (['medSearch', 'medCat', 'medShelf'].includes(e.target.id)) renderMedTable(); });

  $('#orderModalBody')?.addEventListener('click', async e => {
    if (!e.target.closest('#confirmOrder')) return;
    const m = window._orderMed;
    const qty = +$('#orderQty').value;
    if (m.price * qty < 500) { toast(`Minimum order is ${money(500)} per distributor.`, 'warning'); return; }
    const address = $('#orderAddress').value.trim();
    if (!address) { toast('Please enter a delivery address.', 'warning'); return; }
    const phone = digitsOnly($('#orderPhone').value);
    if (phone.length !== 10) { toast('Please enter a 10-digit mobile number.', 'warning'); return; }
    const btn = $('#confirmOrder');
    btn.disabled = true;
    const geo = await geoQuick();
    const [method, payMode] = $('#orderPay').value.split(':');
    const res = await post('api/orders.php', { items: [{ medicine_id: m.id, qty }], method, delivery_address: address, delivery_phone: phone, latitude: geo?.lat ?? null, longitude: geo?.lng ?? null });
    if (!res.ok) { toast(res.error, 'danger'); btn.disabled = false; return; }
    cookieSet(DELIVERY_COOKIE, address);
    cookieSet(DELIVERY_PHONE_COOKIE, phone);
    cacheDrop('api/orders.php');
    bootstrap.Modal.getInstance($('#orderModal')).hide();
    if (method === 'Razorpay') {
      const paid = await payWithRazorpay(res.order_ids, user, payMode === 'upi');
      if (!paid) toast('Payment pending. Pay any time from Orders.', 'warning');
    } else {
      toast('Order placed');
    }
    show('orders');
  });

  $('#addMedForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    if (!$('#mExpiry').value) { toast('Expiry date is required.', 'warning'); return; }
    const medName = $('#mName').value.trim();
    const fd = new FormData();
    fd.append('name', $('#mName').value);
    fd.append('category', $('#mCat').value);
    fd.append('price', $('#mPrice').value);
    if ($('#mMrp').value) fd.append('mrp', $('#mMrp').value);
    fd.append('stock', $('#mStock').value);
    fd.append('batch_no', $('#mBatch').value);
    fd.append('expiry', $('#mExpiry').value);
    if (camBlob) fd.append('image', new File([camBlob], 'camera.jpg', { type: 'image/jpeg' }));
    else if ($('#mImage').files[0]) fd.append('image', $('#mImage').files[0]);
    if (window._editMedId) { fd.append('action', 'update'); fd.append('id', window._editMedId); }
    const res = await (await fetch('api/medicines.php', { method: 'POST', body: fd })).json();
    if (res.ok) {
      toast(`${medName || 'Medicine'} ${window._editMedId ? 'updated' : 'added'}`);
      cacheDrop('api/medicines.php');
      cacheDrop('api/reports.php');
      bootstrap.Modal.getInstance($('#addMedModal')).hide();
      e.target.reset();
      resetCam();
      renderMedTable();
    } else toast(res.error, 'danger');
  });

  $('#addMedModal')?.addEventListener('hidden.bs.modal', () => {
    window._editMedId = null;
    $('#addMedForm').reset();
    $('#addMedModal .modal-title').textContent = 'Add Medicine';
    $('#addMedForm button[type=submit]').textContent = 'Save Medicine';
    resetCam();
  });

  /* camera capture (Add Medicine) */
  let camStream = null, camBlob = null;
  const camStop = () => { if (camStream) { camStream.getTracks().forEach(t => t.stop()); camStream = null; } };
  const resetCam = () => {
    camBlob = null;
    $('#camPreview')?.classList.add('d-none');
    $('#camNote')?.classList.add('d-none');
  };

  $('#takePhotoBtn')?.addEventListener('click', async () => {
    resetCam();
    try {
      camStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
      $('#camVideo').srcObject = camStream;
      $('#camError').classList.add('d-none');
    } catch {
      $('#camError').classList.remove('d-none');
    }
    bootstrap.Modal.getOrCreateInstance($('#cameraModal')).show();
  });

  $('#cameraModal')?.addEventListener('hidden.bs.modal', camStop);

  $('#camShoot')?.addEventListener('click', () => {
    const v = $('#camVideo');
    if (!camStream || !v.videoWidth) return;
    const c = document.createElement('canvas');
    c.width = v.videoWidth; c.height = v.videoHeight;
    c.getContext('2d').drawImage(v, 0, 0, c.width, c.height);
    c.toBlob(b => {
      camBlob = b;
      const p = $('#camPreview');
      p.src = URL.createObjectURL(b);
      p.classList.remove('d-none');
      $('#camNote').classList.remove('d-none');
      bootstrap.Modal.getInstance($('#cameraModal')).hide();
    }, 'image/jpeg', 0.9);
  });

  /* review modal: star pick + submit */
  $('#revStars')?.addEventListener('click', e => {
    const s = e.target.closest('.star-btn');
    if (!s) return;
    const v = +s.dataset.v;
    window._revRating = v;
    $('#revStars').querySelectorAll('.star-btn').forEach(star =>
      (+star.dataset.v <= v) ? (star.textContent = '★', star.classList.remove('text-secondary'), star.classList.add('text-warning'))
                             : (star.textContent = '☆', star.classList.remove('text-warning'), star.classList.add('text-secondary')));
  });

  $('#submitReview')?.addEventListener('click', async () => {
    if (!window._revRating) { toast('Pick a star rating.', 'warning'); return; }
    const res = await post('api/reviews.php', { order_id: +$('#revOrderId').value, rating: window._revRating, comment: $('#revComment').value });
    if (res.ok) { toast('Review submitted'); bootstrap.Modal.getInstance($('#reviewModal')).hide(); show($('#pageTitle').textContent === 'Reviews' ? 'reviews' : 'orders'); }
    else toast(res.error, 'danger');
  });

  function setNotifBadge(n) {
    const b = $('#notifCount');
    if (!b) return;
    b.textContent = n;
    b.style.display = n > 0 ? '' : 'none';
  }

  async function loadNotifCount() {
    const data = await getCached('api/notifications.php', 10000);
    setNotifBadge(data.notifications.filter(n => !+n.is_read).length);
  }

  /* ---------- init ---------- */
  (async () => {
    if (!await loadUser()) return;
    if (user.role === 'org' && !geoFresh()) geoRequest();
    window.addEventListener('geo', () => {
      const el = $('#orderGeo');
      if (!el) return;
      const g = geoStored();
      el.textContent = g ? `\u2713 ${g.lat}, ${g.lng}` : 'not enabled';
    });
    const pages = rolePages();
    document.querySelectorAll('[data-page]').forEach(el => {
      if (!pages.includes(el.dataset.page)) el.classList.add('d-none');
      el.addEventListener('click', () => show(el.dataset.page));
    });
    loadNotifCount();
    setInterval(loadNotifCount, 30000);
    /* warm every tab in parallel so switching feels instant (session lock is released) */
    const PAGE_API = {
      overview: 'api/reports.php', medicines: 'api/medicines.php', orders: 'api/orders.php',
      reviews: 'api/reviews.php?mine=1', notifications: 'api/notifications.php', reports: 'api/reports.php',
      distributors: 'api/distributors.php', audit: 'api/audit.php', verify: 'api/verify.php', map: 'api/locations.php'
    };
    const warm = [...new Set(pages.map(p => PAGE_API[p]).filter(Boolean))];
    setTimeout(() => warm.forEach(u => getCached(u).catch(() => {})), 400);
    const wanted = new URLSearchParams(location.search).get('page');
    show(pages.includes(wanted) ? wanted : (pages.includes('overview') ? 'overview' : pages[0]));
  })();
})();
````

## Stylesheet

### `css/style.css`

> Complete custom theme layered over Bootstrap 5.  
> Lines: 404 &middot; Size: 20.2 KB

````css
/* MEDRED design system - cream + deep green (UI reference). */
:root {
  --green: #0B5C4E;
  --green-dark: #084A3F;
  --green-soft: #E7F0E6;
  --ink: #11332B;
  --body: #38453F;
  --muted: #6F7A74;
  --cream: #F7F5EE;
  --footer-bg: #F0EDE2;
  --line: #E5E2D6;
  --orange-bg: #FCEBD9;
  --orange-fg: #B26A1F;
  --radius: 18px;

  --bs-primary: #0B5C4E;
  --bs-primary-rgb: 11, 92, 78;
  --bs-success: #3E7C5B;
  --bs-success-rgb: 62, 124, 91;
  --bs-warning: #C98A2E;
  --bs-warning-rgb: 201, 138, 46;
  --bs-danger: #C0532F;
  --bs-danger-rgb: 192, 83, 47;
  --bs-secondary: #6F7A74;
  --bs-secondary-rgb: 111, 122, 116;
  --bs-info: #4E7F8C;
  --bs-info-rgb: 78, 127, 140;
  --bs-body-bg: #F7F5EE;
  --bs-body-color: #38453F;
  --bs-border-color: #E5E2D6;
  --bs-link-color: #0B5C4E;
  --bs-link-color-rgb: 11, 92, 78;
  --bs-link-hover-color: #084A3F;
  --bs-font-sans-serif: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
}

body {
  background: var(--cream);
  color: var(--body);
  font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
  -webkit-font-smoothing: antialiased;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
body > main { flex: 1 0 auto; }
body > #footer { margin-top: auto; }

h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6, .display-4, .display-6 {
  color: var(--ink);
  font-weight: 800;
  letter-spacing: -0.02em;
}

a { color: var(--green); }
.text-secondary { color: var(--muted) !important; }

/* ---------- buttons ---------- */
.btn {
  --bs-btn-border-radius: 999px;
  --bs-btn-padding-x: 1.3rem;
  --bs-btn-padding-y: 0.6rem;
  font-weight: 600;
  letter-spacing: 0.01em;
}
.btn-sm { --bs-btn-padding-x: 0.9rem; --bs-btn-padding-y: 0.35rem; font-size: 0.85rem; }
.btn-lg { --bs-btn-padding-x: 1.6rem; --bs-btn-padding-y: 0.8rem; font-size: 1.05rem; }

.btn-primary {
  --bs-btn-bg: var(--green); --bs-btn-border-color: var(--green);
  --bs-btn-hover-bg: var(--green-dark); --bs-btn-hover-border-color: var(--green-dark);
  --bs-btn-active-bg: var(--green-dark); --bs-btn-active-border-color: var(--green-dark);
  --bs-btn-disabled-bg: #8FA9A2; --bs-btn-disabled-border-color: #8FA9A2;
  --bs-btn-color: #fff; --bs-btn-hover-color: #fff; --bs-btn-active-color: #fff;
}
.btn-outline-primary {
  --bs-btn-color: var(--green); --bs-btn-border-color: var(--green);
  --bs-btn-hover-bg: var(--green); --bs-btn-hover-border-color: var(--green); --bs-btn-hover-color: #fff;
  --bs-btn-active-bg: var(--green-dark); --bs-btn-active-border-color: var(--green-dark);
}
.btn-success {
  --bs-btn-bg: var(--green); --bs-btn-border-color: var(--green);
  --bs-btn-hover-bg: var(--green-dark); --bs-btn-hover-border-color: var(--green-dark);
  --bs-btn-active-bg: var(--green-dark); --bs-btn-active-border-color: var(--green-dark);
}
.btn-outline-success {
  --bs-btn-color: var(--green); --bs-btn-border-color: var(--green);
  --bs-btn-hover-bg: var(--green); --bs-btn-hover-border-color: var(--green); --bs-btn-hover-color: #fff;
}
.btn-outline-danger { --bs-btn-color: var(--bs-danger); --bs-btn-border-color: #E3B7A4; --bs-btn-hover-bg: var(--bs-danger); --bs-btn-hover-border-color: var(--bs-danger); }
.btn-outline-secondary { --bs-btn-color: var(--muted); --bs-btn-border-color: var(--line); --bs-btn-hover-bg: #EFEDE2; --bs-btn-hover-border-color: var(--line); --bs-btn-hover-color: var(--ink); }
.btn-outline-warning { --bs-btn-color: var(--bs-warning); --bs-btn-border-color: #E8CD9F; --bs-btn-hover-bg: var(--bs-warning); --bs-btn-hover-border-color: var(--bs-warning); }
.btn-light { --bs-btn-bg: #fff; --bs-btn-border-color: var(--line); --bs-btn-hover-bg: #F4F2E9; --bs-btn-hover-border-color: var(--line); --bs-btn-color: var(--ink); }
.btn-white-pill { background: #fff; border: 1px solid var(--line); color: var(--ink); }
.btn-white-pill:hover { background: #F4F2E9; color: var(--ink); }

/* ---------- forms ---------- */
.form-control, .form-select {
  border-radius: 14px;
  padding: 0.7rem 1.1rem;
  border-color: var(--line);
  background-color: #fff;
  color: var(--body);
}
.form-control::placeholder { color: #9AA49E; }
.form-control:focus, .form-select:focus {
  border-color: var(--green);
  box-shadow: 0 0 0 0.2rem rgba(11, 92, 78, 0.12);
  background-color: #fff;
}
.form-label { font-weight: 600; font-size: 0.875rem; color: var(--ink); }
.form-check-input:checked { background-color: var(--green); border-color: var(--green); }
.form-check-input:focus { border-color: var(--green); box-shadow: 0 0 0 0.2rem rgba(11, 92, 78, 0.12); }

/* ---------- cards / tables / badges ---------- */
.card {
  --bs-card-border-radius: var(--radius);
  --bs-card-border-color: var(--line);
  box-shadow: none;
}
.card-header { background: #fff; border-color: var(--line); font-weight: 700; color: var(--ink); }
.table { --bs-table-border-color: var(--line); color: var(--body); }
.table thead.table-light { --bs-table-bg: #F1EFE6; --bs-table-color: #57635C; }
.table > :not(caption) > * > * { padding: 0.75rem 0.75rem; }

.badge { font-weight: 600; padding: 0.4em 0.75em; border-radius: 999px; }
.badge.bg-success { background: var(--green-soft) !important; color: #3E7C5B !important; }
.badge.bg-warning { background: var(--orange-bg) !important; color: var(--orange-fg) !important; }
.badge.bg-danger { background: #F9E3DB !important; color: #B34A26 !important; }
.badge.bg-primary { background: var(--green-soft) !important; color: #3E7C5B !important; }
.badge.bg-info { background: #E3EEF1 !important; color: #41707C !important; }
.badge.bg-secondary { background: #EDEBE3 !important; color: var(--muted) !important; }
.badge.bg-light { background: #F1EFE6 !important; }

.alert-success { --bs-alert-color: #2F6B4F; --bs-alert-bg: #E8F2EA; --bs-alert-border-color: #D3E6D8; border-radius: 14px; }
.alert-danger { --bs-alert-color: #9C3F22; --bs-alert-bg: #FBE9E2; --bs-alert-border-color: #EFCFC2; border-radius: 14px; }
.alert-warning { --bs-alert-color: #8A5A18; --bs-alert-bg: #FCF0DD; --bs-alert-border-color: #EFD9B4; border-radius: 14px; }

.modal-content { border-radius: 20px; border-color: var(--line); }
.star-btn { cursor: pointer; }

/* ---------- site nav ---------- */
.site-nav {
  background: var(--cream);
  border-bottom: 1px solid var(--line);
  position: sticky;
  top: 0;
  z-index: 1030;
}
.site-nav .container { height: 72px; display: flex; align-items: center; gap: 1.75rem; }
.brand { display: inline-flex; align-items: center; gap: 0.6rem; font-weight: 800; font-size: 1.35rem; color: var(--ink); text-decoration: none; letter-spacing: -0.02em; }
.brand:hover { color: var(--ink); }
.brand-mark {
  width: 36px; height: 36px; border-radius: 11px; background: var(--green);
  color: #fff; display: inline-grid; place-items: center;
  font-size: 1.5rem; font-weight: 700; line-height: 1; padding-bottom: 3px;
}
.brand-logo { width: 36px; height: 36px; border-radius: 11px; object-fit: cover; display: block; }
.nav-links { list-style: none; display: flex; gap: 1.6rem; margin: 0; padding: 0; }
.nav-links a { color: #57635C; text-decoration: none; font-weight: 500; font-size: 0.95rem; }
.nav-links a:hover { color: var(--green); }
.nav-actions { margin-left: auto; display: flex; align-items: center; gap: 1rem; }
.nav-signin { color: var(--ink); font-weight: 600; text-decoration: none; font-size: 0.95rem; }
.nav-signin:hover { color: var(--green); }
.nav-register { color: var(--green); font-weight: 700; text-decoration: none; font-size: 0.95rem; }
.cart-pill { padding: 0.55rem 1.25rem; font-size: 0.95rem; }
.user-chip { color: var(--muted); font-size: 0.9rem; }
.icon-link {
  display: inline-flex; align-items: center; gap: 0.35rem; position: relative;
  color: var(--ink); text-decoration: none; font-weight: 500; font-size: 0.95rem;
}
.icon-link i { font-size: 1.2rem; }
.icon-link:hover { color: var(--green); }
.nav-dot {
  position: absolute; top: -5px; right: -8px; min-width: 16px; height: 16px;
  padding: 0 4px; border-radius: 8px; background: #dc3545; color: #fff;
  font-size: 10px; font-weight: 700; line-height: 16px; text-align: center; display: none;
}
.is-distributor .add-btn { display: none !important; }

/* ---------- pills / profile ---------- */
.pill { display: inline-block; border-radius: 999px; padding: 0.5rem 1.05rem; font-size: 0.9rem; font-weight: 600; }
.pill-green { background: var(--green-soft); color: #2F6B4F; }
.pill-gray { background: #EFEDE2; color: #57635C; }
.profile-title { font-size: clamp(2.2rem, 4vw, 3rem); font-weight: 900; letter-spacing: -0.03em; }
.profile-lbl { color: var(--muted); font-size: 0.95rem; font-weight: 500; }
.profile-num { font-size: 2.6rem; font-weight: 900; color: var(--ink); line-height: 1.15; letter-spacing: -0.02em; }
.profile-link { color: var(--green); font-weight: 600; text-decoration: none; }
.profile-link:hover { text-decoration: underline; }
.profile-account { font-size: 1.7rem; font-weight: 800; color: var(--ink); letter-spacing: -0.02em; line-height: 1.25; margin-top: 0.25rem; }
.profile-h2 { font-size: 1.4rem; font-weight: 800; }
.profile-facts { margin: 0; }
.profile-facts > div { padding: 0.9rem 0; }
.profile-facts dt { color: var(--muted); font-weight: 500; font-size: 0.95rem; }
.profile-facts dd { margin: 0.15rem 0 0; font-weight: 600; color: var(--ink); }

/* ---------- cart empty ---------- */
.cart-empty { font-size: 1.25rem; color: var(--muted); padding: 1.5rem 0 4rem; }
.cart-empty a { color: var(--green); font-weight: 700; text-decoration: none; }
.cart-empty a:hover { text-decoration: underline; }

/* ---------- mini cart bar ---------- */
.mini-cart {
  position: fixed; left: 0; right: 0; bottom: 0; z-index: 1040;
  background: #fff; border-top: 1px solid var(--line);
  box-shadow: 0 -8px 24px rgba(16, 42, 34, 0.08);
  padding: 0.75rem 0;
}
.mini-cart-inner {
  max-width: 1140px; margin: 0 auto; padding: 0 1.5rem;
  display: flex; align-items: center; gap: 1rem;
}
.mini-cart-name {
  flex: 1; min-width: 0; font-weight: 700; color: var(--ink);
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.mini-stepper {
  display: flex; align-items: center; gap: 0.25rem;
  border: 1px solid var(--line); border-radius: 999px; padding: 0.2rem; background: var(--cream);
}
.mini-arrow {
  border: 0; background: transparent; width: 32px; height: 32px; border-radius: 50%;
  color: var(--ink); display: inline-grid; place-items: center;
}
.mini-arrow:hover { background: #fff; color: var(--green); }
.mini-qty { min-width: 1.5rem; text-align: center; font-weight: 700; color: var(--ink); }
.mini-go { padding: 0.45rem 1.1rem; }
.mini-close { border: 0; background: transparent; color: var(--muted); font-size: 0.9rem; padding: 0.25rem; }
@media (max-width: 480px) {
  .mini-cart-inner { flex-wrap: wrap; gap: 0.6rem; }
  .mini-cart-name { flex: 1 1 100%; }
  .mini-go { margin-left: auto; }
}

@media (max-width: 480px) {
  .site-nav .container { gap: 0.5rem; }
  .site-nav .brand { font-size: 1.1rem; }
  .site-nav .brand-mark { width: 30px; height: 30px; font-size: 1.2rem; }
  .site-nav .brand-logo { width: 30px; height: 30px; }
  .cart-pill { padding: 0.5rem 0.85rem; font-size: 0.85rem; }
  .icon-link span { display: none; }
}

/* ---------- hero ---------- */
.hero { padding: 4.5rem 0 4rem; }
.hero-badge {
  display: inline-block; background: var(--green-soft); color: #2F6B4F;
  border-radius: 999px; padding: 0.45rem 1.1rem; font-size: 0.9rem; font-weight: 600;
}
.hero h1 {
  font-size: clamp(2.7rem, 5.4vw, 4.2rem);
  font-weight: 900;
  letter-spacing: -0.035em;
  line-height: 1.04;
  margin: 1.5rem 0 1.25rem;
}
.hero-sub { color: var(--muted); font-size: 1.15rem; max-width: 34rem; line-height: 1.6; }
.hero-art { width: 100%; height: auto; aspect-ratio: 4 / 3; object-fit: cover; border-radius: 24px; display: block; }

.section { padding: 3.5rem 0; }
.section-head { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 1.75rem; }
.section-head h2 { font-size: 1.9rem; margin: 0; }
.view-all { color: var(--green); font-weight: 600; text-decoration: none; }
.view-all:hover { text-decoration: underline; }

.stat-chip { text-align: center; padding: 1.25rem 0.5rem; }
.stat-chip .num { font-size: 2rem; font-weight: 800; color: var(--ink); letter-spacing: -0.02em; }
.stat-chip .lbl { color: var(--muted); font-size: 0.9rem; }
.stat-chip .ico { font-size: 1.6rem; color: var(--green); }

.feature-card { padding: 1.6rem; height: 100%; }
.feature-card .ico {
  width: 46px; height: 46px; border-radius: 13px; background: var(--green-soft);
  color: var(--green); display: grid; place-items: center; font-size: 1.3rem; margin-bottom: 1rem;
}
.feature-card h5 { font-size: 1.05rem; }
.feature-card p { color: var(--muted); font-size: 0.9rem; margin: 0; }

.step { text-align: center; padding: 1rem; }
.step .num {
  width: 52px; height: 52px; border-radius: 50%; background: var(--green); color: #fff;
  display: inline-grid; place-items: center; font-weight: 800; font-size: 1.2rem; margin-bottom: 1rem;
}
.step p { color: var(--muted); font-size: 0.9rem; }

/* ---------- medicine cards ---------- */
.med-card {
  background: #fff; border: 1px solid var(--line); border-radius: var(--radius);
  padding: 1.35rem; display: flex; flex-direction: column; gap: 0.35rem;
  transition: box-shadow 0.15s ease, transform 0.15s ease;
}
.med-card:hover { box-shadow: 0 10px 28px rgba(17, 51, 43, 0.08); transform: translateY(-2px); }
.med-top { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.4rem; }
.med-cat { color: var(--muted); font-size: 0.75rem; font-weight: 600; letter-spacing: 0.09em; text-transform: uppercase; }
.shelf-pill { border-radius: 999px; padding: 0.3rem 0.8rem; font-size: 0.8rem; font-weight: 600; white-space: nowrap; }
.shelf-pill.orange { background: var(--orange-bg); color: var(--orange-fg); }
.shelf-pill.green { background: var(--green-soft); color: #3E7C5B; }
.med-name { color: var(--ink); font-weight: 800; font-size: 1.18rem; text-decoration: none; letter-spacing: -0.01em; line-height: 1.25; }
.med-name:hover { color: var(--green); }
.med-meta { color: var(--muted); font-size: 0.9rem; }
.med-foot { display: flex; align-items: center; justify-content: space-between; margin-top: 0.9rem; }
.med-price { font-size: 1.55rem; font-weight: 800; color: var(--ink); letter-spacing: -0.02em; }
.med-off { font-size: 0.85rem; margin-top: 2px; }
.med-off s { color: #9AA49E; }
.med-off span { color: #3E7C5B; font-weight: 700; margin-left: 0.35rem; }

/* ---------- store filters ---------- */
.sort-select { max-width: 260px; }
.chips { display: flex; flex-wrap: wrap; gap: 0.6rem; }
.chip {
  border: 0; border-radius: 999px; background: #EFEDE2; color: #57635C;
  font-weight: 500; font-size: 0.95rem; padding: 0.55rem 1.15rem; transition: all 0.15s ease;
}
.chip:hover { background: #E7E4D7; }
.chip.active { background: var(--green); color: #fff; font-weight: 600; }

/* ---------- camera capture ---------- */
.cam-preview { width: 56px; height: 56px; object-fit: cover; border-radius: 12px; border: 1px solid var(--line); }
#camVideo { aspect-ratio: 4 / 3; object-fit: cover; background: #0F2B25; }

/* ---------- register segmented control ---------- */
.seg { display: flex; background: #EFEDE2; border-radius: 999px; padding: 5px; }
.seg-input { position: absolute; opacity: 0; pointer-events: none; }
.seg-label {
  flex: 1; text-align: center; padding: 0.7rem 1rem; border-radius: 999px;
  font-weight: 600; color: var(--muted); cursor: pointer; transition: all 0.15s ease; margin: 0;
}
.seg-input:checked + .seg-label { background: #fff; color: var(--ink); box-shadow: 0 1px 4px rgba(17, 51, 43, 0.12); }

/* ---------- auth pages ---------- */
.auth-wrap { max-width: 560px; margin: 3.5rem auto; padding: 0 1rem; }
.auth-wrap h1 { font-size: 2.6rem; font-weight: 900; letter-spacing: -0.03em; margin-bottom: 2rem; }
.auth-card { background: transparent; }

/* ---------- detail pages ---------- */
.crumb { margin-bottom: 1.25rem; }
.crumb a { color: var(--muted); text-decoration: none; font-size: 0.9rem; font-weight: 600; }
.crumb a:hover { color: var(--green); }
.detail-img { border-radius: 20px; border: 1px solid var(--line); width: 100%; height: 100%; object-fit: cover; background: #fff; }
.detail-img-ph { border-radius: 20px; border: 1px solid var(--line); background: #fff; display: grid; place-items: center; min-height: 320px; color: #B9C2BC; font-size: 4rem; }
.detail-card { background: #fff; border: 1px solid var(--line); border-radius: var(--radius); padding: 1.8rem; height: 100%; }
.detail-name { font-size: 1.9rem; font-weight: 800; margin: 0.6rem 0 0.4rem; }
.detail-price { font-size: 2rem; font-weight: 800; color: var(--ink); letter-spacing: -0.02em; }
.detail-rows { margin: 1.4rem 0; border-top: 1px solid var(--line); }
.detail-rows > div { display: flex; justify-content: space-between; gap: 1rem; padding: 0.7rem 0; border-bottom: 1px solid var(--line); }
.detail-rows dt { color: var(--muted); font-weight: 500; }
.detail-rows dd { margin: 0; font-weight: 600; color: var(--ink); text-align: right; }
.review-item { border-bottom: 1px solid var(--line); padding: 0.8rem 0; }
.review-item:last-child { border-bottom: 0; }
.stars { color: #C98A2E; letter-spacing: 0.1em; }

/* ---------- cart ---------- */
.qty { max-width: 90px; }

/* ---------- dashboard (storefront style) ---------- */
.dash-eyebrow { text-transform: uppercase; letter-spacing: 0.12em; font-size: 0.72rem; font-weight: 700; color: var(--muted); }
.dash-title { font-size: clamp(2.2rem, 4vw, 3rem); font-weight: 900; letter-spacing: -0.03em; margin-bottom: 0.25rem; }
.dash-tabs { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.dash-tab {
  display: inline-flex; align-items: center; gap: 0.45rem; cursor: pointer; text-decoration: none;
  background: #fff; border: 1px solid var(--line); color: var(--ink);
  border-radius: 999px; padding: 0.5rem 1rem; font-size: 0.9rem; font-weight: 600;
}
.dash-tab:hover { border-color: var(--green); color: var(--green); }
.dash-tab.active { background: var(--green); border-color: var(--green); color: #fff; }
.dash-tab .badge { background: #EFEDE2; color: var(--ink); font-size: 0.7rem; }
.dash-tab.active .badge { background: rgba(255, 255, 255, 0.25); color: #fff; }
@media (max-width: 480px) {
  .dash-tabs { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 0.35rem; scrollbar-width: none; }
  .dash-tabs::-webkit-scrollbar { display: none; }
  .dash-tab { white-space: nowrap; }
}

.stat-card { border: 1px solid var(--line); border-left-width: 1px; }
.stat-card .card-body { padding: 1.25rem 1.4rem; }
.stat-card .stat-lbl { color: var(--muted); font-size: 0.85rem; font-weight: 600; }
.stat-card .stat-val { color: var(--ink); font-size: 1.9rem; font-weight: 800; letter-spacing: -0.02em; }
.stat-card.ok { border-top: 3px solid #3E7C5B; }
.stat-card.warn { border-top: 3px solid #C98A2E; }
.stat-card.danger { border-top: 3px solid #C0532F; }

.list-group-item { background: transparent; border-color: var(--line); }

/* ---------- footer ---------- */
.site-footer { background: var(--footer-bg); border-top: 1px solid var(--line); margin-top: 4rem; padding: 3.5rem 0 2.5rem; }
.footer-brand { font-weight: 800; font-size: 1.3rem; color: var(--ink); letter-spacing: -0.02em; margin-bottom: 0.75rem; }
.footer-desc { color: var(--muted); font-size: 0.95rem; max-width: 22rem; line-height: 1.6; }
.footer-links { list-style: none; margin: 0; padding: 0; }
.footer-links li { margin-bottom: 0.6rem; }
.footer-links a { color: #57635C; text-decoration: none; font-size: 0.95rem; }
.footer-links a:hover { color: var(--green); }
.footer-note { color: var(--muted); font-size: 0.95rem; line-height: 1.6; }
.footer-note a { color: var(--green); font-weight: 600; text-decoration: none; }

.table-sm td, .table-sm th { font-size: 0.85rem; }
.page-row { cursor: pointer; }

/* ---------- toasts ---------- */
.toast-stack {
  position: fixed; top: 1rem; right: 1rem; z-index: 1095;
  display: flex; flex-direction: column; gap: 0.5rem; width: min(92vw, 360px);
}
.toast-stack .toast { box-shadow: 0 10px 30px rgba(11, 47, 40, 0.18); }
@media (max-width: 575px) { .toast-stack { left: 1rem; right: 1rem; width: auto; } }
````

## Database

### `database.sql`

> PostgreSQL schema, constraints, indexes and demonstration seed data.  
> Lines: 175 &middot; Size: 8.4 KB

````sql
-- MedShare - Medicine Donation and Redistribution System
-- Supabase Postgres schema + seed. Run once in Supabase SQL Editor
-- (or via: php config/setup_db.php if present).

DROP TABLE IF EXISTS audit_logs, reviews, notifications, transactions,
  order_items, orders, medicines, ngos, distributors, users CASCADE;

CREATE TABLE users (
  id INT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  address VARCHAR(255),
  city VARCHAR(80),
  latitude DECIMAL(10,8) DEFAULT NULL,
  longitude DECIMAL(11,8) DEFAULT NULL,
  role VARCHAR(20) NOT NULL CHECK (role IN ('admin','distributor','org')),
  org_type VARCHAR(30) DEFAULT NULL CHECK (org_type IN ('NGO','Medical Shop','Old-Age Home','Orphanage','Charitable Hospital')),
  verified SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE distributors (
  id INT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  user_id INT NOT NULL UNIQUE REFERENCES users(id),
  license_no VARCHAR(60),
  license_image VARCHAR(255) DEFAULT NULL,
  verified SMALLINT NOT NULL DEFAULT 0
);

CREATE TABLE ngos (
  id INT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  user_id INT NOT NULL UNIQUE REFERENCES users(id),
  org_type VARCHAR(30) DEFAULT NULL CHECK (org_type IN ('NGO','Medical Shop','Old-Age Home','Orphanage','Charitable Hospital')),
  registration_no VARCHAR(60),
  license_image VARCHAR(255) DEFAULT NULL,
  verified SMALLINT NOT NULL DEFAULT 0
);

CREATE TABLE medicines (
  id INT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  distributor_id INT NOT NULL REFERENCES distributors(id),
  name VARCHAR(120) NOT NULL,
  category VARCHAR(60) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  mrp DECIMAL(10,2) DEFAULT NULL,
  stock INT NOT NULL DEFAULT 0,
  batch_no VARCHAR(40),
  image VARCHAR(255) DEFAULT NULL,
  expiry DATE NOT NULL,
  low_stock_notified SMALLINT NOT NULL DEFAULT 0,
  deleted SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX medicines_expiry_idx ON medicines (expiry);
CREATE INDEX medicines_category_idx ON medicines (category);

CREATE TABLE orders (
  id INT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  org_user_id INT NOT NULL REFERENCES users(id),
  distributor_id INT NOT NULL REFERENCES distributors(id),
  delivery_address TEXT NOT NULL,
  delivery_phone VARCHAR(15) DEFAULT NULL,
  latitude DECIMAL(10,8) DEFAULT NULL,
  longitude DECIMAL(11,8) DEFAULT NULL,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'Pending' CHECK (status IN ('Pending Payment','Pending','Accepted','Packed','Shipped','Out for Delivery','Delivered','Cancelled')),
  tracking_number VARCHAR(100) DEFAULT NULL,
  delivered_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_items (
  id INT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  order_id INT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  medicine_id INT NOT NULL REFERENCES medicines(id),
  qty INT NOT NULL,
  price DECIMAL(10,2) NOT NULL
);

CREATE TABLE transactions (
  id INT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  order_id INT NOT NULL UNIQUE REFERENCES orders(id),
  amount DECIMAL(10,2) NOT NULL,
  method VARCHAR(20) NOT NULL DEFAULT 'Bank Transfer' CHECK (method IN ('Cash','Bank Transfer','UPI','Card','Razorpay')),
  status VARCHAR(20) NOT NULL DEFAULT 'Pending' CHECK (status IN ('Pending','Completed','Failed','Cancelled')),
  reference VARCHAR(60) DEFAULT NULL,
  txn_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notifications (
  id INT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  user_id INT NOT NULL REFERENCES users(id),
  type VARCHAR(20) NOT NULL DEFAULT 'system' CHECK (type IN ('expiry','order','verify','system','low_stock')),
  text VARCHAR(255) NOT NULL,
  is_read SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reviews (
  id INT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  order_id INT NOT NULL REFERENCES orders(id),
  user_id INT NOT NULL REFERENCES users(id),
  rating SMALLINT NOT NULL DEFAULT 5,
  comment VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE audit_logs (
  id INT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  user_id INT NOT NULL REFERENCES users(id),
  action VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------- seed data ----------
-- password for all seeded accounts: admin123
INSERT INTO users (name, email, password, role, org_type, verified) VALUES
('System Admin', 'admin@medshare.com', '$2y$10$B233t/fNJrEkQhKXxECdSuO9kp5rPKSBCPDhNOgeL0B6V.Z0zPDzC', 'admin', NULL, 1),
('MedPlus Distributors', 'medplus@medshare.com', '$2y$10$B233t/fNJrEkQhKXxECdSuO9kp5rPKSBCPDhNOgeL0B6V.Z0zPDzC', 'distributor', NULL, 1),
('PharmaLink Co.', 'pharmalink@medshare.com', '$2y$10$B233t/fNJrEkQhKXxECdSuO9kp5rPKSBCPDhNOgeL0B6V.Z0zPDzC', 'distributor', NULL, 1),
('CareFirst Pharma', 'carefirst@medshare.com', '$2y$10$B233t/fNJrEkQhKXxECdSuO9kp5rPKSBCPDhNOgeL0B6V.Z0zPDzC', 'distributor', NULL, 0),
('Hope Orphanage', 'hope@medshare.com', '$2y$10$B233t/fNJrEkQhKXxECdSuO9kp5rPKSBCPDhNOgeL0B6V.Z0zPDzC', 'org', 'Orphanage', 1),
('City Care Hospital', 'citycare@medshare.com', '$2y$10$B233t/fNJrEkQhKXxECdSuO9kp5rPKSBCPDhNOgeL0B6V.Z0zPDzC', 'org', 'Charitable Hospital', 1),
('Elder Care Home', 'eldercare@medshare.com', '$2y$10$B233t/fNJrEkQhKXxECdSuO9kp5rPKSBCPDhNOgeL0B6V.Z0zPDzC', 'org', 'Old-Age Home', 0);

INSERT INTO distributors (user_id, license_no, verified) VALUES
(2, 'DL-MP-2024-001', 1),
(3, 'DL-PL-2024-002', 1),
(4, 'DL-CF-2026-003', 0);

INSERT INTO ngos (user_id, registration_no, verified) VALUES
(5, 'NGO-HOPE-2023', 1),
(6, 'CCH-2019-REG', 1),
(7, 'ECH-2026-REG', 0);

INSERT INTO medicines (distributor_id, name, category, price, mrp, stock, batch_no, image, expiry) VALUES
(1, 'Paracetamol 500mg', 'Pain Relief', 2.50, 7.50, 1200, 'PAR-2612', 'uploads/medicines/paracetamol.svg', '2026-12-15'),
(1, 'Amoxicillin 250mg', 'Antibiotic', 4.75, 14.25, 600, 'AMX-2611', 'uploads/medicines/amoxicillin.svg', '2026-11-02'),
(2, 'Cetirizine 10mg', 'Allergy', 1.25, 3.75, 800, 'CET-2701', 'uploads/medicines/cetirizine.svg', '2027-01-20'),
(2, 'Metformin 500mg', 'Diabetes', 3.10, 9.30, 450, 'MET-2610', 'uploads/medicines/metformin.svg', '2026-10-30'),
(1, 'Vitamin D3 60K IU', 'Supplements', 2.00, 6.00, 300, 'VIT-2703', 'uploads/medicines/vitamin-d3.svg', '2027-03-11'),
(3, 'ORS Sachets (20x21.5g)', 'Gastro', 1.90, 5.70, 950, 'ORS-2611', 'uploads/medicines/ors.svg', '2026-11-28'),
(3, 'Amoxicillin 250mg', 'Antibiotic', 4.50, 13.50, 210, 'AMX-2609', 'uploads/medicines/amoxicillin-2.svg', '2026-09-25'),
(2, 'Ibuprofen 400mg', 'Pain Relief', 2.20, 6.60, 0, 'IBU-2612', 'uploads/medicines/ibuprofen.svg', '2026-12-05');

INSERT INTO orders (id, org_user_id, distributor_id, delivery_address, latitude, longitude, total, status, created_at) VALUES
(101, 5, 1, 'Hope Orphanage, Warangal, Telangana', 17.9736, 79.5962, 500.00, 'Delivered', '2026-07-25 10:00:00'),
(102, 6, 1, 'City Care Hospital, Hyderabad, Telangana', 17.4065, 78.4772, 475.00, 'Shipped', '2026-07-28 11:30:00'),
(103, 5, 3, 'Hope Orphanage, Warangal, Telangana', 17.9736, 79.5962, 285.00, 'Pending', '2026-07-30 09:15:00');
SELECT setval(pg_get_serial_sequence('orders','id'), (SELECT MAX(id) FROM orders));

INSERT INTO order_items (order_id, medicine_id, qty, price) VALUES
(101, 1, 200, 2.50),
(102, 2, 100, 4.75),
(103, 6, 150, 1.90);

INSERT INTO transactions (order_id, amount, method, status) VALUES
(101, 500.00, 'Bank Transfer', 'Completed'),
(102, 475.00, 'Bank Transfer', 'Completed'),
(103, 285.00, 'Bank Transfer', 'Pending');

INSERT INTO notifications (user_id, type, text, is_read, created_at) VALUES
(2, 'expiry', 'Amoxicillin 250mg expires in 2 months (CareFirst Pharma)', 0, '2026-07-31 08:00:00'),
(5, 'order', 'Order #103 placed successfully', 0, '2026-07-30 09:15:00'),
(1, 'verify', 'New distributor "CareFirst Pharma" awaiting verification', 0, '2026-07-29 14:00:00');

INSERT INTO reviews (order_id, user_id, rating, comment) VALUES
(101, 5, 5, 'Quality medicines, delivered before expiry. Great value.');

INSERT INTO audit_logs (user_id, action) VALUES
(1, 'Verified distributor: CareFirst Pharma'),
(2, 'Added medicine: Vitamin D3 60K IU'),
(5, 'Placed order #103');
````

## Deployment & Container

### `Dockerfile`

> PHP 8.2 Apache image with PostgreSQL extensions, opcache and security configuration.  
> Lines: 31 &middot; Size: 1.1 KB

````dockerfile
FROM php:8.2-apache

# PostgreSQL PDO driver + opcache. curl, mbstring, fileinfo and openssl are
# already compiled into the official PHP image.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Fail the build if a required extension is missing.
RUN php -m | grep -qi '^pdo_pgsql$' \
    && php -m | grep -qi '^pgsql$' \
    && php -m | grep -qi '^curl$' \
    && php -m | grep -qi '^mbstring$' \
    && php -m | grep -qi '^fileinfo$' \
    && php -m | grep -qi '^openssl$'

COPY docker/php.ini /usr/local/etc/php/conf.d/medishare.ini
COPY docker/apache-security.conf /etc/apache2/conf-enabled/zz-medishare.conf
COPY docker/entrypoint.sh /usr/local/bin/medishare-entrypoint
RUN chmod +x /usr/local/bin/medishare-entrypoint

WORKDIR /var/www/html
COPY . /var/www/html

RUN mkdir -p uploads/licenses uploads/medicines logs \
    && chown -R www-data:www-data uploads logs

EXPOSE 80
ENTRYPOINT ["medishare-entrypoint"]
````

### `docker/apache-security.conf`

> Directory denials, index protection and HTTP security headers.  
> Lines: 24 &middot; Size: 0.6 KB

````apache
ServerName localhost
ServerTokens Prod
ServerSignature Off

<Directory /var/www/html>
    Options -Indexes
    AllowOverride All
    Require all granted
</Directory>

# Never serve application internals.
<DirectoryMatch "^/var/www/html/(config|logs|reference|scripts|docker)">
    Require all denied
</DirectoryMatch>

<FilesMatch "(^\.|\.(env|sql|log|ini|sh|md)$)">
    Require all denied
</FilesMatch>

<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
````

### `docker/php.ini`

> Production PHP, upload, session and opcache settings.  
> Lines: 20 &middot; Size: 0.4 KB

````ini
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /dev/stderr

memory_limit = 128M
upload_max_filesize = 4M
post_max_size = 8M
max_execution_time = 60
max_input_time = 60

date.timezone = UTC
session.cookie_httponly = 1
session.cookie_samesite = Lax
session.use_strict_mode = 1

opcache.enable = 1
opcache.memory_consumption = 64
opcache.validate_timestamps = 0
````

### `docker/entrypoint.sh`

> Adapts Apache to the Render PORT and starts the server.  
> Lines: 15 &middot; Size: 0.6 KB

````bash
#!/bin/sh
set -eu

PORT="${PORT:-80}"

# Render supplies PORT; make Apache listen on it.
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Writable runtime folders (container filesystem is ephemeral; uploads are
# stored in Supabase Storage when the service role key is configured).
mkdir -p /var/www/html/uploads/licenses /var/www/html/uploads/medicines /var/www/html/logs
chown -R www-data:www-data /var/www/html/uploads /var/www/html/logs 2>/dev/null || true

exec apache2-foreground
````

### `.dockerignore`

> Files excluded from the Docker build context.  
> Lines: 26 &middot; Size: 0.3 KB

````text
.git
.gitignore
.env
.env.*
config/secrets.php

logs/*
uploads/licenses/*
uploads/medicines/*
database.sql

reference/
reference.pdf
MediShare_Project_Report.pdf
PROJECT_REPORT.md
CHANGELOG.md
README.md

*.log
desktop.ini
Thumbs.db
.DS_Store
.vscode/
.idea/
node_modules/
vendor/
````

## Utility Scripts

### `scripts/migrate-uploads.php`

> One-time migration of local uploads to Supabase Storage.  
> Lines: 89 &middot; Size: 2.7 KB

````php
<?php
/* One-time migration: copy existing local uploads to Supabase Storage and
   create the five production buckets. Run from the project root:
       php scripts/migrate-uploads.php
   Requires SUPABASE_URL and SUPABASE_SERVICE_ROLE_KEY in the environment (or
   config/secrets.php). Local files are never deleted. */

if (PHP_SAPI !== 'cli') {
  http_response_code(403);
  exit("CLI only\n");
}

require dirname(__DIR__) . '/config/db.php';

if (!storage_enabled()) {
  fwrite(STDERR, "Set SUPABASE_URL and SUPABASE_SERVICE_ROLE_KEY first (env or config/secrets.php).\n");
  exit(1);
}

$root = dirname(__DIR__);
$buckets = [
  STORAGE_BUCKET_MEDICINES => true,
  STORAGE_BUCKET_DISTRIBUTOR_DOCS => false,
  STORAGE_BUCKET_NGO_DOCS => false,
  STORAGE_BUCKET_INVOICES => false,
  STORAGE_BUCKET_QR => false,
];

echo "== Buckets ==\n";
foreach ($buckets as $bucket => $public) {
  [$ok, $error] = storage_ensure_bucket($bucket, $public);
  echo ($ok ? '[ok]   ' : '[fail] ') . $bucket . ($ok ? '' : " - $error") . "\n";
  if (!$ok) exit(1);
}

/* Which private bucket owns each license file? */
$roleByFile = [];
$pdo = db();
foreach ([
  'distributor' => 'SELECT license_image FROM distributors WHERE license_image IS NOT NULL',
  'org' => 'SELECT license_image FROM ngos WHERE license_image IS NOT NULL',
] as $role => $sql) {
  foreach ($pdo->query($sql) as $row) {
    if (!empty($row['license_image'])) {
      $roleByFile[basename($row['license_image'])] = $role;
    }
  }
}

$uploaded = 0;
$failed = 0;

function migrate_file(string $bucket, string $object, string $file): void {
  global $uploaded, $failed;
  $mime = mime_content_type($file) ?: 'application/octet-stream';
  [$ok, $error] = storage_upload_object($bucket, $object, $file, $mime);
  if ($ok) {
    $uploaded++;
    echo "[ok]   $bucket/$object\n";
  } else {
    $failed++;
    echo "[fail] $bucket/$object - $error\n";
  }
}

echo "\n== Medicine images ==\n";
foreach (glob($root . '/uploads/medicines/*') ?: [] as $file) {
  $name = basename($file);
  if ($name[0] === '.') continue;
  migrate_file(STORAGE_BUCKET_MEDICINES, 'medicines/' . $name, $file);
}

echo "\n== License documents ==\n";
foreach (glob($root . '/uploads/licenses/*') ?: [] as $file) {
  $name = basename($file);
  if ($name[0] === '.') continue;
  $role = $roleByFile[$name] ?? 'distributor';
  if (!isset($roleByFile[$name])) {
    echo "[warn] $name is not referenced by the database; using distributor-documents\n";
  }
  $bucket = $role === 'org' ? STORAGE_BUCKET_NGO_DOCS : STORAGE_BUCKET_DISTRIBUTOR_DOCS;
  migrate_file($bucket, 'licenses/' . $name, $file);
}

echo "\n== Summary ==\n";
echo "uploaded: $uploaded\n";
echo "failed:   $failed\n";
echo "Local files were kept. Database paths were not changed.\n";
exit($failed === 0 ? 0 : 1);
````

---

*End of source listing.*
