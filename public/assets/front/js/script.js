/* =========================================================
   odiscount - script.js
   - Année automatique dans le footer
   - Recherche (catégorie + lieu)
   - Clic sur une catégorie : présélection dans le formulaire
   - Menu : élément actif et défilement sur mobile
   - Images : repli propre si une image ne charge pas
   ========================================================= */

(function () {
  'use strict';

  /* ---------- Configuration ---------- */
  var CONFIG = {
    // Page de résultats (à adapter lors de l'intégration)
    searchUrl: 'recherche.html'
  };

  /* ---------- Utilitaires ---------- */
  function slugify(text) {
    return String(text)
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function $(selector, scope) {
    return (scope || document).querySelector(selector);
  }

  function $$(selector, scope) {
    return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
  }

  /* ---------- 1. Année du footer ---------- */
  function initYear() {
    var el = $('#year');
    if (el) el.textContent = new Date().getFullYear();
  }

  /* ---------- 2. Recherche ---------- */
  function initSearch() {
    var form = $('#search-form');
    if (!form) return;

    var select = $('#search-category');
    var input = $('#search-place');

    form.addEventListener('submit', function (event) {
      event.preventDefault();

      var params = new URLSearchParams();
      if (select && select.value) params.set('categorie', select.value);
      if (input && input.value.trim()) params.set('lieu', input.value.trim());

      var query = params.toString();
      var target = CONFIG.searchUrl + (query ? '?' + query : '');

      // Maquette : on affiche la cible. Remplacer par window.location.href = target;
      // lors de l'intégration au site.
      console.log('Recherche →', target);
    });
  }

  /* ---------- 3. Catégories : présélection dans le formulaire ---------- */
  function initCategoryLinks() {
    var select = $('#search-category');
    var input = $('#search-place');
    if (!select) return;

    var links = $$('.mainnav__list a').concat($$('.cat'));

    links.forEach(function (link) {
      link.addEventListener('click', function (event) {
        var label = $('.cat__label', link);
        var name = slugify(label ? label.textContent : link.textContent);
        var option = $$('option', select).filter(function (opt) {
          return opt.value === name;
        })[0];

        if (!option) return;

        event.preventDefault();
        select.value = option.value;

        window.scrollTo({
          top: 0,
          behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
        });

        if (input) input.focus({ preventScroll: true });
      });
    });
  }

  /* ---------- 4. Menu : élément actif + défilement mobile ---------- */
  function initNav() {
    var nav = $('.mainnav__inner');
    if (!nav) return;

    var items = $$('.mainnav__list a');

    items.forEach(function (item) {
      item.addEventListener('click', function () {
        items.forEach(function (other) {
          other.classList.remove('is-active');
          other.removeAttribute('aria-current');
        });
        item.classList.add('is-active');
        item.setAttribute('aria-current', 'page');

        // Centre l'élément cliqué dans la barre défilante (mobile)
        var offset = item.offsetLeft - (nav.clientWidth - item.offsetWidth) / 2;
        nav.scrollTo({ left: offset, behavior: 'smooth' });
      });
    });
  }

  /* ---------- 5. Images : repli si le chargement échoue ---------- */
  function handleBrokenImage(img) {
    img.style.visibility = 'hidden';
    var parent = img.parentElement;
    if (parent) parent.classList.add('is-img-missing');
  }

  function initImageFallback() {
    $$('img').forEach(function (img) {
      img.addEventListener('error', function () {
        handleBrokenImage(img);
      });

      // Cas où l'erreur s'est produite avant l'ajout de l'écouteur
      if (img.complete && img.naturalWidth === 0 && img.src) {
        handleBrokenImage(img);
      }
    });
  }

  /* ---------- Démarrage ---------- */
  function init() {
    initYear();
    initSearch();
    initCategoryLinks();
    initNav();
    initImageFallback();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
