<?php
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