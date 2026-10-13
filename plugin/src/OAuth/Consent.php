<?php
/**
 * The "Connect an AI app" consent screen.
 *
 * @package McpBuilder
 */

namespace McpBuilder\OAuth;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the approval page shown when an AI app asks to connect.
 * Plain HTML styled like the WordPress login screen, so it works without
 * loading wp-admin.
 */
final class Consent {

	public const NONCE = 'mcpb_oauth_consent_';

	/**
	 * @param array<string, mixed>  $client Client row.
	 * @param array<string, string> $query  Original authorization request parameters.
	 */
	public static function page( array $client, string $redirect_uri, string $scope, array $query ): void {
		$user        = wp_get_current_user();
		$app_host    = (string) wp_parse_url( $redirect_uri, PHP_URL_HOST );
		$is_loopback = in_array( $app_host, array( 'localhost', '127.0.0.1', '[::1]', '::1' ), true );
		$read_first  = Tokens::SCOPE_READ === $scope;
		$action      = Server::issuer() . '/authorize';

		ob_start();
		?>
		<h1><?php esc_html_e( 'Connect an AI app', 'mcp-builder' ); ?></h1>
		<p class="lead">
			<?php
			printf(
				/* translators: 1: app name, 2: site name. */
				esc_html__( '%1$s wants to use MCP Builder on %2$s.', 'mcp-builder' ),
				'<strong>' . esc_html( (string) $client['name'] ) . '</strong>',
				'<strong>' . esc_html( get_bloginfo( 'name' ) ) . '</strong>'
			);
			?>
		</p>
		<dl>
			<dt><?php esc_html_e( 'Returns to', 'mcp-builder' ); ?></dt>
			<dd><?php echo esc_html( $is_loopback ? __( 'An app on this computer (local address)', 'mcp-builder' ) : $app_host ); ?></dd>
			<dt><?php esc_html_e( 'Acts as', 'mcp-builder' ); ?></dt>
			<dd><?php echo esc_html( $user->display_name . ' (' . $user->user_login . ')' ); ?></dd>
		</dl>
		<?php if ( 'cimd' !== ( $client['source'] ?? '' ) ) : ?>
			<p class="note"><?php esc_html_e( 'This app registered itself. Only continue if you started this connection yourself just now.', 'mcp-builder' ); ?></p>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( $action ); ?>">
			<?php foreach ( $query as $key => $value ) : ?>
				<?php if ( is_scalar( $value ) && ! in_array( $key, array( 'decision', 'access', '_mcpb_nonce' ), true ) ) : ?>
					<input type="hidden" name="<?php echo esc_attr( (string) $key ); ?>" value="<?php echo esc_attr( (string) $value ); ?>">
				<?php endif; ?>
			<?php endforeach; ?>
			<input type="hidden" name="_mcpb_nonce" value="<?php echo esc_attr( wp_create_nonce( self::NONCE . $client['client_id'] ) ); ?>">
			<fieldset>
				<legend><?php esc_html_e( 'Access', 'mcp-builder' ); ?></legend>
				<label><input type="radio" name="access" value="full" <?php checked( ! $read_first ); ?>> <?php esc_html_e( 'Read and edit pages', 'mcp-builder' ); ?></label>
				<label><input type="radio" name="access" value="read" <?php checked( $read_first ); ?>> <?php esc_html_e( 'Read only', 'mcp-builder' ); ?></label>
			</fieldset>
			<p class="hint"><?php esc_html_e( 'The app can do what your account can do, within MCP Builder\'s safety settings. Every change is snapshotted and logged. You can disconnect it any time under Settings → MCP Builder → Connection.', 'mcp-builder' ); ?></p>
			<div class="actions">
				<button type="submit" name="decision" value="deny" class="secondary"><?php esc_html_e( 'Cancel', 'mcp-builder' ); ?></button>
				<button type="submit" name="decision" value="approve" class="primary"><?php esc_html_e( 'Allow access', 'mcp-builder' ); ?></button>
			</div>
		</form>
		<?php
		self::render( (string) ob_get_clean() );
	}

	public static function error_page( string $message ): void {
		self::render( '<h1>' . esc_html__( 'Connection stopped', 'mcp-builder' ) . '</h1><p class="lead">' . esc_html( $message ) . '</p>', 400 );
	}

	private static function render( string $body, int $status = 200 ): void {
		status_header( $status );
		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'X-Frame-Options: DENY' );
		header( "Content-Security-Policy: frame-ancestors 'none'" );
		?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html__( 'Connect an AI app', 'mcp-builder' ) . ' &lsaquo; ' . esc_html( get_bloginfo( 'name' ) ); ?></title>
<style>
	body { margin: 0; background: #f0f0f1; color: #1d2327; font: 15px/1.6 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
	main { max-width: 420px; margin: 8vh auto; padding: 0 16px; }
	.card { background: #fff; border: 1px solid #c3c4c7; padding: 28px 28px 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04); }
	.site { text-align: center; color: #50575e; font-size: 13px; margin-bottom: 16px; }
	h1 { font-size: 22px; margin: 0 0 12px; line-height: 1.3; }
	.lead { margin: 0 0 16px; }
	dl { display: grid; grid-template-columns: auto 1fr; gap: 6px 14px; margin: 0 0 16px; font-size: 14px; }
	dt { color: #50575e; } dd { margin: 0; word-break: break-word; }
	.note { background: #fcf9e8; border-left: 4px solid #dba617; padding: 8px 12px; font-size: 13px; margin: 0 0 16px; }
	fieldset { border: 1px solid #dcdcde; padding: 10px 14px 12px; margin: 0 0 12px; }
	legend { font-weight: 600; padding: 0 4px; }
	label { display: block; padding: 4px 0; cursor: pointer; }
	.hint { font-size: 13px; color: #50575e; margin: 0 0 18px; }
	.actions { display: flex; justify-content: flex-end; gap: 10px; }
	button { font: inherit; font-weight: 600; padding: 9px 18px; border-radius: 3px; cursor: pointer; border: 1px solid #2271b1; }
	button.primary { background: #2271b1; color: #fff; }
	button.secondary { background: #f6f7f7; color: #2271b1; }
	button:focus-visible, input:focus-visible { outline: 2px solid #2271b1; outline-offset: 2px; }
	.credit { text-align: center; font-size: 12px; color: #646970; margin-top: 14px; }
	.credit a { color: inherit; }
</style>
</head>
<body>
<main>
	<p class="site"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
	<div class="card">
		<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts above. ?>
	</div>
	<p class="credit">MCP Builder by <a href="https://ashrafchitambaa.com" rel="noopener">Ashraf Chitambaa</a></p>
</main>
</body>
</html>
		<?php
		exit;
	}
}
