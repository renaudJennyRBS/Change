<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn\Presentation;

/**
 * @name \Rbs\Productreturn\Presentation\ProductReturnDataComposer
 */
class ProductReturnDataComposer
{
	use \Change\Http\Ajax\V1\Traits\DataComposer;

	/**
	 * @var \Rbs\Productreturn\Documents\ProductReturn
	 */
	protected $return;

	/**
	 * @var \Rbs\Productreturn\ReturnManager
	 */
	protected $returnManager;

	/**
	 * @var null|array
	 */
	protected $dataSets = null;

	/**
	 * @param \Change\Events\Event $event
	 */
	public function __construct(\Change\Events\Event $event)
	{
		$this->return = $event->getParam('return');

		$context = $event->getParam('context');
		$this->setContext(is_array($context) ? $context : []);
		$this->setServices($event->getApplicationServices());

		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$this->orderManager = $commerceServices->getOrderManager();
		$this->catalogManager = $commerceServices->getCatalogManager();
		$this->priceManager = $commerceServices->getPriceManager();
		$this->processManager = $commerceServices->getProcessManager();
		$this->returnManager = $commerceServices->getReturnManager();
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		if ($this->dataSets === null)
		{
			$this->generateDataSets();
		}
		return $this->dataSets;
	}

	protected function generateDataSets()
	{
		$this->dataSets = [];
		if (!$this->return)
		{
			return;
		}
		/** @var \Rbs\Productreturn\Documents\ProductReturn $return */
		$return = $this->return;

		$this->dataSets['common'] = [
			'id' => $return->getId(),
			'code' => $return->getCode(),
			'date' => $this->formatDate($return->getCreationDate()),
			'userId' => $return->getAuthorId(),
			'ownerId' => $return->getOwnerId(),
			'email' => $return->getEmail(),
			'statusInfos' => $this->returnManager->getReturnStatusInfo($return),
			'cancellable' => $this->returnManager->isReturnCancellable($return)
		];

		$this->dataSets['context'] = $return->getContext()->toArray();

		if ($this->hasDataSet('order'))
		{
			$this->generateOrderFullDataSet();
		}
		else
		{
			$this->generateOrderMinimalDataSet();
		}

		if ($this->detailed || $this->hasDataSet('lines'))
		{
			$this->generateLinesDataSet();
		}

		if ($this->detailed || $this->hasDataSet('returnMode'))
		{
			$this->generateReturnModeDataSet();
		}

		if ($this->detailed || $this->hasDataSet('reshipping'))
		{
			$this->generateReshippingDataSet();
		}
	}

	/**
	 * @return array
	 */
	protected function getOrderContext()
	{
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats, 'dataSetNames' => $this->dataSetNames,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => $this->data, 'detailed' => false];
	}

	protected function generateOrderFullDataSet()
	{
		$order = $this->return->getOrderIdInstance();
		if ($order instanceof \Rbs\Order\Documents\Order)
		{
			$this->dataSets['order'] = $this->orderManager->getOrderData($order, $this->getOrderContext());
		}
	}

	protected function generateOrderMinimalDataSet()
	{
		$order = $this->return->getOrderIdInstance();
		if ($order instanceof \Rbs\Order\Documents\Order)
		{
			$this->dataSets['order'] = [
				'common' => [
					'id' => $order->getId(),
					'code' => $order->getCode(),
					'date' => $this->formatDate($order->getCreationDate())
				]
			];
		}
	}

	/**
	 * @return array
	 */
	protected function getProductLineContext()
	{
		$order = $this->return->getOrderIdInstance();
		$webStoreId = $order ? $this->return->getOrderIdInstance()->getWebStoreId() : null;
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => ['webStoreId' => $webStoreId], 'detailed' => false];
	}

	protected function generateLinesDataSet()
	{
		$this->dataSets['lines'] = [];
		$productContext = $this->getProductLineContext();
		foreach ($this->return->getLines() as $line)
		{
			$lineData = $line->toArray();

			if (isset($lineData['options']['productId']))
			{
				$productData = $this->catalogManager->getProductData($lineData['options']['productId'], $productContext);
				if (count($productData))
				{
					$lineData['product'] = $productData;
				}
			}

			$this->dataSets['lines'][] = $lineData;
		}
	}

	protected function generateReturnModeDataSet()
	{
		$this->dataSets['returnMode'] = [
			'id' => $this->return->getReturnModeId()
		];

		$returnMode = $this->return->getReturnModeIdInstance();
		if ($returnMode instanceof \Rbs\Productreturn\Documents\ReturnMode)
		{
			$this->dataSets['returnMode']['title'] = $returnMode->getCurrentLocalization()->getTitle();
			$this->dataSets['returnMode']['instructions'] = $this->formatRichText($returnMode->getCurrentLocalization()->getInstructions());
			$this->dataSets['returnMode']['stickerURL'] = $this->returnManager->getReturnStickerURL(
				$returnMode,
				$this->return,
				$this->websiteUrlManager
			);
			$this->dataSets['returnMode']['sheetURL'] = $this->returnManager->getReturnSheetURL(
				$returnMode,
				$this->return,
				$this->websiteUrlManager
			);
		}
	}

	protected function generateReshippingDataSet()
	{
		$this->dataSets['reshippingMode'] = [
			'code' => $this->return->getReshippingModeCode()
		];

		// TODO reshipping data.

		$reshippingMode = $this->documentManager->getDocumentInstance($this->return->getContext()->get('reshippingModeId'));
		if ($reshippingMode instanceof \Rbs\Productreturn\Documents\ReturnMode)
		{
			$this->dataSets['reshippingMode']['id'] = $reshippingMode->getId();
			$this->dataSets['reshippingMode']['title'] = $reshippingMode->getCurrentLocalization()->getTitle();
		}
		else
		{
			$this->dataSets['reshippingMode']['id'] = $this->return->getContext()->get('reshippingModeId');
			$this->dataSets['reshippingMode']['title'] = $this->return->getContext()->get('reshippingModeTitle');
		}
	}
}