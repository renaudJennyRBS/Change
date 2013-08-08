<?php
namespace Rbs\Stock\Documents;

use Rbs\Commerce\Services\CommerceServices;

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
		'kg' => ['g' => 1000, 'lbs' =>  2.2046226218487757],
		'lbs' => ['g' =>  453.59237, 'kg' => 0.45359237]
	];

	protected $lengthConversion = [
		'cm' => ['m' => 0.01, 'in' => 0.39370078740157477],
		'm' => ['cm' => 100, 'in' =>  39.370078740157477],
		'in' => ['cm' =>  2.54, 'm' => 0.0254]
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

	/**
	 * @throws \RuntimeException
	 */
	protected function checkUnicity()
	{
		$cs = new CommerceServices($this->getApplicationServices(), $this->getDocumentServices());
		$sku = $cs->getStockManager()->getSkuByCode($this->getCode());
		if (($sku instanceof \Rbs\Stock\Documents\Sku) && $this->getId() != $sku->getId())
		{
			throw new \RuntimeException('A SKU with the same code already exists', 999999);
		}
	}

	/**
	 * Check unicity
	 */
	public function onUpdate()
	{
		if ($this->isPropertyModified('code'))
		{
			$this->checkUnicity();
		}
	}

	/**
	 * Check unicity
	 */
	public function onCreate()
	{
		$this->checkUnicity();
	}

	/**
	 * Delete link Inventory Entries
	 * @throws \Exception
	 */
	protected function onDelete()
	{
		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_InventoryEntry');
		$query->andPredicates($query->eq('sku', $this));
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			foreach ($query->getDocuments() as $document)
			{
				/* @var $document \Rbs\Stock\Documents\InventoryEntry */
				$document->delete();
			}
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}
}
