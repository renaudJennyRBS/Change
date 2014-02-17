<?php
namespace Rbs\Commerce\Process;

/**
 * @name \Rbs\Commerce\Process\BaseCoupon
 */
class BaseCoupon implements \Rbs\Commerce\Process\CouponInterface
{
	/**
	 * @var string
	 */
	protected $code;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $options;

	/**
	 * @param array $data
	 */
	public function __construct($data = null)
	{
		if (is_array($data))
		{
			$this->fromArray($data);
		}
	}

	/**
	 * @param string $code
	 * @return $this
	 */
	public function setCode($code)
	{
		$this->code = $code;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param \Zend\Stdlib\Parameters $options
	 * @return $this
	 */
	public function setOptions($options)
	{
		$this->options = $options;
		return $this;
	}

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getOptions()
	{
		if ($this->options === null)
		{
			$this->options = new \Zend\Stdlib\Parameters();
		}
		return $this->options;
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function fromArray(array $array)
	{
		foreach ($array as $name => $value)
		{
			switch ($name)
			{
				case 'code':
					$this->setCode(strval($value));
					break;

				case 'title':
					$this->setTitle(strval($value));
					break;

				case 'options':
					$this->options = null;
					if (is_array($value))
					{
						foreach ($value as $optName => $optValue)
						{
							$this->getOptions()->set($optName, $optValue);
						}
					}
					break;
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array(
			'code' => $this->code,
			'title' => $this->title,
			'options' => $this->getOptions()->toArray()
		);
		return $array;
	}
}