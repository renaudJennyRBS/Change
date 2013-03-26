<?php
namespace Change\Http\Rest;

/**
 * @name \Change\Http\Rest\ResourcesActionsResolver
 */
class ResourcesActionsResolver
{
	/**
	 * @param \Change\Http\Rest\Resolver $resolver
	 */
	protected $resolver;

	/**
	 * @param \Change\Http\Rest\Resolver $resolver
	 */
	function __construct(Resolver $resolver)
	{
		$this->resolver = $resolver;
	}

	/**
	 * Set Event params: resourcesActionName, documentId, LCID
	 * @param \Change\Http\Event $event
	 * @param array $resourceParts
	 * @param $method
	 * @return void
	 */
	public function resolve($event, $resourceParts, $method)
	{
		$nbParts = count($resourceParts);
		$resourceActionClasses = $this->resolver->getResourceActionClasses();
		if ($nbParts == 2 || $nbParts == 3)
		{
			$actionName = $resourceParts[0];
			if (!isset($resourceActionClasses[$actionName]))
			{
				//Action not found
				return;
			}
			$actionClass = $resourceActionClasses[$actionName];
			if (!class_exists($actionClass))
			{
				//Action Class not found
				return;
			}
			$instance = new $actionClass();
			if (!is_callable(array($instance, 'execute')))
			{
				//Callable Not found
				return;
			}

			$documentId = intval($resourceParts[1]);
			$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId);
			if ($document === null)
			{
				//Document not found
				return;
			}
			$event->setParam('documentId', $document->getId());

			$LCID = isset($resourceParts[2]) ? $resourceParts[2] : null;
			if ($LCID)
			{
				if (!$document->getDocumentModel()->isLocalized() || !$event->getApplicationServices()->getI18nManager()->isSupportedLCID($LCID))
				{
					//Invalid LCID
					return;
				}
				$event->setParam('LCID', $LCID);
			}
			else
			{
				if ($document->getDocumentModel()->isLocalized())
				{
					//Invalid LCID
					return;
				}
			}
			$event->setParam('resourcesActionName', $actionName);
			$this->resolver->setAuthorisation($event, $document->getId(), $document->getDocumentModelName() . '.' . $actionName);
			$event->setAction(function($event) use($instance) {$instance->execute($event);});
			return;
		}
	}
}