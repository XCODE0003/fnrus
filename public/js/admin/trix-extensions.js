/**
 * Filament RichEditor (Trix) extensions for FNRUS admin.
 *
 * Adds:
 *   • Text colour picker — saves as <span style="color: #..">...</span>.
 *   • Text highlight (background) — <span style="background: #..">...</span>.
 *   • Video embed — prompts for a YouTube / Vimeo / direct mp4 URL and
 *     injects an <iframe> (or <video>) into the document via Trix's
 *     attachment API.
 *   • Insert raw image by URL — useful when the file is already hosted
 *     elsewhere or you want to paste an /i{hash} legacy attachment.
 *
 * Loaded once on every admin page via Filament asset registration
 * (see AdminPanelProvider::panel()). Hooks each trix-editor as it
 * initialises so the toolbar is augmented in-place. Safe to load
 * outside the admin too — it is a no-op if window.Trix is absent.
 */
(function () {
    'use strict';

    if (typeof window === 'undefined' || !('addEventListener' in document)) return;

    const PALETTE = [
        '#ef4444', '#f97316', '#eab308', '#22c55e',
        '#0ea5e9', '#6366f1', '#a855f7', '#ec4899',
        '#000000', '#6b7280', '#9ca3af', '#ffffff',
    ];

    // --- Register Trix text attributes BEFORE any editor mounts ---------
    // Trix exposes the global as window.Trix after its module loads but
    // before the first trix-initialize. Polling is unnecessary because
    // FilamentScripts loads Trix synchronously; we still guard with a
    // microtask retry to handle late-loading scenarios.
    function registerTrixAttributes() {
        if (typeof window.Trix === 'undefined') {
            return false;
        }
        // styleProperty makes Trix emit inline-style HTML on save.
        if (!window.Trix.config.textAttributes.fnrColor) {
            window.Trix.config.textAttributes.fnrColor = {
                styleProperty: 'color',
                inheritable: true,
            };
        }
        if (!window.Trix.config.textAttributes.fnrHighlight) {
            window.Trix.config.textAttributes.fnrHighlight = {
                styleProperty: 'background-color',
                inheritable: true,
            };
        }
        return true;
    }
    if (!registerTrixAttributes()) {
        const id = setInterval(function () {
            if (registerTrixAttributes()) clearInterval(id);
        }, 50);
    }

    function svgIcon(d) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">' + d + '</svg>';
    }

    // Match Filament's toolbar button look so our buttons don't look like a
    // foreign element on the strip.
    const FILAMENT_BTN_CLS = 'fi-fo-rich-editor-toolbar-btn flex h-8 min-w-[theme(spacing.8)] cursor-pointer items-center justify-center rounded-lg text-sm font-semibold text-gray-700 transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/5 dark:focus-visible:bg-white/5';

    function makeButton(opts) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = FILAMENT_BTN_CLS + ' ' + (opts.cls || '');
        btn.style.padding = '0 6px';
        btn.tabIndex = -1;
        btn.setAttribute('title', opts.title);
        btn.innerHTML = opts.icon;
        if (opts.onClick) btn.addEventListener('click', opts.onClick);
        return btn;
    }

    function makeSwatchPopover(onPick, current) {
        const wrap = document.createElement('div');
        wrap.className = 'fnr-swatch-popover';
        Object.assign(wrap.style, {
            position: 'absolute',
            zIndex: 9999,
            background: '#fff',
            border: '1px solid #d1d5db',
            borderRadius: '8px',
            padding: '8px',
            display: 'grid',
            gridTemplateColumns: 'repeat(6, 1fr)',
            gap: '4px',
            boxShadow: '0 8px 24px rgba(0,0,0,.18)',
        });

        PALETTE.forEach(function (hex) {
            const sw = document.createElement('button');
            sw.type = 'button';
            sw.title = hex;
            Object.assign(sw.style, {
                width: '24px', height: '24px', borderRadius: '4px',
                border: hex === '#ffffff' ? '1px solid #d1d5db' : '1px solid transparent',
                background: hex, cursor: 'pointer', padding: 0,
            });
            sw.addEventListener('click', function () { onPick(hex); close(); });
            wrap.appendChild(sw);
        });

        // Custom color input
        const customWrap = document.createElement('label');
        Object.assign(customWrap.style, {
            gridColumn: 'span 4', display: 'flex', alignItems: 'center',
            gap: '6px', fontSize: '12px', cursor: 'pointer', color: '#374151',
        });
        customWrap.textContent = 'Свой:';
        const customInput = document.createElement('input');
        customInput.type = 'color';
        customInput.value = current || '#ef4444';
        Object.assign(customInput.style, { width: '32px', height: '24px', padding: 0, border: 'none' });
        customInput.addEventListener('input', function (e) { onPick(e.target.value); });
        customInput.addEventListener('change', function () { close(); });
        customWrap.appendChild(customInput);
        wrap.appendChild(customWrap);

        // Clear color
        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.textContent = 'Сброс';
        Object.assign(clearBtn.style, {
            gridColumn: 'span 2', padding: '4px 8px', borderRadius: '4px',
            border: '1px solid #d1d5db', background: '#f9fafb', cursor: 'pointer',
            fontSize: '12px', color: '#374151',
        });
        clearBtn.addEventListener('click', function () { onPick(null); close(); });
        wrap.appendChild(clearBtn);

        function close() {
            document.removeEventListener('click', outside, true);
            if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
        }

        function outside(e) {
            if (!wrap.contains(e.target)) close();
        }
        // Close on outside click — defer attachment until current click bubble ends.
        setTimeout(function () { document.addEventListener('click', outside, true); }, 0);

        return wrap;
    }

    function applyAttribute(editor, attribute, value) {
        if (value === null || value === '') {
            editor.deactivateAttribute(attribute);
        } else {
            editor.activateAttribute(attribute, value);
        }
    }

    function videoEmbed(url) {
        if (!url) return null;
        url = url.trim();

        // YouTube
        let m = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([\w-]{11})/);
        if (m) {
            return '<iframe src="https://www.youtube.com/embed/' + m[1] + '" width="560" height="315" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        }
        // Vimeo
        m = url.match(/vimeo\.com\/(\d+)/);
        if (m) {
            return '<iframe src="https://player.vimeo.com/video/' + m[1] + '" width="560" height="315" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
        }
        // Direct mp4/webm
        if (/\.(mp4|webm|ogg)(\?|$)/i.test(url)) {
            return '<video controls width="560" src="' + url.replace(/"/g, '&quot;') + '"></video>';
        }
        // Fallback iframe
        return '<iframe src="' + url.replace(/"/g, '&quot;') + '" width="560" height="315" frameborder="0" allowfullscreen></iframe>';
    }

    /**
     * Insert as a Trix attachment so the iframe/video tag survives Trix's
     * sanitizer and renders as a preview inside the editor. The saved HTML
     * is `<figure data-trix-attachment="..." data-trix-content-type="..">`
     * — the display side (App\Support\RichHtml::render) un-wraps it back
     * into the raw embed HTML before output.
     */
    function insertEmbedAttachment(editor, html) {
        if (!html || !editor) return;
        const attachment = new window.Trix.Attachment({
            content: html,
            contentType: 'application/vnd.fnrus.embed.html',
        });
        editor.insertAttachment(attachment);
        editor.insertLineBreak();
    }

    /**
     * Insert raw HTML for tags Trix natively keeps (<img>, links).
     * For these we can patch the hidden input + loadHTML and Trix will
     * round-trip them cleanly.
     */
    function insertRawHtml(editorEl, html) {
        if (!html || !editorEl?.editor) return;
        const input = document.getElementById(editorEl.getAttribute('input'));
        if (!input) return;
        const current = input.value || '';
        const sep = current && !/(<\/p>|<\/h\d>|<br>)\s*$/i.test(current) ? '<br>' : '';
        const next = current + sep + html + '<p><br></p>';
        input.value = next;
        editorEl.editor.loadHTML(next);
    }

    // --- Attach toolbar additions on each editor init -------------------
    function augmentEditor(editorEl) {
        if (!editorEl || editorEl.dataset.fnrExt === '1') return;
        if (!editorEl.editor) return; // not yet initialised — wait for event

        const toolbar = editorEl.toolbarElement; // <trix-toolbar>
        if (!toolbar) return;

        // Filament's RichEditor toolbar uses data-trix-button-group
        // attributes instead of the upstream Trix classes.
        const textGroup = toolbar.querySelector('[data-trix-button-group="text-tools"]')
            || toolbar.querySelector('.trix-button-group--text-tools');
        const blockGroup = toolbar.querySelector('[data-trix-button-group="block-tools"]')
            || toolbar.querySelector('.trix-button-group--block-tools');
        if (!textGroup) return;

        editorEl.dataset.fnrExt = '1';
        const editor = editorEl.editor;

        // --- Color ---
        const colorBtn = makeButton({
            title: 'Цвет текста',
            cls: 'fnr-trix-color',
            icon: svgIcon('<path d="M4 20h16"/><path d="M9 4l-5 13"/><path d="M15 4l5 13"/><path d="M6 13h12"/>'),
            onClick: function (e) {
                e.preventDefault();
                e.stopPropagation();
                const rect = colorBtn.getBoundingClientRect();
                const pop = makeSwatchPopover(function (hex) {
                    applyAttribute(editor, 'fnrColor', hex);
                });
                pop.style.left = (window.scrollX + rect.left) + 'px';
                pop.style.top = (window.scrollY + rect.bottom + 4) + 'px';
                document.body.appendChild(pop);
            },
        });
        // Tint the icon to indicate "color".
        colorBtn.style.color = '#ef4444';
        textGroup.appendChild(colorBtn);

        // --- Highlight ---
        const hiBtn = makeButton({
            title: 'Цвет выделения',
            cls: 'fnr-trix-highlight',
            icon: svgIcon('<path d="M9 11l-6 6v3h3l6-6"/><path d="M22 12l-7-7-9 9 7 7"/>'),
            onClick: function (e) {
                e.preventDefault();
                e.stopPropagation();
                const rect = hiBtn.getBoundingClientRect();
                const pop = makeSwatchPopover(function (hex) {
                    applyAttribute(editor, 'fnrHighlight', hex);
                });
                pop.style.left = (window.scrollX + rect.left) + 'px';
                pop.style.top = (window.scrollY + rect.bottom + 4) + 'px';
                document.body.appendChild(pop);
            },
        });
        textGroup.appendChild(hiBtn);

        // --- Video embed ---
        const target = blockGroup || textGroup;
        const vidBtn = makeButton({
            title: 'Вставить видео (YouTube / Vimeo / .mp4)',
            cls: 'fnr-trix-video',
            icon: svgIcon('<polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>'),
            onClick: function (e) {
                e.preventDefault();
                e.stopPropagation();
                const url = window.prompt('URL видео (YouTube, Vimeo или .mp4/.webm):');
                if (!url) return;
                const html = videoEmbed(url);
                if (html) insertEmbedAttachment(editor, html);
            },
        });
        target.appendChild(vidBtn);

        // --- Image by URL ---
        const imgUrlBtn = makeButton({
            title: 'Вставить картинку по URL',
            cls: 'fnr-trix-image-url',
            icon: svgIcon('<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'),
            onClick: function (e) {
                e.preventDefault();
                e.stopPropagation();
                const url = window.prompt('URL изображения:');
                if (!url) return;
                const safe = url.replace(/"/g, '&quot;');
                insertRawHtml(editorEl, '<img src="' + safe + '" alt="" style="max-width: 100%; height: auto;">');
            },
        });
        target.appendChild(imgUrlBtn);
    }

    document.addEventListener('trix-initialize', function (event) {
        augmentEditor(event.target);
    });

    // Filament loads its asset bundle after Trix has already initialised
    // the editors, so the initial 'trix-initialize' events fire before
    // this listener is attached. Sweep the DOM on load and on Livewire
    // morphs to catch existing editors and any that get added later.
    function sweep(root) {
        (root || document).querySelectorAll('trix-editor').forEach(augmentEditor);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { sweep(); });
    } else {
        sweep();
    }
    // Re-sweep when Livewire / Alpine swaps a chunk of the DOM (modal opens,
    // new repeater row, page navigation, etc).
    if (window.Livewire) {
        window.Livewire.hook('morph.updated', function ({ el }) { sweep(el); });
        window.Livewire.hook('commit', function () { sweep(); });
    }
    const mo = new MutationObserver(function (mutations) {
        for (const m of mutations) {
            m.addedNodes.forEach(function (n) {
                if (n.nodeType !== 1) return;
                if (n.tagName === 'TRIX-EDITOR') augmentEditor(n);
                else if (n.querySelectorAll) n.querySelectorAll('trix-editor').forEach(augmentEditor);
            });
        }
    });
    mo.observe(document.body, { childList: true, subtree: true });
})();
