(function () {
  var root = 'relayforge_wp_settings';

  function field(name) {
    return document.querySelector('[name="' + root + '[' + name + ']"]');
  }

  function value(name, fallback) {
    var input = field(name);
    return input ? input.value : fallback;
  }

  function presetCssMap() {
    if (
      typeof window.relayforgeFontPresetCss !== 'undefined' &&
      window.relayforgeFontPresetCss &&
      typeof window.relayforgeFontPresetCss === 'object'
    ) {
      return window.relayforgeFontPresetCss;
    }
    return {};
  }

  /** @param {'heading'|'body'} slot */
  function resolvedFontCss(slot) {
    var mode = value('cards_font_' + slot + '_mode', 'inherit');
    if (!mode || mode === 'inherit') {
      return '';
    }
    var presets = presetCssMap();
    if (mode === 'preset') {
      var slug = value('cards_font_' + slot + '_preset', '');
      return slug && presets[slug] ? presets[slug] : '';
    }
    if (mode === 'custom') {
      return value('cards_font_' + slot + '_custom', '').trim();
    }
    if (mode === 'upload') {
      var nm = value('cards_font_' + slot + '_face', '').trim();
      return nm ? '"' + nm.replace(/["]/g, '') + '", sans-serif' : 'system-ui, sans-serif';
    }
    return '';
  }

  function esc(text) {
    return String(text || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function applyConditionals(template, data) {
    return template.replace(/\{\{#if\s+([a-zA-Z0-9_]+)\}\}([\s\S]*?)\{\{\/if\}\}/g, function (_, key, content) {
      return data[key] ? content : '';
    });
  }

  function fill(template, data) {
    template = applyConditionals(template, data);
    Object.keys(data).forEach(function (key) {
      template = template.split('{{' + key + '}}').join(esc(data[key]));
    });
    return template;
  }

  function templates(type) {
    var titleKey = type === 'tour' ? 'title' : 'name';
    var descKey = type === 'tour' ? 'location' : 'description';
    var defaultTour =
      '<article class="rfp-user-card rfp-user-card--default">' +
      '<img class="rfp-user-card__image" src="{{image_url}}" alt="{{' +
      titleKey +
      '}}" />' +
      '<div class="rfp-user-card__body">' +
      '<h3>{{' +
      titleKey +
      '}}</h3>{{#if location}}<p class="rfp-user-card__meta">{{location}}</p>{{/if}}{{#if price}}<p class="rfp-user-card__price">{{price}}</p>{{/if}}' +
      '<a class="rfp-button" href="{{link}}">{{cta_text}}</a></div></article>';
    var defaultDestination =
      '<article class="rfp-user-card rfp-user-card--default">' +
      '<img class="rfp-user-card__image" src="{{image_url}}" alt="{{' +
      titleKey +
      '}}" />' +
      '<div class="rfp-user-card__body">' +
      '<h3>{{' +
      titleKey +
      '}}</h3>{{#if description}}<p>{{description}}</p>{{/if}}' +
      '<a class="rfp-button" href="{{link}}">{{cta_text}}</a></div></article>';
    return {
      default: type === 'tour' ? defaultTour : defaultDestination,
      minimal: '<article class="rfp-user-card rfp-user-card--minimal"><h3>{{' + titleKey + '}}</h3><p>{{' + descKey + '}}</p>{{#if price}}<p class="rfp-user-card__price">{{price}}</p>{{/if}}<a class="rfp-button" href="{{link}}">{{cta_text}}</a></article>',
      split: '<article class="rfp-user-card rfp-user-card--split"><img class="rfp-user-card__image" src="{{image_url}}" alt="{{' + titleKey + '}}" /><div class="rfp-user-card__body"><h3>{{' + titleKey + '}}</h3><p>{{' + descKey + '}}</p><a class="rfp-button" href="{{link}}">{{cta_text}}</a></div></article>',
      overlay: '<article class="rfp-user-card rfp-user-card--overlay"><img class="rfp-user-card__image" src="{{image_url}}" alt="{{' + titleKey + '}}" /><div class="rfp-user-card__overlay"><h3>{{' + titleKey + '}}</h3><p>{{' + descKey + '}}</p><a class="rfp-button" href="{{link}}">{{cta_text}}</a></div></article>',
      magazine: '<article class="rfp-user-card rfp-user-card--magazine"><img class="rfp-user-card__image" src="{{image_url}}" alt="{{' + titleKey + '}}" /><div class="rfp-user-card__body"><span class="rfp-user-card__badge">{{badge}}</span><h3>{{' + titleKey + '}}</h3><p class="rfp-user-card__meta">{{' + descKey + '}}</p><a class="rfp-button" href="{{link}}">{{cta_text}}</a></div></article>',
      luxury: '<article class="rfp-user-card rfp-user-card--luxury"><div class="rfp-user-card__body"><p class="rfp-user-card__eyebrow">{{eyebrow}}</p><h3>{{' + titleKey + '}}</h3><p class="rfp-user-card__price">{{price}}</p><a class="rfp-button" href="{{link}}">{{cta_text}}</a></div></article>',
      adventure: '<article class="rfp-user-card rfp-user-card--adventure"><img class="rfp-user-card__image" src="{{image_url}}" alt="{{' + titleKey + '}}" /><div class="rfp-user-card__body"><h3>{{' + titleKey + '}}</h3><p>{{' + descKey + '}}</p><div class="rfp-user-card__chips"><span>{{duration}}</span><span>{{badge}}</span></div><a class="rfp-button" href="{{link}}">{{cta_text}}</a></div></article>',
      compact: '<article class="rfp-user-card rfp-user-card--compact"><h3>{{' + titleKey + '}}</h3><p>{{' + descKey + '}}</p><a class="rfp-button" href="{{link}}">{{cta_text}}</a></article>',
      feature: '<article class="rfp-user-card rfp-user-card--feature"><img class="rfp-user-card__image" src="{{image_url}}" alt="{{' + titleKey + '}}" /><div class="rfp-user-card__body"><h3>{{' + titleKey + '}}</h3><p>{{' + descKey + '}}</p>{{#if price}}<p class="rfp-user-card__price">{{price}}</p>{{/if}}<a class="rfp-button" href="{{link}}">{{cta_text}}</a></div></article>'
    };
  }

  function previewData() {
    var image = value('cards_image_fallback', '') || 'https://picsum.photos/720/460';
    var showCurrency = value('cards_show_currency', 'no') === 'yes';
    var currencyCode = 'USD';
    var rawPrice = '249.00';
    var displayPrice = showCurrency ? currencyCode + ' ' + rawPrice : rawPrice;
    return {
      tour: {
        title: 'Ocean Escape Tour',
        location: 'Mombasa, Kenya',
        image_url: image,
        price: displayPrice,
        from_price: displayPrice,
        cta_text: 'View tour',
        link: '/relayforge-demo/tour/',
        duration: '3 days',
        badge: 'Top Rated',
        eyebrow: 'Private Experience'
      },
      destination: {
        name: 'Coastal Paradise',
        description: 'Sunny beach destination with curated tours.',
        image_url: image,
        price: '',
        from_price: '',
        cta_text: 'View destination',
        link: '/relayforge-demo/destination/',
        duration: '12 tours',
        badge: 'Popular',
        eyebrow: 'Destination'
      }
    };
  }

  function customTemplate(type) {
    var mode = value(type === 'tour' ? 'tours_card_template' : 'destinations_card_template', 'default');
    if (mode !== 'custom') return '';
    return value(type === 'tour' ? 'tours_card_custom_html' : 'destinations_card_custom_html', '');
  }

  function renderPreview() {
    var tourTarget = document.getElementById('relayforge-tour-preview');
    var destinationTarget = document.getElementById('relayforge-destination-preview');
    var detailTarget = document.getElementById('relayforge-tour-detail-preview');
    var destinationDetailTarget = document.getElementById('relayforge-destination-detail-preview');
    var styleTarget = document.getElementById('relayforge-preview-custom-css');
    if (!tourTarget || !destinationTarget) return;

    var data = previewData();
    var tourInput = document.getElementById('relayforge_tours_card_template');
    var destInput = document.getElementById('relayforge_destinations_card_template');
    var tourMode = (tourInput ? tourInput.value : null) || value('tours_card_template', 'default');
    var destinationMode = (destInput ? destInput.value : null) || value('destinations_card_template', 'default');
    if (value('cards_show_price', 'yes') === 'no') {
      data.tour.price = '';
    }
    var tourTemplate = customTemplate('tour') || templates('tour')[tourMode] || templates('tour').default;
    var destinationTemplate = customTemplate('destination') || templates('destination')[destinationMode] || templates('destination').default;

    var showCurrency2 = value('cards_show_currency', 'no') === 'yes';
    var price2 = showCurrency2 ? 'USD 799.00' : '799.00';
    tourTarget.innerHTML = fill(tourTemplate, data.tour) + fill(tourTemplate, Object.assign({}, data.tour, { title: 'Safari Highlights', location: 'Maasai Mara', image_url: 'https://picsum.photos/721/460', price: price2, from_price: price2 }));
    destinationTarget.innerHTML = fill(destinationTemplate, data.destination) + fill(destinationTemplate, Object.assign({}, data.destination, { name: 'Mountain Retreat', description: 'Fresh air, hiking trails, and boutique stays.', image_url: 'https://picsum.photos/722/460' }));

    if (detailTarget) {
      detailTarget.innerHTML = tourDetailPreview(data.tour);
    }

    if (destinationDetailTarget) {
      destinationDetailTarget.innerHTML = destinationDetailPreview(data.destination);
    }

    var radius = value('cards_radius', '16');
    var padding = value('cards_spacing_padding', '20');
    var titleSize = value('cards_typography_title_size', '18');
    var ratio = value('cards_image_aspect_ratio', '16 / 10');
    var position = value('cards_image_object_position', 'center center');
    var customCss = value('custom_cards_css', '');
    var colorAccent = value('cards_color_accent', '');
    var colorSurface = value('cards_color_surface', '');
    var colorText = value('cards_color_text', '');
    var fontTitle = resolvedFontCss('heading');
    var fontBody = resolvedFontCss('body');

    var colorAndLayoutVars = '';
    if (colorAccent) colorAndLayoutVars += '--rfp-accent:' + colorAccent + ';--rf-theme-link:' + colorAccent + ';--rfp-hero-overlay:linear-gradient(180deg,color-mix(in srgb,' + colorAccent + ' 56%,transparent),color-mix(in srgb,' + colorAccent + ' 78%,transparent));';
    if (colorSurface) colorAndLayoutVars += '--rfp-surface:' + colorSurface + ';';
    if (colorText) colorAndLayoutVars += '--rfp-text:' + colorText + ';--rf-theme-text:' + colorText + ';';
    colorAndLayoutVars += '--rf-card-radius:' + radius + 'px;--rf-card-padding:' + padding + 'px;--rf-card-title-size:' + titleSize + 'px;--rf-card-image-ratio:' + ratio + ';--rf-card-image-position:' + position + ';';

    var fontScopedVars = '';
    if (fontTitle) fontScopedVars += '--rfp-font-title:' + fontTitle + ';';
    if (fontBody) fontScopedVars += '--rfp-font-family:' + fontBody + ';';

    if (styleTarget) {
      styleTarget.textContent =
        '.relayforge-live-preview{' + colorAndLayoutVars + '}' +
        (fontScopedVars ? '.relayforge-live-preview .rfp.rfp-typography-cards,.relayforge-live-preview .rfp-detail .rfp-typography-cards{' + fontScopedVars + '}' : '') +
        customCss;
    }

    document.querySelectorAll('[data-template-option]').forEach(function (card) {
      var key = card.getAttribute('data-template-option');
      var tourSel = key === tourMode;
      var destSel = key === destinationMode;
      card.classList.toggle('is-selected-tour', tourSel);
      card.classList.toggle('is-selected-destination', destSel);
      var note = card.querySelector('.relayforge-template-option__selected-note');
      if (note) {
        if (!tourSel && !destSel) {
          note.hidden = true;
          note.textContent = '';
        } else {
          note.hidden = false;
          if (tourSel && destSel) {
            note.textContent = 'Selected · Tours & destinations';
          } else if (tourSel) {
            note.textContent = 'Selected · Tours';
          } else {
            note.textContent = 'Selected · Destinations';
          }
        }
      }
    });

    renderGalleryPreviews();
  }

  function renderGalleryPreviews() {
    var data = previewData();
    var tourMode = (document.getElementById('relayforge_tours_card_template') || {}).value || value('tours_card_template', 'default');
    var destinationMode = (document.getElementById('relayforge_destinations_card_template') || {}).value || value('destinations_card_template', 'default');
    var tourTemplates = templates('tour');
    var destinationTemplates = templates('destination');
    document.querySelectorAll('[data-template-preview]').forEach(function (container) {
      var key = container.getAttribute('data-template-preview');
      var isDestinationSelection = key === destinationMode && key !== tourMode;
      var tmpl = isDestinationSelection
        ? (destinationTemplates[key] || destinationTemplates.default)
        : (tourTemplates[key] || tourTemplates.default);
      container.innerHTML = fill(tmpl, isDestinationSelection ? data.destination : data.tour);
    });
  }

  function tourDetailPreview(tour) {
    var price = tour.price ? '<span>' + esc(tour.price) + '</span>' : '';
    var priceFact = tour.price ? '<div><span>Price</span><strong>' + esc(tour.price) + '</strong></div>' : '';
    var bookingPrice = tour.price ? '<p class="rfp-tour-booking__price">From ' + esc(tour.price) + '</p>' : '';
    return '' +
      '<main class="rfp-detail rfp-tour-detail relayforge-preview-detail">' +
        '<section class="rfp-tour-hero rfp-typography-cards" style="background-image:var(--rfp-hero-overlay), url(' + esc(tour.image_url) + ')">' +
          '<div class="rfp-tour-hero__inner"><p class="rfp-eyebrow">Tour Package</p><h1>' + esc(tour.title) + '</h1><div class="rfp-tour-hero__meta"><span>' + esc(tour.location) + '</span><span>' + esc(tour.duration) + '</span>' + price + '</div></div>' +
        '</section>' +
        '<nav class="rfp-tour-tabs rfp-typography-cards"><a href="#">Gallery</a><a href="#">Itinerary</a><a href="#">Tour info</a><a href="#">Availability</a><a href="#">FAQs</a></nav>' +
        '<div class="rfp-tour-layout">' +
          '<div class="rfp-tour-main rfp-typography-cards">' +
            '<section class="rfp-tour-panel"><h2>Gallery</h2><div class="rfp-tour-gallery"><img src="https://picsum.photos/723/460" alt=""><img src="https://picsum.photos/724/460" alt=""><img src="https://picsum.photos/725/460" alt=""></div></section>' +
            '<section class="rfp-tour-panel"><h2>Itinerary</h2><div class="rfp-itinerary-list"><details class="rfp-itinerary-item" open><summary>Day 1: Arrival</summary><p>Meet your guide and settle into the trip.</p></details><details class="rfp-itinerary-item"><summary>Day 2: Main experience</summary><p>Enjoy the core package activities and local stops.</p></details></div></section>' +
            '<section class="rfp-tour-panel"><h2>Tour info</h2><div class="rfp-tour-facts"><div><span>Duration</span><strong>' + esc(tour.duration) + '</strong></div><div><span>Tour type</span><strong>Marine adventure</strong></div><div><span>Location</span><strong>' + esc(tour.location) + '</strong></div>' + priceFact + '</div><div class="rfp-tour-copy"><h3>About this tour</h3><p>Experience a curated coastal trip with scenic views, local culture, and flexible booking support.</p></div></section>' +
            '<section class="rfp-tour-panel"><h2>FAQs</h2><div class="rfp-faq-list"><details class="rfp-faq-item"><summary>Can this package be customized?</summary><p>Yes. Guests can request dates, group size, and activity changes.</p></details></div></section>' +
          '</div>' +
          '<aside class="rfp-tour-booking"><div class="rfp-tour-booking__card"><p class="rfp-eyebrow">Plan this trip</p><h2>Send inquiry</h2>' + bookingPrice + '<form class="rfp-booking-form"><label>Name<input type="text"></label><label>Email<input type="email"></label><label>Preferred date<input type="date"></label><label>Guests<input type="number" value="1"></label><button class="rfp-button" type="button">Send inquiry</button></form></div></aside>' +
        '</div>' +
      '</main>';
  }

  function destinationDetailPreview(destination) {
    return '' +
      '<main class="rfp-detail rfp-tour-detail rfp-destination-detail relayforge-preview-detail">' +
        '<section class="rfp-tour-hero rfp-destination-hero rfp-typography-cards" style="background-image:var(--rfp-hero-overlay), url(' + esc(destination.image_url) + ')">' +
          '<div class="rfp-tour-hero__inner"><p class="rfp-eyebrow">Destination</p><h1>' + esc(destination.name) + '</h1><div class="rfp-tour-hero__meta"><span>Portugal</span><span>Apr-Sep</span><span>12 tours</span></div></div>' +
        '</section>' +
        '<nav class="rfp-tour-tabs rfp-typography-cards"><a href="#">Gallery</a><a href="#">Overview</a><a href="#">Destination info</a><a href="#">Tours</a><a href="#">FAQs</a></nav>' +
        '<div class="rfp-tour-layout">' +
          '<div class="rfp-tour-main rfp-typography-cards">' +
            '<section class="rfp-tour-panel"><h2>Gallery</h2><div class="rfp-tour-gallery"><img src="https://picsum.photos/728/460" alt=""><img src="https://picsum.photos/729/460" alt=""><img src="https://picsum.photos/730/460" alt=""></div></section>' +
            '<section class="rfp-tour-panel"><h2>Overview</h2><div class="rfp-tour-copy"><p>' + esc(destination.description) + '</p></div></section>' +
            '<section class="rfp-tour-panel"><h2>Destination info</h2><div class="rfp-tour-facts"><div><span>Destination</span><strong>' + esc(destination.name) + '</strong></div><div><span>Country</span><strong>Portugal</strong></div><div><span>Best time</span><strong>Apr-Sep</strong></div><div><span>Available tours</span><strong>12</strong></div></div></section>' +
            '<section class="rfp-tour-panel"><h2>Tours in this destination</h2><div class="rfp rfp-typography-cards rfp-tours-grid columns-2"><article class="rfp-user-card rfp-user-card--default"><img class="rfp-user-card__image" src="https://picsum.photos/726/460" alt=""><div class="rfp-user-card__body"><h3>Coastal Highlights</h3><p class="rfp-user-card__meta">Demo region</p><a class="rfp-button" href="/relayforge-demo/tour/">View tour</a></div></article><article class="rfp-user-card rfp-user-card--default"><img class="rfp-user-card__image" src="https://picsum.photos/727/460" alt=""><div class="rfp-user-card__body"><h3>Island Escape</h3><p class="rfp-user-card__meta">Demo region</p><a class="rfp-button" href="/relayforge-demo/tour/">View tour</a></div></article></div></section>' +
            '<section class="rfp-tour-panel"><h2>FAQs</h2><div class="rfp-faq-list"><details class="rfp-faq-item"><summary>Can I combine tours?</summary><p>Yes. The destination page can help visitors request custom packages.</p></details></div></section>' +
          '</div>' +
          '<aside class="rfp-tour-booking"><div class="rfp-tour-booking__card"><p class="rfp-eyebrow">Explore</p><h2>Plan this destination</h2><p>Ask about available tours, travel dates, group size, or custom itineraries.</p><a class="rfp-button" href="/relayforge-demo/packages/">View tours</a></div></aside>' +
        '</div>' +
      '</main>';
  }

  function relayforgeStrings() {
    var s = typeof window.relayforgeAdminStrings === 'object' && window.relayforgeAdminStrings ? window.relayforgeAdminStrings : {};
    return {
      copied: s.copied || 'Copied',
      copyFailed: s.copyFailed || 'Could not copy.',
    };
  }

  function initTabs() {
    var tabButtons = document.querySelectorAll('.relayforge-admin__tab[data-rf-tab]');
    var panels = document.querySelectorAll('.relayforge-admin__panel[data-rf-panel]');
    if (!tabButtons.length || !panels.length) {
      return;
    }

    function activateTab(tabId) {
      tabButtons.forEach(function (button) {
        var active = button.getAttribute('data-rf-tab') === tabId;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-selected', active ? 'true' : 'false');
        button.setAttribute('tabindex', active ? '0' : '-1');
      });
      panels.forEach(function (panel) {
        var active = panel.getAttribute('data-rf-panel') === tabId;
        panel.classList.toggle('is-active', active);
        panel.setAttribute('aria-hidden', active ? 'false' : 'true');
        panel.hidden = !active;
      });
      renderPreview();
    }

    tabButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        activateTab(button.getAttribute('data-rf-tab'));
      });
      button.addEventListener('keydown', function (ev) {
        var keys = ['ArrowRight', 'ArrowLeft', 'Home', 'End'];
        if (keys.indexOf(ev.key) === -1) {
          return;
        }
        var list = Array.prototype.slice.call(tabButtons);
        var i = list.indexOf(button);
        if (ev.key === 'ArrowRight') {
          i = (i + 1) % list.length;
          ev.preventDefault();
          activateTab(list[i].getAttribute('data-rf-tab'));
          list[i].focus();
        } else if (ev.key === 'ArrowLeft') {
          i = (i - 1 + list.length) % list.length;
          ev.preventDefault();
          activateTab(list[i].getAttribute('data-rf-tab'));
          list[i].focus();
        } else if (ev.key === 'Home') {
          ev.preventDefault();
          activateTab(list[0].getAttribute('data-rf-tab'));
          list[0].focus();
        } else if (ev.key === 'End') {
          ev.preventDefault();
          activateTab(list[list.length - 1].getAttribute('data-rf-tab'));
          list[list.length - 1].focus();
        }
      });
    });
  }

  function initCopyButtons() {
    document.querySelectorAll('.rf-copy-field .rf-copy-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var wrap = btn.closest('.rf-copy-field');
        if (!wrap || !wrap.getAttribute('data-rf-copy')) {
          return;
        }
        var text = wrap.getAttribute('data-rf-copy');
        var str = relayforgeStrings();
        var label = btn.textContent;
        function markDone(ok) {
          btn.classList.toggle('is-copied', ok);
          if (ok) {
            btn.textContent = str.copied;
            window.setTimeout(function () {
              btn.textContent = label;
              btn.classList.remove('is-copied');
            }, 2000);
          } else {
            window.alert(str.copyFailed);
          }
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text).then(function () { markDone(true); }).catch(function () { fallback(); });
          return;
        }
        fallback();
        function fallback() {
          try {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            markDone(true);
          } catch (_) {
            markDone(false);
          }
        }
      });
    });
  }

  function initContentTableFilters() {
    document.querySelectorAll('[data-rf-search]').forEach(function (input) {
      var scope = input.getAttribute('data-rf-search');
      var table = document.querySelector('[data-rf-table="' + scope + '"]');
      var countEl = document.querySelector('[data-rf-count="' + scope + '"]');
      if (!table) {
        return;
      }
      var rows = Array.prototype.slice.call(table.querySelectorAll('[data-rf-row]'));
      var label = countEl ? countEl.textContent.replace(/^\d+/, '').trim() : '';
      input.addEventListener('input', function () {
        var q = input.value.toLowerCase().trim();
        var visible = 0;
        rows.forEach(function (row) {
          var match = !q || (row.getAttribute('data-rf-search') || '').indexOf(q) !== -1;
          row.hidden = !match;
          if (match) {
            visible++;
          }
        });
        if (countEl) {
          countEl.textContent = visible + ' ' + label;
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initTabs();
    initCopyButtons();
    initContentTableFilters();

    var templateNames = {
      default: 'Default', minimal: 'Minimal', split: 'Split', overlay: 'Overlay',
      magazine: 'Magazine', luxury: 'Luxury', adventure: 'Adventure', compact: 'Compact', feature: 'Feature'
    };

    document.querySelectorAll('[data-template-target]').forEach(function (button) {
      button.addEventListener('click', function () {
        var isTours = button.getAttribute('data-template-target') === 'tours';
        var inputId = isTours ? 'relayforge_tours_card_template' : 'relayforge_destinations_card_template';
        var labelId = isTours ? 'relayforge-tours-selection-label' : 'relayforge-destinations-selection-label';
        var input = document.getElementById(inputId);
        var label = document.getElementById(labelId);
        var val = button.getAttribute('data-template-value');
        if (input) {
          input.value = val;
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (label) {
          label.textContent = templateNames[val] || val;
        }
      });
    });

    document.querySelectorAll('[name^="' + root + '"]').forEach(function (input) {
      input.addEventListener('input', renderPreview);
      input.addEventListener('change', renderPreview);
    });

    document.querySelectorAll('[data-preview-device]').forEach(function (button) {
      button.addEventListener('click', function () {
        document.querySelectorAll('[data-preview-device]').forEach(function (other) {
          other.classList.toggle('is-active', other === button);
        });
        var frame = document.querySelector('.relayforge-live-preview');
        if (frame) frame.setAttribute('data-preview-frame', button.getAttribute('data-preview-device'));
      });
    });

    document.querySelectorAll('.rfp-font-mode').forEach(function (sel) {
      sel.addEventListener('change', function () {
        var scope = sel.getAttribute('data-rfp-font-scope');
        var fs = sel.closest('.rfp-font-fieldset');
        if (!fs || !scope) return;
        var mode = sel.value;
        fs.querySelectorAll('.rfp-font-sub').forEach(function (sub) {
          var preset = sub.classList.contains('rfp-font-sub--preset');
          var cust = sub.classList.contains('rfp-font-sub--custom');
          var upl = sub.classList.contains('rfp-font-sub--upload');
          var show =
            (preset && mode === 'preset') ||
            (cust && mode === 'custom') ||
            (upl && mode === 'upload');
          sub.classList.toggle('hidden', !show);
        });
        renderPreview();
      });
    });

    document.querySelectorAll('.rfp-upload-font').forEach(function (btn) {
      btn.addEventListener('click', function (ev) {
        ev.preventDefault();
        if (typeof window.wp === 'undefined' || !window.wp.media) {
          window.alert('WordPress media scripts did not load. Try refreshing this page.');
          return;
        }
        var targetId = btn.getAttribute('data-target-upload');
        var inp = targetId ? document.getElementById(targetId) : null;
        var frame = window.wp.media({
          title: btn.getAttribute('data-title-label') || 'Choose a font file',
          button: { text: 'Use this file' },
          library: {},
          multiple: false
        });
        frame.on('select', function () {
          var att = frame.state().get('selection').first().toJSON();
          if (inp) {
            inp.value = att.id;
            inp.dispatchEvent(new Event('change', { bubbles: true }));
          }
          renderPreview();
        });
        frame.open();
      });
    });

    document.querySelectorAll('.rfp-color-field').forEach(function (wrap) {
      var swatch = wrap.querySelector('.rfp-color-swatch');
      var text = wrap.querySelector('input[type="text"]');
      var clear = wrap.querySelector('.rfp-color-clear');
      if (!swatch || !text) return;
      swatch.addEventListener('input', function () {
        text.value = swatch.value;
        text.dispatchEvent(new Event('input', { bubbles: true }));
      });
      text.addEventListener('input', function () {
        if (/^#[0-9a-fA-F]{6}$/.test(text.value)) swatch.value = text.value;
      });
      if (clear) {
        clear.addEventListener('click', function () {
          text.value = '';
          swatch.value = '#aaaaaa';
          text.dispatchEvent(new Event('input', { bubbles: true }));
        });
      }
    });

    renderPreview();
  });
})();
