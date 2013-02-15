<?php
namespace Change\Http\Rest;

/**
 * @name \Change\Http\Rest\Resolver
 */
class Resolver extends \Change\Http\ActionResolver
{
	/**
	 * @param \Change\Http\Event $event
	 * @return void
	 */
	public function resolve(\Change\Http\Event $event)
	{
		$request = $event->getRequest();
		$pathParts = explode('/', $request->getPath());
		switch ($pathParts[1])
		{
			case 'resources' :
				$this->resources($event, array_slice($pathParts, 2), $request->getMethod());
				break;
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param array $resourceParts
	 * @param $method
	 * @return void
	 */
	protected function resources($event, $resourceParts, $method)
	{
		if (count($resourceParts) >= 3)
		{
			$modelName = $resourceParts[0] . '_' . $resourceParts[1] . '_' . $resourceParts[2];
			$event->setParam('modelName', $modelName);
			$documentServices = $event->getDocumentServices();
			$model = $documentServices->getModelManager()->getModelByName($modelName);
			if ($model instanceof \Change\Documents\AbstractModel)
			{
				if (isset($resourceParts[3]) && is_numeric($resourceParts[3]))
				{
					$event->setParam('documentId', intval($resourceParts[3]));
					if (!isset($resourceParts[4]) || $event->getApplicationServices()->getI18nManager()->isSupportedLCID($resourceParts[4]))
					{
						if (isset($resourceParts[4]))
						{
							$event->setParam('LCID', $resourceParts[4]);
						}
						$action = new \Change\Http\Rest\Actions\GetDocument();
						$event->setAction(array($action, 'execute'));
					}
				}
			}
		}
	}
}