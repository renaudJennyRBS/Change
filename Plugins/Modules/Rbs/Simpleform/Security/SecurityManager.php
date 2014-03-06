<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Simpleform\Security;

/**
 * @name \Rbs\Simpleform\Security\SecurityManager
 */
class SecurityManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'Rbs_Simpleform_SecurityManager';
	const EVENT_INSTANTIATE_CAPTCHA = 'instantiateCaptcha';
	const EVENT_RENDER_CAPTCHA = 'renderCaptcha';

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_INSTANTIATE_CAPTCHA, array($this, 'onDefaultInstantiateCaptcha'), 5);
		$eventManager->attach(static::EVENT_RENDER_CAPTCHA, array($this, 'onDefaultRenderCaptcha'), 5);
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Simpleform/Events/SecurityManager');
	}

	// Cross Site Request Forgery prevention.

	/**
	 * @api
	 * @see http://blog.ircmaxell.com/2013/02/preventing-csrf-attacks.html
	 * @return string
	 */
	public function getCSRFToken()
	{
		$session = new \Zend\Session\Container('Change_Rbs_Simpleform');
		$token = \Zend\Math\Rand::getString(64);
		if (!isset($session['CSRFTokens']) || !is_array($session['CSRFTokens']))
		{
			$session['CSRFTokens'] = array();
		}
		$session['CSRFTokens'][$token] = true;
		return $token;
	}

	/**
	 * @api
	 * @see http://blog.ircmaxell.com/2013/02/preventing-csrf-attacks.html
	 * @param string $token
	 * @return boolean
	 */
	public function checkCSRFToken($token)
	{
		if (empty($token))
		{
			return false;
		}

		$session = new \Zend\Session\Container('Change_Rbs_Simpleform');
		if (isset($session['CSRFTokens'][$token]))
		{
			unset($session['CSRFTokens'][$token]);
			return true;
		}
		return false;
	}

	// CAPTCHA.

	/**
	 * @var \Zend\Captcha\AdapterInterface
	 */
	protected $captcha;

	/**
	 * @var string
	 */
	protected $captchaId;

	/**
	 * @param \Zend\Captcha\AdapterInterface $captcha
	 * @return $this
	 */
	public function setCaptcha($captcha)
	{
		$this->captcha = $captcha;
		return $this;
	}

	/**
	 * @return \Zend\Captcha\AdapterInterface
	 */
	protected function getCaptcha()
	{
		if ($this->captcha === null)
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(array('captcha' => false));
			$em->trigger(static::EVENT_INSTANTIATE_CAPTCHA, $this, $args);
			$this->captcha = $args['captcha'];
		}
		return $this->captcha;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getCaptchaId()
	{
		$captcha = $this->getCaptcha();
		if ($captcha && $this->captchaId === null)
		{
			$this->captchaId = $captcha->generate();
		}
		return $this->captchaId;
	}

	/**
	 * @api
	 * @param array $params
	 * @return string|null
	 */
	public function renderCaptcha(array $params = array())
	{
		$captcha = $this->getCaptcha();
		if (!$captcha)
		{
			return null;
		}

		if ($this->captchaId === null)
		{
			$this->captchaId = $captcha->generate();
		}

		$em = $this->getEventManager();
		$params['captcha'] = $captcha;
		$params['htmlResult'] = null;
		$args = $em->prepareArgs($params);
		$em->trigger(static::EVENT_RENDER_CAPTCHA, $this, $args);
		return $args['htmlResult'];
	}

	/**
	 * @api
	 * @param mixed $value
	 * @return boolean
	 */
	public function validateCaptcha($value)
	{
		$captcha = $this->getCaptcha();
		return !$captcha || $captcha->isValid($value);
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultInstantiateCaptcha(\Change\Events\Event $event)
	{
		if (!($event->getParam('captcha') instanceof \Zend\Captcha\AdapterInterface))
		{
			/* @var $application \Change\Application */
			$application = $event->getApplication();
			$dirRoot = $application->getConfiguration()->getEntry('Change/Install/webBaseDirectory');
			$urlRoot = $application->getConfiguration()->getEntry('Change/Install/webBaseURLPath');
			$params = array(
				'font' => $application->getWorkspace()->pluginsModulesPath('Rbs', 'Simpleform', 'Assets', 'Font', 'Gravity-Book.ttf'),
				'fontSize' => 20,
				'width' => 150,
				'height' => 70,
				'wordLen' => 5,
				'dotNoiseLevel' => 50,
				'lineNoiseLevel' => 3,
				'imgDir' => $application->getWorkspace()->projectPath($dirRoot, 'Tmp', 'Captcha'),
				'imgUrl' => $urlRoot . '/Tmp/Captcha'
			);
			$event->setParam('captcha', new \Zend\Captcha\Image($params));
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultRenderCaptcha(\Change\Events\Event $event)
	{
		$captcha = $event->getParam('captcha');
		if (!$event->getParam('htmlResult') && $captcha instanceof \Zend\Captcha\Image)
		{
			$html = '<img width="' . $captcha->getWidth() . '" height="' . $captcha->getHeight() . '"
				alt="' . $captcha->getImgAlt() . '"
				src="' . $captcha->getImgUrl() . $captcha->getId() . $captcha->getSuffix() . '" />';
			$event->setParam('htmlResult', $html);
		}
	}
}