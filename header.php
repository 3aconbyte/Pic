<?php

// Make sure no one attempts to run this script "directly"
if (!defined('AMI')) {
	exit();
}

if (empty($ami_PageTitle)) {
	$ami_PageTitle = 'Пик';
}

if (defined('AMI_PAGE_TYPE')) {
	$ami_PageType = AMI_PAGE_TYPE;
} else {
	$ami_PageType = 'main_page';
}

$ami_CurrentPage = basename($_SERVER['PHP_SELF']);

//
$ami_MenuTemplate = '<div class="span-18 last prepend-5 last"><ul id="menu">%s</ul></div>';

// INDEX PAGE
if ($ami_CurrentPage == 'index.php') {
	ami_array_insert($ami_Menu, 0, '<li class="current">На главную</li>', 'root');
} else {
	ami_array_insert($ami_Menu, 0, '<li><a href="'.ami_link('root').'" title="Вернуться на главную страницу">На главную</a></li>', 'root');
}

// LINKS
if (in_array($ami_CurrentPage, array('links.php', 'links_group.php'))) {
	ami_array_insert($ami_Menu, 5, '<li class="current">Ссылки</li>', 'links');
}


// ABOUT PAGE
if (in_array($ami_CurrentPage, array('index.php', 'about.php', 'about_ext.php', 'about_updates.php', 'login.php', 'register.php', 'myfiles.php', 'profile.php', 'links.php', 'links_group.php', 'upload.php', 'settings.php', 'feedback.php'))) {
	if ($ami_CurrentPage == 'about.php') {
		ami_array_insert($ami_Menu, 10, '<li class="current">О проекте</li>', 'about');
	} else {
		ami_array_insert($ami_Menu, 10, '<li><a href="'.ami_link('about').'" title="Зачем это всё?">О проекте</a></li>', 'about');
	}
}

if ($ami_User['is_guest']) {
	if (in_array($ami_CurrentPage, array('index.php', 'about.php', 'about_ext.php', 'about_updates.php', 'login.php', 'register.php'))) {
		// LOGIN
		if ($ami_CurrentPage == 'login.php') {
			$ami_Menu['login'] = '<li class="current">Вход</li>';
		} else {
			$ami_Menu['login'] = '<li><a href="'.ami_link('login').'" title="Войти в систему">Вход</a></li>';
		}

		// REGISTER
		if ($ami_CurrentPage == 'register.php') {
			$ami_Menu['register'] =  '<li class="current">Регистрация</li>';
		}
	}
} else {
	if ($ami_CurrentPage == 'profile.php') {
		$ami_Menu['profile'] = '<li class="current">'.ami_htmlencode($ami_User['profile_name']).'</li>';
	} else {
		$ami_Menu['profile'] = '<li><a href="'.ami_link('profile').'" title="Мой профиль">'.ami_htmlencode($ami_User['profile_name']).'</a></li>';
	}
}

// SEND NO-CACHE HEADERS
header('Expires: Thu, 21 Jul 1977 07:30:00 GMT');	// When yours truly first set eyes on this world! :)
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');

// FLUSH FP-cache
if (defined('AMI_PAGE_TYPE') && in_array(AMI_PAGE_TYPE, array('upload_page','links_page','links_group_page'))) {
	header('Cache-Control: no-store', false);
} else {
	header('Cache-Control: post-check=0, pre-check=0', false);
}

header('Pragma: no-cache');		// For HTTP/1.0 compability
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="ru-RU" dir="ltr">
<head>
	<title><?php echo $ami_PageTitle; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
if ($ami_Production): ?>
	<link rel="stylesheet" href="<?php echo AMI_CSS_BASE_URL; ?>c/style.css" type="text/css" media="screen, projection">
	<!--[if lt IE 8]><link rel="stylesheet" href="<?php echo AMI_CSS_BASE_URL; ?>c/ie.css" type="text/css" media="screen, projection"><![endif]-->
<?php else: ?>
	<link rel="stylesheet" href="<?php echo AMI_CSS_BASE_URL; ?>css/blueprint/screen.css" type="text/css" media="screen, projection">
	<link rel="stylesheet" href="<?php echo AMI_CSS_BASE_URL; ?>css/style.css" type="text/css" media="screen, projection">
	<link rel="stylesheet" href="<?php echo AMI_CSS_BASE_URL; ?>css/jquery-ui-1.8.6.custom.css" type="text/css" media="screen, projection">
	<!--[if lt IE 8]><link rel="stylesheet" href="<?php echo AMI_CSS_BASE_URL; ?>css/blueprint/ie.css" type="text/css" media="screen, projection"><![endif]-->
<?php endif; ?>
	<link rel="shortcut icon" type="image/x-icon" href="<?php echo AMI_JS_BASE_URL; ?>favicon.ico">
</head>
<?php flush(); ?>
<body id="<?php echo $ami_PageType; ?>" class="kern">
	<div class="container">
<?php
	echo(sprintf($ami_MenuTemplate, implode('', $ami_Menu)));
	define('AMI_HEADER', 1);
?>
