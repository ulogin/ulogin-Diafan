<?php
/**
 * Редактирование статей
 * @package    Diafan.CMS
 * @author     diafan.ru
 * @version    5.4
 * @license    http://cms.diafan.ru/license.html
 * @copyright  Copyright (c) 2003-2014 OOO «Диафан» (http://diafan.ru)
 */
if(!defined('DIAFAN')) {
	include dirname(dirname(dirname(__FILE__))) . '/includes/404.php';
}

class Ulogin_admin extends Frame_admin {
	/**
	 * Выводит контент модуля
	 * @return void
	 */
	public function show() {
		$ulogin_id1 = $this->diafan->configmodules("uloginid1", "ulogin");
		$ulogin_id2 = $this->diafan->configmodules("uloginid2", "ulogin");
		$check = $this->diafan->configmodules("check", "ulogin");
		echo '<p>' . $this->diafan->_('uLogin ID форма входа: ') . (isset($ulogin_id1) && !empty($ulogin_id1) ? $ulogin_id1 : $this->diafan->_('виджет по умолчанию')) . '</p>';
		echo '<p>' . $this->diafan->_('uLogin ID форма синхронизации: ') . (isset($ulogin_id2) && !empty($ulogin_id2) ? $ulogin_id2 : $this->diafan->_('виджет по умолчанию')) . '</p>';
		echo '<p>' . $this->diafan->_('Сохранять ссылку на профиль: ') . ((isset($check) && $check == 1) ? $this->diafan->_('вкл') : $this->diafan->_('выкл')) . '</p>';
	}
}