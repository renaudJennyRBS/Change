<?php
namespace Rbs\Simpleform\Security;

/**
 * @name \Rbs\Simpleform\Security\SecurityManager
 */
class SecurityManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait, \Change\Services\DefaultServicesTrait {
		\Change\Events\EventsCapableTrait::attachEvents as defaultAttachEvents;
	}

	const EVENT_MANAGER_IDENTIFIER = 'Rbs_Simpleform_SecurityManager';
	const EVENT_INSTANTIATE_CAPTCHA = 'instantiateCaptcha';
	const EVENT_RENDER_CAPTCHA = 'renderCaptcha';

	/**
	 * @return \Change\Events\SharedEventManager
	 */
	public function getSharedEventManager()
	{
		if ($this->sharedEventManager === null)
		{
			$this->sharedEventManager = $this->getApplication()->getSharedEventManager();
		}
		return $this->sharedEventManager;
	}

	/**
	 * @param \Zend\EventManager\EventManager $eventManager
	 */
	protected function attachEvents(\Zend\EventManager\EventManager $eventManager)
	{
		$this->defaultAttachEvents($eventManager);
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
		if ($this->documentServices)
		{
			$config = $this->getApplication()->getConfiguration('Rbs/Simpleform/Events/SecurityManager');
			return is_array($config) ? $config : array();
		}
		return array();
	}

	// Cross Site Request Forgery prevention.

	/**
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
			$args = $em->prepareArgs(array());
			$event = new \Zend\EventManager\Event(static::EVENT_INSTANTIATE_CAPTCHA, $this, $args);
			$this->getEventManager()->trigger($event);
			$this->captcha = $event->getParam('captcha');
		}
		return $this->captcha;
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function onDefaultInstantiateCaptcha(\Zend\EventManager\Event $event)
	{
		if (!($event->getParam('captcha') instanceof \Zend\Captcha\AdapterInterface))
		{
			/* @var $application \Change\Application */
			$application = $event->getTarget()->getApplicationServices()->getApplication();
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
			$event->getTarget()->getApplicationServices()->getLogging()->fatal(var_export($params, true));
			$event->setParam('captcha', new \Zend\Captcha\Image($params));
		}
	}

	/**
	 * @return string
	 */
	public function getCaptchaId()
	{
		$captcha = $this->getCaptcha();
		if ($this->captchaId === null)
		{
			$this->captchaId = $captcha->generate();
		}
		return $this->captchaId;
	}

	/**
	 * @param array $params
	 * @return string
	 */
	public function renderCaptcha(array $params = array())
	{
		$captcha = $this->getCaptcha();
		if ($this->captchaId === null)
		{
			$this->captchaId = $captcha->generate();
		}
		$em = $this->getEventManager();
		$params['captcha'] = $captcha;
		$args = $em->prepareArgs($params);

		$event = new \Zend\EventManager\Event(static::EVENT_RENDER_CAPTCHA, $this, $args);
		$this->getEventManager()->trigger($event);

		return $event->getParam('htmlResult');
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function onDefaultRenderCaptcha(\Zend\EventManager\Event $event)
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

	/**
	 * @param mixed $value
	 * @return boolean
	 */
	public function validateCaptcha($value)
	{
		return $this->getCaptcha()->isValid($value);
	}
}