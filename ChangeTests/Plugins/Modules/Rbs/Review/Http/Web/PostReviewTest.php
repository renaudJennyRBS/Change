<?php

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

class PostReviewTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		$appServices = static::initDocumentsDb();
		$schema = new \Rbs\Catalog\Setup\Schema($appServices->getDbProvider()->getSchemaManager());
		$schema->generate();
	}

	public static function tearDownAfterClass()
	{
			static::clearDB();
	}

	protected function attachSharedListener(\Zend\EventManager\SharedEventManager $sharedEventManager)
	{
		parent::attachSharedListener($sharedEventManager);
		$this->attachCommerceServicesSharedListener($sharedEventManager);
	}

	protected function setUp()
	{
		parent::setUp();
		$this->initServices($this->getApplication());
	}

	public function testExecute()
	{
		$user = $this->getNewUser();
		$target = $this->getNewTarget();
		$section = $this->getNewWebsite();

		$event = new Event();
		$event->setParams($this->getDefaultEventArguments());

		$request = new \Change\Http\Request();
		$request->setMethod('POST');
		$data = [
			'userId' => $user->getId(),
			'rating' => 80,
			'content' => 'This test is good, I set 4 stars for it.',
			'targetId' => $target->getId(),
			'sectionId' => $section->getId()
		];
		$request->getPost()->fromArray($data);

		$event->setRequest($request);

		$postReview = new \Rbs\Review\Http\Web\PostReview();
		$postReview->execute($event);
		$result = $event->getResult();
		$this->assertInstanceOf('\Change\Http\Web\Result\AjaxResult', $result);
		/* @var $result \Change\Http\Web\Result\AjaxResult */
		$data = $result->toArray();
		$this->assertFalse(array_key_exists('error', $data));
		//check if review is created
		$model = $this->getApplicationServices()->getModelManager()->getModelByName('Rbs_Review_Review');
		$dqb = $this->getApplicationServices()->getDocumentManager()->getNewQuery($model);
		$query = $dqb->andPredicates($dqb->eq('target', $target));
		$reviews = $query->getDocuments();
		$this->assertCount(1, $reviews);
		$review = $reviews[0];
		/* @var $review \Rbs\Review\Documents\Review */
		$this->assertGreaterThan(0, $review->getId());
		$this->assertEquals($user->getId(), $review->getAuthorId());
		$this->assertEquals(80, $review->getRating());
		$this->assertEquals('This test is good, I set 4 stars for it.', $review->getContent()->getRawText());
		$this->assertEquals($target->getId(), $review->getTarget()->getId());
		$this->assertEquals($section->getId(), $review->getSection()->getId());
	}

	/**
	 * @return \Rbs\Catalog\Documents\Product
	 * @throws Exception
	 */
	protected function getNewTarget()
	{
		$target = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
		/* @var $target \Rbs\Catalog\Documents\Product */
		$target->setLabel('Nintendo NES');
		$target->getCurrentLocalization()->setTitle('Nintendo NES');
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$target->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $target;
	}

	/**
	 * @return \Rbs\User\Documents\User
	 * @throws Exception
	 */
	protected function getNewUser()
	{
		$user = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_User_User');
		/* @var $user \Rbs\User\Documents\User */
		$user->setLabel('Mario Bros');
		$user->setLogin('mario');
		$user->setEmail('mario.bros@nintendo.com');
		$user->setPassword('abcd123');
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$user->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $user;
	}
	
	/**
	 * @return \Rbs\Website\Documents\Website
	 * @throws Exception
	 */
	protected function getNewWebsite()
	{
		$website = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Website_Website');
		/* @var $website \Rbs\Website\Documents\Website */
		$website->setLabel('test');
		$website->getCurrentLocalization()->setTitle('test');
		$website->setBaseurl('http://test.rbs.fr');

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
}