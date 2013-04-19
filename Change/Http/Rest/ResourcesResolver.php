<?php
namespace Change\Http\Rest;

use Change\Documents\AbstractModel;
use Change\Http\Event;
use Change\Http\Rest\Actions\CreateDocument;
use Change\Http\Rest\Actions\CreateLocalizedDocument;
use Change\Http\Rest\Actions\DeleteDocument;
use Change\Http\Rest\Actions\DeleteLocalizedDocument;
use Change\Http\Rest\Actions\DiscoverNameSpace;
use Change\Http\Rest\Actions\GetDocument;
use Change\Http\Rest\Actions\GetDocumentModelCollection;
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
		$nbParts = count($resourceParts);
		if ($nbParts === 1 && is_numeric($resourceParts[0]) && $method === Request::METHOD_GET)
		{
			$documentId = intval($resourceParts[0]);
			$this->getDocumentActionById($event, $documentId);
		}
		elseif (count($resourceParts) < 3 && $method === Request::METHOD_GET)
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
		elseif (count($resourceParts) >= 3)
		{
			$modelName = $resourceParts[0] . '_' . $resourceParts[1] . '_' . $resourceParts[2];
			$documentServices = $event->getDocumentServices();
			$model = $documentServices->getModelManager()->getModelByName($modelName);
			if ($model instanceof AbstractModel)
			{
				$event->setParam('modelName', $modelName);
				$isDirectory = $event->getParam('isDirectory', false);
				if (isset($resourceParts[3]))
				{
					if (is_numeric($resourceParts[3]))
					{
						$documentId = intval($resourceParts[3]);
						if ($documentId > 0)
						{
							$event->setParam('documentId', $documentId);
						}
						else
						{
							//Document Not found
							return;
						}
					}
					else
					{
						//Invalid DocumentId
						return;
					}

					//Localized Document
					if (isset($resourceParts[4]))
					{
						if ($model->isLocalized()
							&& $event->getApplicationServices()->getI18nManager()->isSupportedLCID($resourceParts[4])
						)
						{
							$event->setParam('LCID', $resourceParts[4]);
							if (!$isDirectory)
							{
								if ($method === Request::METHOD_POST)
								{
									$privilege = $modelName . '.create';
									$this->resolver->setAuthorisation($event, $modelName, $privilege);

									$action = function ($event)
									{
										$action = new CreateLocalizedDocument();
										$action->execute($event);
									};
									$event->setAction($action);
									return;
								}

								if ($method === Request::METHOD_GET)
								{
									$privilege = $modelName . '.load';
									$this->resolver->setAuthorisation($event, $documentId, $privilege);

									$action = function ($event)
									{
										$action = new GetLocalizedDocument();
										$action->execute($event);
									};
									$event->setAction($action);
									return;
								}

								if ($method === Request::METHOD_PUT)
								{
									$privilege = $modelName . '.updateLocalized';
									$this->resolver->setAuthorisation($event, $documentId, $privilege);

									$action = function ($event)
									{
										$action = new UpdateLocalizedDocument();
										$action->execute($event);
									};
									$event->setAction($action);
									return;
								}

								if ($method === Request::METHOD_DELETE)
								{
									$privilege = $modelName . '.deleteLocalized';
									$this->resolver->setAuthorisation($event, $documentId, $privilege);

									$action = function ($event)
									{
										$action = new DeleteLocalizedDocument();
										$action->execute($event);
									};
									$event->setAction($action);
									return;
								}

								$result = $this->resolver->buildNotAllowedError($method,
									array(Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE));
								$event->setResult($result);
								return;
							}
						}
						else
						{
							//Invalid LCID
							return;
						}
					}

					if (!$isDirectory)
					{
						if ($method === 'POST')
						{
							$privilege = $modelName . '.create';
							$this->resolver->setAuthorisation($event, $modelName, $privilege);

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

						if ($method === 'GET')
						{
							$privilege = $modelName . '.load';
							$this->resolver->setAuthorisation($event, $documentId, $privilege);

							$action = function ($event)
							{
								$action = new GetDocument();
								$action->execute($event);
							};
							$event->setAction($action);
							return;
						}

						if ($method === 'PUT')
						{
							$privilege = $modelName . '.update';
							$this->resolver->setAuthorisation($event, $documentId, $privilege);

							$action = function ($event)
							{
								$action = new UpdateDocument();
								$action->execute($event);
							};
							$event->setAction($action);
							return;
						}

						if ($method === 'DELETE')
						{
							$privilege = $modelName . '.delete';
							$this->resolver->setAuthorisation($event, $documentId, $privilege);

							$action = function ($event)
							{
								$action = new DeleteDocument();
								$action->execute($event);
							};
							$event->setAction($action);
							return;
						}
					}
				}
				elseif ($isDirectory)
				{
					if ($method === Request::METHOD_POST)
					{
						$privilege = $modelName . 'create';
						$this->resolver->setAuthorisation($event, $modelName, $privilege);

						$action = function ($event)
						{
							$action = new CreateDocument();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}

					if ($method === Request::METHOD_GET)
					{
						$privilege = $modelName . '.collection';
						$this->resolver->setAuthorisation($event, $modelName, $privilege);

						$action = function ($event)
						{
							$action = new GetDocumentModelCollection();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}

					$result = $this->resolver->buildNotAllowedError($method, array(Request::METHOD_GET, Request::METHOD_POST));
					$event->setResult($result);
					return;
				}
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

		$privilege = $modelName . '.load';
		$this->resolver->setAuthorisation($event, $documentId, $privilege);

		if ($document->getDocumentModel()->isLocalized())
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
}