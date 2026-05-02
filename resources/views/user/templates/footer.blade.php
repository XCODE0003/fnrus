<?
	$isProfile = isset($isProfile) && $isProfile;
?>
		<footer class="footer<?= $isProfile ? ' footer_profile' : '' ?>">
			<div class="content footer__container">
				<a href="index.php" class="footer__logo">
					<img src="/assets/img/logo.png" alt="">
				</a>
				<?
				if (!$isProfile){
					?>
					<ul class="footer__menu">
						<li class="footer__menu__item _active"><a href="index.php">Главная</a></li>
						<li class="footer__menu__item"><a href="status.php">Статус софта</a></li>
						<li class="footer__menu__item"><a href="about.php">О нас</a></li>
						<li class="footer__menu__item"><a href="index.php#faq">Помощь</a></li>
						<li class="footer__menu__item"><a href="policy.php">Пользовательское соглашение</a></li>
					</ul>
					<a href="https://t.me/Fnrus_Keys" class="social-btn footer__social-btn" target="_blank"> 
						<span class="social-btn__icon">
							<?= file_get_contents(dirname(__DIR__) . '/img/icon_tg.svg') ?>
						</span>
						<span class="social-btn__text">Telegram Bot</span>
					</a>
					<?
				} else{
					?>
					<p class="footer__copy">©Fnrus 2026. Все права защищены</p>
					<?
				}
				?>
			</div>
			<?
			if (!$isProfile){
				?>
				<div class="content footer__bottom-container">
					<a href="policy.php" class="footer__menu__item">Пользовательское соглашение</a>
					<p class="footer__copy">©Fnrus 2026. Все права защищены</p>
					<a href="https://freekassa.net" target="_blank" rel="noopener noreferrer"><img src="https://cdn.freekassa.net/banners/big-dark-1.png" title="Прием платежей на сайте для физических лиц и т.д." style="max-height: 30px;"></a>
				</div>
				<?
			}
			?>
		</footer>
	</div>

	<div class="popup" id="auth">
		<p class="popup__caption">Авторизация</p>
		<div class="input-block">
			<div class="input-block__label-container">
				<label
					for="auth-login"
					class="input-block__label"
				>
					Логин
				</label>
			</div>
			<div class="input-block__input-container">
				<input
					type="text"
					class="input-block__input"
					placeholder="Введите email или username"
					id="auth-login"
				>
			</div>
		</div>
		<div class="input-block input-block_showHide">
			<div class="input-block__label-container">
				<label
					for="auth-pass"
					class="input-block__label"
				>
					Пароль
				</label>
				<button class="input-block__label-link" data-popup="resetPass">Не помню пароль</button>
			</div>
			<div class="input-block__input-container">
				<input
					type="text"
					class="input-block__input"
					placeholder=""
					id="auth-pass"
				>
				<button class="input-block__showHide-btn"></button>
			</div>
		</div>
		<button class="btn btn-accent popup__submit-btn" type="submit">Войти</button>
		<p class="popup__note">нажав кнопку, вы даете согласие на обработку персональных данных</p>
		<p class="popup__hint">Нет аккаунта? <button class="popup__hint__btn" data-popup="register">Зарегистрироваться</button></p>
	</div>

	<div class="popup" id="register">
		<p class="popup__caption">Регистрация</p>
		<div class="input-block">
			<div class="input-block__label-container">
				<label
					for="register-login"
					class="input-block__label"
				>
					Логин
				</label>
			</div>
			<div class="input-block__input-container">
				<input
					type="text"
					class="input-block__input"
					placeholder="Введите email или username"
					id="register-login"
				>
			</div>
		</div>
		<div class="input-block input-block_showHide">
			<div class="input-block__label-container">
				<label
					for="register-pass"
					class="input-block__label"
				>
					Пароль
				</label>
			</div>
			<div class="input-block__input-container">
				<input
					type="text"
					class="input-block__input"
					placeholder=""
					id="register-pass"
				>
				<button class="input-block__showHide-btn"></button>
			</div>
		</div>
		<div class="input-block input-block_showHide">
			<div class="input-block__label-container">
				<label
					for="register-repeat-pass"
					class="input-block__label"
				>
					Подтвердите пароль
				</label>
			</div>
			<div class="input-block__input-container">
				<input
					type="text"
					class="input-block__input"
					placeholder=""
					id="register-repeat-pass"
				>
				<button class="input-block__showHide-btn"></button>
			</div>
		</div>
		<div class="toggle popup__toggle">
			<input
				type="checkbox"
				class="toggle__input"
				id="register-referral-toggle"
			>
			<label
				for="register-referral-toggle"
				class="toggle__label"
			>
				<span class="toggle__icon"></span>
				<span class="toggle__text">
					У меня есть реферальный код
				</span>
			</label>
		</div>
		<div class="input-block" id="register-referral-input-block" style="display: none">
			<div class="input-block__label-container">
				<label
					for="register-repeat-pass"
					class="input-block__label"
				>
					Реферальный код
				</label>
			</div>
			<div class="input-block__input-container">
				<input
					type="text"
					class="input-block__input"
					placeholder=""
					id="register-repeat-pass"
				>
			</div>
		</div>
		<button class="btn btn-accent popup__submit-btn" type="submit">Зарегистрироваться</button>
		<p class="popup__note">нажав кнопку, вы даете согласие на обработку персональных данных</p>
		<p class="popup__hint">Есть аккаунт? <button class="popup__hint__btn" data-popup="auth">Войти</button></p>
	</div>

	<div class="popup" id="resetPass">
		<p class="popup__caption">Забыли пароль?</p>
		<div class="input-block">
			<div class="input-block__label-container">
				<label
					for="resetPass-email"
					class="input-block__label"
				>
					Email
				</label>
			</div>
			<div class="input-block__input-container">
				<input
					type="text"
					class="input-block__input"
					placeholder="Введите email или username"
					id="resetPass-email"
				>
			</div>
		</div>
		<div class="input-block">
			<div class="input-block__label-container">
				<label
					for="resetPass-code"
					class="input-block__label"
				>
					Код
				</label>
			</div>
			<div class="input-block__input-container">
				<input
					type="text"
					class="input-block__input"
					placeholder=""
					id="resetPass-code"
				>	
			</div>
		</div>
		<div class="input-block input-block_showHide">
			<div class="input-block__label-container">
				<label
					for="resetPass-pass"
					class="input-block__label"
				>
					Придумайте пароль
				</label>
			</div>
			<div class="input-block__input-container">
				<input
					type="text"
					class="input-block__input"
					placeholder=""
					id="resetPass-pass"
				>
				<button class="input-block__showHide-btn"></button>
			</div>
		</div>
		<div class="input-block input-block_showHide">
			<div class="input-block__label-container">
				<label
					for="resetPass-repeat-pass"
					class="input-block__label"
				>
					Подтвердите пароль
				</label>
			</div>
			<div class="input-block__input-container">
				<input
					type="text"
					class="input-block__input"
					placeholder=""
					id="resetPass-repeat-pass"
				>
				<button class="input-block__showHide-btn"></button>
			</div>
		</div>
		<button class="btn btn-accent popup__submit-btn" type="submit">Сбросить пароль</button>
	</div>

	<div class="popup" id="changePass">
		<p class="popup__caption">Изменить пароль</p>
		<div class="input-block input-block_showHide">
			<div class="input-block__label-container">
				<label
					for="changePass-pass"
					class="input-block__label"
				>
					Старый пароль
				</label>
				<button class="input-block__label-link" data-popup="resetPass">Не помню пароль</button>
			</div>
			<div class="input-block__input-container">
				<input
					type="text"
					class="input-block__input"
					placeholder="Введите старый пароль"
					id="changePass-pass"
				>
				<button class="input-block__showHide-btn"></button>
			</div>
		</div>
		<div class="input-block input-block_showHide">
			<div class="input-block__label-container">
				<label
					for="changePass-new-pass"
					class="input-block__label"
				>
					Новый пароль
				</label>
			</div>
			<div class="input-block__input-container">
				<input
					type="text"
					class="input-block__input"
					placeholder=""
					id="changePass-new-pass"
				>
				<button class="input-block__showHide-btn"></button>
			</div>
		</div>
		<div class="input-block input-block_showHide">
			<div class="input-block__label-container">
				<label
					for="changePass-repeat-pass"
					class="input-block__label"
				>
					Подтвердите пароль
				</label>
			</div>
			<div class="input-block__input-container">
				<input
					type="text"
					class="input-block__input"
					placeholder=""
					id="changePass-repeat-pass"
				>
				<button class="input-block__showHide-btn"></button>
			</div>
		</div>
		<button class="btn btn-accent popup__submit-btn" type="submit">Изменить пароль</button>
	</div>

	<div class="popup" id="withdraw">
		<p class="popup__caption">вывод денег</p>
		<div class="input-block input-block_showHide">
			<div class="input-block__label-container">
				<label
					for=""
					class="input-block__label"
				>
					Выберите способ вывода
				</label>
			</div>
			<div class="input-block__input-container">
				<div class="select _unfold input-block__select">
					<select name="withdraw-method">
						<option value="Qiwi">Qiwi</option>
						<option value="Банковская карта">Банковская карта</option>
						<option value="Банковская карта 2">Банковская карта 2</option>
					</select>
				</div>
			</div>
		</div>
		<div class="input-block">
			<div class="input-block__label-container">
				<label
					for="withdraw-details"
					class="input-block__label"
				>
					Реквизиты
				</label>
			</div>
			<div class="input-block__input-container">
				<input
					type="text"
					class="input-block__input"
					placeholder=""
					id="withdraw-details"
				>
			</div>
		</div>
		<div class="checkbox popup__checkbox">
			<input
				type="checkbox"
				class="checkbox__input"
				id="withdraw-confirm-data"
			>
			<label
				for="withdraw-confirm-data"
				class="checkbox__label"
			>
				<span class="checkbox__icon"></span>
				<span class="checkbox__text">
					Я подтверждаю, что данные введены верно
				</span>
			</label>
		</div>
		<button class="btn btn-accent popup__submit-btn" type="submit">Создать заявку</button>
	</div>

	<div class="popup" id="buy">
		<p class="popup__caption">Покупка Nerohole</p>
		<div class="popup__step _active" data-popup-step="1">
			<div class="input-block">
				<div class="input-block__input-container">
					<div class="select _unfold input-block__select">
						<select name="withdraw-method">
							<option value="150₽ за 1 день">150₽ за 1 день</option>
							<option value="150₽ за 1 день">150₽ за 1 день</option>
							<option value="150₽ за 1 день">150₽ за 1 день</option>
							<option value="150₽ за 1 день">150₽ за 1 день</option>
							<option value="150₽ за 1 день">150₽ за 1 день</option>
						</select>
					</div>
				</div>
			</div>
			<div class="input-block">
				<div class="input-block__label-container">
					<label
						for="withdraw-details"
						class="input-block__label"
					>
						Способ оплаты
					</label>
				</div>
				<div class="input-block__radio-container">
					<input type="radio" name="buy-method" id="buy-method-1" checked>
					<label for="buy-method-1" class="input-block__radio-label">
						<div class="input-block__radio-label__icon"></div>
						<span>Платежные системы</span>	
					</label>
					<input type="radio" name="buy-method" id="buy-method-2">
					<label for="buy-method-2" class="input-block__radio-label">
						<div class="input-block__radio-label__icon"></div>
						<span>Списать с баланса</span>	
					</label>
				</div>
			</div>
			<div class="input-block">
				<div class="input-block__input-container">
					<input
						type="text"
						class="input-block__input"
						placeholder="Введите E-mail"
						id="buy-email"
					>
				</div>
			</div>
			<div class="input-block input-block_promo">
				<div class="input-block__input-container">
				<input
						type="text"
						class="input-block__input"
						placeholder="Промокод"
						id="buy-promo"
						autocomplete="new-password"
						name="promo_field_no_autofill"
						data-lpignore="true"
						data-1p-ignore="true"
						data-bwignore="true"
						data-form-type="other"
						readonly
						onfocus="this.removeAttribute('readonly');"
					>
					<button class="input-block__input-container__apply-promo">OK</button>
				</div>
			</div>
			<button class="btn btn-accent popup__submit-btn" type="submit" data-popup-switch-step="2">Перейти к следующему шагу</button>
		</div>
		<div class="popup__step" data-popup-step="2">
			<div class="popup__payment-methods">
				<?
				$payments = ['visamir', 'visamastercard', 'visamir', 'visamastercard', 'umoney', 'crypto', 'binance', 'sbp', 'qiwi'];
				for ($i = 0; $i < 9; $i++){
					?>
					<input type="radio" name="replenishment-payment" id="replenishment-payment-<?= $i+1 ?>">
					<label for="replenishment-payment-<?= $i+1 ?>" class="popup__payment-method">
						<?
						if ($i < 3){
							?><div class="popup__payment-method__custom-info">#<?= $i+1 ?></div><?
						} else if ($i == 3){
							?><div class="popup__payment-method__custom-info">UAH</div><?
						}
						?>
						<img src="img/payment_<?= $payments[$i] ?>.svg" alt="">
					</label>
					<?
				}
				?>
			</div>
			<div class="popup__buy-info">
				<div class="popup__buy-info__block">Срок действия: <span>150₽ за 1 день</span></div>
				<div class="popup__buy-info__block-line">
					<div class="popup__buy-info__block">Email: <span>name@gmail.com</span></div>
					<div class="popup__buy-info__block">Итого к оплате: <span>150₽</span></div>
				</div>
			</div>
			<div class="popup__btns-container">
				<button class="btn popup__back-btn">Назад</button>
				<button class="btn btn-accent popup__submit-btn" type="submit" data-popup-switch-step="2">Перейти к оплате</button>
			</div>
		</div>
	</div>

	<div class="popup" id="replenishment">
		<p class="popup__caption">Пополнить баланс</p>
		<div class="popup__payment-methods">
			<?
			$payments = ['visamir', 'visamastercard', 'visamir', 'visamastercard', 'umoney', 'crypto', 'binance', 'sbp', 'qiwi'];
			for ($i = 0; $i < 9; $i++){
				?>
				<input type="radio" name="replenishment-payment" id="replenishment-payment-<?= $i+1 ?>">
				<label for="replenishment-payment-<?= $i+1 ?>" class="popup__payment-method">
					<?
					if ($i < 3){
						?><div class="popup__payment-method__custom-info">#<?= $i+1 ?></div><?
					} else if ($i == 3){
						?><div class="popup__payment-method__custom-info">UAH</div><?
					}
					?>
					<img src="img/payment_<?= $payments[$i] ?>.svg" alt="">
				</label>
				<?
			}
			?>
		</div>
		<div class="popup__input-line">
			<div class="input-block">
				<div class="input-block__input-container">
					<input type="text" class="input-block__input" placeholder="Введите сумму">
				</div>
			</div>
			<div class="input-block input-block_promo">
				<div class="input-block__input-container">
					<input
						type="text"
						class="input-block__input"
						placeholder="Промокод"
						id="buy-promo"
						autocomplete="new-password"
						name="promo_field_no_autofill"
						data-lpignore="true"
						data-1p-ignore="true"
						data-bwignore="true"
						data-form-type="other"
						readonly
						onfocus="this.removeAttribute('readonly');"
					>
					<button class="input-block__input-container__apply-promo">OK</button>
				</div>
			</div>
		</div>
		<button class="btn btn-accent popup__submit-btn" type="submit">Пополнить баланс</button>
	</div>
	
	
	<script src="js/scripts.min.js"></script>
</body>
</html>
