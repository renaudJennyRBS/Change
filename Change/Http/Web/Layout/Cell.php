<?php
namespace Change\Http\Web\Layout;

class Cell extends Item
{
	/**
	 * @var integer
	 */
	protected $size;

	/**
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return 'cell';
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function initialize(array $data)
	{
		parent::initialize($data);
		$this->size = $data['size'];
	}
}