<?php
namespace Rbs\Catalog\Http\Rest;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Change\Http\Event;

/**
 * @name \Rbs\Catalog\Http\Rest\ListenerAggregate
 */
class ListenerAggregate implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function (Event $event)
		{
			$ar = $event->getController()->getActionResolver();
			if ($ar instanceof \Change\Http\Rest\Resolver)
			{
				$ar->addResolverClasses('catalog', '\Rbs\Catalog\Http\Rest\CatalogResolver');
			}
		};
		$events->attach(\Change\Http\Event::EVENT_REQUEST, $callback, 5);

		$callback = function (Event $event)
		{
			$result = $event->getResult();
			if ($result instanceof \Change\Http\Rest\Result\DocumentResult)
			{
				$modelName = $event->getParam('modelName');
				$model = $event->getDocumentServices()->getModelManager()->getModelByName($modelName);
				if ($modelName == 'Rbs_Catalog_Category' || in_array('Rbs_Catalog_Category', $model->getAncestorsNames()))
				{
					$cr = new \Rbs\Catalog\Http\Rest\CatalogResult();
					$cr->onCategoryResult($event);
				}
				elseif ($modelName == 'Rbs_Catalog_AbstractProduct' || in_array('Rbs_Catalog_AbstractProduct', $model->getAncestorsNames()))
				{
					$cr = new \Rbs\Catalog\Http\Rest\CatalogResult();
					$cr->onProductResult($event);
				}
			}
		};
		$events->attach(\Change\Http\Event::EVENT_RESULT, $callback, 1);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}