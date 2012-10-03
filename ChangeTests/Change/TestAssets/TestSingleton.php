<?php

namespace ChangeTests\Change\TestAssets;

class TestSingleton extends \Change\AbstractSingleton
{
	public $test = null;
	
	public static function reset()
	{
		static::clearInstanceByClassName(get_called_class());
	}
}
