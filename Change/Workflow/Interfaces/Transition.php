<?php
namespace Change\Workflow\Interfaces;

/**
* @name \Change\Workflow\Interfaces\Transition
*/
interface Transition extends WorkflowItem
{
	const TRIGGER_USER = 'USER';
	const TRIGGER_AUTO = 'AUTO';
	const TRIGGER_TIME = 'TIME';
	const TRIGGER_MSG = 'MSG';

	/**
	 * Return Short name
	 * @return string
	 */
	public function getName();

	/**
	 * Return \Change\Workflow\Interfaces\Transition::TRIGGER_*
	 * @return string
	 */
	public function getTrigger();

	/**
	 * Only valid for Time trigger
	 * @return \DateInterval
	 */
	public function getTimeLimit();

	/**
	 * Only valid for User trigger
	 * @return string
	 */
	public function getRole();

	/**
	 * @return string
	 */
	public function getTaskCode();
}