<?php
namespace Change\Http\Rest;

use Change\Http\Rest\Actions\DiscoverNameSpace;

/**
 * @name \Change\Http\Rest\ActionsResolver
 */
class ActionsResolver
{
	const RESOLVER_NAME = 'actions';

	/**
	 * @param \Change\Http\Rest\Resolver $resolver
	 */
	protected $resolver;

	protected $actionClasses = array();

	/**
	 * @param \Change\Http\Rest\Resolver $resolver
	 */
	function __construct(Resolver $resolver)
	{
		$this->resolver = $resolver;
		$this->registerActionClass('collectionItems', '\Change\Http\Rest\Actions\GetCollectionItems');
		$this->registerActionClass('collectionCodes', '\Change\Http\Rest\Actions\GetCollectionCodes');
		$this->registerActionClass('renderRichText', '\Change\Http\Rest\Actions\RenderRichText');
		$this->registerActionClass('activate', '\Change\Http\Rest\Actions\Activation');
		$this->registerActionClass('deactivate', '\Change\Http\Rest\Actions\Deactivation');
	}

	/**
	 * @return array
	 */
	public function getActionClasses()
	{
		return $this->actionClasses;
	}

	/**
	 * @param $actionName
	 * @param $class
	 */
	public function registerActionClass($actionName, $class)
	{
		$this->actionClasses[$actionName] = $class;
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param string[] $namespaceParts
	 * @return string[]
	 */
	public function getNextNamespace($event, $namespaceParts)
	{
		return array_keys($this->actionClasses);
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
			array_unshift($resourceParts, static::RESOLVER_NAME);
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
		elseif ($nbParts == 1)
		{
			$actionClasses = $this->getActionClasses();
			$actionName = $resourceParts[0];
			if (!isset($actionClasses[$actionName]))
			{
				//Action not found
				return;
			}
			$actionClass = $actionClasses[$actionName];
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
			$event->setParam('actionName', $actionName);
			$event->setAction(function($event) use($instance) {$instance->execute($event);});
			return;
		}
	}
}