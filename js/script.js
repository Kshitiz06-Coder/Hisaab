// ============================================
// HISSAB — UI behaviors
// ============================================

document.addEventListener('DOMContentLoaded', function () {
  // --- Sidebar toggle (mobile) ---
  var hamburger = document.getElementById('hamburger');
  var sidebar = document.getElementById('sidebar');
  var overlay = document.getElementById('overlay');
  var sidebarClose = document.getElementById('sidebarClose');

  function openSidebar() {
    sidebar && sidebar.classList.add('open');
    overlay && overlay.classList.add('open');
  }
  function closeSidebar() {
    sidebar && sidebar.classList.remove('open');
    overlay && overlay.classList.remove('open');
  }
  hamburger && hamburger.addEventListener('click', openSidebar);
  sidebarClose && sidebarClose.addEventListener('click', closeSidebar);
  overlay && overlay.addEventListener('click', closeSidebar);

  // --- Generic modal open/close via data attributes ---
  document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var modal = document.getElementById(btn.getAttribute('data-modal-open'));
      if (modal) modal.classList.add('open');
    });
  });
  document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var modal = btn.closest('.modal-backdrop');
      if (modal) modal.classList.remove('open');
    });
  });
  document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
    backdrop.addEventListener('click', function (e) {
      if (e.target === backdrop) backdrop.classList.remove('open');
    });
  });

  // --- Delete confirmation ---
  document.querySelectorAll('.confirm-delete').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var label = form.getAttribute('data-label') || 'this entry';
      if (!confirm('Delete ' + label + '? This cannot be undone.')) {
        e.preventDefault();
      }
    });
  });

  // --- Settings tabs ---
  document.querySelectorAll('[data-tab-target]').forEach(function (tab) {
    tab.addEventListener('click', function (e) {
      e.preventDefault();
      var targetId = tab.getAttribute('data-tab-target');
      document.querySelectorAll('.settings-tabs a').forEach(function (t) { t.classList.remove('active'); });
      document.querySelectorAll('.settings-panel').forEach(function (p) { p.classList.remove('active'); });
      tab.classList.add('active');
      document.getElementById(targetId).classList.add('active');
    });
  });

  // --- Auto-hide flash alerts ---
  document.querySelectorAll('.alert[data-autohide]').forEach(function (alertEl) {
    setTimeout(function () {
      alertEl.style.transition = 'opacity .4s ease';
      alertEl.style.opacity = '0';
      setTimeout(function () { alertEl.remove(); }, 400);
    }, 3500);
  });

  // --- Live balance preview on savings "add funds" input (optional UX touch) ---
  document.querySelectorAll('.savings-add-input').forEach(function (input) {
    input.addEventListener('input', function () {
      var target = parseFloat(input.getAttribute('data-target') || '0');
      var current = parseFloat(input.getAttribute('data-current') || '0');
      var addVal = parseFloat(input.value || '0');
      var preview = document.getElementById('preview-' + input.getAttribute('data-goal-id'));
      if (preview) {
        var pct = Math.min(100, ((current + addVal) / target) * 100);
        preview.style.width = pct + '%';
      }
    });
  });
});
