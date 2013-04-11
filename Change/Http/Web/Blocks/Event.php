<?php
namespace Change\Http\Web\Blocks;

use Zend\EventManager\Event as ZendEvent;

/**
 * @name \Change\Http\Web\Blocks\Event
 */
class Event extends ZendEvent
{
	/**
	 * @param \Change\Http\Web\Layout\Block $blockLayout
	 * @return $this
	 */
	public function setBlockLayout($blockLayout)
	{
		return $this->setParam('blockLayout', $blockLayout);
	}

	/**
	 * @return \Change\Http\Web\Layout\Block|null
	 */
	public function getBlockLayout()
	{
		return $this->getParam('blockLayout');
	}

	/**
	 * @param Parameters $blockParameters
	 * @return $this
	 */
	public function setBlockParameters($blockParameters)
	{
		return $this->setParam('blockParameters', $blockParameters);
	}

	/**
	 * @return Parameters
	 */
	public function getBlockParameters()
	{
		return $this->getParam('blockParameters', null);
	}

	/**
	 * @param \Change\Http\Web\Blocks\Result $blockResult
	 * @return $this
	 */
	public function setBlockResult($blockResult)
	{
		return $this->setParam('blockResult', $blockResult);
	}

	/**
	 * @return \Change\Http\Web\Blocks\Result
	 */
	public function getBlockResult()
	{
		return $this->getParam('blockResult', null);
	}
}