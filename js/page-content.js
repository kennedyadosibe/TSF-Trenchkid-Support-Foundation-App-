/* ===== TSF DYNAMIC PAGE CONTENT ===== */

function tsfEscape(value) {
  return String(value ?? '').replace(/[&<>"']/g, char => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  }[char]));
}

function imageOrInitials(item, className) {
  if (item.image_url) {
    return `<img class="${className}" src="${tsfEscape(item.image_url)}" alt="${tsfEscape(item.title)}">`;
  }
  return `<div class="${className.replace('team-img', 'team-initials').replace('author-avatar', 'author-initials')}">${tsfEscape(item.meta_value || initials(item.title))}</div>`;
}

function initials(name) {
  return String(name || 'TSF').split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase();
}

async function fetchContentItems(types) {
  const res = await fetch(`BACKEND/content_items.php?types=${encodeURIComponent(types.join(','))}`, { credentials: 'same-origin' });
  const data = await res.json();
  if (!data.success) throw new Error(data.message || 'Unable to load content.');
  return data.items || {};
}

function renderTeam(items) {
  const teamGrid = document.querySelector('.team-grid');
  if (teamGrid && items.team_member?.length) {
    teamGrid.innerHTML = items.team_member.map(item => `
      <div class="team-card fade-up visible">
        <div class="team-img-wrap">
          ${imageOrInitials(item, 'team-img')}
          <svg class="team-wave" viewBox="0 0 400 40"><path d="M0,20 Q100,40 200,20 T400,20 L400,40 L0,40 Z" fill="white"/></svg>
        </div>
        <div class="team-body">
          <h3>${tsfEscape(item.title)}</h3>
          <div class="team-role">${tsfEscape(item.subtitle || '')}</div>
          <p class="team-bio">${tsfEscape(item.body || '')}</p>
        </div>
      </div>
    `).join('');
  }

  const advisorsGrid = document.querySelector('.advisors-grid');
  if (advisorsGrid && items.advisor?.length) {
    advisorsGrid.innerHTML = items.advisor.map(item => `
      <div class="advisor-card fade-up visible">
        <div class="advisor-initials">${tsfEscape(item.meta_value || initials(item.title))}</div>
        <div class="advisor-name">${tsfEscape(item.title)}</div>
        <div class="advisor-title">${tsfEscape(item.subtitle || '')}</div>
      </div>
    `).join('');
  }
}

function renderImpact(items) {
  const icons = { child: '👧', check: '✅', school: '🏫', laptop: '💻' };
  const statsGrid = document.querySelector('.impact-hero-stats');
  if (statsGrid && items.impact_stat?.length) {
    statsGrid.innerHTML = items.impact_stat.map(item => `
      <div class="impact-stat-card fade-up visible">
        <div class="num-icon">${tsfEscape(icons[item.subtitle] || item.subtitle || '•')}</div>
        <span class="big-num" data-target="${parseInt(item.meta_value || '0', 10)}">0</span>
        <div class="num-label">${tsfEscape(item.title)}</div>
      </div>
    `).join('');
    statsGrid.querySelectorAll('[data-target]').forEach(el => {
      const target = parseInt(el.dataset.target || '0', 10);
      el.textContent = `${target.toLocaleString()}+`;
    });
  }

  const programsGrid = document.querySelector('.programs-grid');
  if (programsGrid && items.program?.length) {
    programsGrid.innerHTML = items.program.map(item => `
      <div class="program-card fade-up visible">
        ${item.image_url ? `<img class="program-img" src="${tsfEscape(item.image_url)}" alt="${tsfEscape(item.title)}">` : ''}
        <div class="program-body">
          <span class="program-tag">${tsfEscape(item.subtitle || 'Program')}</span>
          <h3>${tsfEscape(item.title)}</h3>
          <p>${tsfEscape(item.body || '')}</p>
          ${item.meta_value ? `<div class="program-stat">${tsfEscape(item.meta_value)}</div>` : ''}
        </div>
      </div>
    `).join('');
  }

  const testimonialsGrid = document.querySelector('.testimonials-grid');
  if (testimonialsGrid && items.testimonial?.length) {
    testimonialsGrid.innerHTML = items.testimonial.map(item => `
      <div class="testimonial-card fade-up visible">
        <div class="stars">★★★★★</div>
        <p class="testimonial-text">${tsfEscape(item.body || '')}</p>
        <div class="testimonial-author">
          ${imageOrInitials(item, 'author-avatar')}
          <div>
            <div class="author-name">${tsfEscape(item.title)}</div>
            <div class="author-role">${tsfEscape(item.subtitle || '')}</div>
          </div>
        </div>
      </div>
    `).join('');
  }

  const regionList = document.querySelector('.region-list');
  if (regionList && items.region?.length) {
    regionList.innerHTML = items.region.map(item => `<li>${tsfEscape(item.title)}</li>`).join('');
  }
}

function renderFaq(items) {
  const faqList = document.querySelector('.faq-list');
  if (faqList && items.faq?.length) {
    faqList.innerHTML = items.faq.map(item => `
      <div class="faq-item fade-up visible">
        <div class="faq-question" onclick="toggleFaq(this)">${tsfEscape(item.title)} <span class="faq-chevron">▼</span></div>
        <div class="faq-answer"><div class="faq-answer-inner">${tsfEscape(item.body || '')}</div></div>
      </div>
    `).join('');
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  try {
    const path = window.location.pathname;
    if (path.includes('team')) {
      renderTeam(await fetchContentItems(['team_member', 'advisor']));
    } else if (path.includes('impact')) {
      renderImpact(await fetchContentItems(['impact_stat', 'program', 'testimonial', 'region']));
    } else if (path.includes('contact')) {
      renderFaq(await fetchContentItems(['faq']));
    }
  } catch (_) {
    // Static page content remains in place if dynamic content is unavailable.
  }
});
