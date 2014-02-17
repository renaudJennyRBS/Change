<?php
namespace Rbs\Collection\Documents;

use Change\Http\Rest\Result\ErrorResult;
use Zend\Http\Response as HttpResponse;
use Zend\Http\Response;

/**
 * @name \Rbs\Collection\Documents\Item
 */
class Item extends \Compilation\Rbs\Collection\Documents\Item implements \Change\Collection\ItemInterface
{
	/**
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	protected function onUpdate()
	{
		if ($this->isPropertyModified('value') && $this->getLocked())
		{
			$this->setValue($this->getValueOldValue());
		}
	}

	/**
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	protected function onDelete()
	{
		if ($this->getLocked())
		{
			throw new \RuntimeException('can not delete locked item', 999999);
		}
	}

	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		/** @var $document Item */
		$document = $event->getDocument();
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$documentLink = $restResult;
			$documentLink->setProperty('locked', $document->getLocked());
			$documentLink->setProperty('value', $document->getValue());
		}
	}

	/**
	 * @param $name
	 * @param $value
	 * @param \Change\Http\Event $event
	 * @return bool
	 */
	protected function processRestData($name, $value, \Change\Http\Event $event)
	{
		$result = parent::processRestData($name, $value, $event);
		if ($this->isPropertyModified('value') && $this->getLocked() === true)
		{
			$result = new ErrorResult('COLLECTION-ITEM-LOCKED', 'Can not modify the value of a locked collection item', HttpResponse::STATUS_CODE_409);
			$event->setResult($result);
			return false;
		}
		if ($this->isPropertyModified('locked') && $this->getLockedOldValue() === true)
		{
			$result = new ErrorResult('COLLECTION-ITEM-LOCKED', 'Can not unlock locked collection item', HttpResponse::STATUS_CODE_409);
			$event->setResult($result);
			return false;
		}
		return $result;
	}

	/**
	 * @return string|null
	 */
	public function getTitle()
	{
		$title = $this->getCurrentLocalization()->isNew() ? $this->getRefLocalization()
			->getTitle() : $this->getCurrentLocalization()->getTitle();
		return $title === null ? $this->getLabel() : $title;
	}
}
