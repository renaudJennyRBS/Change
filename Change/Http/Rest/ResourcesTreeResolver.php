<?php
namespace Change\Http\Rest;

use Change\Http\Rest\Actions\CreateTreeNode;
use Change\Http\Rest\Actions\DeleteTreeNode;
use Change\Http\Rest\Actions\GetTreeNode;
use Change\Http\Rest\Actions\GetTreeNodeCollection;
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
					else
					{
						return;
					}
				}
				$event->setParam('pathIds', $pathIds);

				if ($event->getParam('isDirectory', false))
				{
					if ($method === Request::METHOD_POST)
					{
						$action = function($event) {
							$action = new CreateTreeNode();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}
					elseif ($method === Request::METHOD_GET)
					{
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
						$action = function($event) {
							$action = new GetTreeNode();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}
					elseif ($method === Request::METHOD_PUT)
					{
						$action = function($event) {
							$action = new UpdateTreeNode();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}
					elseif ($method === Request::METHOD_DELETE)
					{
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