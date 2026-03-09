<?php
/**
 * Fonzy Publisher — handles creating WordPress posts from Fonzy article data.
 *
 * @package Fonzy
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Fonzy_Publisher
 *
 * Creates WordPress posts from article data received via the REST API.
 *
 * @since 1.0.0
 */
class Fonzy_Publisher {

	/**
	 * Allowed post statuses for published articles.
	 *
	 * @var array
	 */
	private const ALLOWED_STATUSES = array( 'publish', 'draft', 'pending', 'private' );

	/**
	 * Create a WordPress post from Fonzy article data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Article data from Fonzy.
	 * @return array|WP_Error Post data on success, WP_Error on failure.
	 */
	public function create_post( array $params ) {
		$title   = sanitize_text_field( $params['title'] ?? '' );
		$content = wp_kses_post( $params['content'] ?? '' );
		$slug    = sanitize_title( $params['slug'] ?? $title );
		$status  = sanitize_text_field( $params['status'] ?? 'publish' );

		// Validate post status.
		if ( ! in_array( $status, self::ALLOWED_STATUSES, true ) ) {
			$status = 'draft';
		}

		// Resolve category.
		$category_ids = array();
		if ( ! empty( $params['category'] ) ) {
			$category_ids = $this->resolve_category( $params['category'] );
		}

		// Resolve tags from keywords.
		$tag_ids = array();
		if ( ! empty( $params['keywords'] ) ) {
			$tag_ids = $this->resolve_tags( $params['keywords'] );
		}

		// Build post array.
		$post_data = array(
			'post_title'    => $title,
			'post_content'  => $content,
			'post_name'     => $slug,
			'post_status'   => $status,
			'post_type'     => 'post',
			'post_category' => $category_ids,
			'tags_input'    => $tag_ids,
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Set featured image from URL.
		if ( ! empty( $params['thumbnail'] ) ) {
			$thumb_result = $this->set_featured_image( $post_id, $params['thumbnail'], $title );
			if ( is_wp_error( $thumb_result ) ) {
				// Non-fatal: log via WP debug but don't fail the whole publish.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					wp_trigger_error( __FUNCTION__, 'Fonzy: Failed to set featured image — ' . $thumb_result->get_error_message(), E_USER_NOTICE );
				}
			}
		}

		// Set SEO meta fields (Yoast SEO + RankMath).
		$this->set_seo_meta( $post_id, $params );

		// Build the response.
		$post = get_post( $post_id );

		return array(
			'success'  => true,
			'post_id'  => $post_id,
			'url'      => get_permalink( $post_id ),
			'slug'     => $post->post_name,
			'status'   => $post->post_status,
		);
	}

	/**
	 * Resolve a category by name, slug, or ID. Creates if it doesn't exist.
	 *
	 * @since 1.0.0
	 *
	 * @param string|int $category Category name, slug, or ID.
	 * @return array Category term IDs.
	 */
	private function resolve_category( $category ) {
		// If numeric, treat as ID.
		if ( is_numeric( $category ) ) {
			$term = get_term( absint( $category ), 'category' );
			if ( $term && ! is_wp_error( $term ) ) {
				return array( $term->term_id );
			}
		}

		// Try by slug.
		$term = get_category_by_slug( sanitize_title( $category ) );
		if ( $term ) {
			return array( $term->term_id );
		}

		// Try by name.
		$term = get_term_by( 'name', sanitize_text_field( $category ), 'category' );
		if ( $term ) {
			return array( $term->term_id );
		}

		// Create the category.
		$new_term = wp_insert_term( sanitize_text_field( $category ), 'category' );
		if ( ! is_wp_error( $new_term ) ) {
			return array( $new_term['term_id'] );
		}

		return array();
	}

	/**
	 * Resolve tags from keywords string or array.
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $keywords Keywords (comma-separated string or array).
	 * @return array Tag names for wp_insert_post tags_input.
	 */
	private function resolve_tags( $keywords ) {
		if ( is_array( $keywords ) ) {
			return array_map( 'sanitize_text_field', $keywords );
		}

		if ( is_string( $keywords ) ) {
			$tags = preg_split( '/[,;]+/', $keywords );
			return array_filter( array_map( 'trim', array_map( 'sanitize_text_field', $tags ) ) );
		}

		return array();
	}

	/**
	 * Download an image from URL and set it as the post's featured image.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $url     Image URL.
	 * @param string $title   Image title/alt text.
	 * @return int|WP_Error Attachment ID on success, WP_Error on failure.
	 */
	private function set_featured_image( $post_id, $url, $title = '' ) {
		// Handle thumbnail that might be a JSON array.
		if ( is_string( $url ) ) {
			$decoded = json_decode( $url, true );
			if ( is_array( $decoded ) && ! empty( $decoded ) ) {
				$url = $decoded[0];
			}
		}

		if ( is_array( $url ) ) {
			$url = ! empty( $url ) ? $url[0] : '';
		}

		$url = esc_url_raw( trim( $url ) );
		if ( empty( $url ) ) {
			return new WP_Error(
				'fonzy_empty_url',
				__( 'Thumbnail URL is empty.', 'fonzy-ai-content-publisher' )
			);
		}

		// Validate URL scheme.
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return new WP_Error(
				'fonzy_invalid_url',
				__( 'Thumbnail URL must use http or https.', 'fonzy-ai-content-publisher' )
			);
		}

		// Require media handling functions.
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		// Download the image into the media library.
		$attachment_id = media_sideload_image( $url, $post_id, sanitize_text_field( $title ), 'id' );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		// Set as featured image.
		set_post_thumbnail( $post_id, $attachment_id );

		return $attachment_id;
	}

	/**
	 * Set SEO meta fields for both Yoast SEO and RankMath.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $post_id Post ID.
	 * @param array $params  Article params containing metatitle, metadescription, keywords.
	 */
	private function set_seo_meta( $post_id, array $params ) {
		$meta_title       = sanitize_text_field( $params['metatitle'] ?? '' );
		$meta_description = sanitize_text_field( $params['metadescription'] ?? '' );

		// Build focus keyword string.
		$focus_keyword = '';
		if ( ! empty( $params['keywords'] ) ) {
			if ( is_array( $params['keywords'] ) ) {
				$focus_keyword = sanitize_text_field( $params['keywords'][0] ?? '' );
			} elseif ( is_string( $params['keywords'] ) ) {
				$parts = preg_split( '/[,;]+/', $params['keywords'] );
				$focus_keyword = sanitize_text_field( trim( $parts[0] ?? '' ) );
			}
		}

		// Yoast SEO meta fields.
		if ( $meta_title ) {
			update_post_meta( $post_id, '_yoast_wpseo_title', $meta_title );
		}
		if ( $meta_description ) {
			update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_description );
		}
		if ( $focus_keyword ) {
			update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_keyword );
		}

		// RankMath SEO meta fields.
		if ( $meta_title ) {
			update_post_meta( $post_id, 'rank_math_title', $meta_title );
		}
		if ( $meta_description ) {
			update_post_meta( $post_id, 'rank_math_description', $meta_description );
		}
		if ( $focus_keyword ) {
			update_post_meta( $post_id, 'rank_math_focus_keyword', $focus_keyword );
		}
	}
}
