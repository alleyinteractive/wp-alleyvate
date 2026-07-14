# Process interface

The `Process` interface describes a feature that can be stopped, not just started. It extends the `Feature` interface with a `halt()` method.

## Definition

```php
interface Process extends Feature {
	public function halt(): void;
}
```
