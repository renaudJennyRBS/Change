<?php
namespace Change\I18n;

/**
 * @name \Change\I18n\DefinitionCollection
 */
class DefinitionCollection
{
	/**
	 * @var string
	 */
	protected $LCID;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var boolean
	 */
	protected $loaded = false;

	/**
	 * @var string[]
	 */
	protected $includesPaths = array();

	/**
	 * @var \Change\I18n\DefinitionKey[]
	 */
	protected $definitionKeys = array();

	/**
	 * @param string $LCID
	 * @param string $path
	 */
	public function __Construct($LCID, $path)
	{
		$this->LCID = $LCID;
		$this->path = $path;
	}

	/**
	 * @return string
	 */
	public function getLCID()
	{
		return $this->LCID;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getFilePath()
	{
		return $this->getPath() . DIRECTORY_SEPARATOR . $this->getLCID() . '.xml';
	}

	/**
	 * @param string[] $includesPaths
	 */
	public function setIncludesPaths($includesPaths)
	{
		$this->includesPaths = $includesPaths;
	}

	/**
	 * @return string[]
	 */
	public function getIncludesPaths()
	{
		return $this->includesPaths;
	}

	/**
	 * @param string $path
	 */
	public function addIncludePath($path)
	{
		$this->includesPaths[] = $path;
	}

	/**
	 * @param \Change\I18n\DefinitionKey[] $definitionKeys
	 */
	public function setDefinitionKeys($definitionKeys)
	{
		$this->definitionKeys = $definitionKeys;
	}

	/**
	 * @return \Change\I18n\DefinitionKey[]
	 */
	public function getDefinitionKeys()
	{
		return $this->definitionKeys;
	}

	/**
	 * @param \Change\I18n\DefinitionKey $definitionKey
	 */
	public function addDefinitionKey(\Change\I18n\DefinitionKey $definitionKey)
	{
		$this->definitionKeys[$definitionKey->getId()] = $definitionKey;
	}

	/**
	 * @param string $id
	 * @return boolean
	 */
	public function hasDefinitionKey($id)
	{
		return isset($this->definitionKeys[$id]);
	}

	/**
	 * @param string $id
	 * @return \Change\I18n\DefinitionKey|null
	 */
	public function getDefinitionKey($id)
	{
		return isset($this->definitionKeys[$id]) ? $this->definitionKeys[$id] : null;
	}

	/**
	 * Load and parse the file.
	 */
	public function load()
	{
		if ($this->loaded)
		{
			return;
		}

		$filePath = $this->getFilePath();
		if (!file_exists($filePath))
		{
			$this->loaded = true;
			return;
		}

		$dom = new \DOMDocument('1.0', 'UTF-8');
		if ($dom->load($this->getFilePath()))
		{
			foreach ($dom->documentElement->childNodes as $node)
			{
				/* @var $node \DOMElement */
				if ($node->nodeType == XML_ELEMENT_NODE)
				{
					if ($node->nodeName == 'include')
					{
						$this->addIncludePath($node->getAttribute('id'));
					}
					elseif ($node->nodeName == 'key')
					{
						$id = strtolower($node->getAttribute('id'));
						$text = $node->textContent;
						$format = $node->getAttribute('format') === 'html' ? DefinitionKey::HTML : DefinitionKey::TEXT;
						$this->addDefinitionKey(new DefinitionKey($id, $text, $format));
					}
				}
			}
		}
		$this->loaded = true;
	}
}