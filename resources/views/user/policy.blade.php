@extends('user.layouts.main')
@section('content')
<main>
	<section class="policy">
		<div class="content">
            <ul class="breadcrumbs">
                <li><a href="/">{{ __('site.item_home') }}</a></li><span style="font-size:14px;margin:0 7px;opacity: 0.7">→</span><li><a href="/policy">{{ __('site.policy_breadcrumb') }}</a></li>
            </ul>

            @if(!empty($policy_content))
            <style>
                /* Regular text paragraphs */
                .policy__content p {
                    padding-left: 16px;
                    font-size: 14px;
                    line-height: 20px;
                    font-weight: 500;
                    color: rgba(255, 255, 255, .6);
                    margin-bottom: 4px;
                }
                .policy__content p a {
                    color: #c0a8ff;
                    text-decoration: underline;
                }
                .policy__content p a:hover {
                    text-decoration: none;
                }
                /* Legacy class-based headers */
                .policy__content p.policy__part-caption {
                    padding-left: 0;
                    font-size: 16px;
                    font-weight: 600;
                    font-family: 'Mazzard M';
                    margin-bottom: 15px;
                    color: rgba(255, 255, 255, 1);
                }
                .policy__content p.policy__part-caption:not(:first-child) {
                    margin-top: 35px;
                }
                /* Quill header tags — section titles */
                .policy__content h1,
                .policy__content h2,
                .policy__content h3 {
                    padding-left: 0;
                    font-weight: 600;
                    font-family: 'Mazzard M';
                    margin-bottom: 15px;
                    color: rgba(255, 255, 255, 1);
                }
                .policy__content h1 { font-size: 20px; }
                .policy__content h2 { font-size: 18px; }
                .policy__content h3 { font-size: 16px; }
                .policy__content h1:not(:first-child),
                .policy__content h2:not(:first-child),
                .policy__content h3:not(:first-child) {
                    margin-top: 35px;
                }
                .policy__content h1 a,
                .policy__content h2 a,
                .policy__content h3 a {
                    color: #c0a8ff;
                    text-decoration: underline;
                }
                /* Quill size classes */
                .policy__content .ql-size-small { font-size: 12px; }
                .policy__content .ql-size-large { font-size: 18px; }
                .policy__content .ql-size-huge { font-size: 24px; }
                .policy__content .ql-size-14px { font-size: 14px; }
                .policy__content .ql-size-16px { font-size: 16px; }
                .policy__content .ql-size-18px { font-size: 18px; }
                .policy__content .ql-size-20px { font-size: 20px; }
                .policy__content .ql-size-24px { font-size: 24px; }
                /* Quill indent classes */
                .policy__content .ql-indent-1 { padding-left: 3em; }
                .policy__content .ql-indent-2 { padding-left: 6em; }
                .policy__content .ql-indent-3 { padding-left: 9em; }
                /* Quill alignment */
                .policy__content .ql-align-center { text-align: center; }
                .policy__content .ql-align-right { text-align: right; }
                .policy__content .ql-align-justify { text-align: justify; }
                /* Quill lists */
                .policy__content ol, .policy__content ul {
                    padding-left: 32px;
                    font-size: 14px;
                    line-height: 20px;
                    font-weight: 500;
                    color: rgba(255, 255, 255, .6);
                    margin-bottom: 4px;
                }
                /* Quill blockquote */
                .policy__content blockquote {
                    border-left: 3px solid rgba(255, 255, 255, .3);
                    padding-left: 16px;
                    margin-left: 16px;
                    font-size: 14px;
                    color: rgba(255, 255, 255, .5);
                    font-style: italic;
                }
            </style>
            <div class="policy__content">@richHtml($policy_content)</div>
            @else
            <p class="policy__part-caption">{{ __('site.policy_1_title') }}</p>

            <p class="policy__text">{{ __('site.policy_1_1') }}</p>

            <p class="policy__part-caption">{{ __('site.policy_2_title') }}</p>

            <p class="policy__text">{{ __('site.policy_2_1') }}</p>

            <p class="policy__text">{{ __('site.policy_2_2') }}</p>

            <p class="policy__text">{{ __('site.policy_2_3') }}</p>

            <p class="policy__text">{{ __('site.policy_2_4') }}</p>

            <p class="policy__text">{{ __('site.policy_2_5') }}</p>

            <p class="policy__part-caption">{{ __('site.policy_3_title') }}</p>

            <p class="policy__text">{{ __('site.policy_3_1') }}</p>

            <p class="policy__text">{{ __('site.policy_3_2') }}</p>

            <p class="policy__text">{{ __('site.policy_3_3') }}</p>

            <p class="policy__text">{{ __('site.policy_3_4') }}</p>

            <p class="policy__text">{{ __('site.policy_3_5') }}</p>

            <p class="policy__text">{{ __('site.policy_3_6') }}</p>

            <p class="policy__text">{{ __('site.policy_3_7') }}</p>

            <p class="policy__part-caption">{{ __('site.policy_4_title') }}</p>

            <p class="policy__text">{{ __('site.policy_4_1') }}</p>

            <p class="policy__text">{{ __('site.policy_4_2') }}</p>

            <p class="policy__text">{{ __('site.policy_4_3') }}</p>

            <p class="policy__text">{{ __('site.policy_4_4') }}</p>

            <p class="policy__text">{{ __('site.policy_4_5') }}</p>

            <p class="policy__text">{{ __('site.policy_4_6') }}</p>

            <p class="policy__part-caption">{{ __('site.policy_5_title') }}</p>

            <p class="policy__text">{{ __('site.policy_5_1') }}</p>

            <p class="policy__part-caption">{{ __('site.policy_6_title') }}</p>

            <p class="policy__text">{{ __('site.policy_6_1') }} <a target="_blank" href="https://t.me/Fnrus_Keys">@Fnrus_Keys</a></p>

            <p class="policy__part-caption">{{ __('site.policy_7_title') }}</p>

            <p class="policy__text">{{ __('site.policy_7_1') }} <a href="mailto:Fnrus@proton.me">Fnrus@proton.me</a></p>
            @endif
		</div>
	</section>
</main>
@endsection
