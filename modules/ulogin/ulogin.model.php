<?php
if(!defined('DIAFAN')) {
	include dirname(dirname(dirname(__FILE__))) . '/includes/404.php';
}

class Ulogin_model extends Model {

	public function show_block_auth($place = 0) {
		$redirect_uri = urlencode(BASE_PATH_HREF . $this->diafan->_route->current_link());
		$ulogin_default_options = array ();
		$ulogin_default_options['display'] = 'small';
		$ulogin_default_options['providers'] = 'vkontakte,odnoklassniki,mailru,facebook,google,yandex,twitter';
		$ulogin_default_options['fields'] = 'first_name,last_name,email,photo,photo_big,phone';
		$ulogin_default_options['optional'] = 'sex,bdate,country,city';
		$ulogin_default_options['hidden'] = 'other';
		$ulogin_options = array ();
		$ulogin_options['ulogin_id1'] = $this->diafan->configmodules("uloginid1", "ulogin");
		$ulogin_options['ulogin_id2'] = $this->diafan->configmodules("uloginid2", "ulogin");
		$default_panel = false;
		switch($place) {
			case 0:
				$ulogin_id = $ulogin_options['ulogin_id1'];
				break;
			case 1:
				$ulogin_id = $ulogin_options['ulogin_id2'];
				break;
			default:
				$ulogin_id = $ulogin_options['ulogin_id1'];
		}
		if(empty($ulogin_id)) {
			$ul_options = $ulogin_default_options;
			$default_panel = true;
		}
		$panel = '';
		$panel .= '<div class="ulogin_panel"';
		if($default_panel) {
			$ul_options['redirect_uri'] = $redirect_uri;
			unset($ul_options['label']);
			$x_ulogin_params = '';
			foreach($ul_options as $key => $value)
				$x_ulogin_params .= $key . '=' . $value . ';';
			if($ul_options['display'] != 'window')
				$panel .= ' data-ulogin="' . $x_ulogin_params . '"></div>'; else
				$panel .= ' data-ulogin="' . $x_ulogin_params . '" href="#"><img src="https://ulogin.ru/img/button.png" width=187 height=30 alt="МультиВход"/></div>';
		} else
			$panel .= ' data-uloginid="' . $ulogin_id . '" data-ulogin="redirect_uri=' . $redirect_uri . '"></div>';
		$panel = '<div class="ulogin_block">' . $panel . '</div>';

		return $panel;
	}

	public function show_block_sync($user_id = 0) {
		$current_user = $this->diafan->_users->id;
		$user_id = empty($user_id) ? $current_user : $user_id;
		if(empty($user_id))
			return '';
		$res = DB::query_fetch_all("SELECT * FROM {ulogin} WHERE user_id = %d", $user_id);
		$networks = array ();
		foreach($res as $network) {
			$networks[] = $network;
		}
		$output = '
			<style>
			    .big_provider {
			        display: inline-block;
			        margin-right: 10px;
			    }
			</style>
			<h4>' . $this->diafan->_('Синхронизация аккаунтов') . '</h4>' . $this->show_block_auth(1) . '<p>' . $this->diafan->_('Привяжите ваши аккаунты соц. сетей к личному кабинету для быстрой авторизации через любой из них') . '</p>
            <h4>' . $this->diafan->_('Привязанные аккаунты') . '</h4>';
		if($networks) {
			$output .= '<div id="ulogin_accounts">';
			foreach($networks as $network) {
				if($network['user_id'] = $user_id)
					$output .= "<div data-ulogin-network='{$network['network']}'  data-ulogin-identity='{$network['identity']}' class='ulogin_network big_provider {$network['network']}_big'></div>";
			}
			$output .= '</div>
            <p>' . $this->diafan->_('Вы можете удалить привязку к аккаунту, кликнув по значку') . '</p>';

			return $output;
		}

		return $output;
	}

	public function parse_request() {
		$u_user = $this->uloginGetUserFromToken($_POST['token']);
		if(!$u_user) {
			die($this->diafan->_('Ошибка работы uLogin: Не удалось получить данные о пользователе с помощью токена'));
		}
		$u_user = json_decode($u_user, true);
		$check = $this->uloginCheckTokenError($u_user);
		if(empty($check)) {
			return false;
		}
		$user_id = $this->uloginGetUserIdByIdentity($u_user['identity']);
		if(isset($user_id) && !empty($user_id)) {
			$d = DB::query_result("SELECT * FROM {users} WHERE id=%d", $user_id);
			$f = DB::query_result("SELECT * FROM {users} WHERE trash='0' AND act='1' AND id=%d", $user_id);
			if(!$f && $d) {
				die('Ошибка авторизации. Данный аккаунт удалён или заблокирован. Обратитесь к администратору сайта.');
			}
			if($user_id > 0 && $d > 0) {
				$this->uloginCheckUserId($user_id);
			} else {
				$user_id = $this->uloginRegistrationUser($u_user, 1);
			}
		} else $user_id = $this->uloginRegistrationUser($u_user);
		if($user_id > 0) {
			$this->uloginloginUser($user_id);
		}

		return true;
	}

	/**
	 * Обменивает токен на пользовательские данные
	 * @param bool $token
	 * @return bool|mixed|string
	 */
	public function uloginGetUserFromToken($token = false) {
		$response = false;
		if($token) {
			$data = array ( 'cms' => 'diafan', 'version' => VERSION_CMS );
			$request = 'http://ulogin.ru/token.php?token=' . $token . '&host=' . $_SERVER['HTTP_HOST'] . '&data=' . base64_encode(json_encode($data));
			if(in_array('curl', get_loaded_extensions())) {
				$c = curl_init($request);
				curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
				$response = curl_exec($c);
				curl_close($c);
			} elseif(function_exists('file_get_contents') && ini_get('allow_url_fopen'))
				$response = file_get_contents($request);
		}

		return $response;
	}

	/**
	 * Проверка пользовательских данных, полученных по токену
	 * @param $u_user - пользовательские данные
	 * @return bool
	 */
	public function uloginCheckTokenError($u_user) {
		if(!is_array($u_user)) {
			throw new Exception($this->diafan->_('Ошибка работы uLogin. Данные о пользователе содержат неверный формат'));
		}
		if(isset($u_user['error'])) {
			$strpos = strpos($u_user['error'], 'host is not');
			if($strpos) {
				throw new Exception($this->diafan->_('Ошибка работы uLogin. Адрес хоста не совпадает с оригиналом'));
			}
			switch($u_user['error']) {
				case 'token expired':
					throw new Exception($this->diafan->_('Ошибка работы uLogin. Время жизни токена истекло'));
				case 'invalid token':
					throw new Exception($this->diafan->_('Ошибка работы uLogin. Неверный токен'));
				default:
					throw new Exception($this->diafan->_('Ошибка работы uLogin. ') . $u_user['error']);
			}
		}
		if(!isset($u_user['identity'])) {
			throw new Exception($this->diafan->_('Ошибка работы uLogin. В возвращаемых данных отсутствует переменная
			 "identity"'));
		}
		if(!isset($u_user['first_name'])) {
			throw new Exception($this->diafan->_('Ошибка работы uLogin. В возвращаемых данных отсутствует переменная
			 "first_name"'));
		}

		return true;
	}

	public function uloginGetUserIdByIdentity($identity) {
		$res = DB::query_result("SELECT user_id FROM {ulogin} WHERE identity = '%s'", $identity);
		if($res)
			return $res;

		return false;
	}

	/**
	 * Регистрация на сайте и в таблице uLogin
	 * @param Array $u_user - данные о пользователе, полученные от uLogin
	 * @param int $in_db - при значении 1 необходимо переписать данные в таблице uLogin
	 * @return bool|int|Error
	 */
	function uloginRegistrationUser($u_user, $in_db = 0) {
		if(!isset($u_user['email'])) {
			throw new Exception($this->diafan->_("Через данную форму выполнить вход/регистрацию невозможно. </br>" . "Сообщиете администратору сайта о следующей ошибке: </br></br>" . "Необходимо указать <b>email</b> в возвращаемых полях <b>uLogin</b>"));
		}
		$u_user['network'] = isset($u_user['network']) ? $u_user['network'] : '';
		$u_user['phone'] = isset($u_user['phone']) ? $u_user['phone'] : '';
		$u_user['nickname'] = isset($u_user['nickname']) ? $u_user['nickname'] : '';
		// данные о пользователе есть в ulogin_table, но отсутствуют в Базе
		if($in_db == 1) {
			DB::query("DELETE FROM {ulogin} WHERE identity = '%s'", $u_user['identity']);
		}
		$user_id = DB::query_result("SELECT id FROM {users} WHERE mail='%s'", $u_user['email']);
		// $check_m_user == true -> есть пользователь с таким email
		$check_m_user = $user_id > 0 ? true : false;
		$current_user = isset($this->diafan->_users->id) ? $this->diafan->_users->id : 0;
		// $isLoggedIn == true -> ползователь онлайн
		$isLoggedIn = ($current_user > 0) ? true : false;
		if(!$check_m_user && !$isLoggedIn) { // отсутствует пользователь с таким email в базе -> регистрация
			$user_data = array ();
			$user_data['name'] = $this->uloginGenerateNickname($u_user['first_name'], $u_user['last_name'], $u_user['nickname'], $u_user['bdate']);
			$user_data['fio'] = ((isset($u_user['first_name'])) ? $u_user['first_name'] : '') . " " . ((isset($u_user['last_name'])) ? $u_user['last_name'] : '');
			$user_data['mail'] = $u_user['email'];
			$user_data['password'] = substr(md5($u_user['email']), 0, 8);
			$user_data['phone'] = isset($u_user['phone']) ? $u_user['phone'] : '';
			$identity = ($this->diafan->configmodules("check", "ulogin") != false) ? $u_user['profile'] : '';
			$role_id = DB::query_result("SELECT id FROM {users_role} WHERE registration='1' AND trash='0' LIMIT 1");
			DB::query("INSERT INTO {users} (name, password, fio, phone, identity, mail, act, role_id, created) VALUES ('%h', '%h','%h', '%h', '%h', '%s', '1', %d, %d)", $user_data['name'], $user_data['password'], $user_data['fio'], $user_data['phone'], $identity, $user_data['mail'], $role_id, time());
			$user_id = DB::query_result("SELECT id FROM {users} WHERE trash='0' AND act='1' AND mail='%s'", $u_user['email']);
			DB::query("INSERT INTO {ulogin} (user_id, identity, network) VALUES (%d, '%s', '%s')", $user_id, $u_user['identity'], $u_user['network']);
			//Аватар
			if(isset($u_user['photo']))
				$u_user['photo'] = $u_user['photo'] === "https://ulogin.ru/img/photo.png" ? '' : $u_user['photo'];
			if(isset($u_user['photo_big']))
				$u_user['photo_big'] = $u_user['photo_big'] === "https://ulogin.ru/img/photo_big.png" ? '' : $u_user['photo_big'];
			$photo = (isset($u_user['photo_big']) and !empty($u_user['photo_big'])) ? $u_user['photo_big'] : ((isset($u_user['photo']) and !empty($u_user['photo'])) ? $u_user['photo'] : '');
			$this->diafan->_users->create_avatar($user_data['name'], $photo);

			return $user_id;
		} else { // существует пользователь с таким email или это текущий пользователь
			if(!isset($u_user["verified_email"]) || intval($u_user["verified_email"]) != 1) {
				die('<script src="//ulogin.ru/js/ulogin.js"  type="text/javascript"></script><script type="text/javascript">uLogin.mergeAccounts("' . $_POST['token'] . '")</script>' . $this->diafan->_("Электронный адрес данного аккаунта совпадает с электронным адресом существующего пользователя. <br>Требуется подтверждение на владение указанным email.</br></br>") . $this->diafan->_("Подтверждение аккаунта") . "<br/><a href=" . $this->diafan->_route->current_link() . ">" . $this->diafan->_("Назад") . "</a>");
			}
			if(intval($u_user["verified_email"]) == 1) {
				$user_id = $isLoggedIn ? $current_user : $user_id;
				$other_u = DB::query_result("SELECT IDENTITY FROM {ulogin} WHERE user_id = %d", $user_id);
				if($other_u) {
					if(!$isLoggedIn && !isset($u_user['merge_account'])) {
						die($this->diafan->_("С данным аккаунтом уже связаны данные из другой социальной сети. <br>Требуется привязка новой учётной записи социальной сети к этому аккаунту.<br/>") . $this->diafan->_("Синхронизация аккаунтов") . '<script src="//ulogin.ru/js/ulogin.js"  type="text/javascript"></script><script type="text/javascript">uLogin.mergeAccounts("' . $_POST['token'] . '","' . $other_u . '")</script>' . "<br/><a href=" . $this->diafan->_route->current_link() . ">" . $this->diafan->_("Назад") . "</a>");
					}
				}
				DB::query("INSERT INTO {ulogin} (user_id, identity, network) VALUES (%d,'%s','%s')", $user_id, $u_user['identity'], $u_user['network']);

				return $user_id;
			}
		}

		return false;
	}

	/**
	 * Гнерация логина пользователя
	 * в случае успешного выполнения возвращает уникальный логин пользователя
	 * @param $first_name
	 * @param string $last_name
	 * @param string $nickname
	 * @param string $bdate
	 * @param array $delimiters
	 * @return string
	 */
	public function uloginGenerateNickname($first_name, $last_name = "", $nickname = "", $bdate = "", $delimiters = array (
			'.', '_'
		)) {
		$delim = array_shift($delimiters);
		$first_name = $this->uloginTranslitIt($first_name);
		$first_name_s = substr($first_name, 0, 1);
		$variants = array ();
		if(!empty($nickname))
			$variants[] = $nickname;
		$variants[] = $first_name;
		if(!empty($last_name)) {
			$last_name = $this->uloginTranslitIt($last_name);
			$variants[] = $first_name . $delim . $last_name;
			$variants[] = $last_name . $delim . $first_name;
			$variants[] = $first_name_s . $delim . $last_name;
			$variants[] = $first_name_s . $last_name;
			$variants[] = $last_name . $delim . $first_name_s;
			$variants[] = $last_name . $first_name_s;
		}
		$date = array ();
		if(!empty($bdate))
			$date = explode('.', $bdate);
		if(isset($date[0]) && isset($date[1]) && isset($date[2])) {
			$variants[] = $first_name . $date[2];
			$variants[] = $first_name . $date[2];
			$variants[] = $first_name . $delim . $date[2];
			$variants[] = $first_name . $date[0] . $date[1];
			$variants[] = $first_name . $delim . $date[0] . $date[1];
			$variants[] = $first_name . $delim . $last_name . $date[2];
			$variants[] = $first_name . $delim . $last_name . $delim . $date[2];
			$variants[] = $first_name . $delim . $last_name . $date[0] . $date[1];
			$variants[] = $first_name . $delim . $last_name . $delim . $date[0] . $date[1];
			$variants[] = $last_name . $delim . $first_name . $date[2];
			$variants[] = $last_name . $delim . $first_name . $delim . $date[2];
			$variants[] = $last_name . $delim . $first_name . $date[0] . $date[1];
			$variants[] = $last_name . $delim . $first_name . $delim . $date[0] . $date[1];
			$variants[] = $first_name_s . $delim . $last_name . $date[2];
			$variants[] = $first_name_s . $delim . $last_name . $delim . $date[2];
			$variants[] = $first_name_s . $delim . $last_name . $date[0] . $date[1];
			$variants[] = $first_name_s . $delim . $last_name . $delim . $date[0] . $date[1];
			$variants[] = $last_name . $delim . $first_name_s . $date[2];
			$variants[] = $last_name . $delim . $first_name_s . $delim . $date[2];
			$variants[] = $last_name . $delim . $first_name_s . $date[0] . $date[1];
			$variants[] = $last_name . $delim . $first_name_s . $delim . $date[0] . $date[1];
			$variants[] = $first_name_s . $last_name . $date[2];
			$variants[] = $first_name_s . $last_name . $delim . $date[2];
			$variants[] = $first_name_s . $last_name . $date[0] . $date[1];
			$variants[] = $first_name_s . $last_name . $delim . $date[0] . $date[1];
			$variants[] = $last_name . $first_name_s . $date[2];
			$variants[] = $last_name . $first_name_s . $delim . $date[2];
			$variants[] = $last_name . $first_name_s . $date[0] . $date[1];
			$variants[] = $last_name . $first_name_s . $delim . $date[0] . $date[1];
		}
		$i = 0;
		$exist = true;
		while(true) {
			if($exist = $this->uloginUserExist($variants[$i])) {
				foreach($delimiters as $del) {
					$replaced = str_replace($delim, $del, $variants[$i]);
					if($replaced !== $variants[$i]) {
						$variants[$i] = $replaced;
						if(!$exist = $this->uloginUserExist($variants[$i]))
							break;
					}
				}
			}
			if($i >= count($variants) - 1 || !$exist)
				break;
			$i++;
		}
		if($exist) {
			while($exist) {
				$nickname = $first_name . mt_rand(1, 100000);
				$exist = $this->uloginUserExist($nickname);
			}

			return $nickname;
		} else
			return $variants[$i];
	}

	/**
	 * Проверка существует ли пользователь с заданным логином
	 */
	public function uloginUserExist($login) {
		$user_data = DB::query_result("SELECT id FROM {users} WHERE name = '%s'", $login);
		if(!empty($user_data))
			return true;

		return false;
	}

	/**
	 * Транслит
	 */
	public function uloginTranslitIt($str) {
		$tr = array (
			"А" => "a", "Б" => "b", "В" => "v", "Г" => "g", "Д" => "d", "Е" => "e", "Ж" => "j", "З" => "z", "И" => "i",
			"Й" => "y", "К" => "k", "Л" => "l", "М" => "m", "Н" => "n", "О" => "o", "П" => "p", "Р" => "r", "С" => "s",
			"Т" => "t", "У" => "u", "Ф" => "f", "Х" => "h", "Ц" => "ts", "Ч" => "ch", "Ш" => "sh", "Щ" => "sch",
			"Ъ" => "", "Ы" => "yi", "Ь" => "", "Э" => "e", "Ю" => "yu", "Я" => "ya", "а" => "a", "б" => "b", "в" => "v",
			"г" => "g", "д" => "d", "е" => "e", "ж" => "j", "з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l",
			"м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r", "с" => "s", "т" => "t", "у" => "u", "ф" => "f",
			"х" => "h", "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "sch", "ъ" => "y", "ы" => "y", "ь" => "",
			"э" => "e", "ю" => "yu", "я" => "ya"
		);
		if(preg_match('/[^A-Za-z0-9\_\-]/', $str)) {
			$str = strtr($str, $tr);
			$str = preg_replace('/[^A-Za-z0-9\_\-\.]/', '', $str);
		}

		return $str;
	}

	public function uloginLoginUser($user_id) {
		$result = DB::query("SELECT * FROM {users} WHERE trash='0' AND act='1' AND id='%s'", $user_id);
		$user = DB::fetch_object($result);
		DB::free_result($result);
		$this->diafan->_users->set($user);
		$this->diafan->redirect($this->diafan->_route->current_link());
	}

	/**
	 * Проверка, есть ли пользователь с указанным id в базе
	 * @param $u_id
	 * @return bool
	 */
	public function uloginCheckUserId($user_id = 0) {
		$user_id = DB::query_result("SELECT * FROM {users} WHERE trash='0' AND act='1' AND id=%d", $user_id);
		$current_user = isset($this->diafan->_users->id) ? $this->diafan->_users->id : 0;
		if(($current_user > 0) && ($user_id > 0) && ($current_user != $user_id)) {
			throw new Exception($this->diafan->_('Данный аккаунт привязан к другому пользователю. Вы не можете использовать этот аккаунт.'));
		}

		return true;
	}
}