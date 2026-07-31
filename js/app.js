/* MedShare - client-side demo logic. PHP/MySQL will replace the mock data layer. */
(function () {
  const isDashboard = location.pathname.endsWith('dashboard.html');
  const qs = new URLSearchParams(location.search);
  let role = localStorage.getItem('medshare_role') || qs.get('role') || 'admin';
  const user = USERS[role];
  if (isDashboard && !user) { location.href = 'login.html'; return; }

  /* ---------- login / register (non-dashboard pages) ---------- */
  if (!isDashboard) {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const alertBox = document.getElementById('alertBox');
    const showAlert = (msg, type) => alertBox.innerHTML = `<div class="alert alert-${type} py-2 small">${msg}</div>`;

    if (loginForm) {
      document.querySelectorAll('[data-role]').forEach(b => b.addEventListener('click', () => {
        localStorage.setItem('medshare_role', b.dataset.role);
        location.href = 'dashboard.html';
      }));
      loginForm.addEventListener('submit', e => {
        e.preventDefault();
        localStorage.setItem('medshare_role', 'admin');
        location.href = 'dashboard.html';
      });
    }
    if (registerForm) {
      document.querySelectorAll('input[name="role"]').forEach(r => r.addEventListener('change', () => {
        document.getElementById('orgType').classList.toggle('d-none', r.value === 'Distributor');
      }));
      registerForm.addEventListener('submit', e => {
        e.preventDefault();
        if (!registerForm.checkValidity()) { registerForm.classList.add('was-validated'); return; }
        showAlert('Registration submitted! Admin verification required before you can log in.', 'success');
        registerForm.reset();
      });
    }
    return;
  }

  const pageTitle = document.getElementById('pageTitle');
  const pageContent = document.getElementById('pageContent');

  document.getElementById('userName').textContent = user.name;
  document.getElementById('userNameM').textContent = user.name;
  document.getElementById('userRole').textContent = user.role;
  document.getElementById('notifCount').textContent = NOTIFICATIONS.length;

  const PAGES = ['overview', 'medicines', 'orders', 'notifications', 'reports', 'audit', 'verify'];
  const rolePages = {
    Admin: PAGES,
    Distributor: ['overview', 'medicines', 'orders', 'notifications', 'reports'],
    NGO: ['overview', 'medicines', 'orders', 'notifications']
  };
  const myPages = rolePages[user.role] || PAGES;

  document.querySelectorAll('[data-page]').forEach(el => {
    if (!myPages.includes(el.dataset.page)) el.classList.add('d-none');
    el.addEventListener('click', () => show(el.dataset.page));
  });

  function show(page) {
    pageTitle.textContent = page[0].toUpperCase() + page.slice(1);
    document.querySelectorAll('[data-page]').forEach(el => el.classList.toggle('active', el.dataset.page === page));
    pageContent.innerHTML = RENDER[page]();
    if (page === 'medicines') renderMedTable();
    if (page === 'reports') drawChart();
  }

  /* ---------- pages ---------- */
  const RENDER = {
    overview() {
      const meds = MEDICINES.length, lowStock = MEDICINES.filter(m => m.stock > 0 && m.stock < 250).length;
      const expiring = MEDICINES.filter(m => m.shelf <= '3 months').length;
      const revenue = ORDERS.filter(o => o.status !== 'Pending').reduce((s, o) => s + o.total, 0);
      return `
      <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card stat-card ok"><div class="card-body"><div class="text-secondary small">Medicines Listed</div><div class="h3 mb-0">${meds}</div></div></div></div>
        <div class="col-md-3"><div class="card stat-card warn"><div class="card-body"><div class="text-secondary small">Low Stock</div><div class="h3 mb-0">${lowStock}</div></div></div></div>
        <div class="col-md-3"><div class="card stat-card danger"><div class="card-body"><div class="text-secondary small">Expiring ≤3 Months</div><div class="h3 mb-0">${expiring}</div></div></div></div>
        <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-secondary small">Revenue (Completed)</div><div class="h3 mb-0">$${revenue.toFixed(2)}</div></div></div></div>
      </div>
      <div class="card"><div class="card-header"><i class="bi bi-bell"></i> Recent Notifications</div>
        <ul class="list-group list-group-flush">${NOTIFICATIONS.slice(0, 3).map(n => `<li class="list-group-item small"><span class="badge ${n.type === 'expiry' ? 'bg-danger' : n.type === 'order' ? 'bg-primary' : 'bg-warning'} me-2">${n.type}</span>${n.text} <span class="text-secondary float-end">${n.date}</span></li>`).join('')}</ul>
      </div>`;
    },

    medicines() {
      return `
      <div class="card"><div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-4"><input class="form-control" id="medSearch" placeholder="Search medicine name..."></div>
          <div class="col-md-3"><select class="form-select" id="medCat"><option value="">All Categories</option>${[...new Set(MEDICINES.map(m => m.cat))].map(c => `<option>${c}</option>`).join('')}</select></div>
          <div class="col-md-3"><select class="form-select" id="medShelf"><option value="">Any Shelf Life</option><option>&lt;= 3 months</option><option>4-6 months</option></select></div>
          <div class="col-md-2"><button class="btn btn-primary w-100" id="addMed" ${user.role === 'Distributor' ? '' : 'disabled'}>+ Add Medicine</button></div>
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle" id="medTable">
          <thead class="table-light"><tr><th>Medicine</th><th>Category</th><th>Distributor</th><th>Price</th><th>Stock</th><th>Expiry</th><th>Shelf Life</th><th></th></tr></thead>
          <tbody></tbody>
        </table></div>
      </div></div>`;
    },

    orders() {
      const rows = ORDERS.map(o => `
        <tr>
          <td>#${o.id}</td><td>${o.med}</td><td>${o.qty}</td><td>$${o.total.toFixed(2)}</td>
          <td>${o.buyer}</td><td>${o.date}</td>
          <td><span class="badge ${o.status === 'Delivered' ? 'bg-success' : o.status === 'Shipped' ? 'bg-info' : 'bg-warning text-dark'}">${o.status}</span></td>
          <td><button class="btn btn-sm btn-outline-primary" onclick="window.print()"><i class="bi bi-file-earmark-text"></i> Invoice</button></td>
        </tr>`).join('');
      return `<div class="card"><div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Order</th><th>Medicine</th><th>Qty</th><th>Total</th><th>Buyer</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>${rows}</tbody></table></div></div></div>`;
    },

    notifications() {
      return `<div class="card"><ul class="list-group list-group-flush">${NOTIFICATIONS.map(n => `
        <li class="list-group-item"><span class="badge ${n.type === 'expiry' ? 'bg-danger' : n.type === 'order' ? 'bg-primary' : 'bg-warning'} me-2">${n.type}</span>${n.text}<span class="text-secondary float-end small">${n.date}</span></li>`).join('')}</ul></div>`;
    },

    reports() {
      const byCat = {};
      MEDICINES.forEach(m => byCat[m.cat] = (byCat[m.cat] || 0) + m.stock);
      const cats = Object.keys(byCat);
      return `
      <div class="row g-3">
        <div class="col-md-6"><div class="card"><div class="card-body"><h6>Stock by Category</h6><canvas id="stockChart" height="180"></canvas></div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-body"><h6>Wastage Risk (expiring ≤3 months)</h6>
          <table class="table table-sm mt-2">${MEDICINES.filter(m => m.shelf <= '3 months').map(m => `<tr><td>${m.name}</td><td>${m.dist}</td><td class="text-danger fw-bold">${m.expiry}</td></tr>`).join('')}</table>
        </div></div></div>
      </div>`;
    },

    audit() {
      return `<div class="card"><div class="card-body"><table class="table table-sm"><thead class="table-light"><tr><th>User</th><th>Action</th><th>Date</th></tr></thead><tbody>${AUDIT_LOGS.map(l => `<tr><td>${l.user}</td><td>${l.action}</td><td>${l.date}</td></tr>`).join('')}</tbody></table></div></div>`;
    },

    verify() {
      return `<div class="card"><div class="card-body">
        <table class="table align-middle"><thead class="table-light"><tr><th>Name</th><th>Type</th><th>Document</th><th>Submitted</th><th></th></tr></thead>
        <tbody>${PENDING_VERIFICATIONS.map(v => `<tr><td>${v.name}</td><td>${v.type}</td><td><i class="bi bi-file-earmark-pdf text-danger"></i> ${v.docs}</td><td>${v.date}</td><td><button class="btn btn-sm btn-success me-1" onclick="approve('${v.name}')">Approve</button><button class="btn btn-sm btn-outline-danger" onclick="reject('${v.name}')">Reject</button></td></tr>`).join('')}</tbody></table>
      </div></div>`;
    }
  };

  /* ---------- events (delegated) ---------- */
  pageContent.addEventListener('click', e => {
    const addBtn = e.target.closest('#addMed');
    if (addBtn) {
      alert('Medicine add form goes here (PHP/MySQL will persist this).');
    }
    const orderBtn = e.target.closest('.btn-order');
    if (orderBtn) {
      const m = MEDICINES.find(x => x.id === +orderBtn.dataset.id);
      if (m.stock === 0) { alert('Out of stock!'); return; }
      document.getElementById('orderModalBody').innerHTML = `
        <h6>${m.name}</h6>
        <p class="small text-secondary mb-3">${m.dist} &middot; $${m.price.toFixed(2)}/unit &middot; stock: ${m.stock} &middot; expires ${m.expiry}</p>
        <label class="form-label">Quantity</label>
        <input type="number" class="form-control mb-2" id="orderQty" min="1" max="${m.stock}" value="1">
        <div class="small text-secondary">Total: $<span id="orderTotal">${m.price.toFixed(2)}</span></div>`;
      const qty = document.getElementById('orderQty');
      qty.oninput = () => document.getElementById('orderTotal').textContent = (m.price * Math.max(1, qty.value)).toFixed(2);
      new bootstrap.Modal(document.getElementById('orderModal')).show();
    }
  });

  pageContent.addEventListener('change', renderMedTable);

  function renderMedTable() {
    const q = (document.getElementById('medSearch')?.value || '').toLowerCase();
    const cat = document.getElementById('medCat')?.value || '';
    const shelf = document.getElementById('medShelf')?.value || '';
    const tbody = document.querySelector('#medTable tbody');
    if (!tbody) return;
    tbody.innerHTML = MEDICINES.filter(m =>
      (!q || m.name.toLowerCase().includes(q)) &&
      (!cat || m.cat === cat) &&
      (!shelf || (shelf.includes('3') ? m.shelf <= '3 months' : m.shelf >= '4 months'))
    ).map(m => `
      <tr>
        <td class="fw-bold">${m.name}</td><td>${m.cat}</td><td>${m.dist}</td>
        <td>$${m.price.toFixed(2)}</td>
        <td><span class="badge ${m.stock === 0 ? 'bg-danger' : m.stock < 250 ? 'bg-warning text-dark' : 'bg-success'}">${m.stock}</span></td>
        <td>${m.expiry}</td>
        <td>${m.shelf}</td>
        <td>${user.role === 'NGO' ? `<button class="btn btn-sm btn-primary btn-order" data-id="${m.id}" ${m.stock === 0 ? 'disabled' : ''}>Order</button>` : ''}</td>
      </tr>`).join('');
  }

  /* chart */
  function drawChart() {
    const canvas = document.getElementById('stockChart');
    if (!canvas) return;
    const byCat = {};
    MEDICINES.forEach(m => byCat[m.cat] = (byCat[m.cat] || 0) + m.stock);
    const cats = Object.keys(byCat), vals = cats.map(c => byCat[c]);
    const ctx = canvas.getContext('2d');
    const w = canvas.width = canvas.clientWidth || 500, h = canvas.height = 200;
    const max = Math.max(...vals);
    ctx.fillStyle = '#0d6efd';
    const bw = w / cats.length * 0.6;
    cats.forEach((c, i) => {
      const bh = vals[i] / max * (h - 40);
      ctx.fillRect(i * w / cats.length + w / cats.length * 0.2, h - bh - 20, bw, bh);
      ctx.fillStyle = '#6c757d';
      ctx.font = '11px sans-serif';
      ctx.fillText(c, i * w / cats.length + w / cats.length * 0.2, h - 5);
      ctx.fillText(vals[i], i * w / cats.length + w / cats.length * 0.2 + bw / 2 - 10, h - bh - 25);
      ctx.fillStyle = '#0d6efd';
    });
  }

  /* approve/reject (admin) */
  window.approve = name => { alert(`${name} approved. In production: DB update + notification.`); location.reload(); };
  window.reject = name => { alert(`${name} rejected.`); location.reload(); };

  /* init */
  show('overview');
})();
