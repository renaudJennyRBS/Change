<?php
namespace Change\Http\Rest\Actions;

use Change\Http\Rest\Result\ModelLink;
use Change\Http\Rest\Result\CollectionResult;
use Change\Http\Rest\Result\Link;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\GetModelCollection
 */
class GetModelCollection
{
	/**
	 * Use Event Params: vendor, shortModuleName
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$vendor = $event->getParam('vendor');
		$shortModuleName = $event->getParam('shortModuleName');
		if ($vendor && $shortModuleName)
		{
			$this->generateResult($event, $vendor, $shortModuleName);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param string $vendor
	 * @param string $shortModuleName
	 */
	protected function generateResult($event, $vendor, $shortModuleName)
	{
		$mm = $event->getApplicationServices()->getModelManager();
		$shortModelNames = array();
		foreach ($mm->getModelsNames() as $name)
		{
			$a = explode('_', $name);
			if ($a[0] === $vendor && $a[1] === $shortModuleName)
			{
				$shortModelNames[] = $name;
			}
		}

		if (!count($shortModelNames))
		{
			return;
		}

		$urlManager = $event->getUrlManager();
		$result = new CollectionResult();
		$result->setOffset(0);
		$basePath = $event->getRequest()->getPath();
		$selfLink = new Link($urlManager, $basePath);
		$result->addLink($selfLink);
		$result->setCount(count($shortModelNames));
		$result->setSort(null);

		foreach ($shortModelNames as $name)
		{
			$model = $mm->getModelByName($name);
			$infos = array('name' => $model->getName(),
				'label' => $event->getApplicationServices()->getI18nManager()->trans($model->getLabelKey(), array('ucf')));
			$l = new ModelLink($urlManager, $infos);
			$result->addResource($l);
		}

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$event->setResult($result);
	}
}
