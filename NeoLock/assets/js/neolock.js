(function () {
    'use strict';

    if (typeof jQuery !== 'undefined') {
        try {
            jQuery(function ($) {
                if ($.fn.wpColorPicker) {
                    $('.neo-color-field').wpColorPicker();
                }
                $('.neo-media-button').on('click', function (event) {
                    event.preventDefault();
                    var targetSelector = $(this).data('target');
                    var targetField = $(targetSelector);
                    if (!targetField.length || typeof wp === 'undefined' || !wp.media) return;
                    var frame = wp.media({
                        title: 'Choose media',
                        button: { text: 'Use this file' },
                        multiple: false
                    });
                    frame.on('select', function () {
                        var attachment = frame.state().get('selection').first().toJSON();
                        targetField.val(attachment.url).trigger('change');
                    });
                    frame.open();
                });
                $('.neolock-override-mode').on('change', function () {
                    var row = $(this).data('row');
                    var mode = $(this).val();
                    var fields = $('.neolock-ov-' + row);
                    if (mode === 'custom') fields.prop('disabled', false);
                    else fields.prop('disabled', true);
                });
            });
        } catch (e) {}
    }

    if (typeof window.NeoLockConfig === 'undefined') return;
    var CFG = window.NeoLockConfig;
    var BOOT = window.__NEOLOCK_BOOT || {};
    var MSG = (CFG.custom_message || BOOT.custom_message || '').trim();
    var SHIELD_TEXT = 'CONTENT PROTECTED BY NEOLOCK\n\nScreenshot, screen record, and\ncontent capture attempts are blocked\non this page.';

    function k(ev) {
        try { if (ev && typeof ev.preventDefault === 'function') ev.preventDefault(); } catch (x) {}
        try { if (ev && typeof ev.stopPropagation === 'function') ev.stopPropagation(); } catch (x) {}
        try { if (ev && typeof ev.stopImmediatePropagation === 'function') ev.stopImmediatePropagation(); } catch (x) {}
        try { if (ev) { ev.cancelBubble = true; ev.returnValue = false; } } catch (x) {}
        return false;
    }

    function alertMsg(ev) {
        if (MSG) { try { window.alert(MSG); } catch (x) {} }
        return k(ev);
    }

    function isEditable(target) {
        if (!target) return false;
        var tn = (target.tagName || '').toLowerCase();
        if (tn === 'input' || tn === 'textarea' || tn === 'select') return true;
        if (target.isContentEditable) return true;
        try {
            var p = target.closest && target.closest('[contenteditable="true"], [contenteditable=""]');
            if (p) return true;
        } catch (x) {}
        return false;
    }

    function add(evName, fn) {
        var tgts = [window, document];
        try { if (document.documentElement) tgts.push(document.documentElement); } catch (x) {}
        try { if (document.body) tgts.push(document.body); } catch (x) {}
        for (var i = 0; i < tgts.length; i++) {
            try { tgts[i].addEventListener(evName, fn, { capture: true, passive: false }); } catch (x) {}
        }
    }

    var RMB = CFG.disable_right_click || BOOT.disable_right_click;
    var TSEL = CFG.disable_text_select || BOOT.disable_text_select;
    var CCOPY = CFG.disable_copy || BOOT.disable_copy;
    var CCUT = CFG.disable_cut || BOOT.disable_cut;
    var CPASTE = CFG.disable_paste || BOOT.disable_paste;
    var DRAG = CFG.disable_drag || BOOT.disable_drag;
    var IMG = CFG.disable_image_save;
    var PRINT = CFG.disable_print || BOOT.disable_print;
    var VSRC = CFG.disable_view_source || BOOT.disable_view_source;
    var DEV = CFG.disable_devtools || BOOT.disable_devtools;
    var SCR = CFG.screenshot_protection || BOOT.screenshot_protection;

    if (RMB) {
        add('contextmenu', function (e) {
            if (isEditable(e.target)) return;
            return alertMsg(e);
        });
        add('gesturestart', function (e) { return k(e); });
        add('gesturechange', function (e) { return k(e); });
        add('gestureend', function (e) { return k(e); });
    }

    if (CCOPY || CCUT || CPASTE) {
        if (CCOPY) {
            add('copy', function (e) {
                if (isEditable(e.target)) return;
                try { if (e.clipboardData) e.clipboardData.clearData(); } catch (x) {}
                try { if (window.clipboardData && window.clipboardData.clearData) window.clipboardData.clearData(); } catch (x) {}
                try { if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText('').catch(function () {}); } } catch (x) {}
                return alertMsg(e);
            });
            add('copy', function (e) { if (isEditable(e.target)) return; try { e.preventDefault(); } catch (x) {} }, true);
        }
        if (CCUT) {
            add('cut', function (e) { if (isEditable(e.target)) return; return alertMsg(e); });
        }
        if (CPASTE) {
            add('paste', function (e) { if (isEditable(e.target)) return; return k(e); });
        }
    }

    if (DRAG) {
        add('dragstart', function (e) {
            var t = e.target; if (!t) return;
            var n = (t.tagName || '').toLowerCase();
            if (n === 'img' || n === 'a' || n === 'picture' || n === 'video' || n === 'audio') return k(e);
        });
        add('drag', function (e) {
            var t = e.target; if (!t) return;
            var n = (t.tagName || '').toLowerCase();
            if (n === 'img') return k(e);
        });
        add('dragend', function (e) {
            var t = e.target; if (!t) return;
            var n = (t.tagName || '').toLowerCase();
            if (n === 'img') return k(e);
        });
    }

    if (TSEL) {
        try {
            document.addEventListener('selectionchange', function () {
                try {
                    var sel = window.getSelection();
                    if (!sel || sel.rangeCount === 0) return;
                    var anc = sel.anchorNode;
                    if (!anc) return;
                    var el = anc.nodeType === 1 ? anc : anc.parentElement;
                    if (isEditable(el)) return;
                    sel.removeAllRanges();
                } catch (x) {}
            });
        } catch (x) {}

        add('mousedown', function (e) {
            if (!e || isEditable(e.target)) return;
            var b = e.button != null ? e.button : 0;
            if (RMB && (b === 2 || b === 1)) return k(e);
        });
        add('mouseup', function (e) {
            if (!e || isEditable(e.target)) return;
            if (e.button === 2) return k(e);
        });
    }

    if (IMG) {
        add('mousedown', function (e) {
            var t = e && e.target; if (!t) return;
            var n = (t.tagName || '').toLowerCase();
            if (n === 'img' || t.classList && t.classList.contains('neolock-img-shield')) {
                if (e.button === 2) return k(e);
                try { if (n === 'img' && e.preventDefault) e.preventDefault(); } catch (x) {}
            }
        });

        var LONG_PRESS_MS = 420;
        var longTimer = null;
        var longOrigin = null;
        add('touchstart', function (e) {
            var t = e && e.target; if (!t) return;
            var n = (t.tagName || '').toLowerCase();
            var onImg = (n === 'img') || (t.classList && t.classList.contains('neolock-img-wrap')) || (t.classList && t.classList.contains('neolock-img-shield'));
            if (!onImg) return;
            longOrigin = { x: 0, y: 0 };
            try { longOrigin.x = e.touches[0].clientX; longOrigin.y = e.touches[0].clientY; } catch (x) {}
            if (longTimer) clearTimeout(longTimer);
            longTimer = setTimeout(function () {
                try {
                    var synthetic = { target: t, preventDefault: function () {}, stopPropagation: function () {}, stopImmediatePropagation: function () {} };
                    alertMsg(synthetic);
                } catch (x) {}
            }, LONG_PRESS_MS);
        });
        add('touchmove', function (e) {
            if (!longTimer || !longOrigin || !e || !e.touches || !e.touches[0]) return;
            try {
                var dx = Math.abs(e.touches[0].clientX - longOrigin.x);
                var dy = Math.abs(e.touches[0].clientY - longOrigin.y);
                if (dx > 10 || dy > 10) { clearTimeout(longTimer); longTimer = null; }
            } catch (x) {}
        });
        add('touchend', function () { if (longTimer) { clearTimeout(longTimer); longTimer = null; } });
        add('touchcancel', function () { if (longTimer) { clearTimeout(longTimer); longTimer = null; } });
    }

    function wrapImagesShield() {
        if (!IMG) return;
        try {
            var images = document.querySelectorAll('img:not(.neolock-shielded)');
            for (var i = 0; i < images.length; i++) {
                try {
                    var img = images[i];
                    if (img.closest && img.closest('.neolock-img-wrap')) continue;
                    var parent = img.parentNode;
                    if (!parent) continue;
                    var wrap = document.createElement('span');
                    wrap.className = 'neolock-img-wrap neolock-shielded-wrap';
                    parent.insertBefore(wrap, img);
                    wrap.appendChild(img);
                    var shield = document.createElement('span');
                    shield.className = 'neolock-img-shield';
                    shield.setAttribute('aria-hidden', 'true');
                    wrap.appendChild(shield);
                    img.classList.add('neolock-shielded');
                    img.setAttribute('draggable', 'false');
                    try { img.style.pointerEvents = 'none'; } catch (x) {}
                    img.ondragstart = function () { return false; };
                } catch (x) {}
            }
        } catch (e) {}
    }

    function escSvg(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
    }

    function injectWatermark() {
        if (!CFG.watermark_enabled) return;
        try {
            var box = document.getElementById('neolock-watermark');
            if (!box) {
                box = document.createElement('div');
                box.id = 'neolock-watermark';
                box.className = 'neolock-watermark';
                box.setAttribute('aria-hidden', 'true');
                (document.body || document.documentElement).appendChild(box);
            }
            var text = escSvg(CFG.watermark_text || document.domain || 'Protected');
            var fill = String(CFG.watermark_color || '#33ff77').replace(/[^\w#(),.%\d-]/g, '');
            var opacity = Math.max(0, Math.min(1, parseFloat(CFG.watermark_opacity != null ? CFG.watermark_opacity : 0.08)));
            var size = Math.max(8, parseInt(CFG.watermark_size, 10) || 22);
            var angle = parseInt(CFG.watermark_angle, 10) || -28;
            var svg =
                '<svg xmlns="http://www.w3.org/2000/svg" width="420" height="280">' +
                '<text x="50%" y="50%" fill="' + fill + '" fill-opacity="' + opacity + '" ' +
                'font-family="Courier New, monospace" font-size="' + size + '" font-weight="700" ' +
                'text-anchor="middle" transform="rotate(' + angle + ' 210 140)">' + text + '</text>' +
                '</svg>';
            var encoded = 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
            box.style.backgroundImage = 'url("' + encoded + '")';
            box.style.backgroundSize = '420px 280px';
            box.style.backgroundRepeat = 'repeat';
            box.setAttribute('data-text', text);
        } catch (x) {}
    }

    function applyAll() {
        wrapImagesShield();
        injectWatermark();
        try {
            document.documentElement.classList.add('neolock-active');
            if (document.body) document.body.classList.add('neolock-active');
        } catch (x) {}
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyAll);
    } else {
        applyAll();
    }
    window.addEventListener('load', applyAll);
    setInterval(applyAll, 3500);

    try {
        if (typeof MutationObserver !== 'undefined') {
            var mo = new MutationObserver(function () { applyAll(); });
            mo.observe(document.documentElement, { childList: true, subtree: true, attributes: false, characterData: false });
        }
    } catch (x) {}

    if (PRINT || VSRC || DEV) {
        add('keydown', function (e) {
            try {
                var kk = (e.key || '').toLowerCase();
                var code = e.code || '';
                var ctrl = e.ctrlKey || e.metaKey;
                var shift = e.shiftKey;
                var alt = e.altKey;

                if (DEV) {
                    if (code === 'F12' || kk === 'f12') return k(e);
                    if (ctrl && shift && (code === 'KeyI' || code === 'KeyJ' || code === 'KeyC' || code === 'KeyM' || kk === 'i' || kk === 'j' || kk === 'c' || kk === 'm')) return k(e);
                    if (ctrl && alt && (kk === 'i' || kk === 'j' || code === 'KeyI' || code === 'KeyJ')) return k(e);
                    if (alt && (code === 'KeyA' || kk === 'a') && ctrl) return k(e);
                }

                if (VSRC) {
                    if (ctrl && (code === 'KeyS' || code === 'KeyU' || kk === 's' || kk === 'u')) return k(e);
                    if (alt && (code === 'ArrowLeft' || code === 'ArrowRight' || code === 'Home')) return k(e);
                }

                if (PRINT) {
                    if (ctrl && (code === 'KeyP' || kk === 'p')) return alertMsg(e);
                }
            } catch (x) {}
        });
    }

    var devOpen = false;
    if (DEV) {
        var threshold = 150;
        setInterval(function () {
            try {
                var wDiff = window.outerWidth - window.innerWidth;
                var hDiff = window.outerHeight - window.innerHeight;
                if (Math.abs(wDiff) > threshold || Math.abs(hDiff) > threshold) {
                    if (!devOpen) { devOpen = true; doLockdown(); }
                }
            } catch (x) {}
        }, 700);

        try {
            var re = /./;
            re.toString = function () {
                if (!devOpen) { devOpen = true; doLockdown(); }
                return '';
            };
            var iv = setInterval(function () {
                try { console.log('%c', re); } catch (x) {}
            }, 1500);
            try { setTimeout(function () { clearInterval(iv); }, 45000); } catch (x) {}
        } catch (x) {}
    }

    var shieldEl = null;
    function getShield() {
        if (!shieldEl) {
            shieldEl = document.getElementById('neolock-shield');
            if (!shieldEl) {
                shieldEl = document.createElement('div');
                shieldEl.id = 'neolock-shield';
                shieldEl.className = 'neolock-shield';
                shieldEl.setAttribute('data-neolock-msg', SHIELD_TEXT);
                shieldEl.setAttribute('aria-hidden', 'true');
                (document.body || document.documentElement).appendChild(shieldEl);
            } else {
                if (!shieldEl.getAttribute('data-neolock-msg')) shieldEl.setAttribute('data-neolock-msg', SHIELD_TEXT);
            }
        }
        return shieldEl;
    }

    function raiseShield() {
        try { var s = getShield(); s.classList.add('is-active'); } catch (x) {}
    }
    function lowerShield() {
        try { var s = getShield(); s.classList.remove('is-active'); } catch (x) {}
    }

    if (SCR) {
        document.addEventListener('visibilitychange', function () {
            if (document.hidden || document.msHidden || document.webkitHidden) raiseShield();
            else setTimeout(lowerShield, 450);
        });
        try { document.addEventListener('msvisibilitychange', function () { if (document.msHidden) raiseShield(); else setTimeout(lowerShield, 500); }); } catch (x) {}
        try { document.addEventListener('webkitvisibilitychange', function () { if (document.webkitHidden) raiseShield(); else setTimeout(lowerShield, 500); }); } catch (x) {}

        window.addEventListener('blur', raiseShield);
        window.addEventListener('focus', function () { setTimeout(lowerShield, 250); });
        window.addEventListener('pagehide', raiseShield);
        window.addEventListener('pageshow', function () { setTimeout(lowerShield, 250); });
        window.addEventListener('beforeunload', raiseShield);
        window.addEventListener('popstate', function () { setTimeout(lowerShield, 200); });

        try {
            if (navigator.mediaDevices && typeof navigator.mediaDevices.addEventListener === 'function') {}
        } catch (x) {}

        try {
            if (window.matchMedia) {
                try {
                    var captureMq = window.matchMedia('(display-mode: picture-in-picture)');
                    if (captureMq && captureMq.addEventListener) captureMq.addEventListener('change', function () { if (captureMq.matches) raiseShield(); });
                } catch (x) {}
            }
        } catch (x) {}

        add('keyup', function (e) {
            try {
                var code = e.code || '';
                if (code === 'PrintScreen' || code === 'Snapshot' || e.key === 'PrintScreen' || e.keyCode === 44) {
                    raiseShield();
                    setTimeout(lowerShield, 1200);
                    return k(e);
                }
                if (e.metaKey && e.shiftKey && ((e.code === 'KeyS') || (e.code === 'Key4') || (e.code === 'Key5'))) {
                    raiseShield();
                    setTimeout(lowerShield, 2500);
                    return k(e);
                }
                if (e.metaKey && (e.key === 'Shift' || e.shiftKey) && (e.key === '3' || e.key === '4' || e.key === '5')) {
                    raiseShield();
                    setTimeout(lowerShield, 2500);
                    return k(e);
                }
            } catch (x) {}
        });
        add('keydown', function (e) {
            try {
                var code = e.code || '';
                if (code === 'PrintScreen' || code === 'Snapshot') {
                    raiseShield();
                    return k(e);
                }
            } catch (x) {}
        });
    }

    if (PRINT) {
        window.addEventListener('beforeprint', function () {
            try {
                var b = document.body;
                if (!b) return;
                b.setAttribute('data-neolock-original', b.innerHTML);
                b.innerHTML =
                    '<div style="display:grid;place-items:center;min-height:100vh;color:#fff;background:#000;font-family:Courier New, monospace;font-size:24px;padding:40px;text-align:center;">' +
                    '<div style="border:2px solid #33ff77;padding:30px 50px;border-radius:10px;color:#33ff77;">' +
                    'PRINTING DISABLED<br><small style="opacity:.7;color:#d7ffe6;">Content is protected by NeoLock</small>' +
                    '</div></div>';
            } catch (x) {}
        });
        window.addEventListener('afterprint', function () {
            try {
                var b = document.body;
                if (!b) return;
                var orig = b.getAttribute('data-neolock-original');
                if (orig != null) {
                    b.innerHTML = orig;
                    b.removeAttribute('data-neolock-original');
                    applyAll();
                }
            } catch (x) {}
        });
        try {
            if (window.matchMedia) {
                var pmq = window.matchMedia('print');
                if (pmq && pmq.addEventListener) {
                    pmq.addEventListener('change', function (ev) {
                        if (ev.matches) {
                            try {
                                var b = document.body; if (!b) return;
                                b.setAttribute('data-neolock-original', b.innerHTML);
                                b.innerHTML = '<div style="position:fixed;inset:0;color:#fff;background:#000;font-family:Courier New, monospace;font-size:24px;display:grid;place-items:center;padding:40px;text-align:center;">PRINTING DISABLED<br><small style="opacity:.7;">Content protected</small></div>';
                            } catch (x) {}
                        } else {
                            try {
                                var b = document.body; if (!b) return;
                                var orig = b.getAttribute('data-neolock-original');
                                if (orig != null) { b.innerHTML = orig; b.removeAttribute('data-neolock-original'); applyAll(); }
                            } catch (x) {}
                        }
                    });
                }
            }
        } catch (x) {}
    }

    function doLockdown() {
        try {
            var b = document.body; if (!b) return;
            b.setAttribute('data-neolock-lockdown', '1');
            b.innerHTML =
                '<div id="neolock-ld" style="position:fixed;inset:0;background:#000;color:#33ff77;display:grid;place-items:center;font-family:Courier New, monospace;font-size:22px;text-align:center;padding:40px;letter-spacing:.12em;z-index:2147483647;">' +
                '<div><div style="font-size:40px;margin-bottom:20px;">ACCESS LOCKED</div>' +
                '<div style="max-width:620px;color:#d7ffe6;font-size:16px;line-height:1.7;">Developer tools inspection is not allowed on this page.<br>Please close the inspector and reload the page to continue.</div></div></div>';
            if (window.stop) { try { window.stop(); } catch (x) {} }
        } catch (x) {}
    }

    try {
        document.addEventListener('contextmenu', function (e) {
            var t = e && e.target; if (!t) return;
            var n = (t.tagName || '').toLowerCase();
            if (n === 'img' || t.classList && (t.classList.contains('neolock-img-shield') || t.classList.contains('neolock-img-wrap'))) {
                if (RMB || IMG) return alertMsg(e);
            }
        }, true);
    } catch (x) {}

    window.NeoLock = {
        raiseShield: raiseShield,
        lowerShield: lowerShield,
        refresh: applyAll,
        lockdown: doLockdown,
    };
})();
