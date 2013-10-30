<?php
namespace Rbs\Elasticsearch\Index;

/**
* @name \Rbs\Elasticsearch\Std\SearchQuery
*/
class SearchQuery
{
	/**
	 * @var \Rbs\Elasticsearch\Index\IndexManager
	 */
	protected $indexManager;

	/**
	 * @var \Rbs\Elasticsearch\Index\IndexDefinitionInterface
	 */
	protected $indexDefinition;

	/**
	 * @param \Rbs\Elasticsearch\Index\IndexManager $indexManager
	 * @param \Rbs\Elasticsearch\Index\IndexDefinitionInterface $indexDefinition
	 */
	function __construct($indexManager, $indexDefinition)
	{
		$this->indexManager = $indexManager;
		$this->indexDefinition = $indexDefinition;
	}

	/**
	 * @param \Rbs\Elasticsearch\Index\IndexManager $indexManager
	 * @return $this
	 */
	public function setIndexManager(\Rbs\Elasticsearch\Index\IndexManager $indexManager)
	{
		$this->indexManager = $indexManager;
		return $this;
	}

	/**
	 * @return \Rbs\Elasticsearch\Index\IndexManager
	 */
	public function getIndexManager()
	{
		return $this->indexManager;
	}

	/**
	 * @param \Rbs\Elasticsearch\Index\IndexDefinitionInterface $indexDefinition
	 * @return $this
	 */
	public function setIndexDefinition(\Rbs\Elasticsearch\Index\IndexDefinitionInterface $indexDefinition)
	{
		$this->indexDefinition = $indexDefinition;
		return $this;
	}

	/**
	 * @return \Rbs\Elasticsearch\Index\IndexDefinitionInterface
	 */
	public function getIndexDefinition()
	{
		return $this->indexDefinition;
	}



}