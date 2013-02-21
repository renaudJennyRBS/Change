<?php
namespace Change\Http\Rest;

/**
 * @name \Change\Http\Rest\Resolver
 */
class Resolver extends \Change\Http\ActionResolver
{
	/**
	 * @param \Change\Http\Event $event
	 * @return void
	 */
	public function resolve(\Change\Http\Event $event)
	{
		$request = $event->getRequest();
		$nameSpaces = array_slice(explode('/', $request->getPath()), 1);
		if (end($nameSpaces) === '')
		{
			array_pop($nameSpaces);
			$event->setParam('isDirectory', true);
		}
		$event->setParam('namespace', implode('.', $nameSpaces));

		if (count($nameSpaces) !== 0)
		{
			switch ($nameSpaces[0])
			{
				case 'resources' :
					$this->resources($event, array_slice($nameSpaces, 1), $request->getMethod());
					break;
			}
		}

		if ($event->getAction() === null && $event->getParam('isDirectory') &&  $request->getMethod() === 'GET')
		{
			$event->setAction(array($this, 'discoverNameSpace'));
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param array $resourceParts
	 * @param $method
	 * @return void
	 */
	protected function resources($event, $resourceParts, $method)
	{
		if (count($resourceParts) >= 3)
		{
			$modelName = $resourceParts[0] . '_' . $resourceParts[1] . '_' . $resourceParts[2];
			$event->setParam('modelName', $modelName);

			$documentServices = $event->getDocumentServices();
			$model = $documentServices->getModelManager()->getModelByName($modelName);
			if ($model instanceof \Change\Documents\AbstractModel)
			{
				$method = $event->getRequest()->getMethod();
				$isDirectory = $event->getParam('isDirectory', false);
				if (isset($resourceParts[3]))
				{
					if (is_numeric($resourceParts[3]))
					{
						$event->setParam('documentId', intval($resourceParts[3]));
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
								if ($method === 'GET')
								{
									$action = new \Change\Http\Rest\Actions\GetI18nDocument();
									$event->setAction(function($event) use($action) {$action->execute($event);});
									return;
								}

								if ($method === 'PUT')
								{
									$action = new \Change\Http\Rest\Actions\UpdateI18nDocument();
									$event->setAction(array($action, 'execute'));
									return;
								}

								if ($method === 'DELETE')
								{
									$action = new \Change\Http\Rest\Actions\DeleteI18nDocument();
									$event->setAction(function($event) use($action) {$action->execute($event);});
									return;
								}
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
						if ($method === 'POST' && $model->isLocalized())
						{
							$action = new \Change\Http\Rest\Actions\CreateI18nDocument();
							$event->setAction(function($event) use($action) {$action->execute($event);});
							return;
						}

						if ($method === 'GET')
						{
							$action = new \Change\Http\Rest\Actions\GetDocument();
							$event->setAction(function($event) use($action) {$action->execute($event);});
							return;
						}

						if ($method === 'PUT')
						{
							$action = new \Change\Http\Rest\Actions\UpdateDocument();
							$event->setAction(array($action, 'execute'));
							return;
						}

						if ($method === 'DELETE')
						{
							$action = new \Change\Http\Rest\Actions\DeleteDocument();
							$event->setAction(function($event) use($action) {$action->execute($event);});
							return;
						}
					}
				}
				elseif ($isDirectory)
				{
					if ($method === 'POST')
					{
						$action = new \Change\Http\Rest\Actions\CreateDocument();
						$event->setAction(function($event) use($action) {$action->execute($event);});
						return;
					}

					if ($method === 'GET')
					{
						$action = new \Change\Http\Rest\Actions\GetDocumentModelCollection();
						$event->setAction(function($event) use($action) {$action->execute($event);});
						return;
					}
				}
			}
		}
	}

	/**
	 * @param string $namespace
	 * @return string
	 */
	protected function generatePathInfoByNamespace($namespace)
	{
		if (empty($namespace))
		{
			return '/';
		}
		return '/' . str_replace('.', '/', $namespace) . '/';
	}

	/**
	 * Use Event Params: namespace
	 * @param \Change\Http\Event $event
	 */
	public function discoverNameSpace($event)
	{
		$namespace = $event->getParam('namespace');
		$result = new \Change\Http\Rest\Result\NamespaceResult();
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);

		$urlManager = $event->getUrlManager();
		$selfLink = new \Change\Http\Rest\Result\Link($urlManager, $this->generatePathInfoByNamespace($namespace));
		$result->addLink($selfLink);
		if ($namespace === '')
		{
			$link = new \Change\Http\Rest\Result\Link($urlManager, $this->generatePathInfoByNamespace('resources'), 'resources');
			$result->addLink($link);
			$event->setResult($result);
		}
		$names = explode('.', $namespace);
		if ($names[0] === 'resources')
		{
			if (!isset($names[1]))
			{
				$vendors = $event->getDocumentServices()->getModelManager()->getVendors();
				foreach ($vendors as $vendor)
				{
					$ns = $namespace .'.'. $vendor;
					$link = new \Change\Http\Rest\Result\Link($urlManager, $this->generatePathInfoByNamespace($ns), $ns);
					$result->addLink($link);
				}
				$event->setResult($result);
			}
			elseif (!isset($names[2]))
			{
				$vendor = $names[1];
				$modules = $event->getDocumentServices()->getModelManager()->getShortModulesNames($vendor);
				foreach ($modules as $module)
				{
					$ns = $namespace .'.'. $module;
					$link = new \Change\Http\Rest\Result\Link($urlManager, $this->generatePathInfoByNamespace($ns), $ns);
					$result->addLink($link);
				}
				$event->setResult($result);
			}
			elseif (!isset($names[3]))
			{
				$documents = $event->getDocumentServices()->getModelManager()->getShortDocumentsNames($names[1], $names[2]);
				if ($documents)
				{
					foreach ($documents as $document)
					{
						$ns = $namespace .'.'. $document;
						$link = new \Change\Http\Rest\Result\Link($urlManager, $this->generatePathInfoByNamespace($ns), $ns);
						$result->addLink($link);
					}
					$event->setResult($result);
				}
			}
		}
	}
}