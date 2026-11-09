/**
 * Online Tourism Portal - Interactive Client Logic
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Minimum date restriction for travel bookings (Today or later)
  const travelDateInputs = document.querySelectorAll('input[type="date"].min-today');
  if (travelDateInputs.length > 0) {
    const today = new Date().toISOString().split('T')[0];
    travelDateInputs.forEach(input => {
      input.setAttribute('min', today);
    });
  }

  // 2. Real-time Tour Booking Price Calculator
  const travelersInput = document.getElementById('num_travelers');
  const pricePerPersonElem = document.getElementById('price_per_person');
  const totalAmountElem = document.getElementById('total_amount_display');
  const totalAmountInput = document.getElementById('total_amount_input');

  if (travelersInput && pricePerPersonElem && totalAmountElem) {
    const basePrice = parseFloat(pricePerPersonElem.getAttribute('data-price') || '0');

    function updateTotal() {
      const count = parseInt(travelersInput.value, 10) || 1;
      const validCount = Math.max(1, Math.min(30, count));
      travelersInput.value = validCount;
      const total = basePrice * validCount;
      totalAmountElem.textContent = '₹' + total.toLocaleString('en-IN');
      if (totalAmountInput) {
        totalAmountInput.value = total;
      }
    }

    travelersInput.addEventListener('input', updateTotal);
    travelersInput.addEventListener('change', updateTotal);
    updateTotal();
  }



  // 4. Password Visibility Toggle
  const togglePasswordBtns = document.querySelectorAll('.toggle-password-btn');
  togglePasswordBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const targetId = this.getAttribute('data-target') || 'password';
      const target = document.getElementById(targetId);
      if (target) {
        const isPassword = target.getAttribute('type') === 'password';
        target.setAttribute('type', isPassword ? 'text' : 'password');
        const icon = this.querySelector('i');
        if (icon) {
          icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        }
      }
    });
  });

  // 5. Destructive Action Confirmation Dialogs
  const confirmActions = document.querySelectorAll('.confirm-action');
  confirmActions.forEach(el => {
    el.addEventListener('click', function (e) {
      const msg = this.getAttribute('data-confirm-message') || 'Are you sure you want to proceed?';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  // 6. Landing Page Cursive Hero Typewriter Effect
  const typewriterTarget = document.getElementById('hero-typewriter-text');
  if (typewriterTarget) {
    const words = [
      'Incredible',
      'Breathtaking',
      'Enchanting',
      'Majestic',
      'Unforgettable',
      'Soulful',
      'Timeless'
    ];
    let wordIndex = 0;
    let charIndex = words[0].length;
    let isDeleting = true;
    const typingSpeed = 110;
    const pauseTime = 2200;
    const deleteSpeed = 55;

    function runTypewriter() {
      const currentWord = words[wordIndex];

      if (isDeleting) {
        charIndex--;
        typewriterTarget.textContent = currentWord.substring(0, charIndex);
      } else {
        charIndex++;
        typewriterTarget.textContent = currentWord.substring(0, charIndex);
      }

      let delta = isDeleting ? deleteSpeed : typingSpeed;

      if (!isDeleting && charIndex === currentWord.length) {
        delta = pauseTime;
        isDeleting = true;
      } else if (isDeleting && charIndex === 0) {
        isDeleting = false;
        wordIndex = (wordIndex + 1) % words.length;
        delta = 350;
      }

      setTimeout(runTypewriter, delta);
    }

    // Begin cycling after initial pause on "Incredible"
    setTimeout(runTypewriter, pauseTime);
  }
});
