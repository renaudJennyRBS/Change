<?php
namespace Change\Documents;

/**
* @name \Change\Documents\DocumentCodeManager
*/
class DocumentCodeManager
{
	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	/**
	 * @var \Change\Transaction\TransactionManager
	 */
	protected $transactionManager;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return $this
	 */
	public function setDbProvider(\Change\Db\DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
		return $this;
	}

	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 * @return $this
	 */
	public function setTransactionManager(\Change\Transaction\TransactionManager $transactionManager)
	{
		$this->transactionManager = $transactionManager;
		return $this;
	}

	/**
	 * @return \Change\Transaction\TransactionManager
	 */
	protected function getTransactionManager()
	{
		return $this->transactionManager;
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager(\Change\Documents\DocumentManager $documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param \Change\Documents\AbstractDocument|integer $document
	 * @param string $code
	 * @param integer $contextId
	 * @throws \Exception
	 * @return integer|boolean
	 */
	public function addDocumentCode($document, $code, $contextId = 0)
	{
		if ($document instanceof \Change\Documents\AbstractDocument)
		{
			$documentId =  $document->getId();
		}
		elseif (is_numeric($document))
		{
			$documentId =  intval($document);
		}
		else
		{
			return false;
		}

		$code = strval($code);
		if (empty($code))
		{
			return false;
		}
		$contextId = intval($contextId);

		try
		{
			$this->getTransactionManager()->begin();

			$id = $this->getCodeKey($documentId, $code, $contextId);
			if ($id === null)
			{
				$id = $this->insertDocumentCode($documentId, $code, $contextId);
			}
			$this->getTransactionManager()->commit();

			return $id;
		}
		catch (\Exception $e)
		{
			throw $this->getTransactionManager()->rollBack($e);
		}
	}

	/**
	 * @param string $code
	 * @param integer $contextId
	 * @return \Change\Documents\AbstractDocument[]
	 */
	public function getDocumentsByCode($code, $contextId = 0)
	{
		$documents = [];
		$code = strval($code);
		if (empty($code))
		{
			return $documents;
		}
		$contextId = intval($contextId);
		$ids = $this->getDocumentIds($code, $contextId);
		if (count($ids))
		{
			$dm = $this->getDocumentManager();
			foreach ($ids as $id)
			{
				if (!isset($documents[$id]))
				{
					$doc = $dm->getDocumentInstance($id);
					if ($doc) {
						$documents[$id] = $doc;
					}
				}
			}
			return array_values($documents);
		}
		return $documents;
	}

	/**
	 * @param \Change\Documents\AbstractDocument|integer $document
	 * @param integer $contextId
	 * @return string[]
	 */
	public function getCodesByDocument($document, $contextId = 0)
	{
		$codes = [];
		if ($document instanceof \Change\Documents\AbstractDocument)
		{
			$documentId =  $document->getId();
		}
		elseif (is_numeric($document))
		{
			$documentId =  intval($document);
		}
		else
		{
			return $codes;
		}

		$contextId = intval($contextId);
		$array = $this->getCodes($documentId, $contextId);
		if (count($array))
		{
			return array_values(array_unique($array));
		}
		return $codes;
	}

	/**
	 * @param \Change\Documents\AbstractDocument|integer $document
	 * @param string $code
	 * @param integer $contextId
	 * @throws \Exception
	 * @return integer|boolean
	 */
	public function removeDocumentCode($document, $code, $contextId = 0)
	{
		if ($document instanceof \Change\Documents\AbstractDocument)
		{
			$documentId =  $document->getId();
		}
		elseif (is_numeric($document))
		{
			$documentId =  intval($document);
		}
		else
		{
			return false;
		}

		$code = strval($code);
		if (empty($code))
		{
			return false;
		}
		$contextId = intval($contextId);

		try
		{
			$this->getTransactionManager()->begin();
			$id = $this->getCodeKey($documentId, $code, $contextId);
			if ($id !== null)
			{
				$this->deleteCodeKey($id);
			}
			$this->getTransactionManager()->commit();
			return ($id === null) ? true : $id;
		}
		catch (\Exception $e)
		{
			throw $this->getTransactionManager()->rollBack($e);
		}
	}

	/**
	 * @param integer $documentId
	 * @param string $code
	 * @param integer $contextId
	 * @return integer|null
	 */
	protected function getCodeKey($documentId, $code, $contextId)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder('DocumentCodeManager_getCodeKey');
		if (!$qb->isCached()) {
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->column('id'), 'id'))
				->from($fb->table('change_document_code'))
				->where($fb->logicAnd(
					$fb->eq($fb->column('context_id'), $fb->integerParameter('contextId')),
					$fb->eq($fb->column('document_id'), $fb->integerParameter('documentId')),
					$fb->eq($fb->column('code'), $fb->parameter('code'))
				));
		}
		$sq = $qb->query();
		$sq->bindParameter('contextId', $contextId);
		$sq->bindParameter('documentId', $documentId);
		$sq->bindParameter('code', $code);
		return $sq->getFirstResult($sq->getRowsConverter()->addIntCol('id'));
	}

	/**
	 * @param integer $documentId
	 * @param string $code
	 * @param integer $contextId
	 * @return integer
	 */
	protected function insertDocumentCode($documentId, $code, $contextId)
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder('DocumentCodeManager_insertDocumentCode');
		if (!$qb->isCached()) {
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->table('change_document_code'), $fb->column('context_id'), $fb->column('document_id'), $fb->column('code'))
				->addValues($fb->integerParameter('contextId'), $fb->integerParameter('documentId'), $fb->parameter('code'));
		}
		$iq = $qb->insertQuery();
		$iq->bindParameter('contextId', $contextId);
		$iq->bindParameter('documentId', $documentId);
		$iq->bindParameter('code', $code);
		$iq->execute();
		return $iq->getDbProvider()->getLastInsertId('change_document_code');
	}

	/**
	 * @param integer $id
	 */
	protected function deleteCodeKey($id)
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder('DocumentCodeManager_deleteCodeKey');
		if (!$qb->isCached()) {
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->table('change_document_code'))
				->where($fb->eq($fb->column('id'), $fb->integerParameter('id')));
		}
		$iq = $qb->deleteQuery();
		$iq->bindParameter('id', $id);
		$iq->execute();
	}

	/**
	 * @param integer $documentId
	 * @param integer $contextId
	 * @return string[]
	 */
	protected function getCodes($documentId, $contextId)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder('DocumentCodeManager_getCodes');
		if (!$qb->isCached()) {
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->column('id'), 'id'), $fb->alias($fb->column('code'), 'code'))
				->from($fb->table('change_document_code'))
				->where($fb->logicAnd(
					$fb->eq($fb->column('context_id'), $fb->integerParameter('contextId')),
					$fb->eq($fb->column('document_id'), $fb->integerParameter('documentId'))
				));
		}
		$sq = $qb->query();
		$sq->bindParameter('contextId', $contextId);
		$sq->bindParameter('documentId', $documentId);
		return $sq->getResults($sq->getRowsConverter()->addIntCol('id')->addStrCol('code')->indexBy('id')->singleColumn('code'));
	}

	/**
	 * @param string $code
	 * @param integer $contextId
	 * @return integer[]
	 */
	protected function getDocumentIds($code, $contextId)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder('DocumentCodeManager_getDocumentIds');
		if (!$qb->isCached()) {
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->column('id'), 'id'), $fb->alias($fb->column('document_id'), 'documentId'))
				->from($fb->table('change_document_code'))
				->where($fb->logicAnd(
					$fb->eq($fb->column('context_id'), $fb->integerParameter('contextId')),
					$fb->eq($fb->column('code'), $fb->parameter('code'))
				));
		}
		$sq = $qb->query();
		$sq->bindParameter('contextId', $contextId);
		$sq->bindParameter('code', $code);
		return $sq->getResults($sq->getRowsConverter()->addIntCol('id', 'documentId')->indexBy('id')->singleColumn('documentId'));
	}
} 