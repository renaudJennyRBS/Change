<?php
namespace ChangeTests\Change\Http;

use Change\Http\BaseResolver;

class ActionResolverTest extends \PHPUnit_Framework_TestCase
{
	public function testResolve()
	{
		$event = new \Change\Http\Event();
		$event->setAction('test');

		$resolver = new BaseResolver();

		$resolver->resolve($event);

		$this->assertNull($event->getAction());
	}
}
