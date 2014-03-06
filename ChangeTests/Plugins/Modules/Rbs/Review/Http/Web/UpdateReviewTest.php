<?php

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

class UpdateReviewTest extends \ChangeTests\Change\TestAssets\TestCase
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
		$review = $this->getNewReview();

		$event = new Event();
		$event->setParams($this->getDefaultEventArguments());

		$this->assertEquals(60, $review->getRating());
		$this->assertEquals('test for update', $review->getContent()->getRawText());

		$request = new \Change\Http\Request();
		$request->setMethod('POST');
		$data = [
			'reviewId' => $review->getId(),
			'rating' => 80,
			'content' => 'Update this review!',
			'redirectLocation' => 'http://test.rbs.com/redirectReview'
		];
		$request->getPost()->fromArray($data);

		$event->setRequest($request);

		$postReview = new \Rbs\Review\Http\Web\UpdateReview();
		$postReview->execute($event);
		$result = $event->getResult();
		$this->assertEquals(\Zend\Http\Response::STATUS_CODE_302, $result->getHttpStatusCode());
		$headers = $result->getHeaders()->toArray();
		$this->assertEquals('http://test.rbs.com/redirectReview', $headers['Location']);
		$this->assertInstanceOf('\Change\Http\Web\Result\AjaxResult', $result);
		/* @var $result \Change\Http\Web\Result\AjaxResult */
		$data = $result->toArray();
		$this->assertFalse(array_key_exists('error', $data));
		//check the updated review
		$model = $this->getApplicationServices()->getModelManager()->getModelByName('Rbs_Review_Review');
		$dqb = $this->getApplicationServices()->getDocumentManager()->getNewQuery($model);
		$query = $dqb->andPredicates($dqb->eq('id', $review->getId()));
		$reviews = $query->getDocuments();
		$this->assertCount(1, $reviews);
		$updatedReview = $reviews[0];
		/* @var $updatedReview \Rbs\Review\Documents\Review */
		$this->assertGreaterThan(0, $updatedReview->getId());
		$this->assertEquals($review->getAuthorId(), $updatedReview->getAuthorId());
		$this->assertEquals(80, $updatedReview->getRating());
		$this->assertEquals('Update this review!', $updatedReview->getContent()->getRawText());
		$this->assertEquals($review->getTarget()->getId(), $updatedReview->getTarget()->getId());
		$this->assertEquals($review->getSection()->getId(), $updatedReview->getSection()->getId());
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

	/**
	 * @return \Rbs\Review\Documents\Review
	 * @throws Exception
	 */
	protected function getNewReview()
	{
		$review = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Review_Review');
		/* @var $review \Rbs\Review\Documents\Review */
		$review->setRating(60);
		$review->setContent('test for update');
		$review->setPseudonym('Luigi Bros');
		$review->setTarget($this->getNewTarget());
		$review->setSection($this->getNewWebsite());
		$review->setReviewDate(new \DateTime());
		$review->setPromoted(false);

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$review->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $review;
	}
}