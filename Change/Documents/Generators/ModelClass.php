<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\ModelClass
 * @api
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
		$nsParts[] = $model->getShortModelClassName() . '.php';
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
		
		$code = '<'. '?php' . PHP_EOL . 'namespace ' . $model->getCompilationNameSpace() . ';' . PHP_EOL;
		$extendModel = $model->getExtendedModel();
		$extend = $extendModel ? $extendModel->getModelClassName() : '\Change\Documents\AbstractModel';
		$code .= '
/**
 * @name '.$model->getModelClassName().'
 */
 class ' . $model->getShortModelClassName() . ' extends ' . $extend . PHP_EOL;
		$code .= '{'. PHP_EOL;
		$code .= $this->getConstructor($model);
		if (count($model->getProperties()))
		{
			$code .= $this->getLoadProperties($model);
		}
		if (count($model->getInverseProperties()))
		{
			$code .= $this->getLoadInverseProperties($model);
		}
		$code .= $this->getOthersFunctions($model);
			
		$code .= '}'. PHP_EOL;
		
		$this->compiler = null;
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
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getConstructor($model)
	{
		$code = '
	public function __construct(\\Change\\Documents\\ModelManager $modelManager)
	{
		parent::__construct($modelManager);'. PHP_EOL;
		if ($model->getExtends() && !$model->getReplace())
		{	
			$code .= '		$this->ancestorsNames[] = ' . $this->escapePHPValue($model->getExtends()) . ';'. PHP_EOL;
		}
		
		$descendantsNames = array_keys($this->compiler->getDescendants($model, true));
		if (!$model->getReplace())
		{
			$code .= '		$this->descendantsNames = ' . $this->escapePHPValue($descendantsNames) . ';'. PHP_EOL;
			$code .= '		$this->vendorName = ' . $this->escapePHPValue($model->getVendor()) . ';'. PHP_EOL;
			$code .= '		$this->shortModuleName = ' . $this->escapePHPValue($model->getShortModuleName()) . ';'. PHP_EOL;
			$code .= '		$this->shortName = ' . $this->escapePHPValue($model->getShortName()) . ';'. PHP_EOL;
			$code .= '		$this->injectedBy = null;'. PHP_EOL;
		}
		else
		{
			$code .= '		$this->injectedBy = ' . $this->escapePHPValue($model->getName()) . ';'. PHP_EOL;
		}

		if ($model->getTreeName() !== null)
		{
			$code .= '		$this->treeName = ' . $this->escapePHPValue($model->getTreeName()) . ';'. PHP_EOL;
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
			$code .= '		$p = $this->properties['.$this->escapePHPValue($property->getName()).']';
			if ($property->getParent())
			{
				$code .= ';'. PHP_EOL;
			}
			else
			{
				$code .= ' = new \Change\Documents\Property('.$this->escapePHPValue($property->getName()).', '.$this->escapePHPValue($property->getType()).');'. PHP_EOL;
			}
			 
			$affects = array();
			if ($property->getStateless() !== null) {$affects[] = '->setStateless('.$this->escapePHPValue($property->getStateless()).')';}
			if ($property->getRequired() !== null) {$affects[] = '->setRequired('.$this->escapePHPValue($property->getRequired()).')';}
			if ($property->getMinOccurs() !== null) {$affects[] = '->setMinOccurs('.$this->escapePHPValue($property->getMinOccurs()).')';}
			if ($property->getMaxOccurs() !== null) {$affects[] = '->setMaxOccurs('.$this->escapePHPValue($property->getMaxOccurs()).')';}
			if ($property->getDocumentType() !== null) {$affects[] = '->setDocumentType('.$this->escapePHPValue($property->getDocumentType()).')';}
			if ($property->getCascadeDelete() !== null) {$affects[] = '->setCascadeDelete('.$this->escapePHPValue($property->getCascadeDelete()).')';}
			if ($property->getDefaultValue() !== null) {$affects[] = '->setDefaultValue('.$this->escapePHPValue($property->getDefaultPhpValue(), false).')';}
			if ($property->getLocalized() !== null) {$affects[] = '->setLocalized('.$this->escapePHPValue($property->getLocalized()).')';}
			if ($property->getIndexed() !== null) {$affects[] = '->setIndexed('.$this->escapePHPValue($property->getIndexed()).')';}
			if ($property->getHasCorrection() !== null) {$affects[] = '->setHasCorrection('.$this->escapePHPValue($property->getHasCorrection()).')';}
			if (is_array($property->getConstraintArray()) && count($property->getConstraintArray())) {$affects[] = '->setConstraintArray('.$this->escapePHPValue($property->getConstraintArray()).')';}

			if (count($affects))
			{
				$code .= '		$p' . implode('', $affects) . ';'. PHP_EOL;
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
	protected function loadInverseProperties()
	{
		parent::loadInverseProperties();'. PHP_EOL;
		foreach ($model->getInverseProperties() as $inverseProperty)
		{
			/* @var $inverseProperty \Change\Documents\Generators\InverseProperty */
			$code .= '		$p = $this->inverseProperties['.$this->escapePHPValue($inverseProperty->getName()).'] = new \Change\Documents\InverseProperty('.$this->escapePHPValue($inverseProperty->getName()).');'. PHP_EOL;
			$code .= '		$p->setRelatedDocumentType('.$this->escapePHPValue($inverseProperty->getRelatedDocumentName()).')->setRelatedPropertyName('.$this->escapePHPValue($inverseProperty->getRelatedPropertyName()).');'. PHP_EOL;
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
	 * @api
	 * @return string
	 */
	public function getIcon()
	{
		return '. $this->escapePHPValue($model->getIcon()).';
	}'. PHP_EOL;
		}


		if ($model->getStateless())
		{
			$code .= '
	/**
	 * @api
	 * @return boolean
	 */
	public function isStateless()
	{
		return true;
	}'. PHP_EOL;
		}

		if ($model->getAbstract() !== null)
		{
			$code .= '
	/**
	 * @api
	 * @return boolean
	 */
	public function isAbstract()
	{
		return '. $this->escapePHPValue($model->getAbstract()).';
	}'. PHP_EOL;
		}

		if ($model->getLocalized())
		{
			$code .= '
	/**
	 * @api
	 * @return boolean
	 */
	public function isLocalized()
	{
		return true;
	}'. PHP_EOL;
		}
		
		if ($model->checkHasCorrection())
		{
			$code .= '
	/**
	 * @api
	 * @return boolean
	 */
	public function useCorrection()
	{
		return true;
	}'. PHP_EOL;
		}

		if ($model->getHasUrl() !== null)
		{
			$code .= '
	/**
	 * @api
	 * @return boolean
	 */
	public function hasURL()
	{
		return '. $this->escapePHPValue($model->getHasUrl()).';
	}'. PHP_EOL;
		}
		
		if ($model->getFrontofficeIndexable() !== null)
		{
			$code .= '
	/**
	 * @api
	 * @return boolean
	 */
	public function isFrontofficeIndexable()
	{
		return '. $this->escapePHPValue($model->getFrontofficeIndexable()).' && $this->hasURL();
	}'. PHP_EOL;
		}
		
		if ($model->getBackofficeIndexable() !== null)
		{
			$code .= '
	/**
	 * @api
	 * @return boolean
	 */
	public function isBackofficeIndexable()
	{
		return '. $this->escapePHPValue($model->getBackofficeIndexable()).';
	}'. PHP_EOL;
		}
		
		if ($model->getPublishable() !== null)
		{
			$code .= '
	/**
	 * @api
	 * @return boolean
	 */
	public function isPublishable()
	{
		return '. $this->escapePHPValue($model->getPublishable()).';
	}'. PHP_EOL;
		}
		
		if ($model->getEditable() !== null)
		{
			$code .= '
	/**
	 * @api
	 * @return boolean
	 */
	public function isEditable()
	{
		return '. $this->escapePHPValue($model->getEditable()).';
	}'. PHP_EOL;
		}
			
		if ($model->getUseVersion() !== null)
		{
			$code .= '
	/**
	 * @api
	 * @return boolean
	 */
	public function useVersion()
	{
		return '. $this->escapePHPValue($model->getUseVersion()).';
	}'. PHP_EOL;
		}
		return $code . PHP_EOL;
	}
}