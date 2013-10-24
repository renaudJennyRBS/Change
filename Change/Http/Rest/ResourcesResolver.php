<?php
namespace Change\Http\Rest;

use Change\Documents\AbstractModel;
use Change\Documents\Interfaces\Localizable;
use Change\Http\Event;
use Change\Http\Rest\Actions\CreateDocument;
use Change\Http\Rest\Actions\CreateLocalizedDocument;
use Change\Http\Rest\Actions\DeleteDocument;
use Change\Http\Rest\Actions\DeleteLocalizedDocument;
use Change\Http\Rest\Actions\DiscoverNameSpace;
use Change\Http\Rest\Actions\GetCorrection;
use Change\Http\Rest\Actions\GetDocument;
use Change\Http\Rest\Actions\GetDocumentCollection;
use Change\Http\Rest\Actions\GetLocalizedDocument;
use Change\Http\Rest\Actions\UpdateDocument;
use Change\Http\Rest\Actions\UpdateLocalizedDocument;

/**
 * @name \Change\Http\Rest\ResourcesResolver
 */
class ResourcesResolver
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
	 * @param Event $event
	 * @param string[] $namespaceParts
	 * @return string[]
	 */
	public function getNextNamespace($event, $namespaceParts)
	{
		if (!isset($namespaceParts[1]))
		{
			return $event->getDocumentServices()->getModelManager()->getVendors();
		}
		elseif (!isset($namespaceParts[2]))
		{
			$vendor = $namespaceParts[1];
			return $event->getDocumentServices()->getModelManager()->getShortModulesNames($vendor);
		}
		elseif (!isset($namespaceParts[3]))
		{
			return $event->getDocumentServices()->getModelManager()
				->getShortDocumentsNames($namespaceParts[1], $namespaceParts[2]);
		}
		return array();
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
		$relativePath = implode('/', $event->getParam('isDirectory') ? array_merge($resourceParts, array('')): $resourceParts);
		$documentServices = $event->getDocumentServices();

		// id
		if (preg_match('|^([0-9]+)$|', $relativePath, $matches))
		{
			if ($method !== Request::METHOD_GET)
			{
				$event->setResult($event->getController()->notAllowedError($method, array(Request::METHOD_GET)));
				return;
			}
			$documentId = intval($matches[1]);
			$this->getDocumentActionById($event, $documentId);
			return;
		}

		// [Vendor/][Module/]
		if (preg_match('|^([A-Z][a-z0-9]+/){0,2}$|', $relativePath, $matches))
		{
			if ($method === Request::METHOD_GET)
			{
				$this->getDiscoverNamespaceAction($event, $resourceParts);
				return;
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($method, array(Request::METHOD_GET)));
				return;
			}
		}

		// Vendor/Module/Name/
		if (preg_match('|^[A-Z][a-z0-9]+/[A-Z][a-z0-9]+/[A-Z][A-Za-z0-9]+/$|', $relativePath, $matches))
		{
			$modelName = $resourceParts[0] . '_' . $resourceParts[1] . '_' . $resourceParts[2];
			$model = $documentServices->getModelManager()->getModelByName($modelName);
			if (!$model)
			{
				return;
			}
			$event->setParam('modelName', $modelName);
			if ($method === Request::METHOD_GET)
			{
				$this->resolver->setAuthorization($event, 'Consumer', null, $modelName);

				$action = function ($event)
				{
					$action = new GetDocumentCollection();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}
			elseif ($method === Request::METHOD_POST)
			{
				$this->resolver->setAuthorization($event, 'Creator', null, $modelName);

				$action = function ($event)
				{
					$action = new CreateDocument();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($method, array(Request::METHOD_GET)));
				return;
			}
		}

		// Vendor/Module/Name/Id
		if (preg_match('|^[A-Z][a-z0-9]+/[A-Z][a-z0-9]+/[A-Z][A-Za-z0-9]+/([0-9]+)$|', $relativePath, $matches))
		{
			$modelName = $resourceParts[0] . '_' . $resourceParts[1] . '_' . $resourceParts[2];
			$model = $documentServices->getModelManager()->getModelByName($modelName);
			if (!$model)
			{
				return;
			}
			$event->setParam('modelName', $modelName);
			$documentId = intval($resourceParts[3]);
			$event->setParam('documentId', $documentId);
			if ($method === Request::METHOD_GET)
			{
				$this->resolver->setAuthorization($event, 'Consumer', $documentId, $modelName);
				$action = function ($event)
				{
					$action = new GetDocument();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}
			elseif ($method === Request::METHOD_PUT)
			{
				$this->resolver->setAuthorization($event, 'Creator', $documentId, $modelName);
				$action = function ($event)
				{
					$action = new UpdateDocument();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}
			elseif ($method === Request::METHOD_DELETE)
			{
				$this->resolver->setAuthorization($event, 'Creator', $documentId, $modelName);
				$action = function ($event)
				{
					$action = new DeleteDocument();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}
			elseif ($method === Request::METHOD_POST)
			{
				$this->resolver->setAuthorization($event, 'Creator', null, $modelName);
				if ($model->isLocalized())
				{
					$action = function ($event)
					{
						$action = new CreateLocalizedDocument();
						$action->execute($event);
					};
				}
				else
				{
					$action = function ($event)
					{
						$action = new CreateDocument();
						$action->execute($event);
					};
				}
				$event->setAction($action);
				return;
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($method,
					array(Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE, Request::METHOD_POST)));
				return;
			}
		}

		// Vendor/Module/Name/Id/LCID
		if (preg_match('|^[A-Z][a-z0-9]+/[A-Z][a-z0-9]+/[A-Z][A-Za-z0-9]+/([0-9]+)/([a-z]{2}_[A-Z]{2})$|', $relativePath, $matches))
		{
			$modelName = $resourceParts[0] . '_' . $resourceParts[1] . '_' . $resourceParts[2];
			$model = $documentServices->getModelManager()->getModelByName($modelName);
			if (!$model || !$model->isLocalized() || !$event->getApplicationServices()->getI18nManager()->isSupportedLCID($resourceParts[4]))
			{
				return;
			}
			$event->setParam('modelName', $modelName);
			$documentId = intval($resourceParts[3]);
			$event->setParam('documentId', $documentId);
			$event->setParam('LCID', $resourceParts[4]);
			if ($method === Request::METHOD_GET)
			{
				$this->resolver->setAuthorization($event, 'Consumer', $documentId, $modelName);
				$action = function ($event)
				{
					$action = new GetLocalizedDocument();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}
			elseif ($method === Request::METHOD_PUT)
			{
				$this->resolver->setAuthorization($event, 'Creator', $documentId, $modelName);
				$action = function ($event)
				{
					$action = new UpdateLocalizedDocument();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}
			elseif ($method === Request::METHOD_DELETE)
			{
				$this->resolver->setAuthorization($event, 'Creator', $documentId, $modelName);

				$action = function ($event)
				{
					$action = new DeleteLocalizedDocument();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}
			elseif ($method === Request::METHOD_POST)
			{
				$this->resolver->setAuthorization($event, 'Creator', null, $modelName);
				$action = function ($event)
				{
					$action = new CreateLocalizedDocument();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($method,
					array(Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE, Request::METHOD_POST)));
				return;
			}
		}

		// Vendor/Module/Name/Id[/LCID]/correction
		if (preg_match('|^[A-Z][a-z0-9]+/[A-Z][a-z0-9]+/[A-Z][A-Za-z0-9]+/([0-9]+)(?:/([a-z]{2}_[A-Z]{2}))?/correction$|', $relativePath, $matches))
		{
			$modelName = $resourceParts[0] . '_' . $resourceParts[1] . '_' . $resourceParts[2];
			$model = $documentServices->getModelManager()->getModelByName($modelName);
			if (!$model || !$model->useCorrection())
			{
				return;
			}
			if (isset($matches[2]))
			{
				if (!$model->isLocalized() || !$event->getApplicationServices()->getI18nManager()->isSupportedLCID($matches[2]))
				{
					return;
				}
				$event->setParam('LCID', $matches[2]);
			}
			elseif ($model->isLocalized())
			{
				return;
			}
			$event->setParam('modelName', $modelName);
			$documentId = intval($resourceParts[3]);
			$event->setParam('documentId', $documentId);

			if ($method === Request::METHOD_GET)
			{
				$this->resolver->setAuthorization($event, 'Consumer', $documentId, $modelName);
				$action = function ($event)
				{
					$action = new GetCorrection();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($method, array(Request::METHOD_GET)));
				return;
			}
		}
	}

	/**
	 * @param Event $event
	 * @param $documentId
	 */
	protected function getDocumentActionById($event, $documentId)
	{
		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId);
		if ($document === null)
		{
			//Document not found
			return;
		}
		$event->setParam('documentId', $documentId);
		$modelName = $document->getDocumentModelName();
		$event->setParam('modelName', $modelName);

		$this->resolver->setAuthorization($event, 'Consumer', $documentId, $modelName);
		if ($document instanceof Localizable)
		{
			$event->setParam('LCID', $document->getRefLCID());
			$action = function ($event)
			{
				$action = new GetLocalizedDocument();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
		else
		{
			$action = function ($event)
			{
				$action = new GetDocument();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
	}

	/**
	 * @param Event $event
	 * @param string[] $resourceParts
	 */
	protected function getDiscoverNamespaceAction($event, $resourceParts)
	{
		array_unshift($resourceParts, 'resources');
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
}