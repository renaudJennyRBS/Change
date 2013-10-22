<?php

class UserTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	public function tearDown()
	{
		//delete the new user if exist
		$this->deleteNewUserIfExist();

		parent::tearDown();
	}

	public function testOnCreate()
	{
		$user = $this->getNewUser(false);
		$this->assertNull($user->getHashMethod());
		$user = $this->getNewUser(true);
		$this->assertNotNull($user->getHashMethod());
		$this->assertEquals('bcrypt', $user->getHashMethod());
		$this->assertNotNull($user->getPasswordHash());
		$bcryptHash = $user->getPasswordHash();
		$this->assertNotEquals('abcd123', $bcryptHash);
		$this->deleteNewUserIfExist();
		//a salt must be defined if you want a custom hash
		$this->getApplication()->getConfiguration()->addVolatileEntry('Rbs/User/salt', 'aSalt');
		$user = $this->getNewUser(true, 'md5');
		$this->assertNotNull($user->getHashMethod());
		$this->assertEquals('md5', $user->getHashMethod());
		$this->assertNotNull($user->getPasswordHash());
		$this->assertNotEquals($bcryptHash, $user->getPasswordHash());
	}

	public function testOnUpdate()
	{
		$user = $this->getNewUser(true);
		$this->assertNotNull($user->getPasswordHash());
		$passwordHash = $user->getPasswordHash();
		$user->setPassword('321dcba');
		$dm = $this->getDocumentServices()->getDocumentManager();
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$user->update();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$tm->rollBack($e);
			$this->fail('new user cannot be updated with this error: ' . $e->getMessage());
		}
		$user = $dm->getDocumentInstance($user->getId());
		/* @var $user \Rbs\User\Documents\User */
		$this->assertNotNull($user->getPasswordHash());
		$this->assertNotEquals($passwordHash, $user->getPasswordHash());
	}

	public function testUpdateRestDocumentResult()
	{
		$documentResult = new \Change\Http\Rest\Result\DocumentResult(new \Change\Http\UrlManager(new \Zend\Uri\Http()), $this->getNewUser());
		$this->assertNotNull($documentResult);
		$result = $documentResult->toArray();
		$this->assertNotNull($result);
		$this->assertArrayHasKey('links', $result);
		$this->assertNotNull($result['links']);
		//search in links if the profile link is available
		$isProfileLinkAvailable = false;
		foreach ($result['links'] as $link)
		{
			if (in_array('profiles', $link))
			{
				$this->assertArrayHasKey('rel', $link);
				$this->assertEquals('profiles', $link['rel']);
				$this->assertArrayHasKey('href', $link);
				$this->assertNotNull($link['href']);
				$isProfileLinkAvailable = true;
			}
		}
		$this->assertTrue($isProfileLinkAvailable, 'link profiles has to be in Rest User document result');
	}

	/**
	 * @param boolean $save
	 * @return \Rbs\User\Documents\User
	 */
	protected function getNewUser($save = false, $hashMethod = null)
	{
		$dm = $this->getDocumentServices()->getDocumentManager();
		$tm = $this->getApplicationServices()->getTransactionManager();

		$newUser = $dm->getNewDocumentInstanceByModelName('Rbs_User_User');
		/* @var $newUser \Rbs\User\Documents\User */
		$newUser->setLogin(\Change\Stdlib\String::random(16));
		$newUser->setEmail('mario.bros@nintendo.com');
		$newUser->setPassword('abcd123');
		if ($hashMethod)
		{
			$newUser->setHashMethod($hashMethod);
		}

		if ($save)
		{
			try
			{
				$tm->begin();
				$newUser->save();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				$tm->rollBack($e);
				$this->fail('new user cannot be created with this error: ' . $e->getMessage());
			}
			$this->assertTrue($newUser->getId() > 0);
		}

		return $newUser;
	}

	protected function deleteNewUserIfExist()
	{
		$dqb = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_User_User');
		$user = $dqb->andPredicates($dqb->eq('login', 'super_mario'))->getFirstDocument();
		if ($user)
		{
			$tm = $this->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();
				$user->delete();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				$tm->rollBack($e);
				$this->fail('new user cannot be deleted with this error: ' . $e->getMessage());
			}
		}
	}
}