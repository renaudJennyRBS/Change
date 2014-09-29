<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Http\Ajax;

/**
* @name \Rbs\Commerce\Http\Ajax\Cart
*/
class Cart
{
	/**
	 * @var \Rbs\Commerce\Cart\CartManager
	 */
	protected $cartManager;

	/**
	 * @var \Rbs\Catalog\CatalogManager
	 */
	protected $catalogManager;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var array
	 */
	protected $context;

	/**
	 * Default actionPath: Rbs/Commerce/Cart
	 *
	 * @param \Change\Http\Event $event
	 */
	public function getCart(\Change\Http\Event $event)
	{
		/** @var \Rbs\Commerce\CommerceServices $commerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!$commerceServices)
		{
			return;
		}

		$this->cartManager = $commerceServices->getCartManager();
		$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
		$this->context = $event->paramsToArray();
		$cartData = $this->cartManager->getCartData($cartIdentifier, $this->context);

		$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Commerce/Cart', $cartData);
		$event->setResult($result);
	}

	/**
	 * Default actionPath: Rbs/Commerce/Cart/Transaction
	 * Event Params:
	 *  - website
	 *  - dataSets: connectors
	 *  - data:
	 *    - returnSuccessFunction
	 *
	 * @param \Change\Http\Event $event
	 */
	public function getCartTransaction(\Change\Http\Event $event)
	{
		/** @var \Rbs\Commerce\CommerceServices $commerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!$commerceServices)
		{
			return;
		}
		$this->cartManager = $commerceServices->getCartManager();
		$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
		$cart = $cartIdentifier ? $this->cartManager->getCartByIdentifier($cartIdentifier) : null;
		if ($cart)
		{
			$transactionData = $commerceServices->getProcessManager()->getCartTransactionData($cart, $event->paramsToArray());
			if (is_array($transactionData))
			{
				if (isset($transactionData['errors']))
				{
					$message = $event->getApplicationServices()->getI18nManager()->trans('m.rbs.commerce.front.new_transaction_error', ['ucf']);
					$result = new \Change\Http\Ajax\V1\ErrorResult(null, $message, \Zend\Http\Response::STATUS_CODE_409);
					$result->setData($transactionData['errors']);
					$event->setResult($result);
				}
				else
				{
					$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Commerce/Cart/Transaction', $transactionData);
					$event->setResult($result);
				}
			}
		}
	}

	/**
	 * Default actionPath: Rbs/Commerce/Cart/ShippingFeesEvaluation
	 * Event Params:
	 *  - website
	 *  -
	 *  - data:
	 *
	 * @param \Change\Http\Event $event
	 */
	public function getShippingFeesEvaluation(\Change\Http\Event $event) {
		/** @var \Rbs\Commerce\CommerceServices $commerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!$commerceServices)
		{
			return;
		}
		$this->cartManager = $commerceServices->getCartManager();
		$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
		$cart = $cartIdentifier ? $this->cartManager->getCartByIdentifier($cartIdentifier) : null;
		if ($cart)
		{
			$process = $commerceServices->getProcessManager()->getOrderProcessByCart($cart);
			if ($process)
			{
				$context = $event->paramsToArray();
				$shippingFeesEvaluationData = $commerceServices->getProcessManager()->getShippingFeesEvaluation($process, $cart, $context);
				$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Commerce/Cart/ShippingFeesEvaluation', $shippingFeesEvaluationData);
				$event->setResult($result);
			}
		}
	}


	/**
	 * @param \Change\Http\Event $event
	 */
	public function updateCart(\Change\Http\Event $event)
	{
		/** @var \Rbs\Commerce\CommerceServices $commerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!$commerceServices)
		{
			return;
		}

		$data = $event->getParam('data');
		if (!is_array($data) || !count($data))
		{
			return;
		}
		$acceptedCommands = array_flip(['addProducts', 'updateLinesQuantity', 'setZone', 'updateContext', 'setAccount', 'setAddress',
			'setShippingModes', 'setCoupons']);
		$commands = array_intersect_key($data, $acceptedCommands);
		if (!count($commands))
		{
			return;
		}
		$this->cartManager = $commerceServices->getCartManager();
		$this->catalogManager = $commerceServices->getCatalogManager();
		$this->context = $event->paramsToArray();
		$this->documentManager = $event->getApplicationServices()->getDocumentManager();

		$commerceContext = $commerceServices->getContext();
		$cartManager = $commerceServices->getCartManager();
		$cartIdentifier = $commerceContext->getCartIdentifier();
		$cart = ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
		$updatedDataSet = [];

		if (isset($data['addProducts']) && is_array($data['addProducts']) && count($data['addProducts']))
		{
			if ((!$cart || $cart->isLocked()))
			{
				$cart = $cartManager->getNewCart($commerceContext->getWebStore(), $commerceContext->getBillingArea(), $commerceContext->getZone());

				$currentUser = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
				$cart->setUserId($currentUser->authenticated() ? $currentUser->getId() : 0);

				$commerceContext->setCartIdentifier($cart->getIdentifier());
				$commerceContext->save();
			}
			$productsData = $data['addProducts'];
			$updatedDataSet['addProducts'] = $this->addProducts($cart, $productsData);
		}

		if (!$cart)
		{
			return;
		}
		elseif ($cart->isLocked())
		{
			$cart = $cartManager->getUnlockedCart($cart);
		}

		if (isset($data['updateLinesQuantity']) && is_array($data['updateLinesQuantity']) && count($data['updateLinesQuantity'])) {
			$updatedDataSet['updateLinesQuantity'] = $this->updateLinesQuantity($cart, $data['updateLinesQuantity']);
		}

		if (isset($data['setZone']) && is_array($data['setZone']) && count($data['setZone'])) {
			$updatedDataSet['setZone'] = $this->setZone($cart, $data['setZone']);
		}

		if (isset($data['updateContext']) && is_array($data['updateContext']) && count($data['updateContext'])) {
			$updatedDataSet['updateContext'] = $this->updateContext($cart, $data['updateContext']);
		}

		if (isset($data['setAccount']) && is_array($data['setAccount']) && count($data['setAccount'])) {
			$updatedDataSet['setAccount'] = $this->setAccount($cart, $data['setAccount']);
		}

		if (isset($data['setAddress']) && is_array($data['setAddress'])) {
			/** @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices = $event->getServices('genericServices');
			$updatedDataSet['setAddress'] = $this->setAddress($cart, $data['setAddress'], $genericServices->getGeoManager());
		}

		if (isset($data['setShippingModes']) && is_array($data['setShippingModes'])) {
			/** @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices = $event->getServices('genericServices');
			$updatedDataSet['setShippingModes'] = $this->setShippingModes($cart, $data['setShippingModes'], $genericServices->getGeoManager());
		}

		if (isset($data['setCoupons']) && is_array($data['setCoupons'])) {
			$updatedDataSet['setCoupons'] = $this->setCoupons($cart, $data['setCoupons']);
		}

		$currentUser = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
		if ($currentUser->authenticated())
		{
			$cart->setUserId($currentUser->getId());
			if (!$cart->getEmail()) {
				$userDocument = $this->documentManager->getDocumentInstance($currentUser->getId());
				if ($userDocument instanceof \Rbs\User\Documents\User) {
					$cart->setEmail($userDocument->getEmail());
				}
			}
			$cart->getContext()->set('userName', $currentUser->getName());
		}

		$cartManager->normalize($cart);
		$cartManager->saveCart($cart);

		if ($commerceContext->getCartIdentifier() !== $cart->getIdentifier())
		{
			$commerceContext->setCartIdentifier($cart->getIdentifier());
			$commerceContext->save();
		}

		$cartData = $cartManager->getCartData($cart, $event->paramsToArray());
		$cartData['updated'] = $updatedDataSet;

		$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Commerce/Cart', $cartData);
		$event->setResult($result);
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $productsData ['productId' => integer, 'quantity' => integer, 'options' => hashtable]
	 * @return array
	 */
	protected function addProducts(\Rbs\Commerce\Cart\Cart $cart, array $productsData)
	{
		$dataSet = [];
		$productContext = ['detailed' => false,
			'data' =>[
				'webStoreId' => $cart->getWebStoreId(),
				'billingAreaId' => $cart->getBillingArea() ? $cart->getBillingArea()->getId() : 0,
				'zone' => $cart->getZone()]];

		foreach ($productsData as $productLineData)
		{
			if (is_array($productLineData) && isset($productLineData['productId']))
			{
				$productContext['data']['options'] = isset($productLineData['options']) && is_array($productLineData['options']) ? $productLineData['options'] : [];
				$productData = $this->catalogManager->getProductData($productLineData['productId'], $productContext);
				if ($productData && isset($productData['cart']['key']))
				{
					$lineData = ['key' => $productData['cart']['key'], 'quantity' => 1, 'designation' => $productData['common']['title']];
					$lineData['items'][] = ['codeSKU' => $productData['stock']['sku'], 'reservationQuantity' => 1];
					$lineData['options']  = ['productId' => $productData['common']['id']];
					$lineData['options'] = array_merge($productContext['data']['options'], $lineData['options']);
					if (isset($productLineData['quantity']) && is_numeric($productLineData['quantity']))
					{
						$lineData['quantity'] = intval($productLineData['quantity']);
					}
					$result = $this->addLine($cart, $lineData);
					if ($result) {
						$modalContentUrl = $this->getModalContentUrl($productLineData);
						if ($modalContentUrl) {
							$result['modalContentUrl'] = $modalContentUrl;
						}
						$dataSet[] = $result;
					}
				}
			}
		}
		return $dataSet;
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $lineData
	 * @return array|null
	 */
	protected function addLine(\Rbs\Commerce\Cart\Cart $cart, array $lineData)
	{
		$line = $cart->getNewLine($lineData);
		if ($line->getKey() && ($line->getQuantity() > 0) && count($line->getItems()))
		{
			$previousLine = $this->cartManager->getLineByKey($cart, $line->getKey());
			if ($previousLine)
			{
				$this->cartManager->updateLineQuantityByKey($cart, $line->getKey(), $previousLine->getQuantity() + $line->getQuantity());
			}
			else
			{
				$this->cartManager->addLine($cart, $line);
			}
			return ['key' => $line->getKey()];
		}
		return null;
	}

	/**
	 * @param array $modalInfo
	 * @return string|null
	 */
	protected function getModalContentUrl(array $modalInfo)
	{
		if (isset($modalInfo['sectionPageFunction']) && isset($this->context['website']))
		{
			$website = $this->context['website'];
			if ($website instanceof \Change\Presentation\Interfaces\Website)
			{
				$product = $this->documentManager->getDocumentInstance($modalInfo['productId']);
				if ($product instanceof \Rbs\Catalog\Documents\Product)
				{
					$query = ['sectionPageFunction' => $modalInfo['sectionPageFunction']];
					if (isset($modalInfo['themeName']))
					{
						$query['themeName'] = $modalInfo['themeName'];
					}
					$urlManager = $website->getUrlManager($website->getLCID());
					$absoluteUrl = $urlManager->absoluteUrl(true);
					$section = isset($this->context['section']) ? $this->context['section'] : null;
					if (!$section || $section instanceof \Change\Presentation\Interfaces\Website)
					{
						$url = $urlManager->getCanonicalByDocument($product, $query);
					}
					else
					{
						$url = $urlManager->getByDocument($product, $section, $query);
					}
					$urlManager->absoluteUrl($absoluteUrl);
					return $url->normalize()->toString();
				}
			}
		}
		return null;
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $linesQuantity
	 * @return array
	 */
	protected function updateLinesQuantity(\Rbs\Commerce\Cart\Cart $cart, array $linesQuantity)
	{
		$dataSet = [];
		foreach ($linesQuantity as $lineQuantity)
		{
			if (is_array($lineQuantity) && isset($lineQuantity['key']) && isset($lineQuantity['quantity'])) {
				$key = $lineQuantity['key']; $quantity = intval($lineQuantity['quantity']);

				$previousLine = $this->cartManager->getLineByKey($cart, $key);
				if ($previousLine)
				{
					if ($quantity > 0)
					{
						$this->cartManager->updateLineQuantityByKey($cart, $key, intval($lineQuantity['quantity']));
					}
					else
					{
						$this->cartManager->removeLineByKey($cart, $key);
					}
					$dataSet[] = ['key' => $key];
				}
			}
		}
		return $dataSet;
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $zoneData
	 * @return array
	 */
	protected function setZone(\Rbs\Commerce\Cart\Cart $cart, array $zoneData)
	{
		$dataSet = [];
		if (array_key_exists('zone', $zoneData))
		{
			$zone = $zoneData['zone'];
			if ($zone === null || is_string($zone))
			{
				$cart->setZone(\Change\Stdlib\String::isEmpty($zone) ? null : $zone);
				$dataSet[] = ['zone' => $cart->getZone()];
			}
		}
		return $dataSet;
	}
	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $context
	 * @return array
	 */
	protected function updateContext(\Rbs\Commerce\Cart\Cart $cart, array $context)
	{
		$dataSet = [];
		foreach ($context as $key => $value)
		{
			if (is_numeric($key) || in_array($key, ['LCID', 'pricesValueWithTax', 'storageId', 'userName']))
			{
				continue;
			}

			$cart->getContext()->set($key, $value);
			$dataSet[] = ['key' => $key];
		}
		return $dataSet;
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $accountData
	 * @return array
	 */
	protected function setAccount(\Rbs\Commerce\Cart\Cart $cart, array $accountData)
	{
		$dataSet = [];
		if (isset($accountData['ownerId']))
		{
			$cart->setOwnerId(intval($accountData['ownerId']));
			$dataSet[] = ['key' => 'ownerId', 'value' => $cart->getOwnerId()];
		}

		if (array_key_exists('email', $accountData))
		{
			$cart->setEmail(isset($accountData['email']) ? strval($accountData['email']) : null);
			$dataSet[] = ['key' => 'email', 'value' => $cart->getEmail()];
		}

		if (isset($accountData['userId']))
		{
			$userId = intval($accountData['userId']);
			if ($userId)
			{
				$user = $this->documentManager->getDocumentInstance(intval($accountData['userId']), 'Rbs_User_User');
				if ($user instanceof \Rbs\User\Documents\User)
				{
					$cart->setUserId($user->getId());
					$dataSet[] = ['key' => 'userId', 'value' => $cart->getUserId()];
					if (!array_key_exists('email', $accountData))
					{
						$cart->setEmail($user->getEmail());
						$dataSet[] = ['key' => 'email', 'value' => $cart->getEmail()];
					}
				}
			}
			else
			{
				$cart->setUserId(0);
				$dataSet[] = ['key' => 'userId', 'value' => 0];
			}
		}
		return $dataSet;
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $addressData
	 * @param \Rbs\Geo\GeoManager $geoManager
	 * @return array
	 */
	protected function setAddress(\Rbs\Commerce\Cart\Cart $cart, array $addressData, \Rbs\Geo\GeoManager $geoManager)
	{
		$dataSet = [];
		if (!$addressData) {
			$address = null;
		}
		else {
			$address = new \Rbs\Geo\Address\BaseAddress($addressData);
			$address->setLines($geoManager->getFormattedAddress($address));
		}

		$cart->setAddress($address);
		$dataSet[] = ['address' => $address->toArray()] ;
		return $dataSet;
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $shippingModesData
	 * @param \Rbs\Geo\GeoManager $geoManager
	 * @return array
	 */
	protected function setShippingModes(\Rbs\Commerce\Cart\Cart $cart, array $shippingModesData, \Rbs\Geo\GeoManager $geoManager)
	{
		$dataSet = [];
		$shippingModes = [];
		foreach ($shippingModesData as $shippingModeData)
		{
			$mode = new \Rbs\Commerce\Process\BaseShippingMode($shippingModeData);
			$address = $mode->getAddress();
			if ($address)
			{
				$address->setLines($geoManager->getFormattedAddress($address));
			}
			$dataSet[] = ['mode' => $mode->toArray()] ;
			$shippingModes[] = $mode;
		}
		$cart->setShippingModes($shippingModes);
		return $dataSet;
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param array $couponsData
	 * @return array
	 */
	protected function setCoupons(\Rbs\Commerce\Cart\Cart $cart, array $couponsData)
	{
		$dataSet = [];
		$cart->removeAllCoupons();
		foreach ($couponsData as $couponData)
		{
			if (!isset($couponData['code']) || \Change\Stdlib\String::isEmpty($couponData['code']))
			{
				continue;
			}

			$couponCode = $couponData['code'];
			if (!$cart->getCouponByCode($couponCode))
			{
				$coupon = new \Rbs\Commerce\Process\BaseCoupon($couponData);
				$cart->appendCoupon($coupon);
				$dataSet[] = ['code' => $couponCode];
			}
		}
		return $dataSet;
	}
} 