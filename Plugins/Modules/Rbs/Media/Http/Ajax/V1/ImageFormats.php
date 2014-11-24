<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Rbs\Media\Http\Ajax\V1;

/**
 * @name \Rbs\Media\Http\Ajax\V1\ImageFormats
 */
class ImageFormats
{
	/**
	 * @var \Rbs\Media\Documents\Image
	 */
	protected $image;

	/**
	 * @return \Rbs\Media\Documents\Image|null
	 */
	public function getImage()
	{
		return $this->image;
	}

	/**
	 * @param \Rbs\Media\Documents\Image $image
	 * @return $this
	 */
	public function setImage(\Rbs\Media\Documents\Image $image)
	{
		$this->image = $image;
		return $this;
	}

	/**
	 * @param \Rbs\Media\Documents\Image $image
	 */
	public function __construct($image)
	{
		if ($image instanceof \Rbs\Media\Documents\Image)
		{
			$this->setImage($image);
		}
	}

	/**
	 * @param array $visualFormats
	 * @return array
	 */
	public function getFormatsData($visualFormats)
	{
		if ($this->image && is_array($visualFormats) && count($visualFormats))
		{
			$formats = ['alt' => $this->image->getCurrentLocalization()->getAlt()];
			foreach ($visualFormats as $formatName => $size)
			{
				if (count($size) == 2)
				{
					$formats[$formatName] = $this->image->getPublicURL($size[0], $size[1]);
				}
			}
			return $formats;
		}
		return [];
	}
}