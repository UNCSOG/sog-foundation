<?php
/**
 * The template for displaying search forms.
 */
?>
<form class="search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<div class="input-group">
		<input type="text" name="s" class="form-control" placeholder="<?php esc_attr_e( 'Search', 'sog-foundation-parent' ); ?>" />
		<button type="submit" class="btn btn-secondary" name="submit"><?php esc_html_e( 'Search', 'sog-foundation-parent' ); ?></button>
	</div><!-- /.input-group -->
</form>
