@extends('user.layouts.main')
@section('content')
<main class="policy-page">
	<section class="policy">
		<div class="content">
            @include('user.partials.page-heading', [
                'crumbs' => [
                    ['label' => __('site.item_home'), 'url' => '/'],
                    ['label' => __('site.terms_breadcrumb')],
                ],
                'badge' => __('site.terms_breadcrumb'),
                'badgeIcon' => '/assets/img/icon_star.svg',
                'title' => __('site.page_title_terms'),
            ])

            @if(!empty($content))
            <div class="policy__content">@richHtml($content)</div>
            @else
            <p class="policy__part-caption">{{ __('site.terms_1_title') }}</p>

            <p class="policy__text">{{ __('site.terms_1_1') }}</p>

            <p class="policy__part-caption">{{ __('site.terms_2_title') }}</p>

            <p class="policy__text">{{ __('site.terms_2_1') }}</p>
            <p class="policy__text">{{ __('site.terms_2_2') }}</p>
            <p class="policy__text">{{ __('site.terms_2_3') }}</p>
            <p class="policy__text">{{ __('site.terms_2_4') }}</p>
            <p class="policy__text">{{ __('site.terms_2_5') }}</p>

            <p class="policy__part-caption">{{ __('site.terms_3_title') }}</p>

            <p class="policy__text">{{ __('site.terms_3_1') }}</p>
            <p class="policy__text">{{ __('site.terms_3_2') }}</p>
            <p class="policy__text">{{ __('site.terms_3_3') }}</p>
            <p class="policy__text">{{ __('site.terms_3_4') }}</p>
            <p class="policy__text">{{ __('site.terms_3_5') }}</p>
            <p class="policy__text">{{ __('site.terms_3_6') }}</p>
            <p class="policy__text">{{ __('site.terms_3_7') }}</p>

            <p class="policy__part-caption">{{ __('site.terms_4_title') }}</p>

            <p class="policy__text">{{ __('site.terms_4_1') }}</p>
            <p class="policy__text">{{ __('site.terms_4_2') }}</p>
            <p class="policy__text">{{ __('site.terms_4_3') }}</p>
            <p class="policy__text">{{ __('site.terms_4_4') }}</p>
            <p class="policy__text">{{ __('site.terms_4_5') }}</p>
            <p class="policy__text">{{ __('site.terms_4_6') }}</p>

            <p class="policy__part-caption">{{ __('site.terms_5_title') }}</p>

            <p class="policy__text">{{ __('site.terms_5_1') }} <a target="_blank" href="https://t.me/Fnrus_Keys">@Fnrus_Keys</a></p>

            <p class="policy__part-caption">{{ __('site.terms_6_title') }}</p>

            <p class="policy__text">{{ __('site.terms_6_1') }} <a href="mailto:Fnrus@proton.me">Fnrus@proton.me</a></p>

            @endif
		</div>
	</section>
</main>
@endsection
