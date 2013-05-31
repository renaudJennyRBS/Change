<?php
namespace Change\Workflow\Interfaces;

/**
 * @name \Change\Workflow\Interfaces\InstanceItem
 */
interface InstanceItem
{
	/**
	 * @return \Change\Workflow\Interfaces\WorkflowInstance
	 */
	public function getWorkflowInstance();
}