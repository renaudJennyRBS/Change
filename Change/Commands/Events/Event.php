<?php
namespace Change\Commands\Events;

/**
* @name \Change\Commands\Events\Event
*/
class Event extends \Change\Events\Event
{

	/**
	 * @var bool
	 */
	protected $success = true;

	public function success()
	{
		return $this->success;
	}

	/**
	 * @return \Change\Application
	 */
	public function getApplication()
	{
		return $this->getTarget();
	}

	/**
	 * @return \ArrayObject
	 */
	public function getOutputMessages()
	{
		$outputMessages = $this->getParam('outputMessages');
		if (!($outputMessages instanceof \ArrayObject))
		{
			$outputMessages = new \ArrayObject();
			$this->setParam('outputMessages', $outputMessages);
		}
		return $outputMessages;
	}

	/**
	 * @param string $message
	 * @param int $level
	 */
	public function addMessage($message, $level = 0)
	{
		$outputMessages = $this->getOutputMessages();
		$outputMessages[] = array($message, $level);
	}

	/**
	 * @param string $message
	 */
	public function addInfoMessage($message)
	{
		$this->addMessage($message, 0);
	}

	/**
	 * @param string $message
	 */
	public function addCommentMessage($message)
	{
		$this->addMessage($message, 1);
	}

	/**
	 * @param string $message
	 */
	public function addErrorMessage($message)
	{
		$this->success = false;
		$this->addMessage($message, 2);
	}
}