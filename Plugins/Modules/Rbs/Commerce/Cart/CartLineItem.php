<?php
namespace Rbs\Commerce\Cart;

use Rbs\Commerce\Interfaces\LineItemInterface;

/**
 * @name \Rbs\Commerce\Cart\CartLineItem
 */
class CartLineItem extends \Rbs\Commerce\Std\BaseLineItem implements LineItemInterface, \Serializable
{

	/**
	 * @var array|null
	 */
	protected $serializedData;

	/**
	 * @param string|array|LineItemInterface $codeSKU
	 */
	function __construct($codeSKU)
	{
		if (is_array($codeSKU))
		{
			$this->fromArray($codeSKU);
		}
		else if ($codeSKU instanceof LineItemInterface)
		{
			$this->fromLineItem($codeSKU);
		}
		else
		{
			$this->setCodeSKU($codeSKU);
		}
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager(\Change\Documents\DocumentManager $documentManager)
	{
		if ($this->serializedData)
		{
			$this->restoreSerializedData($documentManager);
		}
		return $this;
	}

	/**
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize()
	{
		$serializedData = array(
			'codeSKU' => $this->codeSKU,
			'reservationQuantity' => $this->reservationQuantity,
			'price' => $this->price,
			'options' => $this->options);
		return serialize((new CartStorage())->getSerializableValue($serializedData));
	}

	/**
	 * Constructs the object
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 * @param string $serialized
	 * @return void
	 */
	public function unserialize($serialized)
	{
		$this->serializedData = unserialize($serialized);
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 */
	protected function restoreSerializedData(\Change\Documents\DocumentManager $documentManager)
	{
		$serializedData = (new CartStorage())->setDocumentManager($documentManager)->restoreSerializableValue($this->serializedData);
		$this->serializedData = null;
		$this->codeSKU = $serializedData['codeSKU'];
		$this->reservationQuantity = $serializedData['reservationQuantity'];
		$this->price = $serializedData['price'];
		$this->options = $serializedData['options'];
	}
}