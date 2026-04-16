<?php
/**
 * Plugin Name: SA School Sports
 * Description: General site customisations for SA School Sports
 * Version: 1.0
 * Author: Yard8, Christopher Boik
 * Author URI: https://yard8.co.za
 */

class Custom_Media_Widget extends WP_Widget {

	/**
     * Register widget with WordPress.
     */
	public function __construct() {
		parent::__construct(
			'custom_media_widget', // Base ID
			__( 'Custom Banner Widget', 'default' ), // Name
			array( 'description' => __( 'Displays a "Select Media" button to open the Wordpress media window', 'default' ), ) // Args
		);
		add_filter('widget_name', array($this, 'update_custom_widget_name'), 10, 3);		
	}

	function update_custom_widget_name($name, $instance, $id_base) {
		if ( ! empty( $instance['title'] ) ) {
			return $instance['title'] . ' — SASS Widget';
		}
		return $name;
	}	

	/**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
	public function widget( $args, $instance ) {
		// --- Device visibility check (skip in customizer — handled by JS) ---
		if ( ! is_customize_preview() ) {
			$device_desktop = isset( $instance['device_desktop'] ) ? $instance['device_desktop'] : '1';
			$device_tablet  = isset( $instance['device_tablet'] )  ? $instance['device_tablet']  : '1';
			$device_mobile  = isset( $instance['device_mobile'] )  ? $instance['device_mobile']  : '1';

			$is_mobile = wp_is_mobile();
			if ( $is_mobile && $device_mobile !== '1' && $device_tablet !== '1' ) {
				return;
			}
			if ( ! $is_mobile && $device_desktop !== '1' ) {
				return;
			}
		}

		// --- Date scheduling check ---
		if ( ! is_customize_preview() ) {
			$show_from = ! empty( $instance['show_from'] ) ? $instance['show_from'] : '';
			$show_to   = ! empty( $instance['show_to'] )   ? $instance['show_to']   : '';
			$now = current_time( 'Y-m-d\TH:i' );
			if ( $show_from && $now < $show_from ) {
				return;
			}
			if ( $show_to && $now > $show_to ) {
				return;
			}
		}

		// --- Taxonomy visibility check ---
		$tax_categories = ! empty( $instance['tax_categories'] ) ? (array) $instance['tax_categories'] : array();
		if ( ! empty( $tax_categories ) && ! is_customize_preview() ) {
			$tax_mode  = ! empty( $instance['tax_mode'] )  ? $instance['tax_mode']  : 'hide';
			$tax_scope = ! empty( $instance['tax_scope'] ) ? $instance['tax_scope'] : '1';

			$matches = false;
			$is_archive = is_category( $tax_categories ) || is_tag( $tax_categories );
			$is_single  = is_single() && has_category( $tax_categories );

			if ( $tax_scope === '1' ) {
				$matches = $is_archive || $is_single;
			} elseif ( $tax_scope === '2' ) {
				$matches = $is_archive;
			} elseif ( $tax_scope === '3' ) {
				$matches = $is_single;
			}

			if ( $tax_mode === 'hide' && $matches ) {
				return;
			}
			if ( $tax_mode === 'show' && ! $matches ) {
				return;
			}
		}

		$title = apply_filters( 'widget_title', $instance['title'] );

		$widget_identifier = $this->get_field_name('');
		$widget_identifier = str_replace( '[]', '', $widget_identifier );

		$field_name = $this->get_field_name("");
		$field_name = preg_replace("/\[\]$/", "", $field_name);
		$control_id = str_replace("widget-", "widget_", $field_name);

		$position_description = ! empty( $instance['position_description'] ) ? $instance['position_description'] : __( 'unset', 'default' );
		$advertiser_uri = ! empty( $instance['advertiser_uri'] ) ? $instance['advertiser_uri'] : "#";

		$sidebar_name = $args["name"];
		$sidebar_name = ! empty( $sidebar_name ) ? $sidebar_name : __( 'unknown', 'default' );

		$widget_media_uri = ! empty( $instance['media_uri'] ) ? $instance['media_uri'] : null;
		$widget_media_aspect_ratio = ! empty( $instance['media_aspect_ratio'] ) ? $instance['media_aspect_ratio'] : "300 / 150";
		?>
<style>
	
	.custom-widget-container{
		display: flow-root;
	}
	.sass-media-widget{
		width: 100%;
		min-height: 100px;
		min-width: 300px;
		display: flex;
		justify-content: center;
		align-items: center;
		float: right; margin-bottom: 20px;
		border: 1px solid #E5E5E5;
		background-repeat: no-repeat;
		position: relative;
		transition: background,border 0.2s;
	}
	/* Widgets Appearing in RHS/Sidebars */
	.td-pb-span4 .sass-media-widget{
		max-width: 300px;
		min-height: 150px;
	}
	.sass-media-widget:hover{
		background: #0000007d;
	}
	.sass-media-widget:not(.image-selected){
		border: 5px #ff8400a6 dashed;
	}		
	.sass-media-widget:not(.image-selected):hover{
		border: 5px #ff8400 dashed
	}	
	[data-widget-id="<?php echo esc_attr( $this->id ); ?>"] .sass-media-widget.image-selected{
		background-size: cover;
		min-width: unset;
		min-height: unset;
		background-image: url("<?php echo $widget_media_uri ?>");
		aspect-ratio: <?php echo $widget_media_aspect_ratio ?>;
		
		display: grid;
    	position: relative;
    	width: 100%;
	}

	.widget-button {
		opacity: 0;
		transition: opacity 0.2s ease;
	}
	.sass-media-widget:not(.image-selected) .widget-button{
		opacity: 1;
	}
	.sass-media-widget:hover .widget-button {
		opacity: 1;
	}
	.widget-button-container{
		display: flex;
		width: 100%;
		justify-content: center;
		gap: 6px;
		flex-wrap: wrap;
		grid-area: 1 / 1;
	}
	.widget-button{
		display: inline-flex;
		align-items: center;
		justify-content: center;
	}
	.widget-button>span{
		display: inline-block;width: 20px;height: 20px;line-height: 1;vertical-align: middle;margin: 0 2px;
	}
	.widget-button.drag-widget-button .dashicons{
		font-size: 16px;
	}
	/* Compact widget buttons at narrow widths (mobile/tablet customizer preview)
	   !important needed to override WP core .wp-core-ui .button which has higher specificity */
	@media (max-width: 768px) {
		.widget-button-container{
			gap: 3px;
		}
		.widget-button{
			font-size: 11px !important;
			padding: 0 6px !important;
			min-height: 24px !important;
			line-height: 24px !important;
		}
		.widget-button>span{
			width: 14px;height: 14px;
		}
		.widget-button .dashicons{
			font-size: 14px;
			width: 14px;
			height: 14px;
		}
	}
	@media (max-width: 480px) {
		.widget-button-container{
			gap: 2px;
		}
		.widget-button{
			font-size: 0 !important;
			padding: 0 5px !important;
			min-height: 22px !important;
			line-height: 22px !important;
		}
		.widget-button>span{
			width: 14px;height: 14px;margin: 0;
		}
		.widget-button .dashicons{
			font-size: 13px;
			width: 13px;
			height: 13px;
		}
	}
	.drag-widget-button{
		cursor: grab !important;
		opacity: 1 !important; /* Always visible so users can find the drag target */
	}
	.drag-widget-button:active{
		cursor: grabbing !important;
	}
	.move-widget-button[disabled],
	.move-widget-button[aria-disabled="true"]{
		opacity: 0.35;
		cursor: not-allowed;
		pointer-events: none;
	}
	/* Clone that follows the cursor: lifted look */
	.sass-widget-dragging{
		z-index: 9999 !important;
		opacity: 0.92 !important;
		transform: scale(1.02) !important;
		box-shadow: 0 12px 32px rgba(0,0,0,0.28), 0 2px 8px rgba(0,0,0,0.12) !important;
		pointer-events: none;
	}
	.sass-widget-dragging .widget-button{
		opacity: 1 !important;
	}
	/* Original widget left behind as ghost (sortable hides it — override) */
	.sass-widget-ghost{
		display: block !important;
		visibility: visible !important;
		opacity: 0.3 !important;
		outline: 2px dashed #2271b1;
		outline-offset: -2px;
	}
	.sass-widget-ghost .widget-button{
		opacity: 0 !important;
		pointer-events: none;
	}
	/* Drop zone placeholder — matches dragged widget size (set via JS) */
	.sass-widget-placeholder{
		border: 2px dashed #2271b1;
		background: rgba(34, 113, 177, 0.06);
		border-radius: 4px;
		box-sizing: border-box;
	}
	.widget-meta-info{
		position: absolute;
		padding-right: 5px;
		padding-left: 5px;

		background: #00000090;
		color: white;

		/* Overflow  */
		text-overflow: ellipsis;
		white-space: nowrap;
		max-width: 100%;
		overflow: hidden;
	}
	.widget-meta-info.top-left{
		left: 0;
		top: 0;
		max-width: 49.75%
	}
	.widget-meta-info.top-right{
		right: 0;
		top: 0;
		max-width: 49.75%
	}
	.widget-meta-info.bottom-right{
		right: 0;
		bottom: 0;
	}
	/* Compact meta labels & ensure thin banners have room for overlays at narrow widths */
	@media (max-width: 768px) {
		.widget-meta-info{
			font-size: 10px;
			line-height: 14px;
			padding-right: 3px;
			padding-left: 3px;
		}
		.sass-media-widget.image-selected{
			min-height: 40px;
		}
	}
	@media (max-width: 480px) {
		.widget-meta-info{
			font-size: 9px;
			line-height: 13px;
			padding-right: 2px;
			padding-left: 2px;
		}
		.sass-media-widget.image-selected{
			min-height: 36px;
		}
	}
	
	<?php 
			if(is_customize_preview())
			{ 
	?>
/* 	.widget_custom_media_widget{
		display: block!important;
	} */
	<?php	
			}	
	?>
	

</style>
<?php
			echo $args['before_widget'];
		$current_user = wp_get_current_user();
		
		// 			$query['autofocus[panel]'] = 'widgets';
		$query['autofocus[section]'] = "sidebar-widgets-td-$sidebar_name";
		// 			$query['autofocus[control]'] = 'widget-custom_media_widget-40-title';
		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$query['url'] = urlencode( $current_url );
		$link = add_query_arg( $query, wp_customize_url() );
		$esc_link = esc_url( $link );
		
		$esc_advertiser_uri = esc_url( $advertiser_uri );
?>
<?php
		$device_desktop = isset( $instance['device_desktop'] ) ? $instance['device_desktop'] : '1';
		$device_tablet  = isset( $instance['device_tablet'] )  ? $instance['device_tablet']  : '1';
		$device_mobile  = isset( $instance['device_mobile'] )  ? $instance['device_mobile']  : '1';
?>
<div class="custom-widget-container wp-core-ui" data-widget-id="<?php echo esc_attr( $this->id ); ?>" data-sidebar-id="<?php echo esc_attr( $args['id'] ?? '' ); ?>" data-widget-instance-id="<?php echo esc_attr( $args['widget_id'] ?? '' ); ?>" data-sass-device-desktop="<?php echo esc_attr( $device_desktop ); ?>" data-sass-device-tablet="<?php echo esc_attr( $device_tablet ); ?>" data-sass-device-mobile="<?php echo esc_attr( $device_mobile ); ?>">
	<a target="_blank" rel="noopener" class="sass-media-widget<?php echo !empty($widget_media_uri) ? ' image-selected' : ''?>" data-sass-media-widget-title="<?php echo esc_attr( $title ); ?>"
	   <?php if ( is_customize_preview() ) { echo 'draggable="false"'; } ?>
	   <?php
		echo user_can( $current_user, 'administrator' ) ? (is_customize_preview() ? null : "href='$esc_link'") : "href='$esc_advertiser_uri'"
	   ?>
	   >
		<?php
		$is_video = $widget_media_uri && ( str_ends_with( $widget_media_uri, '.webm' ) || str_ends_with( $widget_media_uri, '.mp4' ) );
		if ( $is_video ) :
			$video_type = str_ends_with( $widget_media_uri, '.webm' ) ? 'video/webm' : 'video/mp4';
		?>
		<video loop autoplay muted playsinline style="grid-area: 1 / 1; width: 100%;">
			<source src="<?php echo esc_url( $widget_media_uri ); ?>" type="<?php echo esc_attr( $video_type ); ?>">
		</video>
		<?php endif; ?>
		<?php if (user_can( $current_user, 'administrator' )) {
		?>	
		<div class="widget-button-container">
			<button type="button" class="button widget-button edit-widget-button">
				<span class="dashicons-edit dashicons-before"></span>
				Edit
			</button>		
			<?php if (is_customize_preview()) { ?>
			<button type="button" class="button widget-button drag-widget-button drag-widget-handle" title="Drag to reorder">
				<span class="dashicons-move dashicons-before"></span>
			</button>
			<button type="button" class="button widget-button move-widget-button move-widget-up-button">
				<span class="dashicons-arrow-up-alt2 dashicons-before"></span>
				Up
			</button>
			<button type="button" class="button widget-button move-widget-button move-widget-down-button">
				<span class="dashicons-arrow-down-alt2 dashicons-before"></span>
				Down
			</button>
			<button type="button" class="button widget-button remove-widget-button">
				<span class="dashicons-no-alt dashicons-before"></span>
				Remove
			</button>
			<?php } ?>
<!-- 			<button class="button widget-button analytics-widget-button" <?php echo empty($widget_media_uri) ? "disabled" : "" ?>>
				<span class="dashicons-analytics dashicons-before"></span>
				Analytics
			</button> -->
		</div>
		<?php 
		} 
		if(is_customize_preview())
		{ 
		?>		
		<span class="widget-meta-info top-left" title="<?php echo $sidebar_name ?>"><b>Area: </b><?php echo $sidebar_name; ?></span>
		<span class="widget-meta-info top-right" title="<?php echo $position_description ?>"><b>Pos </b><?php echo $position_description ?></span>
		<span class="widget-meta-info bottom-right" title="<?php echo $title ?>"><b>Title </b><?php echo $title ?></span>	
		<input type="hidden" name="sass_widget_id" value="<?php echo $control_id ?>">
		<?php	
		}	
		?>
	</a>
	<div class="selected-media"></div>
</div>
<?php
		echo $args['after_widget'];
	}

	/**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
	public function form( $instance ) {
		$media_uri = ! empty( $instance['media_uri'] ) ? $instance['media_uri'] : '';
		$position_description = ! empty( $instance['position_description'] ) ? $instance['position_description'] : '';
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$media_aspect_ratio = ! empty( $instance['media_aspect_ratio'] ) ? $instance['media_aspect_ratio'] : '';
		$advertiser_uri = ! empty( $instance['advertiser_uri'] ) ? $instance['advertiser_uri'] : '';

		// Device visibility — default to all checked (show everywhere)
		$device_desktop = isset( $instance['device_desktop'] ) ? $instance['device_desktop'] : '1';
		$device_tablet  = isset( $instance['device_tablet'] )  ? $instance['device_tablet']  : '1';
		$device_mobile  = isset( $instance['device_mobile'] )  ? $instance['device_mobile']  : '1';

		// Taxonomy rules
		$tax_mode       = ! empty( $instance['tax_mode'] )       ? $instance['tax_mode']       : 'hide';
		$tax_categories = ! empty( $instance['tax_categories'] ) ? $instance['tax_categories'] : array();
		$tax_scope      = ! empty( $instance['tax_scope'] )      ? $instance['tax_scope']      : '1';

		// Date scheduling
		$show_from = ! empty( $instance['show_from'] ) ? $instance['show_from'] : '';
		$show_to   = ! empty( $instance['show_to'] )   ? $instance['show_to']   : '';

		$widget_identifier = $this->get_field_name('');
		$widget_identifier = str_replace( '[]', '', $widget_identifier );
		$widget_unique_id = wp_generate_uuid4();
?>
<?php
	// Determine status for badge
	$now = current_time( 'Y-m-d\TH:i' );
	$is_expired  = $show_to && $now > $show_to;
	$is_scheduled = $show_from && $now < $show_from;
	$all_devices_off = $device_desktop !== '1' && $device_tablet !== '1' && $device_mobile !== '1';
	$status_class = '';
	$status_label = '';
	if ( $is_expired ) { $status_class = 'sass-status-expired'; $status_label = 'Expired'; }
	elseif ( $is_scheduled ) { $status_class = 'sass-status-scheduled'; $status_label = 'Scheduled'; }
	elseif ( $all_devices_off ) { $status_class = 'sass-status-hidden'; $status_label = 'Hidden'; }

	// Build selected categories lookup for badges
	$selected_terms = array();
	if ( ! empty( $tax_categories ) ) {
		$terms_all = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false, 'orderby' => 'name' ) );
		if ( ! is_wp_error( $terms_all ) ) {
			foreach ( $terms_all as $t ) {
				if ( in_array( (string) $t->term_id, (array) $tax_categories ) ) {
					$anc = get_ancestors( $t->term_id, 'category' );
					$path = array();
					foreach ( array_reverse( $anc ) as $aid ) {
						$a = get_term( $aid, 'category' );
						if ( ! is_wp_error( $a ) ) $path[] = $a->name;
					}
					$path[] = $t->name;
					$selected_terms[] = array( 'id' => $t->term_id, 'label' => implode( ' > ', $path ) );
				}
			}
		}
	}
?>
<div class="custom-media-widget-form <?php echo esc_attr( $status_class ); ?>" data-widget-id="<?php echo esc_attr( $this->id ); ?>" data-widget-unique-id="<?php echo esc_attr( $widget_unique_id ); ?>" data-status="<?php echo esc_attr( $status_label ); ?>">
	<?php if ( ! empty( $media_uri ) ) : ?>
	<div class="sass-widget-thumbnail <?php echo ( ! empty( $media_aspect_ratio ) && preg_match( '/(\d+\.?\d*)\s*\/\s*(\d+\.?\d*)/', $media_aspect_ratio, $m ) && $m[1] / $m[2] > 3 ) ? 'landscape-wide' : ''; ?>">
		<?php if ( str_ends_with( $media_uri, '.webm' ) || str_ends_with( $media_uri, '.mp4' ) ) : ?>
		<video muted loop autoplay playsinline src="<?php echo esc_url( $media_uri ); ?>"></video>
		<?php else : ?>
		<img src="<?php echo esc_url( $media_uri ); ?>" alt="<?php echo esc_attr( $title ); ?>">
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<div class="sass-form-row">
		<button class="button select-media-button" type="button">
			<span class="dashicons-admin-media dashicons-before"></span>
			Select Media
		</button>
	</div>

	<div class="sass-form-row">
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Title</label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" placeholder="e.g. Sport Company Inc.">
	</div>

	<div class="sass-form-row">
		<label for="<?php echo esc_attr( $this->get_field_id( 'position_description' ) ); ?>">Position</label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'position_description' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'position_description' ) ); ?>" type="text" value="<?php echo esc_attr( $position_description ); ?>" placeholder="e.g. RHS Default">
	</div>

	<div class="sass-form-row">
		<label for="<?php echo esc_attr( $this->get_field_id( 'advertiser_uri' ) ); ?>">Link URL</label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'advertiser_uri' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'advertiser_uri' ) ); ?>" type="text" value="<?php echo esc_attr( $advertiser_uri ); ?>" placeholder="https://...">
	</div>

	<details class="sass-section-details" open>
		<summary>Visibility &amp; Scheduling</summary>
		<div class="sass-section-body">
			<div class="sass-form-row">
				<label class="sass-label-inline">Devices</label>
				<div class="sass-device-toggles">
					<label class="sass-device-toggle">
						<input type="hidden" name="<?php echo esc_attr( $this->get_field_name( 'device_desktop' ) ); ?>" value="0">
						<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'device_desktop' ) ); ?>" value="1" <?php checked( $device_desktop, '1' ); ?>>
						<span class="dashicons dashicons-desktop"></span> Desktop
					</label>
					<label class="sass-device-toggle">
						<input type="hidden" name="<?php echo esc_attr( $this->get_field_name( 'device_tablet' ) ); ?>" value="0">
						<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'device_tablet' ) ); ?>" value="1" <?php checked( $device_tablet, '1' ); ?>>
						<span class="dashicons dashicons-tablet"></span> Tablet
					</label>
					<label class="sass-device-toggle">
						<input type="hidden" name="<?php echo esc_attr( $this->get_field_name( 'device_mobile' ) ); ?>" value="0">
						<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'device_mobile' ) ); ?>" value="1" <?php checked( $device_mobile, '1' ); ?>>
						<span class="dashicons dashicons-smartphone"></span> Mobile
					</label>
				</div>
			</div>
			<div class="sass-date-row">
				<div class="sass-date-field">
					<label for="<?php echo esc_attr( $this->get_field_id( 'show_from' ) ); ?>">From</label>
					<input type="date" id="<?php echo esc_attr( $this->get_field_id( 'show_from' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_from' ) ); ?>" value="<?php echo esc_attr( $show_from ); ?>">
				</div>
				<span class="sass-date-separator">&rarr;</span>
				<div class="sass-date-field">
					<label for="<?php echo esc_attr( $this->get_field_id( 'show_to' ) ); ?>">Until</label>
					<input type="date" id="<?php echo esc_attr( $this->get_field_id( 'show_to' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_to' ) ); ?>" value="<?php echo esc_attr( $show_to ); ?>">
				</div>
			</div>
		</div>
	</details>

	<details class="sass-section-details">
		<summary>
			Category Visibility
			<?php if ( ! empty( $selected_terms ) ) : ?>
			<span class="sass-summary-count"><?php echo count( $selected_terms ); ?></span>
			<?php endif; ?>
		</summary>
		<div class="sass-section-body">
			<select class="widefat sass-tax-mode" id="<?php echo esc_attr( $this->get_field_id( 'tax_mode' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'tax_mode' ) ); ?>">
				<option value="hide" <?php selected( $tax_mode, 'hide' ); ?>>Hide on selected</option>
				<option value="show" <?php selected( $tax_mode, 'show' ); ?>>Show only on selected</option>
			</select>
			<?php if ( ! empty( $selected_terms ) ) : ?>
			<div class="sass-tax-selected-badges">
				<?php foreach ( $selected_terms as $st ) : ?>
				<span class="sass-tax-badge" data-term-id="<?php echo esc_attr( $st['id'] ); ?>"><?php echo esc_html( $st['label'] ); ?> <button type="button" class="sass-tax-badge-remove" aria-label="Remove">&times;</button></span>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<input type="text" class="widefat sass-tax-search" placeholder="Search categories...">
			<div class="sass-tax-list">
				<?php
				$terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false, 'orderby' => 'name' ) );
				if ( ! is_wp_error( $terms ) ) {
					// Sort: checked first, then alphabetical
					$checked_terms = array();
					$unchecked_terms = array();
					foreach ( $terms as $term ) {
						if ( in_array( (string) $term->term_id, (array) $tax_categories ) ) {
							$checked_terms[] = $term;
						} else {
							$unchecked_terms[] = $term;
						}
					}
					$sorted_terms = array_merge( $checked_terms, $unchecked_terms );
					foreach ( $sorted_terms as $term ) {
						$ancestors = get_ancestors( $term->term_id, 'category' );
						$chain = array();
						foreach ( array_reverse( $ancestors ) as $aid ) {
							$ancestor = get_term( $aid, 'category' );
							if ( ! is_wp_error( $ancestor ) ) {
								$chain[] = $ancestor->name;
							}
						}
						$breadcrumb = ! empty( $chain ) ? implode( ' &rsaquo; ', $chain ) . ' &rsaquo; ' : '';
						$full_path = strtolower( ( ! empty( $chain ) ? implode( ' › ', $chain ) . ' › ' : '' ) . $term->name );
						$is_checked = in_array( (string) $term->term_id, (array) $tax_categories );
				?>
				<label class="sass-tax-item" data-search="<?php echo esc_attr( $full_path ); ?>" data-term-id="<?php echo esc_attr( $term->term_id ); ?>">
					<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'tax_categories' ) ); ?>[]" value="<?php echo esc_attr( $term->term_id ); ?>" <?php checked( $is_checked ); ?>>
					<?php if ( $breadcrumb ) : ?><span class="sass-tax-ancestors"><?php echo $breadcrumb; ?></span><?php endif; ?><?php echo esc_html( $term->name ); ?>
				</label>
				<?php
					}
				}
				?>
			</div>
			<select class="widefat sass-tax-scope" id="<?php echo esc_attr( $this->get_field_id( 'tax_scope' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'tax_scope' ) ); ?>">
				<option value="1" <?php selected( $tax_scope, '1' ); ?>>Archive &amp; Single posts</option>
				<option value="2" <?php selected( $tax_scope, '2' ); ?>>Archive only</option>
				<option value="3" <?php selected( $tax_scope, '3' ); ?>>Single posts only</option>
			</select>
		</div>
	</details>

	<details class="sass-section-details">
		<summary>Media &amp; Display</summary>
		<div class="sass-section-body">
			<div class="sass-form-row">
				<label for="<?php echo esc_attr( $this->get_field_id( 'media_uri' ) ); ?>">Media URI</label>
				<input class="widefat media-uri-input" id="<?php echo esc_attr( $this->get_field_id( 'media_uri' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'media_uri' ) ); ?>" type="text" value="<?php echo esc_attr( $media_uri ); ?>" placeholder="https://...">
			</div>
			<div class="sass-form-row">
				<label for="<?php echo esc_attr( $this->get_field_id( 'media_aspect_ratio' ) ); ?>">Aspect Ratio</label>
				<input class="widefat media_aspect_ratio" id="<?php echo esc_attr( $this->get_field_id( 'media_aspect_ratio' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'media_aspect_ratio' ) ); ?>" type="text" value="<?php echo esc_attr( $media_aspect_ratio ); ?>">
			</div>
		</div>
	</details>

	<details class="sass-section-details">
		<summary>Move to Sidebar</summary>
		<div class="sass-section-body">
			<select class="widefat sass-move-target">
				<option value="">— Select sidebar —</option>
				<?php
				global $wp_registered_sidebars;
				if ( ! empty( $wp_registered_sidebars ) ) {
					foreach ( $wp_registered_sidebars as $sb_id => $sb ) {
						echo '<option value="' . esc_attr( $sb_id ) . '">' . esc_html( $sb['name'] ) . '</option>';
					}
				}
				?>
			</select>
			<button type="button" class="button sass-move-btn">Move &rarr;</button>
		</div>
	</details>
</div>

<style>
	.select-media-button>span{
		display: inline-block;width: 20px;height: 20px;line-height: 1;vertical-align: middle;margin: 0 2px;
	}
</style>
<!-- Media button click handled by delegated listener in customizer.js -->
<?php 
	}

	/**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['media_uri'] = ( ! empty( $new_instance['media_uri'] ) ) ? sanitize_text_field( $new_instance['media_uri'] ) : '';

		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['position_description'] = ( ! empty( $new_instance['position_description'] ) ) ? sanitize_text_field( $new_instance['position_description'] ) : $old_instance['position_description'];

		$instance['advertiser_uri'] = ( ! empty( $new_instance['advertiser_uri'] ) ) ? sanitize_text_field( $new_instance['advertiser_uri'] ) : $old_instance['advertiser_uri'];

		$instance['media_aspect_ratio'] = ( ! empty( $new_instance['media_aspect_ratio'] ) ) ? sanitize_text_field( $new_instance['media_aspect_ratio'] ) : null;

		// Device visibility
		$instance['device_desktop'] = ! empty( $new_instance['device_desktop'] ) ? '1' : '0';
		$instance['device_tablet']  = ! empty( $new_instance['device_tablet'] )  ? '1' : '0';
		$instance['device_mobile']  = ! empty( $new_instance['device_mobile'] )  ? '1' : '0';

		// Taxonomy rules
		$instance['tax_mode'] = in_array( $new_instance['tax_mode'], array( 'hide', 'show' ) ) ? $new_instance['tax_mode'] : 'hide';
		$instance['tax_categories'] = ! empty( $new_instance['tax_categories'] ) ? array_map( 'absint', (array) $new_instance['tax_categories'] ) : array();
		$instance['tax_scope'] = in_array( $new_instance['tax_scope'], array( '1', '2', '3' ) ) ? $new_instance['tax_scope'] : '1';

		// Date scheduling
		$instance['show_from'] = ! empty( $new_instance['show_from'] ) ? sanitize_text_field( $new_instance['show_from'] ) : '';
		$instance['show_to']   = ! empty( $new_instance['show_to'] )   ? sanitize_text_field( $new_instance['show_to'] )   : '';

		return $instance;
	}

}

function register_custom_media_widget() {
	register_widget( 'Custom_Media_Widget' );
}
add_action( 'widgets_init', 'register_custom_media_widget' );

function enqueue_custom_media_widget_scripts() {
	wp_enqueue_style( 'buttons' );
	if(is_customize_preview()){
		// 		wp_enqueue_media();
		wp_enqueue_style( 'media-views' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'custom-media-widget', plugin_dir_url( __FILE__ ) . 'custom-media-picker.js', array( 'jquery', 'customize-preview', 'customize-widgets', 'jquery-ui-sortable' ), '1.2.0', true );
	}
}
add_action( 'wp_enqueue_scripts', 'enqueue_custom_media_widget_scripts' );

function enqueue_customizer_controls_scripts() {
	wp_enqueue_media();
	wp_enqueue_script( 'my-customizer-controls', plugin_dir_url( __FILE__ ) . 'customizer.js', array( 'jquery', 'customize-controls' ), "1.3.0", true );
	wp_enqueue_style( 'sass-widget-form', plugin_dir_url( __FILE__ ) . 'widget-form.css', array(), '1.3.0' );
}
add_action( 'customize_controls_enqueue_scripts', 'enqueue_customizer_controls_scripts' );

// Enqueue device-preview visibility script inside the customizer preview iframe
function sass_enqueue_customizer_preview_scripts() {
	wp_enqueue_script(
		'sass-customizer-device-preview',
		plugin_dir_url( __FILE__ ) . 'customizer-device-preview.js',
		array( 'customize-preview' ),
		'1.0.2',
		true
	);
}
add_action( 'customize_preview_init', 'sass_enqueue_customizer_preview_scripts' );

// Also need to relay previewedDevice changes from the controls pane to the preview iframe
function sass_enqueue_customizer_controls_device_relay() {
	$inline_js = "
		(function(api) {
			api.bind('ready', function() {
				if (api.previewedDevice) {
					api.previewedDevice.bind(function(newDevice) {
						api.previewer.send('previewedDevice', newDevice);
					});
					api.previewer.bind('ready', function() {
						api.previewer.send('previewedDevice', api.previewedDevice.get());
					});
				}
			});
		})(wp.customize);
	";
	wp_add_inline_script( 'my-customizer-controls', $inline_js, 'after' );
}
add_action( 'customize_controls_enqueue_scripts', 'sass_enqueue_customizer_controls_device_relay' );

// In the customizer preview, bypass only Widget Options' device checks so our JS can
// toggle device visibility. All other Widget Options rules (taxonomy, page, role,
// custom logic) continue to apply normally.
//
// Widget Options uses two suppression paths:
//   1. sidebars_widgets filter — calls widgetopts_display_callback() directly and unsets
//      widgets from sidebar arrays before dynamic_sidebar runs. We remove this so
//      device-hidden widgets stay in the DOM; Widget Options' widget_display_callback
//      at priority 50 still re-applies all non-device rules correctly.
//   2. widget_display_callback (priority 50) — we neutralise only the device sub-filters
//      (widget_options_devices_mobile / widget_options_devices_desktop) so it skips the
//      device block but continues to evaluate taxonomy/page/role/logic checks.
// In the customizer preview, bypass only Widget Options' device checks so our JS
// can toggle device visibility. All other rules (taxonomy, page, role, logic) keep
// working normally.
//
// The challenge: Widget Options has two suppression paths and both must be handled:
//
//   A) sidebars_widgets filter — calls widgetopts_display_callback() *directly*,
//      so adding filters on widget_options_devices_* won't intercept it. We keep
//      this filter active so taxonomy/page rules still strip widgets from the array,
//      but we neutralise its device decision by hooking the device sub-filters before
//      sidebars_widgets calls widgetopts_display_callback().
//
//   B) widget_display_callback filter (priority 50) — same sub-filters apply here too.
//
// Both paths call apply_filters('widget_options_devices_mobile/desktop', $hidden)
// internally, so forcing those to false is enough to skip device suppression in both.
function sass_bypass_widgetopts_device_checks_in_customizer() {
	if ( ! is_customize_preview() ) {
		return;
	}
	// Neutralise device sub-filters — both sidebars_widgets and widget_display_callback
	// call apply_filters('widget_options_devices_mobile/desktop') internally, so
	// returning false here keeps $hidden=false and skips device-based suppression
	// while all other visibility checks (taxonomy, page, role, logic) still run.
	add_filter( 'widget_options_devices_mobile', '__return_false', 99 );
	add_filter( 'widget_options_devices_desktop', '__return_false', 99 );
}
add_action( 'wp_loaded', 'sass_bypass_widgetopts_device_checks_in_customizer', 99 );

// Allow in_category() in Widget Options' PHP logic rules.
// Widget Options restricts which PHP functions can be used in its "Logic" field
// but doesn't include in_category() by default.
add_filter( 'widgetopts_allowed_php_functions', function( $functions ) {
	return array_merge( $functions, array( 'in_category' ) );
});


// Featured image archive view clickable
function wpb_autolink_featured_images( $html, $post_id, $post_image_id ) {
	if (!is_singular()) { 
		$html = '<a class="sass-post-image-wrapper" style="min-width: max-content;align-content: center;" href="' . get_permalink( $post_id ) . '" title="' . esc_attr( get_the_title( $post_id ) ) . '">' . $html . '</a>';
		return $html;
	} else { 
		return $html;
	}
}
add_filter( 'post_thumbnail_html', 'wpb_autolink_featured_images', 10, 3 );


//
//
//
//  INLINE LOGO SHORTCODE
//
//
//

// Enqueue JS for repeater UI + media uploader
function lsv_customize_scripts($hook) {
//     if ($hook !== 'customize.php') return;
    wp_enqueue_media();
    wp_enqueue_script('lsv-customizer', plugin_dir_url( __FILE__ ) . 'lsv-customizer.js', ['jquery', 'customize-controls'], null, true);
}
add_action('customize_controls_enqueue_scripts', 'lsv_customize_scripts');

// Shortcode handler
add_shortcode('logo_tag', function($atts, $content = '') {
    $atts = shortcode_atts(['name'=>''], $atts);
    $name  = sanitize_title($atts['name']);
    if (!$name) return '';

    $data = json_decode(get_theme_mod('lsv_data', '[]'), true);
    if (!is_array($data)) return '';

    foreach ($data as $item) {
        if (sanitize_title($item['name'] ?? '') === $name) {
            $fallback = sanitize_text_field($item['fallback'] ?? '');
            $img      = esc_url($item['image_url'] ?? '');
            $css      = sanitize_text_field($item['css_class'] ?? '');
            $located  = (array) ($item['apply_in'] ?? []);
            $user_is_bot = is_admin() || wp_doing_ajax() || is_feed()
                || (!empty($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|spider|slurp/i', $_SERVER['HTTP_USER_AGENT']));

            // Determine text content or fallback
            $text = trim($content ?: $fallback);

            if ($user_is_bot) {
                return esc_html($text);
            }
            if (!$img) return esc_html($text);
            $class_attr = $css ? ' class="'.esc_attr($css).'"' : '';
            return esc_html($text) . ' <img src="'.$img.'" alt="'.esc_attr($text).'"'.$class_attr.' style="vertical-align:middle;display:inline;margin-bottom: 0;" />';
        }
    }
    return '';
});

// Context filter application
foreach (['content', 'titles', 'menus', 'widgets'] as $loc) {
    $hooks = [
        'content' => 'the_content',
        'titles' => 'the_title',
        'menus' => 'wp_nav_menu_items',
        'widgets' => 'widget_text',
    ];

    $hook = isset($hooks[$loc]) ? $hooks[$loc] : null;

    if ($hook) {
    add_filter($hook, function($text) use ($loc) {
        $data = json_decode(get_theme_mod('lsv_data', '[]'), true);
        if (!is_array($data)) return $text;
        foreach ($data as $item) {
            if (in_array($loc, (array)($item['apply_in'] ?? []), true)) {
                return do_shortcode($text);
            }
        }
        return $text;
    });
    }
}

// Customizer repeater control class
add_action('customize_register', function($wp_customize) {
    // Define your custom control class if needed
//     if (class_exists('WP_Customize_Control')) {
	class LSV_Repeater_Control extends WP_Customize_Control {
		public $type = 'repeater';
		public function render_content() {
			?>
			<label><strong><?php echo esc_html($this->label); ?></strong></label>
			<div class="lsv-repeater-container"></div>
			<button type="button" class="button lsv-add-item">Add Logo Shortcode</button>
			<input type="hidden" id="<?php echo esc_attr($this->id); ?>" value="<?php echo esc_attr($this->value()); ?>" <?php $this->link(); ?>>
			<p class="description">Add entries for each logo shortcode.</p>
			<template class="lsv-item-template">
				<div class="lsv-item">
					<hr>
					<p><label>Name: <input type="text" name="name" /></label></p>
					<p><label>Image: <img class="preview" style="max-height:60px;display:block;"><button class="upload button">Select</button><input type="hidden" name="image_url" /></label></p>
					<p><label>Fallback: <input type="text" name="fallback" /></label></p>
					<p><label>CSS class: <input type="text" name="css_class" /></label></p>
					<p>Apply in:<br>
						<label><input type="checkbox" name="apply_in[]" value="content"> Content</label>
						<label><input type="checkbox" name="apply_in[]" value="titles"> Titles</label>
						<label><input type="checkbox" name="apply_in[]" value="menus"> Menus</label>
						<label><input type="checkbox" name="apply_in[]" value="widgets"> Widgets</label>
					</p>
					<button type="button" class="button lsv-remove-item">Remove</button>
				</div>
			</template>
			<?php
		}
// 	}
	}
});

// Register setting
function lsv_customize_register($wp_customize) {
    $wp_customize->add_section('logo_shortcodes', [
        'title'    => 'Logo Shortcodes',
        'priority' => 160,
    ]);
    $wp_customize->add_setting('lsv_data', [
        'default'           => json_encode([]),
        'sanitize_callback' => 'wp_kses_post',
    ]);
	
    $wp_customize->add_control(new LSV_Repeater_Control($wp_customize, 'lsv_data', [
        'label'    => __('Logo Shortcodes', 'lsv'),
        'section'  => 'logo_shortcodes',
        'settings' => 'lsv_data',
    ]));
}
add_action('customize_register', 'lsv_customize_register');

