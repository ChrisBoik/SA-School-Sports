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
		if (isset($instance['title'])) {
			$name = $instance['title'] . ': ' . $name;

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
	.widget-button>span{
		display: inline-block;width: 20px;height: 20px;line-height: 1;vertical-align: middle;margin: 0 2px;
	}
	.widget-button.drag-widget-button .dashicons{
		font-size: 16px;
	}
	.drag-widget-button{
		cursor: grab;
	}
	.drag-widget-button:active{
		cursor: grabbing;
	}
	.move-widget-button[disabled],
	.move-widget-button[aria-disabled="true"]{
		opacity: 0.35;
		cursor: not-allowed;
		pointer-events: none;
	}
	.custom-widget-container.is-dragging .sass-media-widget{
		outline: 2px dashed #2271b1;
		background: rgba(34, 113, 177, 0.08);
	}
	.sass-widget-placeholder{
		border: 2px dashed #2271b1;
		background: rgba(34, 113, 177, 0.08);
		min-height: 80px;
		margin: 10px 0;
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
<div class="custom-widget-container wp-core-ui" data-widget-id="<?php echo esc_attr( $this->id ); ?>" data-sidebar-id="<?php echo esc_attr( $args['id'] ?? '' ); ?>" data-widget-instance-id="<?php echo esc_attr( $args['widget_id'] ?? '' ); ?>">
	<a target="_blank" rel="noopener" class="sass-media-widget<?php echo !empty($widget_media_uri) ? ' image-selected' : ''?>" data-sass-media-widget-title="<?php echo esc_attr( $title ); ?>"
	   <?php
		echo user_can( $current_user, 'administrator' ) ? (is_customize_preview() ? null : "href='$esc_link'") : "href='$esc_advertiser_uri'"
	   ?>
	   >
		<?php if (str_ends_with($widget_media_uri, '.webm') || str_ends_with($widget_media_uri, '.mp4')) {
		?>
		<video loop autoplay muted style="grid-area: 1 / 1; width: 100%;">
			<source src="<?php echo esc_attr( $widget_media_uri ); ?>" type="video/webm">
			<source src="<?php echo esc_attr( $widget_media_uri ); ?>" type="video/mp4">
			Your browser does not support the video tag.
		</video>
				<?php	
		}	
		?>
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
		$media_uri = ! empty( $instance['media_uri'] ) ? $instance['media_uri'] : __( '', 'default' );
		$position_description = ! empty( $instance['position_description'] ) ? $instance['position_description'] : __( '', 'default' );
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( '', 'default' );
		$media_aspect_ratio = ! empty( $instance['media_aspect_ratio'] ) ? $instance['media_aspect_ratio'] : __( '', 'default' );
		$advertiser_uri = ! empty( $instance['advertiser_uri'] ) ? $instance['advertiser_uri'] : __( '', 'default' );
		
		$widget_identifier = $this->get_field_name('');
		$widget_identifier = str_replace( '[]', '', $widget_identifier );
		$widget_unique_id = wp_generate_uuid4();
?>
<div class="custom-media-widget-form" data-widget-id="<?php echo esc_attr( $this->id ); ?>" data-widget-unique-id="<?php echo esc_attr( $widget_unique_id ); ?>">
	<p>
		<button class="button select-media-button" type="button">
			<span class="dashicons-admin-media dashicons-before"></span>
			Select Media
		</button>
		<!-- 		<button type="button" class="button insert-media add_media"><span class="wp-media-buttons-icon"></span> Add media</button>		 -->
	</p>
	<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'default' ); ?></label> 
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" placeholder="e.g. Sport Company Inc.">
	</p>
	<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'position_description' ) ); ?>"><?php esc_attr_e( 'Position Description:', 'default' ); ?></label> 
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'position_description' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'position_description' ) ); ?>" type="text" value="<?php echo esc_attr( $position_description ); ?>" placeholder="e.g. RHS Default">
	</p>
	<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'advertiser_uri' ) ); ?>"><?php esc_attr_e( 'Advertiser URI:', 'default' ); ?></label> 
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'advertiser_uri' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'advertiser_uri' ) ); ?>" type="text" value="<?php echo esc_attr( $advertiser_uri ); ?>" placeholder="e.g. https://sport.co?utm_source=sass&utm_medium=banner&utm_campaign=spring_collection&utm_id=spr_col_01">
	</p>
	<br/>	
	<details>
		<summary>Advanced Settings</summary>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'media_uri' ) ); ?>"><?php esc_attr_e( 'Media URI:', 'default' ); ?></label> 
			<input class="widefat media-uri-input" id="<?php echo esc_attr( $this->get_field_id( 'media_uri' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'media_uri' ) ); ?>" type="text" value="<?php echo esc_attr( $media_uri ); ?>" placeholder="e.g. https://example.com/image.gif">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'media_aspect_ratio' ) ); ?>"><?php esc_attr_e( 'Aspect Ratio:', 'default' ); ?></label> 
			<input class="widefat media_aspect_ratio" id="<?php echo esc_attr( $this->get_field_id( 'media_aspect_ratio' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'media_aspect_ratio' ) ); ?>" type="text" value="<?php echo esc_attr( $media_aspect_ratio ); ?>" >
		</p>
	</details>
</div>

<style>
	.select-media-button>span{
		display: inline-block;width: 20px;height: 20px;line-height: 1;vertical-align: middle;margin: 0 2px;
	}
</style>
<script>
	{
		let form = document.querySelector('.custom-media-widget-form[data-widget-unique-id="<?php echo esc_attr( $widget_unique_id ); ?>"]');
		let mediaSelectButton = form.querySelector("button.select-media-button");
		mediaSelectButton.addEventListener('click', function(e) {
			openMediaWindow(form);
		});
	}
</script>
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
		wp_enqueue_script( 'custom-media-widget', plugin_dir_url( __FILE__ ) . 'custom-media-picker.js', array( 'jquery', 'customize-preview', 'customize-widgets', 'jquery-ui-sortable' ), '1.0.1', true );
	}
}
add_action( 'wp_enqueue_scripts', 'enqueue_custom_media_widget_scripts' );

function enqueue_customizer_controls_scripts() {
	wp_enqueue_media();
	wp_enqueue_script( 'my-customizer-controls', plugin_dir_url( __FILE__ ) . 'customizer.js', array( 'jquery', 'customize-controls' ), "1.0.4", true );
}
add_action( 'customize_controls_enqueue_scripts', 'enqueue_customizer_controls_scripts' );

// Enqueue device-preview visibility script inside the customizer preview iframe
function sass_enqueue_customizer_preview_scripts() {
	wp_enqueue_script(
		'sass-customizer-device-preview',
		plugin_dir_url( __FILE__ ) . 'customizer-device-preview.js',
		array( 'customize-preview' ),
		'1.0.0',
		true
	);
}
add_action( 'customize_preview_init', 'sass_enqueue_customizer_preview_scripts' );

// Also need to relay previewedDevice changes from the controls pane to the preview iframe
function sass_enqueue_customizer_controls_device_relay() {
	$inline_js = "
		(function(api) {
			console.log('[SASS Device Relay] Controls-side relay script loaded');
			api.bind('ready', function() {
				console.log('[SASS Device Relay] Customizer ready');
				if (api.previewedDevice) {
					console.log('[SASS Device Relay] previewedDevice exists, initial value:', api.previewedDevice.get());
					api.previewedDevice.bind(function(newDevice) {
						console.log('[SASS Device Relay] Device changed to:', newDevice, '— sending to preview');
						api.previewer.send('previewedDevice', newDevice);
					});
					// Send initial device on first preview load
					api.previewer.bind('ready', function() {
						console.log('[SASS Device Relay] Previewer ready — sending initial device:', api.previewedDevice.get());
						api.previewer.send('previewedDevice', api.previewedDevice.get());
					});
				} else {
					console.warn('[SASS Device Relay] api.previewedDevice does NOT exist');
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

// testing
function widgetopts_in_widget_forms( $widget, $return, $instance ){
?>
<br/>
<?php
}
add_action( 'in_widget_form', 'widgetopts_in_widget_forms', 10, 3 );


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

