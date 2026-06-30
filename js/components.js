/* ===== TSF SHARED COMPONENTS ===== */

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
      <div class="header-time" id="live-datetime">Loading...</div>
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
        <div class="footer-contact-item"><span class="icon">-</span><span data-setting="contact_address">Kumasi, Ashanti Region, Ghana</span></div>
        <div class="footer-contact-item"><span class="icon">-</span><span data-setting="contact_phone">+233 XX XXX XXXX</span></div>
        <div class="footer-contact-item"><span class="icon">-</span><span data-setting="contact_email">info@tsfghana.org</span></div>
        <div class="footer-contact-item"><span class="icon">-</span><span>www.tsfghana.org</span></div>
      </div>
    </div>
    <div class="footer-bottom" style="padding-top:1.2rem">
      <p class="footer-copy">&copy; <span id="copy-year"></span> <span data-setting="site_name">Trenchkid Support Foundation</span>. All Rights Reserved.</p>
      <p class="footer-copy">Motto: <span data-setting="site_tagline">Empowering Children, Building Futures</span>.</p>
    </div>
  `;
  document.body.appendChild(footer);
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
  applySiteSettings();

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); } });
  }, { threshold: 0.1 });
  document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));
});
