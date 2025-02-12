<?php
/**
 * GTM_Script class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Features;

use Alley\WP\Types\Feature;
use JsonSerializable;
use stdClass;

/**
 * Google Tag Manager script placement.
 */
final class GTM_Script implements Feature {
	/**
	 * Constructor.
	 *
	 * @phpstan-param array<string, mixed> $data_layer
	 *
	 * @param string                          $tag_id     GTM tag ID.
	 * @param array|stdClass|JsonSerializable $data_layer Initial data layer data.
	 */
	public function __construct(
		private readonly string $tag_id,
		private readonly array|stdClass|JsonSerializable $data_layer,
	) {}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'wp_head', [ $this, 'render_head' ] );
		add_action( 'wp_body_open', [ $this, 'render_body' ] );
	}

	/**
	 * Render the GTM tag in the document body.
	 */
	public function render_head(): void {
		$data  = $this->data_layer instanceof JsonSerializable ? $this->data_layer : (object) $this->data_layer;
		$flags = WP_DEBUG ? JSON_PRETTY_PRINT : 0;

		printf(
			<<<'HTML'
<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push(%s);
</script>

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer',%s);</script>
<!-- End Google Tag Manager -->
HTML,
			wp_json_encode( $data, $flags ),
			wp_json_encode( $this->tag_id, $flags ),
		);
	}

	/**
	 * Render the GTM tag in the document body.
	 */
	public function render_body(): void {
		printf(
			<<<'HTML'
<!-- Google Tag Manager (noscript) -->
<noscript>
	<iframe src="%s" height="0" width="0" style="display:none;visibility:hidden"></iframe>
</noscript>
<!-- End Google Tag Manager (noscript) -->
HTML,
			esc_url( "https://www.googletagmanager.com/ns.html?id={$this->tag_id}" ),
		);
	}
}
