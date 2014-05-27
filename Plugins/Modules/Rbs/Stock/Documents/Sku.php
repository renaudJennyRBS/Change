<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Stock\Documents;

use Change\Documents\AbstractModel;
use Change\Documents\Events\Event;

/**
 * @name \Rbs\Stock\Documents\Sku
 */
class Sku extends \Compilation\Rbs\Stock\Documents\Sku
{

	const UNIT_MASS_G = 'g';
	const UNIT_MASS_KG = 'kg';
	const UNIT_MASS_LBS = 'lbs';

	const UNIT_LENGTH_CM = 'cm';
	const UNIT_LENGTH_M = 'm';
	const UNIT_LENGTH_INCH = 'in';

	protected $massConversion = [
		'g' => ['kg' => 0.001, 'lbs' => 0.0022046226218487757],
		'kg' => ['g' => 1000, 'lbs' => 2.2046226218487757],
		'lbs' => ['g' => 453.59237, 'kg' => 0.45359237]
	];

	protected $lengthConversion = [
		'cm' => ['m' => 0.01, 'in' => 0.39370078740157477],
		'm' => ['cm' => 100, 'in' => 39.370078740157477],
		'in' => ['cm' => 2.54, 'm' => 0.0254]
	];

	/**
	 * @param string $toUnit
	 * @return float
	 */
	public function getMass($toUnit = self::UNIT_MASS_KG)
	{
		$fromUnit = $toUnit;
		$value = 0;
		$props = $this->getPhysicalProperties();
		if (isset($props['mass']))
		{
			$def = $props['mass'];
			if (is_array($def) && isset($def['value']) && isset($def['unit']))
			{
				$fromUnit = $def['unit'];
				$value = doubleval($def['value']);
			}
		}
		return $fromUnit == $toUnit ? $value : $this->massConversion[$fromUnit][$toUnit] * $value;
	}

	/**
	 * @param $value
	 * @param string $unit
	 */
	public function setMass($value, $unit = self::UNIT_MASS_KG)
	{
		if (!is_numeric($value))
		{
			throw new \InvalidArgumentException('value has to be numeric', 999999);
		}
		if (!$this->massConversion[$unit])
		{
			throw new \InvalidArgumentException('unknown unit' . $unit, 999999);
		}
		$props = $this->getPhysicalProperties();
		$props['mass'] = array('value' => $value, 'unit' => $unit);
		$this->setPhysicalProperties($props);
		return $this;
	}

	/**
	 * @param string $toUnit
	 * @return float
	 */
	public function getLength($toUnit = self::UNIT_LENGTH_M)
	{
		$fromUnit = $toUnit;
		$value = 0;
		$props = $this->getPhysicalProperties();
		if (isset($props['length']))
		{
			$def = $props['length'];
			if (is_array($def) && isset($def['value']) && isset($def['unit']))
			{
				$fromUnit = $def['unit'];
				$value = doubleval($def['value']);
			}
		}
		return $fromUnit == $toUnit ? $value : $this->lengthConversion[$fromUnit][$toUnit] * $value;
	}

	/**
	 * @param $type
	 * @param $value
	 * @param $unit
	 */
	protected function setLengthValue($type, $value, $unit)
	{
		if (!is_numeric($value))
		{
			throw new \InvalidArgumentException('value has to be numeric', 999999);
		}
		if (!$this->lengthConversion[$unit])
		{
			throw new \InvalidArgumentException('unknown unit ' . $unit, 999999);
		}
		$props = $this->getPhysicalProperties();
		$props[$type] = array('value' => $value, 'unit' => $unit);
		$this->setPhysicalProperties($props);
		return $this;
	}

	/**
	 * @param $value
	 * @param string $unit
	 */
	public function setLength($value, $unit = self::UNIT_LENGTH_M)
	{
		return $this->setLengthValue('length', $value, $unit);
	}

	/**
	 * @param string $toUnit
	 * @return float
	 */
	public function getWidth($toUnit = self::UNIT_LENGTH_M)
	{
		$fromUnit = $toUnit;
		$value = 0;
		$props = $this->getPhysicalProperties();
		if (isset($props['width']))
		{
			$def = $props['width'];
			if (is_array($def) && isset($def['value']) && isset($def['unit']))
			{
				$fromUnit = $def['unit'];
				$value = doubleval($def['value']);
			}
		}
		return $fromUnit == $toUnit ? $value : $this->lengthConversion[$fromUnit][$toUnit] * $value;
	}

	/**
	 * @param $value
	 * @param string $unit
	 */
	public function setWidth($value, $unit = self::UNIT_LENGTH_M)
	{
		return $this->setLengthValue('width', $value, $unit);
	}

	/**
	 * @param string $toUnit
	 * @return float
	 */
	public function getHeight($toUnit = self::UNIT_LENGTH_M)
	{
		$fromUnit = $toUnit;
		$value = 0;
		$props = $this->getPhysicalProperties();
		if (isset($props['height']))
		{
			$def = $props['height'];
			if (is_array($def) && isset($def['value']) && isset($def['unit']))
			{
				$fromUnit = $def['unit'];
				$value = doubleval($def['value']);
			}
		}
		return $fromUnit == $toUnit ? $value : $this->lengthConversion[$fromUnit][$toUnit] * $value;
	}

	/**
	 * @param $value
	 * @param string $unit
	 */
	public function setHeight($value, $unit = self::UNIT_LENGTH_M)
	{
		return $this->setLengthValue('height', $value, $unit);
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getCode();
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		$this->setCode($label);
		return $this;
	}

	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(array(Event::EVENT_CREATE, Event::EVENT_UPDATE), array($this, 'onDefaultSave'), 10);
	}

	public function onDefaultSave(Event $event)
	{
		/** @var $document Sku */
		$document = $event->getDocument();
		if ($document->isNew() || $document->isPropertyModified('code'))
		{
			$cs = $event->getServices('commerceServices');
			if ($cs instanceof \Rbs\Commerce\CommerceServices)
			{
				$sku = $cs->getStockManager()->getSkuByCode($this->getCode());
				if (($sku instanceof \Rbs\Stock\Documents\Sku) && $this->getId() != $sku->getId())
				{
					throw new \RuntimeException('A SKU with the same code already exists', 999999);
				}
			}
			else
			{
				throw new \RuntimeException('Commerce Services not set', 999999);
			}
		}
	}

	/**
	 * Delete link Inventory Entries
	 * @throws \Exception
	 */
	protected function onDelete()
	{
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Stock_InventoryEntry');
		$query->andPredicates($query->eq('sku', $this));
		foreach ($query->getDocuments() as $document)
		{
			/* @var $document \Rbs\Stock\Documents\InventoryEntry */
			$document->delete();
		}
	}

	/**
	 * @return array
	 */
	public function getDefaultThresholds()
	{
		return array(array('l' => 0, 'c' => \Rbs\Stock\StockManager::THRESHOLD_UNAVAILABLE),
			array('l' => \Rbs\Stock\StockManager::UNLIMITED_LEVEL,
				'c' => \Rbs\Stock\StockManager::THRESHOLD_AVAILABLE));
	}

	public function setDefaultValues(AbstractModel $documentModel)
	{
		parent::setDefaultValues($documentModel);
		$this->setThresholds($this->getDefaultThresholds());
	}

	public function onDefaultUpdateRestResult(Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentLink)
		{
			$restResult->setProperty('code', $this->getCode());
		}
	}
}
