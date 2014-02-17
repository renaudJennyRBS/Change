<?php

namespace Rbs\Price\Collection;

/**
 * @name \Rbs\Price\Collection\IsoCurrency
 */
class IsoCurrency implements \Change\Collection\ItemInterface
{
	/**
	 * @var String
	 */
	protected $label;

	/**
	 * @var String
	 */
	protected $code;

	public function __construct($label, $code)
	{
		$this->label = $label;
		$this->code = $code;
	}
	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->label;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->code;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->code . ' -  ' . $this->label;
	}
}