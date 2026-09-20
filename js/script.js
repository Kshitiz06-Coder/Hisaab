
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

  // --- Delete confirmation (custom popup instead of browser confirm()) ---
  var confirmDeleteModal = document.getElementById('confirmDeleteModal');
  var confirmDeleteText = document.getElementById('confirmDeleteText');
  var confirmDeleteYes = document.getElementById('confirmDeleteYes');
  var pendingDeleteForm = null;

  document.querySelectorAll('.confirm-delete').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      // Fallback to native confirm if the shared popup isn't on this page for some reason
      if (!confirmDeleteModal || !confirmDeleteYes) {
        var label = form.getAttribute('data-label') || 'this entry';
        if (!confirm('Do you really want to delete ' + label + '? This cannot be undone.')) {
          e.preventDefault();
        }
        return;
      }
      e.preventDefault();
      pendingDeleteForm = form;
      var label = form.getAttribute('data-label') || 'this transaction';
      if (confirmDeleteText) {
        confirmDeleteText.textContent = 'Do you really want to delete ' + label + '? This cannot be undone.';
      }
      confirmDeleteModal.classList.add('open');
    });
  });

  if (confirmDeleteYes) {
    confirmDeleteYes.addEventListener('click', function () {
      confirmDeleteModal.classList.remove('open');
      if (pendingDeleteForm) {
        var formToSubmit = pendingDeleteForm;
        pendingDeleteForm = null;
        formToSubmit.submit(); // .submit() bypasses the 'submit' listener above, so no loop
      }
    });
  }

  // Clear the pending form if the popup is dismissed without confirming
  if (confirmDeleteModal) {
    confirmDeleteModal.addEventListener('click', function (e) {
      if (e.target === confirmDeleteModal || e.target.closest('[data-modal-close]')) {
        pendingDeleteForm = null;
      }
    });
  }

  // --- Settings tabs ---
  function activateTab(targetId) {
    var tabLink = document.querySelector('[data-tab-target="' + targetId + '"]');
    var panel = document.getElementById(targetId);
    if (!tabLink || !panel) return;
    document.querySelectorAll('.settings-tabs a').forEach(function (t) { t.classList.remove('active'); });
    document.querySelectorAll('.settings-panel').forEach(function (p) { p.classList.remove('active'); });
    tabLink.classList.add('active');
    panel.classList.add('active');
  }
  document.querySelectorAll('[data-tab-target]').forEach(function (tab) {
    tab.addEventListener('click', function (e) {
      e.preventDefault();
      activateTab(tab.getAttribute('data-tab-target'));
    });
  });
  // Open the right tab if we were redirected back with a #tab-id hash
  // (e.g. after saving something inside a specific Settings tab).
  if (window.location.hash) {
    activateTab(window.location.hash.substring(1));
  }

  // --- Auto-hide flash alerts (shown as floating popups, see .alert[data-autohide] CSS) ---
  document.querySelectorAll('.alert[data-autohide]').forEach(function (alertEl, i) {
    alertEl.style.top = (22 + i * 64) + 'px'; // stack if more than one is ever shown at once
    setTimeout(function () {
      alertEl.classList.add('toast-hide');
      setTimeout(function () { alertEl.remove(); }, 350);
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
