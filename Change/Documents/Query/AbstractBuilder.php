<?php
namespace Change\Documents\Query;

use Change\Db\Query\Expressions\Join;
use Change\Db\Query\Expressions\Parameter;
use Change\Db\Query\Predicates\Conjunction;
use Change\Db\Query\Predicates\Disjunction;
use Change\Db\Query\Predicates\InterfacePredicate;
use Change\Documents\AbstractDocument;
use Change\Documents\AbstractModel;
use Change\Documents\Property;
use Change\Documents\TreeNode;

/**
 * @name \Change\Documents\Query\AbstractBuilder
 */
abstract class AbstractBuilder
{
	/**
	 * @var AbstractModel
	 */
	protected $model;

	/**
	 * @var string
	 */
	protected $tableAliasName;

	/**
	 * @var string
	 */
	protected $LCID;

	/**
	 * @var string
	 */
	protected $localizedTableAliasName;

	/**
	 * @var ChildBuilder[]
	 */
	protected $childBuilderArray;

	/**
	 * @var Join[]
	 */
	protected $joinArray;

	/**
	 * @var PredicateBuilder
	 */
	protected $predicateBuilder;


	/**
	 * @param AbstractModel $model
	 */
	function __construct(AbstractModel $model)
	{
		$this->setModel($model);
		$this->setTableAliasName('_t' . $this->getQuery()->getNextAliasCounter());
	}

	/**
	 * @param AbstractModel $model
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	protected function setModel(AbstractModel $model)
	{
		if ($model->isStateless())
		{
			throw new \InvalidArgumentException('Argument 1 is stateless model', 999999);
		}
		$this->model = $model;
		return $this;
	}

	/**
	 * @return AbstractModel
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * @return \Change\Documents\Query\PredicateBuilder
	 */
	public function getPredicateBuilder()
	{
		if ($this->predicateBuilder === null)
		{
			$this->predicateBuilder = new PredicateBuilder($this);
		}
		return $this->predicateBuilder;
	}

	/**
	 * @return string
	 */
	public function getTableAliasName()
	{
		return $this->tableAliasName;
	}

	/**
	 * @param string $tableAliasName
	 * @return $this
	 */
	protected function setTableAliasName($tableAliasName)
	{
		$this->tableAliasName = $tableAliasName;
		return $this;
	}

	/**
	 * @param string $LCID
	 * @return $this
	 */
	public function setLCID($LCID)
	{
		$this->LCID = $LCID;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLCID()
	{
		if ($this->LCID === null)
		{
			$this->LCID = $this->getDocumentServices()->getDocumentManager()->getLCID();
		}
		return $this->LCID;
	}

	/**
	 * @return string
	 */
	public function getLocalizedTableAliasName()
	{
		if ($this->localizedTableAliasName === null)
		{
			$this->localizedTableAliasName = $this->getTableAliasName() . 'L';
		}
		return $this->localizedTableAliasName;
	}

	/**
	 * @return boolean
	 */
	public function hasLocalizedTable()
	{
		return ($this->localizedTableAliasName !== null);
	}

	protected function setJoinArray($joinArray)
	{
		$this->joinArray = $joinArray;
	}

	protected function getJoinArray()
	{
		return $this->joinArray;
	}

	/**
	 * @param string $tableAliasName
	 * @return Join|null
	 */
	public function getJoin($tableAliasName)
	{
		return isset($this->joinArray[$tableAliasName]) ? $this->joinArray[$tableAliasName] : null;
	}

	/**
	 * @param string $tableAliasName
	 * @param Join $join
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function addJoin($tableAliasName, Join $join)
	{
		$this->joinArray[$tableAliasName] = $join;


		return $this;
	}

	/**
	 * @return array|null
	 */
	protected function getModelFilters()
	{
		$model = $this->model;
		if ($model->hasDescendants())
		{
			return array_merge(array($model->getName()), $model->getDescendantsNames());
		}
		elseif ($model->hasParent())
		{
			return array($model->getName());
		}
		return null;
	}

	/**
	 * @api
	 * @param Property|string $propertyName
	 * @return \Change\Documents\Property|null
	 */
	public function getValidProperty($propertyName)
	{
		if ($propertyName instanceof Property)
		{
			return $this->model->getProperty($propertyName->getName());
		}
		elseif (is_string($propertyName))
		{
			return $this->model->getProperty($propertyName);
		}
		return null;
	}

	/**
	 * @api
	 * @param Property|string $propertyName
	 * @throws \InvalidArgumentException
	 * @return ChildBuilder
	 */
	public function getPropertyBuilder($propertyName)
	{
		$property = $this->getValidProperty($propertyName);
		if ($property === null || $property->getStateless() || $property->getDocumentType() === null)
		{
			throw new \InvalidArgumentException('Argument 1 must be a valid document Property', 999999);
		}
		$model = $this->getDocumentServices()->getModelManager()->getModelByName($property->getDocumentType());
		if ($model === null || $model->isStateless())
		{
			throw new \InvalidArgumentException('Argument 1 must be a valid document Property', 999999);
		}

		$childBuilder = new ChildBuilder($this, $model, $property, $model->getProperty('id'));
		$this->childBuilderArray[$childBuilder->getTableAliasName()] = $childBuilder;
		return $childBuilder;
	}

	/**
	 * @api
	 * @param AbstractModel|string $modelName
	 * @param Property|string $propertyName
	 * @throws \InvalidArgumentException
	 * @return ChildBuilder
	 */
	public function getModelBuilder($modelName, $propertyName)
	{
		$model = (is_string($modelName)) ?  $this->getDocumentServices()->getModelManager()->getModelByName($modelName) : $modelName;
		if (!($model instanceof AbstractModel) || $model->isStateless())
		{
			throw new \InvalidArgumentException('Argument 1 must be a valid document model', 999999);
		}

		$property = $model->getProperty(($propertyName instanceof Property) ? $propertyName->getName() : $propertyName);
		if ($property === null || $property->getStateless())
		{
			throw new \InvalidArgumentException('Argument 2 must be a valid Property', 999999);
		}

		$childBuilder = new ChildBuilder($this, $model, $this->model->getProperty('id'), $property);
		$this->childBuilderArray[$childBuilder->getTableAliasName()] = $childBuilder;
		return $childBuilder;
	}

	/**
	 * @api
	 * @param Property|string $propertyName
	 * @param AbstractModel|string $modelName
	 * @param Property|string $modelPropertyName
	 * @throws \InvalidArgumentException
	 * @return ChildBuilder
	 */
	public function getPropertyModelBuilder($propertyName, $modelName, $modelPropertyName)
	{
		$property = $this->getValidProperty($propertyName);
		if ($property === null || $property->getStateless() || $property->getLocalized())
		{
			throw new \InvalidArgumentException('Argument 1 must be a valid Property', 999999);
		}

		$model = (is_string($modelName)) ?  $this->getDocumentServices()->getModelManager()->getModelByName($modelName) : $modelName;
		if (!($model instanceof AbstractModel) || $model->isStateless())
		{
			throw new \InvalidArgumentException('Argument 2 must be a valid document model', 999999);
		}

		$modelProperty = $model->getProperty(($modelPropertyName instanceof Property) ? $modelPropertyName->getName() : $modelPropertyName);
		if ( $modelProperty === null ||  $modelProperty->getStateless() || $modelProperty->getLocalized())
		{
			throw new \InvalidArgumentException('Argument 3 must be a valid Property', 999999);
		}

		if ($property->getType() === Property::TYPE_DOCUMENTARRAY && $modelProperty->getType() === Property::TYPE_DOCUMENTARRAY)
		{
			throw new \InvalidArgumentException('Invalid Properties type: DocumentArray', 999999);
		}

		$childBuilder = new ChildBuilder($this, $model, $property, $modelProperty);
		$this->childBuilderArray[$childBuilder->getTableAliasName()] = $childBuilder;


		return $childBuilder;
	}

	/**
	 * @return InterfacePredicate|null
	 */
	abstract protected function getPredicate();

	/**
	 * @param InterfacePredicate $predicate
	 */
	abstract protected function setPredicate(InterfacePredicate $predicate);

	/**
	 * @api
	 * @return Query
	 */
	abstract public function getQuery();

	/**
	 * @api
	 * @return \Change\Db\Query\SQLFragmentBuilder
	 */
	abstract public function getFragmentBuilder();

	/**
	 * @param \Change\Db\Query\Builder $qb
	 * @param \ArrayObject $sysPredicate
	 * @return void
	 */
	abstract protected function populateQueryBuilder($qb, \ArrayObject $sysPredicate = null);

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	abstract protected function getDocumentServices();

	/**
	 * @return \Change\Db\DbProvider
	 */
	abstract protected function getDbProvider();

	/**
	 * @api
	 * @param Parameter $parameter
	 * @param mixed $value
	 */
	abstract public function setValuedParameter(Parameter $parameter, $value);


	/**
	 * @param string|Property $propertyName
	 * @param boolean $asc
	 * @return $this
	 */
	abstract public function addOrder($propertyName, $asc = true);

	/**
	 * @api
	 * @param InterfacePredicate $p1
	 * @param InterfacePredicate $_ [optional]
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function andPredicates($p1, $_ = null)
	{
		$predicate = new Conjunction();
		$pp = $this->getPredicate();
		if ($pp)
		{
			$predicate->addArgument($pp);
		}
		$this->addJunctionArgs($predicate, func_get_args());

		$this->setPredicate($predicate);
		return $this;
	}

	/**
	 * @api
	 * @param InterfacePredicate $p1
	 * @param InterfacePredicate $_ [optional]
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function orPredicates($p1, $_ = null)
	{
		$predicate = new Disjunction();
		$pp = $this->getPredicate();
		if ($pp)
		{
			$predicate->addArgument($pp);
		}
		$this->addJunctionArgs($predicate, func_get_args());
		$this->setPredicate($predicate);
		return $this;
	}

	/**
	 * @param Disjunction|Conjunction $junction
	 * @param array $args
	 * @throws \InvalidArgumentException
	 */
	protected function addJunctionArgs($junction, $args)
	{
		foreach ($args as $arg)
		{
			if (is_array($arg))
			{
				$this->addJunctionArgs($junction, $arg);
			}
			elseif ($arg instanceof \Change\Db\Query\InterfaceSQLFragment)
			{
				$junction->addArgument($arg);
			}
			else
			{
				throw new \InvalidArgumentException('Invalid SQL Fragment', 999999);
			}
		}
	}

	/**
	 * @return Join
	 */
	protected function getLocalizedJoin()
	{
		$pb = $this->getPredicateBuilder();
		$fb = $this->getFragmentBuilder();

		$localizedIdentifier = $fb->identifier($this->getLocalizedTableAliasName());

		$id =  $pb->eq('id', $fb->getDocumentColumn('id', $localizedIdentifier));
		$LCID = $fb->eq($fb->getDocumentColumn('LCID', $localizedIdentifier), $fb->string($this->getLCID()));

		$joinCondition = new \Change\Db\Query\Predicates\Conjunction($id, $LCID);
		$joinExpr = new \Change\Db\Query\Expressions\UnaryOperation($joinCondition, 'ON');

		$localizedTable = $fb->getDocumentI18nTable($this->getModel()->getRootName());
		return new Join($fb->alias($localizedTable, $localizedIdentifier), Join::INNER_JOIN, $joinExpr);
	}

	/**
	 * @api
	 * @param Property|string $propertyName
	 * @throws \InvalidArgumentException
	 * @return \Change\Db\Query\Expressions\Column
	 */
	public function getColumn($propertyName)
	{
		$property = $this->getValidProperty($propertyName);
		if (null === $property || $property->getStateless())
		{
			throw new \InvalidArgumentException('Argument 1 must be a valid property', 999999);
		}
		$tableName = ($property->getLocalized()) ? $this->getLocalizedTableAliasName() : $this->getTableAliasName();
		return $this->getFragmentBuilder()->getDocumentColumn($property->getName(), $tableName);
	}

	/**
	 * @api
	 * @param mixed $value
	 * @param string|Property|null $type
	 * @throws \InvalidArgumentException
	 * @return \Change\Db\Query\Expressions\Parameter
	 */
	public function getValueAsParameter($value, $type = null)
	{
		if ($value instanceof AbstractDocument)
		{
			$value = $value->getId();
		}
		elseif ($value instanceof TreeNode)
		{
			$value = $value->getDocumentId();
		}

		if ($type === null)
		{
			if (is_int($value))
			{
				$type = Property::TYPE_INTEGER;
			}
			elseif(is_float($value))
			{
				$type = Property::TYPE_FLOAT;
			}
			elseif(is_bool($value))
			{
				$type = Property::TYPE_BOOLEAN;
			}
			elseif($value instanceof \DateTime)
			{
				$type = Property::TYPE_DATETIME;
			}
			else
			{
				$type = Property::TYPE_STRING;
			}
		}

		if (is_string($type))
		{
			$dbType = $this->getDbProvider()->getSqlMapping()->getDbScalarType($type);
		}
		elseif ($type instanceof Property)
		{
			$dbType = $this->getDbProvider()->getSqlMapping()->getDbScalarType($type->getType());
		}
		else
		{
			throw new \InvalidArgumentException('Argument 2 must be a valid type', 999999);
		}

		$name = '_p' . $this->getQuery()->getNextAliasCounter();
		$parameter = $this->getFragmentBuilder()->typedParameter($name, $dbType);
		$this->setValuedParameter($parameter, $value);
		return $parameter;
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::eq
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @return InterfacePredicate
	 */
	public function eq($propertyName, $value)
	{
		return $this->getPredicateBuilder()->eq($propertyName, $value);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::neq
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @return InterfacePredicate
	 */
	public function neq($propertyName, $value)
	{
		return $this->getPredicateBuilder()->neq($propertyName, $value);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::gt
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @return InterfacePredicate
	 */
	public function gt($propertyName, $value)
	{
		return $this->getPredicateBuilder()->gt($propertyName, $value);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::lt
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @return InterfacePredicate
	 */
	public function lt($propertyName, $value)
	{
		return $this->getPredicateBuilder()->lt($propertyName, $value);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::gte
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @return InterfacePredicate
	 */
	public function gte($propertyName, $value)
	{
		return $this->getPredicateBuilder()->gte($propertyName, $value);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::lte
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @return InterfacePredicate
	 */
	public function lte($propertyName, $value)
	{
		return $this->getPredicateBuilder()->lte($propertyName, $value);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::like
	 * @param string|Property $propertyName
	 * @param mixed $value
	 * @param integer $matchMode
	 * @param boolean $caseSensitive
	 * @return InterfacePredicate
	 */
	public function like($propertyName, $value, $matchMode = \Change\Db\Query\Predicates\Like::ANYWHERE, $caseSensitive = false)
	{
		return $this->getPredicateBuilder()->like($propertyName, $value, $matchMode, $caseSensitive);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::in
	 * @param string|Property $propertyName
	 * @param string|array|\Change\Db\Query\Expressions\AbstractExpression $rhs1
	 * @return InterfacePredicate
	 */
	public function in($propertyName, $rhs1)
	{
		return $this->getPredicateBuilder()->in($propertyName, $rhs1);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::notIn
	 * @param string|Property $propertyName
	 * @param string|array|\Change\Db\Query\Expressions\AbstractExpression $rhs
	 * @return InterfacePredicate
	 */
	public function notIn($propertyName, $rhs)
	{
		return $this->getPredicateBuilder()->notIn($propertyName, $rhs);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::isNull
	 * @param string|Property $propertyName
	 * @return InterfacePredicate
	 */
	public function isNull($propertyName)
	{
		return $this->getPredicateBuilder()->isNull($propertyName);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::isNotNull
	 * @param string|Property $propertyName
	 * @return InterfacePredicate
	 */
	public function isNotNull($propertyName)
	{
		return $this->getPredicateBuilder()->isNotNull($propertyName);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::published
	 * @param \DateTime $at
	 * @param \DateTime $to
	 * @throws \RuntimeException
	 * @return InterfacePredicate
	 */
	public function published($at = null, $to = null)
	{
		return $this->getPredicateBuilder()->published($at, $to);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::notPublished
	 * @param \DateTime $at
	 * @param \DateTime $to
	 * @throws \RuntimeException
	 * @return InterfacePredicate
	 */
	public function notPublished($at = null, $to = null)
	{
		return $this->getPredicateBuilder()->notPublished($at, $to);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::activated
	 * @param \DateTime $at
	 * @param \DateTime $to
	 * @throws \RuntimeException
	 * @return InterfacePredicate
	 */
	public function activated($at = null, $to = null)
	{
		return $this->getPredicateBuilder()->activated($at, $to);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::notActivated
	 * @param \DateTime $at
	 * @param \DateTime $to
	 * @throws \RuntimeException
	 * @return InterfacePredicate
	 */
	public function notActivated($at = null, $to = null)
	{
		return $this->getPredicateBuilder()->notActivated($at, $to);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::childOf
	 * @param TreeNode|AbstractDocument|integer $node
	 * @param string|Property $propertyName
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 * @throws \InvalidArgumentException
	 */
	public function childOf($node, $propertyName = 'id')
	{
		return $this->getPredicateBuilder()->childOf($node, $propertyName);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::descendantOf
	 * @param TreeNode|AbstractDocument|integer $node
	 * @param string|Property $propertyName
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 * @throws \InvalidArgumentException
	 */
	public function descendantOf($node, $propertyName = 'id')
	{
		return $this->getPredicateBuilder()->descendantOf($node, $propertyName);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::ancestorOf
	 * @param TreeNode|AbstractDocument|integer $node
	 * @param string|Property $propertyName
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 * @throws \InvalidArgumentException
	 */
	public function ancestorOf($node, $propertyName = 'id')
	{
		return $this->getPredicateBuilder()->ancestorOf($node, $propertyName);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::ancestorOf
	 * @param TreeNode|AbstractDocument|integer $node
	 * @param string|Property $propertyName
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 * @throws \InvalidArgumentException
	 */
	public function nextSiblingOf($node, $propertyName = 'id')
	{
		return $this->getPredicateBuilder()->nextSiblingOf($node, $propertyName);
	}

	/**
	 * @see \Change\Documents\Query\PredicateBuilder::previousSiblingOf
	 * @param TreeNode|AbstractDocument|integer $node
	 * @param string|Property $propertyName
	 * @return \Change\Db\Query\Predicates\BinaryPredicate
	 * @throws \InvalidArgumentException
	 */
	public function previousSiblingOf($node, $propertyName = 'id')
	{
		return $this->getPredicateBuilder()->previousSiblingOf($node, $propertyName);
	}
}