<?php
namespace Change\Workflow\Interfaces;

/**
* @name \Change\Workflow\Interfaces\WorkflowItem
*/
interface WorkflowItem
{

	/**
	 * @return \Change\Workflow\Interfaces\Workflow
	 */
	public function getWorkflow();

	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * @return \Change\Workflow\Interfaces\WorkflowItem[]
	 */
	public function getWorkflowInputItems();

	/**
	 * @return \Change\Workflow\Interfaces\WorkflowItem[]
	 */
	public function getWorkflowOutputItems();
}