<?php
namespace Rbs\Media\Std;

/**
 * @name \Rbs\Media\Std\GDResizerEngine
 */
class GDResizerEngine
{

	/**
	 * @param $path
	 * @return array
	 */
	public function getImageSize($path)
	{
		$result = getimagesize($path);
		return $result ? array('width' => $result[0], 'height' => $result[1]) : array('height' => null, 'width' => null);
	}

	/**
	 * @param string $inputFileName
	 * @param string $formattedFileName
	 * @param array $formatSizeInfo
	 */
	public function resize($inputFileName, $formattedFileName, $maxWidth, $maxHeight)
	{
		$sizeInfo = getimagesize($inputFileName);
		if ($sizeInfo === false)
		{
			copy($inputFileName, $formattedFileName);
			return;
		}
		$imageType = $sizeInfo[2];
		list ($width, $height) = $this->computeImageSize($sizeInfo[0], $sizeInfo[1], $maxWidth, $maxHeight);
		if ($width == $sizeInfo[0] && $height == $sizeInfo[1])
		{
			copy($inputFileName, $formattedFileName);
			return;
		}
		switch ($imageType)
		{
			case IMAGETYPE_GIF :
				if ($this->isGifAnim($inputFileName))
				{
					copy($inputFileName, $formattedFileName);
				}
				else
				{
					$imageSrc = imagecreatefromgif($inputFileName);
					$colorTransparent = imagecolortransparent($imageSrc);
					$imageFormatted = imagecreate($width, $height);
					imagepalettecopy($imageFormatted, $imageSrc);
					imagefill($imageFormatted, 0, 0, $colorTransparent);
					imagecolortransparent($imageFormatted, $colorTransparent);
					imagecopyresized($imageFormatted, $imageSrc, 0, 0, 0, 0, $width, $height, $sizeInfo[0], $sizeInfo[1]);
					imagegif($imageFormatted, $formattedFileName);
				}
				break;
			case IMAGETYPE_PNG:
				$imageSrc = imagecreatefrompng($inputFileName);
				$imageFormatted = imagecreatetruecolor($width, $height);
				imageAlphaBlending($imageFormatted, false);
				imageSaveAlpha($imageFormatted, true);
				imagecopyresampled($imageFormatted, $imageSrc, 0, 0, 0, 0, $width, $height, $sizeInfo[0], $sizeInfo[1]);
				imagepng($imageFormatted, $formattedFileName);
				break;
			case IMAGETYPE_JPEG :
				$imageSrc = imagecreatefromjpeg($inputFileName);
				$imageFormatted = imagecreatetruecolor($width, $height);
				imagecopyresampled($imageFormatted, $imageSrc, 0, 0, 0, 0, $width, $height, $sizeInfo[0], $sizeInfo[1]);
				imagejpeg($imageFormatted, $formattedFileName, 90);
				break;
			default:
				copy($inputFileName, $formattedFileName);
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

	/**
	 * Return TRUE if the given file is an animated GIF.
	 * @param string $filePath
	 * @return boolean
	 */
	protected function isGifAnim($filePath)
	{
		$isGifAnim = false;
		if (is_readable($filePath))
		{
			$gifContent = file_get_contents($filePath);
			$contentPosition = 0;
			$frameCount = 0;
			while ($frameCount < 2)
			{
				$firstHeader = strpos($gifContent, "\x00\x21\xF9\x04", $contentPosition);
				if ($firstHeader === false)
				{
					break;
				}
				else
				{
					$contentPosition = $firstHeader + 1;
					$secondHeader = strpos($gifContent, "\x00\x2C", $contentPosition);

					if ($secondHeader === false)
					{
						break;
					}
					else
					{
						if ($firstHeader + 8 == $secondHeader)
						{
							$frameCount++;
						}

						$contentPosition = $secondHeader + 1;
					}
				}
			}

			if ($frameCount > 1)
			{
				$isGifAnim = true;
			}
		}

		return $isGifAnim;
	}
}