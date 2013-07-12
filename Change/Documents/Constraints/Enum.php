<?php
namespace Change\Documents\Constraints;

/**
 * @name \Change\Documents\Constraints\Enum
 */
class Enum extends \Zend\Validator\AbstractValidator
{
	const NOT_IN_LIST = 'notInList';

	/**
	 * @var string
	 */
	protected $fromList;

	/**
	 * @var string
	 */
	protected $values;

	/**
	 * @var \Change\Documents\AbstractDocument
	 */
	protected $document;

	/**
	 * @param array $params <fromList => modelName>
	 */
	public function __construct($params = array())
	{
		$this->messageTemplates = array(self::NOT_IN_LIST => self::NOT_IN_LIST);
		parent::__construct($params);
	}

	/**
	 * @return string
	 */
	public function getFromList()
	{
		return $this->fromList;
	}

	/**
	 * @param string $fromList
	 */
	public function setFromList($fromList)
	{
		$this->fromList = $fromList;
	}

	/**
	 * @return string
	 */
	public function getValues()
	{
		return $this->values;
	}

	/**
	 * @param string $values
	 */
	public function setValues($values)
	{
		$this->values = $values;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function setDocument($document)
	{
		$this->document = $document;
	}

	/**
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getDocument()
	{
		return $this->document;
	}

	/**
	 * @param  mixed $value
	 * @throws \LogicException
	 * @throws \RuntimeException
	 * @return boolean
	 */
	public function isValid($value)
	{
		$values = $this->getValues();
		$checkVal = trim($value);
		if (is_string($values) && $values !== '')
		{
			foreach (explode(',', $values) as $enumValue)
			{
				if (trim($enumValue) === $checkVal)
				{
					return true;
				}
			}

			$this->error(self::NOT_IN_LIST);
			return false;
		}

		$fromList = $this->getFromList();
		if (is_string($fromList))
		{
			if ($this->getDocument() instanceof \Change\Documents\AbstractDocument)
			{
				$cm = new \Change\Collection\CollectionManager();
				$cm->setDocumentServices($this->getDocument()->getDocumentServices());
				$collection = $cm->getCollection($fromList, array('document' => $this->getDocument()));
				if ($collection === null)
				{
					throw  new \LogicException('Collection ' . $fromList . ' not found', 999999);
				}

				if ($collection->getItemByValue($value) === null)
				{
					$this->error(self::NOT_IN_LIST);
					return false;
				}
			}
			else
			{
				throw new \RuntimeException('Document not set.', 999999);
			}
		}
		return true;
	}
}