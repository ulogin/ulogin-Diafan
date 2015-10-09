<?php
if(!defined('DIAFAN')) {
	include dirname(dirname(dirname(__FILE__))) . '/includes/404.php';
}

/**
 * Clauses
 */
class Ulogin extends Controller {

	public function show_block_auth() {
		if(isset($_POST["token"])) {
			$this->model->parse_request();
		}
		$result = $this->model->show_block_auth();
		echo $this->diafan->_tpl->get('show_block_auth', 'ulogin', $result);
	}

	public function show_block_sync() {
		if(isset($_POST["token"])) {
			$this->model->parse_request();
		}
		$result = $this->model->show_block_sync();
		echo $this->diafan->_tpl->get('show_block_sync', 'ulogin', $result);
	}
}