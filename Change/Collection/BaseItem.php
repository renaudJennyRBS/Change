<?php

namespace Change\Collection;


class BaseItem implements ItemInterface {

	/* @var $title string */
	protected $title;

	/* @var $title string */
	protected $value;

	/* @var $title string */
	protected $label;

	/**
	 * @param string $value
	 * @param string $label
	 * @param string $title
	 */
	function __construct($value, $label = null, $title = null)
	{
		$this->value = $value;
		$this->label = $label === null ? $this->value : $label;
		$this->title = $title === null ? $this->label : $title;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

}