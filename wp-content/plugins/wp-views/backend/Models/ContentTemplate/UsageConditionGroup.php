<?php

namespace OTGS\Toolset\Views\Models\ContentTemplate;

/**
 * Representation of a group of usage conditions, it usually belongs to a Usage Setting or to a parent group.
 */
class UsageConditionGroup {

	const KEY_CONDITIONS = 'conditions';
	const KEY_PARSED_CONDITIONS = 'parsed_conditions';
	const KEY_NEGATIVE = 'negative';
	const KEY_OPERATOR = 'operator';
	const OPERATOR_AND = 'AND';
	const OPERATOR_OR = 'OR';

	/** @var UsageCondition[] */
	private $conditions;

	/** @var string */
	private $parsed_conditions;

	/** @var string */
	private $operator;

	/** @var bool */
	private $negative;

	/** @var UsageConditionGroup[] */
	private $groups;

	/**
	 * @param string $operator
	 * @param UsageCondition[] $conditions
	 * @param string $parsed_conditions
	 * @param UsageConditionGroup[] $groups
	 * @param bool $negative
	 */
	public function __construct( $operator, $conditions, $parsed_conditions, $groups = [], $negative = false ) {
		$this->operator = $operator;
		$this->conditions = $conditions;
		$this->parsed_conditions = $parsed_conditions;
		$this->groups = $groups;
		$this->negative = $negative;
	}

	/**
	 * Counts the amount of condition that matches a given post. Returns 0 if no condition matches.
	 *
	 * @param \WP_Post $post
	 *
	 * @return int|mixed
	 */
	public function countConditionMatches( \WP_Post $post ) {
		$matchesGroup = $this->isOperatorAND(); // If AND, default TRUE, if OR, default false
		$matchedConditions = 0;

		foreach ( $this->conditions as $condition ) {
			if ( $condition->appliesTo( $post ) ) {
				$matchedConditions++;
				$matchesGroup = $this->isOperatorAND() ? $matchesGroup : true;
			} else {
				$matchesGroup = $this->isOperatorAND() ? false  : $matchesGroup;
			}
		}

		foreach ( $this->groups as $group ) {
			$groupMatchedConditions = $group->countConditionMatches( $post );
			$matchedConditions += $groupMatchedConditions;
			if ( $groupMatchedConditions > 0 ) {
				$matchesGroup = $this->isOperatorAND() ? $matchesGroup  : true;
			} else {
				$matchesGroup = $this->isOperatorAND() ? false : $matchesGroup;
			}
		}
		if ( $this->negative ) {
			$matchesGroup = ! $matchesGroup;

			if ( $matchesGroup ) {
				$matchedConditions = 1;
			}
		}

		return $matchesGroup ? $matchedConditions : 0;
	}

	/**
	 * @return bool
	 */
	public function isOperatorAND() {
		return $this->operator == self::OPERATOR_AND;
	}

	/**
	 * @return string
	 */
	public function toString() {
		$conditions = [];
		foreach ( $this->conditions as $condition ) {
			$conditions[] = $condition->toString();
		}
		foreach ( $this->groups as $group ) {
			$conditions[] = $group->toString();
		}
		$conditionsText = implode(
			sprintf( ' <small>%s</small> ', $this->operator ),
			$conditions
		);
		if ( $this->negative ) {
			return sprintf( __( 'NOT ( %s )', 'wpv-views' ), $conditionsText );
		}
		return $conditionsText;
	}

	/**
	 * @param array $usage_condition_group_array - An array coming from the database setting storage
	 *
	 * @return UsageConditionGroup|null
	 */
	public static function createFromDatabaseArray( $usage_condition_group_array ) {
		if (
			! is_array( $usage_condition_group_array )
			|| ! isset( $usage_condition_group_array[ self::KEY_OPERATOR ] )
			|| ! isset( $usage_condition_group_array[ self::KEY_CONDITIONS ] )
		) {
			return null;
		}

		$sub_groups = [];
		$conditions = [];
		$usage_conditions_array = $usage_condition_group_array[ self::KEY_CONDITIONS ] ?: [];
		foreach ( $usage_conditions_array as $key => $usage_condition_array ) {
			if ( isset( $usage_condition_array[ self::KEY_NEGATIVE ] ) ) {
				$sub_groups[] = static::createFromDatabaseArray( $usage_condition_array );
				unset( $usage_condition_group_array[ $key ] );
			} else {
				$conditions[] = UsageCondition::createFromDatabaseArray( $usage_condition_array );
			}
		}

		return new UsageConditionGroup(
			toolset_getarr( $usage_condition_group_array, self::KEY_OPERATOR, '' ),
			$conditions,
			toolset_getarr( $usage_condition_group_array, self::KEY_PARSED_CONDITIONS, '' ),
			$sub_groups,
			toolset_getarr( $usage_condition_group_array, self::KEY_NEGATIVE, false )
		);
	}

}
