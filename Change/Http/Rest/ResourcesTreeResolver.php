<?php
namespace Change\Http\Rest;

use Change\Http\Rest\Actions\CreateTreeNode;
use Change\Http\Rest\Actions\DeleteTreeNode;
use Change\Http\Rest\Actions\DiscoverNameSpace;
use Change\Http\Rest\Actions\GetTreeNode;
use Change\Http\Rest\Actions\GetTreeNodeCollection;
use Change\Http\Rest\Actions\GetTreeNodeAncestors;
use Change\Http\Rest\Actions\UpdateTreeNode;

/**
 * @name \Change\Http\Rest\ResourcesTreeResolver
 */
class ResourcesTreeResolver
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
	 * @param \Change\Http\Event $event
	 * @param string[] $namespaceParts
	 * @return string[]
	 */
	public function getNextNamespace($event, $namespaceParts)
	{
		if (!isset($namespaceParts[1]))
		{
			$treeNames = $event->getApplicationServices()->getTreeManager()->getTreeNames();
			$vendors = array();
			foreach ($treeNames as $treeName)
			{
				list($vendor, ) = explode('_', $treeName);
				$vendors[$vendor] = true;
			}
			return array_keys($vendors);
		}
		elseif (!isset($namespaceParts[2]))
		{
			$vendor = $namespaceParts[1];
			$treeNames = $event->getApplicationServices()->getTreeManager()->getTreeNames();
			$shortModulesNames = array();
			foreach ($treeNames as $treeName)
			{
				list($vendorTree, $shortModuleName) = explode('_', $treeName);
				if ($vendorTree === $vendor)
				{
					$shortModulesNames[$shortModuleName] = true;
				}
			}
			return array_keys($shortModulesNames);
		}
		return array();
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param array $resourceParts
	 * @param $method
	 * @return void
	 */
	public function resolve($event, $resourceParts, $method)
	{
		if (count($resourceParts) < 2 && $method === Request::METHOD_GET)
		{
			array_unshift($resourceParts, 'resourcestree');
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
		elseif (count($resourceParts) >= 2)
		{
			$vendor = array_shift($resourceParts);
			$shortModuleName = array_shift($resourceParts);
			$treeName = $vendor . '_' . $shortModuleName;
			$applicationServices = $event->getApplicationServices();
			if ($applicationServices->getTreeManager()->hasTreeName($treeName))
			{
				$event->setParam('treeName', $treeName);
				$pathIds = array();

				while(($nodeId = array_shift($resourceParts)) !== null)
				{
					if (is_numeric($nodeId))
					{
						$pathIds[] = intval($nodeId);
					}
					elseif ($nodeId == 'ancestors' && $event->getParam('isDirectory') && count($resourceParts) === 0)
					{
						$event->setParam('pathIds', $pathIds);
						$this->resolver->setAuthorization($event, 'Consumer', end($pathIds), $treeName);
						$action = function($event) {
							$action = new GetTreeNodeAncestors();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}
					else
					{
						//Invalid TreeNode Ids Path
						return;
					}

				}
				$event->setParam('pathIds', $pathIds);
				$resource = end($pathIds);
				if (!$resource) {$resource = $treeName;}

				if ($event->getParam('isDirectory', false))
				{
					if ($method === Request::METHOD_POST)
					{
						$this->resolver->setAuthorization($event, 'Creator', is_numeric($resource) ? $resource : null, $treeName);
						$action = function($event) {
							$action = new CreateTreeNode();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}
					elseif ($method === Request::METHOD_GET)
					{
						$this->resolver->setAuthorization($event, 'Consumer', is_numeric($resource) ? $resource : null, $treeName);
						$action = function($event) {
							$action = new GetTreeNodeCollection();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}

					$result = $event->getController()->notAllowedError($method, array(Request::METHOD_GET, Request::METHOD_POST));
					$event->setResult($result);
					return;
				}
				elseif (count($pathIds))
				{
					if ($method === Request::METHOD_GET)
					{
						$this->resolver->setAuthorization($event, 'Consumer', $resource, $treeName);
						$action = function($event) {
							$action = new GetTreeNode();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}
					elseif ($method === Request::METHOD_PUT)
					{
						$this->resolver->setAuthorization($event, 'Creator', $resource, $treeName);
						$action = function($event) {
							$action = new UpdateTreeNode();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}
					elseif ($method === Request::METHOD_DELETE)
					{
						$this->resolver->setAuthorization($event, 'Creator', $resource, $treeName);
						$action = function($event) {
							$action = new DeleteTreeNode();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}
					$result = $event->getController()->notAllowedError($method, array(Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE));
					$event->setResult($result);
					return;
				}
			}
		}
	}
}