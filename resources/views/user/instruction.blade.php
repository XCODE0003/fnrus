@extends('user.layouts.main')
@section('content')
<main>
    <section class="instruction" style="padding-top: 130px;padding-bottom: 0px!important;">
        <div class="content instruction__container">
            <p class="section-caption instruction__section-caption">{{ $title }}</p>
            <div class="instruction__links">
                @foreach($instruction_buttons as $button)
                    <a href="{{ $button['url'] }}" class="instruction__link"><span>{{ $button['text'] }}</span></a>
                @endforeach
            </div>
            <div style="line-height: 1.7;color: #b5b5b5;">
                @richHtml($instruction)
            </div>
        </div>
    </section>
    @if($faq)
    <section class="faq" id="faq">
        <div class="content">
            <div class="section-category">
                <div class="section-category__icon"><img src="/assets/img/icon_star.svg" alt=""></div>
                <span class="section-category__text">{{ __('site.section_faq_title') }}</span>
            </div>
            <p class="section-caption">{{ __('site.section_faq_caption') }}</p>
            <div class="faq__container">
                @foreach($faq as $f)
                    <div class="accordion">
                        <div class="accordion__header">
                            <span class="accordion__header__content">{{ $f->localized_question }}</span>
                            <div class="accordion__header__btn"></div>
                        </div>
                        <div class="accordion__body">
                            <p class="accordion__body__content">{!! $f->localized_answer !!}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif
</main>
@endsection
