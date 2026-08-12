const   customSelectReset   = new CustomEvent('customSelectReset'),
        popupClose          = new Event('popupClose'),
        popupOpen           = new Event('popupOpen');

var scrollbarWidth;

$(document).ready(function(){
	// Якори
    $('[data-scroll]').click(function(e){
        var scroll_el = $(this).attr('data-scroll');
        if ($(scroll_el).length != 0) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: $(scroll_el).offset().top - 100 }, 500);
        }
    });
	// End Якори

    // Кастомный селект
    $(".select").each(function(){
        let select = $(this).find("select"),
            inner,
            self = $(this),
            search = $(this).find(".select__search").length != 0;

        $(this).click(function(){
            $(this).toggleClass("_active");
        })

        if ($(this).hasClass("_unfold")){
            $(this).append(`
            <div class="select__selected"></div>
            `);
            if ($(this).find(".select__inner").length == 0){
                $(this).append(`<div class="select__inner"></div>`)
            }
            inner = $(this).find(".select__inner");
            select.find("option:not([disabled])").each(function(){
                inner.append(`
                <div class="select__option" data-value="${$(this).attr("value")}">${$(this).text()}</div>
                `)
            })
        }
        inner = $(this).find(".select__inner");
        if ($(this).hasClass("_search")){
            inner.prepend(`
                <div class="select__search">
                    <input name="${select.attr("name")}_input" type="text">
                </div>
            `)
        }

        inner.find(".select__option").click(function(){
            self.find(".select__selected").html($(this).html());
            $(this).addClass("_active").siblings("._active").removeClass("_active");
            select.find(`option[value="${$(this).attr("data-value")}"]`).prop("selected", true).change();
            if (search){
                $(this).siblings("._hidden").removeClass("_hidden");
                $(this).closest(".select__inner").find(".select__search input").val("");
            }
        })

        if (exists($(this).find("option[disabled]"))){
            $(this).find(".select__selected").html($(this).find("option[disabled]").text());
        } else{
            let selectedOption = $(this).find(`.select__option[data-value="${$(this).find("option[selected]").val()}"]`);
            if (exists(selectedOption)){
                $(this).find(".select__selected").html(selectedOption.html());
                $(this).find(`.select__option[data-value="${$(this).find("option[selected]").val()}"]`).addClass("_active");
            } else{
                selectedOption = $(this).find(".select__option").eq(0);
                $(this).find(".select__selected").html(selectedOption.html());
                selectedOption.addClass("_active");
            }
        }
    })
    $(document).on("customSelectReset", ".select", function(){
        let initial,
            select = $(this).find("select");
        if (exists($(this).find("option[disabled]"))){
            initial = $(this).find("option[disabled]");
        } else{
            initial = $(this).find("option").eq(0);
        }

        self.find(".select__selected").text(initial.text());
        self.find(".select__option._active").removeClass("_active");

        select.val(initial.attr('value'));
    });
    $(document).on("input", ".select__search input", function(){
        let search = this.value.toLowerCase();
        $(this).closest(".select__inner").find(".select__option").each(function(){
            $(this).toggleClass("_hidden", $(this).text().toLowerCase().indexOf(search) == -1);
        })
    });
    $(document).click(function(e){
        $(".select._active").not(e.target.closest(".select")).each(function(){
            $(this).removeClass("_active").find(".select__option._hidden").removeClass("_hidden");
            $(this).find(".select__search input").val("");
        })
    })
    // End Кастомный селект

    // Аккордионы
    $(document).on("click", ".accordion__header", function(){
        let accParent = $(this).closest(".accordion");

        console.log(accParent);
        
        accParent.toggleClass("_active")
        .find(".accordion__body").slideToggle(300);

        accParent
        .siblings(".accordion._active").removeClass("_active")
        .find(".accordion__body").slideUp(300);
    })
    // End Аккордионы

    // Попап
    $(".popup").each(function () {
        $(this).wrapInner(`<div class="popup__wrapper"><div class="popup__inner"></div></div>`)
        $(this).find(".popup__inner").append(`
        <button class="popup__close" type="button">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5.46484 12.5375L12.5398 5.46252" stroke="white" stroke-width="1.875" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12.5398 12.5375L5.46484 5.46252" stroke="white" stroke-width="1.875" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        `)
        $(this).find(".popup__input-block").click(function(){
            $(this).removeClass("input-block_error");
        })
    })

    $(document).on("click", ".popup", function(e){
        closePopup();
    })

    $(document).on("click", ".popup__inner", function(e){
        e.stopPropagation();
    })

    $(document).on("click", ".popup__close, .close", function(e){
        e.preventDefault();
        closePopup()
    })
    $(document).on("click", "[data-popup]", function (e) {
        e.preventDefault();
        openPopup($(this).attr("data-popup"));
    })
    // End Попап

    // Showhide
    $(document).on("click", ".input-block__showHide-btn", function(){
        let input = $(this).siblings("input");
        $(this).toggleClass("_active");
        input.attr("type", input.attr("type") == 'text' ? 'password' : 'text');
    })
    // End Showhide

    $("#register-referral-toggle").change(function(){
        $("#register-referral-input-block").toggle(this.checked);
    })

    var isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    if (isIOS) document.documentElement.classList.add('ios');

    function withIosCssMode(config) {
        if (!isIOS) return config;
        return Object.assign({}, config, {
            cssMode: true,
            watchOverflow: true,
            freeMode: false
        });
    }

    ;(()=>{
        let section = $(".reviews");
        new Swiper(".reviews-slider", withIosCssMode({
            slidesPerView: "auto",
            spaceBetween: 25,
            touchEventsTarget: 'container',
            touchReleaseOnEdges: false,
            touchMoveStopPropagation: false,
            threshold: 2,
            touchAngle: 45,
            touchStartPreventDefault: false,
            freeMode: {
                enabled: true,
                sticky: true,
                momentumRatio: 0.85,
                momentumBounceRatio: 0.18,
                momentumVelocityRatio: 0.9,
                minimumVelocity: 0.02
            },
            mousewheel: {
                forceToAxis: true
            },
            navigation: {
                prevEl: section.find(".slider-arrows__prev")[0],
                nextEl: section.find(".slider-arrows__next")[0]
            }
        }));
    })();

    $(".game-cheats__slider-container").each(function(){
        new Swiper($(this).find(".game-cheats-slider")[0], withIosCssMode({
            slidesPerView: 5,
            spaceBetween: 17,
            touchEventsTarget: 'container',
            passiveListeners: false,
            touchReleaseOnEdges: false,
            touchMoveStopPropagation: false,
            threshold: 2,
            touchAngle: 45,
            touchStartPreventDefault: false,
            freeMode: {
                enabled: true,
                sticky: true,
                momentumRatio: 0.85,
                momentumBounceRatio: 0.18,
                momentumVelocityRatio: 0.9,
                minimumVelocity: 0.02
            },
            mousewheel: {
                forceToAxis: true
            },
            navigation: {
                prevEl: $(this).find(".slider-arrows__prev")[0],
                nextEl: $(this).find(".slider-arrows__next")[0]
            },
            breakpoints: {
                0: {
                    slidesPerView: "auto",
                    spaceBetween: 13
                },
                768: {
                    slidesPerView: 3
                },
                1000: {
                    slidesPerView: 4
                },
                1601: {
                  slidesPerView: exists(".cheat-page") ? 4 : 5
                }
            }
        }));
    })

    function setMobileMenu(open){
        $(".header__menu, .header__hamburger").toggleClass("_active", open);
        $("html, body").toggleClass("menu-open", open);
        $(".header__hamburger").attr("aria-expanded", open ? "true" : "false");
    }

    $(".header__hamburger").attr("aria-expanded", "false").click(function(){
        setMobileMenu(!$(".header__menu").hasClass("_active"));
    })

    $(document).on("click", '.header__menu._active a', function(){
        setMobileMenu(false);
    })

    $(document).on("keydown", function(event){
        if (event.key === "Escape" && $(".header__menu").hasClass("_active")) {
            setMobileMenu(false);
            $(".header__hamburger").trigger("focus");
        }
    })

    $(window).on("resize", function(){
        if (window.innerWidth > 1400 && $(".header__menu").hasClass("_active")) {
            setMobileMenu(false);
        }
    })

    ;(()=>{
        let section = $('.cheat-functions');
        new Swiper('.cheat-functions .swiper', withIosCssMode({
            slidesPerView: "auto",
            spaceBetween: 18,
            touchEventsTarget: 'container',
            passiveListeners: false,
            touchReleaseOnEdges: false,
            touchMoveStopPropagation: false,
            threshold: 2,
            touchAngle: 45,
            touchStartPreventDefault: false,
            freeMode: {
                enabled: true,
                sticky: true,
                momentumRatio: 0.85,
                momentumBounceRatio: 0.18,
                momentumVelocityRatio: 0.9,
                minimumVelocity: 0.02
            },
            mousewheel: {
                forceToAxis: true
            },
            navigation: {
                prevEl: section.find(".slider-arrows__prev")[0],
                nextEl: section.find(".slider-arrows__next")[0]
            }
        }))
    })();

    ;(()=>{
        let section = $(".game-cards__slider-container");
        new Swiper(".game-cards-slider", withIosCssMode({
            spaceBetween: 20,
            touchEventsTarget: 'container',
            passiveListeners: false,
            touchReleaseOnEdges: false,
            touchMoveStopPropagation: false,
            threshold: 2,
            touchAngle: 45,
            touchStartPreventDefault: false,
            freeMode: {
                enabled: true,
                sticky: true,
                momentumRatio: 0.85,
                momentumBounceRatio: 0.18,
                momentumVelocityRatio: 0.9,
                minimumVelocity: 0.02
            },
            mousewheel: {
                forceToAxis: true
            },
            navigation: {
                prevEl: section.find(".slider-arrows__prev")[0],
                nextEl: section.find(".slider-arrows__next")[0]
            },
            breakpoints: {
                0: {
                    slidesPerView: 2
                },
                768: {
                    slidesPerView: 3
                },
                1439: {
                    slidesPerView: 4
                }
            }
        }));
    })();

    if (window.innerWidth < 768){
        let section = $(".section2");
        new Swiper(".section2 .swiper", withIosCssMode({
            slidesPerView: "auto",
            spaceBetween: 12,
            touchEventsTarget: 'container',
            passiveListeners: false,
            touchReleaseOnEdges: false,
            touchMoveStopPropagation: false,
            threshold: 2,
            touchAngle: 45,
            touchStartPreventDefault: false,
            freeMode: {
                enabled: true,
                sticky: true,
                momentumRatio: 0.85,
                momentumBounceRatio: 0.18,
                momentumVelocityRatio: 0.9,
                minimumVelocity: 0.02
            },
            mousewheel: {
                forceToAxis: true
            },
            navigation: {
                prevEl: section.find(".slider-arrows__prev")[0],
                nextEl: section.find(".slider-arrows__next")[0]
            }
        }));
    }

    new Swiper('.cheat-block__slider .swiper', withIosCssMode({
        slidesPerView: 1,
        touchEventsTarget: 'container',
        passiveListeners: false,
        touchReleaseOnEdges: false,
        touchMoveStopPropagation: false,
        threshold: 2,
            touchAngle: 45,
        touchStartPreventDefault: false,
        freeMode: {
            enabled: true,
            sticky: true,
            momentumRatio: 0.85,
            momentumBounceRatio: 0.18,
            momentumVelocityRatio: 0.9,
            minimumVelocity: 0.02
        },
        mousewheel: {
            forceToAxis: true
        },
        navigation: {
            prevEl: ".cheat-block__slider__prev",
            nextEl: ".cheat-block__slider__next"
        }
    }))

    $(".header__search__input").on("input", function(){
        let query = this.value.toLowerCase().trim();
        $(".header__search").toggleClass("_active", query.length != 0);
    })

    $(".header__search-btn, .header__search-reset-btn").click(function(e){
        e.preventDefault();
        $(".header__search").toggleClass("_visible");
    })

    $(".header__search__overlay").click(function(){
        $(".header__search").removeClass("_active");
        $(".header__search__input").val('');
    })

    $(document).on("click", ".input-block__copy-btn", function(){
        copyText($(this).siblings("input").val());
    })

    $(".instruction__video").click(function(){
        showNotification('Попався', 'success');
    })

    $(window).resize(function(){
        $(".popup__wrapper").css("max-height", window.innerHeight)
    })

    if (false && exists('.about-section__history__slider')){
        new Swiper('.about-section__history__slider', withIosCssMode({
            slidesPerView: "auto",
            touchEventsTarget: 'container',
            passiveListeners: false,
            touchReleaseOnEdges: false,
            touchMoveStopPropagation: false,
            threshold: 2,
            touchAngle: 45,
            touchStartPreventDefault: false,
            freeMode: {
                enabled: true,
                sticky: true,
                momentumRatio: 0.85,
                momentumBounceRatio: 0.18,
                momentumVelocityRatio: 0.9,
                minimumVelocity: 0.02
            },
            mousewheel: {
                forceToAxis: true
            },
            navigation: {
                prevEl: ".about-section__history__prev-btn",
                nextEl: ".about-section__history__next-btn"
            }
        }))
    }

    $(document).on("click", ".profile__tabs__choose__btn", function(){
        $(this).addClass("_active")
        .siblings(".profile__tabs__choose__btn._active").removeClass("_active")
        .closest(".profile__tabs").find(`.profile__tabs__tab[data-tab="${$(this).data("tab")}"]`).addClass("_active")
        .siblings(".profile__tabs__tab._active").removeClass("_active");
    })

    $(document).on("click", "[data-profile-switch-tab]", function(e){
        e.preventDefault();
        $("[data-profile-tab]._active").removeClass("_active");
        $(`[data-profile-tab="${$(this).data('profile-switch-tab')}"]`).addClass("_active");
    })

    $(document).on("click", "[data-popup-switch-step]", function(e){
        e.preventDefault();
        let popup = $(this).closest(".popup");
        popup.find("[data-popup-step]._active").removeClass("_active");
        $(`[data-popup-step="${$(this).data('popup-switch-step')}"]`).addClass("_active");
    })

    $(document).on("click", ".profile__referral__info-block .value", function(){
        copyText($(this).find("span").text());
    })

    $(document).on("click", ".profile__settings-block_input .profile__settings-block__edit-btn", function(){
        let input = $(this).siblings("input");
        $(this).closest(".profile__settings-block").toggleClass("profile__settings-block_edit");
        if (input.attr("readonly")){
            input.removeAttr("readonly")
        } else{
            input.attr("readonly", true);
        }
    })

    $(document).on("click", ".profile__settings-block_input .profile__settings-block__cancel-btn", function(){
        $(this).closest(".profile__settings-block").removeClass("profile__settings-block_edit").find("input").attr("readonly", true);
    })

    $("#cheat-video").on("popupOpen", function(){
        var container = $("#video-player-container");
        if (container.length === 0) return;
        var ytId = container.data("youtube-id");
        var videoUrl = container.data("video-url");
        container.empty();
        if (ytId) {
            container.append('<iframe src="https://www.youtube.com/embed/' + ytId + '?autoplay=1&rel=0" allow="autoplay; encrypted-media" allowfullscreen></iframe>');
        } else if (videoUrl && /\.(mp4|webm|mov|ogg)(\?|$)/i.test(videoUrl)) {
            container.append('<video src="' + videoUrl + '" controls autoplay></video>');
        } else if (videoUrl) {
            container.append('<iframe src="' + videoUrl + '" allowfullscreen></iframe>');
        }
    })

    $("#cheat-video").on("popupClose", function(){
        $("#video-player-container").empty();
    })
})

// Существует ли элемент
const exists = (elem) => $(elem).length != 0;
// End Существует ли элемент

// Уведомления
function showNotification(text,type){
    let offsetBottom = 20;
    if ($(window).width() < 992){
        offsetBottom += 74;
    }
    if ($(".notification-container").length == 0) $("body").append(`<div class="notification-container"></div>`)
    let id = new Date().getTime();
    $(".notification-container").append(`
        <div class="notification notification_${type}" style="bottom: ${offsetBottom}px" id="notification${id}">
            <span class="icon"></span>
            ${text}
        </div>`
    );
    let newNotif = $(`#notification${id}`);
    let newHeight = newNotif.outerHeight(true) + 10;
    $(".notification").not(newNotif).each(function(){
        let currentBottom = parseInt($(this).css("bottom"), 10) || offsetBottom;
        $(this).css("bottom", (currentBottom + newHeight) + "px");
        if(currentBottom + newHeight > window.innerHeight / 2){
            $(this).fadeOut(300);
        }
    });
    newNotif.click(function(){
        $(this).fadeOut(300);
    })
    setTimeout(()=>{
        newNotif.fadeOut(300);
        setTimeout(()=>{
            newNotif.remove();
        },300)
    },3000)
}
// End Уведомления

// Заморозить скролл
function freeze() {
    if ($("html").css("position") != "fixed") {
        var top = $("html").scrollTop() ? $("html").scrollTop() : $("body").scrollTop();
        if (window.innerWidth > $("html").width()) {
            $("html").css("overflow-y", "hidden");
        }
        /* removed: caused header double-compensation shift */
        $("html").css({ "width": "100%", "height": "100%", "position": "fixed", "top": -top });
    }
}
// End Заморозить скролл

// Разморозить скролл
function unfreeze() {
    if ($("html").css("position") == "fixed") {
        $("html").css("position", "static");
        $("html, body").scrollTop(-parseInt($("html").css("top")));
        /* removed: header no longer modified by freeze */
        $("html").removeAttr("style");
    }
}
// End Разморозить скролл

// Получить скролл
document.getScroll = function() {
    if (window.pageYOffset != undefined) {
        return [pageXOffset, pageYOffset];
    } else {
        var sx, sy, d = document,
            r = d.documentElement,
            b = d.body;
        sx = r.scrollLeft || b.scrollLeft || 0;
        sy = r.scrollTop || b.scrollTop || 0;
        return [sx, sy];
    }
}
// End Получить скролл

// Скопировать текст
function copyText(elem){
    var text = elem;
    try {
        if ($(elem).length != 0){ text = $(elem).val() ? $(elem).val() : $(elem).text(); }
    } catch{

    }
    $("body").append(`<input id="copyTextInput" value="${text}">`);
    $("#copyTextInput").focus().select();
    document.execCommand("copy");
    $("#copyTextInput").remove();
    showNotification("Copied!", 'success', 'copy');
}
// End Скопировать текст

// Открыть попап
function openPopup(elem) {
    if (exists('.popup._active')) closePopup();
    let el = $(`.popup[id="${elem}"]`);
    if (exists(el)){
        el.addClass("_active");
        el[0].dispatchEvent(popupOpen);
    }
    freeze()
}
// End Открыть попап

// Закрыть попап
function closePopup(elem) {
    let el;
    if (elem != undefined) {
        el = $(elem)
    } else {
        el = $(".popup._active")
    }
    if (el[0] != undefined){
        el.removeClass("_active");
        el[0].dispatchEvent(popupClose);
        setTimeout(function () {
            unfreeze();
        }, 300)
    }
}
// End Закрыть попап

var cardTimeline, headerTimeline, section1Timeline, aboutHeadingTimeline, statusHeadingTimeline,
    initAnimations = () => {};

$(window).on("load", function() {
    $("body").addClass("_loaded")
    if (mainHasClass("main")){
        headerTimeline.resume();
        cardTimeline.resume();
        section1Timeline.resume();
    }
    if (mainHasClass("about-page") && aboutHeadingTimeline){
        aboutHeadingTimeline.resume();
    }
    if (mainHasClass("status-page") && statusHeadingTimeline){
        statusHeadingTimeline.resume();
    }
    setTimeout(function(){
        scrollbarWidth = (window.innerWidth - $("body").width());
        initAnimations();
    },0)
})

const   ww = window.innerWidth,
        mainClassList = document.querySelector("main").classList,
        mainHasClass = (classname) => mainClassList.contains(classname),
        $ease = Power1.easeOut;
 
if (ww < 768){
    $(window).resize(function(){
        // $("#ticket-tab").css("max-height", window.innerHeight) // disabled - ticket system removed
    })
}

if (mainHasClass("main")){
    cardTimeline = gsap.timeline()
    .from("#card .container", {y: -100, opacity: 0, duration: .5, ease: $ease})
    .from("#card .eclipse", {scale: 0, opacity: 0, duration: .5, ease: $ease}, '-=.5')
    .from("#card .container__load-text", {width: 0, opacity: 0, duration: .5, ease: $ease, stagger: .1}, '-=.4')
    .from("#card .container__btn", {yPercent: 50, opacity: 0, duration: .5, ease: $ease}, '-=.4')
    .from("#card .telegram", {xPercent: 50, opacity: 0, duration: .5, ease: $ease}, '<')
    .from("#card .skill", {xPercent: -100, opacity: 0, duration: .5, ease: $ease}, '-=.5')
    .from("#card .stats", {xPercent: -80, opacity: 0, duration: .5, ease: $ease}, '-=.2')
    .from("#card .graph", {height: 0, opacity: 0, duration: .5, ease: $ease}, '-=.2')
    .from("#card .platforms", {xPercent: 50, opacity: 0, duration: .5, ease: $ease}, '<-=.2'),

    headerTimeline = gsap.timeline()
    .from(".header__logo", {x: ww >= 768 ? -50 : -20, opacity: 0, duration: .5, ease: $ease});
    if (ww >= 1440){
        headerTimeline
        .from(".header__menu__item, .header__lang, .header__search", {y: -50, opacity: 0, duration: .5, ease: $ease, stagger: .1, clearProps: "transform,opacity"}, '-=.5')
        .from(".header__login", {x: 50, opacity: 0, duration: .5, ease: $ease}, '-=.25')
    } else if (ww >= 768 && ww < 1440){
        headerTimeline
        .from(".header__lang, .header__search, .header__login", {y: -50, opacity: 0, duration: .3, ease: $ease, stagger: .1, clearProps: "transform,opacity"}, '-=.5')
        .from(".header__hamburger", {x: 50, opacity: 0, duration: .3, ease: $ease}, '-=.25')
    } else{
        headerTimeline
        .from(".header__lang, .header__search-btn, .header__hamburger", {y: -20, opacity: 0, duration: .3, ease: $ease, stagger: .1}, '-=.5')
        .from(".header__login", {x: 20, opacity: 0, duration: .3, ease: $ease}, '-=.25')
    }

    section1Timeline = gsap.timeline()
    .from(".main-section1__section-category, .main-section1__section-caption, .main-section1__section-subcaption", {x: -50, opacity: 0, duration: .5, ease: $ease, stagger: .1})
    .from(".main-section1__btns, .main-section1__support", {y: 50, opacity: 0, duration: .5, ease: $ease, stagger: .2}, '-=.5')

    cardTimeline.pause();
    headerTimeline.pause();
    section1Timeline.pause();
}

if (mainHasClass("about-page")){
    aboutHeadingTimeline = gsap.timeline()
    .from(".about-heading-section .section-category, .about-heading-section__section-caption, .about-heading-section__section-subcaption", {x: -50, opacity: 0, duration: .5, ease: $ease, stagger: .1})
    .from(".about-heading-section__btn", {y: 50, opacity: 0, duration: .5, ease: $ease}, '-=.5');
    aboutHeadingTimeline.pause();
}

if (mainHasClass("status-page")){
    statusHeadingTimeline = gsap.timeline()
    .from(".status-heading-section__section-caption, .status-heading-section__section-subcaption", {x: -50, opacity: 0, duration: .5, ease: $ease, stagger: .1})
    .from(".status-heading-section__attention, .status-heading-section__filter", {y: 50, opacity: 0, duration: .5, ease: $ease, stagger: .2}, '-=.5');
    statusHeadingTimeline.pause();
}

initAnimations = () => {

    if (mainHasClass("main")){
        // Секция 2
        gsap.timeline({
            scrollTrigger: {
                trigger: ".section2",
                start: "top center",
                once: true,
                toggleActions: "play complete complete complete"
            }
        })
        .from(".section2 .section-category, .section2 .section-caption", {y: 50, opacity: 0, duration: .5, ease: $ease, stagger: .1})
        .from(".section2__block", {y: -50, opacity: 0, duration: .5, ease: $ease, stagger: .1}, '-=.5')

        // Каталог
        gsap.timeline({
            scrollTrigger: {
                trigger: ".catalog",
                start: "top center",
                once: true,
                toggleActions: "play complete complete complete"
            }
        })
        .from(".catalog .section-category, .catalog .section-caption, .catalog .section-subcaption", {x: -50, opacity: 0, duration: .5, ease: $ease, stagger: .1})
        .from(".catalog__platform", {x: 50, opacity: 0, duration: .5, ease: $ease}, '-=.5')
        .from(".catalog .card", {yPercent: 10, opacity: 0, duration: .5, ease: $ease, stagger: .1}, '-=.5')
    
        // Отзывы
        var reviews = gsap.timeline({
            scrollTrigger: {
                trigger: ".reviews",
                start: "top center",
                once: true,
                toggleActions: "play complete complete complete"
            }
        })
        .from(".reviews .section-category, .reviews .section-caption, .reviews .section-subcaption", {x: -50, opacity: 0, duration: .5, ease: $ease, stagger: .1})
        .from(".reviews .slider-arrows", {x: 50, opacity: 0, duration: .5, ease: $ease}, '-=.5');
        getVisibleElems(".reviews__slide", 'x').then(elems => {
            reviews.from(elems, {yPercent: 10, opacity: 0, duration: .5, ease: $ease, stagger: .1}, '-=.5')
        })

         // FAQ
        gsap.timeline({
            scrollTrigger: {
                trigger: ".faq",
                start: "top bottom",
                once: true,
                toggleActions: "play complete complete complete"
            }
        })
        .from(".faq .section-category, .faq .section-caption", {x: -50, opacity: 0, duration: .5, ease: $ease, stagger: .1});
        
        $(".faq__container .accordion").each(function(index, el){
            gsap.from(el, {
                scrollTrigger: el, // start the animation when the element enters the viewport (once)
                x: index % 2 == 0 ? 50 : -50,
                duration: .5,
                ease: $ease
            });
        })
    } else if (mainHasClass("game")){
        $(".game-cheats__slider-container").each(function(index, el){
            var tl = gsap.timeline({
                scrollTrigger: {
                    trigger: el,
                    start: "top center",
                    once: true,
                    toggleActions: "play complete complete complete"
                }
            })
            getVisibleElems(el.querySelectorAll(".cheat-card"), 'x').then(elems => {
                tl.from(elems, {yPercent: 10, opacity: 0, duration: .5, ease: $ease, stagger: .1})
            })
        })
    } else if (mainHasClass("about-page")){
        gsap.timeline({
            scrollTrigger: {
                trigger: ".about-section__stats",
                start: "top center",
                once: true,
                toggleActions: "play complete complete complete"
            }
        })
        .from(".about-section__stat", {opacity: 0, duration: .5, ease: $ease, stagger: .1})

        let elems = [...$(".about-section__contact")];
        if (ww >= 1440) elems = elems.reverse();

        gsap.timeline({
            scrollTrigger: {
                trigger: ".about-section__contacts",
                start: "top center",
                once: true,
                toggleActions: "play complete complete complete"
            }
        })
        .from(elems, {opacity: 0, duration: .5, ease: $ease, stagger: .1})

        var tl = gsap.timeline({
            scrollTrigger: {
                trigger: ".about-section__history",
                start: "top center",
                once: true,
                toggleActions: "play complete complete complete"
            }
        })
        .from(".about-section__history__caption", {yPercent: 10, opacity: 0, duration: .5, ease: $ease})
        getVisibleElems(".about-section__history__slide", 'x').then(elems => {
            tl.from(elems, {yPercent: 10, opacity: 0, duration: .5, ease: $ease, stagger: .1}, '-=.5')
        })
    } else if (mainHasClass("status-page")){
        gsap.timeline({
            scrollTrigger: {
                trigger: ".cheat-statuses",
                start: "top center",
                once: true,
                toggleActions: "play complete complete complete"
            }
        })
        .from(".game-status-block", {yPercent: 10, opacity: 0, duration: .5, ease: $ease, stagger: .1})
    } else if (mainHasClass("cheat-page")){
        gsap.timeline()
        .from(".cheat-block__slider", {xPercent: -10, opacity: 0, duration: .5, ease: $ease})
        .from(".cheat-block__info", {xPercent: 10, opacity: 0, duration: .5, ease: $ease}, '-=.5')
        .from(".cheat-block__requirements", {yPercent: 10, opacity: 0, duration: .5, ease: $ease}, '-=.5')

        var tl = gsap.timeline({
            scrollTrigger: {
                trigger: ".cheat-functions",
                start: "top center",
                once: true,
                toggleActions: "play complete complete complete"
            }
        })
        .from(".cheat-functions__caption", {y: 20, opacity: 0, duration: .5, ease: $ease})
        getVisibleElems(".cheat-functions__block", 'x').then(elems => {
            tl.from(elems, {yPercent: 10, opacity: 0, duration: .5, ease: $ease, stagger: .1}, '-=.5')
        })
    }
}

function checkVisibility(elements, axis = "all") {
    return new Promise((resolve) => {
        const observer = new IntersectionObserver((entries) => {
            const visibilityArray = entries.map((entry) => {
                if (axis == "x") {
                    return  entry.boundingClientRect.x > 0 &&
                            entry.boundingClientRect.x < entry.rootBounds.width;
                } else if (axis == "y") {
                    return  entry.boundingClientRect.y > 0 &&
                            entry.boundingClientRect.y < entry.rootBounds.height;
                }
                return entry.isIntersecting;
            });
            resolve(visibilityArray);
        });
        
        [...elements].forEach((element) => {
            if (element instanceof Element) observer.observe(element);
            else console.error('Invalid element:', element);
        });
    });
}

async function getVisibleElems(elems, axis = "all"){
    const nodeList = typeof(elems) == "string" ? document.querySelectorAll(elems) : elems;
    const visibilityArray = await checkVisibility(nodeList, axis);
    return Array.from(nodeList).filter((element, index) => visibilityArray[index]);
}
