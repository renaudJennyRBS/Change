<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Images;

/**
 * @name \Change\Presentation\Images\DummyResizerEngine
 */
class DummyResizerEngine
{
	/**
	 * @param string $inputFileName
	 * @param string $formattedFileName
	 * @param integer $maxWidth
	 * @param integer $maxHeight
	 */
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
		return $result ? ['width' => $result[0], 'height' => $result[1]] : ['height' => null, 'width' => null];
	}
}