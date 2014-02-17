<?php
namespace Rbs\Workflow\Std;

use Rbs\Workflow\Documents\Workflow;

/**
 * @name \Rbs\Workflow\Std\Place
 */
class Place implements \Change\Workflow\Interfaces\Place
{
	/**
	 * @var integer;
	 */
	protected $id;

	/**
	 * @var integer
	 */
	protected $type = self::TYPE_INTERMEDIATE;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var Workflow
	 */
	protected $workflow;

	/**
	 * @var Arc[]
	 */
	protected $arcs = array();

	/**
	 * @param Workflow $workflow
	 */
	function __construct($workflow)
	{
		$this->workflow = $workflow;
	}

	/**
	 * Return Place::TYPE_*
	 * @return integer
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Return Short name
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return Workflow
	 */
	public function getWorkflow()
	{
		return $this->workflow;
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return Arc[]
	 */
	public function getWorkflowInputItems()
	{
		return array_values(array_filter($this->arcs, function (\Change\Workflow\Interfaces\Arc $arc)
		{
			return $arc->getDirection() === \Change\Workflow\Interfaces\Arc::DIRECTION_TRANSITION_TO_PLACE;
		}));
	}

	/**
	 * @return Arc[]
	 */
	public function getWorkflowOutputItems()
	{
		return array_values(array_filter($this->arcs, function (\Change\Workflow\Interfaces\Arc $arc)
		{
			return $arc->getDirection() === \Change\Workflow\Interfaces\Arc::DIRECTION_PLACE_TO_TRANSITION;
		}));
	}

	/**
	 * @param Arc[] $arcs
	 * @return $this
	 */
	public function setArcs($arcs)
	{
		$this->arcs = $arcs;
		return $this;
	}

	/**
	 * @return integer[]
	 */
	public function getArcIds()
	{
		if (is_array($this->arcs))
		{
			return array_map(function (Arc $arc)
			{
				return $arc->getId();
			}, $this->arcs);
		}
		return array();
	}

	/**
	 * @param integer $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @param int $type
	 * @return $this
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}

	//Design Function

	/**
	 * @param Arc $arc
	 * @return $this
	 */
	public function addArc(Arc $arc)
	{
		if (!in_array($arc, $this->arcs, true))
		{
			$this->arcs[] = $arc;
		}
		return $this;
	}
}