// KOR SAFETY storefront interactions
(function () {
  'use strict';

  function toast(msg) {
    var t = document.getElementById('toast');
    if (!t) return;
    t.querySelector('.msg').textContent = msg;
    t.classList.add('show');
    clearTimeout(toast._t);
    toast._t = setTimeout(function () { t.classList.remove('show'); }, 2200);
  }

  function csrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function updateCartBadge(count) {
    var b = document.getElementById('cart-badge');
    if (!b) return;
    b.textContent = count;
    b.classList.toggle('hide', !count);
  }

  // AJAX add-to-cart (quick-add buttons + forms marked data-ajax)
  document.addEventListener('click', function (e) {
    var q = e.target.closest('.js-quickadd');
    if (!q) return;
    e.preventDefault();
    var url = q.getAttribute('data-url');
    fetch(url, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
      body: new URLSearchParams({ qty: '1' })
    }).then(function (r) { return r.json(); })
      .then(function (d) { updateCartBadge(d.count); toast(d.message || '장바구니에 담았습니다.'); })
      .catch(function () { toast('오류가 발생했습니다.'); });
  });

  // Product detail: add-to-cart via ajax
  var pdForm = document.getElementById('pd-add-form');
  if (pdForm) {
    pdForm.addEventListener('submit', function (e) {
      if (!pdForm.dataset.ajax) return;
      e.preventDefault();
      fetch(pdForm.action, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: new FormData(pdForm)
      }).then(function (r) { return r.json(); })
        .then(function (d) { updateCartBadge(d.count); toast(d.message || '장바구니에 담았습니다.'); });
    });
  }

  // Quantity steppers
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.qty button');
    if (!btn) return;
    var input = btn.parentElement.querySelector('input');
    var v = parseInt(input.value, 10) || 1;
    v += btn.dataset.step === 'up' ? 1 : -1;
    if (v < 1) v = 1;
    input.value = v;
    if (input.dataset.autosubmit) input.form.submit();
  });

  // Gallery thumbnails
  var main = document.getElementById('pd-main-img');
  if (main) {
    document.querySelectorAll('.pd-thumbs button').forEach(function (b) {
      b.addEventListener('click', function () {
        document.querySelectorAll('.pd-thumbs button').forEach(function (x) { x.classList.remove('active'); });
        b.classList.add('active');
        main.src = b.querySelector('img').src;
      });
    });
  }

  // Mobile drawer
  var drawer = document.getElementById('drawer');
  document.addEventListener('click', function (e) {
    if (e.target.closest('.menu-btn')) { drawer && drawer.classList.add('open'); }
    if (e.target.closest('.drawer .scrim')) { drawer && drawer.classList.remove('open'); }
  });

  // ---- Hero slider ----
  var hero = document.getElementById('hero');
  if (hero) {
    var slides = [].slice.call(hero.querySelectorAll('.hs-slide'));
    var dots = [].slice.call(hero.querySelectorAll('.hs-dot'));
    var interval = parseInt(hero.getAttribute('data-interval'), 10) || 5500;
    var cur = 0, timer = null;
    hero.style.setProperty('--dur', interval + 'ms');

    function go(n, dir) {
      n = (n + slides.length) % slides.length;
      if (n === cur) return;
      slides[cur].classList.remove('active');
      dots[cur].classList.remove('active');
      cur = n;
      slides[cur].classList.add('active');
      dots[cur].classList.add('active');
    }
    function next() { go(cur + 1); }
    function prev() { go(cur - 1); }
    function start() { stop(); timer = setInterval(next, interval); }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }

    hero.querySelector('.hs-arrow.next').addEventListener('click', function () { next(); start(); });
    hero.querySelector('.hs-arrow.prev').addEventListener('click', function () { prev(); start(); });
    dots.forEach(function (d) {
      d.addEventListener('click', function () { go(parseInt(d.dataset.i, 10)); start(); });
    });
    hero.addEventListener('mouseenter', function () { stop(); hero.classList.add('paused'); });
    hero.addEventListener('mouseleave', function () { start(); hero.classList.remove('paused'); });

    // subtle parallax on pointer move
    hero.addEventListener('mousemove', function (e) {
      var r = hero.getBoundingClientRect();
      var dx = (e.clientX - r.left) / r.width - 0.5;
      var dy = (e.clientY - r.top) / r.height - 0.5;
      var bg = slides[cur].querySelector('.hs-bg');
      if (bg) bg.style.transform = 'scale(1.12) translate(' + (dx * -16) + 'px,' + (dy * -12) + 'px)';
    });
    hero.addEventListener('mouseleave', function () {
      var bg = slides[cur].querySelector('.hs-bg');
      if (bg) bg.style.transform = '';
    });

    // keyboard + swipe
    document.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') { prev(); start(); }
      else if (e.key === 'ArrowRight') { next(); start(); }
    });
    var sx = null;
    hero.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; stop(); }, { passive: true });
    hero.addEventListener('touchend', function (e) {
      if (sx === null) return;
      var d = e.changedTouches[0].clientX - sx;
      if (Math.abs(d) > 40) { d < 0 ? next() : prev(); }
      sx = null; start();
    });

    if (slides.length > 1) start();
  }

  // ---- Scroll reveal (staggered fade-up) ----
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!reduce && 'IntersectionObserver' in window) {
    var targets = [].slice.call(document.querySelectorAll(
      '.sec-head, .cat-tile, .p-card, .promo .cell, .showcase-aside, .pd-detail-imgs img'
    ));
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
    targets.forEach(function (el, i) {
      el.classList.add('reveal');
      // stagger within the same grid row group
      var d = (i % 5) * 60;
      el.style.transitionDelay = d + 'ms';
      io.observe(el);
    });
  }
})();
