<?php
namespace Rbs\Workflow\Std;

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
	 * @var \Rbs\Workflow\Documents\Workflow
	 */
	protected $workflow;

	/**
	 * @var \Change\Workflow\Interfaces\Arc[]
	 */
	protected $arcs = array();

	/**
	 * @param \Rbs\Workflow\Documents\Workflow $workflow
	 */
	function __construct($workflow)
	{
		$this->workflow = $workflow;
	}

	/**
	 * Return \Change\Workflow\Interfaces\Place::TYPE_*
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
	 * @return \Change\Workflow\Interfaces\Workflow
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
	 * @return \Change\Workflow\Interfaces\WorkflowItem[]
	 */
	public function getWorkflowInputItems()
	{
		return array_values(array_filter($this->arcs, function (\Change\Workflow\Interfaces\Arc $arc)
		{
			return $arc->getDirection() === \Change\Workflow\Interfaces\Arc::DIRECTION_TRANSITION_TO_PLACE;
		}));
	}

	/**
	 * @return \Change\Workflow\Interfaces\WorkflowItem[]
	 */
	public function getWorkflowOutputItems()
	{
		return array_values(array_filter($this->arcs, function (\Change\Workflow\Interfaces\Arc $arc)
		{
			return $arc->getDirection() === \Change\Workflow\Interfaces\Arc::DIRECTION_PLACE_TO_TRANSITION;
		}));
	}

	/**
	 * @param \Change\Workflow\Interfaces\Arc[] $arcs
	 * @return $this
	 */
	public function setArcs($arcs)
	{
		$this->arcs = $arcs;
		return $this;
	}

	public function getArcIds()
	{
		if (is_array($this->arcs))
		{
			return array_map(function (\Change\Workflow\Interfaces\Arc $arc)
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
	 * @param \Change\Workflow\Interfaces\Arc $arc
	 * @return $this
	 */
	public function addArc($arc)
	{
		if (!in_array($arc, $this->arcs, true))
		{
			$this->arcs[] = $arc;
		}
		return $this;
	}
}