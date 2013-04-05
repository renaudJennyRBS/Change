<?php
namespace Change\Http\Web\Actions;

use Change\Http\Result;
use Change\Http\Web\PathRule;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Actions\RedirectPathRule
 */
class RedirectPathRule
{
	/**
	 * Use Required Event Params: pathRule
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		/* @var $pathRule PathRule */
		$pathRule = $event->getParam('pathRule');
		if (!($pathRule instanceof PathRule))
		{
			throw new \RuntimeException('Invalid Parameter: pathRule', 71000);
		}
		$result = new Result();
		$result->setHttpStatusCode($pathRule->getHttpStatus());
		$result->setHeaderLocation($pathRule->getConfig('Location'));
		$event->setResult($result);
	}
}
