<?php
namespace Rbs\Commerce\Interfaces;

/**
* @name \Rbs\Commerce\Interfaces\Cart
*/
interface Cart extends \Serializable
{
	/**
	 * @return \Rbs\Commerce\Services\CommerceServices
	 */
	public function getCommerceServices();

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @return $this
	 */
	public function setCommerceServices($commerceServices);

	/**
	 * @return string
	 */
	public function getIdentifier();

	/**
	 * @param integer $ownerId
	 * @return $this
	 */
	public function setOwnerId($ownerId);

	/**
	 * @return integer
	 */
	public function getOwnerId();

	/**
	 * @param integer $webStoreId
	 * @return $this
	 */
	public function setWebStoreId($webStoreId);

	/**
	 * @return integer
	 */
	public function getWebStoreId();

	/**
	 * @return boolean
	 */
	public function isLocked();

	/**
	 * @return boolean
	 */
	public function isEmpty();

	/**
	 * @param \DateTime|null $lastUpdate
	 * @return \DateTime
	 */
	public function lastUpdate(\DateTime $lastUpdate = null);

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getContext();

	/**
	 * @return \Rbs\Commerce\Interfaces\CartLine[]
	 */
	public function getLines();

	/**
	 * @param string $lineKey
	 * @return \Rbs\Commerce\Interfaces\CartLine|null
	 */
	public function getLineByKey($lineKey);

	/**
	 * @param string $lineKey
	 * @return \Rbs\Commerce\Interfaces\CartLine|null
	 */
	public function removeLineByKey($lineKey);

	/**
	 * @return \Rbs\Commerce\Interfaces\CartLine[]
	 */
	public function removeAllLines();

	/**
	 * @param \Rbs\Commerce\Interfaces\BillingArea|null $billingArea
	 * @return $this
	 */
	public function setBillingArea($billingArea);

	/**
	 * @return \Rbs\Commerce\Interfaces\BillingArea|null
	 */
	public function getBillingArea();

	/**
	 * @return string|null
	 */
	public function getCurrencyCode();

	/**
	 * @param string|null $zone
	 * @return $this
	 */
	public function setZone($zone);

	/**
	 * @return string|null
	 */
	public function getZone();

	/**
	 * @param CartLineConfig $cartLineConfig
	 * @param integer $quantity
	 * @return \Rbs\Commerce\Interfaces\CartLine
	 */
	public function getNewLine(\Rbs\Commerce\Interfaces\CartLineConfig $cartLineConfig, $quantity);

	/**
	 * @param \Rbs\Commerce\Interfaces\CartItemConfig $cartItemConfig
	 * @return \Rbs\Commerce\Interfaces\CartItem
	 */
	public function getNewItem(\Rbs\Commerce\Interfaces\CartItemConfig $cartItemConfig);

	/**
	 * @param \Rbs\Commerce\Interfaces\CartLine $line
	 * @param integer $lineNumber
	 * @throws \RuntimeException
	 * @return \Rbs\Commerce\Interfaces\CartLine
	 */
	public function insertLineAt(CartLine $line, $lineNumber = 1);

	/**
	 * @param \Rbs\Commerce\Interfaces\CartLine $line
	 * @throws \RuntimeException
	 * @return \Rbs\Commerce\Interfaces\CartLine
	 */
	public function appendLine(CartLine $line);

	/**
	 * @param string $lineKey
	 * @param integer $newQuantity
	 * @return CartLine|null
	 */
	public function updateLineQuantity($lineKey, $newQuantity);

	/**
	 * @param \Rbs\Commerce\Interfaces\CartItem $item
	 * @param float $priceValue
	 * @return \Rbs\Commerce\Interfaces\CartItem
	 */
	public function updateItemPrice($item, $priceValue);

	/**
	 * @param \Rbs\Commerce\Interfaces\CartItem $item
	 * @param TaxApplication[] $taxApplicationArray
	 * @return \Rbs\Commerce\Interfaces\CartItem
	 */
	public function updateItemTaxes($item, $taxApplicationArray);

	/**
	 * @return float|null
	 */
	public function getPriceValue();

	/**
	 * @return \Rbs\Commerce\Interfaces\CartTax[]
	 */
	public function getTaxes();

	/**
	 * @param \Rbs\Commerce\Interfaces\CartError[] $errors
	 * @return $this
	 */
	public function setErrors(array $errors);

	/**
	 * @param \Rbs\Commerce\Interfaces\CartError $error
	 * @return $this
	 */
	public function addError($error);

	/**
	 * @return boolean
	 */
	public function hasError();

	/**
	 * @return \Rbs\Commerce\Interfaces\CartError[]
	 */
	public function getErrors();

	/**
	 * @return float|null
	 */
	public function getPriceValueWithTax();

	/**
	 * @return array
	 */
	public function toArray();
}