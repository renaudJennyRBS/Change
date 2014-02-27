<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\Result;

/**
 * @name \Change\Http\Rest\Result\DocumentCorrectionResult
 */
class DocumentCorrectionResult extends DocumentResult
{
	/**
	 * @var array
	 */
	protected $correctionInfos = array();

	/**
	 * @param array $correction
	 */
	public function setCorrectionInfos($correction)
	{
		$this->correctionInfos = $correction;
	}

	/**
	 * @return array
	 */
	public function getCorrectionInfos()
	{
		return $this->correctionInfos;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setCorrectionValue($name, $value)
	{
		$this->correctionInfos[$name] = $value;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array =  parent::toArray();
		$array['correction'] = $this->convertToArray($this->getCorrectionInfos());
		return $array;
	}
}