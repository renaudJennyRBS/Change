<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\I18n;

/**
 * @name \Change\I18n\I18nString
 */
class I18nString extends PreparedKey
{
	/**
	 * @var I18nManager
	 */
	protected $i18nManager;

	/**
	 * @param I18nManager $i18nManager
	 * @param string|PreparedKey $key
	 * @param string[] $formatters
	 * @param array<string => string> $replacements
	 */
	public function __construct(I18nManager $i18nManager, $key, $formatters = array(), $replacements = array())
	{
		if ($key instanceof PreparedKey)
		{
			if (is_array($formatters))
			{
				$key->mergeFormatters($formatters);
			}
			$formatters = $key->getFormatters();

			if (is_array($replacements))
			{
				$key->mergeReplacements($replacements);
			}
			$replacements = $key->getReplacements();
			$key = $key->getKey();
		}
		parent::__construct($key, $formatters, $replacements);
		$this->setI18nManager($i18nManager);
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->i18nManager->trans($this);
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return $this
	 */
	public function setI18nManager(I18nManager $i18nManager)
	{
		$this->i18nManager = $i18nManager;
		return $this;
	}

	/**
	 * @return \Change\I18n\I18nManager
	 */
	public function getI18nManager()
	{
		return $this->i18nManager;
	}
}