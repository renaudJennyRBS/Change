<?php
namespace Change\User;

/**
 * @name \Change\User\ProfileInterface
 */
interface ProfileInterface
{

	public function getName();

	/**
	 * @return string[]
	 */
	public function getPropertyNames();

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getPropertyValue($name);

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function setPropertyValue($name, $value);
}