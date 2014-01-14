<?
$di = new \Phalcon\DI\FactoryDefault();

$di->setShared('config', function() use ($config){ return $config; });

$di->setShared('session', function() use ($config){
	session_set_cookie_params($config->app->session_lifetime);
	ini_set('session.cookie_secure', '1');
	ini_set('session.cookie_httponly', '1');
	$session = new Phalcon\Session\Adapter\Files();
	$session->start();
	return $session;
});

$di->set('response', 'Phalcon\Http\Response');

$di->set('view', function() use ($config) {
	$view = new Phalcon\Mvc\View\Simple();
	$view->setViewsDir(ROOTDIR . '/app/views/');
	$view->registerEngines(array(
		'.phtml' => function($view) use ($config) {
			$volt = new Phalcon\Mvc\View\Engine\Volt($view);
			$volt->setOptions(
				array(
					'compiledPath'      => ROOTDIR . '/tmp/volt/',
					'compiledExtension' => '.php',
					'compiledSeparator' => '_',
					'compileAlways' => true
				)
			);
			$compiler = $volt->getCompiler();

			$compiler->addFunction('recaptcha_get_html', function() use ($config) {
				return "'". recaptcha_get_html($config->captcha->pub, null, true). "'" ;
			});
			
			return $volt;
		}
	));
	return $view;
}, true);

$di->setShared('db', function() use ($config) {	
	$db = new \Phalcon\Db\Adapter\Pdo\Mysql(array(
		"host" => $config->db->host,
		"username" => $config->db->user,
		"password" => $config->db->password,
		"dbname" => $config->db->dbname,
		"charset" => $config->db->charset,
		"persistent" => $config->db->persistent
	));
	$db->execute('SET NAMES UTF8' , array());
	return $db;
});

$di->setShared('modelsMetadata', function() use ($config) {
	if($config->app->cache_apc) 
	{
		$metaData = new Phalcon\Mvc\Model\MetaData\Apc(
			array(
				"lifetime" => 3600,
				"prefix"   => $config->app->suffix . "-meta-db-main"
			)
		);
	} else {
		$metaData = new \Phalcon\Mvc\Model\Metadata\Files(array(
			'metaDataDir' => ROOTDIR . '/tmp/cache/'
		));
	}
	return $metaData;
});

$di->setShared('modelsManager', function(){
	return new Phalcon\Mvc\Model\Manager();
});

$di->set('crypt', function(){
	return new Phalcon\Crypt();
});

$di->set('security', function(){
	$security = new Phalcon\Security();
	$security->setWorkFactor(12);
	return $security;
}, true);