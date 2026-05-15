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

    /**
     * Direct multipart upload to /admin-editor-upload (custom controller)
     * that mirrors AttachmentSaver and returns a stable /i{hash} URL.
     * The endpoint is gated by the Filament auth bridge.
     *
     * @param {File} file
     * @returns {Promise<{ok:boolean, url?:string, kind?:'image'|'video'|'file', mime?:string, error?:string}>}
     */
    async function uploadEditorFile(file) {
        const cfg = window.fnrTrix || {};
        const maxMb = parseInt(cfg.maxUploadMb || 100, 10);
        if (file.size > maxMb * 1024 * 1024) {
            return { ok: false, error: 'Файл больше ' + maxMb + ' MB' };
        }
        if (!cfg.uploadUrl) {
            return { ok: false, error: 'Endpoint загрузки не настроен' };
        }
        const fd = new FormData();
        fd.append('file', file);

        try {
            const resp = await fetch(cfg.uploadUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': cfg.csrf || '',
                    'Accept': 'application/json',
                },
                body: fd,
            });
            if (!resp.ok) {
                const text = await resp.text().catch(() => '');
                return { ok: false, error: 'HTTP ' + resp.status + (text ? ': ' + text.slice(0, 200) : '') };
            }
            return await resp.json();
        } catch (e) {
            return { ok: false, error: (e && e.message) || 'network error' };
        }
    }

    function pickFile(accept) {
        return new Promise(function (resolve) {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = accept;
            input.style.display = 'none';
            input.addEventListener('change', function () {
                const file = input.files && input.files[0];
                document.body.removeChild(input);
                resolve(file || null);
            }, { once: true });
            document.body.appendChild(input);
            input.click();
        });
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

        // --- Upload image (file picker, NOT URL prompt) ---
        const target = blockGroup || textGroup;
        const imgBtn = makeButton({
            title: 'Загрузить изображение',
            cls: 'fnr-trix-image',
            icon: svgIcon('<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'),
            onClick: async function (e) {
                e.preventDefault();
                e.stopPropagation();
                const file = await pickFile('image/*');
                if (!file) return;
                imgBtn.disabled = true;
                const prevIcon = imgBtn.innerHTML;
                imgBtn.innerHTML = '…';
                const result = await uploadEditorFile(file);
                imgBtn.disabled = false;
                imgBtn.innerHTML = prevIcon;
                if (!result.ok || !result.url) {
                    window.alert('Не удалось загрузить: ' + (result.error || 'unknown'));
                    return;
                }
                const safe = result.url.replace(/"/g, '&quot;');
                insertRawHtml(editorEl, '<img src="' + safe + '" alt="" style="max-width: 100%; height: auto;">');
            },
        });
        target.appendChild(imgBtn);

        // --- Upload video (file picker) ---
        const vidBtn = makeButton({
            title: 'Загрузить видео (mp4 / webm / mov)',
            cls: 'fnr-trix-video',
            icon: svgIcon('<polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>'),
            onClick: async function (e) {
                e.preventDefault();
                e.stopPropagation();
                const file = await pickFile('video/*');
                if (!file) return;
                vidBtn.disabled = true;
                const prevIcon = vidBtn.innerHTML;
                vidBtn.innerHTML = '…';
                const result = await uploadEditorFile(file);
                vidBtn.disabled = false;
                vidBtn.innerHTML = prevIcon;
                if (!result.ok || !result.url) {
                    window.alert('Не удалось загрузить: ' + (result.error || 'unknown'));
                    return;
                }
                const safe = result.url.replace(/"/g, '&quot;');
                const mime = (result.mime || '').replace(/"/g, '&quot;');
                const html = '<video controls preload="metadata" style="max-width: 100%;" src="' + safe + '"' + (mime ? ' type="' + mime + '"' : '') + '></video>';
                insertEmbedAttachment(editor, html);
            },
        });
        target.appendChild(vidBtn);

        // --- Image resize (drag handle + width presets popover) ----
        attachImageResize(editorEl, editor);
    }

    /**
     * Image-resize UX:
     *
     *   1. Click on an image inside the editor → a floating popover
     *      anchored to the document.body opens with width presets
     *      (25/50/75/100% / auto) and a manual px input.
     *   2. While the popover is open, a blue dashed outline is drawn
     *      over the image.
     *   3. Trix represents inserted images in two ways and we handle
     *      both:
     *        • Attachment image (Filament's "attachFiles" / our
     *          "Upload image" via Trix.insertAttachment) — lives inside
     *          a <figure data-trix-attachment>. Use Attachment.setAttributes
     *          ({ width, height }) so the value survives every Trix
     *          re-render of the attachment HTML.
     *        • Raw <img> inserted via loadHTML — directly set inline
     *          style on the <img>. Trix preserves inline styles in this
     *          path.
     *   4. After mutating, force re-serialisation via Trix's input
     *      element so Livewire picks up the new HTML.
     */
    function attachImageResize(editorEl, editor) {
        // Single delegated click listener — no per-image setup that
        // Trix can later wipe during a re-render.
        editorEl.addEventListener('click', function (e) {
            const img = e.target && e.target.tagName === 'IMG' ? e.target : null;
            if (!img || !editorEl.contains(img)) return;
            e.preventDefault();
            e.stopPropagation();
            openResizePopover(img, editorEl, editor);
        });
    }

    function highlightImage(img, on) {
        if (on) {
            img.dataset.fnrPrevOutline = img.style.outline || '';
            img.style.outline = '2px dashed #3b82f6';
            img.style.outlineOffset = '2px';
        } else {
            img.style.outline = img.dataset.fnrPrevOutline || '';
            img.style.outlineOffset = '';
            delete img.dataset.fnrPrevOutline;
        }
    }

    function openResizePopover(img, editorEl, editor) {
        highlightImage(img, true);

        const rect = img.getBoundingClientRect();
        const pop = makeWidthPresetPopover(
            function (value) {
                applyImageWidth(img, value, editorEl, editor);
            },
            function () { highlightImage(img, false); },
        );
        pop.style.left = (window.scrollX + Math.max(8, rect.left)) + 'px';
        pop.style.top = (window.scrollY + rect.bottom + 6) + 'px';
        document.body.appendChild(pop);
    }

    /**
     * Apply the chosen width to an image, handling both attachment and
     * raw paths. value: '25%' | '50%' | '75%' | '100%' | 'auto' | 'NNNpx'.
     */
    function applyImageWidth(img, value, editorEl, editor) {
        const figure = img.closest('figure[data-trix-attachment]');

        if (figure && editor) {
            // Attachment path — find the Attachment object and call
            // setAttributes so Trix persists width/height into the
            // attachment metadata and re-renders the figure with the
            // correct img width/height attributes.
            const attachment = findAttachmentForFigure(editor, figure);
            if (attachment) {
                const dims = computeDimensions(img, value);
                if (dims === null) {
                    attachment.setAttributes({ width: null, height: null });
                } else {
                    attachment.setAttributes(dims);
                }
                // Trix's "input" event is what Livewire/Filament listen
                // for; recordUndoEntry plus a synthetic input dispatch
                // makes sure the form sees the change.
                if (typeof editor.recordUndoEntry === 'function') {
                    editor.recordUndoEntry('Resize image');
                }
                editorEl.dispatchEvent(new Event('input', { bubbles: true }));
                editorEl.dispatchEvent(new Event('change', { bubbles: true }));
                return;
            }
        }

        // Raw <img> path.
        if (value === 'auto' || value === null) {
            img.style.removeProperty('width');
            img.style.removeProperty('height');
        } else {
            img.style.width = value;
            img.style.height = 'auto';
            img.style.maxWidth = '100%';
        }
        if (editor && typeof editor.recordUndoEntry === 'function') {
            editor.recordUndoEntry('Resize image');
        }
        // For raw images we need to write the change back into the
        // hidden <input> that Trix maintains, otherwise loadHTML on
        // next render will revert it.
        try {
            const inputId = editorEl.getAttribute('input');
            const hidden = inputId && document.getElementById(inputId);
            if (hidden && editorEl.innerHTML) {
                hidden.value = editorEl.innerHTML;
            }
        } catch (_) {}
        editorEl.dispatchEvent(new Event('input', { bubbles: true }));
        editorEl.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function findAttachmentForFigure(editor, figure) {
        try {
            const id = figure.getAttribute('data-trix-id');
            const doc = editor.getDocument();
            const attachments = doc.getAttachments();
            for (let i = 0; i < attachments.length; i++) {
                const a = attachments[i];
                if (id && String(a.id) === String(id)) return a;
            }
            // Fallback: match by URL.
            const innerImg = figure.querySelector('img');
            const src = innerImg ? innerImg.getAttribute('src') : null;
            if (src) {
                for (let i = 0; i < attachments.length; i++) {
                    const attrs = attachments[i].getAttributes();
                    if (attrs && (attrs.url === src || attrs.href === src)) {
                        return attachments[i];
                    }
                }
            }
        } catch (_) {}
        return null;
    }

    /**
     * Convert a width preset/value into {width, height} pixels that
     * preserve the image's natural aspect ratio. Trix attachments need
     * both dimensions in pixels — percentages aren't supported.
     */
    function computeDimensions(img, value) {
        if (value === 'auto' || value === null) return null;

        const naturalW = img.naturalWidth || img.getBoundingClientRect().width || 600;
        const naturalH = img.naturalHeight || img.getBoundingClientRect().height || 400;
        const aspect = naturalH / naturalW;

        let targetW;
        const m = String(value).match(/^(\d+)%$/);
        if (m) {
            const pct = parseInt(m[1], 10) / 100;
            // Resolve % against the editor's content width so the
            // resulting pixel value reflects the actual rendered size.
            const editorWidth = (img.closest('trix-editor')?.clientWidth) || naturalW;
            targetW = Math.max(40, Math.round(editorWidth * pct));
        } else {
            const px = parseInt(value, 10);
            targetW = Math.max(40, isFinite(px) ? px : naturalW);
        }
        const targetH = Math.max(20, Math.round(targetW * aspect));
        return { width: targetW, height: targetH };
    }

    function makeWidthPresetPopover(onPick, onClose) {
        const wrap = document.createElement('div');
        Object.assign(wrap.style, {
            position: 'absolute', zIndex: 9999, background: '#fff',
            border: '1px solid #d1d5db', borderRadius: '8px',
            padding: '8px', display: 'flex', flexDirection: 'column',
            gap: '4px', boxShadow: '0 8px 24px rgba(0,0,0,.18)',
            minWidth: '180px',
        });
        const close = function () {
            document.removeEventListener('click', outside, true);
            if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
            if (typeof onClose === 'function') onClose();
        };
        const outside = function (e) { if (!wrap.contains(e.target)) close(); };
        setTimeout(function () { document.addEventListener('click', outside, true); }, 0);

        ['25%', '50%', '75%', '100%', 'auto'].forEach(function (preset) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = 'Ширина: ' + preset;
            Object.assign(btn.style, {
                padding: '6px 10px', borderRadius: '6px',
                border: '1px solid #d1d5db', background: '#f9fafb',
                cursor: 'pointer', fontSize: '13px', color: '#374151',
                textAlign: 'left',
            });
            btn.addEventListener('click', function () { onPick(preset); close(); });
            wrap.appendChild(btn);
        });

        const customWrap = document.createElement('div');
        Object.assign(customWrap.style, { display: 'flex', gap: '4px', alignItems: 'center', marginTop: '4px' });
        const input = document.createElement('input');
        input.type = 'number';
        input.placeholder = 'px';
        input.min = '40';
        input.max = '4000';
        Object.assign(input.style, {
            flex: '1', padding: '4px 6px', borderRadius: '4px',
            border: '1px solid #d1d5db', fontSize: '13px',
        });
        const apply = document.createElement('button');
        apply.type = 'button';
        apply.textContent = 'OK';
        Object.assign(apply.style, {
            padding: '4px 10px', borderRadius: '4px',
            border: '1px solid #3b82f6', background: '#3b82f6',
            color: '#fff', cursor: 'pointer', fontSize: '13px',
        });
        apply.addEventListener('click', function () {
            const v = parseInt(input.value, 10);
            if (v && v >= 40 && v <= 4000) { onPick(v + 'px'); close(); }
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); apply.click(); }
        });
        customWrap.appendChild(input);
        customWrap.appendChild(apply);
        wrap.appendChild(customWrap);

        return wrap;
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
