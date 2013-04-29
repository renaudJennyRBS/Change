<?php
namespace Change\Http\Rest\Result;

/**
 * @name \Change\Http\Rest\Result\ArrayResult
 */
class ArrayResult extends \Change\Http\Result
{
	/**
	 * @var array
	 */
	protected $array;

	/**
	 * @param array $array
	 */
	public function setArray($array)
	{
		$this->array = $array;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		if (is_array($this->array))
		{
			return $this->array;
		}
		return array();
	}

	/**
	 * @return string
	 */
	function __toString()
	{
		return json_encode($this->toArray());
	}
}