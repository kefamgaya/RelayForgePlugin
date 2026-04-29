window.RelayForgeWordPress = window.RelayForgeWordPress || {};

(function () {
  function initBookingMultiStep(form) {
    var shell = form.querySelector('.rfp-bms-steps');
    if (!shell) return;

    var steps = Array.prototype.slice.call(shell.querySelectorAll('[data-rfp-step]'));
    var dots = Array.prototype.slice.call(shell.querySelectorAll('.rfp-bms__dot'));
    var labelEl = shell.querySelector('.rfp-bms__label');
    var errorEl = form.querySelector('.rfp-bms__error');
    var backBtn = form.querySelector('[data-rfp-bms-back]');
    var nextBtn = form.querySelector('[data-rfp-bms-next]');
    var submitBtn = form.querySelector('.rfp-bms__submit');
    var partyLineEl = shell.querySelector('[data-rfp-party-line]');
    var estWrapEl = shell.querySelector('[data-rfp-estimate-wrap]');
    var estAmtEl = shell.querySelector('[data-rfp-estimate-amount]');

    var minGuest = Math.max(1, parseInt(String(shell.dataset.rfpMinGuests || '1'), 10) || 1);
    var maxParty = Math.min(99, Math.max(minGuest, parseInt(String(shell.dataset.rfpMaxParty || '99'), 10) || 99));

    var stepLabels = ['Date & guests', 'Your details', 'Review & send'];
    var current = 0;

    function showError(msg) {
      if (!errorEl) return;
      errorEl.textContent = msg;
      errorEl.hidden = false;
    }

    function clearError() {
      if (!errorEl) return;
      errorEl.textContent = '';
      errorEl.hidden = true;
    }

    function stabilizeParty(ai, chi) {
      var a = Math.max(1, ai | 0);
      var c = Math.max(0, chi | 0);

      while (a + c > maxParty && c > 0) {
        c -= 1;
      }
      while (a + c > maxParty && a > 1) {
        a -= 1;
      }

      while (a + c < minGuest && a + c < maxParty) {
        if (c < maxParty - a) {
          c += 1;
        } else if (a < maxParty - c) {
          a += 1;
        } else {
          break;
        }
      }

      if (c > maxParty - a) {
        c = maxParty - a;
      }

      var minA = Math.max(1, minGuest - c);
      if (a < minA && minA <= maxParty - c) {
        a = minA;
      }

      while (a + c > maxParty && c > 0) {
        c -= 1;
      }
      while (a + c > maxParty && a > Math.max(1, minGuest - c)) {
        a -= 1;
      }

      return { adults: Math.max(1, a | 0), children: Math.max(0, c | 0) };
    }

    function readParty() {
      var ia = shell.querySelector('input[name="rfp_adults"]');
      var ic = shell.querySelector('input[name="rfp_children"]');
      var a = parseInt(String((ia && ia.value) || '1'), 10) || 1;
      var child = parseInt(String((ic && ic.value) || '0'), 10) || 0;
      return stabilizeParty(a, child);
    }

    function writeParty(pa) {
      var ia = shell.querySelector('input[name="rfp_adults"]');
      var ich = shell.querySelector('input[name="rfp_children"]');
      var tot = shell.querySelector('input[name="number_of_people"]');
      var da = shell.querySelector('[data-rfp-display-adults]');
      var dc = shell.querySelector('[data-rfp-display-children]');
      if (ia) ia.value = String(pa.adults);
      if (ich) ich.value = String(pa.children);
      if (tot) tot.value = String(pa.adults + pa.children);
      if (da) da.textContent = String(pa.adults);
      if (dc) dc.textContent = String(pa.children);
      syncExtras();
    }

    function formatDateLabel(iso) {
      if (!iso) return '';
      var parts = iso.split(/\D/).map(Number);
      if (parts.length < 3) return iso;
      var d = new Date(parts[0], parts[1] - 1, parts[2]);
      if (isNaN(d.getTime())) return iso;
      try {
        return d.toLocaleDateString(undefined, {
          weekday: 'short',
          month: 'short',
          day: 'numeric',
          year: 'numeric',
        });
      } catch (_) {
        return iso;
      }
    }

    function formatMoney(amount) {
      var cur = String(shell.dataset.rfpCurrency || '').trim();
      var n = typeof amount !== 'number' || isNaN(amount) ? 0 : amount;
      try {
        var s = n.toLocaleString(undefined, {
          minimumFractionDigits: n % 1 === 0 ? 0 : 2,
          maximumFractionDigits: 2,
        });
        return cur ? cur + ' ' + s : s;
      } catch (_) {
        return (cur ? cur + ' ' : '') + amount;
      }
    }

    function computeEstimate(pa) {
      if (shell.dataset.rfpEstimateEnabled !== '1') return null;

      var model = String(shell.dataset.rfpPricingModel || 'per_person');
      var price = parseFloat(String(shell.dataset.rfpPrice || '0')) || 0;
      var bgSizeRaw = parseInt(String(shell.dataset.rfpBaseGroupSize || '0'), 10) || 0;
      var bgPrice = parseFloat(String(shell.dataset.rfpBaseGroupPrice || '0')) || 0;
      var xp = parseFloat(String(shell.dataset.rfpExtraPersonPrice || '0')) || 0;
      var minG = Math.max(1, parseInt(String(shell.dataset.rfpMinGuests || '1'), 10) || 1);
      var ppl = pa.adults + pa.children;

      if ('group' === model) {
        var baseSize = Math.max(minG, bgSizeRaw > 0 ? bgSizeRaw : minG);
        var baseAmt = bgPrice > 0 ? bgPrice : price * baseSize;
        var extraRate = xp > 0 ? xp : price;

        if (ppl <= baseSize) {
          return baseAmt;
        }
        return baseAmt + (ppl - baseSize) * extraRate;
      }

      if (price > 0 && ppl > 0) {
        return ppl * price;
      }

      return null;
    }

    function guestBreakdown(pa) {
      var a = pa.adults;
      var c = pa.children;
      var aLabel = a !== 1 ? 'adults' : 'adult';
      var cLabel = c !== 1 ? 'children' : 'child';
      return (
        String(a) + ' ' + aLabel + ', ' + String(c) + ' ' + cLabel
      );
    }

    function syncExtras() {
      var pa = readParty();
      if (partyLineEl) {
        var n = pa.adults + pa.children;
        partyLineEl.textContent = n === 1 ? '1 traveller.' : String(n) + ' travellers.';
      }
      var est = computeEstimate(pa);
      if (!estWrapEl || !estAmtEl) return;
      if (est != null && est > 0) {
        estWrapEl.hidden = false;
        estAmtEl.textContent = formatMoney(est);
      } else {
        estWrapEl.hidden = true;
        estAmtEl.textContent = '\u2014';
      }
    }

    /** Inline calendar in the booking column (no modal). */
    function initBookingCalendar() {
      var trig = shell.querySelector('[data-rfp-date-trigger]');
      var panel = shell.querySelector('[data-rfp-calendar-panel]');
      var hid = shell.querySelector('input[name="preferred_date"]');
      var sum = shell.querySelector('[data-rfp-date-summary]');
      var titleEl = shell.querySelector('[data-rfp-cal-title]');
      var grid = shell.querySelector('[data-rfp-cal-grid]');
      var wkRow = shell.querySelector('.rfp-bms__calendar-weekdays');
      var prevBtn = shell.querySelector('[data-rfp-cal-prev]');
      var nextBtn = shell.querySelector('[data-rfp-cal-next]');

      var minIso = String(shell.dataset.rfpMinDate || '').trim();
      function parseLocalParts(iso) {
        var p = String(iso || '').split(/\D/);
        if (p.length < 3) return null;
        var y = parseInt(p[0], 10);
        var m = parseInt(p[1], 10) - 1;
        var d = parseInt(p[2], 10);
        var dt = new Date(y, m, d);
        if (
          isNaN(dt.getTime()) ||
          dt.getFullYear() !== y ||
          dt.getMonth() !== m ||
          dt.getDate() !== d
        ) {
          return null;
        }
        return dt;
      }

      function toIso(dt) {
        var y = dt.getFullYear();
        var mm = dt.getMonth() + 1;
        var dd = dt.getDate();
        return (
          y +
          '-' +
          (mm < 10 ? '0' : '') +
          mm +
          '-' +
          (dd < 10 ? '0' : '') +
          dd
        );
      }

      var emptyDateMsg =
        String(shell.dataset.rfpDateEmptyMsg || '').trim() || 'Tap to choose a date';

      var minD = parseLocalParts(minIso) || new Date();
      minD.setHours(12, 0, 0, 0);

      function flushSummary() {
        if (!sum) return;
        var iso = hid ? String(hid.value || '').trim() : '';
        if (!iso) {
          sum.textContent = emptyDateMsg;
          return;
        }
        sum.textContent = formatDateLabel(iso);
      }

      var viewY = minD.getFullYear();
      var viewM = minD.getMonth();

      function fillWeekdaysOnce() {
        if (!wkRow || wkRow.childElementCount) return;
        for (var w = 0; w < 7; w++) {
          var wd = new Date(2024, 0, 1 + w);
          var cell = document.createElement('span');
          cell.textContent = wd.toLocaleDateString(undefined, { weekday: 'short' }).slice(0, 3);
          wkRow.appendChild(cell);
        }
      }

      fillWeekdaysOnce();

      function monthKey(y, mo) {
        return y * 12 + mo;
      }

      function isPrevLocked() {
        return monthKey(viewY, viewM) <= monthKey(minD.getFullYear(), minD.getMonth());
      }

      function syncViewFromHidden() {
        var v = hid ? String(hid.value || '').trim() : '';
        var d = parseLocalParts(v);
        if (!d) {
          viewY = minD.getFullYear();
          viewM = minD.getMonth();
          return;
        }
        viewY = d.getFullYear();
        viewM = d.getMonth();
      }

      function padMonthStart(year, mo) {
        var jsDay = new Date(year, mo, 1).getDay();
        return jsDay === 0 ? 6 : jsDay - 1;
      }

      function daysInMonth(year, mo) {
        return new Date(year, mo + 1, 0).getDate();
      }

      function renderCal() {
        if (!grid || !titleEl) return;
        titleEl.textContent = new Date(viewY, viewM, 1).toLocaleDateString(undefined, {
          month: 'long',
          year: 'numeric',
        });
        if (prevBtn) prevBtn.disabled = isPrevLocked();

        var dim = daysInMonth(viewY, viewM);
        var pad = padMonthStart(viewY, viewM);
        var sel = hid ? String(hid.value || '').trim() : '';

        grid.innerHTML = '';
        var totalCells = 42;
        var dayNum = 1;

        for (var i = 0; i < totalCells; i++) {
          var cell = document.createElement('div');
          cell.className = 'rfp-bms__calendar-cell';

          if (i < pad || dayNum > dim) {
            cell.classList.add('rfp-bms__calendar-cell--empty');
            cell.appendChild(document.createElement('span'));
            grid.appendChild(cell);
            continue;
          }

          var iso = toIso(new Date(viewY, viewM, dayNum));
          var disabled = iso < minIso;
          var isSel = sel && sel === iso;

          if (disabled) {
            var sp = document.createElement('span');
            sp.className = 'rfp-bms__calendar-day rfp-bms__calendar-day--disabled';
            sp.textContent = String(dayNum);
            sp.setAttribute('aria-disabled', 'true');
            cell.appendChild(sp);
          } else {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'rfp-bms__calendar-day';
            if (isSel) btn.classList.add('rfp-bms__calendar-day--selected');
            btn.textContent = String(dayNum);
            btn.setAttribute('aria-pressed', isSel ? 'true' : 'false');
            btn.setAttribute('aria-label', iso);
            (function (isoLocal) {
              btn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (hid) hid.value = isoLocal;
                flushSummary();
                closePanel();
              });
            })(iso);
            cell.appendChild(btn);
          }
          dayNum += 1;
          grid.appendChild(cell);
        }
      }

      function openPanel() {
        if (!panel) return;
        syncViewFromHidden();
        renderCal();
        panel.hidden = false;
        if (trig) trig.setAttribute('aria-expanded', 'true');
        try {
          panel.focus();
        } catch (_) {}
      }

      function closePanel() {
        if (!panel) return;
        panel.hidden = true;
        if (trig) trig.setAttribute('aria-expanded', 'false');
      }

      function docClick(ev) {
        if (!panel || panel.hidden) return;
        var n = ev.target;
        if (trig && trig.contains(n)) return;
        if (panel.contains(n)) return;
        closePanel();
      }

      if (!trig || !panel || !hid || !grid) {
        flushSummary();
        return;
      }

      document.addEventListener('click', docClick);
      shell.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape' || !panel || panel.hidden) return;
        closePanel();
        try {
          if (trig) trig.focus();
        } catch (_) {}
      });

      trig.addEventListener('click', function (e) {
        e.stopPropagation();
        if (panel.hidden) {
          openPanel();
        } else {
          closePanel();
        }
      });

      if (prevBtn) {
        prevBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          if (isPrevLocked()) return;
          viewM -= 1;
          if (viewM < 0) {
            viewM = 11;
            viewY -= 1;
          }
          renderCal();
        });
      }

      if (nextBtn) {
        nextBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          viewM += 1;
          if (viewM > 11) {
            viewM = 0;
            viewY += 1;
          }
          renderCal();
        });
      }

      flushSummary();
    }

    /** Stepper buttons */
    function initSteppers() {
      shell.querySelectorAll('[data-rfp-stepper-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var key = btn.getAttribute('data-rfp-stepper-target');
          var delta = parseInt(btn.getAttribute('data-rfp-stepper-delta') || '0', 10) || 0;
          if (!delta || ('adults' !== key && 'children' !== key)) return;
          clearError();
          var pa = readParty();
          var a = pa.adults;
          var c = pa.children;
          if ('adults' === key) {
            a += delta;
          } else {
            c += delta;
          }
          writeParty(stabilizeParty(a, c));
        });
      });
    }

    function updateUI() {
      steps.forEach(function (step, i) {
        step.hidden = i !== current;
      });
      dots.forEach(function (dot, i) {
        if (i <= current) {
          dot.classList.add('rfp-bms__dot--done');
        } else {
          dot.classList.remove('rfp-bms__dot--done');
        }
      });
      if (labelEl) {
        labelEl.textContent =
          'Step ' +
          (current + 1) +
          ' of ' +
          steps.length +
          ' \u00b7 ' +
          (stepLabels[current] || '');
      }
      var isLast = current === steps.length - 1;
      if (backBtn) backBtn.hidden = current === 0;
      if (nextBtn) nextBtn.hidden = isLast;
      if (submitBtn) submitBtn.hidden = !isLast;
    }

    function validateStep(idx) {
      if (idx === 0) {
        var dateInput = shell.querySelector('input[name="preferred_date"]');
        if (!dateInput || !String(dateInput.value || '').trim()) {
          showError('Please choose your preferred travel date.');
          var trigE = shell.querySelector('[data-rfp-date-trigger]');
          try {
            if (trigE) trigE.focus();
          } catch (_) {}
          return false;
        }
        var pa = readParty();
        if (pa.adults + pa.children < minGuest) {
          showError('Your party needs to match the minimum group size for this departure.');
          return false;
        }
        if (pa.adults + pa.children > maxParty) {
          showError(
            maxParty <= 98
              ? 'Use at most ' + maxParty + ' travellers for this tour.'
              : 'Please reduce your party size.'
          );
          return false;
        }
      }

      if (idx === 1) {
        var nameInput = form.querySelector('input[name="customer_name"]');
        if (nameInput && !String(nameInput.value || '').trim()) {
          showError('Please enter your name.');
          nameInput.focus();
          return false;
        }
        var emailInput = form.querySelector('input[name="customer_email"]');
        var email = emailInput ? String(emailInput.value || '').trim() : '';
        if (!email) {
          showError('Please enter your email address.');
          if (emailInput) emailInput.focus();
          return false;
        }
        if (!/^\S+@\S+\.\S+$/.test(email)) {
          showError('Please enter a valid email address.');
          if (emailInput) emailInput.focus();
          return false;
        }
      }

      return true;
    }

    function populateReview() {
      function rv(sel, txt) {
        var el = form.querySelector(sel);
        if (!el) return;
        el.textContent = txt !== undefined && txt !== '' ? txt : '\u2014';
      }
      function showRow(which, yes) {
        var row = form.querySelector('[data-rfp-rv-row="' + which + '"]');
        if (!row) return;
        row.hidden = !yes;
      }

      var dateInput = shell.querySelector('input[name="preferred_date"]');
      var pa = readParty();
      var nameInput = form.querySelector('input[name="customer_name"]');
      var emailInput = form.querySelector('input[name="customer_email"]');
      var phoneInput = form.querySelector('input[name="customer_phone"]');
      var notesInput = form.querySelector('textarea[name="message"]');

      var iso = dateInput ? String(dateInput.value || '').trim() : '';
      rv('[data-rfp-rv="date"]', iso ? formatDateLabel(iso) : '');
      rv('[data-rfp-rv="guests"]', guestBreakdown(pa));

      var est = computeEstimate(pa);
      var showTot = est != null && est > 0;
      showRow('total', showTot);
      if (showTot) rv('[data-rfp-rv="total"]', formatMoney(est));

      var nm = nameInput ? String(nameInput.value || '').trim() : '';
      var em = emailInput ? String(emailInput.value || '').trim() : '';
      var ph = phoneInput ? String(phoneInput.value || '').trim() : '';
      var contact = [nm, em, ph].filter(Boolean).join('\n');
      rv('[data-rfp-rv="contact"]', contact);

      var msgTxt = notesInput ? String(notesInput.value || '').trim() : '';
      showRow('notes', !!msgTxt);
      if (msgTxt) rv('[data-rfp-rv="message"]', msgTxt);
    }

    initBookingCalendar();
    initSteppers();
    writeParty(readParty());

    if (backBtn) {
      backBtn.addEventListener('click', function () {
        clearError();
        current = Math.max(0, current - 1);
        updateUI();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        if (!validateStep(current)) return;
        clearError();
        current = Math.min(steps.length - 1, current + 1);
        if (current === steps.length - 1) {
          populateReview();
        }
        updateUI();
      });
    }

    form.addEventListener('submit', function (e) {
      if (current !== steps.length - 1) {
        e.preventDefault();
        return;
      }
      if (!validateStep(0) || !validateStep(1)) {
        e.preventDefault();
        return;
      }
    });

    updateUI();
  }

  function initDynamicFormMultiStep(form) {
    var steps = Array.prototype.slice.call(form.querySelectorAll('[data-rfp-form-step]'));
    if (!steps.length) return;

    var current = 0;
    var backBtn = form.querySelector('[data-rfp-form-back]');
    var nextBtn = form.querySelector('[data-rfp-form-next]');
    var submitBtn = form.querySelector('[data-rfp-form-submit]');
    var label = form.querySelector('[data-rfp-form-step-label]');
    var progress = form.querySelector('[data-rfp-form-progress]');

    function currentControls() {
      return Array.prototype.slice.call(steps[current].querySelectorAll('input, textarea, select'));
    }

    function validateCurrent() {
      var controls = currentControls();
      for (var i = 0; i < controls.length; i += 1) {
        if (!controls[i].checkValidity()) {
          controls[i].reportValidity();
          return false;
        }
      }
      return true;
    }

    function show(index) {
      current = Math.max(0, Math.min(steps.length - 1, index));
      steps.forEach(function (step, idx) {
        step.hidden = idx !== current;
      });
      if (backBtn) backBtn.disabled = current === 0;
      if (nextBtn) nextBtn.hidden = current === steps.length - 1;
      if (submitBtn) submitBtn.hidden = current !== steps.length - 1;
      if (label) label.textContent = 'Step ' + String(current + 1) + ' of ' + String(steps.length);
      if (progress) progress.style.width = String(((current + 1) / steps.length) * 100) + '%';
      var first = currentControls()[0];
      if (first && current > 0) first.focus({ preventScroll: true });
    }

    if (backBtn) backBtn.addEventListener('click', function () { show(current - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function () {
      if (!validateCurrent()) return;
      show(current + 1);
    });

    show(0);
  }

  function initPackagesBrowser(browser) {
    var search = browser.querySelector('[data-rfp-filter="search"]');
    var location = browser.querySelector('[data-rfp-filter="location"]');
    var category = browser.querySelector('[data-rfp-filter="category"]');
    var price = browser.querySelector('[data-rfp-filter="price"]');
    var priceLabel = browser.querySelector('[data-rfp-price-label]');
    var reset = browser.querySelector('[data-rfp-reset-filters]');
    var cards = Array.prototype.slice.call(browser.querySelectorAll('[data-rfp-package]'));
    var count = browser.querySelector('[data-rfp-result-count]');
    var empty = browser.querySelector('[data-rfp-no-results]');

    function value(input) {
      return input ? String(input.value || '').toLowerCase().trim() : '';
    }

    function apply() {
      var q = value(search);
      var loc = value(location);
      var cat = value(category);
      var max = price ? Number(price.value || 0) : 0;
      var visible = 0;

      if (priceLabel && price) {
        priceLabel.textContent = price.value;
      }

      cards.forEach(function (card) {
        var cardPrice = Number(card.getAttribute('data-price') || 0);
        var ok = true;
        if (q && card.getAttribute('data-title').indexOf(q) === -1) ok = false;
        if (loc && card.getAttribute('data-location') !== loc) ok = false;
        if (cat && card.getAttribute('data-category') !== cat) ok = false;
        if (price && max && cardPrice > max) ok = false;
        card.hidden = !ok;
        if (ok) visible += 1;
      });

      if (count) count.textContent = String(visible);
      if (empty) empty.hidden = visible !== 0;
    }

    [search, location, category, price].forEach(function (input) {
      if (input) input.addEventListener('input', apply);
      if (input) input.addEventListener('change', apply);
    });

    if (reset) {
      reset.addEventListener('click', function () {
        if (search) search.value = '';
        if (location) location.value = '';
        if (category) category.value = '';
        if (price) price.value = price.getAttribute('max') || price.value;
        apply();
      });
    }

    apply();
  }

  function initClickableCards() {
    document.addEventListener('click', function (event) {
      var target = event.target;
      if (!target || !target.closest) return;
      if (target.closest('a, button, input, select, textarea, summary')) return;

      var card = target.closest('.rfp-card, .rfp-user-card');
      if (!card) return;

      var link = card.querySelector('a[href*="/tours/"], a[href*="/destinations/"]');
      if (!link || !link.href) return;

      window.location.href = link.href;
    });
  }

  function stretchRelayForgePages() {
    if (!document.body || !document.body.classList.contains('rfp-full-bleed-page')) return;

    var viewportWidth = document.documentElement.clientWidth || window.innerWidth || 0;
    if (!viewportWidth) return;

    document.querySelectorAll('main.rfp-detail').forEach(function (main) {
      main.style.setProperty('margin-left', '0px', 'important');
      main.style.setProperty('width', '100%', 'important');
      main.style.setProperty('max-width', 'none', 'important');

      var rect = main.getBoundingClientRect();
      var left = rect.left || 0;

      main.style.setProperty('width', viewportWidth + 'px', 'important');
      main.style.setProperty('max-width', viewportWidth + 'px', 'important');
      main.style.setProperty('margin-left', -left + 'px', 'important');
      main.style.setProperty('margin-right', '0px', 'important');
    });
  }

  function initFullBleedPages() {
    stretchRelayForgePages();
    window.addEventListener('load', stretchRelayForgePages);
    window.addEventListener('resize', function () {
      window.requestAnimationFrame(stretchRelayForgePages);
    });
  }

  function initThemeFontInheritance() {
    if (!document.body || !document.body.classList.contains('rfp-page')) return;

    var bodyFont = window.getComputedStyle(document.body).fontFamily;
    var probe = document.createElement('h1');
    probe.textContent = 'RelayForge';
    probe.style.cssText = 'position:absolute;visibility:hidden;pointer-events:none;left:-9999px;top:-9999px;margin:0;padding:0;';
    document.body.appendChild(probe);
    var headingFont = window.getComputedStyle(probe).fontFamily;
    document.body.removeChild(probe);

    if (bodyFont) {
      document.body.style.setProperty('--rfp-font-family', bodyFont);
    }
    if (headingFont) {
      document.body.style.setProperty('--rfp-font-title', headingFont);
    }
  }

  function initGalleryLightbox() {
    var galleries = Array.prototype.slice.call(document.querySelectorAll('.rfp-tour-gallery'));
    if (!galleries.length) return;

    var overlay = document.createElement('div');
    overlay.className = 'rfp-lightbox';
    overlay.hidden = true;
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Image gallery');
    overlay.innerHTML = '' +
      '<button type="button" class="rfp-lightbox__close" aria-label="Close image gallery">&times;</button>' +
      '<button type="button" class="rfp-lightbox__nav rfp-lightbox__nav--prev" aria-label="Previous image">&#8249;</button>' +
      '<figure class="rfp-lightbox__figure">' +
        '<img class="rfp-lightbox__image" alt="" />' +
        '<figcaption class="rfp-lightbox__caption"></figcaption>' +
      '</figure>' +
      '<button type="button" class="rfp-lightbox__nav rfp-lightbox__nav--next" aria-label="Next image">&#8250;</button>';
    document.body.appendChild(overlay);

    var image = overlay.querySelector('.rfp-lightbox__image');
    var caption = overlay.querySelector('.rfp-lightbox__caption');
    var closeBtn = overlay.querySelector('.rfp-lightbox__close');
    var prevBtn = overlay.querySelector('.rfp-lightbox__nav--prev');
    var nextBtn = overlay.querySelector('.rfp-lightbox__nav--next');
    var items = [];
    var current = 0;
    var previousFocus = null;
    var touchStartX = 0;
    var touchStartY = 0;

    function show(index) {
      if (!items.length || !image || !caption) return;
      current = (index + items.length) % items.length;
      var item = items[current];
      image.src = item.src;
      image.alt = item.alt || '';
      caption.textContent = items.length > 1 ? String(current + 1) + ' / ' + String(items.length) : '';
      if (prevBtn) prevBtn.hidden = items.length < 2;
      if (nextBtn) nextBtn.hidden = items.length < 2;
    }

    function open(gallery, img) {
      items = Array.prototype.slice.call(gallery.querySelectorAll('img[src]')).map(function (node) {
        return { src: node.currentSrc || node.src, alt: node.getAttribute('alt') || '' };
      });
      current = Math.max(0, Array.prototype.indexOf.call(gallery.querySelectorAll('img[src]'), img));
      previousFocus = document.activeElement;
      show(current);
      overlay.hidden = false;
      document.body.classList.add('rfp-lightbox-open');
      if (closeBtn) closeBtn.focus();
    }

    function close() {
      overlay.hidden = true;
      document.body.classList.remove('rfp-lightbox-open');
      if (image) image.removeAttribute('src');
      if (previousFocus && previousFocus.focus) previousFocus.focus();
    }

    galleries.forEach(function (gallery) {
      gallery.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || target.tagName !== 'IMG') return;
        open(gallery, target);
      });
      gallery.querySelectorAll('img').forEach(function (img) {
        img.setAttribute('tabindex', '0');
        img.setAttribute('role', 'button');
        img.addEventListener('keydown', function (event) {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            open(gallery, img);
          }
        });
      });
    });

    if (closeBtn) closeBtn.addEventListener('click', close);
    if (prevBtn) prevBtn.addEventListener('click', function () { show(current - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { show(current + 1); });
    overlay.addEventListener('click', function (event) {
      if (event.target === overlay) close();
    });
    overlay.addEventListener('touchstart', function (event) {
      if (!event.touches || !event.touches.length) return;
      touchStartX = event.touches[0].clientX;
      touchStartY = event.touches[0].clientY;
    }, { passive: true });
    overlay.addEventListener('touchend', function (event) {
      if (!event.changedTouches || !event.changedTouches.length) return;
      var dx = event.changedTouches[0].clientX - touchStartX;
      var dy = event.changedTouches[0].clientY - touchStartY;
      if (Math.abs(dx) < 40 || Math.abs(dx) < Math.abs(dy)) return;
      show(dx < 0 ? current + 1 : current - 1);
    }, { passive: true });

    document.addEventListener('keydown', function (event) {
      if (overlay.hidden) return;
      if (event.key === 'Escape') close();
      if (event.key === 'ArrowLeft') show(current - 1);
      if (event.key === 'ArrowRight') show(current + 1);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initThemeFontInheritance();
    initFullBleedPages();
    document.querySelectorAll('[data-rfp-dynamic-form="multistep"]').forEach(initDynamicFormMultiStep);
    document.querySelectorAll('[data-rfp-packages-browser]').forEach(initPackagesBrowser);
    document.querySelectorAll('.rfp-bms').forEach(initBookingMultiStep);
    initGalleryLightbox();
    initClickableCards();
  });
})();
