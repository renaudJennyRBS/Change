<?php
namespace Rbs\Media\Std;

/**
 * @name \Rbs\Media\Std\ImagickResizerEngine
 */
class ImagickResizerEngine
{
	/**
	 * @param $path
	 * @return array
	 */
	public function getImageSize($path)
	{
		$imagik = new \Imagick($path);
		return $imagik->valid() ? array('width' => $imagik->getImageWidth(), 'height' => $imagik->getImageHeight()) : array('height' => null, 'width' => null);
	}

	/**
	 * @param string $inputFileName
	 * @param string $formattedFileName
	 * @param array $formatSizeInfo
	 */
	public function resize($inputFileName, $formattedFileName, $maxWidth, $maxHeight)
	{
		$imagik = new \Imagick($inputFileName);
		if (!$imagik->valid())
		{
			copy($inputFileName, $formattedFileName);
			return;
		}
		$origWidth = $imagik->getImageWidth();
		$origHeight = $imagik->getImageHeight();
		list ($width, $height) = $this->computeImageSize($origWidth, $origHeight, $maxWidth, $maxHeight);
		if ($width == $origWidth && $height == $origHeight)
		{
			copy($inputFileName, $formattedFileName);
			return;
		}
		if ($imagik->getNumberImages() > 1)
		{
			copy($inputFileName, $formattedFileName);
		}
		else
		{
			$res = fopen($formattedFileName, 'w');
			$imagik->thumbnailImage($width, $height, true);
			$imagik->writeImageFile($res);
			fclose($res);
		}
	}

	/**
	 * @param $originalWidth
	 * @param $originalHeight
	 * @param $maxWidth
	 * @param $maxHeight
	 * @return array
	 */
	protected function computeImageSize($originalWidth, $originalHeight, $maxWidth, $maxHeight)
	{
		$resourceWidth = $originalWidth;
		$resourceHeight = $originalHeight;
		if ($maxWidth && ($originalWidth > $maxWidth))
		{
			$resourceWidth = $maxWidth;
			$resourceHeight = $resourceWidth * $originalHeight / $originalWidth;
		}

		if ($maxHeight && ($resourceHeight > $maxHeight))
		{
			$resourceHeight = $maxHeight;
			$resourceWidth = $resourceHeight * $originalWidth / $originalHeight;
		}
		$resourceWidth = round($resourceWidth);
		$resourceHeight = round($resourceHeight);
		return array(min($resourceWidth, $originalWidth), min($resourceHeight, $originalHeight));
	}
}