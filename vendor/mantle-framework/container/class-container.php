<?php
/**
 * Container class file.
 *
 * @package Mantle
 */

namespace Mantle\Container;

use ArrayAccess;
use Closure;
use Exception;
use LogicException;
use Mantle\Support\Reflector;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag

/**
 * Service Container
 */
class Container implements ArrayAccess, \Mantle\Contracts\Container {
	/**
	 * The current globally available container (if any).
	 */
	protected static ?\Mantle\Contracts\Container $instance = null;

	/**
	 * An array of the types that have been resolved.
	 *
	 * @var bool[]
	 */
	protected $resolved = [];

	/**
	 * The container's bindings.
	 *
	 * @var array[]
	 */
	protected $bindings = [];

	/**
	 * The container's method bindings.
	 *
	 * @var \Closure[]
	 */
	protected $method_bindings = [];

	/**
	 * The container's shared instances.
	 *
	 * @var object[]
	 */
	protected $instances = [];

	/**
	 * The registered type aliases.
	 *
	 * @var string[]
	 */
	protected $aliases = [];

	/**
	 * The registered aliases keyed by the abstract name.
	 *
	 * @var array[]
	 */
	protected $abstract_aliases = [];

	/**
	 * The extension closures for services.
	 *
	 * @var array[]
	 */
	protected $extenders = [];

	/**
	 * All of the registered tags.
	 *
	 * @var array[]
	 */
	protected $tags = [];

	/**
	 * The stack of concretions currently being built.
	 *
	 * @var array
	 */
	protected $build_stack = [];

	/**
	 * The parameter override stack.
	 *
	 * @var array[]
	 */
	protected $with = [];

	/**
	 * The contextual binding map.
	 *
	 * @var array[]
	 */
	public $contextual = [];

	/**
	 * All of the registered rebound callbacks.
	 *
	 * @var array[]
	 */
	protected $rebound_callbacks = [];

	/**
	 * All of the global resolving callbacks.
	 *
	 * @var \Closure[]
	 */
	protected $global_resolving_callbacks = [];

	/**
	 * All of the global after resolving callbacks.
	 *
	 * @var \Closure[]
	 */
	protected $global_after_resolving_callbacks = [];

	/**
	 * All of the resolving callbacks by class type.
	 *
	 * @var array[]
	 */
	protected $resolving_callbacks = [];

	/**
	 * All of the after resolving callbacks by class type.
	 *
	 * @var array[]
	 */
	protected $after_resolving_callbacks = [];

	/**
	 * Determine if the given abstract type has been bound.
	 *
	 * @param string $abstract Abstract name.
	 */
	public function bound( $abstract ): bool {
		return isset( $this->bindings[ $abstract ] ) ||
			isset( $this->instances[ $abstract ] ) ||
			$this->is_alias( $abstract );
	}

	/**
	 * {@inheritdoc}
	 */
	public function has( string $id ): bool {
		return $this->bound( $id );
	}

	/**
	 * Determine if the given abstract type has been resolved.
	 *
	 * @param string $abstract Abstract name.
	 */
	public function resolved( $abstract ): bool {
		if ( $this->is_alias( $abstract ) ) {
			$abstract = $this->get_alias( $abstract );
		}

		return isset( $this->resolved[ $abstract ] ) ||
			isset( $this->instances[ $abstract ] );
	}

	/**
	 * Determine if a given type is shared.
	 *
	 * @param string $abstract Abstract name.
	 */
	public function is_shared( $abstract ): bool {
		return isset( $this->instances[ $abstract ] ) ||
			( isset( $this->bindings[ $abstract ]['shared'] ) &&
			true === $this->bindings[ $abstract ]['shared'] );
	}

	/**
	 * Determine if a given string is an alias.
	 *
	 * @param  string $name Alias name.
	 * @return bool
	 */
	public function is_alias( $name ) {
		return isset( $this->aliases[ $name ] );
	}

	/**
	 * Register a binding with the container.
	 *
	 * @param  string               $abstract
	 * @param  \Closure|string|null $concrete
	 * @param  bool                 $shared
	 */
	public function bind( $abstract, $concrete = null, $shared = false ): void {
		$this->drop_stale_instances( $abstract );

		// If no concrete type was given, we will simply set the concrete type to the
		// abstract type. After that, the concrete type to be registered as shared
		// without being forced to state their classes in both of the parameters.
		if ( is_null( $concrete ) ) {
			$concrete = $abstract;
		}

		// If the factory is not a Closure, it means it is just a class name which is
		// bound into this container to the abstract type and we will just wrap it
		// up inside its own Closure to give us more convenience when extending.
		if ( ! $concrete instanceof Closure ) {
			$concrete = $this->get_closure( $abstract, $concrete );
		}

		$this->bindings[ $abstract ] = compact( 'concrete', 'shared' );

		// If the abstract type was already resolved in this container we'll fire the
		// rebound listener so that any objects which have already gotten resolved
		// can have their copy of the object updated via the listener callbacks.
		if ( $this->resolved( $abstract ) ) {
			$this->rebound( $abstract );
		}
	}

	/**
	 * Get the Closure to be used when building a type.
	 *
	 * @param  string $abstract
	 * @param  string $concrete
	 * @return \Closure
	 */
	protected function get_closure( $abstract, $concrete ) {
		return function ( $container, $parameters = [] ) use ( $abstract, $concrete ) {
			if ( $abstract === $concrete ) {
				return $container->build( $concrete );
			}

			return $container->resolve(
				$concrete,
				$parameters,
				$raise_events = false
			);
		};
	}

	/**
	 * Determine if the container has a method binding.
	 *
	 * @param string $method Method name.
	 * @return bool
	 */
	public function has_method_binding( $method ) {
		return isset( $this->method_bindings[ $method ] );
	}

	/**
	 * Bind a callback to resolve with Container::call.
	 *
	 * @param  array|string $method
	 * @param  \Closure     $callback
	 */
	public function bind_method( $method, $callback ): void {
		$this->method_bindings[ $this->parse_bind_method( $method ) ] = $callback;
	}

	/**
	 * Get the method to be bound in class@method format.
	 *
	 * @param  array|string $method
	 * @return string
	 */
	protected function parse_bind_method( $method ) {
		if ( is_array( $method ) ) {
			return $method[0] . '@' . $method[1];
		}

		return $method;
	}

	/**
	 * Get the method binding for the given method.
	 *
	 * @param  string $method
	 * @param  mixed  $instance
	 * @return mixed
	 */
	public function call_method_binding( $method, $instance ) {
		return call_user_func( $this->method_bindings[ $method ], $instance, $this );
	}

	/**
	 * Register a binding if it hasn't already been registered.
	 *
	 * @param  string               $abstract
	 * @param  \Closure|string|null $concrete
	 * @param  bool                 $shared
	 */
	public function bind_if( $abstract, $concrete = null, $shared = false ): void {
		if ( ! $this->bound( $abstract ) ) {
				$this->bind( $abstract, $concrete, $shared );
		}
	}

	/**
	 * Register a shared binding in the container.
	 *
	 * @param  string               $abstract
	 * @param  \Closure|string|null $concrete
	 */
	public function singleton( $abstract, $concrete = null ): void {
		$this->bind( $abstract, $concrete, true );
	}

	/**
	 * Register a shared binding if it hasn't already been registered.
	 *
	 * @param  string               $abstract
	 * @param  \Closure|string|null $concrete
	 */
	public function singleton_if( $abstract, $concrete = null ): void {
		if ( ! $this->bound( $abstract ) ) {
			$this->singleton( $abstract, $concrete );
		}
	}

	/**
	 * "Extend" an abstract type in the container.
	 *
	 * @param  string   $abstract
	 * @param  \Closure $closure
	 *
	 * @throws \InvalidArgumentException Thrown on invalid argument.
	 */
	public function extend( $abstract, Closure $closure ): void {
		$abstract = $this->get_alias( $abstract );

		if ( isset( $this->instances[ $abstract ] ) ) {
			$this->instances[ $abstract ] = $closure( $this->instances[ $abstract ], $this );

			$this->rebound( $abstract );
		} else {
			$this->extenders[ $abstract ][] = $closure;

			if ( $this->resolved( $abstract ) ) {
				$this->rebound( $abstract );
			}
		}
	}

	/**
	 * Register an existing instance as shared in the container.
	 *
	 * @param  string $abstract
	 * @param  mixed  $instance
	 * @return mixed
	 */
	public function instance( $abstract, $instance ) {
		$this->remove_abstract_alias( $abstract );

		$is_bound = $this->bound( $abstract );

		unset( $this->aliases[ $abstract ] );

		// We'll check to determine if this type has been bound before, and if it has
		// we will fire the rebound callbacks registered with the container and it
		// can be updated with consuming classes that have gotten resolved here.
		$this->instances[ $abstract ] = $instance;

		if ( $is_bound ) {
			$this->rebound( $abstract );
		}

		return $instance;
	}

	/**
	 * Remove an alias from the contextual binding alias cache.
	 *
	 * @param  string $searched
	 * @return void
	 */
	protected function remove_abstract_alias( $searched ) {
		if ( ! isset( $this->aliases[ $searched ] ) ) {
			return;
		}

		foreach ( $this->abstract_aliases as $abstract => $aliases ) {
			foreach ( $aliases as $index => $alias ) {
				if ( $alias === $searched ) {
					unset( $this->abstract_aliases[ $abstract ][ $index ] );
				}
			}
		}
	}

	/**
	 * Alias a type to a different name.
	 *
	 * @param  string $abstract
	 * @param  string $alias
	 *
	 * @throws LogicException Thrown on logic error.
	 */
	public function alias( $abstract, $alias ): void {
		if ( $alias === $abstract ) {
			throw new LogicException( "[{$abstract}] is aliased to itself." );
		}

		$this->aliases[ $alias ] = $abstract;

		$this->abstract_aliases[ $abstract ][] = $alias;
	}

	/**
	 * Bind a new callback to an abstract's rebind event.
	 *
	 * @param  string   $abstract
	 * @param  \Closure $callback
	 * @return mixed
	 */
	public function rebinding( $abstract, Closure $callback ) {
		$this->rebound_callbacks[ $abstract = $this->get_alias( $abstract ) ][] = $callback;

		if ( $this->bound( $abstract ) ) {
			return $this->make( $abstract );
		}
	}

	/**
	 * Refresh an instance on the given target and method.
	 *
	 * @param  string $abstract
	 * @param  mixed  $target
	 * @param  string $method
	 * @return mixed
	 */
	public function refresh( $abstract, $target, $method ) {
		return $this->rebinding(
			$abstract,
			function ( $app, $instance ) use ( $target, $method ): void {
				$target->{$method}( $instance );
			}
		);
	}

	/**
	 * Fire the "rebound" callbacks for the given abstract type.
	 *
	 * @param  string $abstract
	 * @return void
	 */
	protected function rebound( $abstract ) {
		$instance = $this->make( $abstract );

		foreach ( $this->getReboundCallbacks( $abstract ) as $callback ) {
			call_user_func( $callback, $this, $instance );
		}
	}

	/**
	 * Get the rebound callbacks for a given type.
	 *
	 * @param  string $abstract
	 * @return array
	 */
	protected function getReboundCallbacks( $abstract ) {
		return $this->rebound_callbacks[ $abstract ] ?? [];
	}

	/**
	 * Wrap the given closure such that its dependencies will be injected when executed.
	 *
	 * @param  \Closure $callback
	 * @param  array    $parameters
	 * @return \Closure
	 */
	public function wrap( Closure $callback, array $parameters = [] ) {
		return fn () => $this->call( $callback, $parameters );
	}

	/**
	 * Call the given Closure / class@method and inject its dependencies.
	 *
	 * @param  callable|string $callback Callback to execute.
	 * @param  string[]        $parameters Parameters to pass.
	 * @param  string|null     $default_method Default method.
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException Throw for invalid arguments.
	 */
	public function call( $callback, array $parameters = [], $default_method = null ) {
		return Bound_Method::call( $this, $callback, $parameters, $default_method );
	}

	/**
	 * Get a closure to resolve the given type from the container.
	 *
	 * @param  string $abstract Abstract name.
	 * @return \Closure
	 */
	public function factory( $abstract ) {
		return fn () => $this->make( $abstract );
	}

	/**
	 * An alias function name for make().
	 *
	 * @param  string $abstract
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function make_with( $abstract, array $parameters = [] ) {
		return $this->make( $abstract, $parameters );
	}

	/**
	 * Resolve the given type from the container.
	 *
	 * @param  string $abstract
	 * @param  array  $parameters
	 * @return mixed
	 *
	 * @throws Binding_Resolution_Exception Thrown on missing resolution.
	 */
	public function make( $abstract, array $parameters = [] ) {
		return $this->resolve( $abstract, $parameters );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws Entry_Not_Found_Exception Thrown on entry not found.
	 * @throws Binding_Resolution_Exception Thrown on error resolution.
	 */
	public function get( $id ) {
		try {
			return $this->resolve( $id );
		} catch ( Binding_Resolution_Exception $e ) {
			if ( $this->has( $id ) ) {
					throw $e;
			}

			throw new Entry_Not_Found_Exception( $id, $e->getCode(), $e );
		}
	}

	/**
	 * Resolve the given type from the container.
	 *
	 * @param  string $abstract
	 * @param  array  $parameters
	 * @param  bool   $raise_events
	 * @return mixed
	 *
	 * @throws Binding_Resolution_Exception Thrown on missing resolution.
	 */
	protected function resolve( $abstract, $parameters = [], $raise_events = true ) {
		$abstract = $this->get_alias( $abstract );

		$needs_contextual_build = ! empty( $parameters ) || ! is_null(
			$this->get_contextual_concrete( $abstract )
		);

		// If an instance of the type is currently being managed as a singleton we'll
		// just return an existing instance instead of instantiating new instances
		// so the developer can keep using the same objects instance every time.
		if ( isset( $this->instances[ $abstract ] ) && ! $needs_contextual_build ) {
			return $this->instances[ $abstract ];
		}

		$this->with[] = $parameters;

		$concrete = $this->getConcrete( $abstract );

		// We're ready to instantiate an instance of the concrete type registered for
		// the binding. This will instantiate the types, as well as resolve any of
		// its "nested" dependencies recursively until all have gotten resolved.
		if ( $this->is_buildable( $concrete, $abstract ) ) {
			$object = $this->build( $concrete );
		} else {
			$object = $this->make( $concrete );
		}

		// If we defined any extenders for this type, we'll need to spin through them
		// and apply them to the object being built. This allows for the extension
		// of services, such as changing configuration or decorating the object.
		foreach ( $this->get_extenders( $abstract ) as $extender ) {
			$object = $extender( $object, $this );
		}

		// If the requested type is registered as a singleton we'll want to cache off
		// the instances in "memory" so we can return it later without creating an
		// entirely new instance of an object on each subsequent request for it.
		if ( $this->is_shared( $abstract ) && ! $needs_contextual_build ) {
			$this->instances[ $abstract ] = $object;
		}

		if ( $raise_events ) {
			$this->fire_resolving_callbacks( $abstract, $object );
		}

		// Before returning, we will also set the resolved flag to "true" and pop off
		// the parameter overrides for this build. After those two things are done
		// we will be ready to return back the fully constructed class instance.
		$this->resolved[ $abstract ] = true;

		array_pop( $this->with );

		return $object;
	}

	/**
	 * Get the concrete type for a given abstract.
	 *
	 * @param  string $abstract
	 * @return mixed   $concrete
	 */
	protected function getConcrete( $abstract ) {
		if ( ! is_null( $concrete = $this->get_contextual_concrete( $abstract ) ) ) {
			return $concrete;
		}

		// If we don't have a registered resolver or concrete for the type, we'll just
		// assume each type is a concrete name and will attempt to resolve it as is
		// since the container should be able to resolve concretes automatically.
		if ( isset( $this->bindings[ $abstract ] ) ) {
			return $this->bindings[ $abstract ]['concrete'];
		}

		return $abstract;
	}

	/**
	 * Get the contextual concrete binding for the given abstract.
	 *
	 * @param  string $abstract
	 * @return \Closure|array|string|null
	 */
	protected function get_contextual_concrete( $abstract ) {
		if ( ! is_null( $binding = $this->find_in_contextual_bindings( $abstract ) ) ) {
			return $binding;
		}

		/**
		 * Next we need to see if a contextual binding might be bound under an alias of the
		 * given abstract type.
		 *
		 * To do that, we will need to check if any aliases exist with this type and then
		 * spin through them and check each for contextual bindings as well.
		 */
		if ( empty( $this->abstract_aliases[ $abstract ] ) ) {
			return null;
		}

		foreach ( $this->abstract_aliases[ $abstract ] as $alias ) {
			if ( ! is_null( $binding = $this->find_in_contextual_bindings( $alias ) ) ) {
				return $binding;
			}
		}

		return null;
	}

	/**
	 * Find the concrete binding for the given abstract in the contextual binding array.
	 *
	 * @param  string $abstract
	 * @return \Closure|string|null
	 */
	protected function find_in_contextual_bindings( $abstract ) {
		return $this->contextual[ end( $this->build_stack ) ][ $abstract ] ?? null;
	}

	/**
	 * Determine if the given concrete is buildable.
	 *
	 * @param  mixed  $concrete
	 * @param  string $abstract
	 */
	protected function is_buildable( $concrete, $abstract ): bool {
		return $concrete === $abstract || $concrete instanceof Closure;
	}

	/**
	 * Instantiate a concrete instance of the given type.
	 *
	 * @param  \Closure|string $concrete
	 * @return mixed
	 *
	 * @throws Binding_Resolution_Exception Thrown on missing resolution.
	 */
	public function build( $concrete ) {
		// If the concrete type is actually a Closure, we will just execute it and
		// hand back the results of the functions, which allows functions to be
		// used as resolvers for more fine-tuned resolution of these objects.
		if ( $concrete instanceof Closure ) {
			return $concrete( $this, $this->get_last_parameter_override() );
		}

		try {
			$reflector = new ReflectionClass( $concrete );
		} catch ( ReflectionException $e ) {
			throw new Binding_Resolution_Exception( "Target class [$concrete] does not exist.", 0, $e );
		}

			// If the type is not instantiable, the developer is attempting to resolve
			// an abstract type such as an Interface or Abstract Class and there is
			// no binding registered for the abstractions so we need to bail out.
		if ( ! $reflector->isInstantiable() ) {
			$this->not_instantiable( $concrete );
			return;
		}

		$this->build_stack[] = $concrete;

		$constructor = $reflector->getConstructor();

		// If there are no constructors, that means there are no dependencies then
		// we can just resolve the instances of the objects right away, without
		// resolving any other types or dependencies out of these containers.
		if ( is_null( $constructor ) ) {
			array_pop( $this->build_stack );

			return new $concrete();
		}

		$dependencies = $constructor->getParameters();

		// Once we have all the constructor's parameters we can create each of the
		// dependency instances and then use the reflection instances to make a
		// new instance of this class, injecting the created dependencies in.
		try {
			$instances = $this->resolve_dependencies( $dependencies );
		} catch ( Binding_Resolution_Exception $e ) {
			array_pop( $this->build_stack );

			throw $e;
		}

			array_pop( $this->build_stack );

			return $reflector->newInstanceArgs( $instances );
	}

	/**
	 * Resolve all of the dependencies from the ReflectionParameters.
	 *
	 * @param  \ReflectionParameter[] $dependencies
	 *
	 * @throws Binding_Resolution_Exception Thrown on missing resolution.
	 */
	protected function resolve_dependencies( array $dependencies ): array {
		$results = [];

		foreach ( $dependencies as $dependency ) {
			// If this dependency has a override for this particular build we will use
			// that instead as the value. Otherwise, we will continue with this run
			// of resolutions and let reflection attempt to determine the result.
			if ( $this->has_parameter_override( $dependency ) ) {
				$results[] = $this->get_parameter_override( $dependency );

				continue;
			}

			// If the class is null, it means the dependency is a string or some other
			// primitive type which we can not resolve since it is not a class and
			// we will just bomb out with an error since we have no-where to go.
			$results[] = is_null( Reflector::get_parameter_class_name( $dependency ) )
				? $this->resolve_primitive( $dependency )
				: $this->resolveClass( $dependency );
		}

		return $results;
	}

	/**
	 * Determine if the given dependency has a parameter override.
	 *
	 * @param  \ReflectionParameter $dependency
	 * @return bool
	 */
	protected function has_parameter_override( $dependency ) {
		return array_key_exists(
			$dependency->name,
			$this->get_last_parameter_override()
		);
	}

	/**
	 * Get a parameter override for a dependency.
	 *
	 * @param  \ReflectionParameter $dependency
	 * @return mixed
	 */
	protected function get_parameter_override( $dependency ) {
		return $this->get_last_parameter_override()[ $dependency->name ];
	}

	/**
	 * Get the last parameter override.
	 *
	 * @return array
	 */
	protected function get_last_parameter_override() {
			return count( $this->with ) ? end( $this->with ) : [];
	}

	/**
	 * Resolve a non-class hinted primitive dependency.
	 *
	 * @param  \ReflectionParameter $parameter
	 * @return mixed
	 *
	 * @throws Binding_Resolution_Exception Thrown on missing resolution.
	 */
	protected function resolve_primitive( ReflectionParameter $parameter ) {
		if ( ! is_null( $concrete = $this->get_contextual_concrete( '$' . $parameter->getName() ) ) ) {
			return $concrete instanceof Closure ? $concrete( $this ) : $concrete;
		}

		if ( $parameter->isDefaultValueAvailable() ) {
			return $parameter->getDefaultValue();
		}

		$this->unresolvable_primitive( $parameter );
	}

	/**
	 * Resolve a class based dependency from the container.
	 *
	 * @param  \ReflectionParameter $parameter
	 * @return mixed
	 *
	 * @throws Binding_Resolution_Exception Thrown on missing resolution.
	 */
	protected function resolveClass( ReflectionParameter $parameter ) {
		try {
			return $parameter->isVariadic()
				? $this->resolve_variadic_class( $parameter )
				: $this->make( Reflector::get_parameter_class_name( $parameter ) );
		} catch ( Binding_Resolution_Exception $e ) {
			// If we can not resolve the class instance, we will check to see if the value
			// is optional, and if it is we will return the optional parameter value as
			// the value of the dependency, similarly to how we do this with scalars.
			if ( $parameter->isOptional() ) {
				return $parameter->getDefaultValue();
			}

			throw $e;
		}
	}

	/**
	 * Resolve a class based variadic dependency from the container.
	 *
	 * @param  \ReflectionParameter $parameter
	 * @return mixed
	 */
	protected function resolve_variadic_class( ReflectionParameter $parameter ) {
		$class_name = Reflector::get_parameter_class_name( $parameter );

		$abstract = $this->get_alias( $class_name );

		if ( ! is_array( $concrete = $this->get_contextual_concrete( $abstract ) ) ) {
			return $this->make( $class_name );
		}

		return array_map(
			fn ( $abstract ) => $this->resolve( $abstract ),
			$concrete
		);
	}

	/**
	 * Throw an exception that the concrete is not instantiable.
	 *
	 * @param  string $concrete
	 * @return void
	 *
	 * @throws Binding_Resolution_Exception Thrown on missing resolution.
	 */
	protected function not_instantiable( $concrete ) {
		if ( ! empty( $this->build_stack ) ) {
			$previous = implode( ', ', $this->build_stack );

			$message = "Target [$concrete] is not instantiable while building [$previous].";
		} else {
			$message = "Target [$concrete] is not instantiable.";
		}

		throw new Binding_Resolution_Exception( $message );
	}

	/**
	 * Throw an exception for an unresolvable primitive.
	 *
	 * @param  \ReflectionParameter $parameter
	 *
	 * @throws Binding_Resolution_Exception Thrown on missing resolution.
	 */
	protected function unresolvable_primitive( ReflectionParameter $parameter ): never {
		$message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

		throw new Binding_Resolution_Exception( $message );
	}

	/**
	 * Register a new resolving callback.
	 *
	 * @param  \Closure|string $abstract
	 * @param  \Closure|null   $callback
	 */
	public function resolving( $abstract, Closure $callback = null ): void {
		if ( is_string( $abstract ) ) {
			$abstract = $this->get_alias( $abstract );
		}

		if ( is_null( $callback ) && $abstract instanceof Closure ) {
			$this->global_resolving_callbacks[] = $abstract;
		} else {
			$this->resolving_callbacks[ $abstract ][] = $callback;
		}
	}

	/**
	 * Register a new after resolving callback for all types.
	 *
	 * @param  \Closure|string $abstract
	 * @param  \Closure|null   $callback
	 */
	public function after_resolving( $abstract, Closure $callback = null ): void {
		if ( is_string( $abstract ) ) {
			$abstract = $this->get_alias( $abstract );
		}

		if ( $abstract instanceof Closure && is_null( $callback ) ) {
			$this->global_after_resolving_callbacks[] = $abstract;
		} else {
			$this->after_resolving_callbacks[ $abstract ][] = $callback;
		}
	}

	/**
	 * Fire all of the resolving callbacks.
	 *
	 * @param  string $abstract
	 * @param  mixed  $object
	 * @return void
	 */
	protected function fire_resolving_callbacks( $abstract, $object ) {
			$this->fire_callback_array( $object, $this->global_resolving_callbacks );

			$this->fire_callback_array(
				$object,
				$this->get_callbacks_for_type( $abstract, $object, $this->resolving_callbacks )
			);

			$this->fire_after_resolving_callbacks( $abstract, $object );
	}

	/**
	 * Fire all of the after resolving callbacks.
	 *
	 * @param  string $abstract
	 * @param  mixed  $object
	 * @return void
	 */
	protected function fire_after_resolving_callbacks( $abstract, $object ) {
		$this->fire_callback_array( $object, $this->global_after_resolving_callbacks );

		$this->fire_callback_array(
			$object,
			$this->get_callbacks_for_type( $abstract, $object, $this->after_resolving_callbacks )
		);
	}

	/**
	 * Get all callbacks for a given type.
	 *
	 * @param  string $abstract
	 * @param  object $object
	 * @param  array  $callbacks_per_type
	 */
	protected function get_callbacks_for_type( $abstract, $object, array $callbacks_per_type ): array {
			$results = [];

		foreach ( $callbacks_per_type as $type => $callbacks ) {
			if ( $type === $abstract || $object instanceof $type ) {
					$results = array_merge( $results, $callbacks );
			}
		}

			return $results;
	}

	/**
	 * Fire an array of callbacks with an object.
	 *
	 * @param  mixed $object
	 * @param  array $callbacks
	 * @return void
	 */
	protected function fire_callback_array( $object, array $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$callback( $object, $this );
		}
	}

	/**
	 * Get the container's bindings.
	 *
	 * @return array
	 */
	public function get_bindings() {
			return $this->bindings;
	}

	/**
	 * Get the alias for an abstract if available.
	 *
	 * @param  string $abstract
	 * @return string
	 */
	public function get_alias( $abstract ) {
		if ( ! isset( $this->aliases[ $abstract ] ) ) {
				return $abstract;
		}

		return $this->get_alias( $this->aliases[ $abstract ] );
	}

	/**
	 * Get the extender callbacks for a given type.
	 *
	 * @param  string $abstract
	 * @return array
	 */
	protected function get_extenders( $abstract ) {
			$abstract = $this->get_alias( $abstract );

			return $this->extenders[ $abstract ] ?? [];
	}

	/**
	 * Remove all of the extender callbacks for a given type.
	 *
	 * @param  string $abstract
	 */
	public function forget_extenders( $abstract ): void {
		unset( $this->extenders[ $this->get_alias( $abstract ) ] );
	}

	/**
	 * Drop all of the stale instances and aliases.
	 *
	 * @param  string $abstract
	 * @return void
	 */
	protected function drop_stale_instances( $abstract ) {
		unset( $this->instances[ $abstract ], $this->aliases[ $abstract ] );
	}

	/**
	 * Remove a resolved instance from the instance cache.
	 *
	 * @param  string $abstract
	 */
	public function forget_instance( $abstract ): void {
		unset( $this->instances[ $abstract ] );
	}

	/**
	 * Clear all of the instances from the container.
	 */
	public function forget_instances(): void {
			$this->instances = [];
	}

	/**
	 * Flush the container of all bindings and resolved instances.
	 */
	public function flush(): void {
		$this->aliases          = [];
		$this->resolved         = [];
		$this->bindings         = [];
		$this->instances        = [];
		$this->abstract_aliases = [];
	}

	/**
	 * Get the globally available instance of the container.
	 *
	 * @deprecated Use `get_instance()` instead.
	 * @return \Mantle\Contracts\Container
	 */
	public static function getInstance() {
		return static::get_instance();
	}

	/**
	 * Get the globally available instance of the container.
	 */
	public static function get_instance(): \Mantle\Contracts\Container {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Set the shared instance of the container.
	 *
	 * @param  \Mantle\Contracts\Container|null $container
	 */
	public static function set_instance( \Mantle\Contracts\Container|null $container = null ): ?\Mantle\Contracts\Container {
		static::$instance = $container;

		return static::$instance;
	}

	/**
	 * Determine if a given offset exists.
	 *
	 * @param  mixed $key
	 */
	public function offsetExists( mixed $key ): bool {
			return $this->bound( $key );
	}

	/**
	 * Get the value at a given offset.
	 *
	 * @param  mixed $key
	 */
	public function offsetGet( mixed $key ): mixed {
			return $this->make( $key );
	}

	/**
	 * Set the value at a given offset.
	 *
	 * @param  mixed $key
	 * @param  mixed $value
	 */
	public function offsetSet( mixed $key, mixed $value ): void {
			$this->bind(
				$key,
				$value instanceof Closure ? $value : fn () => $value
			);
	}

	/**
	 * Unset the value at a given offset.
	 *
	 * @param  mixed $key
	 */
	public function offsetUnset( mixed $key ): void {
			unset( $this->bindings[ $key ], $this->instances[ $key ], $this->resolved[ $key ] );
	}

	/**
	 * Dynamically access container services.
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function __get( $key ) {
			return $this[ $key ];
	}

	/**
	 * Dynamically set container services.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function __set( $key, $value ) {
			$this[ $key ] = $value;
	}
}
