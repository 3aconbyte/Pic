<?php

if (!defined('AMI_ROOT')) {
	define('AMI_ROOT', './');
}

require AMI_ROOT.'functions.inc.php';


$ami_PageTitle = 'Регистратура';

// FACEBOOK
$register_facebook_form_action = ami_link('register_facebook');
$csrf_facebook = ami_MakeFormToken($register_facebook_form_action);
//
$register_form_action = ami_link('register');
$csrf = ami_MakeFormToken($register_form_action);
$async = isset($_GET['async']);

// OLD VALUES
$e_email_value = isset($_POST['e']) ? ami_htmlencode($_POST['e']) : '';


if (isset($_GET['ok'])) {
	ami_show_message('Спасибо', 'Вы&nbsp;успешно зарегистрировались и&nbsp;вошли в&nbsp;систему');
}


$facebook_block = '';
if ($ami_UseFacebook) {
	$facebook_block = <<<FMB
	<div class="span-12 append-6 last prepend-top">
		<hr>
		<p>Если вы пользователь сервиса Фейсбук, используйте его — регистрация займет всего 1&nbsp;секунду!<br/>
		<fb:login-button perms="email" autologoutlink="true" size="medium" background="white" length="short"></fb:login-button>
		</p>
	</div>

	<div id="fb-root"></div>
	<script>
		window.fbAsyncInit = function() {
			// Init
			FB.init({ appId: '142764589077335', status: true, cookie: true, xfbml: true });

			// Event
			FB.Event.subscribe('auth.statusChange', function(response) {
				if (response.status == 'connected') {
					PIC.utils.makeGETRequest('$register_facebook_form_action');
				}
			});
		};

		// LOAD
		(function () {
			var e = document.createElement('script');
			e.src = document.location.protocol + '//connect.facebook.net/ru_RU/all.js';
			e.async = true;
			document.getElementById('fb-root').appendChild(e);
		}());
	</script>
FMB;
}

$form = <<<FMB
<div class="span-15 last prepend-5 body_block">
	%s
	<h2>Регистратура</h2>

	<p class="span-10 append-6 last">
		Чтобы загружать картинки на&nbsp;<em>pic.lg.ua</em>,
		регистрироваться не&nbsp;обязательно. Но&nbsp;с&nbsp;регистрацией удобнее.
	</p>

	<p class="span-10 append-6 last">Введите адрес электронной почты в&nbsp;качестве логина.</p>

	<form method="post" action="$register_form_action" name="register" accept-charset="utf-8" autocomplete="off">
		<p>
			<input type="hidden" name="form_sent" value="1">
			<input type="hidden" name="csrf_token" value="$csrf">
		</p>

		<div class="formRow">
			<label for="e" id="label_e">Электронная почта</label><br>
			<input type="text" class="text" id="e" name="e" tabindex="1" maxlength="128" value="$e_email_value">
		</div>

		<div class="formRow">
			<label for="p" id="label_p">Пароль</label><br>
			<input type="text" class="text" id="p" name="p" tabindex="2" maxlength="1024">
		</div>

		<div class="formRow buttons">
			<input class="button" type="submit" name="do" value="Зарегистрироваться" tabindex="3">
		</div>
	</form>
	$facebook_block
</div>
FMB;

try {
	if (isset($_POST['form_sent']) || isset($_GET['facebook'])) {

		// FACEBOOK PART
		if (isset($_GET['facebook'])) {
			// Create our Application instance (replace this with your appId and secret).
			$facebook = new Facebook(array('appId' => '142764589077335','secret' => 'b1da5f70416eed03e55c7b2ce7190bd6','cookie' => TRUE,));
			$session = $facebook->getSession();

			$me = null;
			// Session based API call.
			if ($session) {
				$uid = $facebook->getUser();
				$me = $facebook->api('/me');

				if (!$me) {
					throw new InvalidInputDataException('Ошибка на стороне Фейсбука');
				}

				// REGISTER new USER
				$db = DB::singleton();

				// CHECK EMAIL
				$result = $db->numRows('SELECT id FROM users WHERE facebook_uid=? LIMIT 1', $uid);
				if ($result !== 0) {
					// USER already registered
					ami_redirect(ami_link('root'));
					exit();
				}


				$db->query("INSERT INTO users VALUES('', ?, ?, NOW(), 0, ?)", $me['email'], '-', $uid);
				$user_id = $db->lastID();

				// LOGIN as FACEBOOK USER
				$o_ami_user = new AMI_User();
				$o_ami_user->facebook_login($user_id, $me['email'], 0, md5($session['session_key']), $uid, $me['name']);

				// EXIT
				if ($async) {
					ami_async_response(array('error'=> 0, 'message' => ''), AMI_ASYNC_JSON);
				} else {
					ami_redirect(ami_link('root'));
				}
			} else {
				throw new InvalidInputDataException('Ошибка на стороне Фейсбука');
			}
		}

		// 1. check csrf
		if (!ami_CheckFormToken($csrf)) {
			throw new InvalidInputDataException('Действие заблокировано системой безопасности');
		}

		$email = isset($_POST['e']) ? mb_strtolower(ami_trim($_POST['e'])) : FALSE;
		$password = isset($_POST['p']) ? $_POST['p'] : FALSE;


		// check email
		if (!ami_IsValidEmail($email)) {
			throw new InvalidInputDataException('Вы ввели некорректный адрес электронной почты');
		}

		// check password
		if ((utf8_strlen($password) < 1) || (utf8_strlen($password) > 1024)) {
			throw new InvalidInputDataException('Вы ввели некорректный пароль');
		}


		$db = DB::singleton();
		$result = $db->numRows('SELECT id FROM users WHERE email=? LIMIT 1', $email);
		if ($result !== 0) {
			throw new InvalidInputDataException('Такой адрес эл.&nbsp;почты уже зарегистрирован');
		}

		$t_hasher = new PasswordHash(12, FALSE);
		$cryptPassword = $t_hasher->HashPassword($password);

		$db->query("INSERT INTO users VALUES('', ?, ?, NOW(), 0, '')", $email, $cryptPassword);
		$user_id = $db->lastID();

		// MAKE LOGIN
		$o_ami_user = new AMI_User();
		$o_ami_user->login($user_id, $email, 0);

		// is async request
		if ($async) {
			ami_async_response(array('error'=> 0, 'message' => ''), AMI_ASYNC_JSON);
		} else {
			ami_redirect(ami_link('register_ok'));
		}
	}
} catch (AppLevelException $e) {
	if ($async) {
		ami_async_response(array('error'=> 1, 'message' => $e->getMessage()), AMI_ASYNC_JSON);
	} else {
		ami_show_error_message($e->getMessage());
	}
} catch (InvalidInputDataException $e) {
	if ($async) {
		ami_async_response(array('error'=> 1, 'message' => $e->getMessage()), AMI_ASYNC_JSON);
	} else {
		ami_addOnDOMReady('AMI.utils.init_form($("form[name=register]"));');
		ami_printPage(sprintf($form, '<div class="span-20"><div class="error span-10 last">'.$e->getMessage().'</div></div>'));
		exit();
	}
} catch (Exception $e) {
	if ($async) {
		ami_async_response(array('error'=> 1, 'message' => $e->getMessage()), AMI_ASYNC_JSON);
	} else {
		ami_show_error($e->getMessage());
	}
}

ami_addOnDOMReady('AMI.utils.init_form($("form[name=register]"));');
ami_printPage(sprintf($form, ''));

?>