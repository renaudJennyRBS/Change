<?php
namespace Compilation\Change\Testing\Documents;
abstract class AbstractGeneration extends \Change\Documents\AbstractDocument
{
	private $m_author;
	private $m_authorid;
	private $m_creationdate;
	private $m_modificationdate;
	private $m_modelversion;
	private $m_documentversion;
	private $m_startpublicationdate;
	private $m_endpublicationdate;
	private $m_metastring;
	private $m_bool1;
	private $m_int1;
	private $m_float1;
	private $m_decimal1;
	private $m_date1;
	private $m_datetime1;
	private $m_longstring1;
	private $m_xml1;
	private $m_lob1;
	private $m_json1;
	private $m_object1;
	private $m_documentid1;
	private $m_document1;
	private $m_documentarray1;
	private $m_correctionofid;
	private $m_s18s;

    /**
     * @return string[]
     */
	public function __getSerializedPropertyNames()
	{
		return array_merge(parent::__getSerializedPropertyNames(), array("\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_author',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_authorid',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_creationdate',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_modificationdate',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_modelversion',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_documentversion',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_startpublicationdate',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_endpublicationdate',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_metastring',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_bool1',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_int1',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_float1',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_decimal1',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_date1',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_datetime1',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_longstring1',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_xml1',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_lob1',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_json1',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_object1',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_documentid1',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_document1',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_documentarray1',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_correctionofid',"\0" . 'Compilation\\Change\\Testing\\Documents\\AbstractGeneration' . "\0" . 'm_s18s'));
	}

    public function __destruct()
    {
		$this->m_author = null;
		$this->m_authorid = null;
		$this->m_creationdate = null;
		$this->m_modificationdate = null;
		$this->m_modelversion = null;
		$this->m_documentversion = null;
		$this->m_startpublicationdate = null;
		$this->m_endpublicationdate = null;
		$this->m_metastring = null;
		$this->m_bool1 = null;
		$this->m_int1 = null;
		$this->m_float1 = null;
		$this->m_decimal1 = null;
		$this->m_date1 = null;
		$this->m_datetime1 = null;
		$this->m_longstring1 = null;
		$this->m_xml1 = null;
		$this->m_lob1 = null;
		$this->m_json1 = null;
		$this->m_object1 = null;
		$this->m_documentid1 = null;
		$this->m_document1 = null;
		$this->m_documentarray1 = null;
		$this->m_correctionofid = null;
		$this->m_s18s = null;

        parent::__destruct();
    }

	/**
	 * @return boolean
	 */
	public function isLocalized()
	{
		return true;
	}

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
		$this->propertyUpdated('s18s');
	}

	/**
	 * @param boolean $loadAll if all data must be retrieved (by default)
	 * @return array<String, mixed>
	 */
	public function getDocumentProperties($loadAll = true)
	{
		$propertyBag = parent::getDocumentProperties($loadAll);
		$this->serializeS18s();
		$propertyBag['author'] = $this->m_author;
		$propertyBag['authorid'] = $this->m_authorid;
		$propertyBag['creationdate'] = $this->m_creationdate;
		$propertyBag['modificationdate'] = $this->m_modificationdate;
		$propertyBag['modelversion'] = $this->m_modelversion;
		$propertyBag['documentversion'] = $this->m_documentversion;
		$propertyBag['startpublicationdate'] = $this->m_startpublicationdate;
		$propertyBag['endpublicationdate'] = $this->m_endpublicationdate;
		$propertyBag['metastring'] = $this->m_metastring;
		$propertyBag['bool1'] = $this->m_bool1;
		$propertyBag['int1'] = $this->m_int1;
		$propertyBag['float1'] = $this->m_float1;
		$propertyBag['decimal1'] = $this->m_decimal1;
		$propertyBag['date1'] = $this->m_date1;
		$propertyBag['datetime1'] = $this->m_datetime1;
		$propertyBag['longstring1'] = $this->m_longstring1;
		$propertyBag['xml1'] = $this->m_xml1;
		$propertyBag['lob1'] = $this->m_lob1;
		$propertyBag['json1'] = $this->m_json1;
		$propertyBag['object1'] = $this->m_object1;
		$propertyBag['documentid1'] = $this->m_documentid1;
		$propertyBag['document1'] = $this->m_document1;
        if ($loadAll) {$this->checkLoadedDocumentarray1();}
		$propertyBag['documentarray1'] = $this->m_documentarray1;
		$propertyBag['correctionofid'] = $this->m_correctionofid;
		$propertyBag['s18s'] = $this->m_s18s;
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
			{
				case 'author' : $this->m_author = $propertyValue; break;
				case 'authorid' : $this->m_authorid = (null === $propertyValue) ? null : intval($propertyValue); break;
				case 'creationdate' : $this->m_creationdate = $propertyValue; break;
				case 'modificationdate' : $this->m_modificationdate = $propertyValue; break;
				case 'modelversion' : $this->m_modelversion = $propertyValue; break;
				case 'documentversion' : $this->m_documentversion = (null === $propertyValue) ? null : intval($propertyValue); break;
				case 'startpublicationdate' : $this->m_startpublicationdate = $propertyValue; break;
				case 'endpublicationdate' : $this->m_endpublicationdate = $propertyValue; break;
				case 'metastring' : $this->m_metastring = $propertyValue; break;
				case 'bool1' : $this->m_bool1 = (null === $propertyValue) ? null : (bool)$propertyValue; break;
				case 'int1' : $this->m_int1 = (null === $propertyValue) ? null : intval($propertyValue); break;
				case 'float1' : $this->m_float1 = (null === $propertyValue) ? null : floatval($propertyValue); break;
				case 'decimal1' : $this->m_decimal1 = (null === $propertyValue) ? null : floatval($propertyValue); break;
				case 'date1' : $this->m_date1 = $propertyValue; break;
				case 'datetime1' : $this->m_datetime1 = $propertyValue; break;
				case 'longstring1' : $this->m_longstring1 = $propertyValue; break;
				case 'xml1' : $this->m_xml1 = $propertyValue; break;
				case 'lob1' : $this->m_lob1 = $propertyValue; break;
				case 'json1' : $this->m_json1 = $propertyValue; break;
				case 'object1' : $this->m_object1 = $propertyValue; break;
				case 'documentid1' : $this->m_documentid1 = (null === $propertyValue) ? null : intval($propertyValue); break;
				case 'document1' : $this->m_document1 = $propertyValue; break;
				case 'documentarray1' : $this->m_documentarray1 = $propertyValue; break;
				case 'correctionofid' : $this->m_correctionofid = (null === $propertyValue) ? null : intval($propertyValue); break;
				case 's18s' : $this->m_s18s = $propertyValue; break;
			}
		}						
	}

	/**
	 * @return boolean
	 */
	public function isAuthorValid()
	{
		if ($this->isNew() || $this->isPropertyModified('author'))
		{
			$prop = $this->getPersistentModel()->getProperty('author');
			$value = $this->getAuthor();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('author', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('author', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isAuthoridValid()
	{
		if ($this->isNew() || $this->isPropertyModified('authorid'))
		{
			$prop = $this->getPersistentModel()->getProperty('authorid');
			$value = $this->getAuthorid();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('authorid', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('authorid', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isCreationdateValid()
	{
		if ($this->isNew() || $this->isPropertyModified('creationdate'))
		{
			$prop = $this->getPersistentModel()->getProperty('creationdate');
			$value = $this->getCreationdate();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('creationdate', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('creationdate', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isModificationdateValid()
	{
		if ($this->isNew() || $this->isPropertyModified('modificationdate'))
		{
			$prop = $this->getPersistentModel()->getProperty('modificationdate');
			$value = $this->getModificationdate();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('modificationdate', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('modificationdate', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isModelversionValid()
	{
		if ($this->isNew() || $this->isPropertyModified('modelversion'))
		{
			$prop = $this->getPersistentModel()->getProperty('modelversion');
			$value = $this->getModelversion();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('modelversion', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('modelversion', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isDocumentversionValid()
	{
		if ($this->isNew() || $this->isPropertyModified('documentversion'))
		{
			$prop = $this->getPersistentModel()->getProperty('documentversion');
			$value = $this->getDocumentversion();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('documentversion', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('documentversion', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isStartpublicationdateValid()
	{
		if ($this->isNew() || $this->isPropertyModified('startpublicationdate'))
		{
			$prop = $this->getPersistentModel()->getProperty('startpublicationdate');
			$value = $this->getStartpublicationdate();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('startpublicationdate', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('startpublicationdate', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isEndpublicationdateValid()
	{
		if ($this->isNew() || $this->isPropertyModified('endpublicationdate'))
		{
			$prop = $this->getPersistentModel()->getProperty('endpublicationdate');
			$value = $this->getEndpublicationdate();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('endpublicationdate', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('endpublicationdate', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isMetastringValid()
	{
		if ($this->isNew() || $this->isPropertyModified('metastring'))
		{
			$prop = $this->getPersistentModel()->getProperty('metastring');
			$value = $this->getMetastring();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('metastring', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('metastring', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isBool1Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('bool1'))
		{
			$prop = $this->getPersistentModel()->getProperty('bool1');
			$value = $this->getBool1();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('bool1', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('bool1', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isInt1Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('int1'))
		{
			$prop = $this->getPersistentModel()->getProperty('int1');
			$value = $this->getInt1();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('int1', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('int1', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isFloat1Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('float1'))
		{
			$prop = $this->getPersistentModel()->getProperty('float1');
			$value = $this->getFloat1();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('float1', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('float1', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isDecimal1Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('decimal1'))
		{
			$prop = $this->getPersistentModel()->getProperty('decimal1');
			$value = $this->getDecimal1();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('decimal1', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('decimal1', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isDate1Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('date1'))
		{
			$prop = $this->getPersistentModel()->getProperty('date1');
			$value = $this->getDate1();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('date1', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('date1', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isDatetime1Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('datetime1'))
		{
			$prop = $this->getPersistentModel()->getProperty('datetime1');
			$value = $this->getDatetime1();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('datetime1', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('datetime1', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isLongstring1Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('longstring1'))
		{
			$prop = $this->getPersistentModel()->getProperty('longstring1');
			$value = $this->getLongstring1();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('longstring1', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('longstring1', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isXml1Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('xml1'))
		{
			$prop = $this->getPersistentModel()->getProperty('xml1');
			$value = $this->getXml1();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('xml1', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('xml1', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isLob1Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('lob1'))
		{
			$prop = $this->getPersistentModel()->getProperty('lob1');
			$value = $this->getLob1();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('lob1', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('lob1', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isJson1Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('json1'))
		{
			$prop = $this->getPersistentModel()->getProperty('json1');
			$value = $this->getJson1();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('json1', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('json1', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isObject1Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('object1'))
		{
			$prop = $this->getPersistentModel()->getProperty('object1');
			$value = $this->getObject1();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('object1', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('object1', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isDocumentid1Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('documentid1'))
		{
			$prop = $this->getPersistentModel()->getProperty('documentid1');
			$value = $this->getDocumentid1();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('documentid1', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('documentid1', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isDocument1Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('document1'))
		{
			$prop = $this->getPersistentModel()->getProperty('document1');
			$value = $this->getDocument1();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('document1', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('document1', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isDocumentarray1Valid()
	{
		if ($this->isNew() || $this->isPropertyModified('documentarray1'))
		{
			$prop = $this->getPersistentModel()->getProperty('documentarray1');
			$value = $this->getDocumentarray1Count();
			if ($value === 0) {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('documentarray1', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->getMaxOccurs() > 1 && value > $prop->getMaxOccurs()) {
				$args = array('maxOccurs' => $prop->getMaxOccurs());
				$this->addPropertyErrors('documentarray1', \LocaleService::getInstance()->trans('f.constraints.maxoccurs', array('ucf'), array($args))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->getMinOccurs() > 1 && value < $prop->getMinOccurs()) {
				$args = array('minOccurs' => $prop->getMinOccurs());
				$this->addPropertyErrors('documentarray1', \LocaleService::getInstance()->trans('f.constraints.minoccurs', array('ucf'), array($args))); //TODO Old class Usage
				return false;
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isCorrectionofidValid()
	{
		if ($this->isNew() || $this->isPropertyModified('correctionofid'))
		{
			$prop = $this->getPersistentModel()->getProperty('correctionofid');
			$value = $this->getCorrectionofid();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('correctionofid', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('correctionofid', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isS18sValid()
	{
		if ($this->isNew() || $this->isPropertyModified('s18s'))
		{
			$prop = $this->getPersistentModel()->getProperty('s18s');
			$value = $this->getS18s();
			if ($value === null || $value === '') {
				if (!$prop->isRequired()) {return true;}
				$this->addPropertyErrors('s18s', \LocaleService::getInstance()->trans('f.constraints.isempty', array('ucf'))); //TODO Old class Usage
				return false;
			}
			elseif ($prop->hasConstraints()) {
				foreach ($prop->getConstraintArray() as $name => $params) {
					$params += array('documentId' => $this->getId());
					$c = \change_Constraints::getByName($name, $params); //TODO Old class Usage
					if (!$c->isValid($value)) {
						$this->addPropertyErrors('s18s', \change_Constraints::formatMessages($c)); //TODO Old class Usage
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isValid()
	{
		parent::isValid();
		$this->isAuthorValid();
		$this->isAuthoridValid();
		$this->isCreationdateValid();
		$this->isModificationdateValid();
		$this->isModelversionValid();
		$this->isDocumentversionValid();
		$this->isStartpublicationdateValid();
		$this->isEndpublicationdateValid();
		$this->isMetastringValid();
		$this->isBool1Valid();
		$this->isInt1Valid();
		$this->isFloat1Valid();
		$this->isDecimal1Valid();
		$this->isDate1Valid();
		$this->isDatetime1Valid();
		$this->isLongstring1Valid();
		$this->isXml1Valid();
		$this->isLob1Valid();
		$this->isJson1Valid();
		$this->isObject1Valid();
		$this->isDocumentid1Valid();
		$this->isDocument1Valid();
		$this->isDocumentarray1Valid();
		$this->isCorrectionofidValid();
		$this->isS18sValid();	
	}

	/**
	 * @return string
	 */
	public function getAuthor()
	{
		$this->checkLoaded();
		return $this->m_author;
	}
			
	/**
	 * @return string|NULL
	 */
	public function getAuthorOldValue()
	{
		return $this->getOldValue('author');
	}
			
	/**
	 * @param string $val
	 */
	public function setAuthor($val)
	{
		$this->checkLoaded();
		if ($this->setAuthorInternal($val))
		{
			$this->propertyUpdated('author');
		}
	}

	protected function setAuthorInternal($val)
	{
		$val = $val === null ? val : strval($val);
		$modified = ($this->m_author !== $val);
		if ($modified)
		{
			$this->setOldValue('author', $this->m_author);
			$this->m_author = $val;
			return true;
		}
		return false;
	}
	
	/**
	 * @return string
	 */
	public function getAuthorAsHtml()
	{
		return \f_util_HtmlUtils::textToHtml($this->getAuthor());
	}

	/**
	 * @return integer
	 */
	public function getAuthorid()
	{
		$this->checkLoaded();
		return $this->m_authorid;
	}
			
	/**
	 * @return integer|NULL
	 */
	public function getAuthoridOldValue()
	{
		return $this->getOldValue('authorid');
	}
			
	/**
	 * @param integer $val
	 */
	public function setAuthorid($val)
	{
		$this->checkLoaded();
		if ($this->setAuthoridInternal($val))
		{
			$this->propertyUpdated('authorid');
		}
	}

	protected function setAuthoridInternal($val)
	{
		$val = ($val === null) ? $val : ($val instanceof \Change\Documents\AbstractDocument) ? $val->getId() : intval($val) > 0 ? intval($val) : null;
		$modified = ($this->m_authorid !== $val);
		if ($modified)
		{
			$this->setOldValue('authorid', $this->m_authorid);
			$this->m_authorid = $val;
			return true;
		}
		return false;
	}

	/**
	 * @return \Change\Documents\AbstractDocument|NULL
	 */
	public function getAuthoridInstance()
	{
		return \Change\Documents\DocumentHelper::getDocumentInstanceIfExists($this->getAuthorid());
	}

	/**
	 * @return string
	 */
	public function getCreationdate()
	{
		$this->checkLoaded();
		return $this->m_creationdate;
	}
			
	/**
	 * @return string|NULL
	 */
	public function getCreationdateOldValue()
	{
		return $this->getOldValue('creationdate');
	}
			
	/**
	 * @param string $val
	 */
	public function setCreationdate($val)
	{
		$this->checkLoaded();
		if ($this->setCreationdateInternal($val))
		{
			$this->propertyUpdated('creationdate');
		}
	}

	protected function setCreationdateInternal($val)
	{
		$val = ($val === null) ? $val : ($val instanceof \date_Calendar) ? \date_Formatter::format($val, \date_Formatter::SQL_DATE_FORMAT) : is_long($val) ? date(\date_Formatter::SQL_DATE_FORMAT, $val) : $val;
		$modified = ($this->m_creationdate !== $val);
		if ($modified)
		{
			$this->setOldValue('creationdate', $this->m_creationdate);
			$this->m_creationdate = $val;
			return true;
		}
		return false;
	}

	/**
	 * @param string $val
	 * @return void
	 */
	public function setUICreationdate($val)
	{
		$this->setCreationdate(\date_Converter::convertDateToGMT($val));
	}
	
	/**
	 * @return string
	 */
	public function getUICreationdate()
	{
		return \date_Converter::convertDateToLocal($this->getCreationdate());
	}

	/**
	 * @return string
	 */
	public function getModificationdate()
	{
		$this->checkLoaded();
		return $this->m_modificationdate;
	}
			
	/**
	 * @return string|NULL
	 */
	public function getModificationdateOldValue()
	{
		return $this->getOldValue('modificationdate');
	}
			
	/**
	 * @param string $val
	 */
	public function setModificationdate($val)
	{
		$this->checkLoaded();
		if ($this->setModificationdateInternal($val))
		{
			$this->propertyUpdated('modificationdate');
		}
	}

	protected function setModificationdateInternal($val)
	{
		$val = ($val === null) ? $val : ($val instanceof \date_Calendar) ? \date_Formatter::format($val, \date_Formatter::SQL_DATE_FORMAT) : is_long($val) ? date(\date_Formatter::SQL_DATE_FORMAT, $val) : $val;
		$modified = ($this->m_modificationdate !== $val);
		if ($modified)
		{
			$this->setOldValue('modificationdate', $this->m_modificationdate);
			$this->m_modificationdate = $val;
			return true;
		}
		return false;
	}

	/**
	 * @param string $val
	 * @return void
	 */
	public function setUIModificationdate($val)
	{
		$this->setModificationdate(\date_Converter::convertDateToGMT($val));
	}
	
	/**
	 * @return string
	 */
	public function getUIModificationdate()
	{
		return \date_Converter::convertDateToLocal($this->getModificationdate());
	}

	/**
	 * @return string
	 */
	public function getPublicationstatus()
	{
		$this->checkLoaded();
		return $this->getI18nObject()->getPublicationstatus();
	}

	/**
	 * @return string
	 */
	public function getVoPublicationstatus()
	{
		$this->checkLoaded();
		return $this->getI18nVoObject()->getPublicationstatus();
	}

	/**
	 * @param string $lang
	 * @return string
	 */
	public function getPublicationstatusForLang($lang)
	{
		$this->checkLoaded();
		return $this->getI18nObject($lang)->getPublicationstatus();
	}
			
	/**
	 * @return string|NULL
	 */
	public function getPublicationstatusOldValue()
	{
		return $this->getOldValue('publicationstatus', $this->getI18nObject()->getLang());
	}
			
	protected function setPublicationstatusInternal($val)
	{
		$val = $val === null ? val : strval($val);
		$i18nObject = $this->getI18nObject();
		$modified = $i18nObject->setPublicationstatus($val);
		if ($modified) {$this->setOldValue('publicationstatus', $i18nObject->getPublicationstatusOldValue(), $i18nObject->getLang());}
		return $modified;
	}
	
	/**
	 * @return string
	 */
	public function getPublicationstatusAsHtml()
	{
		return \f_util_HtmlUtils::textToHtml($this->getPublicationstatus());
	}

	/**
	 * @return string
	 */
	public function getModelversion()
	{
		$this->checkLoaded();
		return $this->m_modelversion;
	}
			
	/**
	 * @return string|NULL
	 */
	public function getModelversionOldValue()
	{
		return $this->getOldValue('modelversion');
	}
			
	/**
	 * @param string $val
	 */
	public function setModelversion($val)
	{
		$this->checkLoaded();
		if ($this->setModelversionInternal($val))
		{
			$this->propertyUpdated('modelversion');
		}
	}

	protected function setModelversionInternal($val)
	{
		$val = $val === null ? val : strval($val);
		$modified = ($this->m_modelversion !== $val);
		if ($modified)
		{
			$this->setOldValue('modelversion', $this->m_modelversion);
			$this->m_modelversion = $val;
			return true;
		}
		return false;
	}
	
	/**
	 * @return string
	 */
	public function getModelversionAsHtml()
	{
		return \f_util_HtmlUtils::textToHtml($this->getModelversion());
	}

	/**
	 * @return integer
	 */
	public function getDocumentversion()
	{
		$this->checkLoaded();
		return $this->m_documentversion;
	}
			
	/**
	 * @return integer|NULL
	 */
	public function getDocumentversionOldValue()
	{
		return $this->getOldValue('documentversion');
	}
			
	/**
	 * @param integer $val
	 */
	public function setDocumentversion($val)
	{
		$this->checkLoaded();
		if ($this->setDocumentversionInternal($val))
		{
			$this->propertyUpdated('documentversion');
		}
	}

	protected function setDocumentversionInternal($val)
	{
		$val = ($val === null) ? $val : intval($val);
		$modified = ($this->m_documentversion !== $val);
		if ($modified)
		{
			$this->setOldValue('documentversion', $this->m_documentversion);
			$this->m_documentversion = $val;
			return true;
		}
		return false;
	}

	/**
	 * @return string
	 */
	public function getStartpublicationdate()
	{
		$this->checkLoaded();
		return $this->m_startpublicationdate;
	}
			
	/**
	 * @return string|NULL
	 */
	public function getStartpublicationdateOldValue()
	{
		return $this->getOldValue('startpublicationdate');
	}
			
	/**
	 * @param string $val
	 */
	public function setStartpublicationdate($val)
	{
		$this->checkLoaded();
		if ($this->setStartpublicationdateInternal($val))
		{
			$this->propertyUpdated('startpublicationdate');
		}
	}

	protected function setStartpublicationdateInternal($val)
	{
		$val = ($val === null) ? $val : ($val instanceof \date_Calendar) ? \date_Formatter::format($val, \date_Formatter::SQL_DATE_FORMAT) : is_long($val) ? date(\date_Formatter::SQL_DATE_FORMAT, $val) : $val;
		$modified = ($this->m_startpublicationdate !== $val);
		if ($modified)
		{
			$this->setOldValue('startpublicationdate', $this->m_startpublicationdate);
			$this->m_startpublicationdate = $val;
			return true;
		}
		return false;
	}

	/**
	 * @param string $val
	 * @return void
	 */
	public function setUIStartpublicationdate($val)
	{
		$this->setStartpublicationdate(\date_Converter::convertDateToGMT($val));
	}
	
	/**
	 * @return string
	 */
	public function getUIStartpublicationdate()
	{
		return \date_Converter::convertDateToLocal($this->getStartpublicationdate());
	}

	/**
	 * @return string
	 */
	public function getEndpublicationdate()
	{
		$this->checkLoaded();
		return $this->m_endpublicationdate;
	}
			
	/**
	 * @return string|NULL
	 */
	public function getEndpublicationdateOldValue()
	{
		return $this->getOldValue('endpublicationdate');
	}
			
	/**
	 * @param string $val
	 */
	public function setEndpublicationdate($val)
	{
		$this->checkLoaded();
		if ($this->setEndpublicationdateInternal($val))
		{
			$this->propertyUpdated('endpublicationdate');
		}
	}

	protected function setEndpublicationdateInternal($val)
	{
		$val = ($val === null) ? $val : ($val instanceof \date_Calendar) ? \date_Formatter::format($val, \date_Formatter::SQL_DATE_FORMAT) : is_long($val) ? date(\date_Formatter::SQL_DATE_FORMAT, $val) : $val;
		$modified = ($this->m_endpublicationdate !== $val);
		if ($modified)
		{
			$this->setOldValue('endpublicationdate', $this->m_endpublicationdate);
			$this->m_endpublicationdate = $val;
			return true;
		}
		return false;
	}

	/**
	 * @param string $val
	 * @return void
	 */
	public function setUIEndpublicationdate($val)
	{
		$this->setEndpublicationdate(\date_Converter::convertDateToGMT($val));
	}
	
	/**
	 * @return string
	 */
	public function getUIEndpublicationdate()
	{
		return \date_Converter::convertDateToLocal($this->getEndpublicationdate());
	}

	/**
	 * @return string
	 */
	public function getMetastring()
	{
		$this->checkLoaded();
		return $this->m_metastring;
	}
			
	/**
	 * @return string|NULL
	 */
	public function getMetastringOldValue()
	{
		return $this->getOldValue('metastring');
	}
			
	/**
	 * @param string $val
	 */
	public function setMetastring($val)
	{
		$this->checkLoaded();
		if ($this->setMetastringInternal($val))
		{
			$this->propertyUpdated('metastring');
		}
	}

	protected function setMetastringInternal($val)
	{
		$val = $val === null ? val : strval($val);
		$modified = ($this->m_metastring !== $val);
		if ($modified)
		{
			$this->setOldValue('metastring', $this->m_metastring);
			$this->m_metastring = $val;
			return true;
		}
		return false;
	}

	/**
	 * @return boolean
	 */
	public function getBool1()
	{
		$this->checkLoaded();
		return $this->m_bool1;
	}
			
	/**
	 * @return boolean|NULL
	 */
	public function getBool1OldValue()
	{
		return $this->getOldValue('bool1');
	}
			
	/**
	 * @param boolean $val
	 */
	public function setBool1($val)
	{
		$this->checkLoaded();
		if ($this->setBool1Internal($val))
		{
			$this->propertyUpdated('bool1');
		}
	}

	protected function setBool1Internal($val)
	{
		$val = ($val === null) ? $val : (bool)$val;
		$modified = ($this->m_bool1 !== $val);
		if ($modified)
		{
			$this->setOldValue('bool1', $this->m_bool1);
			$this->m_bool1 = $val;
			return true;
		}
		return false;
	}

	/**
	 * @return integer
	 */
	public function getInt1()
	{
		$this->checkLoaded();
		return $this->m_int1;
	}
			
	/**
	 * @return integer|NULL
	 */
	public function getInt1OldValue()
	{
		return $this->getOldValue('int1');
	}
			
	/**
	 * @param integer $val
	 */
	public function setInt1($val)
	{
		$this->checkLoaded();
		if ($this->setInt1Internal($val))
		{
			$this->propertyUpdated('int1');
		}
	}

	protected function setInt1Internal($val)
	{
		$val = ($val === null) ? $val : intval($val);
		$modified = ($this->m_int1 !== $val);
		if ($modified)
		{
			$this->setOldValue('int1', $this->m_int1);
			$this->m_int1 = $val;
			return true;
		}
		return false;
	}

	/**
	 * @return float
	 */
	public function getFloat1()
	{
		$this->checkLoaded();
		return $this->m_float1;
	}
			
	/**
	 * @return float|NULL
	 */
	public function getFloat1OldValue()
	{
		return $this->getOldValue('float1');
	}
			
	/**
	 * @param float $val
	 */
	public function setFloat1($val)
	{
		$this->checkLoaded();
		if ($this->setFloat1Internal($val))
		{
			$this->propertyUpdated('float1');
		}
	}

	protected function setFloat1Internal($val)
	{
		$val = ($val === null) ? $val : floatval($val);
		$modified = (abs(floatval($this->m_float1) - $val) > 0.0001);
		if ($modified)
		{
			$this->setOldValue('float1', $this->m_float1);
			$this->m_float1 = $val;
			return true;
		}
		return false;
	}

	/**
	 * @return float
	 */
	public function getDecimal1()
	{
		$this->checkLoaded();
		return $this->m_decimal1;
	}
			
	/**
	 * @return float|NULL
	 */
	public function getDecimal1OldValue()
	{
		return $this->getOldValue('decimal1');
	}
			
	/**
	 * @param float $val
	 */
	public function setDecimal1($val)
	{
		$this->checkLoaded();
		if ($this->setDecimal1Internal($val))
		{
			$this->propertyUpdated('decimal1');
		}
	}

	protected function setDecimal1Internal($val)
	{
		$val = ($val === null) ? $val : floatval($val);
		$modified = (abs(floatval($this->m_decimal1) - $val) > 0.0001);
		if ($modified)
		{
			$this->setOldValue('decimal1', $this->m_decimal1);
			$this->m_decimal1 = $val;
			return true;
		}
		return false;
	}

	/**
	 * @return string
	 */
	public function getDate1()
	{
		$this->checkLoaded();
		return $this->m_date1;
	}
			
	/**
	 * @return string|NULL
	 */
	public function getDate1OldValue()
	{
		return $this->getOldValue('date1');
	}
			
	/**
	 * @param string $val
	 */
	public function setDate1($val)
	{
		$this->checkLoaded();
		if ($this->setDate1Internal($val))
		{
			$this->propertyUpdated('date1');
		}
	}

	protected function setDate1Internal($val)
	{
		$val = ($val === null) ? $val : ($val instanceof \date_Calendar) ? \date_Formatter::format($val, \date_Formatter::SQL_DATE_FORMAT) : is_long($val) ? date(\date_Formatter::SQL_DATE_FORMAT, $val) : $val;
		$modified = ($this->m_date1 !== $val);
		if ($modified)
		{
			$this->setOldValue('date1', $this->m_date1);
			$this->m_date1 = $val;
			return true;
		}
		return false;
	}

	/**
	 * @return string
	 */
	public function getDatetime1()
	{
		$this->checkLoaded();
		return $this->m_datetime1;
	}
			
	/**
	 * @return string|NULL
	 */
	public function getDatetime1OldValue()
	{
		return $this->getOldValue('datetime1');
	}
			
	/**
	 * @param string $val
	 */
	public function setDatetime1($val)
	{
		$this->checkLoaded();
		if ($this->setDatetime1Internal($val))
		{
			$this->propertyUpdated('datetime1');
		}
	}

	protected function setDatetime1Internal($val)
	{
		$val = ($val === null) ? $val : ($val instanceof \date_Calendar) ? \date_Formatter::format($val, \date_Formatter::SQL_DATE_FORMAT) : is_long($val) ? date(\date_Formatter::SQL_DATE_FORMAT, $val) : $val;
		$modified = ($this->m_datetime1 !== $val);
		if ($modified)
		{
			$this->setOldValue('datetime1', $this->m_datetime1);
			$this->m_datetime1 = $val;
			return true;
		}
		return false;
	}

	/**
	 * @param string $val
	 * @return void
	 */
	public function setUIDatetime1($val)
	{
		$this->setDatetime1(\date_Converter::convertDateToGMT($val));
	}
	
	/**
	 * @return string
	 */
	public function getUIDatetime1()
	{
		return \date_Converter::convertDateToLocal($this->getDatetime1());
	}

	/**
	 * @return string
	 */
	public function getString1()
	{
		$this->checkLoaded();
		return $this->getI18nObject()->getString1();
	}

	/**
	 * @return string
	 */
	public function getVoString1()
	{
		$this->checkLoaded();
		return $this->getI18nVoObject()->getString1();
	}

	/**
	 * @param string $lang
	 * @return string
	 */
	public function getString1ForLang($lang)
	{
		$this->checkLoaded();
		return $this->getI18nObject($lang)->getString1();
	}
			
	/**
	 * @return string|NULL
	 */
	public function getString1OldValue()
	{
		return $this->getOldValue('string1', $this->getI18nObject()->getLang());
	}
			
	protected function setString1Internal($val)
	{
		$val = $val === null ? val : strval($val);
		$i18nObject = $this->getI18nObject();
		$modified = $i18nObject->setString1($val);
		if ($modified) {$this->setOldValue('string1', $i18nObject->getString1OldValue(), $i18nObject->getLang());}
		return $modified;
	}
	
	/**
	 * @return string
	 */
	public function getString1AsHtml()
	{
		return \f_util_HtmlUtils::textToHtml($this->getString1());
	}

	/**
	 * @return string
	 */
	public function getLongstring1()
	{
		$this->checkLoaded();
		return $this->m_longstring1;
	}
			
	/**
	 * @return string|NULL
	 */
	public function getLongstring1OldValue()
	{
		return $this->getOldValue('longstring1');
	}
			
	/**
	 * @param string $val
	 */
	public function setLongstring1($val)
	{
		$this->checkLoaded();
		if ($this->setLongstring1Internal($val))
		{
			$this->propertyUpdated('longstring1');
		}
	}

	protected function setLongstring1Internal($val)
	{
		$val = $val === null ? val : strval($val);
		$modified = ($this->m_longstring1 !== $val);
		if ($modified)
		{
			$this->setOldValue('longstring1', $this->m_longstring1);
			$this->m_longstring1 = $val;
			return true;
		}
		return false;
	}
	
	/**
	 * @return string
	 */
	public function getLongstring1AsHtml()
	{
		return \f_util_HtmlUtils::textToHtml($this->getLongstring1());
	}

	/**
	 * @return string
	 */
	public function getXml1()
	{
		$this->checkLoaded();
		return $this->m_xml1;
	}
			
	/**
	 * @return string|NULL
	 */
	public function getXml1OldValue()
	{
		return $this->getOldValue('xml1');
	}
			
	/**
	 * @param string $val
	 */
	public function setXml1($val)
	{
		$this->checkLoaded();
		if ($this->setXml1Internal($val))
		{
			$this->propertyUpdated('xml1');
		}
	}

	protected function setXml1Internal($val)
	{
		$val = $val === null ? val : strval($val);
		$modified = ($this->m_xml1 !== $val);
		if ($modified)
		{
			$this->setOldValue('xml1', $this->m_xml1);
			$this->m_xml1 = $val;
			return true;
		}
		return false;
	}

	/**
	 * @return \DOMDocument
	 */
	public function getXml1DOMDocument()
	{
		$document = new \DOMDocument("1.0", "UTF-8");
		if ($this->getXml1() !== null) {$document->loadXML($this->getXml1());}
		return $document;
	}
		
	/**
	 * @param \DOMDocument $document
	 */
	public function setXml1DOMDocument($document)
	{
		 $this->setXml1($document && $document->documentElement ? $document->saveXML() : null);
	}

	/**
	 * @return string
	 */
	public function getLob1()
	{
		$this->checkLoaded();
		return $this->m_lob1;
	}
			
	/**
	 * @return string|NULL
	 */
	public function getLob1OldValue()
	{
		return $this->getOldValue('lob1');
	}
			
	/**
	 * @param string $val
	 */
	public function setLob1($val)
	{
		$this->checkLoaded();
		if ($this->setLob1Internal($val))
		{
			$this->propertyUpdated('lob1');
		}
	}

	protected function setLob1Internal($val)
	{
		$val = $val === null ? val : strval($val);
		$modified = ($this->m_lob1 !== $val);
		if ($modified)
		{
			$this->setOldValue('lob1', $this->m_lob1);
			$this->m_lob1 = $val;
			return true;
		}
		return false;
	}

	/**
	 * @return string
	 */
	public function getRichtext1()
	{
		$this->checkLoaded();
		return $this->getI18nObject()->getRichtext1();
	}

	/**
	 * @return string
	 */
	public function getVoRichtext1()
	{
		$this->checkLoaded();
		return $this->getI18nVoObject()->getRichtext1();
	}

	/**
	 * @param string $lang
	 * @return string
	 */
	public function getRichtext1ForLang($lang)
	{
		$this->checkLoaded();
		return $this->getI18nObject($lang)->getRichtext1();
	}
			
	/**
	 * @return string|NULL
	 */
	public function getRichtext1OldValue()
	{
		return $this->getOldValue('richtext1', $this->getI18nObject()->getLang());
	}
			
	protected function setRichtext1Internal($val)
	{
		$val = $val === null ? val : strval($val);
		$i18nObject = $this->getI18nObject();
		$modified = $i18nObject->setRichtext1($val);
		if ($modified) {$this->setOldValue('richtext1', $i18nObject->getRichtext1OldValue(), $i18nObject->getLang());}
		return $modified;
	}

	/**
	 * @return string
	 */
	public function getRichtext1AsHtml()
	{
		//TODO old XHTMLFragment and BBCode
		return \f_util_HtmlUtils::renderHtmlFragment($this->getRichtext1());
		//$parser = new \website_BBCodeParser();
		//return $parser->convertXmlToHtml($this->getRichtext1());
	}
			
	/**
	 * @param string $val
	 * @return void
	 */
	public function setRichtext1AsBBCode($val)
	{
		$parser = new \website_BBCodeParser();
		$this->setRichtext1($parser->convertBBCodeToXml($val, $parser->getModuleProfile()));
	}
	
	/**
	 * @return string
	 */
	public function getRichtext1AsBBCode()
	{
		$parser = new \website_BBCodeParser();
		return $parser->convertXmlToBBCode($this->getRichtext1());
	}

	/**
	 * @return string
	 */
	public function getJson1()
	{
		$this->checkLoaded();
		return $this->m_json1;
	}
			
	/**
	 * @return string|NULL
	 */
	public function getJson1OldValue()
	{
		return $this->getOldValue('json1');
	}
			
	/**
	 * @param string $val
	 */
	public function setJson1($val)
	{
		$this->checkLoaded();
		if ($this->setJson1Internal($val))
		{
			$this->propertyUpdated('json1');
		}
	}

	protected function setJson1Internal($val)
	{
		$val = ($val === null || is_string($val)) ? $val : \JsonService::getInstance()->encode($val);
		$modified = ($this->m_json1 !== $val);
		if ($modified)
		{
			$this->setOldValue('json1', $this->m_json1);
			$this->m_json1 = $val;
			return true;
		}
		return false;
	}
	
	/**
	 * @return array
	 */
	public function getDecodedJson1()
	{
		$val = $this->getJson1();
		return $val === null ? $val : \JsonService::getInstance()->decode($val);
	}

	/**
	 * @return string
	 */
	public function getObject1()
	{
		$this->checkLoaded();
		return $this->m_object1;
	}
			
	/**
	 * @return string|NULL
	 */
	public function getObject1OldValue()
	{
		return $this->getOldValue('object1');
	}
			
	/**
	 * @param string $val
	 */
	public function setObject1($val)
	{
		$this->checkLoaded();
		if ($this->setObject1Internal($val))
		{
			$this->propertyUpdated('object1');
		}
	}

	protected function setObject1Internal($val)
	{
		$val = ($val === null || is_string($val)) ? $val : serialize($val);
		$modified = ($this->m_object1 !== $val);
		if ($modified)
		{
			$this->setOldValue('object1', $this->m_object1);
			$this->m_object1 = $val;
			return true;
		}
		return false;
	}
	
	/**
	 * @return mixed
	 */
	public function getDecodedObject1()
	{
		$val = $this->getObject1();
		return $val === null ? $val : unserialize($val);
	}

	/**
	 * @return integer
	 */
	public function getDocumentid1()
	{
		$this->checkLoaded();
		return $this->m_documentid1;
	}
			
	/**
	 * @return integer|NULL
	 */
	public function getDocumentid1OldValue()
	{
		return $this->getOldValue('documentid1');
	}
			
	/**
	 * @param integer $val
	 */
	public function setDocumentid1($val)
	{
		$this->checkLoaded();
		if ($this->setDocumentid1Internal($val))
		{
			$this->propertyUpdated('documentid1');
		}
	}

	protected function setDocumentid1Internal($val)
	{
		$val = ($val === null) ? $val : ($val instanceof \Change\Documents\AbstractDocument) ? $val->getId() : intval($val) > 0 ? intval($val) : null;
		$modified = ($this->m_documentid1 !== $val);
		if ($modified)
		{
			$this->setOldValue('documentid1', $this->m_documentid1);
			$this->m_documentid1 = $val;
			return true;
		}
		return false;
	}

	/**
	 * @return \Change\Documents\AbstractDocument|NULL
	 */
	public function getDocumentid1Instance()
	{
		return \Change\Documents\DocumentHelper::getDocumentInstanceIfExists($this->getDocumentid1());
	}
	
	/**
	 * @return integer
	 */
	public function getDocument1OldValueId()
	{
		return $this->getOldValue('document1');
	}
			
	/**
	 * @param \Change\Testing\Documents\Generation $newValue
	 */
	public function setDocument1($newValue)
	{
		$this->checkLoaded();
		$newId = ($newValue instanceof \Change\Documents\AbstractDocument) ? $this->getProvider()->getCachedDocumentId($newValue) : null;
		if ($this->m_document1 != $newId)
		{
			$this->setOldValue('document1', $this->m_document1);
			$this->m_document1 = $newId;
			$this->propertyUpdated('document1');
		}
	}

	/**
	 * @return integer
	 */
	public function getDocument1Id()
	{
		$this->checkLoaded();
		return $this->m_document1;
	}
	
	/**
	 * @return \Change\Testing\Documents\Generation
	 */
	public function getDocument1()
	{
		$this->checkLoaded();
		return ($this->m_document1) ? $this->getProvider()->getCachedDocumentById($this->m_document1) : null;
	}

	/**
	 * @return integer[]
	 */
	public function getDocumentarray1OldValueIds()
	{
		$result = $this->getOldValue('documentarray1');
		if (is_array($result))
		{
			return $result;
		}
		return array();
	}
					
	protected function checkLoadedDocumentarray1()
	{
		$this->checkLoaded();
		if (!is_array($this->m_documentarray1))
		{
			if ($this->getDocumentPersistentState() != self::PERSISTENTSTATE_NEW)
			{
				$this->m_documentarray1 = $this->getProvider()->loadRelations($this, 'documentarray1');
			}
			else
			{
				$this->m_documentarray1 = array();
			}
		}
	}

	/**
	 * @param integer $index
	 * @param \Change\Documents\AbstractDocument $newValue
	 */
	public function setDocumentarray1($index, $newValue)
	{
		if ($newValue instanceof \Change\Documents\AbstractDocument)
		{
			$newId = $this->getProvider()->getCachedDocumentId($newValue); 
			$index = intval($index);
			$this->checkLoadedDocumentarray1();
			if (!in_array($newId, $this->m_documentarray1))
			{
				$this->setOldValue('documentarray1', $this->m_documentarray1);
				if ($index < 0 || $index > count($this->m_documentarray1))
				{
					$index = count($this->m_documentarray1);
				}
				$this->m_documentarray1[$index] = $newId;
				$this->propertyUpdated('documentarray1');
			}
		}
		else
		{
			throw new \Exception(__METHOD__. ': document can not be null');
		}
	}

	/**
	 * @param \Change\Documents\AbstractDocument[] $newValueArray
	 */
	public function setDocumentarray1Array($newValueArray)
	{
		if (is_array($newValueArray))
		{
			$this->checkLoadedDocumentarray1();
			$newValueIds = array(); $dbp = $this->getProvider();
			array_walk($newValueArray, function ($newValue, $index) use (&$newValueIds, $dbp) {
				$newValueIds[] = $dbp->getCachedDocumentId($newValue);
			});
			if ($this->m_documentarray1 != $newValueIds)
			{
				$this->setOldValue('documentarray1', $this->m_documentarray1);
				$this->m_documentarray1 = $newValueIds;
				$this->propertyUpdated('documentarray1');
			}
		}
		else
		{
			throw new \Exception('Invalid type of document array');
		}
	}

	/**
	 * @param \Change\Documents\AbstractDocument $newValue
	 */
	public function addDocumentarray1($newValue)
	{
		if ($newValue instanceof \Change\Documents\AbstractDocument)
		{ 
			$newId = $this->getProvider()->getCachedDocumentId($newValue);
			$this->checkLoadedDocumentarray1();
			if (!in_array($newId, $this->m_documentarray1))
			{
				$this->setOldValue('documentarray1', $this->m_documentarray1);
				$this->m_documentarray1[] = $newId;
				$this->propertyUpdated('documentarray1');
			}
		}
		else
		{
			throw new \Exception(__METHOD__. ': document can not be null');
		}
	}

	/**
	 * @param \Change\Documents\AbstractDocument $value
	 */
	public function removeDocumentarray1($value)
	{
		$this->checkLoadedDocumentarray1();
		if ($value instanceof \Change\Documents\AbstractDocument)
		{
			$valueId = $value->getId();
			$index = array_search($valueId, $this->m_documentarray1);
			if ($index !== false)
			{
				$this->setOldValue('documentarray1', $this->m_documentarray1);
				unset($this->m_documentarray1[$index]);
				$this->propertyUpdated('documentarray1');
			}
		}
	}

	/**
	 * @param integer $index
	 */
	public function removeDocumentarray1ByIndex($index)
	{
		$this->checkLoadedDocumentarray1();
		if (isset($this->m_documentarray1[$index]))
		{
			$this->setOldValue('documentarray1', $this->m_documentarray1);
			unset($this->m_documentarray1[$index]);
			$this->propertyUpdated('documentarray1');
		}
	}

	public function removeAllDocumentarray1()
	{
		$this->checkLoadedDocumentarray1();
		if (count($this->m_documentarray1))
		{
			$this->setOldValue('documentarray1', $this->m_documentarray1);
			$this->m_documentarray1 = array();
			$this->propertyUpdated('documentarray1');
		}
	}

	/**
	 * @param integer $index
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getDocumentarray1($index)
	{
		$this->checkLoadedDocumentarray1();
		return isset($this->m_documentarray1[$index]) ?  $this->getProvider()->getCachedDocumentById($this->m_documentarray1[$index]) : null;
	}
	
	/**
	 * @return integer[]
	 */
	public function getDocumentarray1Ids()
	{
		$this->checkLoadedDocumentarray1();
		return $this->m_documentarray1;
	}

	/**
	 * @return \Change\Documents\AbstractDocument[]
	 */
	public function getDocumentarray1Array()
	{
		$this->checkLoadedDocumentarray1();
		$documents = array(); $dbp = $this->getProvider();
		array_walk($this->m_documentarray1, function ($documentId, $index) use (&$documents, $dbp) {
			$documents[] = $dbp->getCachedDocumentById($documentId);
		});
		return $documents;
	}

	/**
	 * @return \Change\Documents\AbstractDocument[]
	 */
	public function getPublishedDocumentarray1Array()
	{
		$this->checkLoadedDocumentarray1();
		$documents = array(); $dbp = $this->getProvider();
		array_walk($this->m_documentarray1, function ($documentId, $index) use (&$documents, $dbp) {
			$document = $dbp->getCachedDocumentById($documentId);
			if ($document->isPublished()) {$documents[] = $document;}
		});
		return $documents;
	}

	/**
	 * @return integer
	 */
	public function getPublishedDocumentarray1Count()
	{
		return count($this->getPublishedDocumentarray1Array());
	}

	/**
	 * @param \Change\Documents\AbstractDocument $value
	 * @return integer
	 */
	public function getIndexofDocumentarray1($value)
	{
		if ($value instanceof \Change\Documents\AbstractDocument) 
		{
			$this->checkLoadedDocumentarray1();
			$valueId = $this->getProvider()->getCachedDocumentId($value);
			$index = array_search($valueId, $this->m_documentarray1);
			return $index !== false ? $index : -1;
		}
		throw new \Exception(__METHOD__. ': document can not be null');
	}

	/**
	 * @return integer
	 */
	public function getCorrectionid()
	{
		$this->checkLoaded();
		return $this->getI18nObject()->getCorrectionid();
	}

	/**
	 * @return integer
	 */
	public function getVoCorrectionid()
	{
		$this->checkLoaded();
		return $this->getI18nVoObject()->getCorrectionid();
	}

	/**
	 * @param string $lang
	 * @return integer
	 */
	public function getCorrectionidForLang($lang)
	{
		$this->checkLoaded();
		return $this->getI18nObject($lang)->getCorrectionid();
	}
			
	/**
	 * @return integer|NULL
	 */
	public function getCorrectionidOldValue()
	{
		return $this->getOldValue('correctionid', $this->getI18nObject()->getLang());
	}
			
	protected function setCorrectionidInternal($val)
	{
		$val = ($val === null) ? $val : intval($val);
		$i18nObject = $this->getI18nObject();
		$modified = $i18nObject->setCorrectionid($val);
		if ($modified) {$this->setOldValue('correctionid', $i18nObject->getCorrectionidOldValue(), $i18nObject->getLang());}
		return $modified;
	}

	/**
	 * @return integer
	 */
	public function getCorrectionofid()
	{
		$this->checkLoaded();
		return $this->m_correctionofid;
	}
			
	/**
	 * @return integer|NULL
	 */
	public function getCorrectionofidOldValue()
	{
		return $this->getOldValue('correctionofid');
	}
			
	/**
	 * @param integer $val
	 */
	public function setCorrectionofid($val)
	{
		$this->checkLoaded();
		if ($this->setCorrectionofidInternal($val))
		{
			$this->propertyUpdated('correctionofid');
		}
	}

	protected function setCorrectionofidInternal($val)
	{
		$val = ($val === null) ? $val : intval($val);
		$modified = ($this->m_correctionofid !== $val);
		if ($modified)
		{
			$this->setOldValue('correctionofid', $this->m_correctionofid);
			$this->m_correctionofid = $val;
			return true;
		}
		return false;
	}

	/**
	 * @return string
	 */
	public function getS18s()
	{
		$this->checkLoaded();
		return $this->m_s18s;
	}
			
	/**
	 * @return string|NULL
	 */
	public function getS18sOldValue()
	{
		return $this->getOldValue('s18s');
	}
			
	/**
	 * @param string $val
	 */
	public function setS18s($val)
	{
		$this->checkLoaded();
		$this->m_s18sArray = null;
		if ($this->setS18sInternal($val))
		{
			$this->propertyUpdated('s18s');
		}
	}

	protected function setS18sInternal($val)
	{
		$val = $val === null ? val : strval($val);
		$modified = ($this->m_s18s !== $val);
		if ($modified)
		{
			$this->setOldValue('s18s', $this->m_s18s);
			$this->m_s18s = $val;
			return true;
		}
		return false;
	}

	/**
	 * @return void
	 */
	protected function setDefaultValues()
	{
		$this->setDocumentversionInternal(0);
	}
 
	/**
	 * @return \Change\Testing\Documents\Generation
	 */
	public static function getNewInstance()
	{
		return \Change\Testing\Documents\GenerationService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return \Change\Testing\Documents\Generation
	 */
	public static function getInstanceById($documentId)
	{
		return \Change\Testing\Documents\GenerationService::getInstance()->getDocumentInstance($documentId, 'change_testing_generation');
	}
		
	/**
	 * @return GenerationModel
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
		return 'change_testing_generation';
	}
	
	/**
	 * @return \Change\Testing\Documents\GenerationService
	 */
	public function getDocumentService()
	{
		return \Change\Testing\Documents\GenerationService::getInstance();
	}
}
