/**
 * Omni-Channel Voice Booking Concierge Component
 * Online Tourism Portal
 * 
 * Supports:
 * - Live Web Speech Recognition (Mic Input)
 * - Natural Language Speech Synthesis (TTS)
 * - Omnidim AI Function Calling Bridge
 * - Instant Package Reservation & Voucher Generation
 * - Real-Time Live Voice Bookings Ticker
 */

(function () {
  'use strict';

  // State Store
  const VoiceState = {
    selectedPackage: null,
    travelers: 2,
    travelDate: '',
    customerName: '',
    customerPhone: '',
    isListening: false,
    recognition: null,
    packagesCache: []
  };

  // Sound / Audio FX
  function playSuccessTone() {
    try {
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
      osc.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.15); // A5
      gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.35);
    } catch (e) {}
  }

  // Text to Speech
  function speakMessage(text) {
    if (!('speechSynthesis' in window)) return;
    try {
      window.speechSynthesis.cancel();
      const utterance = new SpeechSynthesisUtterance(text);
      utterance.rate = 1.0;
      utterance.pitch = 1.05;
      utterance.lang = 'en-IN';
      
      // Try finding an English (India) or pleasant natural voice
      const voices = window.speechSynthesis.getVoices();
      const indVoice = voices.find(v => v.lang.includes('en-IN') || v.lang.includes('hi-IN')) || voices[0];
      if (indVoice) utterance.voice = indVoice;

      window.speechSynthesis.speak(utterance);
    } catch (e) {
      console.warn('SpeechSynthesis error:', e);
    }
  }

  function getVoiceApiUrl(query = '') {
    const base = typeof window.TP_BASE_PATH !== 'undefined' ? window.TP_BASE_PATH : '';
    return base + 'api/voice-booking.php' + query;
  }

  // Pre-load available packages for instant fuzzy matching
  async function loadPackagesForVoice() {
    try {
      const res = await fetch(getVoiceApiUrl('?action=search_packages'));
      const data = await res.json();
      if (data && data.packages) {
        VoiceState.packagesCache = data.packages;
      }
    } catch (e) {
      console.warn('Could not cache packages:', e);
    }
  }

  // Initialize Speech Recognition
  function initSpeechEngine() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      console.warn('Web Speech API is not supported in this browser. Fallback to guided interactive prompts enabled.');
      return null;
    }

    const reco = new SpeechRecognition();
    reco.continuous = false;
    reco.interimResults = false;
    reco.lang = 'en-IN';

    reco.onstart = () => {
      VoiceState.isListening = true;
      updateVisualizer(true);
      setAgentStatus('Listening to your travel request... speak naturally.', 'listening');
    };

    reco.onresult = (event) => {
      const transcript = event.results[0][0].transcript;
      appendChatMessage('user', transcript);
      processVoiceCommand(transcript);
    };

    reco.onerror = (event) => {
      console.warn('Speech recognition error:', event.error);
      VoiceState.isListening = false;
      updateVisualizer(false);
      setAgentStatus('Could not catch that clearly. Please try speaking again or click a quick prompt.', 'idle');
    };

    reco.onend = () => {
      VoiceState.isListening = false;
      updateVisualizer(false);
    };

    return reco;
  }

  // Update animated audio visualizer
  function updateVisualizer(isActive) {
    const bars = document.querySelectorAll('.voice-wave-bar');
    bars.forEach(bar => {
      if (isActive) {
        bar.classList.add('animating');
      } else {
        bar.classList.remove('animating');
      }
    });

    const micBtn = document.getElementById('voice-mic-main-btn');
    if (micBtn) {
      if (isActive) {
        micBtn.classList.add('pulse-active', 'btn-danger');
        micBtn.classList.remove('btn-primary');
        micBtn.innerHTML = '<i class="bi bi-mic-fill fs-3 text-white"></i>';
      } else {
        micBtn.classList.remove('pulse-active', 'btn-danger');
        micBtn.classList.add('btn-primary');
        micBtn.innerHTML = '<i class="bi bi-mic fs-3 text-white"></i>';
      }
    }
  }

  function setAgentStatus(text, state) {
    const statusEl = document.getElementById('voice-agent-status-text');
    if (statusEl) {
      statusEl.textContent = text;
    }
    const badgeEl = document.getElementById('voice-agent-status-badge');
    if (badgeEl) {
      if (state === 'listening') {
        badgeEl.className = 'badge bg-danger animate-pulse';
        badgeEl.textContent = 'Listening...';
      } else if (state === 'processing') {
        badgeEl.className = 'badge bg-warning text-dark';
        badgeEl.textContent = 'Processing...';
      } else if (state === 'confirmed') {
        badgeEl.className = 'badge bg-success';
        badgeEl.textContent = 'Booking Confirmed';
      } else {
        badgeEl.className = 'badge badge-palette-ice';
        badgeEl.textContent = 'Ready';
      }
    }
  }

  function appendChatMessage(sender, text) {
    const chatContainer = document.getElementById('voice-dialog-stream');
    if (!chatContainer) return;

    const row = document.createElement('div');
    row.className = `d-flex mb-2 ${sender === 'user' ? 'justify-content-end' : 'justify-content-start'}`;
    
    if (sender === 'user') {
      row.innerHTML = `
        <div class="p-2 px-3 rounded-4 bg-primary text-white small shadow-sm" style="max-width: 80%;">
          <div class="fw-semibold small opacity-75 mb-0.5"><i class="bi bi-person-fill me-1"></i> You</div>
          <div>${escapeHtml(text)}</div>
        </div>
      `;
    } else {
      row.innerHTML = `
        <div class="p-2 px-3 rounded-4 bg-white border text-dark small shadow-sm" style="max-width: 80%;">
          <div class="fw-bold small text-royal mb-0.5"><i class="bi bi-robot me-1"></i> Aarya (Voice Concierge)</div>
          <div>${text}</div>
        </div>
      `;
    }
    chatContainer.appendChild(row);
    chatContainer.scrollTop = chatContainer.scrollHeight;
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  // Natural Language Command Interpreter
  async function processVoiceCommand(text) {
    const query = text.toLowerCase();
    setAgentStatus('Processing your travel request...', 'processing');

    // 1. Check if user mentioned number of people
    const peopleMatch = query.match(/(\d+)\s*(people|person|traveler|travelers|tickets?|members?)/i) || query.match(/for\s*(\d+)/i);
    if (peopleMatch && peopleMatch[1]) {
      VoiceState.travelers = Math.max(1, Math.min(20, parseInt(peopleMatch[1], 10)));
    }

    // 2. Check for date mentions (e.g. 15 dec, 20 december, next week, tomorrow)
    if (query.includes('tomorrow')) {
      const d = new Date();
      d.setDate(d.getDate() + 1);
      VoiceState.travelDate = d.toISOString().split('T')[0];
    } else {
      const monthNames = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
      for (let i = 0; i < monthNames.length; i++) {
        const m = monthNames[i];
        if (query.includes(m)) {
          const dayMatch = query.match(/(\d{1,2})(?:st|nd|rd|th)?/);
          if (dayMatch && dayMatch[1]) {
            const day = parseInt(dayMatch[1], 10);
            const year = new Date().getFullYear();
            const dateObj = new Date(year, i, day);
            if (dateObj < new Date()) {
              dateObj.setFullYear(year + 1);
            }
            VoiceState.travelDate = dateObj.toISOString().split('T')[0];
            break;
          }
        }
      }
    }

    if (!VoiceState.travelDate) {
      // Default to 14 days from now if not mentioned
      const defDate = new Date();
      defDate.setDate(defDate.getDate() + 14);
      VoiceState.travelDate = defDate.toISOString().split('T')[0];
    }

    // 3. Match package from text or destinationsCache
    let matchedPkg = null;

    // Check by destination keywords
    const keywords = ['goa', 'manali', 'jaipur', 'kashmir', 'rishikesh', 'ooty', 'darjeeling', 'varanasi', 'udaipur', 'munnar'];
    let targetDest = '';
    for (const kw of keywords) {
      if (query.includes(kw)) {
        targetDest = kw;
        break;
      }
    }

    if (targetDest && VoiceState.packagesCache.length > 0) {
      matchedPkg = VoiceState.packagesCache.find(p => 
        (p.destination_name || '').toLowerCase().includes(targetDest) ||
        (p.title || '').toLowerCase().includes(targetDest)
      );
    }

    // If still null, check if one was already pre-selected
    if (!matchedPkg && VoiceState.selectedPackage) {
      matchedPkg = VoiceState.selectedPackage;
    }

    // If still null, default to first available package or ask user
    if (!matchedPkg && VoiceState.packagesCache.length > 0) {
      matchedPkg = VoiceState.packagesCache[0];
    }

    if (!matchedPkg) {
      const msg = "I couldn't find a matching tour package for that destination. Would you like to explore Goa, Manali, Kashmir, or Jaipur?";
      appendChatMessage('agent', msg);
      speakMessage(msg);
      setAgentStatus('Awaiting destination selection...', 'idle');
      return;
    }

    VoiceState.selectedPackage = matchedPkg;
    updateLiveOrderPreview();

    // Check if command is an immediate booking intent
    if (query.includes('confirm') || query.includes('book') || query.includes('yes') || query.includes('reserve')) {
      await executeVoiceBooking();
    } else {
      const responseMsg = `I found the ${matchedPkg.title} for ${VoiceState.travelers} travelers on ${formatDisplayDate(VoiceState.travelDate)}. Total is ₹${(matchedPkg.price * VoiceState.travelers).toLocaleString('en-IN')}. Say "Confirm Booking" or click below to lock your reservation!`;
      appendChatMessage('agent', responseMsg);
      speakMessage(responseMsg);
      setAgentStatus('Say "Confirm Booking" to finalize.', 'idle');
    }
  }

  function formatDisplayDate(dateStr) {
    if (!dateStr) return 'Upcoming Season';
    try {
      const d = new Date(dateStr);
      return d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch (e) {
      return dateStr;
    }
  }

  function updateLiveOrderPreview() {
    const previewContainer = document.getElementById('voice-order-preview-card');
    if (!previewContainer || !VoiceState.selectedPackage) return;

    const pkg = VoiceState.selectedPackage;
    const total = (parseFloat(pkg.price) || 0) * VoiceState.travelers;

    previewContainer.classList.remove('d-none');
    previewContainer.innerHTML = `
      <div class="card border border-primary border-opacity-25 rounded-3 bg-white p-3 shadow-sm mb-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <span class="badge badge-palette-ice mb-1">${escapeHtml(pkg.destination_name || 'India')}</span>
            <h6 class="fw-bold mb-0 text-dark">${escapeHtml(pkg.title)}</h6>
          </div>
          <span class="text-royal fw-bold fs-6">₹${total.toLocaleString('en-IN')}</span>
        </div>
        <div class="row g-2 small text-muted pt-2 border-top">
          <div class="col-6"><i class="bi bi-calendar-event me-1 text-royal"></i> ${formatDisplayDate(VoiceState.travelDate)}</div>
          <div class="col-6 text-end"><i class="bi bi-people-fill me-1 text-royal"></i> ${VoiceState.travelers} Traveler(s)</div>
        </div>
      </div>
    `;
  }

  // Dispatch booking request to /api/voice-booking.php
  async function executeVoiceBooking() {
    if (!VoiceState.selectedPackage) return;
    
    setAgentStatus('Finalizing your voice booking in database...', 'processing');

    const customerNameInput = document.getElementById('voice-input-name');
    const customerPhoneInput = document.getElementById('voice-input-phone');
    
    const customerName = (customerNameInput && customerNameInput.value.trim()) || 'Aditya Sharma';
    const customerPhone = (customerPhoneInput && customerPhoneInput.value.trim()) || '9997879985';

    try {
      const res = await fetch(getVoiceApiUrl(), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'create_booking',
          package_id: VoiceState.selectedPackage.id,
          num_travelers: VoiceState.travelers,
          travel_date: VoiceState.travelDate,
          user_name: customerName,
          user_phone: customerPhone,
          source: 'voice'
        })
      });

      const result = await res.json();

      if (result.status === 'success') {
        playSuccessTone();
        setAgentStatus('Booking Successfully Confirmed!', 'confirmed');
        
        const successSpeech = `Congratulations! Your reservation for ${result.package_title} is confirmed. Your booking reference is ${result.booking_reference}.`;
        speakMessage(successSpeech);
        
        renderConfirmedSuccessScreen(result);
        
        // Prepend to Live Ticker on the page
        prependToLiveTicker(result);
      } else {
        const errorMsg = result.message || 'Unable to complete voice reservation. Please try again.';
        appendChatMessage('agent', errorMsg);
        speakMessage(errorMsg);
        setAgentStatus('Error: ' + errorMsg, 'idle');
      }
    } catch (err) {
      console.error('Voice booking API error:', err);
      const errMsg = 'Network error while booking. Please check your connection.';
      appendChatMessage('agent', errMsg);
      setAgentStatus('Network error', 'idle');
    }
  }

  // Render confirmed voucher screen inside modal
  function renderConfirmedSuccessScreen(data) {
    const modalBody = document.getElementById('voice-modal-content-area');
    if (!modalBody) return;

    modalBody.innerHTML = `
      <div class="text-center py-4">
        <div class="bg-success text-white p-3 rounded-circle d-inline-flex mb-3 shadow" style="width: 72px; height: 72px; align-items: center; justify-content: center;">
          <i class="bi bi-check2-circle display-4"></i>
        </div>
        <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-pill mb-2">
          <i class="bi bi-mic-fill me-1"></i> Voice Booking Confirmed
        </span>
        <h3 class="fw-bold text-dark mb-1">Reservation Confirmed!</h3>
        <p class="text-muted small mb-3">Your holiday package was successfully created via the Omni-channel Voice Concierge.</p>

        <div class="card border-0 bg-light rounded-4 p-3 mb-4 text-start shadow-sm mx-auto" style="max-width: 420px;">
          <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
            <span class="text-muted small">Booking Reference</span>
            <strong class="font-monospace text-primary fs-6">${escapeHtml(data.booking_reference)}</strong>
          </div>
          <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
            <span class="text-muted small">Tour Package</span>
            <strong class="text-dark small text-truncate ms-2" style="max-width: 200px;">${escapeHtml(data.package_title)}</strong>
          </div>
          <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
            <span class="text-muted small">Travel Date</span>
            <span class="text-dark small fw-semibold">${escapeHtml(data.formatted_date || data.travel_date)}</span>
          </div>
          <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
            <span class="text-muted small">Travelers</span>
            <span class="text-dark small fw-semibold">${escapeHtml(data.number_of_people)} Person(s)</span>
          </div>
          <div class="d-flex justify-content-between pt-1">
            <span class="text-dark fw-bold">Total Amount</span>
            <strong class="text-royal fs-5">${escapeHtml(data.formatted_total)}</strong>
          </div>
        </div>

        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
          <a href="${data.voucher_url}" class="btn btn-primary-tp px-4 py-2">
            <i class="bi bi-ticket-perforated-fill me-1"></i> View Official Travel Voucher
          </a>
          <a href="${data.payment_url}" class="btn btn-outline-tp px-4 py-2">
            <i class="bi bi-credit-card-2-front me-1"></i> Instant Checkout
          </a>
          <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">
            Close
          </button>
        </div>
      </div>
    `;
  }

  // Prepend newly booked item to live ticker
  function prependToLiveTicker(data) {
    const tickerContainer = document.getElementById('live-voice-ticker-items');
    if (!tickerContainer) return;

    const item = document.createElement('div');
    item.className = 'live-ticker-item animate-fade-in d-inline-flex align-items-center me-4';
    item.innerHTML = `
      <span class="badge bg-danger rounded-pill px-2 py-1 me-2" style="font-size: 0.7rem;">
        <i class="bi bi-mic-fill me-1"></i> Just Now
      </span>
      <span class="small text-dark">
        <strong>${escapeHtml(data.customer_name || 'Traveler')}</strong> booked 
        <strong class="text-primary">${escapeHtml(data.package_title)}</strong> (${escapeHtml(data.number_of_people)} Travelers) • 
        <span class="font-monospace text-muted">${escapeHtml(data.booking_reference)}</span>
      </span>
    `;

    tickerContainer.prepend(item);
  }

  // Periodically fetch live voice bookings for ticker
  async function refreshLiveTicker() {
    try {
      const res = await fetch(getVoiceApiUrl('?action=recent_bookings'));
      const data = await res.json();
      if (data && data.status === 'success' && data.data && data.data.length > 0) {
        const tickerContainer = document.getElementById('live-voice-ticker-items');
        if (tickerContainer) {
          tickerContainer.innerHTML = data.data.map(b => `
            <div class="live-ticker-item d-inline-flex align-items-center me-4">
              <span class="badge ${b.booking_source === 'voice' ? 'bg-primary' : 'bg-secondary'} rounded-pill px-2 py-1 me-2" style="font-size: 0.7rem;">
                <i class="bi ${b.booking_source === 'voice' ? 'bi-mic-fill' : 'bi-globe'} me-1"></i> ${escapeHtml(b.time_ago)}
              </span>
              <span class="small text-dark">
                <strong>${escapeHtml(b.masked_user)}</strong> reserved 
                <strong class="text-primary">${escapeHtml(b.package_title)}</strong> (${escapeHtml(b.number_of_people)} Travelers) • 
                <span class="font-monospace text-muted">${escapeHtml(b.booking_reference)}</span>
              </span>
            </div>
          `).join('');
        }
      }
    } catch (e) {
      console.warn('Ticker refresh error:', e);
    }
  }

  // Global helper: Open Voice Booking Modal for a specific package
  window.openVoiceBookingModal = function (packageId, packageTitle, packagePrice) {
    let pkg = null;
    if (packageId && VoiceState.packagesCache.length > 0) {
      pkg = VoiceState.packagesCache.find(p => p.id === parseInt(packageId, 10));
    }
    if (!pkg && packageId) {
      pkg = {
        id: parseInt(packageId, 10),
        title: packageTitle || 'Selected Package',
        price: packagePrice || 9999,
        destination_name: 'India'
      };
    }

    VoiceState.selectedPackage = pkg;
    
    const badgeEl = document.getElementById('voice-modal-selected-badge');
    const nameEl = document.getElementById('voice-modal-pkg-name');
    const priceEl = document.getElementById('voice-modal-pkg-price');

    if (badgeEl && nameEl && priceEl) {
      if (pkg) {
        nameEl.textContent = pkg.title;
        priceEl.textContent = '₹' + Number(pkg.price).toLocaleString('en-IN');
        badgeEl.classList.remove('d-none');
      } else {
        badgeEl.classList.add('d-none');
      }
    }

    const modalEl = document.getElementById('voiceBookingModal');
    if (!modalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  };

  // Wire event handlers on DOM load
  document.addEventListener('DOMContentLoaded', () => {
    loadPackagesForVoice();
    VoiceState.recognition = initSpeechEngine();

    // Refresh ticker immediately & every 15 seconds
    refreshLiveTicker();
    setInterval(refreshLiveTicker, 15000);

    // Microphone toggle button
    const micBtn = document.getElementById('voice-mic-main-btn');
    if (micBtn) {
      micBtn.addEventListener('click', () => {
        if (!VoiceState.recognition) {
          VoiceState.recognition = initSpeechEngine();
        }

        if (VoiceState.isListening) {
          try { VoiceState.recognition.stop(); } catch (e) {}
          VoiceState.isListening = false;
          updateVisualizer(false);
          setAgentStatus('Mic paused. Click again to speak.', 'idle');
        } else {
          try {
            VoiceState.recognition.start();
          } catch (e) {
            console.warn('SpeechRecognition start error:', e);
            // If mic permission blocked, show quick write-in
            const promptFallback = prompt('Please enter your voice command (e.g. "Book Goa for 2 on 20 Dec"):');
            if (promptFallback) {
              appendChatMessage('user', promptFallback);
              processVoiceCommand(promptFallback);
            }
          }
        }
      });
    }

    // Quick Command Suggestion Chips
    document.querySelectorAll('.voice-quick-prompt').forEach(chip => {
      chip.addEventListener('click', function () {
        const cmd = this.getAttribute('data-command') || this.textContent.trim();
        appendChatMessage('user', cmd);
        processVoiceCommand(cmd);
      });
    });

    // Execute Voice Confirmation Button inside modal
    const confirmBtn = document.getElementById('btn-confirm-voice-booking');
    if (confirmBtn) {
      confirmBtn.addEventListener('click', () => {
        executeVoiceBooking();
      });
    }

    // Floating Voice Concierge Launcher Button
    const fabBtn = document.getElementById('btn-launch-voice-concierge');
    if (fabBtn) {
      fabBtn.addEventListener('click', () => {
        window.openVoiceBookingModal();
      });
    }
  });

})();
