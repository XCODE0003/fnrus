@extends('user.layouts.main')
@section('content')
<main class="policy-page">
	<section class="policy">
		<div class="content">
            @include('user.partials.page-heading', [
                'crumbs' => [
                    ['label' => __('site.item_home'), 'url' => '/'],
                    ['label' => __('site.privacy_breadcrumb')],
                ],
                'badge' => __('site.privacy_breadcrumb'),
                'badgeIcon' => '/assets/img/icon_star.svg',
                'title' => __('site.page_title_privacy'),
                'subtitle' => __('site.privacy_intro'),
            ])

            @if(!empty($content))
            <div class="policy__content">@richHtml($content)</div>
            @else
            <p class="policy__part-caption">{{ __('site.privacy_1_title') }}</p>

            <p class="policy__text">{{ __('site.privacy_1_1') }}</p>
            <p class="policy__text">{{ __('site.privacy_1_2') }}</p>
            <p class="policy__text">{{ __('site.privacy_1_3') }}</p>
            <p class="policy__text">{{ __('site.privacy_1_4') }}</p>
            <p class="policy__text">{{ __('site.privacy_1_5') }}</p>

            <p class="policy__part-caption">{{ __('site.privacy_2_title') }}</p>

            <p class="policy__text">{{ __('site.privacy_2_1') }}</p>
            <p class="policy__text">{{ __('site.privacy_2_2') }}</p>
            <p class="policy__text">{{ __('site.privacy_2_3') }}</p>
            <p class="policy__text">{{ __('site.privacy_2_4') }}</p>
            <p class="policy__text">{{ __('site.privacy_2_5') }}</p>

            <p class="policy__part-caption">{{ __('site.privacy_3_title') }}</p>

            <p class="policy__text">{{ __('site.privacy_3_1') }}</p>
            <p class="policy__text">{{ __('site.privacy_3_2') }}</p>
            <p class="policy__text">{{ __('site.privacy_3_3') }}</p>

            <p class="policy__part-caption">{{ __('site.privacy_4_title') }}</p>

            <p class="policy__text">{{ __('site.privacy_4_1') }}</p>
            <p class="policy__text">{{ __('site.privacy_4_2') }}</p>
            <p class="policy__text">{{ __('site.privacy_4_3') }}</p>

            <p class="policy__part-caption">{{ __('site.privacy_5_title') }}</p>

            <p class="policy__text">{{ __('site.privacy_5_1') }}</p>
            <p class="policy__text">{{ __('site.privacy_5_2') }}</p>
            <p class="policy__text">{{ __('site.privacy_5_3') }}</p>

            <p class="policy__part-caption">{{ __('site.privacy_6_title') }}</p>

            <p class="policy__text">{{ __('site.privacy_6_1') }}</p>
            <p class="policy__text">{{ __('site.privacy_6_2') }}</p>
            <p class="policy__text">{{ __('site.privacy_6_3') }}</p>

            <p class="policy__part-caption">{{ __('site.privacy_7_title') }}</p>

            <p class="policy__text">{{ __('site.privacy_7_1') }} <a href="mailto:Fnrus@proton.me">Fnrus@proton.me</a></p>

            @endif
		</div>
	</section>
</main>
@endsection
