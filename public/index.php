<?
define('ROOTDIR', realpath( dirname(__FILE__) . '/../' ));

$config = new \Phalcon\Config\Adapter\Ini(ROOTDIR . '/app/config/config.ini');

define('STATIC_SALT', 'LJKejke8(lef');
define('RECAPTCHA_PUBLIC', $config->captcha->pub);
define('RECAPTCHA_PRIVATE', $config->captcha->priv);

$loader = new \Phalcon\Loader();
$loader->registerDirs(
	array(
		ROOTDIR . '/app/models/',
		ROOTDIR . '/app/vendor/'
	)
)->register();

require_once( ROOTDIR . '/app/vendor/recaptcha-php/recaptchalib.php' );
require_once( ROOTDIR . '/app/config/di.php' );

$app = new Phalcon\Mvc\Micro();

$app->setDI($di);

$app->url->setBaseUri($app->config->app->base_uri);

$app->before(function() use ($app) {
	$route = $app->router->getMatchedRoute()->getName();
	$not_restricted = array('login', 'error');
	if($app->session->has("logged_in") !== true && !in_array($route, $not_restricted)) 
	{
		return $app->response->redirect("login")->sendHeaders();
	} elseif ($route == 'login' && $app->session->has("logged_in")) {
		return $app->response->redirect("")->sendHeaders();
	}
	if($app->request->isSecureRequest() !== true) {
		return $app->response->redirect( 'htpps://'. $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'], true )->sendHeaders();
	}
});

require_once( ROOTDIR . '/app/config/routes.php' );

try {
	$app->handle();
}
catch(Exception $e) {
	if($app->config->app->debug == 0) 
	{
		$app->response->redirect("error")->sendHeaders();
	} else {
		echo get_class($e), ": ", $e->getMessage(), "<br>";
		echo " File=", $e->getFile(), "<br>";
		echo " Line=", $e->getLine(), "<br>";
		echo $e->getTraceAsString();
	}
}