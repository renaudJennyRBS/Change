<?php
namespace Change\Http\Rest;

use Change\Documents\AbstractDocument;
use Change\Documents\AbstractModel;
use Change\Http\Rest\Actions\CreateDocument;
use Change\Http\Rest\Actions\CreateLocalizedDocument;
use Change\Http\Rest\Actions\DeleteDocument;
use Change\Http\Rest\Actions\DeleteLocalizedDocument;
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
	 * Set event Params: modelName, documentId, LCID
	 * @param \Change\Http\Event $event
	 * @param array $resourceParts
	 * @param $method
	 * @return void
	 */
	public function resolve($event, $resourceParts, $method)
	{
		if (count($resourceParts) >= 3)
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
						if ($model->isLocalized() && $event->getApplicationServices()->getI18nManager()->isSupportedLCID($resourceParts[4]))
						{
							$event->setParam('LCID', $resourceParts[4]);
							if (!$isDirectory)
							{
								if ($method === Request::METHOD_POST)
								{
									$action = function($event) {
										$action = new CreateLocalizedDocument();
										$action->execute($event);
									};
									$event->setAction($action);
									return;
								}

								if ($method === Request::METHOD_GET)
								{
									$action = function($event) {
										$action = new GetLocalizedDocument();
										$action->execute($event);
									};
									$event->setAction($action);
									return;
								}

								if ($method === Request::METHOD_PUT)
								{
									$action = function($event) {
										$action = new UpdateLocalizedDocument();
										$action->execute($event);
									};
									$event->setAction($action);
									return;
								}

								if ($method === Request::METHOD_DELETE)
								{
									$action = function($event) {
										$action = new DeleteLocalizedDocument();
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
							if ($model->isLocalized())
							{
								$action = function($event) {
									$action = new CreateLocalizedDocument();
									$action->execute($event);
								};
							}
							else
							{
								$action = function($event) {
									$action = new CreateDocument();
									$action->execute($event);
								};
							}
							$event->setAction($action);
							return;
						}

						if ($method === 'GET')
						{
							$action = function($event) {
								$action = new GetDocument();
								$action->execute($event);
							};
							$event->setAction($action);
							return;
						}

						if ($method === 'PUT')
						{
							$action = function($event) {
								$action = new UpdateDocument();
								$action->execute($event);
							};
							$event->setAction($action);
							return;
						}

						if ($method === 'DELETE')
						{
							$action = function($event) {
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
						$action = function($event) {
							$action = new CreateDocument();
							$action->execute($event);
						};
						$event->setAction($action);
						return;
					}

					if ($method === Request::METHOD_GET)
					{
						$action = function($event) {
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

}