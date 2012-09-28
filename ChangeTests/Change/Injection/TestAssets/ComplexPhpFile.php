<?php

namespace Alpha{

interface InterfaceA
{
	public function A();
}

interface ExtendingInterfaceA extends InterfaceA
{
	public function AA();
}
}
namespace Beta{
interface InterfaceB
{
	public function B();
}

class C 
{
	public function C()
	{
		return "I am C";
	}	
}
}
namespace Gamma{

use Alpha\InterfaceA;
use Beta\C as MyC;

/**
 * Class Comment
 */
abstract class Testable implements InterfaceA, \Beta\InterfaceB
{
	const TEST = "A Testable";
	
	public function A()
	{ 
		$tutu = "tutu";
		call_user_func(function($toto) use ($tutu)
		{
			return "titi";
		});
		return "I implement Alpha InterfaceA";	
	}
	
	public static function B()
	{
		return "I implement Beta InterfaceB";	
	}
}

class C extends MyC
{
	
}
}
namespace Gamma\Zeta{
use \Alpha\InterfaceA as Toto;

class Tested extends \Gamma\Testable
{
	public function test()
	{
		return static::TEST;
	}
}
}