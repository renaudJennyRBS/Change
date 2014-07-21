<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents;

/**
 * @name \Change\Documents\InlineArrayProperty
 */
class InlineArrayProperty  implements \Iterator, \Countable, \ArrayAccess
{

	/**
	 * @var DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var string
	 */
	protected $modelName;

	/**
	 * @var AbstractInline[]
	 */
	protected $inlineDocuments = [];

	/**
	 * @var AbstractInline[]|null
	 */
	protected $defaultDocuments = null;

	/**
	 * @var \Callable|null
	 */
	private $updateCallback;

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
	 * @param \Callable|null $updateCallback
	 * @return $this
	 */
	public function link($updateCallback)
	{
		if ($updateCallback && is_callable($updateCallback))
		{
			$this->updateCallback = $updateCallback;
		}
		else
		{
			$this->updateCallback = null;
		}
		return $this;
	}

	/**
	 * @return $this
	 */
	public function setAsDefault()
	{
		$this->defaultDocuments = null;
		return $this;
	}

	/**
	 * @return AbstractInline[]|null
	 */
	public function getDefaultDocuments()
	{
		return $this->defaultDocuments;
	}

	/**
	 * @return boolean
	 */
	public function isModified()
	{
		return ($this->defaultDocuments !== null && $this->checkModified());
	}

	/**
	 * @return boolean
	 */
	protected function checkModified()
	{
		if ($this->defaultDocuments === null)
		{
			return false;
		}
		elseif (count($this->defaultDocuments) === count($this->inlineDocuments))
		{
			foreach ($this->inlineDocuments as $index => $inlineDocument)
			{
				if (!$inlineDocument->isEquals($this->defaultDocuments[$index]))
				{
					return true;
				}
			}
			$this->defaultDocuments = null;
			return false;
		}
		return true;
	}

	/**
	 * @param array|bool $dbData
	 * @return $this|array
	 */
	public function dbData($dbData = false)
	{
		if ($dbData === false)
		{
			return $this->toDbData();
		}
		else
		{
			foreach ($this->inlineDocuments as $inlineDocument)
			{
				$inlineDocument->link(null);
			}

			if (is_array($dbData))
			{
				$this->fromDbData($dbData);
			}
			else
			{
				$this->fromDbData([]);
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	protected function toDbData()
	{
		if (count($this->inlineDocuments))
		{
			$callback = function(AbstractInline $inlineDoc) {return $inlineDoc->dbData();};
			return  array_map($callback, $this->inlineDocuments);
		}
		return [];
	}

	/**
	 * @param $array
	 * @throws \InvalidArgumentException
	 * @internal param $array
	 */
	protected function fromDbData(array $array)
	{
		$inlineDocuments = [];
		$updateCallback = function() {$this->saveDefault();};
		foreach ($array as $dbData)
		{
			if (is_array($dbData) && isset($dbData['model']))
			{
				$model = $this->getDocumentManager()->getModelManager()->getModelByName($dbData['model']);
				if ($model && $model->isInline())
				{
					$inlineDocument = $this->getDocumentManager()->getNewInlineInstanceByModel($model, false);
					if ($this->isCompatible($inlineDocument))
					{
						$inlineDocument->dbData($dbData);
						$inlineDocument->link($updateCallback);
						$inlineDocuments[] = $inlineDocument;
					}
				}
			}
		}
		$this->inlineDocuments = $inlineDocuments;
		$this->defaultDocuments = null;
	}

	protected $index = 0;

	/**
	 * @return AbstractInline|null
	 */
	public function current()
	{
		return $this->inlineDocuments[$this->index];
	}

	/**
	 * @return void Any returned value is ignored.
	 */
	public function next()
	{
		$this->index++;
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
		return isset($this->inlineDocuments[$this->index]);
	}

	/**
	 * @return void Any returned value is ignored.
	 */
	public function rewind()
	{
		$this->index = 0;
	}

	/**
	 * @return integer
	 */
	public function count()
	{
		return count($this->inlineDocuments);
	}

	/**
	 * @param integer $offset
	 * @return boolean true on success or false on failure.
	 */
	public function offsetExists($offset)
	{
		return isset($this->inlineDocuments[$offset]);
	}

	/**
	 * @param integer $offset
	 * @return \Change\Documents\AbstractInline|null
	 */
	public function offsetGet($offset)
	{
		return $this->offsetExists($offset) ? $this->inlineDocuments[$offset] : null;
	}

	/**
	 * @param integer $offset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		if ($this->offsetExists($offset))
		{
			$this->saveDefault();
			$this->inlineDocuments[$offset]->link(null);
			unset($this->inlineDocuments[$offset]);
			$this->inlineDocuments = array_values($this->inlineDocuments);
			if ($this->index >= $this->count())
			{
				$this->rewind();
			}
		}
	}

	/**
	 * @param integer $offset
	 * @param \Change\Documents\AbstractInline $document
	 * @throws \InvalidArgumentException
	 */
	public function offsetSet($offset, $document)
	{
		if ($offset !== null)
		{
			$offset = intval($offset);
			if ($offset < 0)
			{
				$offset = null;
			}
		}

		if ($this->isCompatible($document))
		{
			$index = $this->indexOf($document);
			if ($index === $offset)
			{
				//Document already in array on same $offset
				return;
			}

			if ($index !== false)
			{
				$this->offsetUnset($index);
			}
			else
			{
				$this->saveDefault();
			}

			$document->link(function() {$this->saveDefault();});
			if ($offset === null || $offset >= $this->count())
			{
				$this->inlineDocuments[] = $document;
			}
			else
			{
				$this->inlineDocuments[$offset] = $document;
			}
		}
		else
		{
			throw new \InvalidArgumentException('Argument 2 should be a compatible inline document', 50001);
		}
	}

	/**
	 * @api
	 * @param \Change\Documents\AbstractInline $document
	 * @return integer|boolean return false if document not found
	 */
	public function indexOf($document)
	{
		if ($this->isCompatible($document))
		{
			/** @var $inlineDocument \Change\Documents\AbstractInline */
			foreach ($this->inlineDocuments as $index => $inlineDocument)
			{
				if ($document === $inlineDocument)
				{
					return $index;
				}
			}
		}
		return false;
	}

	/**
	 * @api
	 * @param \Change\Documents\AbstractInline $document
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function add($document)
	{
		$this->offsetSet(null, $document);
		return $this;
	}

	/**
	 * @api
	 * @param \Change\Documents\AbstractInline $document
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function remove($document)
	{
		$index = $this->indexOf($document);
		if ($index !== false)
		{
			$document->link(null);
			$this->offsetUnset($index);
		}
		return $this;
	}

	/**
	 * @api
	 * @return $this
	 */
	public function removeAll()
	{
		if ($this->count())
		{
			$this->saveDefault();
			foreach ($this->inlineDocuments as $inlineDocument)
			{
				$inlineDocument->link(null);
			}
			$this->inlineDocuments = [];
			$this->rewind();
		}
		return $this;
	}

	/**
	 * @api
	 * @param \Change\Documents\AbstractInline[] $documents
	 * @return $this
	 */
	public function setAll(array $documents = null)
	{
		$this->removeAll();
		if ($documents !== null)
		{
			foreach ($documents as $document)
			{
				if ($this->isCompatible($document))
				{
					$document->link(function() {$this->saveDefault();});
					$this->inlineDocuments[] = $document;
				}
			}
		}
		return $this;
	}

	/**
	 * @api
	 * @param $document
	 * @return boolean
	 */
	public function isCompatible($document)
	{
		if ($document instanceof AbstractInline)
		{
			if (!$this->modelName || $document->getDocumentModel()->isInstanceOf($this->modelName))
			{
				return true;
			}
		}
		return false;
	}

	protected function saveDefault()
	{
		if ($this->updateCallback !== null)
		{
			call_user_func($this->updateCallback);
		}
		if ($this->defaultDocuments === null)
		{
			$this->defaultDocuments = [];
			foreach ($this->inlineDocuments as $inlineDocument)
			{
				$this->defaultDocuments[] = clone($inlineDocument);
			}
		}
	}
}