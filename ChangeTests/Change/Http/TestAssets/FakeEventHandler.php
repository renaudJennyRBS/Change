<?php
namespace ChangeTests\Change\Http\TestAssets;

/**
 * @name \ChangeTests\Change\Http\TestAssets\FakeEventHandler
 */
class FakeEventHandler
{
	public $callNames = array();

	public $throwOn;

	public function setThrowOn($throwOn)
	{
		$this->throwOn = $throwOn;
		$this->callNames = array();
	}

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function onRequest($event)
	{
		$this->callNames[] = 'onRequest';
		if ($this->throwOn == 'onRequest')
		{
			throw new \RuntimeException('onRequest', 10000);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function onAction($event)
	{
		$this->callNames[] = 'onAction';
		$event->setAction(array($this, 'execute'));
		if ($this->throwOn == 'onAction')
		{
			throw new \RuntimeException('onAction', 10001);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function onResult($event)
	{
		$this->callNames[] = 'onResult';
		if ($this->throwOn == 'onResult')
		{
			throw new \RuntimeException('onResult', 10002);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function onResponse($event)
	{
		$this->callNames[] = 'onResponse';
		if ($this->throwOn == 'onResponse')
		{
			throw new \RuntimeException('onResponse', 10003);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function onException($event)
	{
		$code =  $event->getParam('Exception')->getCode();
		$this->callNames[] = 'onException(' . $code . ')';
		if ($this->throwOn == 'onException')
		{
			throw new \RuntimeException('onException', 10004);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$this->callNames[] = 'execute';
		if ($this->throwOn == 'execute')
		{
			throw new \RuntimeException('execute', 10005);
		}
	}
}