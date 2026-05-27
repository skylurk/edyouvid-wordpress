<?php

/**
 * Variables
 *
 * $has_search_terms  (bool)   Check if there are search terms
 * $has_results       (bool)   Check if there are results
 * $search_terms      (string) The search terms
 * $ceu_completions   (array)  The results
 */

// Create array with CSS classes to add to the main element
$container_css_classes = array();

// Add a class if there are search terms
if ( $has_search_terms ) {
	$container_css_classes[] = 'uo-ucec-course-report--has-search-terms';
}

// Add a class if there are results
if ( $has_results ) {
	$container_css_classes[] = 'uo-ucec-course-report--has-results';
}

?>

<div id="uo-ucec-course-report" class="uo-ucec-course-report <?php echo esc_attr( implode( ' ', $container_css_classes ) ); ?>" data-has-results="<?php echo esc_attr( $has_results ? 'true' : 'false' ); ?>">
	<div class="uo-ucec-course-report-search">
		<form method="GET">
			<div class="uo-ucec-course-report-search__user">
				<label class="uo-ucec-course-report-search__fake-input">
					<span class="uo-ucec-course-report-search__label">
						<span><?php esc_html_e( 'User', 'uncanny-ceu' ); ?></span>
					</span>

					<input 
						class="uo-ucec-course-report-search__input" 
						name="search" 
						type="text" 
						value="<?php echo esc_attr( $search_terms ); ?>" 
						title="<?php esc_attr_e( '3 characters minimum', 'uncanny-ceu' ); ?>" 
						pattern=".{3,}" 
						required />
				</label>
			</div>
			<div class="uo-ucec-course-report-search__actions">
				<button type="submit" class="uo-ucec-course-report-search__submit">
					<?php esc_html_e( 'Search', 'uncanny-ceu' ); ?>
				</button>
			</div>
		</form>
	</div>

	<?php
	// Check if it has search terms
	if ( $has_search_terms ) :
		// Check if it has results
		if ( $has_results ) :
			?>
			<?php // Create table ?>
			<div class="uo-ucec-course-report-table">
				<table id="uo-ucec-course-report-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'First Name', 'uncanny-ceu' ); ?></th>
							<th><?php esc_html_e( 'Last Name', 'uncanny-ceu' ); ?></th>
							<th><?php esc_html_e( 'Title', 'uncanny-ceu' ); ?></th>
							<th class="uo-ucec-course-report-cell-date-and-time"><?php esc_html_e( 'Completion Date', 'uncanny-ceu' ); ?></th>
							<th><?php echo esc_html( \uncanny_ceu\Utilities::credit_designation_label( 'plural', __( 'CEUs', 'uncanny-ceu' ) ) ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $ceu_completions as $entry ) { ?>

							<tr>
								<td><?php echo esc_html( $entry['first_name'] ); ?></td>
								<td><?php echo esc_html( $entry['last_name'] ); ?></td>
								<td><?php echo esc_html( $entry['ceu_title'] ); ?></td>
								<td>
									<span data-date="<?php echo esc_attr( $entry['ceu_date'] ); ?>" class="uo-ucec-course-report-datatables-hidden">
										<?php echo esc_html( $entry['ceu_date'] ); ?>
									</span>
									<div class="uo-ucec-course-report-cell-date-and-time">
										<div class="uo-ucec-course-report-cell-date-and-time__date">
											<?php echo esc_html( learndash_adjust_date_time_display( $entry['ceu_date'], 'F d, Y' ) ); ?>
										</div>
										<div class="uo-ucec-course-report-cell-date-and-time__time">
											<?php echo esc_html( learndash_adjust_date_time_display( $entry['ceu_date'], 'g:i A' ) ); ?>
										</div>
									</div>
								</td>
								<td><?php echo esc_html( $entry['ceu_earned'] ); ?></td>
							</tr>

						<?php } ?>
					</tbody>
				</table>
			</div>

		<?php else : ?>

			<?php // Otherwise show "No Results" ?>
			<div class="uo-ucec-course-report-no-results">
				<div class="ucec-report-results-no-results__text">
					<?php esc_html_e( 'No results', 'uncanny-ceu' ); ?>
				</div>
			</div>

		<?php endif; ?>

	<?php endif; ?>

</div>
