<?php
namespace Change\Documents\Generators;

use Zend\Code\Scanner\DirectoryScanner;

/**
 * @name \Change\Documents\Generators\AbstractDocumentClass
 */
class AbstractDocumentClass
{
	/**
	 * @var \Change\Documents\Generators\Compiler
	 */
	protected $compiler;
	
	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Documents\Generators\Model $model
	 * @return boolean
	 */
	public function savePHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Documents\Generators\Model $model)
	{
		$code = $this->getPHPCode($compiler, $model);
		$nsParts = explode('\\', $model->getNameSpace());
		array_shift($nsParts); //Remove regitered namespace part
		$nsParts[] = $this->getClassName($model) . '.php';
		$path  = \Change\Stdlib\Path::compilationPath(implode(DIRECTORY_SEPARATOR, $nsParts));
		\Change\Stdlib\File::write($path, $code);
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
		$code .= 'abstract class ' . $this->getClassName($model) . ' extends ' . $this->getExtendClassName($model) . PHP_EOL;
		$code .= '{'. PHP_EOL;
		$properties = $this->getMemberProperties($model);
		if (count($properties))
		{
			$code .= $this->getMembers($model, $properties);
			$code .= $this->getDbProviderFunctions($model, $properties);
			$code .= $this->getValidateFunctions($model, $properties);
		}
		
		foreach ($model->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getOverride() || $property->getName() === 'id' 
				|| $property->getName() === 'label'|| $property->getName() === 'lang')
			{
				continue;
			}
			if ($property->getType() === 'DocumentArray')
			{
				$code .= $this->getPropertyDocumentArrayAccessors($model, $property);
			}
			elseif ($property->getType() === 'Document')
			{
				$code .= $this->getPropertyDocumentAccessors($model, $property);
			}
			elseif ($property->getLocalized())
			{
				$code .= $this->getPropertyLocalizedAccessors($model, $property);
			}
			else
			{
				$code .= $this->getPropertyAccessors($model, $property);
			}
		}
			
		$code .= $this->getSetDefaultValues($model);
		if (!$model->getInject())
		{
			$code .= $this->getNoneInjectedFunctions($model);
		}
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
		$cn = 'Abstract' .ucfirst($model->getDocumentName());
		return ($withNameSpace) ? $this->addNameSpace($model, $cn) :  $cn;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getExtendClassName($model)
	{
		if ($model->getExtend())
		{
			$pm = $this->compiler->getModelByFullName($model->getExtend());
			return $this->getFinalClassName($pm);
		}
		return '\Change\Documents\AbstractDocument';
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getFinalClassName($model)
	{
		return $this->getFinalClassNameByCode($model->getVendor(), $model->getModuleName(), $model->getDocumentName());
	}

	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @return string
	 */
	protected function getFinalClassNameByCode($vendor, $moduleName, $documentName)
	{
		//TODO Old class Usage
		$oldName = implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME, 'modules', $moduleName, 'persistentdocument', $documentName . '.class.php'));
		if (is_file($oldName))
		{
			return '\\' . $moduleName. '_persistentdocument_' . $documentName;
		}
		return '\\'. ucfirst($vendor).'\\' .  ucfirst($moduleName) . '\Documents\\' . ucfirst($documentName);
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param boolean $withNameSpace
	 * @return string
	 */
	protected function getModelClassName($model, $withNameSpace = false)
	{
		$cn = ucfirst($model->getDocumentName()) . 'Model';
		return ($withNameSpace) ? $this->addNameSpace($model, $cn) :  $cn;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getFinalServiceClassName($model)
	{
		$cn = ucfirst($model->getDocumentName()) . 'Service';
		return '\\'. ucfirst($model->getVendor()). '\\' .  ucfirst($model->getModuleName()) . '\Documents\\' .$cn;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return \Change\Documents\Generators\Property[]
	 */
	protected function getMemberProperties($model)
	{
		$defined = array_diff($this->getAllMemberProperties($model), array('id', 'label', 'lang'));		
		$properties = array();
		foreach ($model->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if (!$property->getOverride() && in_array($property->getName(), $defined))
			{
				$properties[$property->getName()] = $property;
			}
		}
		return $properties;
	}
	
	
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string[]
	 */	
	protected function getAllMemberProperties($model)
	{
		$models = $this->compiler->getAncestors($model);
		$models[] = $model;
		$names = array();
		foreach ($models as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			foreach ($model->getProperties() as $property)
			{
				/* @var $property \Change\Documents\Generators\Property */
				if (!$property->getOverride() && !$property->getLocalized())
				{
					$names[] = $property->getName();
				}
			}
		}
		return $names;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property[] $properties
	 * @return string
	 */
	protected function getMembers($model, $properties)
	{
		$code = '';
		$sleep = array();
		$destruct = array();
		$baseSleep = "\0Compilation\\" . $model->getNameSpace() . "\\" . $this->getClassName($model) . "\0m_";
		foreach ($properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getLocalized())
			{
				continue;
			}
			$code .= '	private $m_'.$property->getName().';'. PHP_EOL;
			$sleep[] = substr($this->escapePHPValue($baseSleep . $property->getName(), false), 5);
			$destruct[] = '		$this->m_'.$property->getName().' = null;';
		}
		if (count($sleep))
		{
			$code .= '
    /**
     * @return string[]
     */
	public function __getSerializedPropertyNames()
	{
		return array_merge(parent::__getSerializedPropertyNames(), array('.implode(',', $sleep).'));
	}

    public function __destruct()
    {'. PHP_EOL . implode(PHP_EOL, $destruct). PHP_EOL .'
        parent::__destruct();
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
		return '.$this->escapePHPValue($model->getLocalized()).';
	}'. PHP_EOL;
		}
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */	
	protected function getNoneInjectedFunctions($model)
	{
		$docClassName = $this->getFinalClassName($model);
		$modelClassName = $this->getModelClassName($model);
		$serviceClassName = $this->getFinalServiceClassName($model);
		$code = ' 
	/**
	 * @return ' . $docClassName . '
	 */
	public static function getNewInstance()
	{
		return ' . $serviceClassName . '::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return ' . $docClassName . '
	 */
	public static function getInstanceById($documentId)
	{
		return ' . $serviceClassName . '::getInstance()->getDocumentInstance($documentId, '.$this->escapePHPValue($model->getFullName()).');
	}
		
	/**
	 * @return ' . $modelClassName . '
	 */
	public function getPersistentModel()
	{
		return \Change\Documents\ModelManager::getInstance()->getModelByName($this->getDocumentModelName());
	}

	/**
	 * @return string
	 */
	public function getDocumentModelName()
	{
		return '.$this->escapePHPValue($model->getFullName()).';
	}
	
	/**
	 * @return ' . $serviceClassName . '
	 */
	public function getDocumentService()
	{
		return ' . $serviceClassName . '::getInstance();
	}' . PHP_EOL;
		
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getSetDefaultValues($model)
	{
		$names = $this->getAllMemberProperties($model);
		$lines = array();
		if ($model->getExtend())
		{
			$lines[] = '		parent::setDefaultValues();';
		}
		foreach ($model->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getDefaultValue() !== null && in_array($property->getName(), $names))
			{
				$lines[] = '		$this->set' . ucfirst($property->getName()) . 'Internal(' . $this->escapePHPValue($property->getDefaultPhpValue(), false). ');';
			}
		}
		$code = '
	/**
	 * @return void
	 */
	protected function setDefaultValues()
	{' . PHP_EOL . implode(PHP_EOL, $lines) . PHP_EOL;
	$code .= '	}' . PHP_EOL;
	
		return $code;
	}
	
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property[] $properties
	 * @return string
	 */
	protected function getDbProviderFunctions($model, $properties)
	{
		$code = '';
		$get = array();	
		$set = array();
		if (isset($properties['s18s']))
		{
			$get[] = '		$this->serializeS18s();';
			$code .= '
	private $m_s18sArray;
	
	protected function serializeS18s()
	{
		if ($this->m_s18sArray !== null)
		{
			$this->setS18s(serialize($this->m_s18sArray));
			$this->m_s18sArray = null;
		}
	}
	
	protected function unserializeS18s()
	{
		$string = $this->getS18s();
		if ($string === null)
		{
			$this->m_s18sArray = array();
		}
		else
		{
			$this->m_s18sArray = unserialize($string);
		}
	}
	
	protected function getS18sProperty($name)
	{
		if ($this->m_s18sArray === null) {$this->unserializeS18s();}
		if (isset($this->m_s18sArray[$name]))
		{
			return $this->m_s18sArray[$name];
		}
		return null;
	}
	
	protected function setS18sProperty($name, $value)
	{
		if ($this->m_s18sArray === null) {$this->unserializeS18s();}
		$this->m_s18sArray[$name] = $value;
		$this->propertyUpdated(\'s18s\');
	}'.PHP_EOL;
			
		}
		foreach ($properties as $property)
		{
			$name = $property->getName();
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getType() === 'DocumentArray')
			{
				$get[] = '        if ($loadAll) {$this->checkLoaded'.ucfirst($name).'();}';
			}
			$get[] = '		$propertyBag['.$this->escapePHPValue($name).'] = $this->m_'.$name.';';
			if ($property->getType() === 'Boolean')
			{
				$sv = '(null === $propertyValue) ? null : (bool)$propertyValue'; 
			}
			elseif ($property->getType() === 'Integer' || $property->getType() === 'DocumentId')
			{
				$sv = '(null === $propertyValue) ? null : intval($propertyValue)';
			}
			elseif ($property->getType() === 'Float' || $property->getType() === 'Decimal')
			{
				$sv = '(null === $propertyValue) ? null : floatval($propertyValue)';
			}
			else
			{
				$sv = '$propertyValue';
			}
			$set[] = '				case '.$this->escapePHPValue($name).' : $this->m_'.$name.' = '.$sv.'; break;';
		}		
		$code .= '
	/**
	 * @param boolean $loadAll if all data must be retrieved (by default)
	 * @return array<String, mixed>
	 */
	public function getDocumentProperties($loadAll = true)
	{
		$propertyBag = parent::getDocumentProperties($loadAll);' . PHP_EOL;
		$code .= implode(PHP_EOL, $get);
		$code .= '
		return $propertyBag;
	}
			
	/**
	 * @param array<String, mixed> $lang
	 * @return void
	 */
	public function setDocumentProperties($propertyBag)
	{
		parent::setDocumentProperties($propertyBag);
		foreach ($propertyBag as $propertyName => $propertyValue)
		{
			switch ($propertyName)
			{' . PHP_EOL;
		$code .= implode(PHP_EOL, $set);
		
		$code .= '
			}
		}						
	}' . PHP_EOL;
		
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property[] $properties
	 * @return string
	 */
	protected function getValidateFunctions($model, $properties)
	{	
		$code = '';
		$validates = array();
		foreach ($properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			$name = $property->getName();
			$validates[] = '		$this->is'.ucfirst($name).'Valid();';
			$code .= $this->generatePropertyValidateFunction($model, $property);
		}	
		$code .= '
	/**
	 * @return boolean
	 */
	public function isValid()
	{
		parent::isValid();' . PHP_EOL;
		$code .= implode(PHP_EOL, $validates);
		$code .= '	
	}' . PHP_EOL;
		return $code;		
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function generatePropertyValidateFunction($model, $property)
	{
		$name = $property->getName();
		$eName = $this->escapePHPValue($name);
		$uName = ucfirst($name);
		if ($property->getType() === 'DocumentArray')
		{
			$code = '
	/**
	 * @return boolean
	 */
	public function is'.$uName.'Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('.$eName.'))
		{
			$prop = $this->getPersistentModel()->getProperty('.$eName.');
			$value = $this->get'.$uName.'Count();
			if ($value === 0) {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('.$eName.', \LocaleService::getInstance()->trans(\'f.constraints.isempty\', array(\'ucf\'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->getMaxOccurs() > 1 && value > $prop->getMaxOccurs()) {
				$args = array(\'maxOccurs\' => $prop->getMaxOccurs());
				$this->addPropertyErrors('.$eName.', \LocaleService::getInstance()->trans(\'f.constraints.maxoccurs\', array(\'ucf\'), array($args))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->getMinOccurs() > 1 && value < $prop->getMinOccurs()) {
				$args = array(\'minOccurs\' => $prop->getMinOccurs());
				$this->addPropertyErrors('.$eName.', \LocaleService::getInstance()->trans(\'f.constraints.minoccurs\', array(\'ucf\'), array($args))); //TODO Old class Usage
				return false;
			}
		}
		return true;
	}' . PHP_EOL;
			return $code;
		}
		
		$code = '
	/**
	 * @return boolean
	 */
	public function is'.$uName.'Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('.$eName.'))
		{
			$prop = $this->getPersistentModel()->getProperty('.$eName.');
			$value = $this->get'.$uName.'();
			if ($value === null || $value === \'\') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('.$eName.', \LocaleService::getInstance()->trans(\'f.constraints.isempty\', array(\'ucf\'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array(\'documentId\' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('.$eName.', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}' . PHP_EOL;
		return $code;
	}
	
	/**
	 * @return string
	 */
	public function getCommentaryType($property)
	{
		switch ($property->getType())
		{
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_BOOLEAN :
				return 'boolean';
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_FLOAT :
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_DECIMAL :
				return 'float';
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_INTEGER :
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_DOCUMENTID :
				return 'integer';
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_DOCUMENT :
			case \Change\Documents\AbstractDocument::PROPERTYTYPE_DOCUMENTARRAY :
				$docType = $property->getDocumentType() ? $this->compiler->cleanModelName($property->getDocumentType()) : null;	
				if ($docType === null || $docType == $this->compiler->cleanModelName(\Change\Documents\AbstractModel::BASE_MODEL))
				{
					return '\Change\Documents\AbstractDocument';
				}
				else
				{
					
					list ($vendor, $moduleName, $docName) = explode('_', $docType);
					return $this->getFinalClassNameByCode($vendor, $moduleName, $docName);
				}
			default:
				return 'string';
		}
	}
	
	/**
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */	
	protected function buildValConverter($property)
	{
		if ($property->getType() === 'DateTime' || $property->getType() === 'Date')
		{
			return '$val = ($val === null) ? $val : ($val instanceof \date_Calendar) ? \date_Formatter::format($val, \date_Formatter::SQL_DATE_FORMAT) : is_long($val) ? date(\date_Formatter::SQL_DATE_FORMAT, $val) : $val';
		}
		elseif ($property->getType() === 'Boolean')
		{
			return '$val = ($val === null) ? $val : (bool)$val';
		}
		elseif ($property->getType() === 'Integer')
		{
			return '$val = ($val === null) ? $val : intval($val)';
		}
		elseif ($property->getType() === 'Float' || $property->getType() === 'Decimal')
		{
			return '$val = ($val === null) ? $val : floatval($val)';
		}
		elseif ($property->getType() === 'DocumentId')
		{
			return '$val = ($val === null) ? $val : ($val instanceof \Change\Documents\AbstractDocument) ? $val->getId() : intval($val) > 0 ? intval($val) : null';
		}
		elseif ($property->getType() === 'JSON')
		{
			return '$val = ($val === null || is_string($val)) ? $val : \JsonService::getInstance()->encode($val)';
		}
		elseif ($property->getType() === 'Object')
		{
			return '$val = ($val === null || is_string($val)) ? $val : serialize($val)';
		}
		elseif ($property->getType() === 'Document' || $property->getType() === 'DocumentArray')
		{
			return '$val = $val === null || !($val instanceof \Change\Documents\AbstractDocument)) ? null : $val->getId()';
		}
		else
		{
			return '$val = $val === null ? val : strval($val)';
		}
	}
		
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */	
	protected function getPropertyAccessors($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$mn = '$this->m_' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);			
		$code .= '
	/**
	 * @return '.$ct.'
	 */
	public function get'.$un.'()
	{
		$this->checkLoaded();
		return '.$mn.';
	}
			
	/**
	 * @return '.$ct.'|NULL
	 */
	public function get'.$un.'OldValue()
	{
		return $this->getOldValue('.$en.');
	}
			
	/**
	 * @param '.$ct.' $val
	 */
	public function set'.$un.'($val)
	{
		$this->checkLoaded();'.PHP_EOL;
		if ($name === 's18s') {$code .= '		$this->m_s18sArray = null;'.PHP_EOL;}
		$code .= '		if ($this->set'.$un.'Internal($val))
		{
			$this->propertyUpdated('.$en.');
		}
	}'.PHP_EOL;
		
		$code .= '
	protected function set'.$un.'Internal($val)
	{'.PHP_EOL;
		$code .= '		' . $this->buildValConverter($property) . ';'.PHP_EOL;	
		if ($property->getType() === 'Float' || $property->getType() === 'Decimal')
		{
			$code .= '		$modified = (abs(floatval('.$mn.') - $val) > 0.0001);'.PHP_EOL;
		}
		else
		{
			$code .= '		$modified = ('.$mn.' !== $val);'.PHP_EOL;
		}
		
		$code .= '		if ($modified)
		{
			$this->setOldValue('.$en.', '.$mn.');
			'.$mn.' = $val;
			return true;
		}
		return false;
	}'.PHP_EOL;
		
		$code .= $this->getPropertyExtraGetters($model, $property);
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyLocalizedAccessors($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$mn = '$this->m_' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$code .= '
	/**
	 * @return '.$ct.'
	 */
	public function get'.$un.'()
	{
		$this->checkLoaded();
		return $this->getI18nObject()->get'.$un.'();
	}

	/**
	 * @return '.$ct.'
	 */
	public function getVo'.$un.'()
	{
		$this->checkLoaded();
		return $this->getI18nVoObject()->get'.$un.'();
	}

	/**
	 * @param string $lang
	 * @return '.$ct.'
	 */
	public function get'.$un.'ForLang($lang)
	{
		$this->checkLoaded();
		return $this->getI18nObject($lang)->get'.$un.'();
	}
			
	/**
	 * @return '.$ct.'|NULL
	 */
	public function get'.$un.'OldValue()
	{
		return $this->getOldValue('.$en.', $this->getI18nObject()->getLang());
	}
			
	protected function set'.$un.'Internal($val)
	{'.PHP_EOL;
		$code .= '		' . $this->buildValConverter($property) . ';'.PHP_EOL;	
		$code .= '		$i18nObject = $this->getI18nObject();'.PHP_EOL;	
		$code .= '		$modified = $i18nObject->set'.$un.'($val);'.PHP_EOL;
		$code .= '		if ($modified) {$this->setOldValue('.$en.', $i18nObject->get'.$un.'OldValue(), $i18nObject->getLang());}'.PHP_EOL;
		$code .= '		return $modified;
	}'.PHP_EOL;
		
		$code .= $this->getPropertyExtraGetters($model, $property);
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyExtraGetters($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$mn = '$this->m_' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
	
		if ($property->getType() === 'DateTime')
		{
			$code .= '
	/**
	 * @param string $val
	 * @return void
	 */
	public function setUI'.$un.'($val)
	{
		$this->set'.$un.'(\date_Converter::convertDateToGMT($val));
	}
	
	/**
	 * @return string
	 */
	public function getUI'.$un.'()
	{
		return \date_Converter::convertDateToLocal($this->get'.$un.'());
	}'.PHP_EOL;
		}
		elseif ($property->getType() === 'RichText')
		{
			$code .= '
	/**
	 * @return string
	 */
	public function get'.$un.'AsHtml()
	{
		//TODO old XHTMLFragment and BBCode
		return \f_util_HtmlUtils::renderHtmlFragment($this->get'.$un.'());
		//$parser = new \website_BBCodeParser();
		//return $parser->convertXmlToHtml($this->get'.$un.'());
	}
			
	/**
	 * @param string $val
	 * @return void
	 */
	public function set'.$un.'AsBBCode($val)
	{
		$parser = new \website_BBCodeParser();
		$this->set'.$un.'($parser->convertBBCodeToXml($val, $parser->getModuleProfile()));
	}
	
	/**
	 * @return string
	 */
	public function get'.$un.'AsBBCode()
	{
		$parser = new \website_BBCodeParser();
		return $parser->convertXmlToBBCode($this->get'.$un.'());
	}'.PHP_EOL;
		}
		elseif ($property->getType() === 'LongString' || $property->getType() === 'String')
		{
			$code .= '	
	/**
	 * @return string
	 */
	public function get'.$un.'AsHtml()
	{
		return \f_util_HtmlUtils::textToHtml($this->get'.$un.'());
	}'.PHP_EOL;
		}		
		elseif ($property->getType() === 'XML')
		{
			$code .= '
	/**
	 * @return \DOMDocument
	 */
	public function get'.$un.'DOMDocument()
	{
		$document = new \DOMDocument("1.0", "UTF-8");
		if ($this->get'.$un.'() !== null) {$document->loadXML($this->get'.$un.'());}
		return $document;
	}
		
	/**
	 * @param \DOMDocument $document
	 */
	public function set'.$un.'DOMDocument($document)
	{
		 $this->set'.$un.'($document && $document->documentElement ? $document->saveXML() : null);
	}'.PHP_EOL;
		}	
		elseif ($property->getType() === 'JSON')
		{
			$code .= '	
	/**
	 * @return array
	 */
	public function getDecoded'.$un.'()
	{
		$val = $this->get'.$un.'();
		return $val === null ? $val : \JsonService::getInstance()->decode($val);
	}'.PHP_EOL;
		}
		elseif ($property->getType() === 'Object')
		{
			$code .= '	
	/**
	 * @return mixed
	 */
	public function getDecoded'.$un.'()
	{
		$val = $this->get'.$un.'();
		return $val === null ? $val : unserialize($val);
	}'.PHP_EOL;
		}
		elseif ($property->getType() === 'DocumentId')
		{
			$code .= '
	/**
	 * @return \Change\Documents\AbstractDocument|NULL
	 */
	public function get'.$un.'Instance()
	{
		return \Change\Documents\DocumentHelper::getDocumentInstanceIfExists($this->get'.$un.'());
	}'.PHP_EOL;
		}
		
		if ($property->getFromList())
		{
			$code .= '
	/**
	 * @return string
	 */
	public function get'.$un.'Label()
	{
		$list = \list_ListService::getInstance()->getByListId('.$this->escapePHPValue($property->getFromList()).');
		if ($list === null)
		{
			return null;
		}
		$listItem = $list->getItemByValue($this->get'.$un.'());
		if ($listItem === null)
		{
			return null;
		}
		return $listItem->getLabel();
	}
	
	/**
	 * @return string
	 */
	public function get'.$un.'LabelAsHtml()
	{
		$label = $this->get'.$un.'Label();
		return $label ? \f_util_HtmlUtils::textToHtml($label) : null;
	}'.PHP_EOL;
		}
	
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyDocumentAccessors($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$mn = '$this->m_' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$code .= '	
	/**
	 * @return integer
	 */
	public function get'.$un.'OldValueId()
	{
		return $this->getOldValue('.$en.');
	}
			
	/**
	 * @param '.$ct.' $newValue
	 */
	public function set'.$un.'($newValue)
	{
		$this->checkLoaded();
		$newId = ($newValue instanceof \Change\Documents\AbstractDocument) ? $this->getProvider()->getCachedDocumentId($newValue) : null;
		if ('.$mn.' != $newId)
		{
			$this->setOldValue('.$en.', '.$mn.');
			'.$mn.' = $newId;
			$this->propertyUpdated('.$en.');
		}
	}

	/**
	 * @return integer
	 */
	public function get'.$un.'Id()
	{
		$this->checkLoaded();
		return '.$mn.';
	}
	
	/**
	 * @return '.$ct.'
	 */
	public function get'.$un.'()
	{
		$this->checkLoaded();
		return ('.$mn.') ? $this->getProvider()->getCachedDocumentById('.$mn.') : null;
	}' . PHP_EOL;
		
		return $code;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyDocumentArrayAccessors($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$mn = '$this->m_' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$code .= '
	/**
	 * @return integer[]
	 */
	public function get'.$un.'OldValueIds()
	{
		$result = $this->getOldValue('.$en.');
		if (is_array($result))
		{
			return $result;
		}
		return array();
	}
					
	protected function checkLoaded'.$un.'()
	{
		$this->checkLoaded();
		if (!is_array('.$mn.'))
		{
			if ($this->getDocumentPersistentState() != self::PERSISTENTSTATE_NEW)
			{
				'.$mn.' = $this->getProvider()->loadRelations($this, '.$en.');
			}
			else
			{
				'.$mn.' = array();
			}
		}
	}

	/**
	 * @param integer $index
	 * @param '.$ct.' $newValue
	 */
	public function set'.$un.'($index, $newValue)
	{
		if ($newValue instanceof \Change\Documents\AbstractDocument)
		{
			$newId = $this->getProvider()->getCachedDocumentId($newValue); 
			$index = intval($index);
			$this->checkLoaded'.$un.'();
			if (!in_array($newId, '.$mn.'))
			{
				$this->setOldValue('.$en.', '.$mn.');
				if ($index < 0 || $index > count('.$mn.'))
				{
					$index = count('.$mn.');
				}
				'.$mn.'[$index] = $newId;
				$this->propertyUpdated('.$en.');
			}
		}
		else
		{
			throw new \Exception(__METHOD__. \': document can not be null\');
		}
	}

	/**
	 * @param '.$ct.'[] $newValueArray
	 */
	public function set'.$un.'Array($newValueArray)
	{
		if (is_array($newValueArray))
		{
			$this->checkLoaded'.$un.'();
			$newValueIds = array(); $dbp = $this->getProvider();
			array_walk($newValueArray, function ($newValue, $index) use (&$newValueIds, $dbp) {
				$newValueIds[] = $dbp->getCachedDocumentId($newValue);
			});
			if ('.$mn.' != $newValueIds)
			{
				$this->setOldValue('.$en.', '.$mn.');
				'.$mn.' = $newValueIds;
				$this->propertyUpdated('.$en.');
			}
		}
		else
		{
			throw new \Exception(\'Invalid type of document array\');
		}
	}

	/**
	 * @param '.$ct.' $newValue
	 */
	public function add'.$un.'($newValue)
	{
		if ($newValue instanceof \Change\Documents\AbstractDocument)
		{ 
			$newId = $this->getProvider()->getCachedDocumentId($newValue);
			$this->checkLoaded'.$un.'();
			if (!in_array($newId, '.$mn.'))
			{
				$this->setOldValue('.$en.', '.$mn.');
				'.$mn.'[] = $newId;
				$this->propertyUpdated('.$en.');
			}
		}
		else
		{
			throw new \Exception(__METHOD__. \': document can not be null\');
		}
	}

	/**
	 * @param '.$ct.' $value
	 */
	public function remove'.$un.'($value)
	{
		$this->checkLoaded'.$un.'();
		if ($value instanceof \Change\Documents\AbstractDocument)
		{
			$valueId = $value->getId();
			$index = array_search($valueId, '.$mn.');
			if ($index !== false)
			{
				$this->setOldValue('.$en.', '.$mn.');
				unset('.$mn.'[$index]);
				$this->propertyUpdated('.$en.');
			}
		}
	}

	/**
	 * @param integer $index
	 */
	public function remove'.$un.'ByIndex($index)
	{
		$this->checkLoaded'.$un.'();
		if (isset('.$mn.'[$index]))
		{
			$this->setOldValue('.$en.', '.$mn.');
			unset('.$mn.'[$index]);
			$this->propertyUpdated('.$en.');
		}
	}

	public function removeAll'.$un.'()
	{
		$this->checkLoaded'.$un.'();
		if (count('.$mn.'))
		{
			$this->setOldValue('.$en.', '.$mn.');
			'.$mn.' = array();
			$this->propertyUpdated('.$en.');
		}
	}

	/**
	 * @param integer $index
	 * @return '.$ct.'
	 */
	public function get'.$un.'($index)
	{
		$this->checkLoaded'.$un.'();
		return isset('.$mn.'[$index]) ?  $this->getProvider()->getCachedDocumentById('.$mn.'[$index]) : null;
	}
	
	/**
	 * @return integer[]
	 */
	public function get'.$un.'Ids()
	{
		$this->checkLoaded'.$un.'();
		return '.$mn.';
	}

	/**
	 * @return '.$ct.'[]
	 */
	public function get'.$un.'Array()
	{
		$this->checkLoaded'.$un.'();
		$documents = array(); $dbp = $this->getProvider();
		array_walk('.$mn.', function ($documentId, $index) use (&$documents, $dbp) {
			$documents[] = $dbp->getCachedDocumentById($documentId);
		});
		return $documents;
	}

	/**
	 * @return '.$ct.'[]
	 */
	public function getPublished'.$un.'Array()
	{
		$this->checkLoaded'.$un.'();
		$documents = array(); $dbp = $this->getProvider();
		array_walk('.$mn.', function ($documentId, $index) use (&$documents, $dbp) {
			$document = $dbp->getCachedDocumentById($documentId);
			if ($document->isPublished()) {$documents[] = $document;}
		});
		return $documents;
	}

	/**
	 * @return integer
	 */
	public function getPublished'.$un.'Count()
	{
		return count($this->getPublished'.$un.'Array());
	}

	/**
	 * @param '.$ct.' $value
	 * @return integer
	 */
	public function getIndexof'.$un.'($value)
	{
		if ($value instanceof \Change\Documents\AbstractDocument) 
		{
			$this->checkLoaded'.$un.'();
			$valueId = $this->getProvider()->getCachedDocumentId($value);
			$index = array_search($valueId, '.$mn.');
			return $index !== false ? $index : -1;
		}
		throw new \Exception(__METHOD__. \': document can not be null\');
	}' . PHP_EOL;
		return $code;
	}
}