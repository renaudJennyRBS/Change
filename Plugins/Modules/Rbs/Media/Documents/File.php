<?php
namespace Rbs\Media\Documents;

use Change\Documents\Events\Event;

/**
 * @name \Rbs\Media\Documents\File
 */
class File extends \Compilation\Rbs\Media\Documents\File
{
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
	 * @return \Change\Storage\ItemInfo|null
	 */
	public function getItemInfo()
	{
		return $this->getStorageManager()->getItemInfo($this->getPath());
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(Event::EVENT_UPDATE, array($this, 'onDefaultUpdate'), 10);
		$eventManager->attach(Event::EVENT_DELETED, array($this, 'onDefaultDeleted'), 10);
		$eventManager->attach('getDownloadUri', array($this, 'onDefaultGetDownloadUri'), 5);
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
	public function onDefaultUpdate(Event $event)
	{
		if ($this->getPath() != $this->getPathOldValue())
		{
			$engine = $this->getStorageManager()->getStorageByStorageURI($this->getPathOldValue());
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
}
