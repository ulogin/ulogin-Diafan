<?php
/**
 * Обработка запроса при регистрации пользователя
 * @package    DIAFAN.CMS
 * @author     diafan.ru
 * @version    5.4
 * @license    http://www.diafan.ru/license.html
 * @copyright  Copyright (c) 2003-2015 OOO «Диафан» (http://www.diafan.ru/)
 */
if(!defined('DIAFAN')) {
	$path = __FILE__;
	$i = 0;
	while(!file_exists($path . '/includes/404.php')) {
		if($i == 10)
			exit;
		$i++;
		$path = dirname($path);
	}
	include $path . '/includes/404.php';
}

class Ulogin_action extends Action {

	/**
	 * Инициализация модуля
	 * @return void
	 */
	public function init() {
		if(isset($_POST['action']) && $_POST['action'] == 'delete') {
			$this->delete();
		}
	}

	/**
	 * Удаление привязки аккаунта "на лету"
	 * @return void
	 */
	public function delete() {
		if($_POST["identity"]) {
			try {
				DB::query("DELETE FROM {ulogin} WHERE identity='%s'", $_POST['identity']);
				$this->result["result"] = 'success';
				$this->result["msg"] = "Удаление привязки аккаунта " . $_POST['network'] . " успешно выполнено";
			} catch(Exception $e) {
				$this->result["result"] = 'error';
				$this->result["msg"] = "Ошибка при удалении аккаунта \n Exception: " . $e->getMessage();
			}
		}
	}
}