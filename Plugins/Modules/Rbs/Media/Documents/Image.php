<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Media\Documents;

use Change\Documents\Events\Event;
use Change\Http\Rest\V1\Link;
use Change\Http\Rest\V1\Resources\DocumentLink;
use Change\Http\Rest\V1\Resources\DocumentResult;
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
		$eventManager->attach(Event::EVENT_DELETED, array($this, 'onDefaultDeleted'), 10);
		$eventManager->attach('getDownloadUri', array($this, 'onDefaultGetDownloadUri'), 5);
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultSave(Event $event)
	{
		if ($this->isPropertyModified('path'))
		{
			if ($this->getPathOldValue() && 	$this->getPath() != $this->getPathOldValue())
			{
				$engine = $this->getStorageManager()->getStorageByStorageURI($this->getPathOldValue());
				$engine->unlink();
			}

			$size = $this->getImageSize();
			$this->setHeight($size['height']);
			$this->setWidth($size['width']);
		}
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultDeleted(Event $event)
	{
		if ($this->getPath())
		{
			$engine = $this->getStorageManager()->getStorageByStorageURI($this->getPath());
			$engine->unlink();
		}
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultGetDownloadUri(Event $event)
	{
		if ($this->activated())
		{
			$event->setParam('downloadUri', $this->getPath());
		}
	}

	/**
	 * @param Event $event
	 */
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