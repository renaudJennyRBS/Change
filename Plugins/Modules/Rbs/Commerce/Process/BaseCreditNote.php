<?php
namespace Rbs\Commerce\Process;

/**
 * @name \Rbs\Commerce\Process\BaseCreditNote
 */
class BaseCreditNote implements \Rbs\Commerce\Process\CreditNoteInterface
{

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var float|null
	 */
	protected $amount;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $options;

	/**
	 * @param array|\Rbs\Commerce\Process\CreditNoteInterface|null $creditNote
	 */
	public function __construct($creditNote = null)
	{
		if ($creditNote instanceof \Rbs\Commerce\Process\CreditNoteInterface)
		{
			$this->fromArray($creditNote->toArray());
		}
		elseif(is_array($creditNote))
		{
			$this->fromArray($creditNote);
		}
	}

	/**
	 * @param int $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
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
	 * @param float|null $amount
	 * @return $this
	 */
	public function setAmount($amount)
	{
		$this->amount = $amount;
		return $this;
	}

	/**
	 * @return float|null
	 */
	public function getAmount()
	{
		return $this->amount;
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
		$this->options = null;
		$this->id = null;
		$this->amount = null;
		$this->title = null;
		foreach ($array as $name => $value)
		{
			if ($value === null)
			{
				continue;
			}
			switch ($name)
			{
				case 'id':
					$this->setId(intval($value));
					break;
				case 'title':
					$this->setTitle(strval($value));
					break;
				case 'amount':
					$this->setAmount(floatval($value));
					break;
				case 'options':
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
			'id' => $this->id,
			'title' => $this->title,
			'amount' => $this->amount,
			'options' => $this->options ? $this->options->toArray() : null
		);
		return $array;
	}
}