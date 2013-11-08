<?php
namespace ChangeTests\Change\Permissions;

use Change\Permissions\PermissionsManager;

/**
 * @name \ChangeTests\Change\Permissions\PermissionManagerTest
 */
class PermissionManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	/**
	 * @return PermissionsManager
	 */
	protected function getPermissionManager()
	{
		return $this->getApplicationServices()->getPermissionsManager();
	}

	public function testConstruct()
	{
		$permissionManager = $this->getPermissionManager();
		$this->assertInstanceOf('\Change\Permissions\PermissionsManager', $permissionManager);
	}

	/**
	 * @depends testConstruct
	 */
	public function testGetSectionAccessorIds()
	{
		//TODO draft, improve this test
		$this->createSectionWithPermissionRules();
		$permissionManager = $this->getPermissionManager();
		$accessorIds = $permissionManager->getSectionAccessorIds(100, 90);
		$this->assertCount(3, $accessorIds);
		$accessorIds = $permissionManager->getSectionAccessorIds(100, 90, 'Rbs_User_User');
		$this->assertCount(2, $accessorIds);
		$accessorIds = $permissionManager->getSectionAccessorIds(100, 90, 'Rbs_User_Group');
		$this->assertCount(1, $accessorIds);
	}

	protected function createSectionWithPermissionRules()
	{
		$tm = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();

		//TODO draft, improve this test
		$website = $this->getNewReadonlyDocument('Rbs_Website_Website', 90);
		$topic1 = $this->getNewReadonlyDocument('Rbs_Website_Topic', 100);

		$users = $this->createUsers(3);
		$groups = $this->createGroups(3);

		$permissionManager = $this->getPermissionManager();
		$permissionManager->addWebRule($topic1->getId(), $website->getId(), $users[0]->getId());
		$permissionManager->addWebRule($topic1->getId(), $website->getId(), $users[1]->getId());
		$permissionManager->addWebRule($topic1->getId(), $website->getId(), $groups[0]->getId());

		$tm->commit();
	}

	/**
	 * @param int $number
	 * @return \Rbs\User\Documents\User[]
	 * @throws \Exception
	 */
	protected function createUsers($number)
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		$users = [];
		for ($i = 0; $i < $number; $i++)
		{
			$user = $documentManager->getNewDocumentInstanceByModelName('Rbs_User_User');
			/* @var $user \Rbs\User\Documents\User */
			$user->setLabel('user' . $i);
			$user->setLogin('user' . $i);
			$user->setEmail('user' . $i . '@rbs.fr');

			$tm = $this->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();
				$user->save();
				$tm->commit();
			}
			catch(\Exception $e)
			{
				throw $tm->rollBack($e);
			}
			$users[] = $user;
		}
		return $users;
	}

	/**
	 * @param int $number
	 * @return \Rbs\User\Documents\Group[]
	 * @throws \Exception
	 */
	protected function createGroups($number)
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		$groups = [];
		for ($i = 0; $i < $number; $i++)
		{
			$group = $documentManager->getNewDocumentInstanceByModelName('Rbs_User_Group');
			/* @var $group \Rbs\User\Documents\Group */
			$group->setLabel('group' . $i);

			$tm = $this->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();
				$group->save();
				$tm->commit();
			}
			catch(\Exception $e)
			{
				throw $tm->rollBack($e);
			}
			$groups[] = $group;
		}
		return $groups;
	}
}
