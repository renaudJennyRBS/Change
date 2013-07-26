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
class ModelsInfo
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

		$models = array();

		$i18n = $applicationServices->getI18nManager();
		$modelManager = $documentServices->getModelManager();
		foreach ($modelManager->getModelsNames() as $modelName)
		{
			$model = $modelManager->getModelByName($modelName);
			$models[] = array(
				'name' => $model->getName(),
				'label' => $i18n->trans($model->getLabelKey(), array('ucf')),
				'leaf' => ! $model->hasDescendants(),
				'root' => ! $model->hasParent(),
				'abstract' => $model->isAbstract(),
				'publishable' => $model->isPublishable(),
				'plugin' => $i18n->trans('m.' . $model->getVendorName() . '.' . $model->getShortModuleName() . '.admin.js.module-name', array('ucf'))
			);
		}

		$result->setArray($models);
		return $result;
	}
}