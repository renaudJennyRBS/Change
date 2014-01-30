<?php

namespace ChangeTests\Rbs\Mail;

class MailManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	public function setUp()
	{
		//set the cache folder for mail
		$mailCacheFolder = $this->getApplication()->getWorkspace()->cachePath('mail');
		if (!is_dir($mailCacheFolder))
		{
			\Change\Stdlib\File::mkdir($mailCacheFolder);
			$this->assertTrue(is_dir($mailCacheFolder), 'Mail cache folder has not been created');
		}
	}

	public function tearDown()
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();

		//empty mails
		$dqb = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Mail_Mail');
		$mails = $dqb->getDocuments();

		/** @var $mail \Rbs\Mail\Documents\Mail */
		foreach ($mails as $mail)
		{
			$mail->delete();
		}

		//empty jobs
		$jobManager = $this->getApplicationServices()->getJobManager();
		$jobIds = $jobManager->getJobIds();
		foreach ($jobIds as $jobId)
		{
			$job = $jobManager->getJob($jobId);
			$jobManager->deleteJob($job);
		}

		//remove the cache folder for mail
		$mailCacheFolder = $this->getApplication()->getWorkspace()->cachePath('mail');
		if (is_dir($mailCacheFolder))
		{
			\Change\Stdlib\File::rmdir($mailCacheFolder);
			$this->assertFalse(is_dir($mailCacheFolder), 'Mail cache folder isn\'t removed');
		}

		$tm->commit();
	}

	public function testGetMailByCode()
	{
		$method = new \ReflectionMethod(
			'\Rbs\Mail\MailManager', 'getMailByCode'
		);

		$method->setAccessible(true);
		$genericServices = new \Rbs\Generic\GenericServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$mailManager = $genericServices->getMailManager();

		$website = $this->getNewWebsite();

		$this->assertNull($method->invoke($mailManager, "test", $website, 'fr_FR'));

		$this->getNewMail('test1', [], 'fr_FR', 'a subject');

		//if the mail has no websites defined, it been considered as the default mail for this code & LCID.
		$this->assertNotNull($method->invoke($mailManager, "test1", $website, 'fr_FR'));

		$this->getNewMail('test2', [$website], 'fr_FR', 'C\'est un email en français');

		/* @var $mail \Rbs\Mail\Documents\Mail */
		$mail = $method->invoke($mailManager, "test2", $website, 'fr_FR');
		$this->assertNotNull($mail);
		$this->getApplicationServices()->getDocumentManager()->pushLCID('fr_FR');
		$this->assertEquals('C\'est un email en français', $mail->getCurrentLocalization()->getSubject());
		$this->getApplicationServices()->getDocumentManager()->popLCID();

		//english mail is not available
		$mail = $method->invoke($mailManager, "test2", $website, 'en_US');
		$this->assertNull($mail);

		$this->getNewMail('test3', [$website], 'en_US', 'It\'s an english mail');
		$mail = $method->invoke($mailManager, "test3", $website, 'en_US');
		$this->assertNotNull($mail);
		$this->getApplicationServices()->getDocumentManager()->pushLCID('en_US');
		$this->assertEquals('It\'s an english mail', $mail->getCurrentLocalization()->getSubject());
		$this->getApplicationServices()->getDocumentManager()->popLCID();

		$website2 = $this->getNewWebsite();
		$this->getNewMail('test4', [$website2], 'fr_FR', 'website2 mail');

		$mail = $method->invoke($mailManager, "test4", $website2, 'fr_FR');
		$this->assertNotNull($mail);
		$this->getApplicationServices()->getDocumentManager()->pushLCID('fr_FR');
		$this->assertEquals('website2 mail', $mail->getCurrentLocalization()->getSubject());
		$this->getApplicationServices()->getDocumentManager()->popLCID();

		//test on the first website return nothing
		$mail = $method->invoke($mailManager, "test4", $website, 'fr_FR');
		$this->assertNull($mail);
	}

	public function testSend()
	{
		$genericServices = new \Rbs\Generic\GenericServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$mailManager = $genericServices->getMailManager();
		$this->assertInstanceOf('\Rbs\Mail\MailManager', $mailManager);

		//send method of MailManager will create a job, first we check if there is no job in base
		$jobManager = $this->getApplicationServices()->getJobManager();
		$jobIds = $jobManager->getJobIds();
		$this->assertCount(0, $jobIds);
		$LCID = $this->getApplicationServices()->getI18nManager()->getLCID();

		$website = $this->getNewWebsite();
		$mail = $this->getNewMail('test', [$website], 'fr_FR', 'New mail for test');

		$emails = ['mario.bros@nintendo.fr', 'luigi.bros@nintendo.fr'];
		$at = (new \DateTime())->add((new \DateInterval('PT10M')));
		$substitutions = ['aSubstitution' => 'the substitution'];
		$mailManager->send('test', $website, $LCID, $emails, $substitutions, $at);
		$jobIds = $jobManager->getJobIds();
		$this->assertCount(1, $jobIds);
		$job = $jobManager->getJob($jobIds[0]);
		$this->assertEquals('Rbs_Mail_SendMail', $job->getName());
		$expectedEmails = ['to' => $emails, 'cc' => [], 'bcc' => [], 'reply-to' => []];
		$expectedArguments = ['mailId' => $mail->getId(), 'emails' => $expectedEmails, 'LCID' => $LCID, 'substitutions' => $substitutions, 'websiteId' => $website->getId()];
		$this->assertEquals($expectedArguments, $job->getArguments());
		$this->assertEquals($at, $job->getStartDate());
	}

	public function testGetCodes()
	{
		$genericServices = new \Rbs\Generic\GenericServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$mailManager = $genericServices->getMailManager();
		$this->assertInstanceOf('\Rbs\Mail\MailManager', $mailManager);

		$codes = $mailManager->getCodes();
		$this->assertCount(0, $codes);

		$this->getNewMail('test1', [], 'fr_FR', 'test 1');
		$codes = $mailManager->getCodes();
		$this->assertCount(1, $codes);
		$this->assertTrue(in_array('test1', $codes), 'test1 code is not in code array');

		$this->getNewMail('test2', [], 'fr_FR', 'test 1');
		$codes = $mailManager->getCodes();
		$this->assertCount(2, $codes);
		$this->assertTrue(in_array('test1', $codes), 'test1 code is not in code array');
		$this->assertTrue(in_array('test2', $codes), 'test2 code is not in code array');
	}

	public function testIsValidEmailFormat()
	{
		$genericServices = new \Rbs\Generic\GenericServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$mailManager = $genericServices->getMailManager();
		$this->assertInstanceOf('\Rbs\Mail\MailManager', $mailManager);

		$goodEmail = 'mario.bros@nintendo.com';
		$this->assertTrue($mailManager->isValidEmailFormat($goodEmail), var_export($goodEmail, true) . ' return a wrong email format');
		$goodEmail = ['email' => 'mario.bros@nintendo.com', 'name' => 'Mario Bros'];
		$this->assertTrue($mailManager->isValidEmailFormat($goodEmail), var_export($goodEmail, true) . ' return a wrong email format');

		$wrongEmail = ['mario.bros@nintendo'];
		$this->assertFalse($mailManager->isValidEmailFormat($wrongEmail), var_export($wrongEmail, true) . ' return a good email format');
		$wrongEmail = ['mario.bros@nintendo' => ['Mario', 'Bros']];
		$this->assertFalse($mailManager->isValidEmailFormat($wrongEmail), var_export($wrongEmail, true) . ' return a good email format');
	}

	public function testConvertEmailsParam()
	{
		$method = new \ReflectionMethod(
			'\Rbs\Mail\MailManager', 'convertEmailsParam'
		);
		$method->setAccessible(true);

		$genericServices = new \Rbs\Generic\GenericServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$mailManager = $genericServices->getMailManager();
		$this->assertInstanceOf('\Rbs\Mail\MailManager', $mailManager);

		//test with simple emails
		$verySimpleEmail = ['mario.bros@nintendo.com'];
		$namedEmail = ['email' => 'luigi.bros@nintendo.com', 'name' => 'Luigi Bros'];
		$emailArray = ['mario.bros@nintendo.com', 'donkey.kong@nintendo.com', 'peach.toadstool@nintendo.com'];
		$moreComplexEmailArray = ['mario.bros@nintendo.com', $namedEmail, 'peach.toadstool@nintendo.com'];

		$result = $method->invoke($mailManager, $verySimpleEmail);
		$this->assertArrayHasKey('to', $result);
		$this->assertCount(1, $result['to']);
		$this->assertEquals($verySimpleEmail[0], $result['to'][0]);

		$result = $method->invoke($mailManager, $namedEmail);
		$this->assertArrayHasKey('to', $result);
		$this->assertCount(1, $result['to']);
		$this->assertEquals($namedEmail, $result['to'][0]);

		$result = $method->invoke($mailManager, $emailArray);
		$this->assertArrayHasKey('to', $result);
		$this->assertCount(3, $result['to']);
		$this->assertEquals($emailArray, $result['to']);

		$result = $method->invoke($mailManager, $moreComplexEmailArray);
		$this->assertArrayHasKey('to', $result);
		$this->assertCount(3, $result['to']);
		$this->assertEquals($moreComplexEmailArray, $result['to']);

		//test with hash table of 'to', 'cc', 'bcc', 'reply-to'...
		$emailTable = ['to' => $verySimpleEmail, 'cc' => $namedEmail, 'bcc' => $moreComplexEmailArray, 'reply-to' => 'admin@test.com'];
		$result = $method->invoke($mailManager, $emailTable);
		$to = $result['to'];
		$this->assertEquals($verySimpleEmail, $to);
		$this->assertArrayHasKey('cc', $result);
		$cc = $result['cc'];
		$this->assertEquals($namedEmail, $cc);
		$this->assertArrayHasKey('bcc', $result);
		$bcc = $result['bcc'];
		$this->assertEquals($moreComplexEmailArray, $bcc);
		$this->assertArrayHasKey('reply-to', $result);
		$replyTo = $result['reply-to'];
		$this->assertEquals('admin@test.com', $replyTo);
	}

	public function testRender()
	{
		$this->markTestSkipped('Method MailManager::render() cannot be tested yet');
		$genericServices = new \Rbs\Generic\GenericServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$this->getEventManagerFactory()->addSharedService('genericServices', $genericServices);
		$mailManager = $genericServices->getMailManager();
		$this->assertInstanceOf('\Rbs\Mail\MailManager', $mailManager);

		//attach generic blockManager listeners to display a rich text block
		$blockManager = $this->getApplicationServices()->getBlockManager();
		$blockManager->getEventManager()->attachAggregate(new \Rbs\Generic\Events\BlockManager\Listeners());
		//attach website richText
		$richTextManager = $this->getApplicationServices()->getRichTextManager();
		$richTextManager->getEventManager()->attachAggregate(new \Rbs\Generic\Events\RichTextManager\Listeners());

		$website = $this->getNewWebsite();
		$LCID = 'fr_FR';
		$mail = $this->getNewMail('test', [$website], $LCID, 'I test render method');

		$this->getApplicationServices()->getDocumentManager()->pushLCID($LCID);
		$mail->setWebsites([$website]);
		$mail->setSubstitutions(['aSubstitution']);
		$mail->getCurrentLocalization()->setSenderMail('admin@admin.com');
		$mail->getCurrentLocalization()->setSenderName('Admin of tests');
		$mail->getCurrentLocalization()->setEditableContent($this->getContentSample());
		$mail->setTemplate($this->getNewTemplate());
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$mail->save();
			$tm->commit();
			$this->getApplicationServices()->getDocumentManager()->popLCID();
		}
		catch (\Exception $e)
		{
			$this->getApplicationServices()->getDocumentManager();
			throw $tm->rollBack($e);
		}

		$substitutions = ['aSubstitution' => 'the substitution'];
		$html = $mailManager->render($mail, $website, $LCID, $substitutions);
		$htmlDomDocument = new \DOMDocument(1, 'utf-8');
		$htmlDomDocument->loadHTML($html);
		$expectedDomDocument = new \DOMDocument(1, 'utf-8');
		$expectedDomDocument->loadHTML($this->getHtmlSample());
		$this->assertXmlStringEqualsXmlString($expectedDomDocument->saveHTML(), $htmlDomDocument->saveHTML());
	}

	public function testGetSubstitutedString()
	{
		$genericServices = new \Rbs\Generic\GenericServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$this->getEventManagerFactory()->addSharedService('genericServices', $genericServices);
		$mailManager = $genericServices->getMailManager();
		$this->assertInstanceOf('\Rbs\Mail\MailManager', $mailManager);

		$stringToSubstitute = 'Hello, I have a {color} {object}';
		$substitutions = ['color' => 'black', 'object' => 'cube'];

		$substitutedString = $mailManager->getSubstitutedString($stringToSubstitute, $substitutions);
		$this->assertEquals('Hello, I have a black cube', $substitutedString);
	}

	/**
	 * @param string $code
	 * @param \Rbs\Website\Documents\Website[] $websites
	 * @param string $LCID
	 * @param string $subject
	 * @return \Rbs\Mail\Documents\Mail
	 * @throws \Exception
	 */
	protected function getNewMail($code, $websites, $LCID, $subject)
	{
		$this->getApplicationServices()->getDocumentManager()->pushLCID($LCID);
		$mail = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Mail_Mail');
		/* @var $mail \Rbs\Mail\Documents\Mail */
		$mail->setLabel('new mail');
		$mail->setCode($code);
		if (count($websites))
		{
			$mail->setWebsites($websites);
		}
		$mail->getCurrentLocalization()->setSubject($subject);

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$mail->save();
			$tm->commit();
			$this->getApplicationServices()->getDocumentManager()->popLCID();
		}
		catch (\Exception $e)
		{
			$this->getApplicationServices()->getDocumentManager()->popLCID();
			throw $tm->rollBack($e);
		}
		return $mail;
	}

	/**
	 * @return \Rbs\Website\Documents\Website
	 * @throws \Exception
	 */
	protected function getNewWebsite()
	{
		$website = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Website_Website');
		/* @var $website \Rbs\Website\Documents\Website */
		$website->setLabel('Website Test');
		$website->getCurrentLocalization()->setTitle('Website Test');

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$website->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $website;
	}

	/**
	 * @return \Rbs\Theme\Documents\Template
	 * @throws \Exception
	 */
	protected function getNewTemplate()
	{
		$template = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Theme_Template');
		/* @var $template \Rbs\Theme\Documents\Template */
		$template->setLabel('Test Mail template');
		$template->setCode('test_template');
		$html = '<!doctype html>
				<html>
					<body>
						<div id="content">
							<!-- mainContent -->
						</div>
					</body>
				</html>';
		$template->setHtml($html);
		$template->setEditableContent(['mainContent' => ['id' => 'mainContent', 'grid' => 9, 'type' => 'container', 'parameters' => []]]);
		$template->setHtmlForBackoffice('<div class="row"><div data-editable-zone-id="mainContent" class="col-sm-12"></div><div>');
		$template->setActive(true);

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$template->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $template;
	}

	protected function getContentSample()
	{
		return ['mainContent' =>
			[
				'id' => 'mainContent', 'grid' => 12, 'type' => 'container',
				'items' => [
					[
						'type' => 'block', 'name' => 'Rbs_Mail_Richtext', 'id' => 2, 'label' => 'Rbs_Mail_Richtext',
						'parameters' => [
							'contentType' => 'Markdown', 'content' => 'It\'s a beautiful HTML sample with {aSubstitution}', 'TTL' => 60
						]
					]
				]
			]
		];
	}

	/**
	 * @return string
	 */
	protected function getHtmlSample()
	{
		return '<!doctype html>
				<html>
					<body>
						<div id="content">
							<div data-type="block" data-id="2" data-name="Rbs_Mail_Richtext">
								<div class="richtext">
									<p>It\'s a beautiful HTML sample with the substitution</p>
								</div>
							</div>
						</div>
					</body>
				</html>';
	}
}