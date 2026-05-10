<?php
/**
 * Template: Availability list view (combined Timeframe + Item + Location).
 *
 * @var array $templateData  Provided by \CommonsBooking\View\Admin\AvailabilityView::index().
 */
global $templateData;
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo esc_html__( 'Availability', 'commonsbooking' ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=cb-availability-wizard' ) ); ?>" class="page-title-action">
		<?php echo esc_html__( 'Add New', 'commonsbooking' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( empty( $templateData['timeframes'] ) ) : ?>
		<p><?php echo esc_html__( 'No timeframes found. Click "Add New" to create your first availability.', 'commonsbooking' ); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col" class="manage-column column-title column-primary">
						<?php echo esc_html__( 'Timeframe Name', 'commonsbooking' ); ?>
					</th>
					<th scope="col" class="manage-column">
						<?php echo esc_html__( 'Item', 'commonsbooking' ); ?>
					</th>
					<th scope="col" class="manage-column">
						<?php echo esc_html__( 'Location', 'commonsbooking' ); ?>
					</th>
					<th scope="col" class="manage-column">
						<?php echo esc_html__( 'Start Date', 'commonsbooking' ); ?>
					</th>
					<th scope="col" class="manage-column">
						<?php echo esc_html__( 'End Date', 'commonsbooking' ); ?>
					</th>
					<th scope="col" class="manage-column">
						<?php echo esc_html__( 'Status', 'commonsbooking' ); ?>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $templateData['timeframes'] as $row ) : ?>
					<?php
					$tf            = $row['timeframe'];
					$item          = $row['item'];
					$location      = $row['location'];
					$editUrl       = get_edit_post_link( $tf->ID );
					$deleteUrl     = get_delete_post_link( $tf->ID );
					$itemTitle     = $item ? esc_html( $item->post_title ) : '&mdash;';
					$locationTitle = $location ? esc_html( $location->post_title ) : '&mdash;';
					?>
					<tr id="cb-availability-row-<?php echo (int) $tf->ID; ?>">
						<td class="column-title column-primary">
							<strong>
								<a href="<?php echo esc_url( $editUrl ); ?>">
									<?php echo esc_html( $tf->post_title ); ?>
								</a>
							</strong>
							<div class="row-actions">
								<span class="edit">
									<a href="<?php echo esc_url( $editUrl ); ?>">
										<?php echo esc_html__( 'Edit', 'commonsbooking' ); ?>
									</a>
								</span>
								<?php if ( $deleteUrl ) : ?>
									&nbsp;|&nbsp;
									<span class="trash">
										<a href="<?php echo esc_url( $deleteUrl ); ?>" class="submitdelete">
											<?php echo esc_html__( 'Delete', 'commonsbooking' ); ?>
										</a>
									</span>
								<?php endif; ?>
							</div>
						</td>
						<td><?php echo $itemTitle; ?></td>
						<td><?php echo $locationTitle; ?></td>
						<td><?php echo esc_html( $row['start_date'] ); ?></td>
						<td><?php echo esc_html( $row['end_date'] ); ?></td>
						<td><?php echo esc_html( $tf->post_status ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
