<?php
/**
 * Archive contacts
 *
 * @author    Pronamic
 * @copyright 2026 Pronamic
 * @license   GPL-2.0-or-later
 * @package   Pronamic\Orbis\Contacts
 */

namespace Pronamic\Orbis\Contacts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\get_header();

?>
<div class="card">
	<?php \get_template_part( 'templates/search_form' ); ?>

	<?php if ( \have_posts() ) : ?>

		<div class="table-responsive">
			<table class="table table-striped table-condense table-hover">
				<col width="84" />

				<thead>
					<tr>
						<th><span class="visually-hidden"><?php \esc_html_e( 'Photo', 'orbis-contacts' ); ?></span></th>
						<th><?php \esc_html_e( 'Name', 'orbis-contacts' ); ?></th>
						<th><?php \esc_html_e( 'Type', 'orbis-contacts' ); ?></th>
						<th><?php \esc_html_e( 'Email Addresses', 'orbis-contacts' ); ?></th>
						<th><?php \esc_html_e( 'Categories', 'orbis-contacts' ); ?></th>
						<th><?php \esc_html_e( 'Author', 'orbis-contacts' ); ?></th>
						<th><span class="visually-hidden"><?php \esc_html_e( 'Actions', 'orbis-contacts' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php

					while ( \have_posts() ) :
						\the_post();

						$contact_type = \get_post_type_object( (string) \get_post_type() );

						$email_addresses = ContactJson::get_email_addresses( (int) \get_the_ID() );

						$categories = \get_the_terms( (int) \get_the_ID(), 'orbis_contact_category' );
						$categories = \is_array( $categories ) ? \wp_list_pluck( $categories, 'name' ) : [];

						?>

						<tr id="post-<?php \the_ID(); ?>" <?php \post_class(); ?>>
							<td>
								<?php if ( \has_post_thumbnail() ) : ?>

									<?php

									\the_post_thumbnail(
										'avatar',
										[
											'class' => 'rounded-circle',
											'alt'   => '',
										]
									);

									?>

								<?php endif; ?>
							</td>
							<td>
								<a href="<?php \the_permalink(); ?>"><?php \the_title(); ?></a>

								<?php \get_template_part( 'templates/table-cell-comments' ); ?>
							</td>
							<td>
								<?php echo \esc_html( null === $contact_type ? '' : $contact_type->labels->singular_name ); ?>
							</td>
							<td>
								<?php foreach ( $email_addresses as $email_address ) : ?>

									<a href="<?php echo \esc_url( 'mailto:' . $email_address['email'] ); ?>"><?php echo \esc_html( $email_address['email'] ); ?></a><br />

								<?php endforeach; ?>
							</td>
							<td>
								<?php echo \esc_html( \implode( ', ', $categories ) ); ?>
							</td>
							<td>
								<?php \the_author(); ?>
							</td>
							<td>
								<?php \get_template_part( 'templates/table-cell-actions' ); ?>
							</td>
						</tr>

					<?php endwhile; ?>
				</tbody>
			</table>
		</div>

	<?php else : ?>

		<div class="card-body">
			<?php \get_template_part( 'templates/content-none' ); ?>
		</div>

	<?php endif; ?>
</div>

<?php

if ( \function_exists( 'orbis_content_nav' ) ) {
	\orbis_content_nav();
} else {
	\the_posts_pagination();
}

\get_footer();
