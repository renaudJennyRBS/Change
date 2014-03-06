<?php
namespace ChangeTests\Change\Mail;

use ChangeTests\Change\Mail\TestAssets\FakeMailManager;
use ChangeTests\Change\TestAssets\TestCase;

/**
 * @name \ChangeTests\Change\Mail\MailManagerTest
 */
class MailManagerTest extends TestCase
{

	public function testPrepareMessage()
	{
		$application = $this->getApplicationServices();
		$mailManager = $application->getMailManager();

		$from = array('fromtest@rbschange.fr');
		$to = array('totest@rbschange.fr');
		$cc = array('cctest@rbschange.fr');
		$bcc = array('bcctest@rbschange.fr');
		$replyTo = array('replytotest@rbschange.fr');
		$attachFiles = array();
		$encoding = null;
		$subject = 'Le retour de Chuck Norris';
		$body = 'Chuck Norris est de retour !';
		$txtBody = null;

		$message = $mailManager->prepareMessage($from, $to, $subject, $body, $txtBody, $cc, $bcc, $replyTo, $encoding,
			$attachFiles);

		$this->assertEquals('ASCII', $message->getEncoding());
		$this->assertEquals($subject, $message->getSubject());
		$this->assertEquals($body, $message->getBody());

		$this->assertEquals(count($from), $message->getFrom()->count());
		$this->assertEquals(count($to), $message->getTo()->count());
		$this->assertEquals(count($cc), $message->getCc()->count());
		$this->assertEquals(count($bcc), $message->getBcc()->count());
		$this->assertEquals(count($replyTo), $message->getReplyTo()->count());

		$this->assertTrue($message->getFrom()->has($from[0]));
		$this->assertTrue($message->getTo()->has($to[0]));
		$this->assertTrue($message->getCc()->has($cc[0]));
		$this->assertTrue($message->getBcc()->has($bcc[0]));
		$this->assertTrue($message->getReplyTo()->has($replyTo[0]));

		$from = array(['email' => 'fromtest@rbschange.fr', 'name' => 'fromtest'], ['email' => 'fromtest2@rbschange.fr', 'name' => 'fromtest2']);
		$to = array('totest@rbschange.fr', 'totest2@rbschange.fr');
		$cc = array('cctest@rbschange.fr', 'cctest2@rbschange.fr');
		$bcc = array('bcctest@rbschange.fr', 'bcctest2@rbschange.fr');
		$replyTo = array('replytotest@rbschange.fr', 'replytotest2@rbschange.fr');

		$attachFiles = array(__DIR__ . '/TestAssets/test.zip' => 'application/zip',
			__DIR__ . '/TestAssets/logo.png' => 'image/png');
		$encoding = 'UTF-8';
		$subject = 'Le retour de Chuck Norris 2';
		$body = '<html><body>Chuck Norris est de retour !</body><html>';
		$txtBody = 'Chuck Norris est de retour !';

		$message = $mailManager->prepareMessage($from, $to, $subject, $body, $txtBody, $cc, $bcc, $replyTo, $encoding,
			$attachFiles);
		$this->assertEquals('UTF-8', $message->getEncoding());
		$this->assertEquals($subject, $message->getSubject());
		$this->assertTrue($message->getBody()->isMultiPart());
		$this->assertEquals($body, $message->getBody()->getPartContent(1));
		$this->assertEquals($txtBody, $message->getBody()->getPartContent(0));

		$this->assertEquals(count($from), $message->getFrom()->count());
		$this->assertEquals(count($to), $message->getTo()->count());
		$this->assertEquals(count($cc), $message->getCc()->count());
		$this->assertEquals(count($bcc), $message->getBcc()->count());
		$this->assertEquals(count($replyTo), $message->getReplyTo()->count());

		$this->assertTrue($message->getFrom()->has('fromtest@rbschange.fr'));
		$this->assertEquals('fromtest', $message->getFrom()->get('fromtest@rbschange.fr')->getName());
		$this->assertTrue($message->getFrom()->has('fromtest2@rbschange.fr'));
		$this->assertTrue($message->getTo()->has($to[0]));
		$this->assertTrue($message->getTo()->has($to[1]));
		$this->assertTrue($message->getCc()->has($cc[0]));
		$this->assertTrue($message->getCc()->has($cc[1]));
		$this->assertTrue($message->getBcc()->has($bcc[0]));
		$this->assertTrue($message->getBcc()->has($bcc[1]));
		$this->assertTrue($message->getReplyTo()->has($replyTo[0]));
		$this->assertTrue($message->getReplyTo()->has($replyTo[1]));

		$parts = $message->getBody()->getParts();
		$this->assertEquals('text/plain', $parts[0]->type);
		$this->assertEquals('text/html', $parts[1]->type);
		$this->assertEquals('application/zip', $parts[2]->type);
		$this->assertEquals('image/png', $parts[3]->type);
	}

	public function testPrepareHeader()
	{
		$application = $this->getApplicationServices();
		$mailManager = $application->getMailManager();

		$from = array('fromtest@rbschange.fr');
		$to = array('totest@rbschange.fr');
		$subject = 'Le retour de Chuck Norris';
		$body = 'Chuck Norris est de retour !';

		$message = $mailManager->prepareMessage($from, $to, $subject, $body);

		$headers = array('X-API-Key' => 'RBS Change', 'RBSCHANGE' => 'version 4.0');
		$mailManager->prepareHeader($message, $headers);

		$headers = $message->getHeaders();

		$h = $headers->get('X-API-Key');
		$this->assertEquals('X-API-Key', $h->getFieldName());
		$this->assertEquals('RBS Change', $h->getFieldValue());

		$h = $headers->get('RBSCHANGE');
		$this->assertEquals('RBSCHANGE', $h->getFieldName());
		$this->assertEquals('version 4.0', $h->getFieldValue());
	}

	public function testPrepareFakeMail()
	{
		$application = $this->getApplication();
		$configuration = $application->getConfiguration();

		$mailManager = new FakeMailManager();
		$mailManager->setApplication($application);

		$from = array('fromtest@rbschange.fr');
		$to = array('totest@rbschange.fr');
		$cc = array('cctest@rbschange.fr');
		$bcc = array('bcctest@rbschange.fr');
		$subject = 'Le retour de Chuck Norris';
		$body = 'Chuck Norris est de retour !';

		$message = $mailManager->prepareMessage($from, $to, $subject, $body, null, $cc, $bcc);
		$message = $mailManager->prepareFakeMail($message);

		$this->assertTrue($message->getTo()->has('totest@rbschange.fr'));
		$this->assertEquals($subject, $message->getSubject());
		$this->assertEquals(1, $message->getCc()->count());
		$this->assertEquals(1, $message->getBcc()->count());

		$configuration->addVolatileEntry('Change/Mail/fakemail', 'loic.couturier@rbs.fr');
		$message = $mailManager->prepareFakeMail($message);

		$this->assertFalse($message->getTo()->has('totest@rbschange.fr'));
		$this->assertTrue($message->getTo()->has('loic.couturier@rbs.fr'));
		$this->assertEquals('[FAKE] ' . $subject . ' [To : totest@rbschange.fr][Cc : cctest@rbschange.fr][Bcc : bcctest@rbschange.fr]', $message->getSubject());
		$this->assertEquals(0, $message->getCc()->count());
		$this->assertEquals(0, $message->getBcc()->count());

		$configuration->addVolatileEntry('Change/Mail/fakemail', array('loic.couturier@rbs.fr', 'franck.stauffer@rbs.fr'));
		$to = array('totest@rbschange.fr', 'totest2@rbschange.fr');

		$message = $mailManager->prepareMessage($from, $to, $subject, $body, null, $cc, $bcc);
		$message = $mailManager->prepareFakeMail($message);

		$this->assertTrue($message->getTo()->has('loic.couturier@rbs.fr'));
		$this->assertTrue($message->getTo()->has('franck.stauffer@rbs.fr'));
		$this->assertEquals('[FAKE] ' . $subject . ' [To : totest@rbschange.fr, totest2@rbschange.fr][Cc : cctest@rbschange.fr][Bcc : bcctest@rbschange.fr]', $message->getSubject());
	}
}