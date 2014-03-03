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
 * @name \Rbs\Media\Std\Resizer
 */
class Resizer
{
	/**
	 * @var GDResizerEngine|ImagickResizerEngine|DummyResizerEngine
	 */
	protected $resizeEngine;

	function __construct()
	{
		if (class_exists('Imagick', false))
		{
			$this->resizeEngine = new ImagickResizerEngine();
		}
		else if (function_exists('gd_info'))
		{
			$this->resizeEngine = new GDResizerEngine();
		}
		else
		{
			$this->resizeEngine = new DummyResizerEngine();
		}
	}

	/**
	 * @param $path
	 * @return array
	 */
	public function getImageSize($path)
	{
		return $this->resizeEngine->getImageSize($path);
	}

	/**
	 * @param string $inputFileName
	 * @param string $formattedFileName
	 * @param array $formatSizeInfo
	 */
	public function resize($inputFileName, $formattedFileName, $maxWidth, $maxHeight)
	{
		return $this->resizeEngine->resize($inputFileName, $formattedFileName, $maxWidth, $maxHeight);
	}

}