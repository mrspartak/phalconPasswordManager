<?
$app->get('/', function () use ($app) {
	$message = $app->request->get('message');
	$data = Data::find('user_id = '.$app->session->get('user_id'));
	
	echo $app['view']->render('index/index', array('data' => $data, 'token' => $app->security->getSessionToken(), 'message' => $message));
});


$app->get('/login', function () use ($app) {	
	$message = $app->request->get('message');
	$error = $app->request->get('error');
	echo $app['view']->render('login/login', array('error' => $error, 'message' => $message)); 
})->setName('login');


$app->post('/login', function () use ($app) {	
	$user_nick = $app->request->getPost("user_nick");
	$user_password = $app->request->getPost("user_password");
	
	$token = $app->request->getPost("token");
	
	if($token) {
		$s = file_get_contents('http://ulogin.ru/token.php?token=' . $token . '&host=' . $_SERVER['HTTP_HOST']);
		$user = json_decode($s, true);
		
		$nick = Users::sha1rounds($app->config->app->static_salt . $user['identity']);
		$pass = Users::sha1rounds($app->config->app->static_salt . $user['identity'] . $user['network']);
	} elseif($user_nick && $user_password) {
		
		$resp = recaptcha_check_answer (RECAPTCHA_PRIVATE,  $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
		if (!$resp->is_valid) {
			return $app->response->redirect("login?message=error_recaptcha")->sendHeaders();
		}
		
		$nick = Users::sha1rounds($app->config->app->static_salt . $user_nick);
		$pass = $user_password;
	} else {
		return $app->response->redirect("error/500")->sendHeaders();
	}
	
	$user = Users::findFirst(array(
		"conditions" => "user_nick = ?1",
		"bind"       => array(1 => $nick)
	));
	
	if($user->id == null) {
		$user = new Users();
		$salt = Users::generate_salt();
		$data = array(
			'user_nick' => $nick,
			'user_password' => Users::sha1rounds($app->config->app->static_salt . $pass . $salt),
			'password_salt' => $salt,
			'openid' => ($token) ? 1 : 0
		);
		$user->save($data);
		$user->login($user);
	} else {
		if(Users::sha1rounds($app->config->app->static_salt . $pass . $user->password_salt) == $user->user_password) {
			$user->login($user);
		} else {
			return $app->response->redirect("login?message=error_wrong_credentials")->sendHeaders();
		}

	}
	 
})->setName('login');


$app->get('/logout', function () use ($app) {
	$app->session->destroy();
	$app->response->redirect("login")->sendHeaders();
})->setName('login');


$app->post('/addData', function () use ($app) {
	if($app->security->checkToken('token') === false) {$app->response->redirect("?message=token_error")->sendHeaders();exit();}
	
	$text_open = $app->request->getPost('text_open');
	$text_closed = $app->request->getPost('text_closed');
	$text_secret_key = $app->request->getPost('text_secret_key');
	
	if(!$text_open || !$text_closed || !$text_secret_key)
		 {$app->response->redirect("?message=empty_fields")->sendHeaders();exit();}
	
	$data['user_id'] = $app->session->get('user_id');
	$data['text_open'] = $text_open;
	$data['text_closed'] = $app->crypt->encryptBase64($text_closed, $text_secret_key);
	$data['salt'] = Users::generate_salt();
	$data['text_secret_key'] = Users::sha1rounds($app->config->app->static_salt . $text_secret_key . $data['salt']);
	
	$d = new Data();
	$d->save($data);
	
	$app->response->redirect("?message=data_add_success")->sendHeaders();
	
});


$app->post('/ajax', function() use ($app){
	if(!$app->request->isAjax()) {$app->response->setStatusCode(404, "Not Found")->sendHeaders();exit();}
	if($app->security->checkToken('token') === false) {$app->response->setStatusCode(501, "Token error")->sendHeaders();exit();}
	
	$action = $app->request->getPost('action');
	$data = $app->request->getPost('data');
	$filter = new \Phalcon\Filter();
	
	switch($action) {
		case 'get_secret':
			if(!$data['id']) {$app->response->setStatusCode(500, "No data given")->sendHeaders();exit();}
			
			$row = Data::findFirst( (int) $data['id'] );
			if($row == null) {$app->response->setStatusCode(500, "Data error")->sendHeaders();exit();}
			if($row->user_id != $app->session->get('user_id')) {$app->response->setStatusCode(500, "Data error 1")->sendHeaders();exit();}
			
			if(Users::sha1rounds($app->config->app->static_salt . $data['secret_key'] . $row->salt) == $row->text_secret_key) {
				$response['text_secret'] = $app->crypt->decryptBase64($row->text_closed, $data['secret_key']);
				$response['text_secret'] = iconv('cp1251', 'utf-8', $response['text_secret']);
			} else {
				{$app->response->setStatusCode(500, "Wrong credentials")->sendHeaders();exit();}
			}
		break;
		
		case 'delete_row':
			if(!$data['id']) {$app->response->setStatusCode(500, "No data given")->sendHeaders();exit();}
			$row = Data::findFirst( (int) $data['id'] );
			if($row == null) {$app->response->setStatusCode(500, "Data error")->sendHeaders();exit();}
			if($row->user_id != $app->session->get('user_id')) {$app->response->setStatusCode(500, "Data error")->sendHeaders();exit();}
			
			if(Users::sha1rounds($app->config->app->static_salt . $data['secret_key'] . $row->salt) == $row->text_secret_key) {
				if ($row->delete() == false) {
					$app->response->setStatusCode(500, "Error while deleting")->sendHeaders();
				} else {
					$response['status'] = 'ok';
				}
			} else {
				{$app->response->setStatusCode(500, "Wrong credentials")->sendHeaders();exit();}
			}
		break;
		
		default:
		$app->response->setStatusCode(404, "Not Found")->sendHeaders();
	}
	
	$app->response->setContentType('application/json', 'UTF-8')
		->setContent(json_encode($response))
		->send();
});


$app->notFound(function () use ($app) {
	$app->response->setStatusCode(500, "Error")->sendHeaders();
	echo $app['view']->render('errors/404', array());
});