@extends('user.layouts.main')
@section('content')
@php
    $reviewCount = $reviews ? count($reviews) : 0;
    // No rating column in DB yet — show static 5.0 average until backend supports it
    $avgRating = '5.0';
@endphp
<main class="reviews-page">
    <section class="reviews-page__section">
        <div class="content">
            <div class="reviews-page__head">
                <div class="reviews__badge">
                    <span class="reviews__badge-icon"><img src="/assets/img/rv-badge.svg" alt=""></span>
                    <span class="reviews__badge-text">{{ __('site.section_reviews_title') }}</span>
                </div>
                <h1 class="reviews-page__title">{{ __('site.reviews_title') }}</h1>
                <p class="reviews-page__subtitle">{{ __('site.reviews_subtitle') }}</p>
            </div>

            {{-- ==== Rating stats card ==== --}}
            <div class="reviews-stats">
                <div class="reviews-stats__rating">
                    <p class="reviews-stats__value">{{ $avgRating }}</p>
                    <div class="reviews-stats__stars" aria-label="{{ $avgRating }} {{ __('site.reviews_of_5') }}">
                        @for($i = 0; $i < 5; $i++)
                            <img src="/assets/img/rv-star.svg" alt="" width="20" height="20">
                        @endfor
                    </div>
                </div>
                <div class="reviews-stats__count">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 17l6-6 4 4 8-8" stroke="#F1FF9D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <polyline points="14 7 21 7 21 14" stroke="#F1FF9D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>@plural($reviewCount, __('site.reviews_count_one'), __('site.reviews_count_few'), __('site.reviews_count_many'))</span>
                </div>
            </div>

            {{-- ==== Inline review form ==== --}}
            <div class="reviews-form" id="reviews-form">
                <p class="reviews-form__title">{{ __('site.btn_leave_review') }}</p>
                <div class="reviews-form__grid">
                    {{-- Honeypots to absorb browser autofill (Chrome/Yandex ignore autocomplete=off) --}}
                    <input type="text" name="fake_user_email" autocomplete="username" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
                    <input type="password" name="fake_user_pass" autocomplete="current-password" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
                    <div class="reviews-form__field">
                        <label class="reviews-form__label" for="rv-author">{{ __('site.review_form_name') }} <span>*</span></label>
                        <input type="text" id="rv-author" name="rv_review_display_name" class="reviews-form__input" placeholder="{{ __('site.review_form_name_ph') }}" maxlength="64" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </div>
                    <div class="reviews-form__field">
                        <label class="reviews-form__label">{{ __('site.review_form_product') }}</label>
                        <div class="rv-select" id="rv-select" data-empty="{{ __('site.review_form_product_ph') }}">
                            <input type="hidden" id="rv-product" value="">
                            <button type="button" class="rv-select__trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="rv-select__value">{{ __('site.review_form_product_ph') }}</span>
                                <svg class="rv-select__chev" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <div class="rv-select__menu" role="listbox" hidden>
                                @foreach($products ?? [] as $product)
                                    <button type="button" class="rv-select__option" role="option" data-value="{{ $product->title }}" data-label="{{ $product->title }}">
                                        <span class="rv-select__dot"></span>
                                        {{ $product->title }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="reviews-form__field reviews-form__field--rating">
                        <label class="reviews-form__label">{{ __('site.review_form_rating') }} <span>*</span></label>
                        <div class="reviews-form__stars" id="rv-stars" role="radiogroup" aria-label="{{ __('site.review_form_rating') }}">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" class="reviews-form__star {{ $i <= 5 ? 'is-active' : '' }}" data-value="{{ $i }}" aria-label="{{ $i }}/5">
                                    <img src="/assets/img/rv-star.svg" alt="" width="28" height="28">
                                </button>
                            @endfor
                        </div>
                        <input type="hidden" id="rv-rating" value="5">
                    </div>
                    <div class="reviews-form__field reviews-form__field--wide">
                        <label class="reviews-form__label" for="rv-text">{{ __('site.review_form_text') }} <span>*</span></label>
                        <div class="reviews-form__textarea-wrap">
                            <textarea id="rv-text" class="reviews-form__textarea" rows="5" maxlength="1000" placeholder="{{ __('site.review_form_text_ph_long') }}"></textarea>
                            <span class="reviews-form__counter" id="rv-counter">0/1000</span>
                        </div>
                    </div>
                </div>
                <div class="reviews-form__actions">
                    <button type="button" class="reviews-form__submit" id="rv-submit">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ __('site.btn_leave_review') }}
                    </button>
                </div>
            </div>

            {{-- ==== Existing reviews grid ==== --}}
            @if(count($reviews))
            <div class="reviews-grid">
                @foreach($reviews as $review)
                @php
                    // 1-2 letter initials from author name
                    $initials = collect(preg_split('/\s+/u', trim($review->author ?? '')))
                        ->filter()
                        ->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                        ->take(2)
                        ->implode('');
                    // Prefer the dedicated product column; fall back to legacy data
                    // stored in `link` ("5★ · Name" or a plain product name; URLs hidden).
                    $productLine = trim($review->product ?? '');
                    if ($productLine === '') {
                        $l = $review->link ?? '';
                        if ($l && preg_match('/^\s*\d+★\s*·\s*(.+)$/u', $l, $m)) {
                            $productLine = trim($m[1]);
                        } elseif ($l && !preg_match('~^https?://~i', $l)) {
                            $productLine = trim($l);
                        }
                    }
                @endphp
                <div class="rev-card">
                    <div class="rev-card__head">
                        <span class="rev-card__avatar" aria-hidden="true">{{ $initials ?: '?' }}</span>
                        <div class="rev-card__meta">
                            <p class="rev-card__name">{{ $review->author }}</p>
                            @if(!empty($productLine))<p class="rev-card__sub">{{ $productLine }}</p>@endif
                        </div>
                        @if(!empty($review->created_at))
                        <span class="rev-card__date">{{ \Carbon\Carbon::parse($review->created_at)->translatedFormat('j M Y') }}</span>
                        @endif
                    </div>
                    <div class="rev-card__stars">
                        @for($i = 0; $i < 5; $i++)<img src="/assets/img/rv-star.svg" alt="">@endfor
                    </div>
                    <p class="rev-card__text">{{ $review->text }}</p>
                </div>
                @endforeach
            </div>
            @else
            <p class="reviews-page__empty">{{ __('site.reviews_empty') }}</p>
            @endif
        </div>
    </section>
</main>

<script>
(function(){
    var ta = document.getElementById('rv-text');
    var counter = document.getElementById('rv-counter');
    if (ta && counter) {
        var upd = function() { counter.textContent = ta.value.length + '/1000'; };
        ta.addEventListener('input', upd); upd();
    }

    var ratingHidden = document.getElementById('rv-rating');
    var stars = document.querySelectorAll('#rv-stars .reviews-form__star');
    function paint(n) {
        stars.forEach(function(s, i){ s.classList.toggle('is-active', i < n); });
    }
    stars.forEach(function(star){
        star.addEventListener('click', function(){
            var v = +star.getAttribute('data-value');
            ratingHidden.value = v;
            paint(v);
        });
        star.addEventListener('mouseenter', function(){
            paint(+star.getAttribute('data-value'));
        });
    });
    var starsWrap = document.getElementById('rv-stars');
    if (starsWrap) starsWrap.addEventListener('mouseleave', function(){ paint(+ratingHidden.value); });

    // ===== Custom product select =====
    var sel = document.getElementById('rv-select');
    if (sel) {
        var trigger = sel.querySelector('.rv-select__trigger');
        var menu = sel.querySelector('.rv-select__menu');
        var valueLbl = sel.querySelector('.rv-select__value');
        var hidden = document.getElementById('rv-product');
        var placeholder = sel.getAttribute('data-empty');

        function close(){ sel.classList.remove('is-open'); menu.hidden = true; trigger.setAttribute('aria-expanded','false'); }
        function open(){ sel.classList.add('is-open'); menu.hidden = false; trigger.setAttribute('aria-expanded','true'); }

        trigger.addEventListener('click', function(e){
            e.stopPropagation();
            if (sel.classList.contains('is-open')) close(); else open();
        });
        menu.addEventListener('click', function(e){
            var opt = e.target.closest('.rv-select__option');
            if (!opt) return;
            var v = opt.getAttribute('data-value');
            var l = opt.getAttribute('data-label');
            hidden.value = v;
            valueLbl.textContent = l;
            valueLbl.classList.remove('rv-select__value--placeholder');
            sel.classList.add('has-value');
            menu.querySelectorAll('.rv-select__option').forEach(function(o){ o.classList.toggle('is-selected', o === opt); });
            close();
        });
        document.addEventListener('click', function(e){ if (!sel.contains(e.target)) close(); });
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') close(); });

        // reset
        window._resetProductSelect = function(){
            hidden.value = '';
            valueLbl.textContent = placeholder;
            sel.classList.remove('has-value');
            menu.querySelectorAll('.rv-select__option').forEach(function(o){ o.classList.remove('is-selected'); });
        };
    }

    var submit = document.getElementById('rv-submit');
    if (submit) {
        submit.addEventListener('click', function(){
            var author = (document.getElementById('rv-author').value || '').trim();
            var text = (document.getElementById('rv-text').value || '').trim();
            var product = (document.getElementById('rv-product').value || '').trim();
            var rating = ratingHidden.value;
            if (!author || !text) {
                if (window.showNotification) window.showNotification(@json(__('site.review_form_validation')), 'fail');
                return;
            }
            // Product is stored in its OWN column now (shown under the nickname).
            submit.disabled = true;
            $.ajax({
                type: 'POST',
                url: (window.api_url || (location.origin + '/api')) + '/reviews/submit',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify({ author: author, text: text, link: '', product: product || '' }),
                success: function(data){
                    submit.disabled = false;
                    if (data.ok === true) {
                        if (window.showNotification) window.showNotification(data.description, 'success');
                        document.getElementById('rv-author').value = '';
                        document.getElementById('rv-text').value = '';
                        if (typeof window._resetProductSelect === 'function') window._resetProductSelect();
                        ratingHidden.value = 5; paint(5);
                        if (counter) counter.textContent = '0/1000';
                    } else if (window.showNotification) {
                        window.showNotification(data.description || @json(__('site.cancel_error')), 'fail');
                    }
                },
                error: function(){
                    submit.disabled = false;
                    if (window.showNotification) window.showNotification(@json(__('site.invoice_network_error')), 'fail');
                }
            });
        });
    }
})();
</script>
@endsection
