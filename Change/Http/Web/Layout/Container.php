<?php
namespace Change\Http\Web\Layout;


class Container extends Item
{
	/**
	 * @var integer
	 */
	protected $grid;

	/**
	 * @var string
	 */
	protected $gridMode;

	/**
	 * @return int
	 */
	public function getGrid()
	{
		return $this->grid;
	}

	/**
	 * @return string
	 */
	public function getGridMode()
	{
		return $this->gridMode;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return 'container';
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function initialize(array $data)
	{
		parent::initialize($data);
		$this->grid = $data['grid'];
		$this->gridMode = $data['gridMode'];
	}
}