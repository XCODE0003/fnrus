<?
	$isProfile = isset($isProfile) && $isProfile;
?>

<!DOCTYPE html>
<html lang="ru">

<head>

	<meta charset="utf-8">

	<title><?= ($title ?? 'Page Title') . ' | FNRUS' ?></title>
	<meta name="description" content="description">
	<meta name="keywords" content="keywords"/>

	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	
	<link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32.png?v=3">
	<link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon-16.png?v=3">
	<link rel="apple-touch-icon" sizes="180x180" href="/assets/img/apple-touch-icon.png?v=3">
	<link rel="shortcut icon" href="/assets/img/favicon.ico?v=3">

	<link rel="stylesheet" href="css/style.min.css">
	<!-- Libs -->
	<link rel="stylesheet" href="libs/Swiper/swiper-bundle.min.css">
	<link rel="stylesheet" href="libs/simplebar/simplebar.css">
	<!-- End Libs -->
</head>

<body>

	<div class="preloader">
		<style>.preloader{position:fixed;left:0;right:0;top:0;bottom:0;background-color:#253144;z-index:999;display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:center;-ms-flex-pack:center;justify-content:center;-webkit-transition:opacity 1s;-o-transition:opacity 1s;transition:opacity 1s}.preloader .circular-loader{-webkit-animation:2s linear infinite rotate;animation:2s linear infinite rotate;width:200px;height:200px;-webkit-transition:opacity .3s ease-in-out;-o-transition:opacity .3s ease-in-out;transition:opacity .3s ease-in-out}.preloader .circular-loader circle{stroke:#8456ff}.preloader .loader-path{stroke-dasharray:150,200;stroke-dashoffset:-10;-webkit-animation:1.5s ease-in-out infinite dash;animation:1.5s ease-in-out infinite dash;stroke-linecap:round}body:not(._loaded){overflow:hidden}body._loaded .preloader{opacity:0;pointer-events:none}body._loaded .preloader .circular-loader{opacity:0}@-webkit-keyframes rotate{to{-webkit-transform:rotate(360deg);transform:rotate(360deg)}}@keyframes rotate{to{-webkit-transform:rotate(360deg);transform:rotate(360deg)}}@-webkit-keyframes dash{0%{stroke-dasharray:1,200;stroke-dashoffset:0}50%{stroke-dasharray:89,200;stroke-dashoffset:-35}100%{stroke-dasharray:89,200;stroke-dashoffset:-124}}@keyframes dash{0%{stroke-dasharray:1,200;stroke-dashoffset:0}50%{stroke-dasharray:89,200;stroke-dashoffset:-35}100%{stroke-dasharray:89,200;stroke-dashoffset:-124}}</style>
		<div class="loader">
			<svg class="circular-loader" viewBox="25 25 50 50" style="max-width:20vh;max-height:20vh;">
			  <circle class="loader-path" cx="50" cy="50" r="20" fill="none" stroke="#F1FF9D" stroke-width="2" />
			</svg>
		</div>
	</div>

	<div class="wrapper">
		<header class="header">
			<div class="content header__container">
				<a href="index.php" class="header__logo">
					<img src="/assets/img/logo.png" alt="">
				</a>
				<ul class="header__menu">
					<li class="header__menu__item"><a href="">Главная</a></li>
					<li class="header__menu__item"><a href="status.php">Статус софта</a></li>
					<li class="header__menu__item"><a href="about.php">О нас</a></li>
					<li class="header__menu__item"><a href="index.php#faq" data-scroll="#faq">Помощь</a></li>
				</ul>
				<div class="select header__lang">
					<select name="lang" id="">
						<option value="RU">RU</option>
						<option value="END">EN</option>
					</select>
					<div class="select__selected">
						<img src="img/flag_ru.svg" alt="">
						<span>RU</span>
					</div>
					<div class="select__inner">
						<div class="select__option" data-value="RU">
							<img src="img/flag_ru.svg" alt="">
							<span>RU</span>
						</div>
						<div class="select__option" data-value="EN">
							<img src="img/flag_en.svg" alt="">
							<span>EN</span>
						</div>
					</div>
				</div>
				<button class="header__search-btn"></button>
				<form class="header__search">
					<input type="text" class="header__search__input" placeholder="Поиск по сайту...">
					<button class="header__search-reset-btn"></button>
					<div class="header__search__inner">
						<div class="header__search__scroll-container" data-simplebar>
							<?
							$statuses = [
								'undetected' => 'undetected',
								'on-update' => 'on update',
								'detected' => 'detected'
							];
							$statusesKeys = array_keys($statuses);
							for ($i = 0; $i < 10; $i++){
								$isCheat = rand(0,5) > 4;
								if ($isCheat) $key = $statusesKeys[rand(0,count($statusesKeys) - 1)];
								?>
								<div class="search-item<?= $isCheat ? ' search-item_cheat' : '' ?>">
									<a href="javascript:void(0)" class="search-item__link"></a>
									<div class="search-item__image">
										<img src="img/<?= $isCheat ? 'cheat_ninja' : 'card_pubg' ?>.png" alt="">
									</div>
									<div class="search-item__info">
										<?
										if ($isCheat){
											?>
											<span class="cheat-status cheat-status_<?= $key ?> search-item__status">
												<?= file_get_contents(dirname(__DIR__) . '/img/icon_lightning.svg') ?>
												<?= $statuses[$key] ?>
											</span>
											<?
										} else{
											?><span class="types search-item__types"><?= \App\Providers\AppServiceProvider::pluralize(13, __('site.text_x_cheats_one'), __('site.text_x_cheats_few'), __('site.text_x_cheats_many')) ?></span><?
										}
										?>
										<span class="search-item__name"><?= $isCheat ? 'Софт' : 'Pubg Mobile' ?></span>
									</div>
								</div>
								<?
							}
							?>
						</div>
					</div>
					<div class="header__search__overlay"></div>
				</form>
				<?
				if ($isProfile){
					?>
					<a href="profile.php" class="btn header__login">
						<span class="btn__icon">
							<img src="img/icon_user.svg" alt="">
						</span>
						username
					</a>
					<?
				} else{
					?>
					<button class="btn header__login" data-popup="auth">
						<span class="btn__icon">
							<img src="img/icon_user.svg" alt="">
						</span>
						Вход/Регистрация
					</button>
					<?
				}
				?>
				<button class="header__hamburger"><span></span></button>
			</div>
		</header>