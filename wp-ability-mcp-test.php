<?php
/**
 * Plugin Name: WP Ability MCP Test
 * Description: A test plugin for WP Ability MCP integration.
 * Version: 1.0.0
 * Text Domain: wp-ability-mcp-test
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	// Composer dependencies are missing
	add_action(
		'admin_notices',
		function () {
			?>
            <div class="notice notice-error">
                <p><?php esc_html_e( 'WP Ability MCP Test plugin requires Composer dependencies. Please run "composer install" in the plugin directory.', 'wp-ability-mcp-test' ); ?></p>
            </div>
			<?php
		}
	);

	return;
}

require_once __DIR__ . '/vendor/autoload.php';

$adapter = \WP\MCP\Core\McpAdapter::instance();

add_action(
	'wp_abilities_api_init',
	function () {
		wp_register_ability(
			'wp-ability-mcp-test/get-post-count',
			array(
				'label'               => __( 'Get Post Count', 'wp-ability-mcp-test' ),
				'description'         => __( 'Retrieves the total number of published posts.', 'wp-ability-mcp-test' ),
				'category'            => 'site',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_type' => array(
							'type'        => 'string',
							'description' => __( 'The post type to count.', 'wp-ability-mcp-test' ),
							'default'     => 'post',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'count' => array(
							'type'        => 'integer',
							'description' => __( 'The number of published posts.', 'wp-ability-mcp-test' ),
						),
					),
				),
				'execute_callback'    => 'wp_ability_mcp_test_get_post_count',
				'permission_callback' => function () {
					return current_user_can( 'read' );
				},
			)
		);
	}
);

/**
 * Execute callback for get-post-count ability.
 */
function wp_ability_mcp_test_get_post_count( $input ) {
    $post_type = $input['post_type'] ?? 'post';

    $count = wp_count_posts( $post_type );

    return array(
            'count' => (int) $count->publish,
    );
}


add_action(
	'mcp_adapter_init',
	function ( $adapter ) {
		$abilities = array(
			'wp-ability-mcp-test/get-post-count',
		);
		$adapter->create_server(
			'mcp-demo-server', // Unique server identifier.
			'mcp-demo-server', // REST API namespace.
			'mcp',             // REST API route.
			'MCP Demo Server', // Server name.
			'MCP Demo Server', // Server description.
			'v1.0.0',          // Server version.
			array(             // Transport methods.
				\WP\MCP\Transport\HttpTransport::class,  // Recommended: MCP 2025-06-18 compliant.
			),
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class, // Error handler.
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class, // Observability handler.
            $abilities,        // Abilities to expose as tools
			array(),           // Resources (optional).
			array(),           // Prompts (optional).
		);
	}
);
