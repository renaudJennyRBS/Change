<?php
namespace Change\Documents\Query;

use Change\Db\Query\Expressions\Join;
use Change\Db\Query\Expressions\UnaryOperation;
use Change\Db\Query\Predicates\Like;
use Change\Documents\AbstractDocument;
use Change\Documents\Property;
use Change\Documents\TreeManager;
use Change\Documents\TreeNode;

/**
* @name \Change\Documents\Query\TreePredicateBuilder
*/
class TreePredicateBuilder
{
	/**
	 * @var AbstractBuilder
	 */
	protected $builder;

	/**
	 * @var TreeManager
	 */
	protected $treeManager;

	/**
	 * @var PredicateBuilder
	 */
	protected $predicateBuilder;

	/**
	 * @param AbstractBuilder $builder
	 * @param TreeManager $treeManager
	 */
	function __construct(AbstractBuilder $builder, TreeManager $treeManager)
	{
		$this->builder = $builder;
		$this->treeManager = $treeManager;
	}

	/**
	 * @return \Change\Db\Query\SQLFragmentBuilder
	 */
	protected function getFragmentBuilder()
	{
		return $this->builder->getFragmentBuilder();
	}

	/**
	 * @return \Change\Documents\Query\PredicateBuilder
	 */
	protected function getPredicateBuilder()
	{
		if ($this->predicateBuilder === null)
		{
			$this->predicateBuilder = $this->builder->getPredicateBuilder();
		}
		return $this->predicateBuilder;
	}

	/**
	 * @param TreeNode|AbstractDocument|integer $node
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	protected function validateNodeArgument($node)
	{
		if ($node instanceof AbstractDocument)
		{
			$document = $node;
		}
		elseif ($node instanceof TreeNode)
		{
			$document = $node->setTreeManager($this->treeManager)->getDocument();
		}
		elseif (is_numeric($node))
		{
			$node = $this->treeManager->getNodeById($node);
			if ($node)
			{
				$document = $node->setTreeManager($this->treeManager)->getDocument();
			}
			else
			{
				$document = null;
			}
		}
		else
		{
			$document = null;
		}

		if ($document === null || !$document->getDocumentModel()->useTree())
		{
			throw new \InvalidArgumentException('Argument 1 must by a valid node', 999999);
		}

		$node = $this->treeManager->getNodeByDocument($document);
		if ($node === null)
		{
			throw new \InvalidArgumentException('Argument 1 must by a valid node', 999999);
		}
		return array($document, $node);
	}

	/**
	 * @param \Change\Db\Query\SQLFragmentBuilder $fragmentBuilder
	 * @param \Change\Db\Query\Expressions\Table $treeTable
	 * @param string $treeTableIdentifier
	 * @param string|Property $propertyName
	 * @return Join
	 */
	protected function buildTreeTableJoin($fragmentBuilder, $treeTable, $treeTableIdentifier, $propertyName)
	{
		$id = $this->getPredicateBuilder()->eq($propertyName, $fragmentBuilder->getDocumentColumn('id', $treeTableIdentifier));
		$joinExpr = new UnaryOperation($id, 'ON');
		$join = new Join($fragmentBuilder->alias($treeTable, $treeTableIdentifier), Join::INNER_JOIN, $joinExpr);
		return $join;
	}

	/**
	 * @param TreeNode|AbstractDocument|integer $node
	 * @param string|Property $propertyName
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 * @throws \InvalidArgumentException
	 */
	public function childOf($node, $propertyName = 'id')
	{
		list($document, $node) = $this->validateNodeArgument($node);

		/* @var $document AbstractDocument */
		/* @var $node TreeNode */
		$fb = $this->getFragmentBuilder();
		$treeTable = $fb->getTreeTable($node->getTreeName());
		$treeTableIdentifier = '_j' . $this->builder->getQuery()->getNextAliasCounter() . 'T';
		$join = $this->buildTreeTableJoin($fb, $treeTable, $treeTableIdentifier, $propertyName);

		$this->builder->addJoin($treeTableIdentifier, $join);

		return $fb->eq($fb->column('parent_id', $treeTableIdentifier),
			$this->builder->getValueAsParameter($document->getId(), Property::TYPE_INTEGER));
	}

	/**
	 * @param TreeNode|AbstractDocument|integer $node
	 * @param string|Property $propertyName
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 * @throws \InvalidArgumentException
	 */
	public function descendantOf($node, $propertyName = 'id')
	{
		list(, $node) = $this->validateNodeArgument($node);

		/* @var $node TreeNode */
		$fb = $this->getFragmentBuilder();
		$treeTable = $fb->getTreeTable($node->getTreeName());
		$treeTableIdentifier = '_j' . $this->builder->getQuery()->getNextAliasCounter() . 'T';

		$join = $this->buildTreeTableJoin($fb, $treeTable, $treeTableIdentifier, $propertyName);
		$this->builder->addJoin($treeTableIdentifier, $join);

		return $fb->like($fb->column('node_path', $treeTableIdentifier),
			$this->builder->getValueAsParameter($node->getFullPath(), Property::TYPE_STRING),
			Like::BEGIN);
	}

	/**
	 * @param TreeNode|AbstractDocument|integer $node
	 * @param string|Property $propertyName
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 * @throws \InvalidArgumentException
	 */
	public function ancestorOf($node, $propertyName = 'id')
	{
		list(, $node) = $this->validateNodeArgument($node);

		/* @var $node TreeNode */
		$fb = $this->getFragmentBuilder();
		$treeTable = $fb->getTreeTable($node->getTreeName());
		$treeTableIdentifier = '_j' . $this->builder->getQuery()->getNextAliasCounter() . 'T';

		$join = $this->buildTreeTableJoin($fb, $treeTable, $treeTableIdentifier, $propertyName);
		$this->builder->addJoin($treeTableIdentifier, $join);
		$ancestorsIds = $node->getAncestorIds();
		if (count($ancestorsIds) == 0)
		{
			$ancestorsIds[] = -1;
		}
		return $this->getPredicateBuilder()->in($fb->getDocumentColumn('id', $treeTableIdentifier), $ancestorsIds);
	}

	/**
	 * @param TreeNode|AbstractDocument|integer $node
	 * @param string|Property $propertyName
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 * @throws \InvalidArgumentException
	 */
	public function nextSiblingOf($node, $propertyName = 'id')
	{
		list(, $node) = $this->validateNodeArgument($node);

		/* @var $node TreeNode */
		$fb = $this->getFragmentBuilder();
		$treeTable = $fb->getTreeTable($node->getTreeName());
		$treeTableIdentifier = '_j' . $this->builder->getQuery()->getNextAliasCounter() . 'T';
		$join = $this->buildTreeTableJoin($fb, $treeTable, $treeTableIdentifier, $propertyName);

		$this->builder->addJoin($treeTableIdentifier, $join);

		return $fb->logicAnd(
			$fb->eq($fb->column('parent_id', $treeTableIdentifier),
				$this->builder->getValueAsParameter($node->getParentId(), Property::TYPE_INTEGER)),
			$fb->gt($fb->column('node_order', $treeTableIdentifier),
				$this->builder->getValueAsParameter($node->getPosition(), Property::TYPE_INTEGER))
		);
	}

	/**
	 * @param TreeNode|AbstractDocument|integer $node
	 * @param string|Property $propertyName
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 * @throws \InvalidArgumentException
	 */
	public function previousSiblingOf($node, $propertyName = 'id')
	{
		list(, $node) = $this->validateNodeArgument($node);
		/* @var $node TreeNode */
		$fb = $this->getFragmentBuilder();
		$treeTable = $fb->getTreeTable($node->getTreeName());
		$treeTableIdentifier = '_j' . $this->builder->getQuery()->getNextAliasCounter() . 'T';
		$join = $this->buildTreeTableJoin($fb, $treeTable, $treeTableIdentifier, $propertyName);

		$this->builder->addJoin($treeTableIdentifier, $join);

		return $fb->logicAnd(
			$fb->eq($fb->column('parent_id', $treeTableIdentifier),
				$this->builder->getValueAsParameter($node->getParentId(), Property::TYPE_INTEGER)),
			$fb->lt($fb->column('node_order', $treeTableIdentifier),
				$this->builder->getValueAsParameter($node->getPosition(), Property::TYPE_INTEGER))
		);
	}
} 