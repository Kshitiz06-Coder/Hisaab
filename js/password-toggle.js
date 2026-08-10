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
      
      // Swap the image source directly
      if (img) {
          img.src = showing ? 'img/show.png' : 'img/hide.png';
      }
      
      btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
  });
});