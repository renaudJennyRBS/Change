<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Presentation;

use Rbs\Commerce\CommerceServices;

/**
 * @name \Rbs\Commerce\Presentation\TwigExtension
 */
class TwigExtension  implements \Twig_ExtensionInterface
{
	/**
	 * @var \Rbs\Commerce\CommerceServices
	 */
	protected $commerceServices;

	/**
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 */
	function __construct(\Rbs\Commerce\CommerceServices $commerceServices)
	{
		$this->commerceServices = $commerceServices;
	}

	/**
	 * Returns the name of the extension.
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'Rbs_Commerce';
	}

	/**
	 * Initializes the runtime environment.
	 * This is where you can load some file that contains filter functions for instance.
	 * @param \Twig_Environment $environment The current Twig_Environment instance
	 */
	public function initRuntime(\Twig_Environment $environment)
	{

	}

	/**
	 * Returns the token parser instances to add to the existing list.
	 * @return array An array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances
	 */
	public function getTokenParsers()
	{
		return array();
	}

	/**
	 * Returns the node visitor instances to add to the existing list.
	 * @return array An array of Twig_NodeVisitorInterface instances
	 */
	public function getNodeVisitors()
	{
		return array();
	}

	/**
	 * Returns a list of filters to add to the existing list.
	 * @return array An array of filters
	 */
	public function getFilters()
	{
		return array();
	}

	/**
	 * Returns a list of tests to add to the existing list.
	 * @return array An array of tests
	 */
	public function getTests()
	{
		return array();
	}

	/**
	 * Returns a list of functions to add to the existing list.
	 * @return array An array of functions
	 */
	public function getFunctions()
	{
		return [
			new \Twig_SimpleFunction('formatPrice', [$this, 'formatPrice']),
			new \Twig_SimpleFunction('formatRate', [$this, 'formatRate']),
			new \Twig_SimpleFunction('taxTitle', [$this, 'taxTitle']),
			new \Twig_SimpleFunction('getAttributesByVisibility', [$this, 'getAttributesByVisibility']),
			new \Twig_SimpleFunction('getAttributeByName', [$this, 'getAttributeByName'])
		];
	}

	/**
	 * Returns a list of operators to add to the existing list.
	 * @return array An array of operators
	 */
	public function getOperators()
	{
		return array();
	}

	/**
	 * Returns a list of global variables to add to the existing list.
	 * @return array An array of global variables
	 */
	public function getGlobals()
	{
		return array();
	}

	/**
	 * @return CommerceServices
	 */
	protected function getCommerceServices()
	{
		return $this->commerceServices;
	}

	/**
	 * @param float $value
	 * @param string|null $currencyCode
	 * @return string
	 */
	public function formatPrice($value, $currencyCode = null)
	{
		if ($value === null || !is_numeric($value))
		{
			return '';
		}
		return $this->getCommerceServices()->getPriceManager()->formatValue($value, $currencyCode);
	}

	/**
	 * @param float $rate
	 * @return string
	 */
	public function formatRate($rate)
	{
		if ($rate === null || !is_numeric($rate))
		{
			return '';
		}
		return $this->getCommerceServices()->getPriceManager()->formatRate($rate);
	}

	/**
	 * @param string|\Rbs\Price\Tax\TaxInterface $tax
	 * @return string
	 */
	public function taxTitle($tax)
	{
		return $this->getCommerceServices()->getPriceManager()->taxTitle($tax);
	}


	/**
	 * @param array $productData
	 * @param string $technicalName
	 * @return array|null
	 */
	public function getAttributeByName($productData, $technicalName)
	{
		if (!is_array($productData))
		{
			return null;
		}

		if (isset($productData['common']['attributes'][$technicalName])) {
			$key = $productData['common']['attributes'][$technicalName];
			if (isset($productData['attributes'][$key]['value']))
			{
				return $productData['attributes'][$key];
			}
		}

		if (isset($productData['rootProduct']['common']))
		{
			return $this->getAttributeByName($productData['rootProduct'], $technicalName);
		}
		return null;
	}

	/**
	 * @param array $productData
	 * @param string $visibility
	 * @return array
	 */
	public function getAttributesByVisibility($productData, $visibility = 'specifications')
	{
		$sections = [];
		if (is_array($productData) && isset($productData['rootProduct']['attributesVisibility']))
		{
			$sections = $this->getAttributesByVisibility($productData['rootProduct'], $visibility);
		}

		if (is_array($productData) && isset($productData['attributesVisibility'][$visibility]))
		{
			$keys = $productData['attributesVisibility'][$visibility];
			if (is_array($keys) && count($keys)) {
				$technicalNames = array_flip($productData['common']['attributes']);
				if (!is_array($technicalNames))
				{
					$technicalNames = [];
				}
				foreach ($keys as $key)
				{
					if (isset($productData['attributes'][$key]['value']))
					{
						$attr = $productData['attributes'][$key];
						$section = $attr['section'] ? $attr['section'] : '';

						if (isset($technicalNames[$key]))
						{
							$attr['technicalName'] = $technicalNames[$key];
						}
						$sections[$section][$key] = $attr;
					}
				}
			}
		}

		return $sections;
	}
}