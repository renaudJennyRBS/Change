<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\SchemaClass
 */
class SchemaClass
{
	/**
	 * @var \Change\Documents\Generators\Compiler
	 */
	protected $compiler;
	
	/**
	 * @var \Change\Db\InterfaceSchemaManager
	 */
	protected $schemaManager;
	
	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Db\InterfaceSchemaManager $schemaManager
	 * @param string $compilationPath
	 * @return boolean
	 */
	public function savePHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Db\InterfaceSchemaManager $schemaManager, $compilationPath)
	{
		$code = $this->getPHPCode($compiler, $schemaManager);
		\Change\Stdlib\File::write(implode(DIRECTORY_SEPARATOR, array($compilationPath, 'Change', 'Documents', 'Schema.php')), $code);
		return true;
	}
	
	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Db\InterfaceSchemaManager $schemaManager
	 * @return string
	 */
	public function getPHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Db\InterfaceSchemaManager $schemaManager)
	{
		$this->compiler = $compiler;
		$this->schemaManager = $schemaManager;
		$code = '<'. '?php
namespace Compilation\\Change\\Documents;
class Schema
{
	/**
	 * @var \Change\Db\Schema\TableDefinition[]
	 */
	protected $tables = array();
			
	/**
	 * @return \Change\Db\Schema\TableDefinition[]
	 */
	public function getTables()
	{
		return $this->tables;
	}
	
	public function __construct()
	{
		$id = new \Change\Db\Schema\FieldDefinition(\'document_id\', \'int\', \'int(11)\', false, \'0\');
		$model = new \Change\Db\Schema\FieldDefinition(\'document_model\', \'varchar\', \'varchar(50)\', false, \'\');
		$lcid = new \Change\Db\Schema\FieldDefinition(\'lcid\', \'varchar\', \'varchar(10)\', true, NULL);'. PHP_EOL;
		
		foreach ($this->compiler->getModelsByLevel(0) as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			$descendants = $this->compiler->getDescendants($model);
			$code .= $this->generateTableDef($model, $descendants);
		}	
		$code .= '
	}
}'. PHP_EOL;
		
		$this->compiler = null;
		$this->schemaManager = null;
		return $code;
	}
	
	/**
	 * @param mixed $value
	 * @return string
	 */
	protected function escapePHPValue($value, $removeSpace = true)
	{
		if ($removeSpace)
		{
			return str_replace(array(PHP_EOL, ' ', "\t"), '', var_export($value, true));
		}
		return var_export($value, true);
	}
	
	/**
	 * 
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Model[] $descendants
	 * @return string
	 */
	protected function generateTableDef($model, $descendants)
	{ 
		$tn = $this->schemaManager->getDocumentTableName($model->getName());

		$lines = array();
		$lines[] = '';
		$lines[] = '		$table = new \Change\Db\Schema\TableDefinition('.$this->escapePHPValue($tn).');';
		$lines[] = '		$table->addField($id)->addField($model);';
		$lines = array_merge($lines, $this->generateFieldsDef($model, false));
		foreach ($descendants as $dm)
		{
			/* @var $dm \Change\Documents\Generators\Model */
			$lines = array_merge($lines, $this->generateFieldsDef($dm, false));
		}
		$lines[] = '		$pk = new \Change\Db\Schema\KeyDefinition();';
		$lines[] = '		$table->addKey($pk->setPrimary(true)->addField($id));';
		$lines[] = '		$this->tables[] = $table;';
		
		if ($model->checkLocalized())
		{
			$lines[] = '';
			$tn = $this->schemaManager->getDocumentI18nTableName($model->getName());
			$lines[] = '		$table = new \Change\Db\Schema\TableDefinition('.$this->escapePHPValue($tn).');';
			$lines[] = '		$table->addField($id)->addField($lcid);';
			$lines = array_merge($lines, $this->generateFieldsDef($model, true));
			foreach ($descendants as $dm)
			{
				/* @var $dm \Change\Documents\Generators\Model */
				$lines = array_merge($lines, $this->generateFieldsDef($dm, true));
			}
			$lines[] = '		$pk = new \Change\Db\Schema\KeyDefinition();';
			$lines[] = '		$table->addKey($pk->setPrimary(true)->addField($id)->addField($lcid));';
			$lines[] = '		$this->tables[] = $table;';
		}
		return implode(PHP_EOL, $lines);
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param boolean $localized
	 * @return string[]
	 */
	protected function generateFieldsDef($model, $localized)
	{
		$lines = array();
		foreach ($model->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getParent() !== null || $property->getLocalized() != $localized)
			{
				continue;
			}

			$pn = $property->getName();
			$def =  $this->schemaManager->getDocumentFieldDefinition($pn, $property->getType(), $property->getDbSize());
			$name = $this->escapePHPValue($def->getName());
			$type = $this->escapePHPValue($def->getType());
			$typeData = $this->escapePHPValue($def->getTypeData());
			$nullable = $this->escapePHPValue($def->getNullable());
			$defaultValue = $this->escapePHPValue($def->getDefaultValue());
			$lines[] = '		$table->addField(new \Change\Db\Schema\FieldDefinition('.$name.', '.$type.', '.$typeData.', '.$nullable.', '.$defaultValue.'));';
		}
		return $lines;
	}
}