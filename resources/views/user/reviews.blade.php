@extends('user.layouts.main')
@section('content')
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
                <button class="btn btn-accent reviews-page__leave-btn" data-popup="review">{{ __('site.btn_leave_review') }}</button>
            </div>

            @if(count($reviews))
            <div class="reviews-grid">
                @foreach($reviews as $review)
                <div class="rev-card">
                    <div>
                        <div class="rev-card__head">
                            <img class="rev-card__avatar" src="{{ $review->avatar }}" alt="" loading="lazy" onerror="this.onerror=null;this.src='/assets/img/default_avatar.svg'">
                            <div class="rev-card__meta">
                                <p class="rev-card__name">{{ $review->author }}</p>
                                <div class="rev-card__stars">
                                    @for($i = 0; $i < 5; $i++)<img src="/assets/img/rv-star.svg" alt="">@endfor
                                </div>
                            </div>
                        </div>
                        <p class="rev-card__text">{{ $review->text }}</p>
                    </div>
                    @if(!empty($review->link))
                    <a target="_blank" href="{{ $review->link }}" class="rev-card__btn">{{ __('site.btn_reviews') }}</a>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <p class="reviews-page__empty">{{ __('site.reviews_empty') }}</p>
            @endif
        </div>
    </section>
</main>
@endsection
