<?php
namespace Rbs\Media\Std;

/**
 * @name \Rbs\Media\Std\DummyResizerEngine
 */
class DummyResizerEngine
{
	public function resize($inputFileName, $formattedFileName, $maxWidth, $maxHeight)
	{
		copy($inputFileName, $formattedFileName);
	}

	/**
	 * @param $path
	 * @return array
	 */
	public function getImageSize($path)
	{
		return array('height' => null, 'width' => null);
	}
}