@extends('user.layouts.main')
@section('content')
<main>
	<section class="not-found">
		<div class="content not-found__container">
			<p class="not-found__code">404</p>
			<h1 class="not-found__caption">{{ __('site.page_not_found') }}</h1>
			<p class="not-found__text">{{ __('site.page_not_found_text') }}</p>
			<a href="/" class="btn btn-accent not-found__btn">{{ __('site.page_not_found_btn') }}</a>
		</div>
	</section>
</main>
@endsection
