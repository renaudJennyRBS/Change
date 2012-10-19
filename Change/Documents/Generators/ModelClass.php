<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\ModelClass
 */
class ModelClass
{
	/**
	 * @var \Change\Documents\Generators\Compiler
	 */
	protected $compiler;
	
	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Documents\Generators\Model $model
	 * @param string $compilationPath
	 * @return boolean
	 */	
	public function savePHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Documents\Generators\Model $model, $compilationPath)
	{
		$code = $this->getPHPCode($compiler, $model);
		$nsParts = explode('\\', $model->getNameSpace());
		$nsParts[] = $this->getClassName($model) . '.php';
		array_unshift($nsParts, $compilationPath);
		\Change\Stdlib\File::write(implode(DIRECTORY_SEPARATOR, $nsParts), $code);
		return true;
	}
		
	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	public function getPHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Documents\Generators\Model $model)
	{
		$this->compiler = $compiler;
		
		$code = '<'. '?php' . PHP_EOL . 'namespace Compilation\\' . $model->getNameSpace() . ';' . PHP_EOL;
		$pm = $compiler->getParent($model);
		$extend = $pm ? $this->getClassName($pm, true) : '\Change\Documents\AbstractModel';
		$code .= 'class ' . $this->getClassName($model) . ' extends ' . $extend . PHP_EOL;
		$code .= '{'. PHP_EOL;
		$code .= $this->getConstructor($model);
		if (count($model->getProperties()))
		{
			$code .= $this->getLoadProperties($model);
		}
		if (count($model->getSerializedproperties()))
		{
			$code .= $this->getLoadSerialisedProperties($model);
		}
		if (count($model->getInverseProperties()))
		{
			$code .= $this->getLoadInverseProperties($model);
		}
		$code .= $this->getOthersFunctions($model);
		
		$code .= $this->getWorkflowFunctions($model);
		
		$code .= '}'. PHP_EOL;
		
		$this->compiler = null;
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param string $className
	 * @return string
	 */
	protected function addNameSpace($model, $className)
	{
		return '\Compilation\\' . $model->getNameSpace() . '\\' . $className;
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
	 * @param \Change\Documents\Generators\Model $model
	 * @param boolean $withNameSpace
	 * @return string
	 */
	protected function getClassName($model, $withNameSpace = false)
	{
		$cn = ucfirst($model->getDocumentName()) . 'Model';
		return ($withNameSpace) ? $this->addNameSpace($model, $cn) :  $cn;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param boolean $withNameSpace
	 * @return string
	 */
	protected function getServiceClassName($model, $withNameSpace = false)
	{
		$cn = ucfirst($model->getDocumentName()) . 'Service';
		return ($withNameSpace) ? $this->addNameSpace($model, $cn) :  $cn;
	}
			
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getConstructor($model)
	{
		$code = '
	public function __construct()
	{
		parent::__construct();'. PHP_EOL;
		if ($model->getExtend() && !$model->getInject())
		{	
			$code .= '		$this->m_parentName = ' . $this->escapePHPValue($model->getExtend()) . ';'. PHP_EOL;
			$code .= '		$this->m_ancestorsNames[] = ' . $this->escapePHPValue($model->getExtend()) . ';'. PHP_EOL;
		}
		
		$childrenNames = array_keys($this->compiler->getDescendants($model, true));
		if (count($childrenNames))
		{
			$code .= '		$this->m_childrenNames = ' . $this->escapePHPValue($childrenNames) . ';'. PHP_EOL;
		}
		$code .= '	}'. PHP_EOL;
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getLoadProperties($model)
	{
		$code = '
	protected function loadProperties()
	{
		parent::loadProperties();'. PHP_EOL;
		foreach ($model->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			$affects = array();
			if ($property->getRequired() !== null) {$affects[] = '->setRequired('.$this->escapePHPValue($property->getRequired()).')';}
			if ($property->getMinOccurs() !== null) {$affects[] = '->setMinOccurs('.$this->escapePHPValue($property->getMinOccurs()).')';}
			if ($property->getMaxOccurs() !== null) {$affects[] = '->setMaxOccurs('.$this->escapePHPValue($property->getMaxOccurs()).')';}
			if ($property->getDocumentType() !== null) {$affects[] = '->setDocumentType('.$this->escapePHPValue($property->getDocumentType()).')';}
			if ($property->getCascadeDelete() !== null) {$affects[] = '->setCascadeDelete('.$this->escapePHPValue($property->getCascadeDelete()).')';}
			if ($property->getTreeNode() !== null) {$affects[] = '->setTreeNode('.$this->escapePHPValue($property->getTreeNode()).')';}
			if ($property->getDefaultValue() !== null) {$affects[] = '->setDefaultValue('.$this->escapePHPValue($property->getDefaultValue(), false).')';}
			if ($property->getLocalized() !== null) {$affects[] = '->setLocalized('.$this->escapePHPValue($property->getLocalized()).')';}
			if ($property->getIndexed() !== null) {$affects[] = '->setIndexed('.$this->escapePHPValue($property->getIndexed()).')';}
			if ($property->getFromList() !== null) {$affects[] = '->setFromList('.$this->escapePHPValue($property->getFromList()).')';}
			if (is_array($property->getConstraintArray()) && count($property->getConstraintArray())) {$affects[] = '->setConstraintArray('.$this->escapePHPValue($property->getConstraintArray()).')';}
				
			$escapeName = $this->escapePHPValue($property->getName());
			if (!$property->getOverride())
			{
				$code .= '		$this->m_properties['.$escapeName.'] = new \Change\Documents\Property('.$escapeName.', '.$this->escapePHPValue($property->getType()).');'. PHP_EOL;
			}
			if (count($affects))
			{
				$code .= '		$this->m_properties['.$escapeName.']' . implode('', $affects) . ';'. PHP_EOL;
			}
		}
		$code .= '	}'. PHP_EOL;
		return $code;
	}
	
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getLoadSerialisedProperties($model)
	{
		$code = '
	protected function loadSerialisedProperties()
	{
		parent::loadSerialisedProperties();'. PHP_EOL;
		foreach ($model->getSerializedproperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\SerializedProperty */
			$affects = array();
			if ($property->getRequired() !== null) {$affects[] = '->setRequired('.$this->escapePHPValue($property->getRequired()).')';}
			if ($property->getMinOccurs() !== null) {$affects[] = '->setMinOccurs('.$this->escapePHPValue($property->getMinOccurs()).')';}
			if ($property->getMaxOccurs() !== null) {$affects[] = '->setMaxOccurs('.$this->escapePHPValue($property->getMaxOccurs()).')';}
			if ($property->getDocumentType() !== null) {$affects[] = '->setDocumentType('.$this->escapePHPValue($property->getDocumentType()).')';}
			if ($property->getDefaultValue() !== null) {$affects[] = '->setDefaultValue('.$this->escapePHPValue($property->getDefaultValue(), false).')';}
			if ($property->getLocalized() !== null) {$affects[] = '->setLocalized('.$this->escapePHPValue($property->getLocalized()).')';}
			if ($property->getIndexed() !== null) {$affects[] = '->setIndexed('.$this->escapePHPValue($property->getIndexed()).')';}
			if ($property->getFromList() !== null) {$affects[] = '->setFromList('.$this->escapePHPValue($property->getFromList()).')';}
			if (is_array($property->getConstraintArray()) && count($property->getConstraintArray())) {$affects[] = '->setConstraintArray('.$this->escapePHPValue($property->getConstraintArray()).')';}
				

			$escapeName = $this->escapePHPValue($property->getName());
			if (!$property->getOverride())
			{
				$code .= '		$this->m_serialisedproperties['.$escapeName.'] = new \Change\Documents\Property('.$escapeName.', '.$this->escapePHPValue($property->getType()).');'. PHP_EOL;
			}
			if (count($affects))
			{
				$code .= '		$this->m_serialisedproperties['.$escapeName.']' . implode('', $affects) . ';'. PHP_EOL;
			}
		}
		$code .= '	}'. PHP_EOL;
		return $code;
	}
	
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getLoadInverseProperties($model)
	{
		$code = '
	protected function loadInvertProperties()
	{
		parent::loadInvertProperties();'. PHP_EOL;
		foreach ($model->getInverseProperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\InverseProperty */
			$affects = array();
			if ($property->getRequired() !== null) {$affects[] = '->setRequired('.$this->escapePHPValue($property->getRequired()).')';}
			if ($property->getMinOccurs() !== null) {$affects[] = '->setMinOccurs('.$this->escapePHPValue($property->getMinOccurs()).')';}
			if ($property->getMaxOccurs() !== null) {$affects[] = '->setMaxOccurs('.$this->escapePHPValue($property->getMaxOccurs()).')';}
			if ($property->getDocumentType() !== null) {$affects[] = '->setDocumentType('.$this->escapePHPValue($property->getDocumentType()).')';}		
			if ($property->getSrcName() !== null) {$affects[] = '->setRelationName('.$this->escapePHPValue($property->getSrcName()).')';}	
			
			$escapeName = $this->escapePHPValue($property->getName());
			if (!$property->getOverride())
			{
				$code .= '		$this->m_invertProperties['.$escapeName.'] = new \Change\Documents\Property('.$escapeName.', '.$this->escapePHPValue($property->getType()).');'. PHP_EOL;
			}
			if (count($affects))
			{
				$code .= '		$this->m_invertProperties['.$escapeName.']' . implode('', $affects) . ';'. PHP_EOL;
			}
		}
		$code .= '	}'. PHP_EOL;
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */	
	protected function getOthersFunctions($model)
	{	
		$code = '';
		
		if ($model->getIcon())
		{
			$code .= '
	/**
	 * @return string
	 */
	public function getIcon()
	{
		return '. $this->escapePHPValue($model->getIcon()).';
	}'. PHP_EOL;
		}
		
		if (!$model->getInject())
		{
			$code .= '
	/**
	 * @return string
	 */
	public function getName()
	{
		return '. $this->escapePHPValue($model->getFullName()).';
	}


	/**
	 * @return string
	 */
	public function getVendorName()
	{
		return '. $this->escapePHPValue($model->getVendor()).';
	}
			

	/**
	 * @return string
	 */
	public function getModuleName()
	{
		return '. $this->escapePHPValue($model->getModuleName()).';
	}

	/**
	 * @return string
	 */
	public function getDocumentName()
	{
		return '. $this->escapePHPValue($model->getDocumentName()).';
	}'. PHP_EOL;
			
		}		
		
		if ($model->getLocalized() !== null)
		{
			$code .= '
	/**
	 * @return boolean
	 */
	public function isLocalized()
	{
		return '. $this->escapePHPValue($model->getLocalized()).';
	}'. PHP_EOL;
		}		

		if ($model->getHasUrl() !== null)
		{
			$code .= '
	/**
	 * @return boolean
	 */
	public function hasURL()
	{
		return '. $this->escapePHPValue($model->getHasUrl()).';
	}'. PHP_EOL;
		}
		
		if ($model->getUseRewriteUrl() !== null)
		{
			$code .= '
	/**
	 * @return boolean
	 */
	public function useRewriteURL()
	{
		return '. $this->escapePHPValue($model->getUseRewriteUrl()).' && $this->hasURL();
	}'. PHP_EOL;
		}		
		
		if ($model->getIndexable() !== null)
		{
			$code .= '
	/**
	 * @return boolean
	 */
	public function isIndexable()
	{
		return '. $this->escapePHPValue($model->getIndexable()).' && $this->hasURL();
	}'. PHP_EOL;
		}
		
		if ($model->getBackofficeIndexable() !== null)
		{
			$code .= '
	/**
	 * @return boolean
	 */
	public function isBackofficeIndexable()
	{
		return '. $this->escapePHPValue($model->getBackofficeIndexable()).';
	}'. PHP_EOL;
		}
		
		if ($model->getUsePublicationDates() !== null)
		{
			$code .= '
	/**
	 * @return boolean
	 */
	public function usePublicationDates()
	{
		return '. $this->escapePHPValue($model->getUsePublicationDates()).';
	}'. PHP_EOL;
		}
		
		if ($model->getStatus() !== null)
		{
			$code .= '
	/**
	 * @return string
	 */
	public function getDefaultStatus()
	{
		return '. $this->escapePHPValue($model->getStatus()).';
	}'. PHP_EOL;
		}						
		return $code . PHP_EOL;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getWorkflowFunctions($model)
	{
		$code = '';
		
		if ($model->getUseCorrection() !== null)
		{
			$code .= '
	/**
	 * @return boolean
	 */
	public function useCorrection()
	{
		return '. $this->escapePHPValue($model->getUseCorrection()).';
	}'. PHP_EOL;
		}
		
		if ($model->getWorkflowStartTask() !== null)
		{
			$code .= '
	/**
	 * @return boolean
	 */
	public function hasWorkflow()
	{
		return '. $this->escapePHPValue(($model->getWorkflowStartTask() == true)).';
	}
		
	/**
	 * @return string
	 */
	public function getWorkflowStartTask()
	{
		return '. $this->escapePHPValue($model->getWorkflowStartTask()).';
	}'. PHP_EOL;
		}
		
		
		if ($model->getWorkflowParameters() !== null)
		{
			$wps = array();
			foreach ($model->getWorkflowParameters() as $name => $value)
			{
				$wps[] = $this->escapePHPValue($name) . ' => ' . $this->escapePHPValue($value, false);
			}
			$code .= '
	/**
	 * @return array
	 */
	public function getWorkflowParameters()
	{
		return array('. implode(', ', $wps).');
	}'. PHP_EOL;
		}
		
		return $code . PHP_EOL;		
	}
}