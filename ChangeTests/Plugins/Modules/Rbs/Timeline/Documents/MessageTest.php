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
		$dm = $this->getDocumentServices()->getDocumentManager();
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
		$dm = $this->getDocumentServices()->getDocumentManager();
		$newMessage = $dm->getDocumentInstance($this->newMessageId, $dm->getModelManager()->getModelByName('Rbs_Timeline_Message'));
		/* @var $newMessage \Rbs\Timeline\Documents\Message */
		$this->assertNotNull($newMessage->getMessage()->getHtml());
		//html result need to be trimed before comparing with expected result
		$trimedHtml = trim($newMessage->getMessage()->getHtml());
		$this->assertEquals('<p>Hello: <strong>Markdown bold</strong></p>', $trimedHtml);

		return $newMessage;
	}

	/**
	 * @depends testOnCreate
	 * @param \Rbs\Timeline\Documents\Message $message
	 */
	public function testOnUpdate($message)
	{
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

	public function testUpdateRestDocumentResult()
	{
		//declare a profile manager listener for this test
		$this->getApplication()->getConfiguration()->addVolatileEntry('Change/Events/ProfileManager/Rbs_Generic', '\\Rbs\\Generic\\Events\\ProfileManager\\Listeners');
		$dm = $this->getDocumentServices()->getDocumentManager();
		$tm = $this->getApplicationServices()->getTransactionManager();
		$newMessage = $dm->getDocumentInstance($this->newMessageId, $dm->getModelManager()->getModelByName('Rbs_Timeline_Message'));

		$documentResult = new \Change\Http\Rest\Result\DocumentResult(new \Change\Http\UrlManager(new \Zend\Uri\Http()), $newMessage);
		$this->assertNotNull($documentResult);
		$result = $documentResult->toArray();
		$this->assertNotNull($result);
		$this->assertArrayHasKey('properties', $result);
		$this->assertNotNull($result['properties']);
		$this->assertArrayHasKey('avatar', $result['properties']);
		//avatar is not null because there is a default avatar
		$this->assertNotNull($result['properties']['avatar']);
		$defaultAvatar = $result['properties']['avatar'];

		//Test the avatar part
		//To do that, we create an user in database with a different avatar in his profile
		$user = $this->createProfiledUser();
		$newMessage = $dm->getNewDocumentInstanceByModelName('Rbs_Timeline_Message');
		/* @var $newMessage \Rbs\Timeline\Documents\Message */
		$newMessage->setLabel(' ');
		$newMessage->setMessage('Message in a bottle');
		$newMessage->setContextId(0);
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

		$documentResult = new \Change\Http\Rest\Result\DocumentResult(new \Change\Http\UrlManager(new \Zend\Uri\Http()), $newMessage);
		$this->assertNotNull($documentResult);
		$result = $documentResult->toArray();
		//in our profiled user, we set a different value for the avatar
		$this->assertNotEquals($defaultAvatar, $result['properties']['avatar']);
	}

	public function testUpdateRestDocumentLink()
	{
		//declare a profile manager listener for this test
		$this->getApplication()->getConfiguration()->addVolatileEntry('Change/Events/ProfileManager/Rbs_Generic', '\\Rbs\\Generic\\Events\\ProfileManager\\Listeners');
		$dm = $this->getDocumentServices()->getDocumentManager();
		$tm = $this->getApplicationServices()->getTransactionManager();
		$newMessage = $dm->getDocumentInstance($this->newMessageId, $dm->getModelManager()->getModelByName('Rbs_Timeline_Message'));

		$documentLink = new \Change\Http\Rest\Result\DocumentLink(new \Change\Http\UrlManager(new \Zend\Uri\Http()), $newMessage, \Change\Http\Rest\Result\DocumentLink::MODE_PROPERTY);
		$this->assertNotNull($documentLink);
		$result = $documentLink->toArray();
		$this->assertNotNull($result);
		$this->assertArrayHasKey('message', $result);
		$this->assertNotNull($result['message']);
		$this->assertArrayHasKey('authorId', $result);
		$this->assertNotNull($result['authorId']);
		$this->assertArrayHasKey('authorName', $result);
		$this->assertNotNull($result['authorName']);
		$this->assertArrayHasKey('avatar', $result);
		//avatar is not null because there is a default avatar
		$this->assertNotNull($result['avatar']);
		$defaultAvatar = $result['avatar'];
		//But our message has no context, so contextModel is not defined
		$this->assertArrayNotHasKey('contextModel', $result);
		//And there is no identifier too (user with id 99 doesn't exist)
		$this->assertArrayNotHasKey('authorIdentifier', $result);

		//Test the avatar part
		//To do that, we create an user in database with a different avatar in his profile
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

		$documentLink = new \Change\Http\Rest\Result\DocumentLink(new \Change\Http\UrlManager(new \Zend\Uri\Http()),
			$newMessage, \Change\Http\Rest\Result\DocumentLink::MODE_PROPERTY);
		$this->assertNotNull($documentLink);
		$result = $documentLink->toArray();
		//in our profiled user, we set a different value for the avatar
		$this->assertNotEquals($defaultAvatar, $result['avatar']);
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
		$dm = $this->getDocumentServices()->getDocumentManager();
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
		$pm = new \Change\User\ProfileManager();
		$pm->setDocumentServices($this->getDocumentServices());

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