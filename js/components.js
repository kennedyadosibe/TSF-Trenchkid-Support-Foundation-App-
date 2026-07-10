/* ===== TSF SHARED COMPONENTS ===== */

const TSF_ICONS = {
  admin: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2a2 2 0 1 1-4 0V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1A2 2 0 1 1 4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H2.8a2 2 0 1 1 0-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7A2 2 0 1 1 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3h.1a1.7 1.7 0 0 0 .9-1.6v-.2a2 2 0 1 1 4 0V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1A2 2 0 1 1 19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1a1.7 1.7 0 0 0 1.6.9h.2a2 2 0 1 1 0 4H21a1.7 1.7 0 0 0-1.6 1Z"/>',
  book: '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15Z"/>',
  laptop: '<rect x="4" y="5" width="16" height="11" rx="2"/><path d="M2 20h20"/><path d="M9 16h6"/>',
  handshake: '<path d="m11 17 2 2a2.8 2.8 0 0 0 4 0l3-3a2.8 2.8 0 0 0 0-4l-2.5-2.5"/><path d="m13 7 2-2a2.8 2.8 0 0 1 4 0l1 1"/><path d="M7 12l3.5-3.5a2.8 2.8 0 0 1 4 0L16 10"/><path d="m2 12 5 5"/><path d="m22 12-5 5"/>',
  health: '<path d="M12 21s-7-4.4-9.3-9A5.4 5.4 0 0 1 12 6a5.4 5.4 0 0 1 9.3 6C19 16.6 12 21 12 21Z"/><path d="M12 8v6"/><path d="M9 11h6"/>',
  heart: '<path d="M20.8 4.6a5.4 5.4 0 0 0-7.6 0L12 5.8l-1.2-1.2a5.4 5.4 0 0 0-7.6 7.6L12 21l8.8-8.8a5.4 5.4 0 0 0 0-7.6Z"/>',
  lightbulb: '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2Z"/>',
  target: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5" data-fill="1"/>',
  sprout: '<path d="M12 22V12"/><path d="M12 12c-3.5 0-6-2.5-6-6 3.5 0 6 2.5 6 6Z"/><path d="M12 12c3.5 0 6-2.5 6-6-3.5 0-6 2.5-6 6Z"/>',
  globe: '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20"/><path d="M12 2a15 15 0 0 0 0 20"/>',
  star: '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8-6.2-3.3L5.8 21 7 14.2 2 9.3l6.9-1L12 2Z"/>',
  unlock: '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 7.6-1.8"/>',
  lock: '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
  graduation: '<path d="m22 10-10-5-10 5 10 5 10-5Z"/><path d="M6 12v5c3.5 2 8.5 2 12 0v-5"/><path d="M22 10v6"/>',
  hands: '<path d="M7 11v7a3 3 0 0 0 3 3h2"/><path d="M17 11v7a3 3 0 0 1-3 3h-2"/><path d="M7 11 4 8a2 2 0 0 1 3-3l4 4"/><path d="m17 11 3-3a2 2 0 0 0-3-3l-4 4"/>',
  user: '<circle cx="12" cy="8" r="4"/><path d="M4 22a8 8 0 0 1 16 0"/>',
  child: '<circle cx="12" cy="7" r="4"/><path d="M5 22v-3a7 7 0 0 1 14 0v3"/><path d="M8 14l-3 3"/><path d="m16 14 3 3"/>',
  check: '<circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/>',
  school: '<path d="M3 22h18"/><path d="M6 22V10l6-4 6 4v12"/><path d="M10 22v-6h4v6"/><path d="M9 12h.01"/><path d="M15 12h.01"/>',
  building: '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h.01"/><path d="M12 7h.01"/><path d="M16 7h.01"/><path d="M8 11h.01"/><path d="M12 11h.01"/><path d="M16 11h.01"/><path d="M9 21v-5h6v5"/>',
  mail: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
  mapPin: '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
  phone: '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1A19.5 19.5 0 0 1 5.2 12 19.8 19.8 0 0 1 2.1 3.4 2 2 0 0 1 4.1 1.2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8 9.2a16 16 0 0 0 6.8 6.8l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z"/>',
  clock: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
  message: '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/>',
  send: '<path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="M22 2 11 13"/>',
  party: '<path d="m4 20 4-14 10 10-14 4Z"/><path d="m14 4 1 2"/><path d="m18 2 1 2"/><path d="m20 8 2 1"/><path d="m10 14 4-4"/>',
  camera: '<path d="M14.5 4 16 7h3a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3v-7a3 3 0 0 1 3-3h3l1.5-3h5Z"/><circle cx="12" cy="13" r="4"/>',
  briefcase: '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 12h18"/>',
  facebook: '<path d="M14 8h3V4h-3a5 5 0 0 0-5 5v3H6v4h3v6h4v-6h3l1-4h-4V9a1 1 0 0 1 1-1Z"/>',
  twitter: '<path d="m4 4 16 16"/><path d="M20 4 4 20"/>',
  instagram: '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
  linkedin: '<path d="M6 9v12"/><path d="M6 5v.01"/><path d="M11 21v-7a4 4 0 0 1 8 0v7"/><path d="M11 9v12"/>'
};

function tsfIcon(name) {
  return `<span class="tsf-icon" data-icon="${name}" aria-hidden="true"></span>`;
}

function hydrateTsfIcons(root = document) {
  root.querySelectorAll('.tsf-icon[data-icon]').forEach(el => {
    const icon = TSF_ICONS[el.dataset.icon];
    if (!icon || el.dataset.hydrated === 'true') return;
    el.innerHTML = `<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">${icon}</svg>`;
    el.dataset.hydrated = 'true';
  });
}

function getActivePage() {
  const path = window.location.pathname;
  if (path.includes('about')) return 'about';
  if (path.includes('team')) return 'team';
  if (path.includes('impact')) return 'impact';
  if (path.includes('gallery')) return 'gallery';
  if (path.includes('donate')) return 'donate';
  if (path.includes('contact')) return 'contact';
  if (path.includes('news')) return 'news';
  return 'home';
}

function isActive(page) {
  return getActivePage() === page ? 'active' : '';
}

function renderHeader() {
  const header = document.createElement('header');
  header.innerHTML = `
    <div class="header-top">
      <a href="index.html" class="header-brand">
        <img src="images/tsf-logo.png" alt="TSF Logo">
        <div class="brand-text">
          <span class="brand-name" data-setting="site_name">Trenchkid Support Foundation</span>
          <span class="brand-tagline" data-setting="site_tagline">Empowering Children, Building Futures</span>
        </div>
      </a>
      <div class="header-actions">
        <div class="header-time" id="live-datetime">Loading...</div>
        <a href="admin/login.php" class="admin-portal-link icon-only" title="Admin Portal" aria-label="Admin Portal">${tsfIcon('admin')}</a>
      </div>
    </div>
    <nav>
      <ul class="nav-links" id="nav-links">
        <li><a href="index.html" class="${isActive('home')}">Home</a></li>
        <li><a href="about.html" class="${isActive('about')}">About</a></li>
        <li><a href="team.html" class="${isActive('team')}">Our Team</a></li>
        <li><a href="impact.html" class="${isActive('impact')}">Impact</a></li>
        <li><a href="gallery.html" class="${isActive('gallery')}">Gallery</a></li>
        <li><a href="news.html" class="${isActive('news')}">News</a></li>
        <li><a href="contact.html" class="${isActive('contact')}">Contact</a></li>
        <li><a href="donate.html" class="nav-donate-btn ${isActive('donate')}">Donate</a></li>
      </ul>
      <button type="button" class="hamburger" id="hamburger" aria-label="Toggle menu">
        <span></span><span></span><span></span>
      </button>
    </nav>
  `;
  document.body.insertBefore(header, document.body.firstChild);
  hydrateTsfIcons(header);

  document.getElementById('hamburger').addEventListener('click', function () {
    document.getElementById('nav-links').classList.toggle('open');
    this.classList.toggle('active');
  });
}

function renderFooter() {
  const footer = document.createElement('footer');
  footer.innerHTML = `
    <div class="footer-grid">
      <div class="footer-brand footer-col">
        <img src="images/tsf-logo.png" alt="TSF Logo">
        <p class="footer-motto">"<span data-setting="site_tagline">Empowering Children, Building Futures</span>"</p>
        <p data-setting="mission_statement">TSF transforms lives through education, digital skills, and mentorship - giving underprivileged children not just hope, but a future.</p>
      </div>
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="index.html">Home</a></li>
          <li><a href="about.html">About TSF</a></li>
          <li><a href="team.html">Our Team</a></li>
          <li><a href="impact.html">Our Impact</a></li>
          <li><a href="gallery.html">Gallery</a></li>
          <li><a href="news.html">News & Articles</a></li>
          <li><a href="donate.html">Donate Now</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Our Work</h4>
        <ul>
          <li><a href="impact.html">Children Supported</a></li>
          <li><a href="impact.html">Projects Completed</a></li>
          <li><a href="impact.html">Testimonials</a></li>
          <li><a href="donate.html">Where Money Goes</a></li>
          <li><a href="contact.html">Volunteer</a></li>
          <li><a href="contact.html">Partner With Us</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <div class="footer-contact-item"><span class="icon">${tsfIcon('mapPin')}</span><span data-setting="contact_address">Bolga, Upper East Region, Ghana</span></div>
        <div class="footer-contact-item"><span class="icon">${tsfIcon('phone')}</span><span data-setting="contact_phone">+233 XX XXX XXXX</span></div>
        <div class="footer-contact-item"><span class="icon">${tsfIcon('mail')}</span><span data-setting="contact_email">info@tsfghana.org</span></div>
        <div class="footer-contact-item"><span class="icon">${tsfIcon('globe')}</span><span>www.tsfghana.org</span></div>
      </div>
    </div>
    <div class="footer-bottom" style="padding-top:1.2rem">
      <p class="footer-copy">&copy; <span id="copy-year"></span> <span data-setting="site_name">Trenchkid Support Foundation</span>. All Rights Reserved.</p>
      <p class="footer-copy">Motto: <span data-setting="site_tagline">Empowering Children, Building Futures</span>.</p>
    </div>
  `;
  document.body.appendChild(footer);
  hydrateTsfIcons(footer);
  document.getElementById('copy-year').textContent = new Date().getFullYear();
}

function renderMessageBox() {
  const box = document.createElement('div');
  box.className = 'message-box';
  box.innerHTML = `
    <div class="message-panel" id="msg-panel">
      <div class="message-panel-header">Share Your Thoughts About TSF</div>
      <div class="message-panel-body">
        <div id="msg-alert" class="alert"></div>
        <div class="form-group">
          <input type="text" class="form-control" id="msg-name" placeholder="Your Name">
          <span class="error-msg" id="msg-name-err">Please enter your name.</span>
        </div>
        <div class="form-group">
          <textarea class="form-control" id="msg-text" rows="3" placeholder="Write your message..."></textarea>
          <span class="error-msg" id="msg-text-err">Please write a message.</span>
        </div>
        <button type="button" class="btn btn-blue" style="width:100%;justify-content:center" onclick="submitMessage()">Send Message</button>
      </div>
    </div>
    <button type="button" class="message-toggle" id="msg-toggle" title="Share your thoughts" aria-label="Share your thoughts">+</button>
  `;
  document.body.appendChild(box);
  hydrateTsfIcons(box);

  document.getElementById('msg-toggle').addEventListener('click', function () {
    document.getElementById('msg-panel').classList.toggle('open');
  });
}

async function applySiteSettings() {
  try {
    const res = await fetch('BACKEND/site_settings.php', { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.success || !data.settings) return;
    Object.entries(data.settings).forEach(([key, value]) => {
      document.querySelectorAll(`[data-setting="${key}"]`).forEach(el => {
        if (el.tagName === 'A' && key.endsWith('_url')) {
          el.href = value || 'contact.html';
        } else {
          el.textContent = value;
        }
      });
      document.querySelectorAll(`[data-setting-bg="${key}"]`).forEach(el => {
        if (!value) return;
        el.style.backgroundImage = `linear-gradient(rgba(7, 24, 68, 0.62), rgba(26, 63, 163, 0.62)), url("${value}")`;
        el.style.backgroundSize = 'cover';
        el.style.backgroundPosition = 'center';
      });
      document.querySelectorAll(`[data-setting-src="${key}"]`).forEach(el => {
        if (!value) return;
        el.src = value;
        el.hidden = false;
        el.closest('[data-setting-image-frame]')?.classList.add('has-image');
      });
    });
    applyContactFallbackSettings(data.settings);
  } catch (_) {
    // Static content remains in place if the backend is unavailable.
  }
}

function applyContactFallbackSettings(settings) {
  const labelMap = {
    Address: 'contact_address',
    Phone: 'contact_phone',
    Email: 'contact_email',
    'Office Hours': 'office_hours'
  };
  document.querySelectorAll('.contact-detail-text').forEach(box => {
    const label = box.querySelector('label')?.textContent?.trim();
    const key = labelMap[label];
    if (!key || !settings[key]) return;
    const target = box.querySelector('a') || box.querySelector('p');
    if (!target) return;
    target.textContent = settings[key];
    if (key === 'contact_email' && target.tagName === 'A') target.href = `mailto:${settings[key]}`;
    if (key === 'contact_phone' && target.tagName === 'A') target.href = `tel:${settings[key].replace(/\\s+/g, '')}`;
  });
  const socialKeys = ['facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url', 'whatsapp_url'];
  document.querySelectorAll('.social-strip .social-btn').forEach((link, index) => {
    const key = socialKeys[index];
    if (key && settings[key]) link.href = settings[key];
  });
}

async function submitMessage() {
  const name = document.getElementById('msg-name');
  const text = document.getElementById('msg-text');
  const nameErr = document.getElementById('msg-name-err');
  const textErr = document.getElementById('msg-text-err');
  const alert = document.getElementById('msg-alert');
  let valid = true;

  nameErr.classList.remove('show'); name.classList.remove('error');
  textErr.classList.remove('show'); text.classList.remove('error');
  alert.classList.remove('show', 'alert-success', 'alert-error');

  if (!name.value.trim()) { nameErr.classList.add('show'); name.classList.add('error'); valid = false; }
  if (!text.value.trim()) { textErr.classList.add('show'); text.classList.add('error'); valid = false; }

  if (valid) {
    try {
      const tokenRes = await fetch('BACKEND/csrf_token.php?form=contact', { credentials: 'same-origin' });
      const tokenData = await tokenRes.json();
      if (!tokenData.success) throw new Error(tokenData.message || 'Unable to prepare message.');

      const res = await fetch('BACKEND/contact.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          csrf_token: tokenData.csrf_token,
          type: 'comment',
          name: name.value.trim(),
          email: '',
          subject: 'Website thought',
          message: text.value.trim()
        })
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Unable to send message.');

      alert.className = 'alert alert-success show';
      alert.textContent = 'Thank you! Your message has been recorded.';
      name.value = ''; text.value = '';
      name.classList.add('success'); text.classList.add('success');
      setTimeout(() => { alert.classList.remove('show'); name.classList.remove('success'); text.classList.remove('success'); }, 3500);
    } catch (err) {
      alert.className = 'alert alert-error show';
      alert.textContent = err.message || 'Unable to send message.';
    }
  }
}

document.addEventListener('DOMContentLoaded', function () {
  renderHeader();
  renderFooter();
  renderMessageBox();
  hydrateTsfIcons();
  applySiteSettings();

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); } });
  }, { threshold: 0.1 });
  document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));
});
