<?php
namespace Change\Http\Rest\Actions;

use \Change\Http\Rest\Result\CollectionResult;
use Change\Http\Rest\Result\ArrayResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\GetFile
 */
class GetFile
{
	/**
	 * Use Required Event Params: path
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$storagePath = $event->getParam('path');
		if (!$storagePath)
		{
			//Document Not Found
			return;
		}
		$storageManager =  $event->getApplicationServices()->getStorageManager();
		$itemInfo = $storageManager->getItemInfo($storagePath);
		if ($itemInfo && file_exists($storagePath))
		{
			$infos = parse_url($storagePath);
			if (!isset($infos['path'])) {$infos['path'] = '/';}

			$findPath = array(DIRECTORY_SEPARATOR, $infos['scheme'] . '://');
			$replacePath = array('/', 'storage/');

			$path = 'storage/' . $infos['host'] . $infos['path'];
			if ($itemInfo->isDir())
			{
				if (substr($storagePath, -1) !== '/')
				{
					$storagePath .= '/';
				}
				$result = new CollectionResult();
				$result->addLink(new \Change\Http\Rest\Result\Link($event->getUrlManager(), $path));
				$dirHandle = opendir($storagePath);
				$childNames = array();
				while (($name = readdir($dirHandle)) !== false)
				{
					if ($name !== '.' && $name !== '..')
					{
						$childNames[] = $name;
					}
				}
				closedir($dirHandle);
				$count = 0;
				foreach ($childNames as $name)
				{
					$childPath = $storagePath . $name;
					$fileInfo = $storageManager->getItemInfo($childPath);
					if ($fileInfo)
					{
						$count++;
						$restPath = str_replace($findPath, $replacePath, $fileInfo->getPathname());
						if ($fileInfo->isDir())
						{
							$restPath .= '/';
						}
						$link = new \Change\Http\Rest\Result\Link($event->getUrlManager(), $restPath);
						$res = array('name' => $fileInfo->getFilename(), 'link' => $link->toArray());
						$result->addResource($res);
					}
				}
				$result->setCount($count);
			}
			else
			{
				$link = new \Change\Http\Rest\Result\Link($event->getUrlManager(), $path);
				$hrefContent = $event->getUrlManager()->getByPathInfo($path)->setQuery(array('content' => 1))->normalize()->toString();
				$result = new ArrayResult();
				$content = $event->getRequest()->getQuery('content');
				if ($content)
				{
					$event->setParam('itemInfo', $itemInfo);
					$event->getController()->getEventManager()->attach(\Change\Http\Event::EVENT_RESPONSE, array($this, 'onResultContent'), 10);
				}
				$result->setArray(array(
					'link' => $link->toArray(),
					'path' => $storagePath,
					'size' => $itemInfo->getSize(),
					'data' => $hrefContent,
					'mimeType' => $itemInfo->getMimeType()));
			}
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$result->setHeaderLastModified(\DateTime::createFromFormat('U', $itemInfo->getMTime()));
			$event->setResult($result);
		}
	}


	/**
	 * @param \Change\Http\Event $event
	 */
	public function onResultContent($event)
	{
		$itemInfo = $event->getParam('itemInfo');
		if ($itemInfo instanceof \Change\Storage\ItemInfo)
		{
			/* @var $result ArrayResult */
			$result = $event->getResult();
			$ra = $result->toArray();

			$response = $event->getController()->createResponse();

			if (!$event->getController()->resultNotModified($event->getRequest(), $result))
			{
				$response->setStatusCode(HttpResponse::STATUS_CODE_200);

				$response->getHeaders()->clearHeaders();

				if (isset($ra['mimeType']))
				{
					$result->getHeaders()->addHeaderLine('Content-Type: ' . $ra['mimeType']);
				}
				else
				{
					$result->getHeaders()->addHeaderLine('Content-Type:  application/octet-stream');
				}
				$response->getHeaders()->addHeaders($result->getHeaders());
				$response->setContent(file_get_contents($itemInfo->getPathname()));
			}
			else
			{
				$response->setStatusCode(HttpResponse::STATUS_CODE_304);
			}
			return $response;
		}
	}
}
