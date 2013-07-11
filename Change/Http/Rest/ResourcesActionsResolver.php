<?php
namespace Change\Http\Rest;

use Change\Http\Rest\Actions\DiscoverNameSpace;

/**
 * @name \Change\Http\Rest\ResourcesActionsResolver
 */
class ResourcesActionsResolver
{
	/**
	 * @param \Change\Http\Rest\Resolver $resolver
	 */
	protected $resolver;

	protected $resourceActionClasses = array();

	/**
	 * @param \Change\Http\Rest\Resolver $resolver
	 */
	function __construct(Resolver $resolver)
	{
		$this->resolver = $resolver;

		$this->resourceActionClasses = array(
			'startValidation' => '\Change\Http\Rest\Actions\StartValidation',
			'startPublication' => '\Change\Http\Rest\Actions\StartPublication',
			'deactivate' => '\Change\Http\Rest\Actions\Deactivate',
			'activate' => '\Change\Http\Rest\Actions\Activate',
			'getCorrection' => '\Change\Http\Rest\Actions\GetCorrection',
			'startCorrectionValidation' => '\Change\Http\Rest\Actions\StartCorrectionValidation',
			'startCorrectionPublication' => '\Change\Http\Rest\Actions\StartCorrectionPublication');
	}

	/**
	 * @return array
	 */
	public function getResourceActionClasses()
	{
		return $this->resourceActionClasses;
	}

	/**
	 * @param $actionName
	 * @param $class
	 */
	public function registerActionClass($actionName, $class)
	{
		$this->resourceActionClasses[$actionName] = $class;
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param string[] $namespaceParts
	 * @return string[]
	 */
	public function getNextNamespace($event, $namespaceParts)
	{
		return array_keys($this->resourceActionClasses);
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
		if ($nbParts == 0 && $method === Request::METHOD_GET)
		{
			array_unshift($resourceParts, 'resourcesactions');
			$event->setParam('namespace', implode('.', $resourceParts));
			$event->setParam('resolver', $this);
			$action = function ($event)
			{
				$action = new DiscoverNameSpace();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif ($nbParts == 2 || $nbParts == 3)
		{
			$resourceActionClasses = $this->getResourceActionClasses();
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
			$this->resolver->setAuthorisation($event, 'Actions', $document->getId(), $document->getDocumentModelName() . '.' . $actionName);
			$event->setAction(function($event) use($instance) {$instance->execute($event);});
			return;
		}
	}
}