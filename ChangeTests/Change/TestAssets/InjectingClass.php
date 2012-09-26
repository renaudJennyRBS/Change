<?php
namespace ChangeTests\Change\TestAssets;

class InjectingClass extends OriginalClass
{
	public function test()
	{
		return 'InjectingClass';
	}
}