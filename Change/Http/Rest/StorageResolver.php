<?php
namespace Change\Http\Rest;

use Change\Http\Rest\Actions\DiscoverNameSpace;
use Change\Http\Rest\Actions\UploadFile;
use Change\Http\Rest\Actions\GetFile;
/**
 * @name \Change\Http\Rest\StorageResolver
 */
class StorageResolver
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
			return $event->getApplicationServices()->getStorageManager()->getStorageNames();
		}
		return array();
	}

	/**
	 * Set Event params: query
	 * @param \Change\Http\Event $event
	 * @param array $resourceParts
	 * @param $method
	 * @return void
	 */
	public function resolve($event, $resourceParts, $method)
	{
		$isDirectory = (bool)$event->getParam('isDirectory');
		$nbParts = count($resourceParts);
		if ($nbParts == 0 && $method === Request::METHOD_GET)
		{
			array_unshift($resourceParts, 'storage');
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
		elseif ($method === Request::METHOD_GET)
		{
			if ($isDirectory)
			{
				$resourceParts[] = '';
			}
			$storageName = array_shift($resourceParts);
			$storageEngine = $event->getApplicationServices()->getStorageManager()->getStorageByName($storageName);
			if ($storageEngine)
			{
				$cleaner = function ($string) {return trim($string) != '';};
				$path = \Change\Storage\StorageManager::DEFAULT_SCHEME . '://' . $storageName . '/' . implode('/', array_filter($resourceParts, $cleaner));
				$event->setParam('path', $path);

				//TODO Activate Authorisation
				//$privilege = 'storage.get';
				//$this->resolver->setAuthorisation($event, $path, $privilege);

				$action = function ($event)
				{
					$action = new GetFile();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}
		}
		elseif ($method === Request::METHOD_POST && $event->getRequest()->getFiles('file', false))
		{
			$storageName = array_shift($resourceParts);
			$storageEngine = $event->getApplicationServices()->getStorageManager()->getStorageByName($storageName);
			if ($storageEngine)
			{
				$file = $event->getRequest()->getFiles('file');
				if (isset($file['name']) && $file['error'] === 0)
				{
					$cleaner = function ($string) {return trim($string) != '';};
					if ($isDirectory)
					{
						$resourceParts[] = uniqid() . '_' . $file['name'];
					}
					$storagePath = $storageEngine->normalizePath(implode('/', array_filter($resourceParts, $cleaner)));
					$path = \Change\Storage\StorageManager::DEFAULT_SCHEME . '://' . $storageName . '/' . $storagePath;
					$event->setParam('destinationPath', $path);
					$event->setParam('file', $file);

					//TODO Activate Authorisation
					//$privilege = 'storage.upload';
					//$this->resolver->setAuthorisation($event, $path, $privilege);

					$action = function ($event)
					{
						$action = new UploadFile();
						$action->execute($event);
					};
					$event->setAction($action);
					return;
				}
			}
		}
	}
}