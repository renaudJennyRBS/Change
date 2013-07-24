<?php
namespace Rbs\Admin\Http\Rest\Actions;

use Change\Documents\Query\Query;
use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
 * Returns the list of all the functions declared in the blocks.
 *
 * @name \Rbs\Admin\Http\Rest\Actions\PublishableModels
 */
class PublishableModels
{
	/**
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute(Event $event)
	{
		$request = $event->getRequest();
		$billingArea = null;
		if ($request->isGet())
		{
			$event->setResult($this->generateResult($event->getDocumentServices(), $event->getApplicationServices()));
		}
	}


	/**
	 * @param $documentServices \Change\Documents\DocumentServices
	 * @param $applicationServices \Change\Application\ApplicationServices
	 * @return \Change\Http\Rest\Result\ArrayResult
	 */
	protected function generateResult($documentServices, $applicationServices)
	{
		$result = new \Change\Http\Rest\Result\ArrayResult();

		$publishableModels = array();

		$modelManager = $documentServices->getModelManager();
		foreach ($modelManager->getModelsNames() as $modelName)
		{
			$model = $modelManager->getModelByName($modelName);
			if ($model->isPublishable())
			{
				$publishableModels[] = array(
					'name'  => $model->getName(),
					'label' => $applicationServices->getI18nManager()->trans($model->getLabelKey(), array('ucf'))
				);
			}
		}

		$result->setArray($publishableModels);
		return $result;
	}
}