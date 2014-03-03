<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
		$result = getimagesize($path);
		if ($result === false)
		{
			$result = getimagesizefromstring(file_get_contents($path));
		}
		return $result ? array('width' => $result[0], 'height' => $result[1]) : array('height' => null, 'width' => null);
	}
}