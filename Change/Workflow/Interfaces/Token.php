<?php
namespace Change\Workflow\Interfaces;

/**
* @name \Change\Workflow\Interfaces\Token
*/
interface Token extends InstanceItem
{
	const STATUS_FREE = 'FREE';
	const STATUS_LOCKED = 'LOCK';
	const STATUS_CONSUMED = 'CONS';
	const STATUS_CANCELLED = 'CANC';

	/**
	 * @return \Change\Workflow\Interfaces\Place
	 */
	public function getPlace();

	/**
	 * Return \Change\Workflow\Interfaces\Token::STATUS_*
	 * @return string
	 */
	public function getStatus();

	/**
	 * @return \ArrayObject
	 */
	public function getContext();

	/**
	 * @return \DateTime|null
	 */
	public function getEnabledDate();

	/**
	 * @return \DateTime|null
	 */
	public function getCanceledDate();

	/**
	 * @return \DateTime|null
	 */
	public function getConsumedDate();

	/**
	 * @return \DateTime|null
	 */
	public function enable($dateTime);

	/**
	 * @return \DateTime|null
	 */
	public function consume($dateTime);
}