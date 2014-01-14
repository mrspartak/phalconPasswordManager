<?php

class Data extends \Phalcon\Mvc\Model
{
	protected $_source = 'data';

	public function initialize()
	{
		$this->skipAttributes(array('id', 'creation_date'));
  }
}