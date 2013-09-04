<?php
namespace Rbs\Stock\Std;

/**
* @name \Rbs\Stock\Std\Reservation
*/
class Reservation implements \Rbs\Stock\Interfaces\Reservation
{
	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $codeSku;

	/**
	 * @var integer
	 */
	protected $skuId;

	/**
	 * @var integer
	 */
	protected $webStoreId;

	/**
	 * @var integer
	 */
	protected $quantity;

	/**
	 * @param integer $id
	 */
	public function __construct($id = null)
	{
		$this->id = $id;
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
	 * @param string $codeSku
	 * @return $this
	 */
	public function setCodeSku($codeSku)
	{
		$this->codeSku = $codeSku;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCodeSku()
	{
		return $this->codeSku;
	}

	/**
	 * @param int $skuId
	 * @return $this
	 */
	public function setSkuId($skuId)
	{
		$this->skuId = $skuId;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getSkuId()
	{
		return $this->skuId;
	}

	/**
	 * @param integer $quantity
	 * @return $this
	 */
	public function setQuantity($quantity)
	{
		$this->quantity = $quantity;
		return $this;
	}

	/**
	 * @param integer $add
	 * @return $this
	 */
	public function addQuantity($add)
	{
		$this->quantity += $add;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}

	/**
	 * @param int $webStoreId
	 * @return $this
	 */
	public function setWebStoreId($webStoreId)
	{
		$this->webStoreId = $webStoreId;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getWebStoreId()
	{
		return $this->webStoreId;
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->codeSku .'/' . $this->webStoreId;
	}

	/**
	 * @param \Rbs\Stock\Interfaces\Reservation $reservation
	 * @return $this
	 */
	public function fromReservation(\Rbs\Stock\Interfaces\Reservation $reservation)
	{
		$this->setQuantity($reservation->getQuantity());
		$this->setCodeSku($reservation->getCodeSku());
		$this->setWebStoreId($reservation->getWebStoreId());
		return $this;
	}

	/**
	 * @param \Rbs\Stock\Interfaces\Reservation $reservation
	 * @return boolean
	 */
	public function isSame($reservation)
	{
		if ($reservation instanceof \Rbs\Stock\Interfaces\Reservation)
		{
			return $this->getKey() == $reservation->getKey();
		}
		return false;
	}
}