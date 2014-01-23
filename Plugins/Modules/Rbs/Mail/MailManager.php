<?php
namespace Rbs\Mail;

/**
 * @name \Rbs\Mail\MailManager
 */
class MailManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'MailManager';

	const VARIABLE_REGEXP = '/\{([a-z][A-Za-z0-9.]*)\}/';

	/**
	 * @var \Change\Job\JobManager
	 */
	protected $jobManager;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param \Change\Job\JobManager $jobManager
	 * @return $this
	 */
	public function setJobManager($jobManager)
	{
		$this->jobManager = $jobManager;
		return $this;
	}

	/**
	 * @return \Change\Job\JobManager
	 */
	protected function getJobManager()
	{
		return $this->jobManager;
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
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Rbs/Mail/Events/MailManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('render', array($this, 'onDefaultRender'), 5);
		$callback = function ($event)
		{
			(new \Rbs\User\Events\InstallMails())->execute($event);
		};
		$eventManager->attach('installMails', $callback, 5);
	}

	/**
	 * @param string $code
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param string $LCID
	 * @param string|array $to
	 * @param array $substitutions
	 * @param \DateTime $at
	 * @throws \RuntimeException
	 */
	public function send($code, $website, $LCID, $to, $substitutions = [], $at = null)
	{
		$mail = $this->getMailByCode($code, $website, $LCID);
		if ($mail === null)
		{
			throw new \RuntimeException('Mail not available for code, website, LCID');
		}

		$emails = $this->convertEmailsParam($to);
		if (count($emails) === 0)
		{
			throw new \RuntimeException('No dest');
		}

		$argument = ['mailId' => $mail->getId(), 'websiteId' => $website->getId(), 'emails' => $emails, 'LCID' => $LCID, 'substitutions' => $substitutions];
		$this->getJobManager()->createNewJob('Rbs_Mail_SendMail', $argument, $at);
	}

	/**
	 * @return string[]
	 */
	public function getCodes()
	{
		$dqb = $this->getDocumentManager()->getNewQuery('Rbs_Mail_Mail');
		$qb = $dqb->dbQueryBuilder();

		$qb->addColumn('code');
		$query = $qb->query();
		return $query->getResults($query->getRowsConverter()->addStrCol('code'));
	}

	/**
	 * @param \Rbs\Mail\Documents\Mail $mail
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param string $LCID
	 * @param array $substitutions
	 * @return string
	 */
	public function render($mail, $website, $LCID, $substitutions)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(array(
			'mail' => $mail,
			'website' => $website,
			'LCID' => $LCID,
			'substitutions' => $substitutions
		));
		$eventManager->trigger('render', $this, $args);
		return isset($args['html']) ? $args['html'] : '';
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultRender($event)
	{
		$mail = $event->getParam('mail');
		$website = $event->getParam('website');
		$LCID = $event->getParam('LCID');
		$substitutions = $event->getParam('substitutions');
		if ($mail instanceof \Rbs\Mail\Documents\Mail && $website instanceof \Change\Presentation\Interfaces\Website && is_string($LCID) && is_array($substitutions))
		{
			$applicationServices = $event->getApplicationServices();
			$application = $event->getApplication();
			$urlManager = $website->getUrlManager($LCID);
			$urlManager->setAbsoluteUrl(true);
			$urlManager->setPathRuleManager($applicationServices->getPathRuleManager());

			$result = new \Change\Http\Web\Result\Page($mail->getCode());

			$mailTemplate = $mail->getTemplate();
			$templateLayout = $mailTemplate->getContentLayout($website->getId());

			$mailLayout = $mail->getContentLayout();
			$containers = array();
			foreach ($templateLayout->getItems() as $item)
			{
				if ($item instanceof \Change\Presentation\Layout\Container)
				{
					$container = $mailLayout->getById($item->getId());
					if ($container)
					{
						$containers[] = $container;
					}
				}
			}
			$mailLayout->setItems($containers);

			$blocks = array_merge($templateLayout->getBlocks(), $mailLayout->getBlocks());

			if (count($blocks))
			{
				$blockManager = $applicationServices->getBlockManager();

				$httpWebEvent = new \Change\Http\Web\Event(null, null, $event->getParams());
				$httpWebEvent->setUrlManager($website->getUrlManager($LCID));

				$blockInputs = array();
				foreach ($blocks as $block)
				{
					/* @var $block \Change\Presentation\Layout\Block */
					$information = $blockManager->getBlockInformation($block->getName());
					if ($information && $information->isMailSuitable())
					{
						$blockParameter = $blockManager->getParameters($block, $httpWebEvent);
						$blockInputs[] = array($block, $blockParameter);
					}
				}

				$blockResults = array();
				foreach ($blockInputs as $infos)
				{
					list($blockLayout, $parameters) = $infos;

					/* @var $blockLayout \Change\Presentation\Layout\Block */
					$blockResult = $blockManager->getResult($blockLayout, $parameters, $httpWebEvent);
					if (isset($blockResult))
					{
						$blockResults[$blockLayout->getId()] = $blockResult;
					}
				}
				$result->setBlockResults($blockResults);
			}

			$cachePath = $application->getWorkspace()->cachePath('twig', 'mail', $mail->getCode() . '.twig');
			$cacheTime = max($mail->getModificationDate()->getTimestamp(), $mailTemplate->getModificationDate()->getTimestamp());

			if (!file_exists($cachePath) || filemtime($cachePath) <> $cacheTime)
			{
				$themeManager = $applicationServices->getThemeManager();
				$twitterBootstrapHtml = new \Change\Presentation\Layout\TwitterBootstrapHtml();
				$callableTwigBlock = function(\Change\Presentation\Layout\Block $item) use ($twitterBootstrapHtml)
				{
					return '{{ pageResult.htmlBlock(\'' . $item->getId() . '\', ' . var_export($twitterBootstrapHtml->getBlockClass($item), true). ')|raw }}';
				};
				$twigLayout = $twitterBootstrapHtml->getHtmlParts($templateLayout, $mailLayout, $callableTwigBlock);
				$twigLayout = array_merge($twigLayout, $twitterBootstrapHtml->getResourceParts($templateLayout, $mailLayout, $themeManager, $applicationServices, $application->inDevelopmentMode()));

				$htmlTemplate = str_replace(array_keys($twigLayout), array_values($twigLayout), $mailTemplate->getHtml());

				\Change\Stdlib\File::write($cachePath, $htmlTemplate);
				touch($cachePath, $cacheTime);
			}

			$templateManager = $applicationServices->getTemplateManager();
			$html = $templateManager->renderTemplateFile($cachePath, array('pageResult' => $result));
			$event->setParam('html', $this->getSubstitutedString($html, $substitutions));
		}
	}

	/**
	 * @param string $code
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param string $LCID
	 * @return \Rbs\Mail\Documents\Mail|null
	 */
	protected function getMailByCode($code, $website, $LCID)
	{
		$dqb = $this->getDocumentManager()->getNewQuery('Rbs_Mail_Mail', $LCID);
		$pb = $dqb->getPredicateBuilder();
		$dqb->andPredicates($pb->eq('code', $code), $pb->eq('LCID', $LCID),
			$pb->logicOr($pb->eq('websites', $website), $pb->isNull('websites'))
		);
		$dqb->addOrder('websites', false);
		return $dqb->getFirstDocument();
	}

	/**
	 * @param array $to
	 * @return array
	 */
	protected function convertEmailsParam($to)
	{
		$emails = ['to' => [], 'cc' => [], 'bcc' => [], 'reply-to' => []];
		if ($this->isValidEmailFormat($to))
		{
			$emails['to'] = [$to];
		}
		elseif (is_array($to) && array_key_exists('to', $to))
		{
			$result = $this->convertEmailsElement($to['to']);
			$emails['to'] = $result;
			if (array_key_exists('cc', $to))
			{
				$result = $this->convertEmailsElement($to['cc']);
				$emails['cc'] = $result;
			}
			if (array_key_exists('bcc', $to))
			{
				$result = $this->convertEmailsElement($to['bcc']);
				$emails['bcc'] = $result;
			}
			if (array_key_exists('reply-to', $to))
			{
				$result = $this->convertEmailsElement($to['reply-to']);
				$emails['reply-to'] = $result;
			}
		}
		elseif (is_array($to))
		{
			$emails['to'] = [];
			foreach ($to as $email)
			{
				if ($this->isValidEmailFormat($email))
				{
					$emails['to'][] = $email;
				}
			}
		}
		return $emails;
	}

	/**
	 * @param array|string $email
	 * @return boolean
	 */
	public function isValidEmailFormat($email)
	{
		//an email can be a string,
		//or an hash table with email and name like ['email' => 'mario.bros@nintendo.com', 'name' => 'Mario Bros']
		if (is_string($email))
		{
			return true;
		}
		elseif (is_array($email))
		{
			if (array_key_exists('email', $email) && is_string($email['email']) &&
				array_key_exists('name', $email) && is_string($email['name']))
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @param array $element
	 * @return array
	 */
	protected function convertEmailsElement($element)
	{
		$emails = [];
		if ($this->isValidEmailFormat($element))
		{
			$emails = $element;
		}
		elseif (is_array($element))
		{
			foreach ($element as $email)
			{
				if ($this->isValidEmailFormat($email))
				{
					$emails[] = $email;
				}
			}
		}
		return $emails;
	}

	public function getSubstitutedString($string, $substitutions)
	{
		if ($string)
		{
			if (count($substitutions))
			{
				$string = preg_replace_callback(static::VARIABLE_REGEXP, function ($matches) use ($substitutions)
				{
					if (array_key_exists($matches[1], $substitutions))
					{
						return $substitutions[$matches[1]];
					}
					return '';
				}, $string);
			}
		}
		return ($string) ? $string : null;
	}
}