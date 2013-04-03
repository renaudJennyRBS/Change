<?php
namespace Change\Http\Rest;

use Change\Http\Rest\Actions\CreateTreeNode;
use Change\Http\Rest\Actions\DeleteTreeNode;
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
	 * @param array $resourceParts
	 * @param $method
	 * @return void
	 */
	public function resolve($event, $resourceParts, $method)
	{
		if (count($resourceParts) >= 2)
		{
			$vendor = array_shift($resourceParts);
			$shortModuleName = array_shift($resourceParts);
			$treeName = $vendor . '_' . $shortModuleName;
			$documentServices = $event->getDocumentServices();
			if ($documentServices->getTreeManager()->hasTreeName($treeName))
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
						$this->resolver->setAuthorisation($event, end($pathIds), $treeName . '.ancestors');
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
						$this->resolver->setAuthorisation($event, $resource, $treeName . '.createNode');
						$action = function($event) {
							$action = new CreateTreeNode();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}
					elseif ($method === Request::METHOD_GET)
					{
						$this->resolver->setAuthorisation($event, $resource, $treeName . '.children');
						$action = function($event) {
							$action = new GetTreeNodeCollection();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}

					$result = $this->resolver->buildNotAllowedError($method, array(Request::METHOD_GET, Request::METHOD_POST));
					$event->setResult($result);
					return;
				}
				elseif (count($pathIds))
				{
					if ($method === Request::METHOD_GET)
					{
						$this->resolver->setAuthorisation($event, $resource, $treeName . '.loadNode');
						$action = function($event) {
							$action = new GetTreeNode();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}
					elseif ($method === Request::METHOD_PUT)
					{
						$this->resolver->setAuthorisation($event, $resource, $treeName . '.updateNode');
						$action = function($event) {
							$action = new UpdateTreeNode();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}
					elseif ($method === Request::METHOD_DELETE)
					{
						$this->resolver->setAuthorisation($event, $resource, $treeName . '.deleteNode');
						$action = function($event) {
							$action = new DeleteTreeNode();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}
					$result = $this->resolver->buildNotAllowedError($method, array(Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE));
					$event->setResult($result);
					return;
				}
			}
		}
	}
}