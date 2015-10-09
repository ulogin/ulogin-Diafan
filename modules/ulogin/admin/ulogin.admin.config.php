<?php
if(!defined('DIAFAN')) {
	include dirname(dirname(dirname(__FILE__))) . '/includes/404.php';
}

class Ulogin_admin_config extends Frame_admin {
	/**
	 * @var array поля в базе данных для конфигурации модуля
	 */
	public $variables = array (
		'config' => array (
			'uloginid1' => array (
				'type' => 'text', 'name' => 'uLogin ID форма входа',
				'help' => 'Идентификатор виджета авторизации и регистрации. Пустое поле - виджет по умолчанию',
			), 'uloginid2' => array (
				'type' => 'text', 'name' => 'uLogin ID форма синхронизации',
				'help' => 'Идентификатор виджета блока синхронизации. Пустое поле - виджет по умолчанию',
			), 'check' => array (
				'type' => 'checkbox', 'name' => 'Сохранять ссылку на профиль',
				'help' => 'Сохранять ссылку на страницу пользователя в соцсети при авторизации через uLogin',
				'default' => true,
			),
		)
	);

	/**
	 * @var array настройки модуля
	 */
	public $config = array (
		'config', // файл настроек модуля
	);
}