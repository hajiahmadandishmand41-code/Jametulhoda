'use strict';
document.querySelectorAll('[data-theme-toggle]').forEach(button => {
  const update = () => { const dark = document.documentElement.dataset.theme === 'dark'; button.setAttribute('aria-pressed', String(dark)); button.innerHTML = dark ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>'; };
  update();
  button.addEventListener('click', () => {
    const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = theme; document.documentElement.setAttribute('data-bs-theme', theme);
    try { localStorage.setItem('jhd-theme', theme); } catch (_) {}
    update();
  });
});
const menuButton = document.getElementById('menuToggle');
const navigation = document.getElementById('siteNavigation');
if (menuButton && navigation) {
  const close = () => { navigation.classList.remove('is-open'); menuButton.setAttribute('aria-expanded', 'false'); };
  menuButton.addEventListener('click', () => {
    const open = menuButton.getAttribute('aria-expanded') !== 'true';
    navigation.classList.toggle('is-open', open); menuButton.setAttribute('aria-expanded', String(open));
    if (open) navigation.querySelector('a')?.focus();
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && navigation.classList.contains('is-open')) { close(); menuButton.focus(); } });
  document.addEventListener('click', e => { if (!e.target.closest('.jhd-header')) close(); });
  matchMedia('(min-width: 992px)').addEventListener('change', close);
}
