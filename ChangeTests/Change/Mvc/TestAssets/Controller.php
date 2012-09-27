<?php
namespace Tests\Change\Mvc\TestAssets;

class Controller extends \Change\Mvc\Controller
{
	protected function loadContext()
	{
		$this->context = new \Change\Mvc\Context($this);
	}
}