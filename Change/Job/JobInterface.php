<?php
namespace Change\Job;

/**
* @name \Change\Job\JobInterface
*/
interface JobInterface
{
	const STATUS_WAITING = 'waiting';
	const STATUS_SUCCESS = 'success';
	const STATUS_FAILED = 'failed';
	const STATUS_RUNNING = 'running';

	/**
	 * @return string
	 */
	public function getStatus();

	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * @return array
	 */
	public function getArguments();

	/**
	 * @param string $name
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	public function getArgument($name, $defaultValue = null);

	/**
	 * @return \DateTime
	 */
	public function getStartDate();

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return \DateTime|null
	 */
	public function getLastModificationDate();

}