document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.toggle-password').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault(); // Prevent any form submission weirdness
      
      var input = document.getElementById(btn.getAttribute('data-target'));
      if (!input) return;

      var img = btn.querySelector('img');
      var showing = input.type === 'text';
      
      // Toggle the input type
      input.type = showing ? 'password' : 'text';
      
      // Swap the image source, preserving whatever folder depth this page is at
      // (e.g. "img/show.png" on root pages, "../img/show.png" from admin/ pages)
      if (img) {
          img.src = img.src.replace(/(show|hide)\.png(\?.*)?$/, (showing ? 'show.png' : 'hide.png'));
      }
      
      btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
  });
});