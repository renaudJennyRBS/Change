<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Mail;

use Change\Configuration\Configuration;
use Zend\Mail\Message;
use Zend\Mail\Transport\Sendmail as SendmailTransport;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mail\Transport\TransportInterface as ZendTransportInterface;
use Zend\Mail\Message as ZendMessage;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;

/**
 * @name \Change\Mail\MailManager
 */
class MailManager
{

	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @param \Change\Application $application
	 */
	public function setApplication(\Change\Application $application)
	{
		$this->application = $application;
	}

	/**
	 * @return \Change\Application
	 */
	protected function getApplication()
	{
		return $this->application;
	}

	/**
	 * @return Configuration
	 */
	protected function getConfiguration()
	{
		return $this->getApplication()->getConfiguration();
	}

	/**
	 * @return \Change\Logging\Logging
	 */
	protected function getLogging()
	{
		return $this->getApplication()->getLogging();
	}

	/**
	 * @param array $from ['email@email.com', ['email'=>'email3@email.com'], 'email2@email.com', ['email'=>'email3@email.com', 'name' => 'My name']]
	 * @param array $to ['email@email.com', ['email'=>'email3@email.com'], 'email2@email.com', ['email'=>'email3@email.com', 'name' => 'My name']]
	 * @param string $subject
	 * @param string $body
	 * @param string|null $txtBody
	 * @param array|null $cc ['email@email.com', ['email'=>'email3@email.com'], 'email2@email.com', ['email'=>'email3@email.com', 'name' => 'My name']]
	 * @param array|null $bcc ['email@email.com', ['email'=>'email3@email.com'], 'email2@email.com', ['email'=>'email3@email.com', 'name' => 'My name']]
	 * @param array|null $replyTo ['email@email.com', ['email'=>'email3@email.com'], 'email2@email.com', ['email'=>'email3@email.com', 'name' => 'My name']]
	 * @param string|null $encoding
	 * @param array|null $attachFiles
	 * @return ZendMessage
	 */
	public function prepareMessage($from, $to, $subject, $body, $txtBody = null, $cc = array(), $bcc = array(),
		$replyTo = array(), $encoding = null, $attachFiles = array())
	{
		$message = new Message();

		foreach ($from as $value)
		{
			if (is_array($value))
			{
				if (array_key_exists('email', $value))
				{
					if (array_key_exists('name', $value))
					{
						$message->addFrom($value['email'], $value['name']);
					}
					else
					{
						$message->addFrom($value['email']);
					}
				}
			}
			else
			{
				$message->addFrom($value);
			}
		}

		foreach ($to as $value)
		{
			if (is_array($value))
			{
				if (array_key_exists('email', $value))
				{
					if (array_key_exists('name', $value))
					{
						$message->addTo($value['email'], $value['name']);
					}
					else
					{
						$message->addTo($value['email']);
					}
				}
			}
			else
			{
				$message->addTo($value);
			}
		}

		foreach ($cc as $value)
		{
			if (is_array($value))
			{
				if (array_key_exists('email', $value))
				{
					if (array_key_exists('name', $value))
					{
						$message->addCc($value['email'], $value['name']);
					}
					else
					{
						$message->addCc($value['email']);
					}
				}
			}
			else
			{
				$message->addCc($value);
			}
		}

		foreach ($bcc as $value)
		{
			if (is_array($value))
			{
				if (array_key_exists('email', $value))
				{
					if (array_key_exists('name', $value))
					{
						$message->addBcc($value['email'], $value['name']);
					}
					else
					{
						$message->addBcc($value['email']);
					}
				}
			}
			else
			{
				$message->addBcc($value);
			}
		}

		foreach ($replyTo as $value)
		{
			if (is_array($value))
			{
				if (array_key_exists('email', $value))
				{
					if (array_key_exists('name', $value))
					{
						$message->addReplyTo($value['email'], $value['name']);
					}
					else
					{
						$message->addReplyTo($value['email']);
					}
				}
			}
			else
			{
				$message->addReplyTo($value);
			}
		}

		if ($encoding !== null)
		{
			$message->setEncoding($encoding);
		}

		$message->setSubject($subject);

		if ($txtBody !== null)
		{
			$text = new MimePart($txtBody);
			$text->type = "text/plain";

			$html = new MimePart($body);
			$html->type = "text/html";

			$body = new MimeMessage();
			$body->setParts(array($text, $html));

			$message->setBody($body);
		}
		else
		{
			$message->setBody($body);
		}

		if (is_array($attachFiles) && count($attachFiles) > 0)
		{
			if (!$message->getBody() instanceof MimeMessage)
			{
				$html = new MimePart($message->getBody());
				$html->type = "text/html";

				$body = new MimeMessage();
				$body->setParts(array($html));
			}

			foreach ($attachFiles as $file => $mimeType)
			{
				if (file_exists($file))
				{
					$filePart = new MimePart(fopen($file, 'r'));
					$filePart->type = $mimeType;
					$message->getBody()->addPart($filePart);
				}
				else
				{
					$this->getLogging()->warn(get_class($this) . ' try to send not existing file (' . $file . ')');
				}
			}
		}

		return $message;
	}

	/**
	 * @param ZendMessage $message
	 * @param array $headers
	 * @return ZendMessage
	 */
	public function prepareHeader($message, $headers = array())
	{
		$message->getHeaders()->addHeaders($headers);
	}

	/**
	 * @param ZendMessage $message
	 */
	public function send($message)
	{
		$this->prepareFakeMail($message);

		$transport = $this->getTransport();
		$transport->send($message);
	}

	/**
	 * @return ZendTransportInterface
	 */
	protected function getTransport()
	{
		$type = strtolower($this->getConfiguration()->getEntry('Change/Mail/type'));
		$transport = null;

		switch ($type)
		{

			case 'smtp' :
				$transport = new SmtpTransport();

				$options = array(
					'name' => $this->getConfiguration()->getEntry('Change/Mail/host', 'localhost'),
					'host' => $this->getConfiguration()->getEntry('Change/Mail/host', '127.0.0.1'),
					'port' => $this->getConfiguration()->getEntry('Change/Mail/port', 25)
				);

				if ($this->getConfiguration()->getEntry('Change/Mail/auth') == 'true')
				{
					$options['connection_class'] = $this->getConfiguration()->getEntry('Change/Mail/connection', 'login');
					$options['connection_config'] = array(
						'username' => $this->getConfiguration()->getEntry('Change/Mail/username'),
						'password' => $this->getConfiguration()->getEntry('Change/Mail/password'),
					);
					$ssl = $this->getConfiguration()->getEntry('Change/Mail/ssl');
					if ($ssl !== null)
					{
						$options['connection_config']['ssl'] = $ssl;
					}
				}

				$transport->setOptions(new SmtpOptions($options));

				break;
			case 'sendmail' :
				$transport = new SendmailTransport($this->getConfiguration()->getEntry('Change/Mail/parameters'));
		}

		return $transport;
	}

	/**
	 * @param ZendMessage $message
	 * @return ZendMessage
	 */
	protected function prepareFakeMail($message)
	{
		$fakemail = $this->getConfiguration()->getEntry('Change/Mail/fakemail', null);
		if ($fakemail !== null)
		{
			$currentCc = $message->getCc();
			$currentBcc = $message->getBcc();
			$currentTo = $message->getTo();
			$currentSubject = $message->getSubject();

			$newSubject = '[FAKE] ' . $currentSubject . ' [To : ';
			$i = 0;
			foreach ($currentTo as $to)
			{
				if ($i > 0)
				{
					$newSubject .= ', ';
				}
				$newSubject .= $to->getEmail();
				++$i;
			}
			$newSubject .= ']';

			$i = 0;
			$newCc = '';
			foreach ($currentCc as $cc)
			{
				if ($i > 0)
				{
					$newCc .= ', ';
				}
				$newCc .= $cc->getEmail();
				++$i;
			}
			if ($newCc != '')
			{
				$newSubject .= '[Cc : ' . $newCc .']';
			}

			$i = 0;
			$newBcc = '';
			foreach ($currentBcc as $bcc)
			{
				if ($i > 0)
				{
					$newBcc .= ', ';
				}
				$newBcc .= $bcc->getEmail();
				++$i;
			}
			if ($newBcc != '')
			{
				$newSubject .= '[Bcc : ' . $newBcc .']';
			}

			$message->setSubject($newSubject);
			$message->setTo($fakemail);
			$message->setCc(array());
			$message->setBcc(array());
		}

		return $message;
	}
}