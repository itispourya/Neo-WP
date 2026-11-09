var NeoMarketPublic = (function($) {
    var $container, $content;
    var STORAGE_WISHLIST = 'neomarket_wishlist';
    var STORAGE_COMPARE  = 'neomarket_compare';
    var STORAGE_THEME    = 'neomarket_theme';

    function init() {
        $container = $('#neomarket-app');
        $content = $('#neomarket-content-area');
        initTheme();
        updateCounters();

        if (!$container.length) return;
        bindGlobalEvents();

        // Decide initial view
        const params = new URLSearchParams(window.location.search);
        const initial = $content && $content.data('initial-view');
        if (params.get('product_id')) {
            loadProduct(params.get('product_id'));
        } else if (initial) {
            loadView(initial);
        } else if (params.get('view') === 'cart') {
            loadView('cart');
        } else if (params.get('view') === 'checkout') {
            loadView('checkout');
        } else {
            loadCatalog();
        }

        // Init noUiSlider if on catalog
        window.onpopstate = function(event) {
            const p = new URLSearchParams(window.location.search);
            if (p.get('product_id')) loadProduct(p.get('product_id'));
            else if (p.get('view') === 'cart') loadView('cart');
            else if (p.get('view') === 'checkout') loadView('checkout');
            else loadCatalog();
        };
    }

    function restURL(route) {
        // Build safe REST URL — use wp_localize data if available else fallback
        const base = (typeof neomarket_public !== 'undefined' && neomarket_public.rest_url) ? neomarket_public.rest_url : wpApiSettings.root;
        return base + 'neomarket/v1/' + route.replace(/^\//, '');
    }

    function request(route, method, data = {}) {
        const args = {
            method: method,
            headers: { 'X-WP-Nonce': (typeof wpApiSettings !== 'undefined') ? wpApiSettings.nonce : '' }
        };
        if (method.toUpperCase() === 'GET') {
            const qs = $.param(data);
            if (qs) route += (route.indexOf('?') > -1 ? '&' : '?') + qs;
        } else {
            args.headers['Content-Type'] = 'application/json';
            args.body = JSON.stringify(data);
        }
        return fetch(restURL(route), args).then(r => r.json());
    }

    function startLoader() { $container && $container.addClass('loading'); }
    function stopLoader()  { $container && $container.removeClass('loading'); }

    function loadCatalog(params = {}) {
        startLoader();
        $.post(neomarket_ajax.ajax_url, $.extend({ action: 'neomarket_load_catalog', nonce: neomarket_ajax.nonce, paged: 1 }, params), function(resp) {
            if (resp.success) {
                $content.html(resp.html);
                if (!params.view) history.pushState(null, '', window.location.pathname);
                initNoUiSlider();
                attachCatalogWidgets();
            }
            stopLoader();
        });
    }

    function loadProduct(productId) {
        startLoader();
        $.post(neomarket_ajax.ajax_url, { action: 'neomarket_load_product', nonce: neomarket_ajax.nonce, product_id: productId }, function(resp) {
            if (resp.success) {
                $content.html(resp.html);
                initProductGallery();
                history.pushState({ type: 'product', id: productId }, '', window.location.pathname + '?product_id=' + productId);
            }
            stopLoader();
        });
    }

    function loadView(view) {
        startLoader();
        $.post(neomarket_ajax.ajax_url, { action: 'neomarket_load_view', nonce: neomarket_ajax.nonce, view: view }, function(resp) {
            if (resp.success) {
                $content.html(resp.html);
                history.pushState(null, '', window.location.pathname + '?view=' + view);
                if (view === 'checkout') initCheckoutForm();
            }
            stopLoader();
        });
    }

    function initProductGallery() {
        if (typeof Swiper === 'undefined') return;
        var thumbs = new Swiper(".neomarket-thumbs-slider", {
            spaceBetween: 10, slidesPerView: 4, freeMode: true, watchSlidesProgress: true,
        });
        new Swiper(".neomarket-main-slider", {
            spaceBetween: 10,
            navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
            thumbs: { swiper: thumbs },
        });
    }

    function initNoUiSlider() {
        if (typeof noUiSlider === 'undefined') return;
        var slider = document.getElementById('neomarket-price-slider');
        if (!slider) return;
        noUiSlider.create(slider, {
            start: [0, 50000000],
            connect: true,
            range: { 'min': 0, 'max': 50000000 },
            format: { to: v => Math.round(v), from: v => Number(v) }
        });
        var minEl = document.getElementById('price-min-display');
        var maxEl = document.getElementById('price-max-display');
        slider.noUiSlider.on('update', function(values) {
            if (minEl) minEl.textContent = Number(values[0]).toLocaleString('fa-IR') + ' تومان';
            if (maxEl) maxEl.textContent = Number(values[1]).toLocaleString('fa-IR') + ' تومان';
        });
        slider.noUiSlider.on('change', function(values) {
            loadCatalog({ price_min: values[0], price_max: values[1] });
        });
    }

    function attachCatalogWidgets() {}

    // ===== Global UI: Wishlist / Compare / QuickView / Theme / Cart =====
    function bindGlobalEvents() {
        $(document)
            .on('click', '.view-product-btn, .neomarket-product-card', function(e) {
                if ($(e.target).is('button, a, input, select, textarea')) return;
                if ($(this).data('id')) loadProduct($(this).data('id'));
            })
            .on('click', '.back-to-catalog', function() { loadCatalog(); })
            .on('click', '.go-to-cart', function() { loadView('cart'); })
            .on('click', '.go-to-checkout', function() { loadView('checkout'); })
            .on('click', '.neomarket-cat-list a', function(e) {
                e.preventDefault();
                $('.neomarket-cat-list a').removeClass('active');
                $(this).addClass('active');
                loadCatalog({ category: $(this).data('cat') });
            })
            .on('click', '[data-view]', function() {
                loadView($(this).data('view'));
            })
            .on('input', '#neomarket-search-input', debounce(function() {
                loadCatalog({ search: $(this).val() });
            }, 350))
            .on('click', '#apply-price-filter', function() {
                loadCatalog({ price_min: $('#price-min').val(), price_max: $('#price-max').val() });
            })
            .on('submit', '#neomarket-submit-review', function(e) {
                e.preventDefault();
                var $f = $(this);
                $.post(neomarket_ajax.ajax_url, $f.serialize() + '&action=neomarket_submit_review&nonce=' + neomarket_ajax.nonce, function(r) {
                    alert(r.data);
                    if (r.success) loadProduct($f.find('input[name=product_id]').val());
                });
            })
            .on('click', '.tab-nav li', function() {
                var t = $(this).data('tab');
                $('.tab-nav li').removeClass('active'); $(this).addClass('active');
                $('.tab-pane').removeClass('active'); $('#tab-' + t).addClass('active');
            })
            .on('click', '.add-to-cart-btn', function() {
                var id = $(this).data('product_id') || $(this).closest('[data-id]').data('id');
                if (!id) return;
                addToCart(id);
            })
            .on('click', '.nmk-qty-btn', function() {
                var $row = $(this).closest('.cart-item');
                var key = $row.data('key');
                var step = $(this).data('step') * 1;
                var cur = $row.find('.nmk-qty-input').val() * 1;
                var new_qty = Math.max(0, cur + step);
                $.post(neomarket_ajax.ajax_url, { action: 'neomarket_cart_update', nonce: neomarket_ajax.nonce, key: key, quantity: new_qty }, function(r) {
                    if (r.success) loadView('cart');
                });
            })
            .on('click', '.nmk-remove-item', function() {
                var key = $(this).closest('.cart-item').data('key');
                $.post(neomarket_ajax.ajax_url, { action: 'neomarket_cart_remove', nonce: neomarket_ajax.nonce, key: key }, function(r) { if (r.success) loadView('cart'); });
            })
            .on('click', '.add-to-wishlist', function(e) {
                e.stopPropagation();
                toggleWishlist($(this).data('id'), $(this));
            })
            .on('click', '.add-to-compare', function(e) {
                e.stopPropagation();
                toggleCompare($(this).data('id'), $(this));
            })
            .on('click', '.quick-view-btn', function(e) {
                e.stopPropagation();
                openQuickView($(this).data('id'));
            })
            .on('click', '#nmk-theme-toggle', function() {
                setTheme(getTheme() === 'dark' ? 'light' : 'dark');
            });
    }

    function debounce(fn, ms) { let t; return function(...a) { clearTimeout(t); t = setTimeout(() => fn.apply(this, a), ms); }; }

    // === Cart ===
    function addToCart(productId, qty = 1) {
        $.post(neomarket_ajax.ajax_url, { action: 'neomarket_cart_add', nonce: neomarket_ajax.nonce, product_id: productId, quantity: qty }, function(resp) {
            if (resp.success) {
                updateCounters();
                var banner = $('<div class="nmk-float-notice">' + (resp.data.message || 'Added to cart!') + '</div>');
                $('body').append(banner);
                setTimeout(() => banner.remove(), 2500);
            } else {
                alert(resp.data || 'Cart error');
            }
        });
    }

    function updateCounters() {
        $.post(neomarket_ajax.ajax_url, { action: 'neomarket_cart_get', nonce: neomarket_ajax.nonce }, function(resp) {
            if (resp.success) {
                $('.neomarket-cart-count').text(resp.data.count || 0);
                $('.neomarket-cart-total').text((resp.data.totals && resp.data.totals.total ? Number(resp.data.totals.total).toLocaleString('fa-IR') : 0) + ' تومان');
            }
        });
        $('.neomarket-wishlist-count').text(getWishlist().length);
        $('.neomarket-compare-count').text(getCompare().length);
    }

    // === Wishlist / Compare (localStorage based) ===
    function getWishlist() { try { return JSON.parse(localStorage.getItem(STORAGE_WISHLIST) || '[]'); } catch(e) { return []; } }
    function setWishlist(arr) { localStorage.setItem(STORAGE_WISHLIST, JSON.stringify(arr)); }
    function toggleWishlist(id, $el) {
        var list = getWishlist();
        var idx = list.indexOf(id);
        if (idx > -1) list.splice(idx,1); else list.push(id);
        setWishlist(list);
        if ($el) $el.toggleClass('active', idx === -1);
        updateCounters();
    }

    function getCompare() { try { return JSON.parse(localStorage.getItem(STORAGE_COMPARE) || '[]'); } catch(e) { return []; } }
    function setCompare(arr) { localStorage.setItem(STORAGE_COMPARE, JSON.stringify(arr)); }
    function toggleCompare(id, $el) {
        var list = getCompare();
        if (list.length >= 4 && list.indexOf(id) === -1) { alert('Maximum 4 products compare allowed'); return; }
        var idx = list.indexOf(id);
        if (idx > -1) list.splice(idx,1); else list.push(id);
        setCompare(list);
        if ($el) $el.toggleClass('active', idx === -1);
        updateCounters();
    }

    // === Quick View (HTML5 dialog) ===
    function openQuickView(id) {
        request('products/' + id, 'GET').then(function(p) {
            var dlg = document.createElement('dialog');
            dlg.className = 'neomarket-quick-view';
            dlg.innerHTML = '<div style="display:flex;gap:20px;max-width:900px;">' +
                '<div style="flex:0 0 300px;"><img src="' + (p.thumbnail || '') + '" style="width:100%"></div>' +
                '<div style="flex:1;"><h2>' + p.title + '</h2>' +
                '<p>قیمت: ' + Number(p.sale_price || p.regular_price).toLocaleString('fa-IR') + ' تومان</p>' +
                '<p>' + (p.excerpt || '') + '</p>' +
                '<button class="button-primary add-to-cart-btn" data-product_id="' + id + '">افزودن به سبد خرید</button></div></div>' +
                '<button onclick="this.closest(\'dialog\').close();" style="position:absolute;top:10px;right:10px;">×</button>';
            document.body.appendChild(dlg);
            dlg.showModal();
            dlg.addEventListener('close', () => dlg.remove());
        });
    }

    // === Theme (Dark/Light) ===
    function getTheme() { return localStorage.getItem(STORAGE_THEME) || 'light'; }
    function setTheme(t) {
        localStorage.setItem(STORAGE_THEME, t);
        if ($container) { $container.attr('data-theme', t); }
        else $('body').attr('data-neomarket-theme', t);
    }
    function initTheme() {
        var t = getTheme();
        if ($container) $container.attr('data-theme', t);
        else $('body').attr('data-neomarket-theme', t);
    }

    // === Checkout ===
    function initCheckoutForm() {
        $(document).on('submit', '#neomarket-checkout-form', function(e) {
            e.preventDefault();
            var $f = $(this);
            var data = $f.serializeObject();
            startLoader();
            $.post(neomarket_ajax.ajax_url, $.extend({ action: 'neomarket_checkout_submit', nonce: neomarket_ajax.nonce }, data), function(resp) {
                stopLoader();
                if (resp.success) {
                    handleCheckoutResult(resp.data);
                } else {
                    alert(resp.data);
                }
            });
        });
    }

    function handleCheckoutResult(res) {
        if (res.redirect) { window.location.href = res.redirect; return; }
        if (res.html) {
            $content.find('#neomarket-payment-details-host').html(res.html);
        }
    }

    // === Payment Gateways CTC + Crypto ===
    function confirmCardToCard(serialized) {
        $.post(neomarket_ajax.ajax_url, serialized + '&action=neomarket_ajax_ctc_submit', function(r) {
            if (r.success && r.data.redirect) {
                window.location.href = r.data.redirect;
            } else {
                alert(r.data || 'خطا در ارسال');
            }
        });
    }
    function confirmCrypto(serialized) {
        $.post(neomarket_ajax.ajax_url, serialized + '&action=neomarket_ajax_crypto_submit', function(r) {
            if (r.success && r.data.redirect) {
                window.location.href = r.data.redirect;
            } else {
                alert(r.data || 'خطا در ارسال');
            }
        });
    }

    $.fn.serializeObject = function() {
        var o = {};
        var a = this.serializeArray();
        $.each(a, function() {
            if (o[this.name] !== undefined) {
                if (!o[this.name].push) o[this.name] = [o[this.name]];
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

    return {
        init: init,
        confirmCardToCard: confirmCardToCard,
        confirmCrypto: confirmCrypto,
        addToCart: addToCart,
        loadCatalog: loadCatalog,
        loadProduct: loadProduct,
        loadView: loadView,
    };

})(jQuery);

jQuery(function() { if (typeof NeoMarketPublic !== 'undefined') NeoMarketPublic.init(); });
