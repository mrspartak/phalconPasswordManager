<?php

class Users extends \Phalcon\Mvc\Model
{

    /**
     * @return array|string
     */
    public function columnMap()
    {
        return array(
            'id'            => 'id',
            'user_nick'     => 'user_nick',
            'user_password' => 'user_password',
            'password_salt' => 'password_salt',
            'openid'        => 'openid',
        );
    }

    /**
     * @return \Phalcon\Mvc\Model|string
     */
    public function setSource()
    {
        return 'users';
    }

    public function initialize()
    {
        $this->skipAttributes(array('id'));
    }

    /**
     * @return string
     */
    public static function generateSalt()
    {
        $slave = '1234567890!@#$%^&*()_+-=qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM[];,.';
        $length = strlen($slave);
        $salt = '';
        for ($i = 0; $i < 40; $i++) {
            $salt .= $slave[rand(0, $length)];
        }
        return $salt;
    }

    /**
     * @param $string
     *
     * @return string
     */
    public static function md5Rounds($string)
    {
        $config = Phalcon\DI::getDefault()->getConfig();

        for ($i = 0; $i < $config->app->hash_rounds; $i++) {
            $string = md5($config->app->static_salt . $string);
        }

        return $string;
    }

    /**
     * @param $string
     *
     * @return string
     */
    public static function sha1Rounds($string)
    {

        $config = Phalcon\DI::getDefault()->getConfig();

        for ($i = 0; $i < $config->app->hash_rounds; $i++) {
            $string = sha1($config->app->static_salt . $string);
        }

        return $string;
    }

    /**
     * @param $user
     */
    public function login($user)
    {
        $session = $this->getDI()->getSession();
        $session->set("logged_in", true);
        $session->set('user_id', $user->id);

        $security = $this->getDI()->getSecurity();
        $security->getToken();

        $response = $this->getDI()->getResponse();
        $response->redirect()->sendHeaders();
    }
}
