<?php
namespace Rbs\Elasticsearch\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Elasticsearch\Http\Web\Suggest
*/
class Suggest extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		$datas = $event->getRequest()->getPost()->toArray();

		$indexManager = new \Rbs\Elasticsearch\Services\IndexManager();
		$indexManager->setDocumentServices($event->getDocumentServices());

		$indexDef = $indexManager->findIndexDefinitionByMapping('fulltext', 'fr_FR', array('website' => $event->getWebsite()));
		if ($indexDef)
		{
			$datas = array('name' => $indexDef->getName());
			$event->setResult($this->getNewAjaxResult($datas));
			return;
		}

		$event->setResult($this->getNewAjaxResult(array('fulltext', 'fr_FR')));
	}
}