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
  let razorpayLoader = null;
  const loadRazorpay = () => razorpayLoader || (razorpayLoader = loadScript('https://checkout.razorpay.com/v1/checkout.js')
    .catch(err => { razorpayLoader = null; throw err; }));
const cachedGet = async (url, ttl = 60000) => {
  const key = 'medshare_cache:' + url;
  try { const c = JSON.parse(sessionStorage.getItem(key) || 'null'); if (c && Date.now() - c.at < ttl) return c.data; } catch {}
  const data = await apiGet(url);
  try { sessionStorage.setItem(key, JSON.stringify({ at: Date.now(), data })); } catch {}
  return data;
};

/* ---------- Razorpay checkout (Amazon-style: order first, then pay) ---------- */
  async function payWithRazorpay(orderIds, user, upi = false) {
    /* start loading the checkout while the server creates the payment order */
    const scriptPromise = loadRazorpay();
    const create = await apiPost('api/payment.php', { action: 'create', order_ids: orderIds });
    if (!create.ok) { toast(create.error, 'danger'); return false; }
    try { await scriptPromise; }
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
