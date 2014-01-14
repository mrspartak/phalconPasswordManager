<?php

class Data extends \Phalcon\Mvc\Model
{

    /**
     * @return \Phalcon\Mvc\Model|string
     */
    public function setSource()
    {
        return 'data';
    }

    public function initialize()
    {
        $this->skipAttributes(array('id', 'creation_date'));
    }
}
