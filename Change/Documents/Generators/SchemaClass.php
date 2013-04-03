<?php
namespace Change\Documents\Generators;

use Change\Db\DbProvider;

/**
 * @name \Change\Documents\Generators\SchemaClass
 */
class SchemaClass
{
	/**
	 * @var Compiler
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
	 * @param Compiler $compiler
	 * @param DbProvider $dbProvider
	 * @param string $compilationPath
	 * @return boolean
	 */
	public function savePHPCode(Compiler $compiler, DbProvider $dbProvider, $compilationPath)
	{
		$code = $this->getPHPCode($compiler, $dbProvider);
		\Change\Stdlib\File::write(implode(DIRECTORY_SEPARATOR, array($compilationPath, 'Change', 'Documents', 'Schema.php')), $code);
		return true;
	}
	
	/**
	 * @param Compiler $compiler
	 * @param DbProvider $dbProvider
	 * @return string
	 */
	public function getPHPCode(Compiler $compiler, DbProvider $dbProvider)
	{
		$this->compiler = $compiler;
		$this->schemaManager = $dbProvider->getSchemaManager();
		$this->sqlMapping = $dbProvider->getSqlMapping();
		
		$code = '<' . '?php
namespace Compilation\\Change\\Documents;
		
/**
 * @name \\Compilation\\Change\\Documents\\Schema
 */
class Schema extends \\Change\\Db\\Schema\\SchemaDefinition
{
	/**
	 * @var \Change\Db\Schema\TableDefinition[]
	 */
	protected $tables;
		
	/**
	 * @return \Change\Db\Schema\TableDefinition[]
	 */
	public function getTables()
	{
		if ($this->tables === null)
		{
			$schemaManager = $this->getSchemaManager();
			$idDef = $schemaManager->newIntegerFieldDefinition('.$this->escapePHPValue($this->sqlMapping->getDocumentFieldName('id')).')->setDefaultValue(\'0\')->setNullable(false);
			$modelDef = $schemaManager->newVarCharFieldDefinition('.$this->escapePHPValue($this->sqlMapping->getDocumentFieldName('model')).', array(\'length\' => 50))->setDefaultValue(\'\')->setNullable(false);
			$lcidDef = $schemaManager->newVarCharFieldDefinition('.$this->escapePHPValue($this->sqlMapping->getDocumentFieldName('LCID')).', array(\'length\' => 5))->setDefaultValue(\'\')->setNullable(false);
		
			$relOrderDef = $schemaManager->newIntegerFieldDefinition(\'relorder\')->setDefaultValue(\'0\')->setNullable(false);
			$relatedIdDef = $schemaManager->newIntegerFieldDefinition(\'relatedid\')->setDefaultValue(\'0\')->setNullable(false);' . PHP_EOL;
		
		foreach ($this->compiler->getModelsByLevel(0) as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			if ($model->getStateless()) {continue;}
			$descendants = $this->compiler->getDescendants($model);
			$this->completeDbOptions($model, $descendants, $this->schemaManager);
			$code .= $this->generateTableDef($model, $descendants);
		}

		$treeNames = array();
		foreach ($this->compiler->getModels() as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			if (is_string($treeName = $model->getTreeName()))
			{
				if (!in_array($treeName, $treeNames))
				{
					$treeNames[] = $treeName;
					$tnEsc = $this->escapePHPValue($this->sqlMapping->getTreeTableName($treeName));
					$code .=	'
			$this->tables['.$tnEsc.'] = $tableDef = $schemaManager->newTableDefinition('.$tnEsc.')
				->addField($schemaManager->newIntegerFieldDefinition(\'document_id\')->setNullable(false)->setDefaultValue(\'0\'))
				->addField($schemaManager->newIntegerFieldDefinition(\'parent_id\')->setNullable(false)->setDefaultValue(\'0\'))
				->addField($schemaManager->newIntegerFieldDefinition(\'node_order\')->setNullable(false)->setDefaultValue(\'0\'))
				->addField($schemaManager->newIntegerFieldDefinition(\'node_level\')->setNullable(false)->setDefaultValue(\'0\'))
				->addField($schemaManager->newVarCharFieldDefinition(\'node_path\')->setNullable(false)->setDefaultValue(\'\'))
				->addField($schemaManager->newIntegerFieldDefinition(\'children_count\')->setNullable(false)->setDefaultValue(\'0\'));
			$tableDef->addKey($this->newPrimaryKey()->addField($tableDef->getField(\'document_id\')));
			$tableDef->addKey($this->newIndexKey()->setName(\'tree_node\')->addField($tableDef->getField(\'parent_id\'))->addField($tableDef->getField(\'node_order\')));'.PHP_EOL;
				}
			}
		}

		$code .= '
		}
		return $this->tables;
	}
}' . PHP_EOL;
		
		$this->compiler = null;
		$this->schemaManager = null;
		return $code;
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Model[] $descendants
	 */
	protected function completeDbOptions($model, $descendants)
	{
		$schemaManager = $this->schemaManager;
		$sqlMapping = $this->sqlMapping;

		foreach ($model->getProperties() as $propertyName => $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getDbOptions() !== null)
			{
				$type = $property->getComputedType();
				$dbType = $sqlMapping->getDbScalarType($type);
				$property->setDbOptions($schemaManager->getFieldDbOptions($dbType, $property->getDbOptions()));
			}
		}

		foreach($descendants as $model)
		{
			foreach ($model->getProperties() as $propertyName => $property)
			{
				/* @var $property \Change\Documents\Generators\Property */
				if ($property->getDbOptions() !== null)
				{
					$type = $property->getComputedType();
					$dbType = $sqlMapping->getDbScalarType($type);
					$dbOptions = $schemaManager->getFieldDbOptions($dbType, $property->getDbOptions());
					if ($property->getParent() === null)
					{
						$property->setDbOptions($dbOptions);
					}
					else
					{
						$property = $property->getRoot();
						$property->setDbOptions($schemaManager->getFieldDbOptions($dbType, $dbOptions, $property->getDbOptions()));
					}
				}
			}
		}
	}

	/**
	 * @param string $value
	 * @param bool $removeSpace
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
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Model[] $descendants
	 * @return string
	 */
	protected function generateTableDef($model, $descendants)
	{
		$lines = array('');
		$tnEsc = $this->escapePHPValue($this->sqlMapping->getDocumentTableName($model->getName()));
		$lines[] = '			$this->tables['.$tnEsc.'] = $schemaManager->newTableDefinition('.$tnEsc.')';
		$lines[] = '				->addField($idDef)->addField($modelDef)';

		foreach ($this->addDefFields($model, false) as $line)
		{
			$lines[] = '				->addField(' . $line .')';
		}

		foreach ($descendants as $dm)
		{
			foreach ($this->addDefFields($dm, false) as $line)
			{
				$lines[] = '				->addField(' . $line .')';
			}
		}
		$lines[] = '				->addKey($this->newPrimaryKey()->addField($idDef));';

		if ($model->checkLocalized())
		{
			$tnEsc = $this->escapePHPValue($this->sqlMapping->getDocumentI18nTableName($model->getName()));
			$lines[] = '			$this->tables['.$tnEsc.'] = $schemaManager->newTableDefinition('.$tnEsc.')';
			$lines[] = '				->addField($idDef)->addField($lcidDef)';						
			foreach ($this->addDefFields($model, true) as $line)
			{
				$lines[] = '				->addField(' . $line .')';
			}
	
			foreach ($descendants as $dm)
			{
				foreach ($this->addDefFields($dm, true) as $line)
				{
					$lines[] = '				->addField(' . $line .')';
				}
			}
			$lines[] = '				->addKey($this->newPrimaryKey()->addField($idDef)->addField($lcidDef));';
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
					$relNames[] = $p->getName();
				}
			}
		}
		
		if (count($relNames))
		{
			$relNames = array_values(array_unique($relNames));
			$lines[] = '			$relNames ='.$this->escapePHPValue($relNames).';';
			$lines[] = '			$relNameDef = $schemaManager->newEnumFieldDefinition(\'relname\', array(\'VALUES\' => $relNames))->setDefaultValue($relNames[0])->setNullable(false);';

			
			$tnEsc = $this->escapePHPValue($this->sqlMapping->getDocumentRelationTableName($model->getName()));
			$lines[] = '			$this->tables['.$tnEsc.'] = $schemaManager->newTableDefinition('.$tnEsc.')';
			$lines[] = '				->addField($idDef)->addField($relNameDef)->addField($relOrderDef)->addField($relatedIdDef)';
			$lines[] = '				->addKey($this->newPrimaryKey()->addField($idDef)->addField($relNameDef)->addField($relOrderDef));';			
		}
		return implode(PHP_EOL, $lines);
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param boolean $localized
	 * @return string[]
	 */
	protected function addDefFields($model, $localized)
	{
		$fields = array();
		foreach ($model->getProperties() as $propertyName => $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getParent() !== null || $property->getStateless() || $property->getLocalized() != $localized)
			{
				continue;
			}
			elseif ($localized && $propertyName === 'LCID')
			{
				continue;
			}
				
			$fnEsc = $this->escapePHPValue($this->sqlMapping->getDocumentFieldName($propertyName));
				
			if ($propertyName === 'publicationStatus')
			{
				$fd = '$schemaManager->newEnumFieldDefinition('.$fnEsc.', array(\'VALUES\' => array(\'DRAFT\',\'VALIDATION\',\'PUBLISHABLE\',\'UNPUBLISHABLE\',\'DEACTIVATED\',\'FILED\')))';
			}
			else
			{
				$propertyType = $property->getType();
				$dbOptionsEsc = $this->escapePHPValue($property->getDbOptions());
				switch ($propertyType)
				{
					case 'Document' :
					case 'Integer' :
					case 'DocumentId' :
						$fd = '$schemaManager->newIntegerFieldDefinition('.$fnEsc.', '. $dbOptionsEsc .')';
						break;
					case 'DocumentArray' :
						$fd = '$schemaManager->newIntegerFieldDefinition('.$fnEsc.', '. $dbOptionsEsc .')->setDefaultValue(\'0\')';
						break;
					case 'String' :
						$fd = '$schemaManager->newVarCharFieldDefinition('.$fnEsc.', '. $dbOptionsEsc .')';
						break;
					case 'LongString' :
					case 'XML' :
					case 'RichText' :
					case 'JSON' :
						$fd = '$schemaManager->newTextFieldDefinition('.$fnEsc.', '. $dbOptionsEsc .')';
						break;
					case 'Lob' :
					case 'Object' :
						$fd = '$schemaManager->newLobFieldDefinition('.$fnEsc.', '. $dbOptionsEsc .')';
						break;
					case 'Boolean' :
						$fd = '$schemaManager->newBooleanFieldDefinition('.$fnEsc.', '. $dbOptionsEsc .')->setDefaultValue(\'0\')';
						break;
					case 'Date' :
					case 'DateTime' :
						$fd = '$schemaManager->newDateFieldDefinition('.$fnEsc.', '. $dbOptionsEsc .')';
						break;
					case 'Float' :
						$fd = '$schemaManager->newFloatFieldDefinition('.$fnEsc.', '. $dbOptionsEsc .')';
						break;
					case 'Decimal' :
						$fd = '$schemaManager->newNumericFieldDefinition('.$fnEsc.', '. $dbOptionsEsc .')';
						break;
					default:
						throw new \RuntimeException('Invalid property type: ' . $propertyType, 54029);
				}
			}
			$fields[] = $fd;
		}
		return $fields;
	}
}