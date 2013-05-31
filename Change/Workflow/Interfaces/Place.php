<?php
namespace Change\Workflow\Interfaces;

/**
* @name \Change\Workflow\Interfaces\Place
*/
interface Place extends WorkflowItem
{
	const TYPE_START = 1;
	const TYPE_INTERMEDIATE = 5;
	const TYPE_END = 9;

	/**
	 * Return \Change\Workflow\Interfaces\Place::TYPE_*
	 * @return integer
	 */
	public function getType();


	/**
	 * Return Short name
	 * @return string
	 */
	public function getName();


}