<?php
namespace Change\Db\Query\Predicates;

use Change\Db\Query\Expressions\Func;

use Change\Db\Query\Expressions\ExpressionList;

use Change\Db\Query\Expressions\String;

use Change\Db\Query\Expressions\AbstractExpression;
use Change\Db\Query\Expressions\BinaryOperation;
use Change\Db\Query\Expressions\Concat;

/**
 * @name \Change\Db\Query\Predicates\Like
 */
class Like extends BinaryPredicate
{
	const ANYWHERE = 0;
	const BEGIN = 1;
	const END = 2;
	const EXACT = 3;
	
	/**
	 * @var integer
	 */
	protected $matchMode;
	
	/**
	 * @var boolean
	 */
	protected $caseSensitive;

	/**
	 * @var string
	 */
	protected $wildCard = '%';
	
	/**
	 * @param AbstractExpression $lhs
	 * @param AbstractExpression $rhs
	 * @param integer $matchMode
	 * @param boolean $caseSensitive
	 */
	public function __construct(AbstractExpression $lhs = null, AbstractExpression $rhs = null, $matchMode = self::ANYWHERE, $caseSensitive = false)
	{
		parent::__construct($lhs, $rhs);
		$this->setMatchMode($matchMode);
		
		//This method update operator
		$this->setCaseSensitive($caseSensitive);
	}

	/**
	 * @param boolean $caseSensitive
	 */
	public function setCaseSensitive($caseSensitive)
	{
		$this->caseSensitive = ($caseSensitive == true);
		$this->setOperator(($this->caseSensitive) ? 'LIKE BINARY' : 'LIKE');
	}

	/**
	 * @return boolean
	 */
	public function getCaseSensitive()
	{
		return $this->caseSensitive;
	}

	/**
	 * @throws \InvalidArgumentException
	 * @param number $matchMode
	 */
	public function setMatchMode($matchMode)
	{
		switch ($matchMode)
		{
			case self::ANYWHERE:
			case self::BEGIN:
			case self::END:
			case self::EXACT:
				$this->matchMode = $matchMode;
				return;
		}
		throw new \InvalidArgumentException('Argument 1 must be a valid const');
	}

	/**
	 * @return number
	 */
	public function getMatchMode()
	{
		return $this->matchMode;
	}

	/**
	 * @param string $wildCard
	 */
	public function setWildCard($wildCard)
	{
		$this->wildCard = $wildCard;
	}

	/**
	 * @return string
	 */
	public function getWildCard()
	{
		return $this->wildCard;
	}

	public function getCompletedRightHandExpression()
	{
		$arguments = array($this->getRightHandExpression());
		switch ($this->matchMode)
		{
			case self::BEGIN :
				array_push($arguments, new String($this->wildCard));
				break;
			case self::END :
				array_unshift($arguments, new String($this->wildCard));
				break;
			case self::ANYWHERE :
				array_push($arguments, new String($this->wildCard));
				array_unshift($arguments, new String($this->wildCard));
				break;
			default:
				return $this->getRightHandExpression();
		}
		return new Concat($arguments);
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		$rhe = $this->getCompletedRightHandExpression();
		return $this->getLeftHandExpression()->toSQL92String() . ' ' . $this->getOperator() . ' ' . $rhe->toSQL92String();
	}
}