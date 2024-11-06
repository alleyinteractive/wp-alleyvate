<?php
/**
 * Hookable trait file
 *
 * @package Mantle
 */

namespace Mantle\Support\Traits;

use Mantle\Support\Attributes\Action;
use Mantle\Support\Attributes\Filter;
use Mantle\Support\Collection;
use Mantle\Support\Service_Provider;
use Mantle\Support\Str;
use ReflectionClass;

use function Mantle\Support\Helpers\collect;

/**
 * Register all hooks on a class.
 *
 * Collects all of the `on_{hook}` and `on_{hook}_at_{priority}` methods as
 * well as the attribute based `#[Action]` methods and registers them with
 * the respective WordPress hooks.
 */
trait Hookable {
	/**
	 * Constructor (can be overridden by the trait user).
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Boot all actions and attribute methods on the service provider.
	 *
	 * Collects all of the `on_{hook}`, `on_{hook}_at_{priority}`,
	 * `action__{hook}`, and `filter__{hook}` methods as well as the attribute
	 * based `#[Action]` and `#[Filter]` methods and registers them with the
	 * respective WordPress hooks.
	 */
	protected function register_hooks(): void {
		$this->collect_action_methods()
			->merge( $this->collect_attribute_hooks() )
			->unique()
			->each(
				function ( array $item ): void {
					if ( $this->use_event_dispatcher() ) {
						if ( 'action' === $item['type'] ) {
							\Mantle\Support\Helpers\add_action( $item['hook'], [ $this, $item['method'] ], $item['priority'] );
						} else {
							\Mantle\Support\Helpers\add_filter( $item['hook'], [ $this, $item['method'] ], $item['priority'] );
						}
					} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
						// Use the default WordPress action/filter methods.
						if ( 'action' === $item['type'] ) {
							\add_action( $item['hook'], [ $this, $item['method'] ], $item['priority'], 999 );
						} else {
							\add_filter( $item['hook'], [ $this, $item['method'] ], $item['priority'], 999 );
						}
					}
				},
			);
	}

	/**
	 * Collect all action methods from the service provider.
	 *
	 * @return Collection<int, array{type: string, hook: string, method: string, priority: int}>
	 */
	protected function collect_action_methods(): Collection {
		return collect( get_class_methods( static::class ) )
			->filter(
				fn ( string $method ) => Str::starts_with( $method, [ 'on_', 'action__', 'filter__' ] )
			)
			->map(
				function ( string $method ) {
					$type = match ( true ) {
						Str::starts_with( $method, 'filter__' ) => 'filter',
						default => 'action',
					};

					$hook = match ( true ) {
						Str::starts_with( $method, 'on_' ) => Str::after( $method, 'on_' ),
						default => Str::after( $method, $type . '__' ),
					};

					$priority = 10;

					if ( Str::contains( $hook, '_at_' ) ) {
						// Strip the priority from the hook name.
						$priority = (int) Str::after_last( $hook, '_at_' );
						$hook     = Str::before_last( $hook, '_at_' );
					}

					return [
						'type'     => $type,
						'hook'     => $hook,
						'method'   => $method,
						'priority' => $priority,
					];
				}
			);
	}

	/**
	 * Collect all attribute actions on the service provider.
	 *
	 * Allow methods with the `#[Action]` attribute to automatically register
	 * WordPress hooks.
	 *
	 * @return Collection<int, array{type: string, hook: string, method: string, priority: int}>
	 */
	protected function collect_attribute_hooks(): Collection {
		$items = new Collection();
		$class = new ReflectionClass( static::class );

		foreach ( $class->getMethods() as $method ) {
			foreach ( $method->getAttributes( Action::class ) as $attribute ) {
				$instance = $attribute->newInstance();

				$items->push(
					[
						'type'     => 'action',
						'hook'     => $instance->hook_name,
						'method'   => $method->getName(),
						'priority' => $instance->priority,
					]
				);
			}

			foreach ( $method->getAttributes( Filter::class ) as $attribute ) {
				$instance = $attribute->newInstance();

				$items->push(
					[
						'type'     => 'filter',
						'hook'     => $instance->hook_name,
						'method'   => $method->getName(),
						'priority' => $instance->priority,
					]
				);
			}
		}

		return $items;
	}

	/**
	 * Determine if the service provider should use the event dispatcher or the
	 * core WordPress hooks.
	 *
	 * By default, it is only enabled if the class is an instance of the
	 * `Service_Provider` class. For external uses of this trait, the event
	 * dispatcher won't be used.
	 */
	public function use_event_dispatcher(): bool {
		return class_exists( Service_Provider::class ) && $this instanceof Service_Provider;
	}
}
