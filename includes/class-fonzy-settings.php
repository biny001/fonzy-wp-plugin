<?php
/**
 * Fonzy Settings — admin settings page for the plugin.
 *
 * @package Fonzy
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Fonzy_Settings
 *
 * Adds an informational settings page under Settings > Fonzy.
 *
 * @since 1.0.0
 */
class Fonzy_Settings {

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
	}

	/**
	 * Add the Fonzy settings page under the Settings menu.
	 *
	 * @since 1.0.0
	 */
	public function add_menu_page() {
		add_options_page(
			__( 'Fonzy Settings', 'fonzy' ),
			__( 'Fonzy', 'fonzy' ),
			'manage_options',
			'fonzy-settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_page() {
		$rest_url     = rest_url( 'fonzy/v1/publish' );
		$validate_url = rest_url( 'fonzy/v1/validate' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="card" style="max-width: 700px; padding: 20px; margin-top: 20px;">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Connection Status', 'fonzy' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %s: status text (active) */
						esc_html__( 'The Fonzy plugin is %s and ready to receive articles.', 'fonzy' ),
						'<strong style="color: #00a32a;">' . esc_html__( 'active', 'fonzy' ) . '</strong>'
					);
					?>
				</p>

				<h3><?php esc_html_e( 'API Endpoints', 'fonzy' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Publish Endpoint', 'fonzy' ); ?></th>
						<td><code><?php echo esc_html( $rest_url ); ?></code></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Validate Endpoint', 'fonzy' ); ?></th>
						<td><code><?php echo esc_html( $validate_url ); ?></code></td>
					</tr>
				</table>

				<h3><?php esc_html_e( 'Setup Instructions', 'fonzy' ); ?></h3>
				<ol>
					<li><?php echo wp_kses( __( 'Go to <strong>Users &rarr; Profile</strong> in your WordPress admin.', 'fonzy' ), array( 'strong' => array() ) ); ?></li>
					<li><?php echo wp_kses( __( 'Scroll down to <strong>Application Passwords</strong>.', 'fonzy' ), array( 'strong' => array() ) ); ?></li>
					<li><?php echo wp_kses( __( 'Enter a name (e.g., &ldquo;Fonzy&rdquo;) and click <strong>Add New Application Password</strong>.', 'fonzy' ), array( 'strong' => array() ) ); ?></li>
					<li><?php esc_html_e( 'Copy the generated password.', 'fonzy' ); ?></li>
					<li>
						<?php
						printf(
							/* translators: %s: link to Fonzy dashboard */
							wp_kses(
								__( 'In your %s, go to <strong>Settings &rarr; Integrations &rarr; WordPress</strong>.', 'fonzy' ),
								array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ), 'strong' => array() )
							),
							'<a href="https://fonzy.ai" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Fonzy dashboard', 'fonzy' ) . '</a>'
						);
						?>
					</li>
					<li>
						<?php
						printf(
							/* translators: %s: the site URL */
							esc_html__( 'Enter your site URL (%s), username, and the application password.', 'fonzy' ),
							'<code>' . esc_html( home_url() ) . '</code>'
						);
						?>
					</li>
				</ol>

				<h3><?php esc_html_e( 'Supported SEO Plugins', 'fonzy' ); ?></h3>
				<p><?php esc_html_e( 'Fonzy automatically sets SEO meta fields for:', 'fonzy' ); ?></p>
				<ul style="list-style: disc; padding-left: 20px;">
					<li><?php echo wp_kses( __( '<strong>Yoast SEO</strong> &mdash; meta title, meta description, focus keyword', 'fonzy' ), array( 'strong' => array() ) ); ?></li>
					<li><?php echo wp_kses( __( '<strong>RankMath</strong> &mdash; title, description, focus keyword', 'fonzy' ), array( 'strong' => array() ) ); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}
}
