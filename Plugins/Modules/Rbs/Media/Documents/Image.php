<?php
namespace Rbs\Media\Documents;

use Change\Documents\Events\Event;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;
use Change\Http\Rest\Result\Link;
use Rbs\Media\Std\Resizer;

/**
 * @name \Rbs\Media\Documents\Image
 */
class Image extends \Compilation\Rbs\Media\Documents\Image
{
	/**
	 * @var array
	 */
	protected $imageSize = false;

	/**
	 * @var \Change\Storage\StorageManager;
	 */
	private $storageManager;

	/**
	 * @throws \RuntimeException
	 * @return \Change\Storage\StorageManager
	 */
	protected function getStorageManager()
	{
		if ($this->storageManager === null)
		{
			throw new \RuntimeException('Storage manager not set', 999999);
		}
		return $this->storageManager;
	}

	public function onDefaultInjection(\Change\Events\Event $event)
	{
		parent::onDefaultInjection($event);
		//Initialize Storage Manager (register change:// scheme)
		$this->storageManager = $event->getApplicationServices()->getStorageManager();
	}

	/**
	 * @return array
	 */
	public function getImageSize()
	{
		if ($this->imageSize === false)
		{
			$this->imageSize = (new Resizer())->getImageSize($this->getPath());
		}
		return $this->imageSize;
	}

	/**
	 * @return string
	 */
	public function getMimeType()
	{
		return $this->getStorageManager()->getMimeType($this->getPath());
	}

	/**
	 * @param string $mimeType
	 * @return $this
	 */
	public function setMimeType($mimeType)
	{
		// TODO: Implement setMimeType() method.
		return $this;
	}

	/**
	 * @param int $maxWidth
	 * @param int $maxHeight
	 * @return null|string
	 */
	public function getPublicURL($maxWidth = 0, $maxHeight = 0)
	{
		$sm = $this->getStorageManager();
		$query = array();
		if ($maxWidth !== null)
		{
			$query['max-width'] = intval($maxWidth);
		}
		if ($maxHeight !== null)
		{
			$query['max-height'] = intval($maxHeight);
		}
		$changeUri = $this->getPath();
		if (count($query))
		{
			$changeUri .= '?' . http_build_query($query);
		}
		return $sm->getPublicURL($changeUri);
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(array(Event::EVENT_CREATE, Event::EVENT_UPDATE), array($this, 'onDefaultSave'), 10);
	}

	public function onDefaultSave(Event $event)
	{
		if ($this->isPropertyModified('path'))
		{
			$size = $this->getImageSize();
			$this->setHeight($size['height']);
			$this->setWidth($size['width']);
		}
	}

	public function onDefaultUpdateRestResult(Event $event)
	{
		parent::onDefaultUpdateRestResult($event);

		$result = $event->getParam('restResult');
		/* @var $document Image */
		$document = $event->getDocument();
		$urlManager = $result->getUrlManager();
		if ($result instanceof DocumentResult)
		{
			$link = array('rel' => 'publicurl', 'href' => $document->getPublicURL());
			$result->addLink($link);
			$selfLinks = $result->getRelLink('self');
			$selfLink = array_shift($selfLinks);
			if ($selfLink instanceof Link)
			{
				$pathParts = explode('/', $selfLink->getPathInfo());
				array_pop($pathParts);
				$link = new Link($urlManager, implode('/', $pathParts) . '/resize', 'resizeurl');
				$result->addAction($link);
			}
		}
		else if ($result instanceof DocumentLink)
		{
			$pathParts = explode('/', $result->getPathInfo());
			array_pop($pathParts);
			$actions = $result->getProperty('actions', []);
			$actions[] = new Link($urlManager, implode('/', $pathParts) . '/resize', 'resizeurl');
			$result->setProperty('actions', $actions);
			$result->setProperty('width', $document->getWidth());
			$result->setProperty('height', $document->getHeight());
		}
	}
}