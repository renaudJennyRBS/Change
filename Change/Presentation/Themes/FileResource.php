<?php
namespace Change\Presentation\Themes;

use Change\Presentation\Interfaces\ThemeResource;

/**
 * @name \Change\Presentation\Themes\FileResource
 */
class FileResource implements ThemeResource
{
	/**
	 * @var \SplFileInfo
	 */
	protected $splFileInfo;

	/**
	 * @param string $filePath
	 */
	function __construct($filePath)
	{
		$this->splFileInfo = new \SplFileInfo($filePath);
	}

	/**
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->splFileInfo->isReadable();
	}

	/**
	 * @return \Datetime
	 */
	public function getModificationDate()
	{
		if ($this->isValid())
		{
			return \DateTime::createFromFormat('U', $this->splFileInfo->getMTime());
		}
		return new \DateTime();
	}

	/**
	 * @return string
	 */
	public function getContent()
	{
		if ($this->isValid())
		{
			return file_get_contents($this->splFileInfo->getPathname());
		}
		return null;
	}

	/**
	 * @return string
	 */
	public function getContentType()
	{
		$extension = $this->splFileInfo->getExtension();
		switch ($extension)
		{
			case 'css':
				return 'text/css';
			case 'js':
				return 'application/javascript';
			case 'png':
				return 'image/png';
			case 'gif':
				return 'image/gif';
			case 'jpg':
			case 'jpeg':
				return 'image/jpeg';
			case 'svg':
				return 'image/svg+xml';
			case 'xml':
				return 'text/xml';
			default :
				return 'application/octet-stream';
		}
	}
}