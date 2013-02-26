<?php
namespace ChangeTests\Change\Http;

use Change\Http\ActionResolver;

class ActionResolverTest extends \PHPUnit_Framework_TestCase
{

	public function testResolve()
	{
		$event = new \Change\Http\Event();
		$event->setAction('test');

		$resolver = new ActionResolver();

		$resolver->resolve($event);

		$this->assertNull($event->getAction());

	}
}
