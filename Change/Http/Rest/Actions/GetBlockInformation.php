<?php
namespace Change\Http\Rest\Actions;

use Change\Http\Rest\Result\BlockResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\GetBlockInformation
 */
class GetBlockInformation
{
	/**
	 * Use Required Event Params: documentId, modelName
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$blockName = $event->getParam('blockName');
		if ($blockName)
		{
			$this->generateResult($event, $blockName);
		}
		return;
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param string $blockName
	 */
	protected function generateResult($event, $blockName)
	{
		$bm = $event->getApplicationServices()->getBlockManager();
		$info = $bm->getBlockInformation($blockName);
		if ($info)
		{
			$result = new BlockResult($event->getUrlManager(), $info);
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$event->setResult($result);
		}
	}
}
