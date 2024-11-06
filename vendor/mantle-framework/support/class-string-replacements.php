<?php
/**
 * String_Replacements class file
 *
 * @package Mantle
 */

namespace Mantle\Support;

/**
 * Collects pairs of strings to search and replace.
 */
class String_Replacements {
	/**
	 * Collected strings to search for.
	 *
	 * @var array
	 */
	protected $search = [];

	/**
	 * Collected strings to replace found search values.
	 *
	 * @var array
	 */
	protected $replace = [];

	/**
	 * Number of search-replace pairs collected.
	 *
	 * @var int
	 */
	protected $length = 0;

	/**
	 * Whether only individual strings have been added and thus can be passed to
	 * \str_replace() as arrays of strings.
	 *
	 * @var bool
	 */
	protected $only_strings = true;

	/**
	 * Add a search/replace pair.
	 *
	 * @param string|string[] $search  The value or values being searched for.
	 * @param string|string[] $replace The value or values that replaces found $search values.
	 */
	public function add( $search, $replace ): void {
		// Allow passing the results of expressions that might not generate different values.
		if ( $search === $replace ) {
			return;
		}

		if ( $this->only_strings && ( ! \is_string( $search ) || ! \is_string( $replace ) ) ) {
			$this->only_strings = false;
		}

		$this->search[]  = $search;
		$this->replace[] = $replace;
		$this->length++;
	}

	/**
	 * Apply the search/replace pairs to a subject using \str_replace().
	 *
	 * @param string|string[] $subject String or strings to alter.
	 * @return string|string[] Altered string or strings.
	 */
	public function replace( $subject ) {
		return $this->apply( $subject, '\str_replace' );
	}

	/**
	 * Apply the search/replace pairs to a subject using \str_ireplace().
	 *
	 * @param string|string[] $subject String or strings to alter.
	 * @return string|string[] Altered string or strings.
	 */
	public function ireplace( $subject ) {
		return $this->apply( $subject, '\str_ireplace' );
	}

	/**
	 * Wrapper to apply the \str_*() function to the subject.
	 *
	 * @param string|string[] $subject  Subject.
	 * @param callable        $callable \str_replace() or \str_ireplace().
	 * @return string|string[] Altered string or strings.
	 */
	private function apply( $subject, callable $callable ) {
		if ( ! $this->length ) {
			return $subject;
		}

		if ( $this->only_strings ) {
			return $callable( $this->search, $this->replace, $subject );
		}

		for ( $i = 0; $i < $this->length; $i++ ) {
			$subject = $callable( $this->search[ $i ], $this->replace[ $i ], $subject );
		}

		return $subject;
	}
}
