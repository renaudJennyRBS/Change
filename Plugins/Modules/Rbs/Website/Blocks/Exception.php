<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Website\Blocks\Exception
 */
class Exception extends Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getPresentationServices, getDocumentServices
	 * Optional Event method: getHttpRequest
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('message');
		$parameters->setLayoutParameters($event->getBlockLayout());
		$exception = $event->getParam('Exception');
		if ($exception instanceof \Exception)
		{
			$message = 'Exception (code ' . $exception->getCode() . ') : ' . $exception->getMessage();
			if ($parameters->getParameter('showStackTrace'))
			{
				$message .= PHP_EOL . $exception->getTraceAsString();
			}
			$parameters->setParameterValue('message', $message);
		}
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getBlockParameters(), getBlockResult(),
	 *        getPresentationServices(), getDocumentServices()
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		return 'exception.twig';
	}
}