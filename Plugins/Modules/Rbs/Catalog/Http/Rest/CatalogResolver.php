<?php
namespace Rbs\Catalog\Http\Rest;

use Change\Http\Event;
use Change\Http\Request;
use Change\Http\Rest\Actions\DiscoverNameSpace;

/**
 * @name \Rbs\Catalog\Http\Rest\CatalogResolver
 */
class CatalogResolver
{
	/**
	 * @param \Change\Http\Rest\Resolver $resolver
	 */
	protected $resolver;

	/**
	 * @param \Change\Http\Rest\Resolver
	 */
	public function __construct(\Change\Http\Rest\Resolver $resolver)
	{
		$this->resolver = $resolver;
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param string[] $namespaceParts
	 * @return string[]
	 */
	public function getNextNamespace($event, $namespaceParts)
	{
		return array('category', 'product');
	}

	/**
	 * Set event Params: modelName, documentId, LCID
	 * @param Event $event
	 * @param array $resourceParts
	 * @param $method
	 * @return void
	 */
	public function resolve($event, $resourceParts, $method)
	{
		if (count($resourceParts) == 0)
		{
			if ($method !== Request::METHOD_GET)
			{
				$result = $event->getController()->notAllowedError($method, array(Request::METHOD_GET));
				$event->setResult($result);
				return;
			}
			array_unshift($resourceParts, 'catalog');
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
		elseif (count($resourceParts) == 3)
		{
			if ($resourceParts[0] === 'category' && is_numeric($resourceParts[1]) && $resourceParts[2] === 'products')
			{
				// TODO list conditions
			}
			elseif ($resourceParts[0] === 'product' && is_numeric($resourceParts[1]) && $resourceParts[2] === 'categories')
			{
				// TODO list conditions
			}
		}
		elseif (count($resourceParts) == 4)
		{
			if ($resourceParts[0] === 'category' && is_numeric($resourceParts[1]) &&
				$resourceParts[2] === 'products' && is_numeric($resourceParts[3]))
			{
				$event->setParam('categoryId', intval($resourceParts[1]));
				$event->setParam('conditionId', intval($resourceParts[3]));
				switch ($method)
				{
					case Request::METHOD_GET:
						$action = function ($event)
						{
							$action = new \Rbs\Catalog\Http\Rest\Actions\GetCategoryProducts();
							$action->execute($event);
						};
						$event->setAction($action);
						break;

					case Request::METHOD_DELETE:
						$action = function ($event)
						{
							$action = new \Rbs\Catalog\Http\Rest\Actions\DeleteCategoryProducts();
							$action->execute($event);
						};
						$event->setAction($action);
						break;

					case Request::METHOD_POST:
						$event->setParam('productIds', $event->getRequest()->getPost()->get("productIds"));
						$event->setParam('priorities', $event->getRequest()->getPost()->get("priorities"));
						$action = function ($event)
						{
							$action = new \Rbs\Catalog\Http\Rest\Actions\SetCategoryProducts();
							$action->execute($event);
						};
						$event->setAction($action);
						break;

					case Request::METHOD_PUT:
						$event->setParam('addProductIds', $event->getRequest()->getPost()->get("addProductIds"));
						$event->setParam('priorities', $event->getRequest()->getPost()->get("priorities"));
						$event->setParam('removeProductIds', $event->getRequest()->getPost()->get("removeProductIds"));
						$action = function ($event)
						{
							$action = new \Rbs\Catalog\Http\Rest\Actions\UpdateCategoryProducts();
							$action->execute($event);
						};
						$event->setAction($action);
						break;

					default:
						$result = $event->getController()->notAllowedError($method,
							array(Request::METHOD_GET, Request::METHOD_DELETE, Request::METHOD_POST, Request::METHOD_PUT));
						$event->setResult($result);
						break;
				}
			}
			elseif ($resourceParts[0] === 'product' && is_numeric($resourceParts[1]) &&
				$resourceParts[2] === 'categories' && is_numeric($resourceParts[3]))
			{
				$event->setParam('productId', intval($resourceParts[1]));
				$event->setParam('conditionId', intval($resourceParts[3]));
				switch ($method)
				{
					case Request::METHOD_GET:
						$action = function ($event)
						{
							$action = new \Rbs\Catalog\Http\Rest\Actions\GetProductCategories();
							$action->execute($event);
						};
						$event->setAction($action);
						break;

					case Request::METHOD_DELETE:
						$action = function ($event)
						{
							$action = new \Rbs\Catalog\Http\Rest\Actions\DeleteProductCategories();
							$action->execute($event);
						};
						$event->setAction($action);
						break;

					case Request::METHOD_POST:
						$event->setParam('categoryIds', $event->getRequest()->getPost()->get("categoryIds"));
						$event->setParam('priorities', $event->getRequest()->getPost()->get("priorities"));
						$action = function ($event)
						{
							$action = new \Rbs\Catalog\Http\Rest\Actions\SetProductCategories();
							$action->execute($event);
						};
						$event->setAction($action);
						break;

					case Request::METHOD_PUT:
						$event->setParam('addCategoryIds', $event->getRequest()->getPost()->get("addCategoryIds"));
						$event->setParam('priorities', $event->getRequest()->getPost()->get("priorities"));
						$event->setParam('removeCategoryIds', $event->getRequest()->getPost()->get("removeCategoryIds"));
						$action = function ($event)
						{
							$action = new \Rbs\Catalog\Http\Rest\Actions\UpdateProductCategories();
							$action->execute($event);
						};
						$event->setAction($action);
						break;

					default:
						$result = $event->getController()->notAllowedError($method,
							array(Request::METHOD_GET, Request::METHOD_DELETE, Request::METHOD_POST, Request::METHOD_PUT));
						$event->setResult($result);
						break;
				}
			}
			return;
		}
	}
}