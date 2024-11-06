<?php
/**
 * Pipeline class file.
 *
 * @package Mantle
 */

namespace Mantle\Support;

use Closure;
use Mantle\Contracts\Container;
use Mantle\Contracts\Pipeline as PipelineContract;
use Mantle\Support\Traits\Makeable;
use RuntimeException;
use Throwable;

/**
 * Middleware Pipeline
 */
class Pipeline implements PipelineContract {
	use Makeable;

	/**
	 * The object being passed through the pipeline.
	 *
	 * @var mixed
	 */
	protected $passable;

	/**
	 * The array of class pipes.
	 *
	 * @var array
	 */
	protected $pipes = [];

	/**
	 * The method to call on each pipe.
	 *
	 * @var string
	 */
	protected $method = 'handle';

	/**
	 * Create a new class instance.
	 *
	 * @param Container|null $container Container instance.
	 */
	public function __construct( protected ?Container $container = null ) {
	}

	/**
	 * Set the object being sent through the pipeline.
	 *
	 * @param mixed $passable Data to send through the pipeline.
	 * @return static
	 */
	public function send( $passable ) {
		$this->passable = $passable;

		return $this;
	}

	/**
	 * Set the array of pipes.
	 *
	 * @param  array<callable>|null $pipes
	 * @return static
	 */
	public function through( $pipes ) {
		$this->pipes = is_array( $pipes ) ? $pipes : func_get_args();

		return $this;
	}

	/**
	 * Set the method to call on the pipes.
	 *
	 * @param  string $method
	 * @return static
	 */
	public function via( $method ) {
		$this->method = $method;

		return $this;
	}

	/**
	 * Run the pipeline with a final destination callback.
	 *
	 * @param  \Closure $destination
	 * @return mixed
	 */
	public function then( Closure $destination ) {
		$pipeline = array_reduce(
			array_reverse( $this->pipes() ),
			$this->carry(),
			$this->prepare_destination( $destination )
		);

		return $pipeline( $this->passable );
	}

	/**
	 * Run the pipeline and return the result.
	 *
	 * @return mixed
	 */
	public function thenReturn() {
		return $this->then( fn ( $passable ) => $passable );
	}

	/**
	 * Get the final piece of the Closure onion.
	 *
	 * @param  \Closure $destination
	 * @return \Closure
	 */
	protected function prepare_destination( Closure $destination ) {
		return function ( $passable ) use ( $destination ) {
			try {
				return $destination( $passable );
			} catch ( Throwable $e ) {
				return $this->handle_exception( $passable, $e );
			}
		};
	}

	/**
	 * Get a Closure that represents a slice of the application onion.
	 *
	 * @return \Closure
	 */
	protected function carry() {
		return fn ( $stack, $pipe ) => function ( $passable ) use ( $stack, $pipe ) {
			try {
				if ( is_callable( $pipe ) ) {
					// If the pipe is a callable, then we will call it directly, but otherwise we
					// will resolve the pipes out of the dependency container and call it with
					// the appropriate method and arguments, returning the results back out.
					return $pipe( $passable, $stack );
				} elseif ( ! is_object( $pipe ) ) {
					[ $name, $parameters] = $this->parse_pipe_string( $pipe );

					// If the pipe is a string we will parse the string and resolve the class out
					// of the dependency injection container. We can then build a callable and
					// execute the pipe function giving in the parameters that are required.
					$pipe = $this->get_container()->make( $name );

					$parameters = array_merge( [ $passable, $stack ], $parameters );
				} else {
					// If the pipe is already an object we'll just make a callable and pass it to
					// the pipe as-is. There is no need to do any extra parsing and formatting
					// since the object we're given was already a fully instantiated object.
					$parameters = [ $passable, $stack ];
				}

				$carry = method_exists( $pipe, $this->method )
					? $pipe->{$this->method}( ...$parameters )
					: $pipe( ...$parameters );

				return $this->handle_carry( $carry );
			} catch ( Throwable $e ) {
				return $this->handle_exception( $passable, $e );
			}
		}; // phpcs:ignore Generic.CodeAnalysis.EmptyPHPStatement.SemicolonWithoutCodeDetected
	}

	/**
	 * Parse full pipe string to get name and parameters.
	 *
	 * @param  string $pipe
	 * @return array
	 */
	protected function parse_pipe_string( $pipe ) {
		[$name, $parameters] = array_pad( explode( ':', $pipe, 2 ), 2, [] );

		if ( is_string( $parameters ) ) {
			$parameters = explode( ',', $parameters );
		}

		return [ $name, $parameters ];
	}

	/**
	 * Get the array of configured pipes.
	 *
	 * @return array
	 */
	protected function pipes() {
		return $this->pipes;
	}

	/**
	 * Get the container instance.
	 *
	 * @return Container
	 * @throws RuntimeException Thrown on missing container instance.
	 */
	protected function get_container() {
		if ( ! isset( $this->container ) ) {
			throw new RuntimeException( 'A container instance has not been passed to the Pipeline.' );
		}

		return $this->container;
	}

	/**
	 * Handle the value returned from each pipe before passing it to the next.
	 *
	 * @param  mixed $carry
	 * @return mixed
	 */
	protected function handle_carry( $carry ) {
		return $carry;
	}

	/**
	 * Handle the given exception.
	 *
	 * @param mixed     $passable Passable object.
	 * @param Throwable $e Exception thrown.
	 *
	 * @throws Throwable Thrown when an exception is passed.
	 */
	protected function handle_exception( $passable, Throwable $e ): never {
		throw $e;
	}
}
