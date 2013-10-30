<?php
namespace Rbs\Elasticsearch\Facet;

/**
 * @name \Rbs\Elasticsearch\Facet\ModelFacetDefinition
 */
class ModelFacetDefinition extends  AbstractFacetDefinition
{
	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @return integer
	 */
	public function getId()
	{
		return -1;
	}

	/**
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}
}