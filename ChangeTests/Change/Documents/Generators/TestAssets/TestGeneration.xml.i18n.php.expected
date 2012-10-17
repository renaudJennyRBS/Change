<?php
namespace Compilation\Change\Testing\Documents;
class GenerationI18n extends \Change\Documents\AbstractI18nDocument
{
	private $m_publicationstatus;
	private $m_string1;
	private $m_richtext1;
	private $m_correctionid;

    /**
     * @internal For framework internal usage only
     * @param array<String, mixed> $propertyBag
     * @return void
     */
	public function setDocumentProperties($propertyBag)
	{
		parent::setDocumentProperties($propertyBag);
		foreach ($propertyBag as $propertyName => $propertyValue)
		{
			switch ($propertyName)
			{
				case 'publicationstatus' : $this->m_publicationstatus = $propertyValue; break;
				case 'string1' : $this->m_string1 = $propertyValue; break;
				case 'richtext1' : $this->m_richtext1 = $propertyValue; break;
				case 'correctionid' : $this->m_correctionid = (null === $propertyValue) ? null : intval($propertyValue); break;
			}
		}
	}

    /**
     * @internal For framework internal usage only
     * @param array<String, mixed> $propertyBag
     * @return void
     */
	public function getDocumentProperties($propertyBag)
	{
		$propertyBag = parent::getDocumentProperties($propertyBag);
		$propertyBag['publicationstatus'] = $this->m_publicationstatus;
		$propertyBag['string1'] = $this->m_string1;
		$propertyBag['richtext1'] = $this->m_richtext1;
		$propertyBag['correctionid'] = $this->m_correctionid;
		return $propertyBag;
	}

	/**
	 * @param string $publicationstatus
	 * @return boolean
	 */
	public function setPublicationstatus($publicationstatus)
	{
		$modified = $this->m_publicationstatus !== $publicationstatus;
		if ($modified)
		{
			if (!array_key_exists('publicationstatus', $this->modifiedProperties))
			{
				$this->modifiedProperties['publicationstatus'] = $this->m_publicationstatus;
			}
			$this->m_publicationstatus = $publicationstatus;
			$this->m_modified = true;
			return true;
		}
		return false;
	}
				
	/**
	 * @return string
	 */
	public function getPublicationstatus()
	{
		return $this->m_publicationstatus;
	}
			
	/**
	 * @return string|NULL
	 */
	public final function getPublicationstatusOldValue()
	{
		return array_key_exists('publicationstatus', $this->modifiedProperties) ? $this->modifiedProperties['publicationstatus'] : null;
	}

	/**
	 * @param string $string1
	 * @return boolean
	 */
	public function setString1($string1)
	{
		$modified = $this->m_string1 !== $string1;
		if ($modified)
		{
			if (!array_key_exists('string1', $this->modifiedProperties))
			{
				$this->modifiedProperties['string1'] = $this->m_string1;
			}
			$this->m_string1 = $string1;
			$this->m_modified = true;
			return true;
		}
		return false;
	}
				
	/**
	 * @return string
	 */
	public function getString1()
	{
		return $this->m_string1;
	}
			
	/**
	 * @return string|NULL
	 */
	public final function getString1OldValue()
	{
		return array_key_exists('string1', $this->modifiedProperties) ? $this->modifiedProperties['string1'] : null;
	}

	/**
	 * @param string $richtext1
	 * @return boolean
	 */
	public function setRichtext1($richtext1)
	{
		$modified = $this->m_richtext1 !== $richtext1;
		if ($modified)
		{
			if (!array_key_exists('richtext1', $this->modifiedProperties))
			{
				$this->modifiedProperties['richtext1'] = $this->m_richtext1;
			}
			$this->m_richtext1 = $richtext1;
			$this->m_modified = true;
			return true;
		}
		return false;
	}
				
	/**
	 * @return string
	 */
	public function getRichtext1()
	{
		return $this->m_richtext1;
	}
			
	/**
	 * @return string|NULL
	 */
	public final function getRichtext1OldValue()
	{
		return array_key_exists('richtext1', $this->modifiedProperties) ? $this->modifiedProperties['richtext1'] : null;
	}

	/**
	 * @param integer $correctionid
	 * @return boolean
	 */
	public function setCorrectionid($correctionid)
	{
		$modified = $this->m_correctionid !== $correctionid;
		if ($modified)
		{
			if (!array_key_exists('correctionid', $this->modifiedProperties))
			{
				$this->modifiedProperties['correctionid'] = $this->m_correctionid;
			}
			$this->m_correctionid = $correctionid;
			$this->m_modified = true;
			return true;
		}
		return false;
	}
				
	/**
	 * @return integer
	 */
	public function getCorrectionid()
	{
		return $this->m_correctionid;
	}
			
	/**
	 * @return integer|NULL
	 */
	public final function getCorrectionidOldValue()
	{
		return array_key_exists('correctionid', $this->modifiedProperties) ? $this->modifiedProperties['correctionid'] : null;
	}

	/**
	 * @return void
	 */
	public function setDefaultValues()
	{
		parent::setDefaultValues();
		$this->setPublicationstatus('DRAFT');
		$this->setModifiedProperties();
	}
}
