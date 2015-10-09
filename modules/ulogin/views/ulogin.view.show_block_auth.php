<?php
if(!defined('DIAFAN')) {
	include dirname(dirname(dirname(__FILE__))) . '/includes/404.php';
}
if(!$this->diafan->_users->id) {
	$this->diafan->_site->js_view[] = 'https://ulogin.ru/js/ulogin.js';
	echo '<div class="ulogin_block block">';
	echo '<h3>' . $this->diafan->_('Войти с помощью') . '</h3>';
	echo $result;
	echo '</div>';
	echo '<div class="errors error" style="display:none"></div>';
}