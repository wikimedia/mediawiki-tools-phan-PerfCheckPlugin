<?php declare( strict_types=1 );

use ast\Node;
use Phan\AST\ASTHasher;
use Phan\AST\ContextNode;
use Phan\Config;
use Phan\Issue;
use Phan\Language\Element\FunctionInterface;
use Phan\Language\Type\LiteralStringType;
use Phan\PluginV3\PluginAwarePostAnalysisVisitor;

/**
 * Main class where the code is analyzed
 * @suppress PhanUnreferencedClass
 */
class PerformanceVisitor extends PluginAwarePostAnalysisVisitor {
	/**
	 * All issues which we currently emit.
	 */
	private const ISSUES_MAP = [
		'PerformanceCheckArrayMap' => [
			'msg' => 'Array_map with a lambda is slow. Consider replacing with a foreach ' .
				'loop, a string callable, or save the closure in a variable.',
			'severity' => Issue::SEVERITY_NORMAL
		],
		'PerformanceCheckQueryLoop' => [
			'msg' => 'Database queries within loops are slow. Consider using JOINS and/or ' .
				'other SQL constructs.',
			'severity' => Issue::SEVERITY_NORMAL
		],
		'PerformancheCheckLoopFunction' => [
			'msg' => 'Calling a function in the loop condition is slow.',
			'severity' => Issue::SEVERITY_NORMAL
		],
		'PerformanceCheckLiteralRegex' => [
			'msg' => 'Calling a regexp function on a literal value. Consider using strpos or str_replace.',
			'severity' => Issue::SEVERITY_NORMAL
		],
		'PerformanceCheckSwitchableElseif' => [
			'msg' => 'Long elseifs are slow. Consider replacing with a switch.',
			'severity' => Issue::SEVERITY_NORMAL
		],
		'PerformanceCheckStrtr' => [
			'msg' => 'Dividing an array for str_replace is slow. Consider using strtr with the whole array.',
			'severity' => Issue::SEVERITY_NORMAL
		]
	];
	/**
	 * @var Node[]
	 * @suppress PhanReadOnlyProtectedProperty
	 * This is a magic property. Although not inherited, phan checks for a property with this
	 * exact name to determine whether to pass in a list of parent nodes.
	 */
	protected $parent_node_list;

	/**
	 * Shorthand to get the context node for the given node
	 * @author Bawolff
	 * @param Node $node
	 * @return ContextNode
	 */
	protected function getCtxN( Node $node ) {
		return new ContextNode(
			$this->code_base,
			$this->context,
			$node
		);
	}

	/**
	 * @inheritDoc
	 */
	public function visitMethodCall( Node $node ) : void {
		try {
			$method = $this->getCtxN( $node )->getMethod( $node->children['method'], false );
		} catch ( \Phan\Exception\NodeException $_ ) {
			// Something complicated, but don't care.
			return;
		}

		if ( in_array( (string)$method->getFQSEN(), $this->getDBMethods() ) ) {
			// @todo Improve the check
			$this->handleDBMethod();
		}
		$this->checkLoopPassByRef( $node, $method );
	}

	/**
	 * Get a list of DB select methods
	 * @return array
	 */
	protected function getDBMethods() : array {
		static $dbMethods = [];

		if ( $dbMethods ) {
			return $dbMethods;
		}

		$prefixes = [
			'\Wikimedia\Rdbms\IDatabase',
			'\Wikimedia\Rdbms\Database',
		];
		$dbFuncs = [
			'select',
			'selectField',
			'selectFieldValues',
			'selectRow',
		];

		foreach ( $prefixes as $prefix ) {
			foreach ( $dbFuncs as $func ) {
				$dbMethods[] = "$prefix::$func";
			}
		}
		return $dbMethods;
	}

	/**
	 * Handles a call to select methods, to prevent queries within loops.
	 */
	protected function handleDBMethod() : void {
		if ( strpos( $this->context->getFile(), 'maintenance/' ) !== false ) {
			// Queries inside loops are common in this case.
			return;
		}

		// While and dowhile are usually used in acceptable cases, e.g. for batching or see
		// jobqueue. So cut down on false positives.
		$loopKinds = [
			// \ast\AST_DO_WHILE,
			// \ast\AST_WHILE,
			\ast\AST_FOR,
			\ast\AST_FOREACH
		];
		foreach ( $this->parent_node_list as $node ) {
			if ( in_array( $node->kind, $loopKinds ) ) {
				$this->emitPerformanceIssue( 'PerformanceCheckQueryLoop' );
			}
		}
	}

	/**
	 * Shorthand to emit one of our issues
	 * @param string $name
	 */
	protected function emitPerformanceIssue( string $name ) : void {
		$shouldEcho = Config::getValue( 'plugin_config' )['perf_check_echo'] ?? false;
		if ( $shouldEcho ) {
			// Hack for Wikimedia CI
			printf(
				"PerformanceCheck - %s in %s at line %d: %s\n",
				$name,
				$this->context->getFile(),
				$this->context->getLineNumberStart(),
				self::ISSUES_MAP[ $name ]['msg']
			);
		} else {
			$this->emit(
				$name,
				self::ISSUES_MAP[ $name ]['msg'],
				[],
				self::ISSUES_MAP[ $name ]['severity']
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function visitCall( Node $node ) : void {
		if ( $node->children['expr']->kind !== \ast\AST_NAME ) {
			return;
		}

		$func = $this->getCtxN( $node )->getFunction( $node->children['expr']->children['name'] );
		$fqsen = (string)$func->getFQSEN();

		$regexFuncs = [ '\preg_match', '\preg_match_all', '\preg_replace' ];

		if ( $fqsen === '\array_map' ) {
			$this->handleArrayMap( $node );
		} elseif ( $fqsen === '\str_replace' ) {
			$this->handleStrReplace( $node );
		} elseif ( in_array( $fqsen, $regexFuncs ) ) {
			$this->handleRegexFunc( $node );
		}
		$this->checkLoopPassByRef( $node, $func );
	}

	/**
	 * Check calls to array_map and ensure it's not used with anonymous closures.
	 * Closures passed in as variables are acceptably fast, and making the closure
	 * static doesn't seem to help.
	 * @param Node $node
	 */
	protected function handleArrayMap( Node $node ) : void {
		$cb = $node->children['args']->children[0];

		if ( !( $cb instanceof Node ) ) {
			return;
		}

		if ( $cb->kind === \ast\AST_CLOSURE ) {
			$this->emitPerformanceIssue( 'PerformanceCheckArrayMap' );
		}
	}

	/**
	 * Check a call to str_replace and see if it can be replaced with strtr
	 * @param Node $node
	 */
	protected function handleStrReplace( Node $node ) : void {
		$args = $node->children['args']->children;
		if ( $args[0] instanceof Node && $args[0]->kind === \ast\AST_CALL ) {
			// First argument. We're looking for array_keys(something)
			if ( $args[0]->children['expr']->kind !== \ast\AST_NAME ) {
				return;
			}

			$func = $this->getCtxN( $args[0] )->getFunction( $args[0]->children['expr']->children['name'] );
			$fqsen = (string)$func->getFQSEN();
			if ( $fqsen !== '\array_keys' ) {
				return;
			}

			// This is the argument to array_keys
			$array = $args[0]->children['args']->children[0];

			if ( $args[1] instanceof Node && $args[1]->kind === \ast\AST_CALL ) {
				// Second argument. Here we look for array_values called on the same array
				if ( $args[1]->children['expr']->kind !== \ast\AST_NAME ) {
					return;
				}

				$func = $this->getCtxN( $args[1] )->getFunction( $args[1]->children['expr']->children['name'] );
				$fqsen = (string)$func->getFQSEN();
				if ( $fqsen === '\array_values' ) {
					$array2 = $args[1]->children['args']->children[0];

					if ( ASTHasher::hash( $array ) === ASTHasher::hash( $array2 ) ) {
						$this->emitPerformanceIssue( 'PerformanceCheckStrtr' );
					}
				}
			}
		}
	}

	/**
	 * Check a regex function to see if it's actually called with a plain text needle
	 * @param Node $node
	 */
	protected function handleRegexFunc( Node $node ) : void {
		$pattern = $node->children['args']->children[0];
		if ( $pattern instanceof Node && $pattern->kind === \ast\AST_VAR ) {
			$var = $this->getCtxN( $pattern )->getVariable();
			$types = $var->getUnionType()->getTypeSet();
			// We must ensure that 'literal' is the *only* possible type.
			if ( count( $types ) === 1 && $types[0] instanceof LiteralStringType ) {
				$pattern = $types[0]->getValue();
			}
		}
		if ( !is_string( $pattern ) || !strlen( $pattern ) ) {
			// Either a non-var node, or a non-string literal. Ignore anyway.
			return;
		}

		$delimiter = $pattern[0];
		if ( substr( $pattern, -1 ) !== $delimiter ) {
			// A preg modifier. Play it safe and go away.
			// @todo There's still something we can do. For instance, if there's only an 'i' modifier
			// we can suggest case insensitive funcs.
			// NOTE: Watch out for the $matches parameter then! E.g., preg_match( '!bla!i',$str, $m )
			// used to get the exact form of "bla" (which could be "BLA", "Bla", etc.)
			return;
		}

		$pattern = substr( $pattern, 1, strrpos( $pattern, $delimiter ) - 1 );
		// Taken from https://www.php.net/manual/en/function.preg-quote.php minus !, = and :
		// The backslash is separated because we're only interested in its uses outside of escaping
		$specialChars = '([.+*?\[^\\]$(){}<>|\\-#]|\\\\[a-z0-9])';
		// The ASCII art below means: a special char, not preceded by an odd amount of backslashes
		$regexRegex = '/(?<!\\\\)(\\\\\\\\)*' . $specialChars . '/i';
		if ( !preg_match( $regexRegex, $pattern ) ) {
			$this->emitPerformanceIssue( 'PerformanceCheckLiteralRegex' );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function visitFor( Node $node ) : void {
		$this->handleLoop( $node );
	}

	/**
	 * @inheritDoc
	 */
	public function visitDoWhile( Node $node ) : void {
		$this->handleLoop( $node );
	}

	/**
	 * @inheritDoc
	 */
	public function visitWhile( Node $node ) : void {
		$this->handleLoop( $node );
	}

	/**
	 * Handles for, while and dowhile to find repeated function calls in the loop condition.
	 * @param Node $node
	 */
	protected function handleLoop( Node $node ) : void {
		// @todo Use a broader criterion, e.g. if the function is native (ideally, this should check
		// whether the function is pure, i.e. it doesn't change its outer state)
		$blacklist = [
			'\count',
			'\sizeof',
			'\strlen'
		];
		$conds = $node->children['cond'];

		if ( !( $conds instanceof Node ) ) {
			return;
		}

		$conds = $conds->kind === \ast\AST_EXPR_LIST ? $conds->children : [ $conds ];
		foreach ( $conds as $cond ) {
			$left = $cond->children['left'] ?? null;
			$right = $cond->children['right'] ?? null;
			$funcNode = null;
			// If no left/right, chances are this is a function call on a variable, which
			// then will probably be modified inside the loop
			if ( $left instanceof Node && $left->kind === \ast\AST_CALL ) {
				$funcNode = $left;
			} elseif ( $right instanceof Node && $right->kind === \ast\AST_CALL ) {
				$funcNode = $right;
			}

			if ( !( $funcNode instanceof Node ) ) {
				return;
			}
			$funcName = $funcNode->children['expr']->children['name'];
			$func = $this->getCtxN( $funcNode )->getFunction( $funcName );

			if ( in_array( (string)$func->getFQSEN(), $blacklist ) ) {
				$args = $funcNode->children['args']->children;
				assert( count( $args ) >= 1 );
				if ( !( $args[0] instanceof Node ) || (
						$args[0]->kind === \ast\AST_VAR &&
						// @phan-suppress-next-line PhanUndeclaredProperty
						!in_array( ASTHasher::hash( $args[0] ), $node->modifiedVars ?? [] )
					)
				) {
					// Only emit for literals, and variables which don't change within the loop
					$this->emitPerformanceIssue( 'PerformancheCheckLoopFunction' );
				}
			}
		}
	}

	/**
	 * Check long chains of elseifs to see if they can be converted to switch
	 * @inheritDoc
	 */
	public function visitIf( Node $node ) : void {
		if ( \Phan\Config::getValue( 'simplify_ast' ) ) {
			// Too much stuff to dig through
			return;
		}
		if ( count( $node->children ) < 4 ) {
			return;
		}

		// For the sake of readability, we only consider an elseif convertible to a switch
		// if the same LHS is being compared in all cases, with the same operator.
		// Plus, the operator must not be a strict one (=== or !==), as switch uses loose comparison.
		$lhs = $op = null;
		foreach ( $node->children as $if ) {
			$cond = $if->children['cond'];
			if ( $cond === null ) {
				// Should only happen for the 'else'
				continue;
			}

			$excludeOps = [
				\ast\flags\BINARY_IS_IDENTICAL,
				\ast\flags\BINARY_IS_NOT_IDENTICAL,
			];

			if ( !isset( $cond->children['left'] ) ||
				$cond->kind !== \ast\AST_BINARY_OP ||
				in_array( $cond->flags, $excludeOps ) ) {
				return;
			}
			if ( $op === null ) {
				$op = $cond->kind;
				$lhs = ASTHasher::hash( $cond->children['left'] );
			} elseif ( ASTHasher::hash( $cond->children['left'] ) !== $lhs || $cond->kind !== $op ) {
				return;
			}
		}
		if ( $lhs !== null ) {
			$this->emitPerformanceIssue( 'PerformanceCheckSwitchableElseif' );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function visitAssignOp( Node $node ) {
		$varNode = $node->children['var'];
		if ( $varNode->kind === \ast\AST_DIM ) {
			$varNode = $varNode->children['expr'];
		}
		$this->checkMarkLoopVarModified( $varNode );
	}

	/**
	 * @inheritDoc
	 */
	public function visitAssign( Node $node ) {
		$varNode = $node->children['var'];
		if ( $varNode->kind === \ast\AST_DIM ) {
			$varNode = $varNode->children['expr'];
		}
		$this->checkMarkLoopVarModified( $varNode );
	}

	/**
	 * @inheritDoc
	 */
	public function visitUnset( Node $node ) {
		$varNode = $node->children['var'];
		if ( $varNode->kind === \ast\AST_DIM ) {
			$varNode = $varNode->children['expr'];
		}
		$this->checkMarkLoopVarModified( $varNode );
	}

	/**
	 * Search for pass by ref in the parameters of $func
	 * @param Node $node
	 * @param FunctionInterface $func
	 */
	protected function checkLoopPassByRef( Node $node, FunctionInterface $func ) {
		$args = $node->children['args']->children;
		foreach ( $args as $i => $arg ) {
			if ( !( $arg instanceof Node && $arg->kind === \ast\AST_VAR ) ) {
				continue;
			}
			$param = $func->getParameterForCaller( $i );
			if ( $param && $param->isPassByReference() ) {
				$this->checkMarkLoopVarModified( $arg );
			}
		}
	}

	/**
	 * Given a node representing a variable, if we're inside a loop, write in the loop node that
	 * this variable will be modified. This is used to emit PerformancheCheckLoopFunction, and
	 * possibly other in the future.
	 * @todo UNLESS we find other use cases: in preorder, save the conds var, and run this function
	 * only for that var.
	 * @param Node $varNode
	 * @suppress PhanUndeclaredProperty
	 */
	protected function checkMarkLoopVarModified( Node $varNode ) {
		$varHash = ASTHasher::hash( $varNode );

		$loopKinds = [
			\ast\AST_DO_WHILE,
			\ast\AST_WHILE,
			\ast\AST_FOR,
			\ast\AST_FOREACH
		];
		foreach ( $this->parent_node_list as $pnode ) {
			if ( in_array( $pnode->kind, $loopKinds ) ) {
				// Not the cleanest way to do this.
				if ( !property_exists( $pnode, 'modifiedVars' ) ) {
					$pnode->modifiedVars = [];
				}
				$pnode->modifiedVars[] = $varHash;
			}
		}
	}
}
