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
	}

	.widget-button {
		opacity: 0;
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
		justify-content: space-around;
	}
	.widget-button>span{
		display: inline-block;width: 20px;height: 20px;line-height: 1;vertical-align: middle;margin: 0 2px;
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
<div class="custom-widget-container wp-core-ui" data-widget-id="<?php echo esc_attr( $this->id ); ?>">
	<a target="_blank" rel="noopener" class="sass-media-widget<?php echo !empty($widget_media_uri) ? ' image-selected' : ''?>" data-sass-media-widget-title="<?php echo esc_attr( $title ); ?>"
	   <?php
		echo user_can( $current_user, 'administrator' ) ? (is_customize_preview() ? null : "href='$esc_link'") : "href='$esc_advertiser_uri'"
	   ?>
	   >
		<?php if (user_can( $current_user, 'administrator' )) {
		?>	
		<div class="widget-button-container">
			<button class="button widget-button edit-widget-button">
				<span class="dashicons-edit dashicons-before"></span>
				Edit
			</button>		
			<button class="button widget-button analytics-widget-button" <?php echo empty($widget_media_uri) ? "disabled" : "" ?>>
				<span class="dashicons-analytics dashicons-before"></span>
				Analytics
			</button>
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
		wp_enqueue_script( 'custom-media-widget', plugin_dir_url( __FILE__ ) . 'custom-media-picker.js', array( 'jquery', 'customize-preview', 'customize-widgets' ), '1.0.0', true );
	}
}
add_action( 'wp_enqueue_scripts', 'enqueue_custom_media_widget_scripts' );

function enqueue_customizer_controls_scripts() {
	wp_enqueue_media();
	wp_enqueue_script( 'my-customizer-controls', plugin_dir_url( __FILE__ ) . 'customizer.js', array( 'jquery', 'customize-controls' ), "1.0.3", true );
}
add_action( 'customize_controls_enqueue_scripts', 'enqueue_customizer_controls_scripts' );

// testing
function widgetopts_in_widget_forms( $widget, $return, $instance ){
?>
<br/>
<?php
}
add_action( 'in_widget_form', 'widgetopts_in_widget_forms', 10, 3 );


