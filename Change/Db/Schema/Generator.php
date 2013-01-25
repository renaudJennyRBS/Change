<?php
namespace Change\Db\Schema;

/**
 * @name \Change\Db\Schema\Generator
 */
class Generator
{
	/**
	 * @var \Change\Workspace
	 */
	protected $workspace;
	
	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;
	
	/**
	 * @param \Change\Workspace $workspace
	 * @param \Change\Db\DbProvider $dbProvider
	 */	
	public function __construct(\Change\Workspace $workspace, \Change\Db\DbProvider $dbProvider)
	{
		$this->workspace = $workspace;
		$this->dbProvider = $dbProvider;
	}
	
	public function generate()
	{
		$dbProvider = $this->dbProvider;
		$schemaManager = $dbProvider->getSchemaManager();

		if (!$schemaManager->check())
		{
			throw new \RuntimeException('unable to connect to database: '.$schemaManager->getName());
		}
		$dbType = ucfirst($dbProvider->getType());
		
		$dbSchemaClass = 'Change\\Db\\'.$dbType.'\\Schema';
		if (class_exists($dbSchemaClass))
		{
			$dbSchema = new $dbSchemaClass($schemaManager);
			if ($dbSchema instanceof \Change\Db\Schema\SchemaDefinition)
			{
				$dbSchema->generate();
			}
			else
			{
				throw new \RuntimeException('Class '.$dbSchemaClass.' must be a instance of : \Change\Db\Schema\SchemaDefinition');
			}
		}
		
		//@TODO Check Modules Specific Schema
		//$workspace = $this->workspace;
		
			
		if (class_exists('Compilation\Change\Documents\Schema'))
		{
			$documentSchema = new \Compilation\Change\Documents\Schema($schemaManager);
			if ($documentSchema instanceof \Change\Db\Schema\SchemaDefinition)
			{
				$documentSchema->generate();
			}
			else
			{
				throw new \RuntimeException('Class \Compilation\Change\Documents\Schema must be a instance of : \Change\Db\Schema\SchemaDefinition');
			}
		}
	}
}