<?php
namespace Change\Documents\Constraints;

/**
 * @name \Change\Documents\Constraints\Unique
 */
class Unique extends \Zend\Validator\AbstractValidator
{
	const NOTUNIQUE = 'notUnique';
	
	/**
	 * @var string
	 */
	protected $modelName;

	/**
	 * @var string
	 */
	protected $propertyName;

	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var integer
	 */
	protected $documentId = 0;
	
 	/**
	 * @param array $params <modelName => modelName, propertyName => propertyName, [documentId => documentId]>
	 */   
	public function __construct($params = array())
	{
		$this->messageTemplates = array(self::NOTUNIQUE => self::NOTUNIQUE);
		$this->messageVariables = array('propertyName' => 'propertyName');
		parent::__construct($params);
	}
	
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return $this->modelName;
	}

	/**
	 * @param string $modelName
	 */
	public function setModelName($modelName)
	{
		$this->modelName = $modelName;
	}

	/**
	 * @return string
	 */
	public function getPropertyName()
	{
		return $this->propertyName;
	}

	/**
	 * @param string $propertyName
	 */
	public function setPropertyName($propertyName)
	{
		$this->propertyName = $propertyName;
	}

	/**
	 * @return integer
	 */
	public function getDocumentId()
	{
		return $this->documentId;
	}

	/**
	 * @param integer $documentId
	 */
	public function setDocumentId($documentId)
	{
		$this->documentId = $documentId;
	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function setApplicationServices($applicationServices)
	{
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function setDocumentServices($documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @param  mixed $value
	 * @return boolean
	 */
	public function isValid($value)
	{
		$modelName = $this->getModelName();
		$model = $this->getDocumentServices()->getModelManager()->getModelByName($modelName);
		if ($model === null)
		{
			throw new \InvalidArgumentException('Invalid document model name: ' . $modelName, 52003);
		}
		
		$property = $model->getProperty($this->getPropertyName());
		if ($property === null)
		{
			throw new \InvalidArgumentException('Invalid property name: ' . $modelName . '::' . $this->getPropertyName(), 52004);
		}	

		$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		
		$query = $qb->select($fb->getDocumentColumn('id'))
			->from($property->getLocalized() ? $fb->getDocumentI18nTable($model->getRootName()) : $fb->getDocumentTable($model->getRootName()))
			->where(
				$fb->logicAnd(
					$fb->neq($fb->getDocumentColumn('id'), $fb->integerParameter('id')),
					$fb->eq($fb->getDocumentColumn($property->getName()), $fb->parameter('value'))
				)
			)->query();
		
		$query->setMaxResults(1);
		$query->bindParameter('id', $this->getDocumentId());
		$query->bindParameter('value', $value);
		$rows = $query->getResults();
		if (count($rows))
		{
			$this->error(self::NOTUNIQUE);
			return false;
		}
		return true;
	}	
}