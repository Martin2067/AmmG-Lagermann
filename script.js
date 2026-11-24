const toggle = document.getElementById('menuToggle');
const nav = document.getElementById('siteNav');
if (toggle && nav) {
  const setMenuOpen = (open) => {
    nav.classList.toggle('open', open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? toggle.dataset.closeLabel : toggle.dataset.openLabel);
  };

  toggle.addEventListener('click', () => setMenuOpen(!nav.classList.contains('open')));
  nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setMenuOpen(false)));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && nav.classList.contains('open')) {
      setMenuOpen(false);
      toggle.focus();
    }
  });
}

const form = document.querySelector('.contact-form');
if (form) {
  form.addEventListener('invalid', (event) => {
    const field = event.target;
    if (field.validity.valueMissing) {
      const message = field.name === 'gdpr' ? form.dataset.consentMessage : form.dataset.requiredMessage;
      field.setCustomValidity(message);
    } else if (field.validity.typeMismatch) {
      field.setCustomValidity(form.dataset.emailMessage);
    }
  }, true);

  form.addEventListener('input', (event) => event.target.setCustomValidity(''));
}

const year = document.getElementById('year');
if (year) year.textContent = new Date().getFullYear();
