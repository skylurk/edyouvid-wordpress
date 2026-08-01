<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Admin_Groups {

	public static function register(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
	}

	public static function add_menu(): void {
		add_submenu_page(
			'gld-bundles',
			'Groups & Pricing',
			'Groups & Pricing',
			'manage_options',
			'gld-groups',
			array( __CLASS__, 'render_page' )
		);
	}

	// ── Render ────────────────────────────────────────────────────────────

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$group_id = absint( $_GET['group'] ?? 0 );

		if ( $group_id && get_post_type( $group_id ) === 'groups' ) {
			self::render_detail( $group_id );
		} else {
			self::render_list();
		}
	}

	private static function render_list(): void {
		$groups = get_posts( array(
			'post_type'      => 'groups',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		$currency = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '';
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Groups & Pricing</h1>
			<hr class="wp-header-end">

			<?php
			$groups_per_page  = 20;
			$groups_paged     = max( 1, (int) ( $_GET['group_paged'] ?? 1 ) );
			$groups_total     = count( $groups );
			$groups_pages     = max( 1, (int) ceil( $groups_total / $groups_per_page ) );
			$groups_page_rows = array_slice( $groups, ( $groups_paged - 1 ) * $groups_per_page, $groups_per_page );
			?>
			<?php if ( empty( $groups ) ) : ?>
				<p style="color:#666">No groups yet.</p>
			<?php else : ?>
			<div style="max-width:1100px;max-height:480px;overflow-y:auto;margin-bottom:8px">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Group</th>
						<th>Leader(s)</th>
						<th style="width:90px">Learners</th>
						<th style="width:110px">Status</th>
						<th style="width:110px">Price/student</th>
						<th style="width:110px">Expiry</th>
						<th style="width:100px">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $groups_page_rows as $group ) :
						$group_id  = $group->ID;
						$leaders   = learndash_get_groups_administrators( $group_id );
						$learners  = learndash_get_groups_user_ids( $group_id );
						$sub       = GLD_Subscription::get_for_group( $group_id );
						$status    = GLD_Subscription::status_label( $sub );
					?>
					<tr>
						<td><strong><?php echo esc_html( $group->post_title ); ?></strong></td>
						<td style="font-size:12px">
							<?php if ( empty( $leaders ) ) : ?>
								<span style="color:#999">— none —</span>
							<?php else : ?>
								<?php foreach ( $leaders as $leader ) : ?>
									<div><?php echo esc_html( $leader->display_name ); ?> <span style="color:#999">(<?php echo esc_html( $leader->user_email ); ?>)</span></div>
								<?php endforeach; ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( count( $learners ) ); ?></td>
						<td><?php echo self::status_badge( $status ); ?></td>
						<td><?php echo $sub && $sub->per_seat_price > 0 ? esc_html( $currency . number_format( (float) $sub->per_seat_price, 2 ) ) : '—'; ?></td>
						<td><?php echo $sub ? esc_html( date( 'M j, Y', strtotime( $sub->expiry_date ) ) ) : '—'; ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=gld-groups&group=' . $group_id ) ); ?>" class="button button-small">View</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			</div>
			<?php if ( $groups_pages > 1 ) : ?>
				<div style="max-width:1100px;display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:18px;font-size:13px;color:#666">
					<?php if ( $groups_paged > 1 ) : ?>
						<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'group_paged', $groups_paged - 1 ) ); ?>">&larr; Prev</a>
					<?php else : ?>
						<span class="button button-small" style="opacity:.4;cursor:not-allowed">&larr; Prev</span>
					<?php endif; ?>
					<span>Page <?php echo (int) $groups_paged; ?> of <?php echo (int) $groups_pages; ?></span>
					<?php if ( $groups_paged < $groups_pages ) : ?>
						<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'group_paged', $groups_paged + 1 ) ); ?>">Next &rarr;</a>
					<?php else : ?>
						<span class="button button-small" style="opacity:.4;cursor:not-allowed">Next &rarr;</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_detail( int $group_id ): void {
		$group    = get_post( $group_id );
		$leaders  = learndash_get_groups_administrators( $group_id );
		$learners = learndash_get_groups_user_ids( $group_id );
		$sub      = GLD_Subscription::get_for_group( $group_id );
		$status   = GLD_Subscription::status_label( $sub );
		$currency = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '';
		$hidden   = class_exists( 'GLD_Course_Visibility' ) ? GLD_Course_Visibility::get_hidden_ids_for_group( $group_id ) : array();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html( $group->post_title ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=gld-groups' ) ); ?>" class="page-title-action">&larr; Back to Groups</a>
			<hr class="wp-header-end">

			<div style="display:flex;gap:20px;flex-wrap:wrap;max-width:1100px;margin-bottom:24px">
				<div style="flex:1;min-width:280px;background:#fff;border:1px solid #ccc;border-radius:4px;padding:20px">
					<h2 style="margin-top:0;font-size:14px">Leaders</h2>
					<?php if ( empty( $leaders ) ) : ?>
						<p style="color:#999">No leaders assigned.</p>
					<?php else : ?>
						<?php foreach ( $leaders as $leader ) : ?>
							<div style="margin-bottom:6px">
								<strong><?php echo esc_html( $leader->display_name ); ?></strong><br>
								<span style="color:#666;font-size:12px"><?php echo esc_html( $leader->user_email ); ?></span>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<div style="flex:1;min-width:280px;background:#fff;border:1px solid #ccc;border-radius:4px;padding:20px">
					<h2 style="margin-top:0;font-size:14px">Subscription</h2>
					<p>Status: <?php echo self::status_badge( $status ); ?></p>
					<?php if ( $sub ) : ?>
						<p style="font-size:13px;color:#444">
							Plan: <strong><?php echo esc_html( $sub->bundle_name ?: '—' ); ?></strong><br>
							Price/student: <strong><?php echo $sub->per_seat_price > 0 ? esc_html( $currency . number_format( (float) $sub->per_seat_price, 2 ) ) : '—'; ?></strong><br>
							Started: <?php echo esc_html( date( 'M j, Y', strtotime( $sub->start_date ) ) ); ?><br>
							Expires: <?php echo esc_html( date( 'M j, Y', strtotime( $sub->expiry_date ) ) ); ?><br>
							Credit balance: <?php echo esc_html( $currency . number_format( (float) $sub->credit_balance, 2 ) ); ?>
						</p>
						<p style="font-size:12px;color:#888">Pricing is managed globally on the <a href="<?php echo esc_url( admin_url( 'admin.php?page=gld-bundles' ) ); ?>">Course Bundles</a> page.</p>
					<?php else : ?>
						<p style="color:#999">This group hasn't activated access yet (no payment card set up).</p>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( ! empty( $hidden ) ) : ?>
			<div style="max-width:1100px;background:#fff;border:1px solid #ccc;border-radius:4px;padding:20px;margin-bottom:24px">
				<h2 style="margin-top:0;font-size:14px">Hidden Courses</h2>
				<p style="font-size:12px;color:#666">Courses the group leader has hidden from newly-added members:</p>
				<ul style="margin:0 0 0 18px">
					<?php foreach ( $hidden as $cid ) : ?>
						<li><?php echo esc_html( get_the_title( $cid ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>

			<?php
			$learners_per_page  = 20;
			$learners_paged     = max( 1, (int) ( $_GET['learner_paged'] ?? 1 ) );
			$learners_total     = count( $learners );
			$learners_pages     = max( 1, (int) ceil( $learners_total / $learners_per_page ) );
			$learners_page_rows = array_slice( $learners, ( $learners_paged - 1 ) * $learners_per_page, $learners_per_page );
			?>
			<div style="max-width:1100px;background:#fff;border:1px solid #ccc;border-radius:4px;padding:20px">
				<h2 style="margin-top:0;font-size:14px">Learners (<?php echo esc_html( count( $learners ) ); ?>)</h2>
				<?php if ( empty( $learners ) ) : ?>
					<p style="color:#999">No learners in this group yet.</p>
				<?php else : ?>
				<div style="max-height:420px;overflow-y:auto">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Name</th>
							<th>Email</th>
							<th style="width:160px">Registered</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $learners_page_rows as $user_id ) :
							$user = get_userdata( $user_id );
							if ( ! $user ) continue;
						?>
						<tr>
							<td><?php echo esc_html( $user->display_name ); ?></td>
							<td><?php echo esc_html( $user->user_email ); ?></td>
							<td><?php echo esc_html( date( 'M j, Y', strtotime( $user->user_registered ) ) ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				</div>
				<?php if ( $learners_pages > 1 ) : ?>
					<div style="display:flex;align-items:center;justify-content:center;gap:12px;margin-top:14px;font-size:13px;color:#666">
						<?php if ( $learners_paged > 1 ) : ?>
							<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'learner_paged', $learners_paged - 1 ) ); ?>">&larr; Prev</a>
						<?php else : ?>
							<span class="button button-small" style="opacity:.4;cursor:not-allowed">&larr; Prev</span>
						<?php endif; ?>
						<span>Page <?php echo (int) $learners_paged; ?> of <?php echo (int) $learners_pages; ?></span>
						<?php if ( $learners_paged < $learners_pages ) : ?>
							<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'learner_paged', $learners_paged + 1 ) ); ?>">Next &rarr;</a>
						<?php else : ?>
							<span class="button button-small" style="opacity:.4;cursor:not-allowed">Next &rarr;</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	private static function status_badge( string $status ): string {
		$colors = array(
			'active'   => array( '#0a7d2f', '#e6f6ea' ),
			'expiring' => array( '#8a6100', '#fff4e0' ),
			'expired'  => array( '#a91616', '#fbe8e8' ),
			'none'     => array( '#666',    '#f0f0f1' ),
		);
		$labels = array(
			'active'   => 'Active',
			'expiring' => 'Expiring Soon',
			'expired'  => 'Expired',
			'none'     => 'Not Active',
		);
		[ $fg, $bg ] = $colors[ $status ] ?? $colors['none'];
		$label = $labels[ $status ] ?? ucfirst( $status );
		return '<span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;color:' . esc_attr( $fg ) . ';background:' . esc_attr( $bg ) . '">' . esc_html( $label ) . '</span>';
	}
}
