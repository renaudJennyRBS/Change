<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\RichtextProperty
 */
class RichtextProperty
{
	const DEFAULT_EDITOR = 'Markdown';

	/**
	 * @var string
	 */
	protected $editor = self::DEFAULT_EDITOR;

	/**
	 * @var boolean|string
	 */
	protected $oldEditor = false;

	/**
	 * @var string|null
	 */
	protected $rawText;

	/**
	 * @var boolean|string|null
	 */
	protected $oldRawText = false;

	/**
	 * @var string|null
	 */
	protected $html;

	/**
	 * @var boolean|string|null
	 */
	protected $oldHtml = false;

	/**
	 * @param string|array $default
	 */
	function __construct($default = null)
	{
		if (is_string($default))
		{
			$this->fromJSONString($default);
			$this->setAsDefault();
		}
		elseif (is_array($default))
		{
			$this->fromArray($default);
			$this->setAsDefault();
		}
		elseif ($default instanceof RichtextProperty)
		{
			$this->fromRichtextProperty($default);
			$this->setAsDefault();
		}
	}

	/**
	 * @return array[]
	 */
	public function toArray()
	{
		return array('e' => $this->editor, 't' => $this->rawText, 'h' => $this->html);
	}

	/**
	 * @param array|null $array
	 * @return $this
	 */
	public function fromArray($array)
	{
		if (is_array($array) && count($array))
		{
			$this->setEditor((isset($array['e'])) ? $array['e'] : self::DEFAULT_EDITOR);
			$this->setRawText((isset($array['t'])) ? $array['t'] : null);
			$this->setHtml(isset($array['h']) ? $array['h'] : null);
		}
		else
		{
			$this->setEditor(self::DEFAULT_EDITOR);
			$this->setRawText(null);
			$this->setHtml(null);
		}
		return $this;
	}

	/**
	 * @param string|null $JSONString
	 * @return $this
	 */
	public function fromJSONString($JSONString)
	{
		if (is_string($JSONString))
		{
			if (substr($JSONString, 0, 1) === '{' && substr($JSONString, -1) === '}')
			{
				$this->fromArray(json_decode($JSONString, true));
			}
			else
			{
				$this->fromArray(array('t' => $JSONString));
			}
		}
		else
		{
			$this->fromArray(null);
		}
		return $this;
	}

	/**
	 * @param RichtextProperty|null $richtext
	 * @return $this
	 */
	public function fromRichtextProperty($richtext)
	{
		if ($richtext instanceof RichtextProperty)
		{
			$this->setEditor($richtext->getEditor());
			$this->setRawText($richtext->getRawText());
			$this->setHtml($richtext->getHtml());
		}
		else
		{
			$this->fromArray(null);
		}
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function toJSONString()
	{
		if ($this->editor === self::DEFAULT_EDITOR && $this->isEmpty())
		{
			return null;
		}
		return json_encode($this->toArray());
	}
	/**
	 * @return boolean
	 */
	public function isEmpty()
	{
		return ($this->rawText == null);
	}

	/**
	 * @param string $editor
	 * @return $this
	 */
	public function setEditor($editor)
	{
		$editor = $editor ? $editor : self::DEFAULT_EDITOR;
		if ($editor !== $this->editor && $this->oldEditor === false)
		{
			$this->oldEditor = $this->editor;
		}
		elseif ($this->oldEditor === $editor)
		{
			$this->oldEditor = false;
		}
		$this->editor = $editor;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getEditor()
	{
		return $this->editor;
	}

	/**
	 * @param null|string $rawText
	 * @return $this
	 */
	public function setRawText($rawText)
	{
		if ($rawText !== $this->rawText && $this->oldRawText === false)
		{
			$this->oldRawText = $this->rawText;
		}
		elseif ($this->oldRawText === $rawText)
		{
			$this->oldRawText = false;
		}
		$this->rawText = $rawText;
		return $this;
	}

	/**
	 * @return null|string
	 */
	public function getRawText()
	{
		return $this->rawText;
	}

	/**
	 * @param null|string $html
	 * @return $this
	 */
	public function setHtml($html)
	{
		if ($html !== $this->html && $this->oldHtml === false)
		{
			$this->oldHtml = $this->html;
		}
		elseif ($this->oldHtml === $html)
		{
			$this->oldHtml = false;
		}
		$this->html = $html;
		return $this;
	}

	/**
	 * @return null|string
	 */
	public function getHtml()
	{
		return $this->html;
	}

	/**
	 * @param RichtextProperty $richText
	 * @return boolean
	 */
	public function equals($richText)
	{
		if ($richText instanceof RichtextProperty)
		{
			return $this->getEditor() == $richText->getEditor() && $this->getRawText() == $richText->getRawText();
		}
		return empty($richText) && $this->isEmpty();
	}

	/**
	 * @return $this
	 */
	public function setAsDefault()
	{
		$this->oldEditor = $this->oldHtml = $this->oldRawText = false;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDefaultJSONString()
	{
		if ($this->isModified())
		{
			$array = array('e' => ($this->oldEditor !== false) ? $this->oldEditor : $this->editor,
				't' => ($this->oldRawText !== false) ? $this->oldRawText : $this->rawText,
				'h' => ($this->oldHtml !== false) ? $this->oldHtml : $this->html);

			if ($array['e'] == self::DEFAULT_EDITOR && $array['t'] == null)
			{
				return null;
			}
			return json_encode($array);
		}
		return $this->toJSONString();
	}

	/**
	 * @return boolean
	 */
	public function isModified()
	{
		return $this->oldEditor !== false || $this->oldHtml !== false || $this->oldRawText !== false;
	}
}