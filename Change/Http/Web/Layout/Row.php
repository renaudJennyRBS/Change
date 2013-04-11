<?php
namespace Change\Http\Web\Layout;

/**
 * @name \Change\Http\Web\Layout\Row
 * @package Change\Http\Web\Layout
 */
class Row extends Item
{
	/**
	 * @var integer
	 */
	protected $grid;

	/**
	 * @return string
	 */
	public function getType()
	{
		return 'row';
	}

	/**
	 * @param integer $grid
	 */
	public function setGrid($grid)
	{
		$this->grid = $grid;
	}

	/**
	 * @return integer
	 */
	public function getGrid()
	{
		return $this->grid;
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function initialize(array $data)
	{
		parent::initialize($data);
		$this->grid = $data['grid'];
	}
}