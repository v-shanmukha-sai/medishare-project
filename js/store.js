/* MediShare storefront pages: index / store / medicine / distributor / cart. */
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
          const res = await apiPost('api/orders.php', { items, method, delivery_address: address, latitude: geo?.lat ?? null, longitude: geo?.lng ?? null });
          if (!res.ok) {
            btn.disabled = false;
            $('#orderMsg').innerHTML = `<div class="alert alert-danger small mb-0">${res.error}</div>`;
            toast(res.error, 'danger');
            return;
          }

          cookieSet(DELIVERY_COOKIE, address);
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
