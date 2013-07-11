<?php
namespace Change\Workflow\Interfaces;

/**
 * @name \Change\Workflow\Interfaces\WorkflowInstance
 */
interface WorkflowInstance
{
	const STATUS_OPEN = 'OP';
	const STATUS_CLOSED = 'CL';
	const STATUS_SUSPENDED = 'SU';
	const STATUS_CANCELED = 'CA';

	/**
	 * Return unique Identifier
	 * @return string
	 */
	public function getId();

	/**
	 * @return \Change\Workflow\Interfaces\Workflow
	 */
	public function getWorkflow();

	/**
	 * @return \Change\Documents\AbstractDocument|null
	 */
	public function getDocument();

	/**
	 * Return all Workflow instance Items defined
	 * @return \Change\Workflow\Interfaces\InstanceItem[]
	 */
	public function getItems();

	/**
	 * @return \ArrayObject
	 */
	public function getContext();

	/**
	 * Return \Change\Workflow\Interfaces\WorkflowInstance::STATUS_*
	 * @return string
	 */
	public function getStatus();

	/**
	 * @return \DateTime|null
	 */
	public function getStartDate();

	/**
	 * @return \DateTime|null
	 */
	public function getEndDate();

	/**
	 * @param \Change\Workflow\Interfaces\Place $place
	 * @return \Change\Workflow\Interfaces\Token
	 */
	public function createToken($place);

	/**
	 * @param \Change\Workflow\Interfaces\Transition $transition
	 * @return \Change\Workflow\Interfaces\WorkItem
	 */
	public function createWorkItem($transition);

	/**
	 * @param array $context
	 * @throws \RuntimeException
	 */
	public function start($context);

	/**
	 * @param string $taskId
	 * @param array $context
	 * @throws \RuntimeException
	 */
	public function process($taskId, $context);

	/**
	 * @param \DateTime $date
	 * @return void
	 */
	public function cancel(\DateTime $date = null);

	/**
	 * @param \DateTime $date
	 * @return void
	 */
	public function close(\DateTime $date = null);

	/**
	 * @param boolean $suspend
	 * @return void
	 */
	public function suspend($suspend);
}