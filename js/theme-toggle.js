// ============================================
// HISSAB — Dark mode toggle
// Applies <html data-theme="dark|light">, persisted in localStorage.
// Keeps every switch on the page (topbar dropdown + Settings) in sync.
// ============================================

(function () {
  var STORAGE_KEY = 'hissab-theme';

  function getStoredTheme() {
    try {
      return localStorage.getItem(STORAGE_KEY) || 'light';
    } catch (e) {
      return 'light';
    }
  }

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    document.querySelectorAll('.theme-switch-input').forEach(function (input) {
      input.checked = theme === 'dark';
    });
  }

  function setTheme(theme) {
    try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) {}
    applyTheme(theme);
  }

  document.addEventListener('DOMContentLoaded', function () {
    applyTheme(getStoredTheme());

    document.querySelectorAll('.theme-switch-input').forEach(function (input) {
      input.addEventListener('change', function () {
        setTheme(input.checked ? 'dark' : 'light');
      });
    });
  });
})();
