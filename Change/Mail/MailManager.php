<?php
namespace Change\Mail;

use Change\Configuration\Configuration;
use Change\Logging\Logging;
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
	 * @var Configuration
	 */
	protected $configuration;

	/**
	 * @var Logging
	 */
	protected $logging;

	/**
	 * @param Configuration $configuration
	 */
	public function setConfiguration(Configuration $configuration)
	{
		$this->configuration = $configuration;
	}

	/**
	 * @return Configuration
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}

	/**
	 * @param \Change\Logging\Logging $logging
	 */
	public function setLogging($logging)
	{
		$this->logging = $logging;
	}

	/**
	 * @return \Change\Logging\Logging
	 */
	public function getLogging()
	{
		return $this->logging;
	}

	/**
	 * @param array $from
	 * @param array $to
	 * @param string $subject
	 * @param string $body
	 * @param string|null $txtBody
	 * @param array|null $cc
	 * @param array|null $bcc
	 * @param string|null $encoding
	 * @param array|null $attachFiles
	 * @return ZendMessage
	 */
	public function prepareMessage($from, $to, $subject, $body, $txtBody = null, $cc = array(), $bcc = array(),
		$replyTo = array(), $encoding = null, $attachFiles = array())
	{
		$message = new Message();

		foreach ($from as $email => $name)
		{
			if (is_integer($email))
			{
				$message->addFrom($name);
			}
			else
			{
				$message->addFrom($email, $name);
			}
		}

		foreach ($to as $email => $name)
		{
			if (is_integer($email))
			{
				$message->addTo($name);
			}
			else
			{
				$message->addTo($email, $name);
			}
		}

		foreach ($cc as $email => $name)
		{
			if (is_integer($email))
			{
				$message->addCc($name);
			}
			else
			{
				$message->addCc($email, $name);
			}
		}

		foreach ($bcc as $email => $name)
		{
			if (is_integer($email))
			{
				$message->addBcc($name);
			}
			else
			{
				$message->addBcc($email, $name);
			}
		}

		foreach ($replyTo as $email => $name)
		{
			if (is_integer($email))
			{
				$message->addReplyTo($name);
			}
			else
			{
				$message->addReplyTo($email, $name);
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