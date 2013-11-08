<?php
namespace Rbs\Commerce\Events\Http\Rest;

use Change\Http\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\Http\Rest\Listeners
 */
class Listeners implements ListenerAggregateInterface
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
		$callback = function (\Change\Events\Event $event)
		{
			$controller = $event->getTarget();
			if ($controller instanceof \Change\Http\Rest\Controller)
			{
				$resolver = $controller->getActionResolver();
				if ($resolver instanceof \Change\Http\Rest\Resolver)
				{
					$resolver->addResolverClasses('commerce', '\Rbs\Commerce\Http\Rest\CommerceResolver');
				}
			}
		};
		$events->attach('registerServices', $callback, 1);

		$events->attach(Event::EVENT_ACTION, array($this, 'registerActions'));
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

	/**
	 * @param Event $event
	 */
	public function registerActions(Event $event)
	{
		if (!$event->getAction())
		{
			$relativePath = $event->getParam('pathInfo');
			if ($relativePath === 'rbs/price/taxInfo')
			{
				$event->setAction(function ($event)
				{
					(new \Rbs\Price\Http\Rest\Actions\TaxInfo())->execute($event);
				});
			}
			else if (preg_match('#^resources/Rbs/Catalog/ProductListItem/([0-9]+)/(highlight|downplay|moveup|movedown|highlighttop|highlightbottom)$#',
				$relativePath, $matches)
			)
			{
				$event->getController()->getActionResolver()
					->setAuthorization($event, 'Consumer', null, 'Rbs_Catalog_ProductListItem');
				$event->setParam('documentId', intval($matches[1]));
				$methodName = $matches[2];
				$event->setAction(function ($event) use ($methodName)
				{
					$cr = new \Rbs\Catalog\Http\Rest\ProductListItemResult();
					call_user_func(array($cr, $methodName), $event);
				});
			}
			else if (preg_match('#^resources/Rbs/Catalog/(ProductList|SectionProductList|CrossSellingProductList|Product)/([0-9]+)/ProductListItems/?$#',
				$relativePath, $matches)
			)
			{
				$event->getController()->getActionResolver()
					->setAuthorization($event, 'Consumer', null, 'Rbs_Catalog_ProductListItem');
				$event->setParam('documentId', intval($matches[2]));
				$event->setAction(function ($event)
				{
					$cr = new \Rbs\Catalog\Http\Rest\ProductListItemResult();
					$cr->productListItemCollection($event);
				});
			}
			else if (preg_match('#^resources/Rbs/Catalog/Product/([0-9]+)/Prices/?$#', $relativePath, $matches))
			{
				$event->getController()->getActionResolver()->setAuthorization($event, 'Consumer', null, 'Rbs_Price_Price');
				$event->setParam('documentId', intval($matches[1]));
				$event->setAction(function ($event)
				{
					$cr = new \Rbs\Catalog\Http\Rest\PriceResult();
					$cr->productPriceCollection($event);
				});
			}
			else if (preg_match('#^resources/Rbs/Catalog/VariantGroup/([0-9]+)/Products/?$#', $relativePath,
				$matches)
			)
			{
				$event->setParam('documentId', intval($matches[1]));
				$event->setAction(function ($event)
				{
					(new \Rbs\Catalog\Http\Rest\VariantGroup())->getProducts($event);
				});
			}
			else if ($relativePath === 'rbs/catalog/productlistitem/delete')
			{
				$event->getController()->getActionResolver()->setAuthorization($event, 'CategoryManager');
				$event->setAction(function ($event)
				{
					$cr = new \Rbs\Catalog\Http\Rest\ProductListItemResult();
					$cr->delete($event);
				});
			}
			else if ($relativePath === 'rbs/catalog/productlistitem/addproducts')
			{
				$event->getController()->getActionResolver()->setAuthorization($event, 'CategoryManager');
				$event->setAction(function ($event)
				{
					$cr = new \Rbs\Catalog\Http\Rest\ProductListItemResult();
					$cr->addproducts($event);
				});
			}
			if ($relativePath === 'rbs/order/productPriceInfo')
			{
				$event->setAction(function ($event)
				{
					(new \Rbs\Order\Http\Rest\Actions\ProductPriceInfo())->execute($event);
				});
			}
		}
	}
}