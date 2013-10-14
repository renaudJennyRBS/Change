<?php
namespace Rbs\Elasticsearch\Std;


/**
* @name \Rbs\Elasticsearch\Std\ModelFacetDefinition
*/
class ModelFacetDefinition extends  AbstractFacetDefinition
{
	/**
	 * @var string
	 */
	protected $title;

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