
<main>
	<section class="profile">
		<div class="content profile__container">
			<div class="profile__sidebar">
				<ul class="profile__block profile__menu">
					<li class="profile__menu__item<?= $profilePage == 'profile' ? ' _active' : '' ?>">
						<a href="profile.php">
							<?= file_get_contents(dirname(__DIR__) . '/img/icon_13.svg') ?>
							Профиль
						</a>
					</li>
					<li class="profile__menu__item<?= $profilePage == 'orders' ? ' _active' : '' ?>">
						<a href="orders.php">
							<?= file_get_contents(dirname(__DIR__) . '/img/icon_14.svg') ?>
							Мои покупки
						</a>
					</li>
					<li class="profile__menu__item<?= $profilePage == 'tickets' ? ' _active' : '' ?>">
						<a href="tickets.php">
							<?= file_get_contents(dirname(__DIR__) . '/img/icon_support.svg') ?>
							Тикеты
						</a>
					</li>
					<li class="profile__menu__item<?= $profilePage == 'referral' ? ' _active' : '' ?>">
						<a href="referral.php">
							<?= file_get_contents(dirname(__DIR__) . '/img/icon_16.svg') ?>
							Реф. система
						</a>
					</li>
				</ul>
				<div class="profile__block profile__identity">
					<div class="profile__identity__container">
						<div class="profile__avatar">
							<img src="img/avatar.png" alt="">
						</div>
						<div class="profile__identity__user-info">
							<p class="profile__identity__nickname">Nickname</p>
							<p class="profile__identity__balance">240,50 ₽</p>
						</div>
						<a href="" class="profile__identity__exit"></a>
					</div>
					<button class="btn btn-accent profile__identity__replenishment-btn" data-popup="replenishment">Пополнить баланс</button>
				</div>
			</div>
			<div class="profile__main">