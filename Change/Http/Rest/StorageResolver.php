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
	 * @param string $storageURI
	 * @return string
	 */
	public static function buildPathInfo($storageURI)
	{
		$infos = parse_url($storageURI);
		if (!isset($infos['path'])) {$infos['path'] = '/';}
		return 'storage/' . $infos['host'] . $infos['path'];
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
		elseif ($method === Request::METHOD_POST)
		{
			if ($event->getRequest()->getFiles('file', false))
			{
				$storageName = array_shift($resourceParts);
				$storageEngine = $event->getApplicationServices()->getStorageManager()->getStorageByName($storageName);
				if ($storageEngine)
				{
					$file = $event->getRequest()->getFiles('file');
					if (isset($file['name']))
					{
						if ($file['error'] === 0)
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
						else
						{
							switch ($file['error'])
							{
								case UPLOAD_ERR_INI_SIZE :
									$msg = "The uploaded file exceeds " . ini_get('upload_max_filesize') . ". See php.ini.";
									break;

								case UPLOAD_ERR_INI_SIZE :
									$msg = "Upload error: UPLOAD_ERR_INI_SIZE";
									break;

								case UPLOAD_ERR_FORM_SIZE :
									$msg = "Upload error: UPLOAD_ERR_FORM_SIZE";
									break;

								case UPLOAD_ERR_PARTIAL :
									$msg = "Upload error: UPLOAD_ERR_PARTIAL";
									break;

								case UPLOAD_ERR_NO_FILE :
									$msg = "Upload error: UPLOAD_ERR_NO_FILE";
									break;

								case UPLOAD_ERR_NO_TMP_DIR :
									$msg = "Upload error: UPLOAD_ERR_NO_TMP_DIR";
									break;

								case UPLOAD_ERR_CANT_WRITE :
									$msg = "Upload error: UPLOAD_ERR_CANT_WRITE";
									break;

								case UPLOAD_ERR_EXTENSION :
									$msg = "Upload error: UPLOAD_ERR_EXTENSION";
									break;

								default :
									$msg = "Unknown upload error: " . $file['error'];
							}

							throw new \RuntimeException($msg, 999999);
						}
					}
				}
			}
			else
			{
				throw new \RuntimeException("No file ", 999999);
			}
		}
	}
}