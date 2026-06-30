/* ===== TSF TIME + VALIDATION ===== */

// Live time display
function updateTime() {
  const el = document.getElementById('live-datetime');
  if (!el) return;
  const now = new Date();
  const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const day = days[now.getDay()];
  const date = now.getDate();
  const month = months[now.getMonth()];
  const year = now.getFullYear();
  let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
  const ampm = h >= 12 ? 'PM' : 'AM';
  h = h % 12 || 12;
  el.textContent = `${day}, ${date} ${month} ${year} | ${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')} ${ampm}`;
}

setInterval(updateTime, 1000);
document.addEventListener('DOMContentLoaded', function () {
  setTimeout(updateTime, 100);
});

/* ===== VALIDATION HELPERS ===== */
const Validate = {
  required(val) { return val.trim().length > 0; },
  email(val) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val.trim()); },
  emailOptional(val) { return val.trim() === '' || this.email(val); },
  ghPhone(val) {
    const v = val.trim();
    return /^0[0-9]{9}$/.test(v) || /^\+233[0-9]{9}$/.test(v);
  },
  minLen(val, n) { return val.trim().length >= n; },

  setError(input, errEl, msg) {
    input.classList.remove('success'); input.classList.add('error');
    errEl.textContent = msg; errEl.classList.add('show');
  },
  setSuccess(input, errEl) {
    input.classList.remove('error'); input.classList.add('success');
    errEl.classList.remove('show');
  },
  clearField(input, errEl) {
    input.classList.remove('error', 'success');
    errEl.classList.remove('show');
  }
};

/* ===== DONATE FORM VALIDATION ===== */
function validateDonateForm() {
  const paymentMethod = window.selectedPaymentMethod || '';
  const fields = {
    fname: { el: document.getElementById('d-fname'), err: document.getElementById('d-fname-err'), rules: ['required'], msgs: ['First name is required.'] },
    lname: { el: document.getElementById('d-lname'), err: document.getElementById('d-lname-err'), rules: ['required'], msgs: ['Last name is required.'] },
    email: { el: document.getElementById('d-email'), err: document.getElementById('d-email-err'), rules: ['emailOptional'], msgs: ['Enter a valid email address or leave it blank.'] },
    amount: { el: document.getElementById('d-amount'), err: document.getElementById('d-amount-err'), rules: ['required'], msgs: ['Please enter a donation amount.'] },
    gender: { el: document.getElementById('d-gender'), err: document.getElementById('d-gender-err'), rules: ['required'], msgs: ['Please select your gender.'] },
  };
  if (paymentMethod === 'mobile_money') {
    fields.phone = { el: document.getElementById('d-phone'), err: document.getElementById('d-phone-err'), rules: ['required','ghPhone'], msgs: ['Phone number is required.', 'Enter a valid Ghana phone number (e.g. 0244000000 or +233244000000).'] };
  } else {
    const phoneEl = document.getElementById('d-phone');
    const phoneErr = document.getElementById('d-phone-err');
    if (phoneEl && phoneErr) Validate.clearField(phoneEl, phoneErr);
  }

  let valid = true;
  for (const key in fields) {
    const { el, err, rules, msgs } = fields[key];
    if (!el) continue;
    Validate.clearField(el, err);
    let fieldOk = true;
    for (let i = 0; i < rules.length; i++) {
      const rule = rules[i];
      if (!Validate[rule](el.value)) {
        Validate.setError(el, err, msgs[i]);
        fieldOk = false; valid = false;
        break;
      }
    }
    if (fieldOk) Validate.setSuccess(el, err);
  }

  // Amount positive check
  const amtEl = document.getElementById('d-amount');
  if (amtEl && parseFloat(amtEl.value) <= 0) {
    Validate.setError(amtEl, document.getElementById('d-amount-err'), 'Amount must be greater than 0.');
    valid = false;
  }

  if (paymentMethod === 'mobile_money') {
    const networkErr = document.getElementById('d-network-err');
    if (!window.selectedMobileNetwork) {
      if (networkErr) {
        networkErr.textContent = 'Please select MTN, Telecel, or AirtelTigo.';
        networkErr.classList.add('show');
      }
      valid = false;
    } else if (networkErr) {
      networkErr.classList.remove('show');
    }
  }

  return valid;
}

/* ===== CONTACT FORM VALIDATION ===== */
function validateContactForm() {
  const name = document.getElementById('c-name');
  const nameErr = document.getElementById('c-name-err');
  const email = document.getElementById('c-email');
  const emailErr = document.getElementById('c-email-err');
  const msg = document.getElementById('c-message');
  const msgErr = document.getElementById('c-message-err');
  let valid = true;

  [name, email, msg].forEach(el => el && Validate.clearField(el, document.getElementById(el.id.replace('c-','c-') + '-err')));

  if (!name || !Validate.required(name.value)) { Validate.setError(name, nameErr, 'Name is required.'); valid = false; }
  else Validate.setSuccess(name, nameErr);
  if (!email || !Validate.required(email.value)) { Validate.setError(email, emailErr, 'Email is required.'); valid = false; }
  else if (!Validate.email(email.value)) { Validate.setError(email, emailErr, 'Enter a valid email address.'); valid = false; }
  else Validate.setSuccess(email, emailErr);
  if (!msg || !Validate.minLen(msg.value, 10)) { Validate.setError(msg, msgErr, 'Message must be at least 10 characters.'); valid = false; }
  else Validate.setSuccess(msg, msgErr);

  return valid;
}
