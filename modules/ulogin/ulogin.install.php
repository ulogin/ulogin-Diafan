<?php
if(!defined('DIAFAN')) {
	include dirname(dirname(dirname(__FILE__))) . '/includes/404.php';
}

class Ulogin_install extends Install {
	public $title = 'uLogin';
	public $modules = array (
		array (
			'name' => 'ulogin', 'admin' => true, 'site' => true, 'site_page' => true,
		),
	);

	public $admin = array (
		array (
			'name' => 'uLogin', 'rewrite' => 'ulogin', 'group_id' => '1', 'sort' => 1, 'act' => true,
			'children' => array (
				array (
					'name' => 'Настройки', 'rewrite' => 'ulogin/config',
				),
			)
		),
	);
	/**
	 * @var array таблица в базе данных
	 */
	public $tables = array (
		array (
			'name' => 'ulogin', 'comment' => 'uLogin Table', 'fields' => array (
			array (
				'name' => 'id', 'type' => 'INT(11) UNSIGNED NOT NULL AUTO_INCREMENT', 'comment' => 'Идентификатор'
			), array (
				'name' => 'user_id', 'type' => 'INT(11) UNSIGNED NOT NULL', 'comment' => 'ID Пользователя'
			), array (
				'name' => 'identity', 'type' => 'TEXT NOT NULL', 'comment' => 'Уникальный Идентификатор Пользователя'
			), array (
				'name' => 'network', 'type' => 'TEXT NOT NULL',
				'comment' => 'Неуникальный идентификатор Соцсети Пользователя'
			)
		), 'keys' => array (
			'PRIMARY KEY (id)',
		),
		),
	);
	/**
	 * @var array таблица дефолтных значений
	 */
	public $config = array (
		array (
			"name" => "check", "module_name" => "ulogin", "value" => true,
		),
	);
}