<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Constraints;

/**
 * @name \Change\Documents\Constraints\Translator
 */
class Translator extends \Zend\I18n\Translator\Translator implements \Zend\Validator\Translator\TranslatorInterface
{
	
	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;
	
	/**
	 * @return \Change\I18n\I18nManager
	 */
	public function getI18nManager()
	{
		return $this->i18nManager;
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 */
	public function setI18nManager($i18nManager)
	{
		$this->i18nManager = $i18nManager;
	}

	/**
	 * Get a translated message.
	 *
	 * @param  string $message
	 * @param  string $locale
	 * @param  string $textDomain
	 * @param  boolean     $returnPluralRule
	 * @return string|null
	 */
	protected function getTranslatedMessage($message, $locale = null, $textDomain = 'default', $returnPluralRule = false)
	{
		if (strpos($message, ' ') === false)
		{
			if (strpos($message, '.') === false)
			{
				$pk = new \Change\I18n\PreparedKey($textDomain . '.' . $message, array('ucf'));
			}
			else
			{
				$pk = new \Change\I18n\PreparedKey($message, array('ucf'));
			}

			if ($pk->isValid())
			{
				$msg = $this->i18nManager->trans($pk);
				if ($msg !== $pk->getKey())
				{
					return $msg;
				}
			}
		}
		return null;
	}
}