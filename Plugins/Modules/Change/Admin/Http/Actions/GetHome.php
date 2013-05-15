<?php
namespace Change\Admin\Http\Actions;

use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Admin\Http\Actions\GetHome
 */
class GetHome
{
	/**
	 * Use Required Event Params:
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$result = new \Change\Admin\Http\Result\Home();
		$templateFileName = implode(DIRECTORY_SEPARATOR, array( __DIR__ , 'Assets', 'home.twig'));
		$attributes = array('baseURL' => $event->getUrlManager()->getByPathInfo('/')->normalize()->toString());

		$manager = new \Change\Admin\Manager($event->getApplicationServices(), $event->getDocumentServices());
		$attributes['resources'] = $manager->getResources();
		$renderer = function () use ($templateFileName, $manager, $attributes)
		{
			return $manager->renderTemplateFile($templateFileName, $attributes);
		};
		$result->setRenderer($renderer);
		$event->setResult($result);
	}
}