<?php
/**
 * Template: Availability creation wizard (3 steps).
 *
 * @var array $templateData  Provided by \CommonsBooking\View\Admin\AvailabilityWizard::index().
 */
global $templateData;

$items             = $templateData['items'] ?? [];
$locations         = $templateData['locations'] ?? [];
$timeframeTypes    = $templateData['timeframeTypes'] ?? [];
$repetitionOptions = $templateData['repetitionOptions'] ?? [];
$gridOptions       = $templateData['gridOptions'] ?? [];
?>
<div class="wrap">
	<h1><?php echo esc_html__( 'Add Availability', 'commonsbooking' ); ?></h1>

	<!-- Progress indicator -->
	<div id="cb-wizard-progress" class="cb-wizard-progress" aria-label="<?php echo esc_attr__( 'Wizard progress', 'commonsbooking' ); ?>">
		<span class="cb-wizard-step-indicator cb-wizard-active" data-step="1">
			<?php echo esc_html__( '1. Item', 'commonsbooking' ); ?>
		</span>
		<span class="cb-wizard-separator">&rsaquo;</span>
		<span class="cb-wizard-step-indicator" data-step="2">
			<?php echo esc_html__( '2. Location', 'commonsbooking' ); ?>
		</span>
		<span class="cb-wizard-separator">&rsaquo;</span>
		<span class="cb-wizard-step-indicator" data-step="3">
			<?php echo esc_html__( '3. Timeframe', 'commonsbooking' ); ?>
		</span>
	</div>

	<!-- Global error/success notice -->
	<div id="cb-wizard-notice" class="notice" style="display:none;" role="alert"></div>

	<form id="cb-availability-wizard-form" novalidate>

		<!-- ===================== STEP 1: Item ===================== -->
		<div class="cb-wizard-step" id="cb-wizard-step-1">
			<div class="cb_welcome-panel">
				<div class="cb_welcome-panel-content">
					<h2><?php echo esc_html__( 'Step 1: Choose an Item', 'commonsbooking' ); ?></h2>

					<p class="description">
						<?php echo esc_html__( 'Select an existing item or create a new one.', 'commonsbooking' ); ?>
					</p>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="cb-wizard-item-select">
									<?php echo esc_html__( 'Item', 'commonsbooking' ); ?>
								</label>
							</th>
							<td>
								<select id="cb-wizard-item-select" name="item_id">
									<option value=""><?php echo esc_html__( '— Select existing item —', 'commonsbooking' ); ?></option>
									<?php foreach ( $items as $item ) : ?>
										<option value="<?php echo (int) $item->ID; ?>">
											<?php echo esc_html( $item->post_title ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								&nbsp;
								<button type="button" id="cb-wizard-item-toggle-create" class="button button-secondary">
									<?php echo esc_html__( 'Create new item', 'commonsbooking' ); ?>
								</button>
							</td>
						</tr>
					</table>

					<!-- Inline item creation form -->
					<div id="cb-wizard-item-create-form" class="cb-wizard-inline-create" style="display:none;">
						<h3><?php echo esc_html__( 'New Item', 'commonsbooking' ); ?></h3>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">
									<label for="cb-wizard-item-title">
										<?php echo esc_html__( 'Item Name', 'commonsbooking' ); ?>
										<span class="required" aria-hidden="true">*</span>
									</label>
								</th>
								<td>
									<input type="text"
										id="cb-wizard-item-title"
										name="new_item_title"
										class="regular-text"
										placeholder="<?php echo esc_attr__( 'Enter item name', 'commonsbooking' ); ?>"
									>
								</td>
							</tr>
						</table>
						<p>
							<button type="button" id="cb-wizard-item-create-submit" class="button button-primary">
								<?php echo esc_html__( 'Create Item', 'commonsbooking' ); ?>
							</button>
							<span id="cb-wizard-item-create-spinner" class="spinner" style="float:none;"></span>
							<span id="cb-wizard-item-create-error" class="cb-wizard-inline-error" style="display:none; color:red;"></span>
						</p>
					</div><!-- /cb-wizard-item-create-form -->

				</div><!-- /cb_welcome-panel-content -->
			</div><!-- /cb_welcome-panel -->

			<p class="submit">
				<button type="button" id="cb-wizard-step1-next" class="button button-primary">
					<?php echo esc_html__( 'Next', 'commonsbooking' ); ?>
				</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=cb-availability' ) ); ?>" class="button button-secondary">
					<?php echo esc_html__( 'Cancel', 'commonsbooking' ); ?>
				</a>
			</p>
		</div><!-- /cb-wizard-step-1 -->

		<!-- ===================== STEP 2: Location ===================== -->
		<div class="cb-wizard-step" id="cb-wizard-step-2" style="display:none;">
			<div class="cb_welcome-panel">
				<div class="cb_welcome-panel-content">
					<h2><?php echo esc_html__( 'Step 2: Choose a Location', 'commonsbooking' ); ?></h2>

					<p class="description">
						<?php echo esc_html__( 'Select an existing location or create a new one.', 'commonsbooking' ); ?>
					</p>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="cb-wizard-location-select">
									<?php echo esc_html__( 'Location', 'commonsbooking' ); ?>
								</label>
							</th>
							<td>
								<select id="cb-wizard-location-select" name="location_id">
									<option value=""><?php echo esc_html__( '— Select existing location —', 'commonsbooking' ); ?></option>
									<?php foreach ( $locations as $location ) : ?>
										<option value="<?php echo (int) $location->ID; ?>">
											<?php echo esc_html( $location->post_title ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								&nbsp;
								<button type="button" id="cb-wizard-location-toggle-create" class="button button-secondary">
									<?php echo esc_html__( 'Create new location', 'commonsbooking' ); ?>
								</button>
							</td>
						</tr>
					</table>

					<!-- Inline location creation form -->
					<div id="cb-wizard-location-create-form" class="cb-wizard-inline-create" style="display:none;">
						<h3><?php echo esc_html__( 'New Location', 'commonsbooking' ); ?></h3>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">
									<label for="cb-wizard-location-title">
										<?php echo esc_html__( 'Location Name', 'commonsbooking' ); ?>
										<span class="required" aria-hidden="true">*</span>
									</label>
								</th>
								<td>
									<input type="text"
										id="cb-wizard-location-title"
										name="new_location_title"
										class="regular-text"
										placeholder="<?php echo esc_attr__( 'Enter location name', 'commonsbooking' ); ?>"
									>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="cb-wizard-location-street">
										<?php echo esc_html__( 'Street', 'commonsbooking' ); ?>
									</label>
								</th>
								<td>
									<input type="text"
										id="cb-wizard-location-street"
										name="new_location_street"
										class="regular-text"
										placeholder="<?php echo esc_attr__( 'Street and number', 'commonsbooking' ); ?>"
									>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="cb-wizard-location-postcode">
										<?php echo esc_html__( 'Postcode', 'commonsbooking' ); ?>
									</label>
								</th>
								<td>
									<input type="text"
										id="cb-wizard-location-postcode"
										name="new_location_postcode"
										class="small-text"
										placeholder="<?php echo esc_attr__( 'Postcode', 'commonsbooking' ); ?>"
									>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="cb-wizard-location-city">
										<?php echo esc_html__( 'City', 'commonsbooking' ); ?>
									</label>
								</th>
								<td>
									<input type="text"
										id="cb-wizard-location-city"
										name="new_location_city"
										class="regular-text"
										placeholder="<?php echo esc_attr__( 'City', 'commonsbooking' ); ?>"
									>
								</td>
							</tr>
						</table>
						<p>
							<button type="button" id="cb-wizard-location-create-submit" class="button button-primary">
								<?php echo esc_html__( 'Create Location', 'commonsbooking' ); ?>
							</button>
							<span id="cb-wizard-location-create-spinner" class="spinner" style="float:none;"></span>
							<span id="cb-wizard-location-create-error" class="cb-wizard-inline-error" style="display:none; color:red;"></span>
						</p>
					</div><!-- /cb-wizard-location-create-form -->

				</div><!-- /cb_welcome-panel-content -->
			</div><!-- /cb_welcome-panel -->

			<p class="submit">
				<button type="button" id="cb-wizard-step2-back" class="button button-secondary">
					<?php echo esc_html__( 'Back', 'commonsbooking' ); ?>
				</button>
				<button type="button" id="cb-wizard-step2-next" class="button button-primary">
					<?php echo esc_html__( 'Next', 'commonsbooking' ); ?>
				</button>
			</p>
		</div><!-- /cb-wizard-step-2 -->

		<!-- ===================== STEP 3: Timeframe details ===================== -->
		<div class="cb-wizard-step" id="cb-wizard-step-3" style="display:none;">
			<div class="cb_welcome-panel">
				<div class="cb_welcome-panel-content">
					<h2><?php echo esc_html__( 'Step 3: Timeframe Details', 'commonsbooking' ); ?></h2>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="cb-wizard-tf-title">
									<?php echo esc_html__( 'Title', 'commonsbooking' ); ?>
									<span class="required" aria-hidden="true">*</span>
								</label>
							</th>
							<td>
								<input type="text"
									id="cb-wizard-tf-title"
									name="tf_title"
									class="regular-text"
									placeholder="<?php echo esc_attr__( 'Timeframe title', 'commonsbooking' ); ?>"
								>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cb-wizard-tf-type">
									<?php echo esc_html__( 'Type', 'commonsbooking' ); ?>
								</label>
							</th>
							<td>
								<select id="cb-wizard-tf-type" name="tf_type">
									<?php foreach ( $timeframeTypes as $typeId => $typeLabel ) : ?>
										<option value="<?php echo (int) $typeId; ?>">
											<?php echo esc_html( $typeLabel ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cb-wizard-tf-start-date">
									<?php echo esc_html__( 'Start Date', 'commonsbooking' ); ?>
									<span class="required" aria-hidden="true">*</span>
								</label>
							</th>
							<td>
								<input type="date"
									id="cb-wizard-tf-start-date"
									name="tf_start_date"
									class="regular-text"
								>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cb-wizard-tf-end-date">
									<?php echo esc_html__( 'End Date', 'commonsbooking' ); ?>
								</label>
							</th>
							<td>
								<input type="date"
									id="cb-wizard-tf-end-date"
									name="tf_end_date"
									class="regular-text"
								>
								<p class="description">
									<?php echo esc_html__( 'Leave blank for an open-ended timeframe.', 'commonsbooking' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php echo esc_html__( 'Full Day', 'commonsbooking' ); ?>
							</th>
							<td>
								<label for="cb-wizard-tf-full-day">
									<input type="checkbox"
										id="cb-wizard-tf-full-day"
										name="tf_full_day"
										value="on"
									>
									<?php echo esc_html__( 'Users select whole days only (no specific time slots).', 'commonsbooking' ); ?>
								</label>
							</td>
						</tr>
						<tr id="cb-wizard-tf-time-row">
							<th scope="row">
								<?php echo esc_html__( 'Time Slot', 'commonsbooking' ); ?>
							</th>
							<td>
								<label for="cb-wizard-tf-start-time">
									<?php echo esc_html__( 'Start time', 'commonsbooking' ); ?>
								</label>
								<input type="time"
									id="cb-wizard-tf-start-time"
									name="tf_start_time"
									class="small-text"
								>
								&nbsp;&nbsp;
								<label for="cb-wizard-tf-end-time">
									<?php echo esc_html__( 'End time', 'commonsbooking' ); ?>
								</label>
								<input type="time"
									id="cb-wizard-tf-end-time"
									name="tf_end_time"
									class="small-text"
								>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cb-wizard-tf-repetition">
									<?php echo esc_html__( 'Repetition', 'commonsbooking' ); ?>
								</label>
							</th>
							<td>
								<select id="cb-wizard-tf-repetition" name="tf_repetition">
									<?php foreach ( $repetitionOptions as $repKey => $repLabel ) : ?>
										<option value="<?php echo esc_attr( $repKey ); ?>">
											<?php echo esc_html( $repLabel ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cb-wizard-tf-grid">
									<?php echo esc_html__( 'Grid', 'commonsbooking' ); ?>
								</label>
							</th>
							<td>
								<select id="cb-wizard-tf-grid" name="tf_grid">
									<?php foreach ( $gridOptions as $gridKey => $gridLabel ) : ?>
										<option value="<?php echo esc_attr( $gridKey ); ?>">
											<?php echo esc_html( $gridLabel ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					</table>

				</div><!-- /cb_welcome-panel-content -->
			</div><!-- /cb_welcome-panel -->

			<p class="submit">
				<button type="button" id="cb-wizard-step3-back" class="button button-secondary">
					<?php echo esc_html__( 'Back', 'commonsbooking' ); ?>
				</button>
				<button type="submit" id="cb-wizard-step3-submit" class="button button-primary">
					<?php echo esc_html__( 'Create Availability', 'commonsbooking' ); ?>
				</button>
				<span id="cb-wizard-submit-spinner" class="spinner" style="float:none;"></span>
			</p>
		</div><!-- /cb-wizard-step-3 -->

	</form><!-- /cb-availability-wizard-form -->
</div><!-- /wrap -->
