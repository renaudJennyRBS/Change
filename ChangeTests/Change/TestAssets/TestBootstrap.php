<?php

namespace ChangeTests\Change\TestAssets;

class TestBootstrap
{
	public static function main(\Change\Application $app)
	{
		define('TESTBOOTSTRAP_OK', true);
	}
}