<?php
namespace Rbs\Media\Documents;

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
	 * @return array
	 */
	public function getImageSize()
	{
		// Load the storage manager even if not used in the function itself
		$sm = $this->getApplicationServices()->getStorageManager();
		if ($this->imageSize === false)
		{
			$this->imageSize = (new Resizer())->getImageSize($this->getPath());
		}
		return $this->imageSize;
	}

	/**
	 *
	 */
	public function onUpdate()
	{
		$this->updateImageSizeProperties();
	}

	/**
	 *
	 */
	public function onCreate()
	{
		$this->updateImageSizeProperties();
	}

	/**
	 *
	 */
	protected function updateImageSizeProperties()
	{
		if ($this->isPropertyModified('path'))
		{
			$size = $this->getImageSize();
			$this->setHeight($size['height']);
			$this->setWidth($size['width']);
		}
	}

	/**
	 * @return string
	 */
	public function getMimeType()
	{
		return $this->getApplicationServices()->getStorageManager()->getMimeType($this->getPath());
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
		$sm = $this->getApplicationServices()->getStorageManager();
		$query = array();
		if (intval($maxWidth))
		{
			$query['max-width'] = intval($maxWidth);
		}
		if (intval($maxHeight))
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
		$eventManager->attach('updateRestResult', function(\Change\Documents\Events\Event $event) {
			$result = $event->getParam('restResult');
			/* @var $document Image */
			$document = $event->getDocument();
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
					$link = new Link($event->getParam('urlManager'), implode('/', $pathParts) . '/resize', 'resizeurl');
					$result->addAction($link);
				}
			}
			else if ($result instanceof DocumentLink)
			{
				$pathParts = explode('/', $result->getPathInfo());
				array_pop($pathParts);
				$result->setProperty('actions', array(new Link($event->getParam('urlManager'), implode('/', $pathParts) . '/resize', 'resizeurl')));
				$result->setProperty('width', $document->getWidth());
				$result->setProperty('height', $document->getHeight());
			}
		}, 5);
	}
}