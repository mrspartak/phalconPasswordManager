<?php

define('ROOTDIR', realpath(dirname(__FILE__) . '/../'));

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

require_once(ROOTDIR . '/app/vendor/recaptcha-php/recaptchalib.php');
require_once(ROOTDIR . '/app/config/di.php');

$app = new Phalcon\Mvc\Micro($di);

$app->url->setBaseUri($app->config->app->base_uri);

$app->before(
    function () use ($app) {
        $route = $app->router->getMatchedRoute()->getName();
        $not_restricted = array('login', 'error');
        if ($app->session->has("logged_in") !== true && !in_array($route, $not_restricted)) {
            $app->response->redirect("login")->sendHeaders();
            return false;
        } elseif ($route == 'login' && $app->session->has("logged_in")) {
            $app->response->redirect()->sendHeaders();
            return false;
        }
        if ( $app->config->app->debug!=1 && $app->request->isSecureRequest() !== true) {
            $app->response->redirect($app->config->app->base_uri, true)->sendHeaders();
            return false;
        }
    }
);

require_once(ROOTDIR . '/app/config/routes.php');

try {
    $app->handle();
} catch (Exception $e) {
    if ($app->config->app->debug == 0) {
        $app->response->redirect("error")->sendHeaders();
    } else {
        $s = get_class($e) . ": " . $e->getMessage() . "<br>" . " File=" . $e->getFile() . "<br>" . " Line="
            . $e->getLine() . "<br>" . $e->getTraceAsString();

        $app->response->setContent($s)->send();
    }
}
