<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\DocumentArrayProperty
 */
class DocumentArrayProperty implements \Iterator, \Countable, \ArrayAccess
{

	/**
	 * @var DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var string|null
	 */
	protected $modelName;

	/**
	 * @var integer
	 */
	protected $index = 0;

	/**
	 * @var array
	 */
	protected $ids = array();

	/**
	 * @var array|null
	 */
	protected $defaultIds;

	/**
	 * @param DocumentManager $documentManager
	 * @param string $modelName
	 */
	function __construct(DocumentManager $documentManager, $modelName)
	{
		$this->documentManager = $documentManager;
		$this->modelName = $modelName;
	}

	/**
	 * @param DocumentManager $documentManager
	 */
	public function setDocumentManager(DocumentManager $documentManager)
	{
		$this->documentManager = $documentManager;
	}

	/**
	 * @return DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param null|string $modelName
	 */
	public function setModelName($modelName)
	{
		$this->modelName = $modelName;
	}

	/**
	 * @return null|string
	 */
	public function getModelName()
	{
		return $this->modelName;
	}

	/**
	 * @return integer[]
	 */
	public function getIds()
	{
		return array_values($this->ids);
	}

	/**
	 * @param integer[] $ids
	 */
	public function setDefaultIds(array $ids)
	{
		$this->defaultIds = null;
		$this->ids = array();
		foreach ($ids as $id)
		{
			if (($id = intval($id)) > 0)
			{
				$this->ids[] = $id;
			}
		}
	}

	/**
	 * @return integer[]|null
	 */
	public function getDefaultIds()
	{
		return $this->defaultIds;
	}

	/**
	 * @return AbstractDocument[]|null
	 */
	public function getDefaultDocuments()
	{
		if ($this->defaultIds)
		{
			$documents = array();
			foreach($this->defaultIds as $id)
			{
				$document = $this->documentManager->getDocumentInstance($id);
				if ($document)
				{
					$documents[] = $document;
				}
			}
			return $documents;
		}
		return null;
	}

	/**
	 * @return boolean
	 */
	public function isModified()
	{
		return ($this->defaultIds !== null && $this->defaultIds != $this->ids);
	}

	/**
	 * @return AbstractDocument[]
	 */
	public function toArray()
	{
		$documents = array();
		foreach ($this->ids as $id)
		{
			$document = $this->documentManager->getDocumentInstance($id);
			if ($this->isCompatible($document))
			{
				$documents[] = $document;
			}
		}
		return $documents;
	}

	/**
	 * @param AbstractDocument[]|DocumentCollection|DocumentArrayProperty $documents
	 * @throws \InvalidArgumentException
	 */
	public function fromArray($documents)
	{
		if ($this->defaultIds === null)
		{
			$this->defaultIds = $this->ids;
		}
		$this->ids = array();
		if (is_array($documents) || $documents instanceof DocumentCollection || $documents instanceof DocumentArrayProperty)
		{
			/* @var $document AbstractDocument */
			foreach ($documents as $document)
			{
				if (!$document instanceof AbstractDocument)
				{
					continue;
				}
				if (!in_array($document->getId(), $this->ids) && $this->isCompatible($document))
				{
					$this->ids[] = $document->getId();
				}
			}
		}
		else
		{
			throw new \InvalidArgumentException('Argument 1 should be a array or a DocumentCollection', 50001);
		}

		if ($this->defaultIds === $this->ids)
		{
			$this->defaultIds = null;
		}
	}

	/**
	 * @param integer[] $ids
	 */
	public function fromIds(array $ids)
	{
		if ($this->defaultIds === null)
		{
			$this->defaultIds = $this->ids;
		}
		$this->ids = array();
		foreach ($ids as $id)
		{
			if (($id = intval($id)) > 0 && !in_array($id, $this->ids))
			{
				$this->ids[] = $id;
			}
		}

		if ($this->defaultIds === $this->ids)
		{
			$this->defaultIds = null;
		}
	}

	/**
	 * @param AbstractDocument $document
	 * @return boolean
	 */
	public function isCompatible($document)
	{
		if ($document instanceof AbstractDocument)
		{
			if ($this->modelName)
			{
				return ($document->getDocumentModelName() === $this->modelName
					|| in_array($this->modelName, $document->getDocumentModel()->getDescendantsNames()));
			}
			return true;
		}
		return false;
	}

	/**
	 * @return AbstractDocument|null
	 */
	public function current()
	{
		return $this->documentManager->getDocumentInstance($this->ids[$this->index]);
	}

	/**
	 * @return void Any returned value is ignored.
	 */
	public function next()
	{
		$this->index++;
		if ($this->offsetExists($this->index) && $this->current($this->index) === null)
		{
			//Invalid document Id
			$this->next();
		}
	}

	/**
	 * @return integer
	 */
	public function key()
	{
		return $this->index;
	}

	/**
	 * @return boolean The return value will be casted to boolean and then evaluated.
	 * Returns true on success or false on failure.
	 */
	public function valid()
	{
		return isset($this->ids[$this->index]);
	}

	/**
	 * @return void Any returned value is ignored.
	 */
	public function rewind()
	{
		$this->index = 0;
	}

	/**
	 * @param integer $offset
	 * @return boolean true on success or false on failure.
	 */
	public function offsetExists($offset)
	{
		return isset($this->ids[$offset]);
	}

	/**
	 * @param integer $offset
	 * @return \Change\Documents\AbstractDocument|null
	 */
	public function offsetGet($offset)
	{
		return $this->documentManager->getDocumentInstance($this->ids[$offset]);
	}

	/**
	 * @param AbstractDocument $document
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function add($document)
	{
		$this->offsetSet(null, $document);
		return $this;
	}

	/**
	 * @param AbstractDocument $document
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function remove($document)
	{
		$index = $this->indexOf($document);
		if (is_int($index))
		{
			$this->offsetUnset($index);
		}
		return $this;
	}

	/**
	 * @param AbstractDocument $document
	 * @throws \InvalidArgumentException
	 * @return integer|boolean return false if document not found
	 */
	public function indexOf($document)
	{
		if ($document instanceof AbstractDocument)
		{
			$id = $document->getId();
			return array_search($id, $this->ids);
		}
		else
		{
			throw new \InvalidArgumentException('Argument 2 should be a AbstractDocument', 50001);
		}
	}

	/**
	 * @param integer $offset
	 * @param AbstractDocument $document
	 * @throws \InvalidArgumentException
	 */
	public function offsetSet($offset, $document)
	{
		if ($document instanceof AbstractDocument)
		{
			$id = $document->getId();
			if (!in_array($id, $this->ids))
			{
				if ($this->defaultIds === null)
				{
					$this->defaultIds = $this->ids;
				}
				$index = ($offset === null) ? -1 : intval($offset);
				if ($index > $this->count() || $index < 0)
				{
					$this->ids[] = $id;
				}
				else
				{
					$this->ids[$offset] = $id;
				}

				if ($this->ids === $this->defaultIds)
				{
					$this->defaultIds = null;
				}
			}
		}
		else
		{
			throw new \InvalidArgumentException('Argument 2 should be a AbstractDocument', 50001);
		}
	}

	/**
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		if ($this->defaultIds === null)
		{
			$this->defaultIds = $this->ids;
		}
		unset($this->ids[$offset]);
		$this->ids = array_values($this->ids);
		if ($this->index >= $this->count())
		{
			$this->rewind();
		}
		if ($this->ids === $this->defaultIds)
		{
			$this->defaultIds = null;
		}
	}

	/**
	 * @return integer
	 */
	public function count()
	{
		return count($this->ids);
	}
}