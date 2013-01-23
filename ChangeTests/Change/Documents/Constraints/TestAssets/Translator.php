<?php
namespace ChangeTests\Documents\Constraints\TestAssets;

/**
 * @name \ChangeTests\Documents\Constraints\TestAssets\Translator
 */
class Translator extends \Change\Documents\Constraints\Translator
{	
	/**
	 * Get a translated message.
	 * @param  string $message
	 * @param  string $locale
	 * @param  string $textDomain
	 * @return string|null
	 */
	protected function getTranslatedMessage($message, $locale = null, $textDomain = 'default')
	{
		if (strpos($message, ' ') === false)
		{
			$pk = new \Change\I18n\PreparedKey($textDomain . '.' . $message, array('ucf'));
			$msg = ($pk->isValid()) ? $pk->getKey() : null;
			return $msg;
		}
		return null;
	}
	
}
