<?php
/**
 * Fonzy REST API — registers the /wp-json/fonzy/v1/ endpoints.
 *
 * @package Fonzy
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Fonzy_REST_API
 *
 * Registers and handles all Fonzy REST API routes.
 *
 * @since 1.0.0
 */
class Fonzy_REST_API {

	/**
	 * Publisher instance.
	 *
	 * @var Fonzy_Publisher
	 */
	private $publisher;

	/**
	 * Constructor.
	 *
	 * @param Fonzy_Publisher $publisher Publisher instance.
	 */
	public function __construct( Fonzy_Publisher $publisher ) {
		$this->publisher = $publisher;
	}

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {

		// POST /wp-json/fonzy/v1/publish
		register_rest_route( 'fonzy/v1', '/publish', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_publish' ),
			'permission_callback' => array( $this, 'check_permission' ),
			'args'                => array(
				'title'           => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => function ( $value ) {
						return is_string( $value ) && ! empty( trim( $value ) );
					},
					'description'       => __( 'The article title.', 'fonzy' ),
				),
				'content'         => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'wp_kses_post',
					'description'       => __( 'The article HTML content.', 'fonzy' ),
				),
				'slug'            => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_title',
					'description'       => __( 'The URL slug for the post.', 'fonzy' ),
				),
				'metatitle'       => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
					'description'       => __( 'SEO meta title.', 'fonzy' ),
				),
				'metadescription' => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
					'description'       => __( 'SEO meta description.', 'fonzy' ),
				),
				'thumbnail'       => array(
					'type'              => 'string',
					'default'           => '',
					'description'       => __( 'URL of the featured image.', 'fonzy' ),
				),
				'keywords'        => array(
					'default'           => '',
					'description'       => __( 'Comma-separated tags or array of tag names.', 'fonzy' ),
				),
				'category'        => array(
					'default'           => '',
					'description'       => __( 'Category name, slug, or ID.', 'fonzy' ),
				),
				'status'          => array(
					'type'              => 'string',
					'default'           => 'publish',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => function ( $value ) {
						$allowed = array( 'publish', 'draft', 'pending', 'private' );
						return in_array( $value, $allowed, true );
					},
					'description'       => __( 'Post status. One of: publish, draft, pending, private.', 'fonzy' ),
				),
			),
		) );

		// GET /wp-json/fonzy/v1/validate
		register_rest_route( 'fonzy/v1', '/validate', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'handle_validate' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		// GET /wp-json/fonzy/v1/categories
		register_rest_route( 'fonzy/v1', '/categories', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'handle_categories' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );
	}

	/**
	 * Permission check — requires a valid WordPress user with publish_posts capability.
	 * Authentication is handled by WordPress core (Application Passwords / Basic Auth).
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return true|WP_Error True if permitted, WP_Error otherwise.
	 */
	public function check_permission( WP_REST_Request $request ) {
		if ( ! current_user_can( 'publish_posts' ) ) {
			return new WP_Error(
				'fonzy_unauthorized',
				__( 'You must authenticate with a user that has publish_posts capability.', 'fonzy' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * POST /fonzy/v1/publish — create a new post from Fonzy article data.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error Response on success, WP_Error on failure.
	 */
	public function handle_publish( WP_REST_Request $request ) {
		$params = $request->get_params();

		$result = $this->publisher->create_post( $params );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * GET /fonzy/v1/validate — check the connection is working.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response Response with site info.
	 */
	public function handle_validate( WP_REST_Request $request ) {
		$user = wp_get_current_user();

		return rest_ensure_response( array(
			'valid'   => true,
			'site'    => get_bloginfo( 'name' ),
			'url'     => home_url(),
			'user'    => $user->user_login,
			'version' => FONZY_VERSION,
		) );
	}

	/**
	 * GET /fonzy/v1/categories — list available categories.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response Response with categories array.
	 */
	public function handle_categories( WP_REST_Request $request ) {
		$categories = get_categories( array(
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		$result = array();
		foreach ( $categories as $cat ) {
			$result[] = array(
				'id'   => $cat->term_id,
				'name' => $cat->name,
				'slug' => $cat->slug,
			);
		}

		return rest_ensure_response( $result );
	}
}
