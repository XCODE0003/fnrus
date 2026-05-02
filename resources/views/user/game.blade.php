@extends('user.layouts.main')
@section('content')
    <main class="game">
        <section class="game-cheats">
                <div class="content game-cheats__slider-container">
                    <div class="section-caption-container">
                        <div class="section-caption-container__inner">
                            <p class="section-caption">{{ $title }}</p>
                        </div>
                        @if($display_products == 0)
                        <div class="slider-arrows">
                            <button class="slider-arrows__arrow slider-arrows__prev game-cheats__slider-arrow"></button>
                            <button class="slider-arrows__arrow slider-arrows__next game-cheats__slider-arrow"></button>
                        </div>
                        @endif
                    </div>

                    @if (!$categories)
                        <div class="profile__empty-block" style="padding: 50px 0">
                            <div class="profile__empty-block__icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g opacity="0.5">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M13.125 5.42534C13.125 3.59754 11.5546 2.13355 9.69368 2.31408L9.69141 2.3143C8.17354 2.45597 6.875 3.99404 6.875 5.58367V6.392C6.875 6.73718 6.59518 7.017 6.25 7.017C5.90482 7.017 5.625 6.73718 5.625 6.392V5.58367C5.625 3.42367 7.34255 1.27877 9.57412 1.06981C12.1794 0.817724 14.375 2.87017 14.375 5.42534V6.57534C14.375 6.92051 14.0952 7.20034 13.75 7.20034C13.4048 7.20034 13.125 6.92051 13.125 6.57534V5.42534Z" fill="white"></path>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M2.81165 7.34899C3.54949 6.47178 4.8145 6.04297 6.66703 6.04297H13.3337C15.1862 6.04297 16.4512 6.47178 17.1891 7.34899C17.921 8.21913 17.9895 9.36273 17.8716 10.4284L17.8706 10.4372L17.2462 15.4319C17.1541 16.2871 16.9383 17.2238 16.175 17.9211C15.4158 18.6147 14.2394 18.9596 12.5004 18.9596H7.50036C5.76132 18.9596 4.58497 18.6147 3.82569 17.9211C3.0624 17.2237 2.84665 16.287 2.75452 15.4318L2.12909 10.4284C2.01117 9.36274 2.07975 8.21913 2.81165 7.34899ZM3.3711 10.2866L3.9969 15.293C4.08011 16.0696 4.25249 16.6179 4.66879 16.9982C5.09076 17.3837 5.88941 17.7096 7.50036 17.7096H12.5004C14.1113 17.7096 14.91 17.3837 15.3319 16.9982C15.7482 16.6179 15.9207 16.0697 16.0039 15.2931L16.0051 15.2821L16.6296 10.2865C16.7359 9.32108 16.625 8.62026 16.2325 8.15361C15.8453 7.69332 15.0395 7.29297 13.3337 7.29297H6.66703C4.96123 7.29297 4.15541 7.69332 3.76825 8.15361C3.37573 8.62028 3.26482 9.32112 3.3711 10.2866Z" fill="white"></path>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.0801 10.0013C12.0801 9.54106 12.4532 9.16797 12.9134 9.16797H12.9209C13.3811 9.16797 13.7542 9.54106 13.7542 10.0013C13.7542 10.4615 13.3811 10.8346 12.9209 10.8346H12.9134C12.4532 10.8346 12.0801 10.4615 12.0801 10.0013Z" fill="white"></path>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.24512 10.0013C6.24512 9.54106 6.61821 9.16797 7.07845 9.16797H7.08594C7.54617 9.16797 7.91927 9.54106 7.91927 10.0013C7.91927 10.4615 7.54617 10.8346 7.08594 10.8346H7.07845C6.61821 10.8346 6.24512 10.4615 6.24512 10.0013Z" fill="white"></path>
                                    </g>
                                </svg>
                            </div>
                            <p class="profile__empty-block__caption">{{ __('site.section_category_not_found') }}</p>
                        </div>
                    @else
                        @if($display_products == 0)
                        {{-- Close .content before carousel for full-width iOS scroll --}}
                </div>
                            <div class="swiper game-cheats-slider js-carousel" data-carousel="game-cheats">
                                <div class="swiper-wrapper">
                                    @foreach($categories as $card)
                                        @foreach($card['products'] as $p)
                                            <div class="swiper-slide cheat-card">
                                                <div class="cheat-card__logo" style="overflow: hidden;">
                                                    <img src="/i{{ $p['image_site'] }}" alt="">
                                                </div>
                                                <span class="cheat-status cheat-status_{{ $p['status_class'] }}">
                                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M25.5865 11.2411C25.5009 11.0228 25.3516 10.8354 25.1579 10.7033C24.9642 10.5712 24.7352 10.5005 24.5008 10.5004H16.6219L18.6396 1.42022C18.7797 0.791267 18.3834 0.167907 17.7544 0.027892C17.5551 -0.0165117 17.3475 -0.00777573 17.1525 0.0532257C16.9576 0.114227 16.7821 0.225355 16.6436 0.37549L2.64382 15.5419C2.2065 16.0151 2.23567 16.7531 2.70885 17.1905C2.92454 17.3898 3.20749 17.5004 3.50118 17.5003H10.0494L7.06099 26.4643C6.85717 27.0755 7.18749 27.7362 7.79876 27.94C7.99742 28.0062 8.21024 28.0178 8.41489 27.9734C8.61955 27.929 8.80848 27.8304 8.96186 27.6878L25.2949 12.5214C25.4667 12.3621 25.5864 12.1547 25.6384 11.9263C25.6904 11.6978 25.6723 11.459 25.5865 11.2411Z" fill="white"/>
                                                    </svg>
                                                    {{ $p['status_title'] }}
                                                </span>

                                                <p class="cheat-card__name">{{ $p['title'] }}</p>
                                                <ul class="cheat-card__list" style="margin-bottom:15px">
                                                    @foreach(json_decode($p['advantages'], true) as $a)
                                                        <li>{{ $a }}</li>
                                                    @endforeach
                                                </ul>
                                                @php
                                                    $hack_status = \App\Models\HackStatus::getByID($p['hack_status']);
                                                    $status = '';
                                                    if ($hack_status->title_pub != '') {
                                                        $status = $hack_status->title_pub;
                                                    }
                                                @endphp

                                                @if($status != '')
                                                    <div class="types">{{ $status }}</div>
                                                @endif
                                                <br>
                                                <div class="cheat-card__platform" style="margin: 0">
                                                    @if($card['title'] == 'iOS')
                                                        <svg width="22" height="28" viewBox="0 0 22 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M21.2384 9.39714C19.8547 7.94286 18.6451 6.54 16.1019 6.39714C14.0436 6.3 13.108 7.65429 11.1841 7.70286C9.39732 7.74857 9.53436 6.20286 5.82102 6.63714C2.60578 7.02286 -0.211502 10.5114 0.0125104 15.4029C0.239158 20.6857 3.63361 28 6.80668 28C9.04153 27.9543 9.57653 26.7914 11.4108 26.7914C13.6878 26.7914 14.2228 28.24 16.0149 27.9543C19.0984 27.4714 22 22.1886 22 20.4943C20.3028 19.5229 18.6055 17.8771 18.3815 15.1171C18.1997 12.3543 19.7651 10.6114 21.2384 9.39714Z" fill="white"></path>
                                                            <path d="M10.5595 6.78286C10.3355 4.84571 11.5452 0.631429 15.7883 0C16.2785 3.24571 13.8222 6.97429 10.5595 6.78286Z" fill="white"></path>
                                                        </svg>
                                                    @endif
                                                    @if($card['title'] == 'Android')
                                                        <svg width="24" height="28" viewBox="0 0 24 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M4.17744 9.38221H19.8255V20.594C19.8255 21.6198 18.9894 22.4484 17.9682 22.4484H16.6866V26.2685C16.6866 27.2285 15.9055 28 14.9335 28C13.9614 28 13.1745 27.2257 13.1745 26.2685V22.4455H10.8255V26.2657C10.8255 27.2228 10.0386 27.9971 9.06654 27.9971C8.11475 27.9971 7.32787 27.2228 7.32787 26.2657L7.3134 22.4455H6.05207C5.0135 22.4455 4.17744 21.6198 4.17744 20.5912V9.38221ZM1.75603 9.0622C0.786885 9.0622 0 9.83937 0 10.7794V18.0197C0 18.9797 0.786885 19.7511 1.75603 19.7511C2.72517 19.7511 3.4947 18.9768 3.4947 18.0197V10.7794C3.4947 9.83651 2.7136 9.0622 1.75603 9.0622ZM19.8746 8.77647H4.108C4.108 6.09924 5.72806 3.7763 8.13211 2.56483L6.91996 0.361896C6.85053 0.241892 6.88525 0.0904578 7.00386 0.0247412C7.12247 -0.0266891 7.27869 0.00759777 7.34523 0.127602L8.57184 2.35054C9.61331 1.89338 10.7734 1.64194 12 1.64194C13.2266 1.64194 14.3838 1.89338 15.4282 2.34768L16.6548 0.124745C16.7242 0.00474052 16.8775 -0.0266891 16.9961 0.021884C17.1176 0.0904578 17.1495 0.241892 17.08 0.359039L15.8679 2.56197C18.2546 3.77344 19.8746 6.09924 19.8746 8.77647ZM9.06654 5.30778C9.06654 4.95348 8.77724 4.65062 8.40405 4.65062C8.04243 4.65062 7.75603 4.95348 7.75603 5.30778C7.75603 5.65922 8.04532 5.96209 8.40405 5.96209C8.77724 5.96495 9.06654 5.66208 9.06654 5.30778ZM16.244 5.30778C16.244 4.95348 15.9547 4.65062 15.5959 4.65062C15.2199 4.65062 14.9335 4.95348 14.9335 5.30778C14.9335 5.65922 15.2228 5.96209 15.5959 5.96209C15.9547 5.96495 16.244 5.66208 16.244 5.30778ZM22.244 9.0622C21.2893 9.0622 20.5053 9.81937 20.5053 10.7794V18.0197C20.5053 18.9797 21.2864 19.7511 22.244 19.7511C23.2131 19.7511 24 18.9768 24 18.0197V10.7794C24 9.81937 23.216 9.0622 22.244 9.0622Z" fill="white"></path>
                                                        </svg>
                                                    @endif
                                                    @if(in_array($card['title'], ['Пк', 'Gameloop']))
                                                        <svg width="22" height="25" viewBox="0 0 448 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M0 93.7l183.6-25.3v177.4H0V93.7zm0 324.6l183.6 25.3V268.4H0v149.9zm203.8 28L448 480V268.4H203.8v177.9zm0-380.6v180.1H448V32L203.8 65.7z" fill="white"></path>
                                                        </svg>
                                                    @endif
                                                    @if($card['title'] == 'Эмуляторы')
                                                        <svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M23.9125 7.39438C24.0637 7.7325 24.1487 7.91625 24.2631 8.26C21.6319 7.88687 17.9456 9.26875 16.4412 11.1287C17.9981 8.64187 17.9431 5.605 15.8294 4.0525C13.4919 2.32875 9.60562 2.59625 7.01813 5.70125C4.825 8.3325 4.2375 12.75 6.57938 14.4731C8.86438 16.1531 12.4656 15.8719 15.9656 11.7188C15.9831 11.7012 15.9962 11.6838 16.0137 11.6663C13.3425 15.6619 9.1275 16.8638 5.7025 15.7894C3.22875 15.0131 1.87812 13.0831 1.31625 12.2806C0.908125 11.6969 0.5225 11.0175 0.2025 10.285C1.24125 4.43875 6.35125 0 12.5 0C17.5837 0 21.9606 3.035 23.9125 7.39438Z" fill="#E3E3E3"/>
                                                            <path d="M25 12.5C25 19.4037 19.4037 25 12.5 25C8.15375 25 4.32438 22.7806 2.0875 19.4163C2.43375 19.53 2.76312 19.64 3.07 19.7362C4.22375 20.105 5.02875 19.9475 6.10313 19.7856C7.08125 19.6369 7.86875 19.6137 8.33375 19.2975C8.15812 19.6662 7.88187 20.135 7.45687 20.6137C6.82562 21.315 6.15 21.715 5.7025 21.93C7.18937 22.3819 9.71562 22.9125 12.72 22.3688C15.5575 21.8556 17.4962 20.6231 18.8606 19.7375C20.1587 18.8956 21.8606 17.79 23.2469 15.79C24.2337 14.3644 24.7181 12.9187 24.9856 11.875C24.9975 12.0587 25 12.3163 25 12.5Z" fill="#A5A5A5"/>
                                                            <path d="M19.4956 16.3637C18.8688 16.7275 18.2106 16.7675 18.0262 16.4556C17.8462 16.1394 18.21 15.5919 18.8375 15.2275C19.4687 14.8638 20.1269 14.8238 20.3069 15.14C20.4913 15.4513 20.1269 16.0044 19.4956 16.3637Z" fill="white"/>
                                                            <path d="M16.4412 11.1287C16.81 10.4881 17.105 9.56125 17.105 8.77187C17.105 6.35062 15.14 4.38563 12.7188 4.38563C10.2975 4.38563 8.3325 6.35062 8.3325 8.77187C8.3325 11.1931 10.2975 13.1581 12.7188 13.1581C14.0038 13.1581 15.1619 12.6056 15.9644 11.7194C12.6581 16.5506 8.86312 16.1538 6.57812 14.4738C4.23625 12.75 4.82375 8.33313 7.01688 5.70188C9.60438 2.59625 13.4906 2.32875 15.8281 4.05312C17.9431 5.60562 18.0306 8.57562 16.4412 11.1287Z" fill="#8C8C8C"/>
                                                            <path d="M24.16 7.98625C24.0062 7.59 23.6688 7.28125 23.2488 7.22C23.2463 7.21938 23.2438 7.21938 23.2413 7.21875C20.61 6.84563 18.3075 8.73625 16.8025 10.5956C16.6 10.8894 16.2219 11.4313 16.0131 11.6656C13.3419 15.6613 9.12688 16.8631 5.70188 15.7887C3.22813 15.0125 1.8775 13.0825 1.31563 12.28C0.9075 11.6963 0.521875 11.0169 0.201875 10.2844C0.06625 11.0044 0 11.7456 0 12.5C0 15.0569 0.7675 17.4338 2.0875 19.4163C2.43375 19.53 2.76187 19.6425 3.07 19.7362C4.94125 20.3075 7.00375 20.0331 8.33375 19.2975C8.15812 19.6662 7.88187 20.135 7.45687 20.6137C6.82562 21.315 6.15 21.715 5.7025 21.93C7.18937 22.3819 9.71562 22.9125 12.72 22.3688C15.5575 21.8556 17.4962 20.6231 18.8606 19.7375C20.1587 18.8956 21.8606 17.79 23.2469 15.79C24.2337 14.3644 24.8481 12.9862 24.9831 11.9387C24.9919 11.8737 24.975 11.7613 24.97 11.6775C24.89 10.3838 24.6094 9.14375 24.16 7.98625ZM19.4956 16.3637C18.8688 16.7275 18.2106 16.7675 18.0262 16.4556C17.8462 16.1394 18.21 15.5919 18.8375 15.2275C19.4687 14.8638 20.1269 14.8238 20.3069 15.14C20.4913 15.4513 20.1269 16.0044 19.4956 16.3637Z" fill="white"/>
                                                        </svg>
                                                    @endif
                                                    <span>{{ $card['title'] }}</span>
                                                </div>
                                                <a href="/{{ $alias }}/{{ $p['alias'] }}" class="cheat-card__btn">{{ __('site.btn_more') }}</a>
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                                <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>
                            </div>
                        @endif
                    @endif


            @if($display_products == 1)
                @foreach($categories as $card)
             <div class="content game-cheats__slider-container">
                <div class="section-caption-container">
                    <div class="section-caption-container__inner">
                        <h2>
                            @if($card['title'] == 'iOS')
                            <img style="width: 18px;height: 22px;margin-right: 5px;vertical-align: middle" src="/assets/img/icon_apple.svg" alt="">
                            @elseif($card['title'] == 'Android')
                                <img style="width: 18px;height: 22px;margin-right: 5px;vertical-align: middle" src="/assets/img/icon_android.svg" alt="">
                            @elseif(in_array($card['title'], ['Пк', 'Gameloop']))
                                <img style="width: 20px;height: 22px;margin-right: 5px;vertical-align: middle" src="/assets/img/icon_windows.svg" alt="">
                            @elseif($card['title'] == 'Эмуляторы')
                                <img style="width: 22px;height: 22px;margin-right: 5px;vertical-align: middle" src="/assets/img/icon_idk.svg" alt="">
                            @endif
                            {{ $card['title'] }}
                        </h2>
                    </div>
                    <div class="slider-arrows">
                        <button class="slider-arrows__arrow slider-arrows__prev game-cheats__slider-arrow"></button>
                        <button class="slider-arrows__arrow slider-arrows__next game-cheats__slider-arrow"></button>
                    </div>
                </div>
             </div>
             {{-- Carousel moved outside .content for iOS full-width scroll --}}
                <div class="swiper game-cheats-slider js-carousel" data-carousel="game-cheats">
                    <div class="swiper-wrapper">
                        @foreach($card['products'] as $p)
                            <div class="swiper-slide cheat-card">
                                <div class="cheat-card__logo" style="overflow: hidden;">
                                    <img src="/i{{ $p['image_site'] }}" alt="">
                                </div>
                                <span class="cheat-status cheat-status_{{ $p['status_class'] }}">
                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M25.5865 11.2411C25.5009 11.0228 25.3516 10.8354 25.1579 10.7033C24.9642 10.5712 24.7352 10.5005 24.5008 10.5004H16.6219L18.6396 1.42022C18.7797 0.791267 18.3834 0.167907 17.7544 0.027892C17.5551 -0.0165117 17.3475 -0.00777573 17.1525 0.0532257C16.9576 0.114227 16.7821 0.225355 16.6436 0.37549L2.64382 15.5419C2.2065 16.0151 2.23567 16.7531 2.70885 17.1905C2.92454 17.3898 3.20749 17.5004 3.50118 17.5003H10.0494L7.06099 26.4643C6.85717 27.0755 7.18749 27.7362 7.79876 27.94C7.99742 28.0062 8.21024 28.0178 8.41489 27.9734C8.61955 27.929 8.80848 27.8304 8.96186 27.6878L25.2949 12.5214C25.4667 12.3621 25.5864 12.1547 25.6384 11.9263C25.6904 11.6978 25.6723 11.459 25.5865 11.2411Z" fill="white"/>
                                    </svg>
							        {{ $p['status_title'] }}
						        </span>

                                <p class="cheat-card__name">{{ $p['title'] }}</p>
                                <ul class="cheat-card__list" style="margin-bottom:15px">
                                    @foreach(json_decode($p['advantages'], true) as $a)
                                        <li>{{ $a }}</li>
                                    @endforeach
                                </ul>
                                @php
                                    $hack_status = \App\Models\HackStatus::getByID($p['hack_status']);
                                    $status = '';
                                    if ($hack_status->title_pub != '') {
                                        $status = $hack_status->title_pub;
                                    }
                                @endphp
                                @if($status != '')
                                    <div class="types">{{ $status }}</div>
                                @endif
                                <a href="/{{ $alias }}/{{ $p['alias'] }}" class="cheat-card__btn">{{ __('site.btn_more') }}</a>
                            </div>
                        @endforeach
                    </div>
                    <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span></div>
            @endforeach
            @endif
            <div class="content game-cards__slider-container">
                <div class="section-caption-container">
                    <div class="section-caption-container__inner">
                        <p class="section-caption">{{ __('site.section_recommendation_title')  }}</p>
                    </div>
                    <div class="slider-arrows">
                        <button class="slider-arrows__arrow slider-arrows__prev game-cheats__slider-arrow"></button>
                        <button class="slider-arrows__arrow slider-arrows__next game-cheats__slider-arrow"></button>
                    </div>
                </div>
            </div>
            {{-- Carousel moved outside .content for iOS full-width scroll --}}
            <div class="swiper game-cards-slider js-carousel" data-carousel="game-cards">
                <div class="swiper-wrapper">
                    @foreach($recommendations as $rec)
                        <div class="swiper-slide">
                        <div class="card">
                            <a href="/{{ $rec->alias }}" class="card__link"></a>
                            <div class="card__img">
                                <img src="{{ $rec->image_site }}" alt="">
                            </div>
                            <div class="types">@plural($rec->count_products, __('site.text_x_cheats_one'), __('site.text_x_cheats_few'), __('site.text_x_cheats_many'))</div>
                            <div class="card__name-container">
                                <div class="card__name">{{ $rec->title }}</div>
                                <button class="card__btn"></button>
                            </div>
                        </div>
                        </div>
                    @endforeach
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