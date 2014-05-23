<?php

class MessageTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @var integer
	 */
	protected $newMessageId;

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
		parent::setUp();
		//declare a rich text manager listener for this test suit
		$this->getApplication()->getConfiguration()->addVolatileEntry('Change/Events/RichTextManager/Rbs_Generic', '\\Rbs\\Generic\\Events\\RichTextManager\\Listeners');
		$dm = $this->getApplicationServices()->getDocumentManager();
		$tm = $this->getApplicationServices()->getTransactionManager();

		//Now let's do the message
		$newMessage = $dm->getNewDocumentInstanceByModelName('Rbs_Timeline_Message');
		/* @var $newMessage \Rbs\Timeline\Documents\Message */
		$newMessage->setLabel(' ');
		$newMessage->setMessage('Hello: **Markdown bold**');
		$newMessage->setContextId(0);
		$newMessage->setAuthorId(99);
		$newMessage->setAuthorName('Georges Perec');
		$this->assertNull($newMessage->getMessage()->getHtml());

		try
		{
			$tm->begin();
			$newMessage->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$tm->rollBack($e);
		}

		$this->assertTrue($newMessage->getId() > 0);
		$this->newMessageId = $newMessage->getId();
	}

	public function testOnCreate()
	{
		//test the markdown parser call when a new message creation
		$dm = $this->getApplicationServices()->getDocumentManager();
		$newMessage = $dm->getDocumentInstance($this->newMessageId, 'Rbs_Timeline_Message');
		/* @var $newMessage \Rbs\Timeline\Documents\Message */
		$this->assertNotNull($newMessage->getMessage()->getHtml());
		//html result need to be trimed before comparing with expected result
		$trimedHtml = trim($newMessage->getMessage()->getHtml());
		$this->assertEquals('<p>Hello: <strong>Markdown bold</strong></p>', $trimedHtml);

		return $newMessage;
	}

	public function testOnUpdate()
	{
		$dm = $this->getApplicationServices()->getDocumentManager();
		/* @var $message \Rbs\Timeline\Documents\Message */
		$message = $dm->getDocumentInstance($this->newMessageId, 'Rbs_Timeline_Message');

		$message->setMessage('Hello: **Markdown bold** + edited message with *Markdown Italic* :-)');
		$tm = $this->getApplicationServices()->getTransactionManager();

		try
		{
			$tm->begin();
			$message->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$tm->rollBack($e);
		}
		$trimedHtml = trim($message->getMessage()->getHtml());
		$this->assertEquals('<p>Hello: <strong>Markdown bold</strong> + edited message with <em>Markdown Italic</em> :-)</p>', $trimedHtml);
	}

	public function testOnDefaultUpdateRestResult()
	{
		//declare a profile manager listener for this test
		$this->getApplication()->getConfiguration()->addVolatileEntry('Change/Events/ProfileManager/Rbs_Generic', '\\Rbs\\Generic\\Events\\ProfileManager\\Listeners');
		$dm = $this->getApplicationServices()->getDocumentManager();
		$tm = $this->getApplicationServices()->getTransactionManager();
		$newMessage = $dm->getDocumentInstance($this->newMessageId, $this->getApplicationServices()->getModelManager()->getModelByName('Rbs_Timeline_Message'));

		$documentLink = new \Change\Http\Rest\V1\Resources\DocumentLink(new \Change\Http\UrlManager(new \Zend\Uri\Http()), $newMessage, \Change\Http\Rest\V1\Resources\DocumentLink::MODE_PROPERTY);
		$this->assertNotNull($documentLink);
		$result = $documentLink->toArray();
		$this->assertNotNull($result);
		$this->assertArrayHasKey('message', $result);
		$this->assertNotNull($result['message']);
		$this->assertArrayHasKey('authorId', $result);
		$this->assertNotNull($result['authorId']);
		$this->assertArrayHasKey('authorName', $result);
		$this->assertNotNull($result['authorName']);
		//But our message has no context, so contextModel is not defined
		$this->assertArrayNotHasKey('contextModel', $result);
		//And there is no identifier too (user with id 99 doesn't exist)
		$this->assertArrayNotHasKey('authorIdentifier', $result);

		//To do that, we create an user in database
		$user = $this->createProfiledUser();
		$newMessage = $dm->getNewDocumentInstanceByModelName('Rbs_Timeline_Message');
		/* @var $newMessage \Rbs\Timeline\Documents\Message */
		$newMessage->setLabel(' ');
		$newMessage->setMessage('Message in a bottle');
		//this time, we set a context id, the context is the author user document
		$newMessage->setContextId($user->getId());
		$newMessage->setAuthorId($user->getId());
		$newMessage->setAuthorName($user->getLabel());
		try
		{
			$tm->begin();
			$newMessage->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$tm->rollBack($e);
		}

		$documentLink = new \Change\Http\Rest\V1\Resources\DocumentLink(new \Change\Http\UrlManager(new \Zend\Uri\Http()),
			$newMessage, \Change\Http\Rest\V1\Resources\DocumentLink::MODE_PROPERTY);
		$this->assertNotNull($documentLink);
		$result = $documentLink->toArray();
		//now because the context is set we can check the contextModel
		$this->assertArrayHasKey('contextModel', $result);
		$this->assertEquals('Rbs_User_User', $result['contextModel']);
		//and because the author user exist, we can check authorIdentifier
		$this->assertArrayHasKey('authorIdentifier', $result);
		$this->assertNotNull($result['authorIdentifier']);
	}

	/**
	 * @return \Rbs\User\Documents\User
	 */
	protected function createProfiledUser()
	{
		$dm = $this->getApplicationServices()->getDocumentManager();
		$tm = $this->getApplicationServices()->getTransactionManager();

		$user = $dm->getNewDocumentInstanceByModelName('Rbs_User_User');
		/* @var $user \Rbs\User\Documents\User */
		$user->setEmail('writer@rbs.fr');
		//Identifier must be unique, that's why we put a rand number after it
		$user->setLogin('writer' . rand(0, 9999999));
		try
		{
			$tm->begin();
			$user->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$tm->rollBack($e);
		}

		$aUser = new \Rbs\User\Events\AuthenticatedUser($user);
		$pm = $this->getApplicationServices()->getProfileManager();

		$profile = new \Rbs\Admin\Profile\Profile();
		//Choose a british singer for avatar
		$profile->setPropertyValue('avatar', 'Rbs/Admin/img/sting.jpg');
		try
		{
			$tm->begin();
			$pm->saveProfile($aUser, $profile);
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$tm->rollBack($e);
		}
		$this->assertNotNull($pm->loadProfile($aUser, 'Rbs_Admin'));
		return $user;
	}
}