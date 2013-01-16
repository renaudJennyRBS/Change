<?php
namespace Change\Documents\Constraints;

/**
 * @name \Change\Documents\Constraints\Translator
 */
class Translator extends \Zend\I18n\Translator\Translator
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
	 * @return string|null
	 */
	protected function getTranslatedMessage($message, $locale = null, $textDomain = 'default')
	{
		if (strpos($message, ' ') === false)
		{
			$pk = new \Change\I18n\PreparedKey($textDomain . '.' . $message, array('ucf'));
			$msg = ($pk->isValid()) ? $this->i18nManager->trans($pk) : null;
			return $msg;
		}
		return null;
	}
}