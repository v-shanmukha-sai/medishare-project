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
