<?php
namespace Change\Presentation\Layout;

/**
 * @package Change\Presentation\Layout
 * @name \Change\Presentation\Layout\Block
 */
class Block extends Item
{

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return 'block';
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function initialize(array $data)
	{
		parent::initialize($data);
		$this->name = $data['name'];
	}
}