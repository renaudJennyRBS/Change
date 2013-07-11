<?php
namespace Rbs\Workflow\Tasks\PublicationProcess;

use Zend\EventManager\Event;
/**
* @name \Rbs\Workflow\Tasks\PublicationProcess\CheckPublication
*/
class CheckPublication
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		/* @var $workItem \Change\Workflow\Interfaces\WorkItem */
		$workItem = $event->getParam('workItem');

		/* @var $documentServices \Change\Documents\DocumentServices */
		$documentServices = $event->getParam('documentServices');
		$ctx = $workItem->getContext();

		//TODO
		$documentServices->getApplicationServices()->getLogging()->info(__METHOD__);
		$deadLine = new \DateTime();
		$ctx['fileDeadLine'] = $deadLine->add(new \DateInterval('PT5M'))->format(\DateTime::ISO8601);
	}
}