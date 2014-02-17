<?php
namespace Change\Commands\Events;

/**
* @name \Change\Commands\Events\Event
*/
class Event extends \Change\Events\Event
{

	/**
	 * @var CommandResponseInterface
	 */
	protected $commandResponse;

	/**
	 * @return \Change\Application
	 */
	public function getApplication()
	{
		return $this->getTarget();
	}

	/**
	 * @param \Change\Commands\Events\CommandResponseInterface $commandResponse
	 */
	public function setCommandResponse($commandResponse)
	{
		$this->commandResponse = $commandResponse;
	}

	/**
	 * @return \Change\Commands\Events\CommandResponseInterface
	 */
	public function getCommandResponse()
	{
		return $this->commandResponse;
	}

}