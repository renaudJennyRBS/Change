<?php
namespace Change\I18n;

/**
 * @name \Change\I18n\DefinitionKey
 */
class DefinitionKey
{
	const TEXT = 'TEXT';
	const HTML = 'HTML';
	
	/**
	 * @var string
	 */
	protected $id;
	
	/**
	 * @var string
	 */
	protected $text;
	
	/**
	 * @var integer
	 */
	protected $format;
	
	/**
	 * @param string $id
	 * @param string $text
	 * @param integer $format
	 */
	public function __construct($id, $text = null, $format = self::TEXT)
	{
		$this->id = $id;
		$this->text = $text;
		$this->format = $format;
	}
	
	/**
	 * @param string $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}
	
	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * @param string $text
	 */
	public function setText($text)
	{
		$this->text = $text;
	}
	
	/**
	 * @return string
	 */
	public function getText()
	{
		return $this->text;
	}
	
	/**
	 * @param integer $format
	 */
	public function setFormat($format = self::TEXT)
	{
		$this->format = $format;
	}
	
	/**
	 * DefinitionKey::TEXT | DefinitionKey::HTML
	 * @return integer
	 */
	public function getFormat()
	{
		return $this->format;
	}
}