// Real-time (as-you-type) validation for the Register and Login forms.
// Each field gets checked on every keystroke/blur and shows an inline error
// under the input — no need to submit the form to find out something's wrong.
(function () {

  const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  // Letters (incl. common accented characters), spaces, apostrophes, periods, hyphens.
  const NAME_PATTERN = /^[A-Za-z\u00C0-\u024F\s'.-]*$/;

  function setState(input, errorEl, message) {
    if (!errorEl) return;
    if (message === null) {
      // Neutral: field is empty and untouched, or just cleared — don't nag yet.
      errorEl.textContent = '';
      errorEl.classList.remove('show');
      input.classList.remove('input-invalid', 'input-valid');
    } else if (message === true) {
      // Valid.
      errorEl.textContent = '';
      errorEl.classList.remove('show');
      input.classList.remove('input-invalid');
      input.classList.add('input-valid');
    } else {
      // Invalid — show the message.
      errorEl.textContent = message;
      errorEl.classList.add('show');
      input.classList.remove('input-valid');
      input.classList.add('input-invalid');
    }
  }

  function bind(input, errorEl, validateFn, opts) {
    if (!input) return null;
    opts = opts || {};

    function run() {
      const value = input.value;

      if (value.trim() === '' && !opts.requiredEvenEmpty) {
        setState(input, errorEl, null);
        return true; // don't block submit here; "required" attr / server catches empty
      }

      const result = validateFn(value);
      if (result === true) {
        setState(input, errorEl, true);
        return true;
      }
      setState(input, errorEl, result);
      return false;
    }

    input.addEventListener('input', run);
    input.addEventListener('blur', run);
    return run;
  }

  // ---------- Full name ----------
  const nameInput = document.getElementById('full_name');
  const nameError = document.getElementById('full_name_error');
  const validateName = bind(nameInput, nameError, function (value) {
    if (/\d/.test(value)) return 'Name cannot contain numbers.';
    if (!NAME_PATTERN.test(value)) return "Name can only contain letters, spaces, and ' . -";
    if (value.trim().length < 2) return 'Name is too short.';
    return true;
  });

  // ---------- Email ----------
  const emailInput = document.getElementById('email');
  const emailError = document.getElementById('email_error');
  const validateEmail = bind(emailInput, emailError, function (value) {
    if (!EMAIL_PATTERN.test(value)) return 'Enter a valid email address (e.g. you@example.com).';
    return true;
  });

  // ---------- Password (register page only — login just needs "not empty") ----------
  const passwordInput = document.getElementById('password');
  const passwordError = document.getElementById('password_error');
  const isRegisterPage = !!document.getElementById('confirm_password');

  let validatePassword = null;
  if (isRegisterPage) {
    validatePassword = bind(passwordInput, passwordError, function (value) {
      if (value.length < 6) return 'Password must be at least 6 characters.';
      return true;
    });
  }

  // ---------- Confirm password (register page only) ----------
  const confirmInput = document.getElementById('confirm_password');
  const confirmError = document.getElementById('confirm_password_error');
  let validateConfirm = null;
  if (confirmInput) {
    validateConfirm = bind(confirmInput, confirmError, function (value) {
      if (value !== passwordInput.value) return 'Passwords do not match.';
      return true;
    }, { requiredEvenEmpty: false });

    // Re-check the confirm field whenever the password field changes too,
    // since typing in "password" can make an already-filled "confirm" wrong/right.
    if (passwordInput) {
      passwordInput.addEventListener('input', function () {
        if (confirmInput.value !== '') validateConfirm();
      });
    }
  }

  // ---------- Block submit if any visible field is currently invalid ----------
  const form = (nameInput || emailInput || passwordInput || confirmInput);
  const formEl = form ? form.closest('form') : null;
  if (formEl) {
    formEl.addEventListener('submit', function (e) {
      let ok = true;

      if (nameInput && nameInput.value.trim() !== '' && validateName && !validateName()) ok = false;
      if (emailInput && emailInput.value.trim() !== '' && validateEmail && !validateEmail()) ok = false;
      if (passwordInput && isRegisterPage && passwordInput.value !== '' && validatePassword && !validatePassword()) ok = false;
      if (confirmInput && confirmInput.value !== '' && validateConfirm && !validateConfirm()) ok = false;

      if (!ok) {
        e.preventDefault();
        const firstInvalid = formEl.querySelector('.input-invalid');
        if (firstInvalid) firstInvalid.focus();
      }
    });
  }
})();
