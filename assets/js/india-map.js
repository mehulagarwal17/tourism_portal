/**
 * Interactive India Destination Map Component
 * Powered by Google Maps JavaScript API
 * Usage Attribution: gmp_git_agentskills_v1
 */

(function () {
  'use strict';

  // Active markers map and pending selection queue
  let activeMarkersMap = new Map();
  let pendingDestinationId = null;

  // Immediately define window.flyToDestinationPin so it is NEVER undefined
  window.flyToDestinationPin = function (destId) {
    const id = parseInt(destId, 10);
    if (isNaN(id)) return;

    if (activeMarkersMap.has(id)) {
      const item = activeMarkersMap.get(id);
      if (item && typeof item.openDetail === 'function') {
        item.openDetail();
      }
    } else {
      pendingDestinationId = id;
      // Highlight sidebar item if it exists
      document.querySelectorAll('.map-dest-sidebar-item').forEach(el => el.classList.remove('active'));
      const targetCard = document.querySelector(`.map-dest-sidebar-item[data-dest-id="${id}"]`);
      if (targetCard) {
        targetCard.classList.add('active');
      }
    }
  };

  // Callback-based, foolproof loader for Google Maps JavaScript API
  function loadGoogleMapsScript(apiKey) {
    if (window.google && window.google.maps && typeof window.google.maps.Map === 'function') {
      return Promise.resolve();
    }

    if (window.__gmpPromise) {
      return window.__gmpPromise;
    }

    window.__gmpPromise = new Promise((resolve, reject) => {
      // Unique callback name on window
      const cbName = '__gmp_ready_' + Math.random().toString(36).substring(2, 9);
      
      window[cbName] = function () {
        delete window[cbName];
        resolve();
      };

      const existingScript = document.querySelector('script[data-gmp-script="1"]');
      if (existingScript) {
        if (window.google && window.google.maps && typeof window.google.maps.Map === 'function') {
          resolve();
        }
        return;
      }

      const script = document.createElement('script');
      script.setAttribute('data-gmp-script', '1');
      script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=maps,marker&v=weekly&callback=${cbName}`;
      script.async = true;
      script.defer = true;
      script.onerror = (err) => {
        delete window[cbName];
        window.__gmpPromise = null;
        reject(new Error('Google Maps script network request failed.'));
      };

      document.head.appendChild(script);
    });

    return window.__gmpPromise;
  }

  // Master India Map Initialization
  window.initIndiaDestinationsMap = async function (containerId, destinations, apiKey, options) {
    const container = document.getElementById(containerId);
    if (!container || !destinations || !destinations.length) return;

    options = options || {};
    const defaultCenter = options.center || { lat: 22.0, lng: 79.5 };
    const defaultZoom = options.zoom || 5;

    try {
      await loadGoogleMapsScript(apiKey);

      if (!window.google || !window.google.maps) {
        throw new Error('Google Maps SDK is unavailable.');
      }

      // Resolve constructors safely
      let MapClass = google.maps.Map;
      let InfoWindowClass = google.maps.InfoWindow;
      let AdvancedMarkerClass = null;

      if (google.maps.marker && typeof google.maps.marker.AdvancedMarkerElement === 'function') {
        AdvancedMarkerClass = google.maps.marker.AdvancedMarkerElement;
      }

      // If importLibrary is supported, try obtaining newer modules
      if (typeof google.maps.importLibrary === 'function') {
        try {
          const mapsLib = await google.maps.importLibrary("maps");
          if (mapsLib && typeof mapsLib.Map === 'function') {
            MapClass = mapsLib.Map;
          }
          if (mapsLib && typeof mapsLib.InfoWindow === 'function') {
            InfoWindowClass = mapsLib.InfoWindow;
          }
        } catch (e) {
          console.warn("importLibrary('maps') fallback to google.maps.Map:", e);
        }

        try {
          const markerLib = await google.maps.importLibrary("marker");
          if (markerLib && typeof markerLib.AdvancedMarkerElement === 'function') {
            AdvancedMarkerClass = markerLib.AdvancedMarkerElement;
          }
        } catch (e) {
          console.warn("importLibrary('marker') fallback:", e);
        }
      }

      if (typeof MapClass !== 'function') {
        throw new Error('Google Maps Map constructor is not available.');
      }

      const map = new MapClass(container, {
        center: defaultCenter,
        zoom: defaultZoom,
        minZoom: 4,
        maxZoom: 14,
        mapId: options.mapId || 'DEMO_MAP_ID',
        internalUsageAttributionIds: ['gmp_git_agentskills_v1'],
        gestureHandling: 'cooperative',
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true,
        zoomControl: true,
      });

      const infoWindow = new InfoWindowClass({
        disableAutoPan: false,
      });

      activeMarkersMap.clear();
      const bounds = new google.maps.LatLngBounds();

      destinations.forEach(dest => {
        if (!dest.lat || !dest.lng) return;

        const pos = { lat: parseFloat(dest.lat), lng: parseFloat(dest.lng) };
        bounds.extend(pos);

        const openDetail = () => {
          const attractionsHtml = (dest.attractions || [])
            .map(att => `<span class="badge badge-palette-ice text-truncate small me-1 mb-1">${att}</span>`)
            .join('');

          const contentHtml = `
            <div class="gmp-info-card">
              <div class="info-card-img" style="background-image: url('${dest.image}');">
                <span class="info-card-state"><i class="bi bi-geo-alt-fill me-1"></i>${dest.state}</span>
                <span class="info-card-pkg">${dest.package_count} Packages</span>
              </div>
              <div class="p-3">
                <h5 class="fw-bold mb-1" style="color: var(--tp-midnight, #091540);">${dest.name}</h5>
                <p class="small text-muted mb-2">${dest.description || ''}</p>
                <div class="mb-2">
                  <small class="text-secondary fw-semibold d-block mb-1"><i class="bi bi-calendar-check me-1" style="color: var(--tp-royal, #1b2cc1);"></i> Best Time: ${dest.best_time || 'All Seasons'}</small>
                  <div class="d-flex flex-wrap">${attractionsHtml}</div>
                </div>
                <div class="d-flex gap-2 pt-2 border-top">
                  <a href="${dest.url}" class="btn btn-sm btn-primary-tp flex-fill">
                    <i class="bi bi-compass me-1"></i> Explore Details
                  </a>
                  <a href="packages.php?destination_id=${dest.id}" class="btn btn-sm btn-outline-tp flex-fill">
                    <i class="bi bi-suitcase-lg me-1"></i> View Tours
                  </a>
                </div>
              </div>
            </div>
          `;

          infoWindow.setContent(contentHtml);
          infoWindow.open({
            anchor: markerInstance,
            map: map,
          });

          map.panTo(pos);
          if (map.getZoom() < 8) {
            map.setZoom(8);
          }

          // Highlight corresponding card in list if exists
          document.querySelectorAll('.map-dest-sidebar-item').forEach(el => el.classList.remove('active'));
          const targetCard = document.querySelector(`.map-dest-sidebar-item[data-dest-id="${dest.id}"]`);
          if (targetCard) {
            targetCard.classList.add('active');
            targetCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          }
        };

        let markerInstance;
        if (AdvancedMarkerClass) {
          const pinContainer = document.createElement('div');
          pinContainer.className = 'gmp-custom-dest-pin';
          pinContainer.setAttribute('data-id', dest.id);
          pinContainer.innerHTML = `
            <div class="dest-pin-badge">
              <span class="dest-pin-icon"><i class="bi bi-geo-alt-fill"></i></span>
              <span class="dest-pin-label">${dest.name}</span>
            </div>
            <div class="dest-pin-pulse"></div>
          `;

          markerInstance = new AdvancedMarkerClass({
            map: map,
            position: pos,
            title: dest.name + ' (' + dest.state + ')',
            content: pinContainer,
          });

          if (typeof markerInstance.addEventListener === 'function') {
            markerInstance.addEventListener('gmp-click', openDetail);
          } else if (typeof markerInstance.addListener === 'function') {
            markerInstance.addListener('click', openDetail);
          }
        } else {
          markerInstance = new google.maps.Marker({
            map: map,
            position: pos,
            title: dest.name + ' (' + dest.state + ')',
            animation: google.maps.Animation.DROP,
          });
          markerInstance.addListener('click', openDetail);
        }

        activeMarkersMap.set(dest.id, { marker: markerInstance, pos, openDetail });
      });

      // Fit bounds if more than 1 destination
      if (destinations.length > 1 && !options.disableAutoBounds) {
        map.fitBounds(bounds, { top: 40, right: 40, bottom: 40, left: 40 });
      }

      // Reset Map View button handler
      const resetBtn = document.getElementById('btn-reset-map-view');
      if (resetBtn) {
        resetBtn.addEventListener('click', () => {
          infoWindow.close();
          document.querySelectorAll('.map-dest-sidebar-item').forEach(el => el.classList.remove('active'));
          if (destinations.length > 1) {
            map.fitBounds(bounds, { top: 40, right: 40, bottom: 40, left: 40 });
          } else {
            map.setCenter(defaultCenter);
            map.setZoom(defaultZoom);
          }
        });
      }

      // If user clicked any destination before initialization finished, trigger it now
      if (pendingDestinationId !== null) {
        window.flyToDestinationPin(pendingDestinationId);
        pendingDestinationId = null;
      }

    } catch (err) {
      console.error('Google Maps initialization error:', err);
      if (container) {
        container.innerHTML = `
          <div class="p-4 text-center d-flex flex-column align-items-center justify-content-center h-100 bg-light rounded-4 border">
            <i class="bi bi-geo-alt-fill display-4 text-royal mb-2"></i>
            <h5 class="fw-bold text-dark mb-1">Interactive Destinations Ready</h5>
            <p class="text-muted small mb-3">Select any destination from the Quick Selection list on the right to view packages and tourist guides.</p>
          </div>
        `;
      }
    }
  };

  // Localized Single-Destination Map Loader
  window.initSingleDestinationMap = async function (containerId, lat, lng, destName, apiKey) {
    const container = document.getElementById(containerId);
    if (!container || !lat || !lng) return;

    try {
      await loadGoogleMapsScript(apiKey);
      if (!window.google || !window.google.maps) return;

      let MapClass = google.maps.Map;
      let AdvancedMarkerClass = (google.maps.marker && typeof google.maps.marker.AdvancedMarkerElement === 'function') 
        ? google.maps.marker.AdvancedMarkerElement 
        : null;

      if (typeof google.maps.importLibrary === 'function') {
        try {
          const mapsLib = await google.maps.importLibrary("maps");
          if (mapsLib && typeof mapsLib.Map === 'function') MapClass = mapsLib.Map;
        } catch (e) {}
        try {
          const markerLib = await google.maps.importLibrary("marker");
          if (markerLib && typeof markerLib.AdvancedMarkerElement === 'function') {
            AdvancedMarkerClass = markerLib.AdvancedMarkerElement;
          }
        } catch (e) {}
      }

      const pos = { lat: parseFloat(lat), lng: parseFloat(lng) };
      const map = new MapClass(container, {
        center: pos,
        zoom: 11,
        mapId: 'DEMO_MAP_ID',
        internalUsageAttributionIds: ['gmp_git_agentskills_v1'],
        gestureHandling: 'cooperative',
        mapTypeControl: false,
        streetViewControl: false,
      });

      if (AdvancedMarkerClass && google.maps.marker && google.maps.marker.PinElement) {
        const pin = new google.maps.marker.PinElement({
          background: '#1b2cc1',
          borderColor: '#091540',
          glyphColor: '#abd2fa',
          scale: 1.25,
        });

        new AdvancedMarkerClass({
          map: map,
          position: pos,
          title: destName,
          content: pin.element,
        });
      } else {
        new google.maps.Marker({
          map: map,
          position: pos,
          title: destName,
        });
      }
    } catch (e) {
      console.warn('Single destination map error:', e);
    }
  };
})();
