<?php
namespace Rbs\Generic\Http\Rest\Actions;
use Change\Http\Rest\Result\ArrayResult;
use Zend\Http\Response;

/**
 * @name \Rbs\Generic\Http\Rest\Actions\DocumentFilters
 */
class DocumentFilters
{
	/**
	 * @param \Change\Http\Event $event
	 */
	public function getFiltersList(\Change\Http\Event $event)
	{
		$modelName = $event->getRequest()->getQuery('model');
		if ($modelName)
		{
			$applicationServices = $event->getApplicationServices();
			$result = new ArrayResult();

			$qb = $applicationServices->getDbProvider()->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select();
			$qb->from($fb->table('change_document_filters'));
			$qb->where($fb->eq($fb->column('model_name'), $fb->parameter('modelName')));
			$sc = $qb->query();
			$sc->bindParameter('modelName', $modelName);

			$filters = array();
			foreach ($sc->getResults() as $row)
			{
				$row['filter_id'] = intval($row['filter_id']);
				$row['user_id'] = intval($row['user_id']);
				if ($row['content'])
				{
					$row['content'] = json_decode($row['content']);
				}
				$filters[] = $row;
			}

			$result->setArray($filters);
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		}
		else
		{
			$result = new \Change\Http\Rest\Result\ErrorResult(999999, 'invalid model name');
		}

		$event->setResult($result);
	}


	/**
	 * @param \Change\Http\Event $event
	 * @throws \Exception
	 */
	public function createFilter(\Change\Http\Event $event)
	{
		$params = $event->getRequest()->getPost();

		$modelName = $params->get('model_name');
		$content = $params->get('content');
		$title = $params->get('title');

		if ($modelName && $content && $title)
		{
			$result = new ArrayResult();

			$applicationServices = $event->getApplicationServices();
			$userId = $applicationServices->getAuthenticationManager()->getCurrentUser()->getId();
			$transactionManager = $applicationServices->getTransactionManager();
			try
			{
				$transactionManager->begin();

				$stmt = $event->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
				$fb = $stmt->getFragmentBuilder();

				$stmt->insert($fb->table('change_document_filters'), 'model_name', 'title', 'content', 'user_id');
				$stmt->addValues($fb->parameter('model_name'), $fb->parameter('title'), $fb->parameter('content'), $fb->parameter('user_id'));
				$query = $stmt->insertQuery();
				$query->bindParameter('model_name', $modelName);
				$query->bindParameter('title', $title);
				$query->bindParameter('content', json_encode($content));
				$query->bindParameter('user_id', $userId);
				$query->execute();

				$transactionManager->commit();

				$newFilterId = $event->getApplicationServices()->getDbProvider()->getLastInsertId('change_document_filters');
				$result->setArray([
					'filter_id' => $newFilterId,
					'title' => $title,
					'model_name' => $modelName,
					'content' => $content,
					'user_id' => $userId
				]);

			}
			catch (\Exception $e)
			{
				throw $transactionManager->rollBack($e);
			}

			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		}
		else
		{
			$result = new \Change\Http\Rest\Result\ErrorResult(999999, 'invalid model name');
		}

		$event->setResult($result);
	}


	/**
	 * @param \Change\Http\Event $event
	 * @throws \Exception
	 */
	public function updateFilter(\Change\Http\Event $event)
	{
		$params = $event->getRequest()->getPost();

		$modelName = $params->get('model_name');
		$content = $params->get('content');
		$id = $params->get('filter_id');
		$title = $params->get('title');

		if ($id && $modelName && $content && $title)
		{
			$result = new ArrayResult();
			$applicationServices = $event->getApplicationServices();

			$transactionManager = $applicationServices->getTransactionManager();
			try
			{
				$transactionManager->begin();

				$stmt = $event->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
				$fb = $stmt->getFragmentBuilder();

				$stmt->update($fb->table('change_document_filters'));
				$stmt->assign($fb->column('model_name'), $fb->parameter('model_name'));
				$stmt->assign($fb->column('title'), $fb->parameter('title'));
				$stmt->assign($fb->column('content'), $fb->parameter('content'));
				$stmt->where($fb->eq($fb->column('filter_id'), $fb->integerParameter('filter_id')));
				$query = $stmt->updateQuery();
				$query->bindParameter('filter_id', $id);
				$query->bindParameter('model_name', $modelName);
				$query->bindParameter('title', $title);
				$query->bindParameter('content', json_encode($content));
				$query->execute();

				$transactionManager->commit();

				$result->setArray([
					'filter_id' => $id,
					'title' => $title,
					'model_name' => $modelName,
					'content' => $content
				]);

			}
			catch (\Exception $e)
			{
				throw $transactionManager->rollBack($e);
			}

			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		}
		else
		{
			$result = new \Change\Http\Rest\Result\ErrorResult(999999, 'invalid model name and/or filter id');
		}

		$event->setResult($result);
	}


	/**
	 * @param \Change\Http\Event $event
	 * @throws \Exception
	 */
	public function deleteFilter(\Change\Http\Event $event)
	{
		$id = $event->getRequest()->getQuery('filter_id');

		if ($id)
		{
			$applicationServices = $event->getApplicationServices();

			$transactionManager = $applicationServices->getTransactionManager();
			try
			{
				$transactionManager->begin();

				$stmt = $event->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
				$fb = $stmt->getFragmentBuilder();
				$stmt->delete($fb->table('change_document_filters'));
				$stmt->where($fb->eq($fb->column('filter_id'), $fb->integerParameter('filter_id')));
				$query = $stmt->deleteQuery();
				$query->bindParameter('filter_id', $id);
				$query->execute();

				$transactionManager->commit();
			}
			catch (\Exception $e)
			{
				throw $transactionManager->rollBack($e);
			}

			$result = new ArrayResult();
			$result->setArray([]);
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		}
		else
		{
			$result = new \Change\Http\Rest\Result\ErrorResult(999999, 'invalid filter id');
		}

		$event->setResult($result);
	}
}