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
	 * @var \Change\Db\SqlMapping
	 */
	protected $sqlMapping;
	
	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param string $compilationPath
	 * @return boolean
	 */
	public function savePHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Db\DbProvider $dbProvider, $compilationPath)
	{
		$code = $this->getPHPCode($compiler, $dbProvider);
		\Change\Stdlib\File::write(implode(DIRECTORY_SEPARATOR, array($compilationPath, 'Change', 'Documents', 'Schema.php')), $code);
		return true;
	}
	
	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return string
	 */
	public function getPHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Db\DbProvider $dbProvider)
	{
		$this->compiler = $compiler;
		$this->schemaManager = $dbProvider->getSchemaManager();
		$this->sqlMapping = $dbProvider->getSqlMapping();
		
		$defId =  $this->schemaManager->getDocumentFieldDefinition('id', 'Integer', null);
		$defId->setNullable(false)->setDefaultValue('0');
		
		$defModel =  $this->schemaManager->getDocumentFieldDefinition('model', 'String', 50);
		$defModel->setNullable(false)->setDefaultValue('');

		$defLCID =  $this->schemaManager->getDocumentFieldDefinition('lcid', 'String', 10);
		$defLCID->setNullable(false);
		

		
		$defRelOrder = $this->schemaManager->getDocumentFieldDefinition('relorder', 'Integer', null);
		$defRelOrder->setNullable(false)->setDefaultValue('0');
		
		$defRelatedId = $this->schemaManager->getDocumentFieldDefinition('relatedId', 'Integer', null);
		$defRelatedId->setNullable(false)->setDefaultValue('0');
		
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
		$id = '.$this->generateNewFieldDef($defId).';
		$model = '.$this->generateNewFieldDef($defModel).';
		$lcid = '.$this->generateNewFieldDef($defLCID).';
		$relOrder = '.$this->generateNewFieldDef($defRelOrder).';
		$relatedId = '.$this->generateNewFieldDef($defRelatedId).';'. PHP_EOL;
		
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
		$tn = $this->sqlMapping->getDocumentTableName($model->getName());

		$lines = array('', '');
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
			$tn = $this->sqlMapping->getDocumentI18nTableName($model->getName());
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
		
		$relNames = array();
		foreach (array_merge(array($model), $descendants) as $m)
		{
			/* @var $m \Change\Documents\Generators\Model */
			foreach ($m->getProperties() as $p)
			{
				/* @var $p \Change\Documents\Generators\Property */
				if ($p->getType() === 'DocumentArray')
				{
					$relNames[] = $this->escapePHPValue($p->getName());
				}
			}
		}
		
		if (count($relNames))
		{
			$lines[] = '';
			$tn = $this->sqlMapping->getDocumentRelationTableName($model->getName());
			$typeData = 'enum(' . implode(',', array_unique($relNames)). ')';
			$defRelName =  new \Change\Db\Schema\FieldDefinition('relname', 'enum', $typeData, true, null);	
			$lines[] = '		$relName = '.$this->generateNewFieldDef($defRelName).';';
			$lines[] = '		$table = new \Change\Db\Schema\TableDefinition('.$this->escapePHPValue($tn).');';
			$lines[] = '		$table->addField($id)->addField($relName)->addField($relOrder)->addField($relatedId);';
			$lines[] = '		$pk = new \Change\Db\Schema\KeyDefinition();';
			$lines[] = '		$table->addKey($pk->setPrimary(true)->addField($id)->addField($relName)->addField($relOrder));';
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
			$ca = $property->getConstraintArray();
			if ($property->getType() === 'String')
			{
				$typeSize = isset($ca['maxSize']['max']) ? $ca['maxSize']['max'] : 255;
			}
			else
			{
				$typeSize = null;
			}
			
			$def =  $this->schemaManager->getDocumentFieldDefinition($pn, $property->getType(), $typeSize);
			$lines[] = '		$table->addField(' . $this->generateNewFieldDef($def) .');';
		}
		return $lines;
	}
	
	/**
	 * @param \Change\Db\Schema\FieldDefinition $def
	 * @return string
	 */
	protected function generateNewFieldDef($def)
	{
		$name = $this->escapePHPValue($def->getName());
		$type = $this->escapePHPValue($def->getType());
		$typeData = $this->escapePHPValue($def->getTypeData());
		$nullable = $this->escapePHPValue($def->getNullable());
		$defaultValue = $this->escapePHPValue($def->getDefaultValue());
		return 'new \Change\Db\Schema\FieldDefinition('.$name.', '.$type.', '.$typeData.', '.$nullable.', '.$defaultValue.')';
	}
}