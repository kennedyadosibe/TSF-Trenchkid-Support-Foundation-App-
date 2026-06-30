/* ===== TSF DONATE JS ===== */

let selectedMethod = '';
let selectedNetwork = '';
let donateCsrfToken = '';

function selectPayment(method) {
  selectedMethod = method;
  window.selectedPaymentMethod = method;
  document.querySelectorAll('.pay-option').forEach(o => o.classList.remove('selected'));
  document.querySelector(`.pay-option[data-method="${method}"]`)?.classList.add('selected');

  const mobileMoneyFields = document.getElementById('mobile-money-fields');
  const cardFields = document.getElementById('card-fields');
  if (mobileMoneyFields) mobileMoneyFields.style.display = method === 'mobile_money' ? 'block' : 'none';
  if (cardFields) cardFields.style.display = method === 'card' ? 'block' : 'none';

  if (method !== 'mobile_money') {
    selectedNetwork = '';
    window.selectedMobileNetwork = '';
    document.querySelectorAll('.network-option').forEach(o => o.classList.remove('selected'));
  }

  const label = { mobile_money: 'Mobile Money', card: 'Visa / Mastercard' }[method];
  const indicator = document.getElementById('method-indicator');
  if (indicator) indicator.textContent = `Selected: ${label}`;
}

function selectMobileNetwork(network) {
  selectedNetwork = network;
  window.selectedMobileNetwork = network;
  document.querySelectorAll('.network-option').forEach(o => o.classList.remove('selected'));
  document.querySelector(`.network-option[data-network="${network}"]`)?.classList.add('selected');
  const err = document.getElementById('d-network-err');
  if (err) err.classList.remove('show');
}

async function fetchDonateToken() {
  const res = await fetch('BACKEND/csrf_token.php?form=donate', { credentials: 'same-origin' });
  const data = await res.json();
  if (!data.success) throw new Error(data.message || 'Unable to prepare secure donation form.');
  donateCsrfToken = data.csrf_token;
  return donateCsrfToken;
}

async function submitDonation(e) {
  e.preventDefault();
  if (!selectedMethod) {
    showDonateAlert('error', 'Please select a payment method.');
    return;
  }
  if (!validateDonateForm()) return;

  const fname = document.getElementById('d-fname').value.trim();
  const lname = document.getElementById('d-lname').value.trim();
  const email = document.getElementById('d-email').value.trim();
  const phone = document.getElementById('d-phone') ? document.getElementById('d-phone').value.trim() : '';
  const amount = document.getElementById('d-amount').value;
  const gender = document.getElementById('d-gender').value;

  const btn = document.getElementById('donate-btn');
  btn.disabled = true;
  btn.textContent = 'Preparing secure checkout...';

  try {
    if (!donateCsrfToken) await fetchDonateToken();
    const res = await fetch('BACKEND/initialize_payment.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        csrf_token: donateCsrfToken,
        first_name: fname,
        last_name: lname,
        email,
        phone,
        amount,
        gender,
        payment_method: selectedMethod,
        mobile_network: selectedNetwork
      })
    });
    const data = await res.json();
    if (!data.success) {
      throw new Error(data.message || 'Unable to start payment.');
    }

    if (!data.authorization_url) {
      throw new Error(data.message || 'Unable to start payment.');
    }
    window.location.href = data.authorization_url;
  } catch (err) {
    showDonateAlert('error', err.message || 'Unable to start payment. Please try again.');
    donateCsrfToken = '';
    try { await fetchDonateToken(); } catch (_) {}
    btn.disabled = false;
    btn.textContent = 'Complete Donation';
  }
}

function showDonateAlert(type, msg) {
  const el = document.getElementById('donate-alert');
  if (!el) return;
  el.className = `alert alert-${type} show`;
  el.textContent = msg;
  setTimeout(() => el.classList.remove('show'), 4000);
}

async function updateDonorDisplay() {
  try {
    const res = await fetch('BACKEND/donation_summary.php', { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.success) return;

    const totalEl = document.getElementById('total-funds');
    if (totalEl) totalEl.textContent = `GHS ${Number(data.summary.total_raised || 0).toFixed(2)}`;
    const countEl = document.getElementById('donor-count');
    if (countEl) countEl.textContent = data.summary.total_donors || 0;

    const lastEl = document.getElementById('last-donation');
    if (lastEl && data.last_donation) {
      const d = data.last_donation;
      const date = new Date(d.created_at).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' });
      lastEl.textContent = `${d.first_name} ${d.last_name.charAt(0)}. - GHS ${Number(d.amount).toFixed(2)} (${date})`;
    }
  } catch (_) {
    // Keep the page usable if the backend is not configured yet.
  }
}

async function verifyReturnedPayment() {
  const params = new URLSearchParams(window.location.search);
  const reference = params.get('reference') || params.get('trxref');
  if (!reference) return;

  const btn = document.getElementById('donate-btn');
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Verifying payment...';
  }
  showDonateAlert('success', 'Verifying your payment. Please wait...');

  try {
    const res = await fetch(`BACKEND/verify_payment.php?reference=${encodeURIComponent(reference)}`, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Payment verification failed.');

    document.getElementById('donate-form-wrap').style.display = 'none';
    document.getElementById('donate-success').style.display = 'block';
    document.getElementById('success-name').textContent = data.first_name || 'Friend';
    document.getElementById('success-amount').textContent = `GHS ${Number(data.amount || 0).toFixed(2)}`;
    updateDonorDisplay();
    window.history.replaceState({}, document.title, window.location.pathname);
  } catch (err) {
    showDonateAlert('error', err.message || 'Payment verification failed. Please contact TSF support.');
    if (btn) {
      btn.disabled = false;
      btn.textContent = 'Complete Donation';
    }
  }
}

document.addEventListener('DOMContentLoaded', function () {
  updateDonorDisplay();
  fetchDonateToken().catch(err => showDonateAlert('error', err.message));
  verifyReturnedPayment();

  const form = document.getElementById('donate-form');
  if (form) form.addEventListener('submit', submitDonation);

  document.querySelectorAll('.amount-preset').forEach(btn => {
    btn.addEventListener('click', function () {
      document.getElementById('d-amount').value = this.dataset.amount;
      document.querySelectorAll('.amount-preset').forEach(b => b.classList.remove('selected'));
      this.classList.add('selected');
    });
  });

  document.querySelectorAll('.network-option').forEach(btn => {
    btn.addEventListener('click', function () {
      selectMobileNetwork(this.dataset.network);
    });
  });
});
