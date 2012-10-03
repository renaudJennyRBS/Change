<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\ModelClass
 */
class ModelClass
{
	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	public function getPHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Documents\Generators\Model $model)
	{
		return '';
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	public function getPHPModelCode($model)
	{
		$code = '<'. '?php' . PHP_EOL . 'namespace ' . $model->getPHPNameSpace() . ';' . PHP_EOL;
		$pm = $this->getParent($model);
		$extend = $pm ? $pm->getPHPModelClassName(true) : '\f_persistentdocument_PersistentDocumentModel';
		$code .= 'class ' . $model->getPHPModelClassName() . ' extends ' . $extend . PHP_EOL;
		$code .= '{'. PHP_EOL;
		$code .= $this->getPHPModelConstructor($model);
		if (count($model->getProperties()))
		{
			$code .= $this->getPHPModelLoadProperties($model);
		}
		if (count($model->getSerializedproperties()))
		{
			$code .= $this->getPHPModelLoadSerialisedProperties($model);
		}
		$code .= '}'. PHP_EOL;
		return $code;
	}
	
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getPHPModelConstructor($model)
	{
		$code = '
	protected function __construct()
	{
		parent::__construct();'. PHP_EOL;
		if ($model->getExtend())
		{
			$code .= '		$this->m_preservedPropertiesNames = array_merge($this->m_preservedPropertiesNames, ' . $this->getPHPEscape($model->evaluatePreservedPropertiesNames()) . ');'. PHP_EOL;
			if (!$model->getInject())
			{
				$code .= '		$this->m_parentName = ' . $this->getPHPEscape($model->getExtend()) . ';'. PHP_EOL;
			}
		}
		elseif (!$model->getInject())
		{
			$code .= '		$this->m_preservedPropertiesNames = ' . $this->getPHPEscape($model->evaluatePreservedPropertiesNames()) . ';'. PHP_EOL;
			$code .= '		$this->m_childrenNames = ' . $this->getPHPEscape(array_keys($this->getDescendants($model))) . ';'. PHP_EOL;
		}
		$code .= '	}'. PHP_EOL;
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getPHPModelLoadProperties($model)
	{
		$code = '
	protected function loadProperties()
	{
		parent::loadProperties();'. PHP_EOL;
		foreach ($model->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			$code .= '		$p = new \PropertyInfo('.$this->getPHPEscape($property->getName()).', '.$this->getPHPEscape($property->getType()).');'. PHP_EOL;
			$code .= '		$this->m_properties['.$this->getPHPEscape($property->getName()).'] = $p';
			if ($property->getRequired() !== null) {$code .= '->setRequired('.$this->getPHPEscape($property->getRequired()).')';}
			if ($property->getMinOccurs() !== null) {$code .= '->setMinOccurs('.$this->getPHPEscape($property->getMinOccurs()).')';}
			if ($property->getMaxOccurs() !== null) {$code .= '->setMaxOccurs('.$this->getPHPEscape($property->getMaxOccurs()).')';}
			if ($property->getDocumentType() !== null) {$code .= '->setDocumentType('.$this->getPHPEscape($property->getDocumentType()).')';}
			if ($property->getCascadeDelete() !== null) {$code .= '->setCascadeDelete('.$this->getPHPEscape($property->getCascadeDelete()).')';}
			if ($property->getTreeNode() !== null) {$code .= '->setTreeNode('.$this->getPHPEscape($property->getTreeNode()).')';}
			if ($property->getDefaultValue() !== null) {$code .= '->setDefaultValue('.$this->getPHPEscape($property->getDefaultValue(), false).')';}
			if ($property->getLocalized() !== null) {$code .= '->setLocalized('.$this->getPHPEscape($property->getLocalized()).')';}
			if ($property->getIndexed() !== null) {$code .= '->setIndexed('.$this->getPHPEscape($property->getIndexed()).')';}
			if ($property->getFromList() !== null) {$code .= '->setFromList('.$this->getPHPEscape($property->getFromList()).')';}
			if (is_array($property->getConstraintArray()) && count($property->getConstraintArray())) {$code .= '->setConstraints('.$this->getPHPEscape($property->getConstraintArray()).')';}
			$code .= ';'. PHP_EOL;
		}
		$code .= '	}'. PHP_EOL;
		return $code;
	}
	
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getPHPModelLoadSerialisedProperties($model)
	{
		$code = '
	protected function loadSerialisedProperties()
	{
		parent::loadSerialisedProperties();'. PHP_EOL;
		foreach ($model->getSerializedproperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\SerializedProperty */
			$code .= '		$p = new \PropertyInfo('.$this->getPHPEscape($property->getName()).', '.$this->getPHPEscape($property->getType()).');'. PHP_EOL;
			$code .= '		$this->m_serialisedproperties['.$this->getPHPEscape($property->getName()).'] = $p';
			if ($property->getRequired() !== null) {$code .= '->setRequired('.$this->getPHPEscape($property->getRequired()).')';}
			if ($property->getMinOccurs() !== null) {$code .= '->setMinOccurs('.$this->getPHPEscape($property->getMinOccurs()).')';}
			if ($property->getMaxOccurs() !== null) {$code .= '->setMaxOccurs('.$this->getPHPEscape($property->getMaxOccurs()).')';}
			if ($property->getDocumentType() !== null) {$code .= '->setDocumentType('.$this->getPHPEscape($property->getDocumentType()).')';}
			if ($property->getDefaultValue() !== null) {$code .= '->setDefaultValue('.$this->getPHPEscape($property->getDefaultValue(), false).')';}
			if ($property->getLocalized() !== null) {$code .= '->setLocalized('.$this->getPHPEscape($property->getLocalized()).')';}
			if ($property->getIndexed() !== null) {$code .= '->setIndexed('.$this->getPHPEscape($property->getIndexed()).')';}
			if ($property->getFromList() !== null) {$code .= '->setFromList('.$this->getPHPEscape($property->getFromList()).')';}
			if (is_array($property->getConstraintArray()) && count($property->getConstraintArray())) {$code .= '->setConstraints('.$this->getPHPEscape($property->getConstraintArray()).')';}
			$code .= ';'. PHP_EOL;
		}
		$code .= '	}'. PHP_EOL;
		return $code;
	}
	
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getPHPModelLoadInverseProperties($model)
	{
		$code = '
	protected function loadInvertProperties()
	{
		parent::loadInvertProperties();'. PHP_EOL;
		foreach ($model->getInverseProperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\SerializedProperty */
			$code .= '		$p = new \PropertyInfo('.$this->getPHPEscape($property->getName()).', '.$this->getPHPEscape($property->getType()).');'. PHP_EOL;
			$code .= '		$this->m_invertProperties['.$this->getPHPEscape($property->getName()).'] = $p';
			if ($property->getRequired() !== null) {$code .= '->setRequired('.$this->getPHPEscape($property->getRequired()).')';}
			if ($property->getMinOccurs() !== null) {$code .= '->setMinOccurs('.$this->getPHPEscape($property->getMinOccurs()).')';}
			if ($property->getMaxOccurs() !== null) {$code .= '->setMaxOccurs('.$this->getPHPEscape($property->getMaxOccurs()).')';}
			if ($property->getDocumentType() !== null) {$code .= '->setDocumentType('.$this->getPHPEscape($property->getDocumentType()).')';}
			$code .= ';'. PHP_EOL;
		}
		$code .= '	}'. PHP_EOL;
		return $code;
	}
}