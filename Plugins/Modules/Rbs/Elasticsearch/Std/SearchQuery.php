<?php
namespace Rbs\Elasticsearch\Std;

/**
* @name \Rbs\Elasticsearch\Std\SearchQuery
*/
class SearchQuery
{
	/**
	 * @var \Rbs\Elasticsearch\Services\IndexManager
	 */
	protected $indexManager;

	/**
	 * @var \Rbs\Elasticsearch\Std\IndexDefinitionInterface
	 */
	protected $indexDefinition;

	/**
	 * @param \Rbs\Elasticsearch\Services\IndexManager $indexManager
	 * @param \Rbs\Elasticsearch\Std\IndexDefinitionInterface $indexDefinition
	 */
	function __construct($indexManager, $indexDefinition)
	{
		$this->indexManager = $indexManager;
		$this->indexDefinition = $indexDefinition;
	}

	/**
	 * @param \Rbs\Elasticsearch\Services\IndexManager $indexManager
	 * @return $this
	 */
	public function setIndexManager(\Rbs\Elasticsearch\Services\IndexManager $indexManager)
	{
		$this->indexManager = $indexManager;
		return $this;
	}

	/**
	 * @return \Rbs\Elasticsearch\Services\IndexManager
	 */
	public function getIndexManager()
	{
		return $this->indexManager;
	}

	/**
	 * @param \Rbs\Elasticsearch\Std\IndexDefinitionInterface $indexDefinition
	 * @return $this
	 */
	public function setIndexDefinition(\Rbs\Elasticsearch\Std\IndexDefinitionInterface $indexDefinition)
	{
		$this->indexDefinition = $indexDefinition;
		return $this;
	}

	/**
	 * @return \Rbs\Elasticsearch\Std\IndexDefinitionInterface
	 */
	public function getIndexDefinition()
	{
		return $this->indexDefinition;
	}



}