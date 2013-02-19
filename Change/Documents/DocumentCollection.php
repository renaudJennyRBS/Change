<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\DocumentCollection
 */
class DocumentCollection implements \Iterator, \Countable, \ArrayAccess
{
	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;
	
	/**
	 * @var integer
	 */
	protected $index = 0;
	
	/**
	 * @var array
	 */
	protected $entries = array();

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param array $array
	 */
	public function __construct(DocumentManager $documentManager, array $array = null)
	{
		$this->documentManager = $documentManager;
		if ($array !== null)
		{
			foreach ($array as $rawValue)
			{
				if ($rawValue instanceof AbstractDocument)
				{
					$this->entries[] = $this->convertToEntry($rawValue);
				}
				elseif (is_array($rawValue))
				{
					if (count($rawValue) == 2 && isset($rawValue[0]) && isset($rawValue[1]))
					{
						$this->entries[] = array(intval($rawValue[0]), strval($rawValue[1]));
					}
					elseif (isset($rawValue['id']) && isset($rawValue['model']))
					{
						$this->entries[] = array(intval($rawValue['id']), strval($rawValue['model']));
					}
				}
				elseif (is_numeric($rawValue))
				{
					$this->entries[] = array(intval($rawValue), null);
				}
			}
		}
	}
	
	
	/**
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->documentManager;
	}
	
	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
	}
	
	/**
	 * @param integer $offset
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return isset($this->entries[$offset]);
	}
	
	/**
	 * @param integer $offset
	 * @return \Change\Documents\AbstractDocument|null
	 */
	public function offsetGet($offset)
	{
		if (isset($this->entries[$offset]))
		{
			return $this->convertToDocument($this->entries[$offset]);
		}
		return null;
	}

	/**
	 * @param integer $offset
	 * @param \Change\Documents\AbstractDocument $value
	 * @throws \InvalidArgumentException
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		if ($value === null)
		{
			if ($offset !== null)
			{
				$this->offsetUnset($offset);
			}
		}
		elseif($value instanceof AbstractDocument)
		{
			if ($offset === null)
			{
				$this->entries[] = $value;
			}
			else
			{
				$index = intval($offset);
				if ($index > $this->count() || $index < 0)
				{
					$this->entries[] = $this->convertToEntry($value);
				}
				else
				{
					$this->entries[$offset] = $this->convertToEntry($value);
				}
			}
		}
		else
		{
			throw new \InvalidArgumentException('Value must be null or \Change\Documents\AbstractDocument');
		}
	}
	
	/**
	 * @param integer $offset
	 */
	public function offsetUnset($offset)
	{
		unset($this->entries[$offset]);
		$this->entries = array_values($this->entries);
	}
	
	/**
	 * @param array $entry
	 * @return \Change\Documents\AbstractDocument|null
	 */
	protected function convertToDocument($entry)
	{
		$model = isset($entry[1]) ?$this->documentManager->getModelManager()->getModelByName($entry[1]) : null;
		return $this->documentManager->getDocumentInstance($entry[0], $model);
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return array
	 */
	protected function convertToEntry(AbstractDocument $document)
	{
		return array($document->getId(), $document->getDocumentModelName());
	}
	
	/**
	 * @return\Change\Documents\AbstractDocument|null
	 */
	public function current()
	{
		return $this->convertToDocument($this->entries[$this->index]);
	}
	
	/**
	 * @return integer
	 */
	public function key()
	{
		 return $this->index;
	}
	
	
	public function next()
	{
		 ++$this->index;
	}
	
	public function rewind()
	{
		$this->index = 0;
	}
	
	/**
	 * @return boolean
	 */
	public function valid()
	{
		return isset($this->entries[$this->index]);
	}
	
	/**
	 * @return integer
	 */
	public function count()
	{
		return count($this->entries);
	}
}