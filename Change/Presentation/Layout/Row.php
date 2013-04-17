<?php
namespace Change\Presentation\Layout;

/**
 * @package Change\Presentation\Layout
 * @name \Change\Presentation\Layout\Row
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