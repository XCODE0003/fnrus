{{--
  Technical-support section (ТЗ §5) — sits directly under the FAQ block on
  every page that has one, and is the scroll target of the header's
  "Техподдержка" item (/#support).

  Contacts come from the existing Filament settings (settings/support) —
  support_btn1..3 — so no new columns. The button text is the network name;
  the handle under it is derived from the URL, and the icon is picked from the
  URL's host so a "Вконтакте" button never renders a Discord logo.
  Rendered outside the pages' @if($faq) guard so #support always exists.
--}}
@php
    $__support = \App\Models\ShopSettings::getDefault();

    $__contacts = [];
    foreach ([1, 2, 3] as $__i) {
        $__url = trim((string) ($__support->{'support_btn' . $__i . '_url'} ?? ''));
        if ($__url === '') { continue; }
        $__text = trim((string) ($__support->{'support_btn' . $__i . '_text'} ?? '')) ?: parse_url($__url, PHP_URL_HOST);
        $__host = strtolower((string) parse_url($__url, PHP_URL_HOST));
        $__path = trim((string) parse_url($__url, PHP_URL_PATH), '/');

        if (str_contains($__host, 't.me') || str_contains($__host, 'telegram')) {
            $__icon = 'telegram';
            $__handle = $__path !== '' ? '@' . $__path : $__url;
        } elseif (str_contains($__host, 'discord')) {
            $__icon = 'discord';
            $__handle = $__host . ($__path !== '' ? '/' . $__path : '');
        } elseif (str_contains($__host, 'vk.com') || str_contains($__host, 'vk.ru')) {
            $__icon = 'vk';
            $__handle = $__path !== '' ? '@' . $__path : $__url;
        } elseif (str_contains($__host, 'youtube') || str_contains($__host, 'youtu.be')) {
            $__icon = 'youtube';
            $__handle = $__host . ($__path !== '' ? '/' . $__path : '');
        } else {
            // Unknown host — fall back to the button's own name.
            $__t = mb_strtolower($__text);
            $__icon = str_contains($__t, 'telegram') || str_contains($__t, 'телеграм') ? 'telegram'
                : (str_contains($__t, 'discord') || str_contains($__t, 'дискорд') ? 'discord'
                : (str_contains($__t, 'вконтакте') || str_contains($__t, 'vk') ? 'vk' : 'link'));
            $__handle = $__host . ($__path !== '' ? '/' . $__path : '');
        }

        $__contacts[] = ['url' => $__url, 'label' => $__text, 'handle' => $__handle, 'icon' => $__icon];
    }

    // Fall back to the footer's Telegram bot when support buttons are unset.
    if (!count($__contacts) && !empty($__support->btn_tg_bot_url)) {
        $__contacts[] = [
            'url' => $__support->btn_tg_bot_url,
            'label' => 'Telegram',
            'handle' => '@' . trim((string) parse_url($__support->btn_tg_bot_url, PHP_URL_PATH), '/'),
            'icon' => 'telegram',
        ];
    }

    $__desc = $__support->support_text ?: __('site.support_desc');
@endphp
<section class="support" id="support">
    <div class="content support__grid">
        <div class="support__intro">
            <span class="support__eyebrow">{{ __('site.support_eyebrow') }}</span>
            <h2 class="support__title">
                {{ __('site.support_heading') }}
                <span class="support__title-accent">{{ __('site.support_heading_accent') }}</span>
            </h2>
            <p class="support__desc">{{ $__desc }}</p>
            <span class="support__chip">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ __('site.support_reply_time') }}
            </span>
        </div>

        @if(count($__contacts))
            <div class="support__cards">
                @foreach($__contacts as $__c)
                    <a class="support__card" href="{{ $__c['url'] }}" target="_blank" rel="noopener noreferrer">
                        <span class="support__card-icon">@include('user.partials.icon', ['icon' => $__c['icon'], 'color' => '#cbb6ff', 'size' => 22])</span>
                        <span class="support__card-body">
                            <span class="support__card-label">{{ $__c['label'] }}</span>
                            <span class="support__card-value">{{ $__c['handle'] }}</span>
                        </span>
                        <span class="support__card-arrow" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M7 17L17 7M17 7H9M17 7v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
