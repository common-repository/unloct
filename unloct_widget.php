<?php

/**
 * Unloct Widget
 *
 * @description: The Unloct Widget
 * @since      : 2.0.0
 */

class Unloct_Widget extends WP_Widget {

    function __construct() {
 
        parent::__construct(
            'my-text',  // Base ID
            'Unloct',   // Name
			array( 'description' => __( 'Place this widget in your sidebar so that Unloct subscribers can log into your website. Everytime they login, you get paid. Everyone wins. Visit Unloct.com for more.', 'text_domain' ), ) // Args
        );
		
        add_action( 'widgets_init', function() {
            register_widget( 'My_Widget' );
        });
 
    }
 
    public $args = array(
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
        'before_widget' => '<div class="widget-wrap">',
        'after_widget'  => '</div></div>'
    );
 
    public function widget( $args, $instance ) {
 
        echo $args['before_widget'];
		
		echo '<a href=' . '"' . 'https://unloct.com/oauth/authorize?client_id=' . $instance['app_id'] . '&response_type=code&scope=*&redirect_uri=' . html_entity_decode(wp_login_url()) . '"' . ' target=' . '"' . '_blank' . '"' . '><img src=' . '"' . plugin_dir_url( __FILE__ ) . 'img/widget' . $instance['logo_color'] . '.png' . '"' . '></a>';
       
	    //echo '<div class="textwidget">';
 
        //echo esc_html__( $instance['app_loginurl'], 'text_domain' );
		
        //echo '</div>';
 
        echo $args['after_widget'];
 
    }
 
    public function form( $instance ) {
 
        $app_id = ! empty( $instance['app_id'] ) ? $instance['app_id'] : esc_html__( '', 'text_domain' );
		$logo_color = ! empty( $instance['logo_color'] ) ? $instance['logo_color'] : esc_html__( '', 'logo_color' );
        //$app_loginurl = ! empty( $instance['app_loginurl'] ) ? $instance['app_loginurl'] : esc_html__( '', 'text_domain' );
        ?>
        <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'app_id' ) ); ?>"><?php echo esc_html__( 'Enter the Client ID of your website. Your Client ID gets generated once you register a free account at Unloct.com (no billing info required as we do not make money from creators, only the subscribers pay a monthly fee). Then login with that free account on Unloct.com and click Creators->Register App in the menu bar. Your Client ID will be displayed alongside your Client Secret. You should also enter that Client ID and Client Secret in the Unloct Settings in your admin panel once you log in as the admin of your WordPress website. Enter your Client ID here:', 'text_domain' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'app_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'app_id') ); ?>" type="text" value="<?php echo esc_attr( $app_id ); ?>">
        </p>
		<p>This widget will display an image in your sidebar that your subscribers fan click to be redirected to Unloct.com so they can support you. There are 3 images to choose from, please choose which color scheme you prefer:</p>
		  <input id="<?php echo esc_attr( $this->get_field_id( 'logo_color' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'logo_color') ); ?>" type="radio" value="1" <?php if ($instance['logo_color'] != 2 && $instance['logo_color'] != 3 && $instance['logo_color'] != 4 && $instance['logo_color'] != 5) echo "checked"; ?>>
		  <label for="1"><?php echo esc_html__( 'Grey', 'logo_color' ); ?></label><br>
		  <input id="<?php echo esc_attr( $this->get_field_id( 'logo_color' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'logo_color') ); ?>" type="radio" value="2" <?php if ($instance['logo_color'] == 2) echo "checked"; ?>>
		  <label for="2"><?php echo esc_html__( 'Pink', 'logo_color' ); ?></label><br>
		  <input id="<?php echo esc_attr( $this->get_field_id( 'logo_color' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'logo_color') ); ?>" type="radio" value="3" <?php if ($instance['logo_color'] == 3) echo "checked"; ?>>
		  <label for="3"><?php echo esc_html__( 'Blue', 'logo_color' ); ?></label><br>
		  <input id="<?php echo esc_attr( $this->get_field_id( 'logo_color' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'logo_color') ); ?>" type="radio" value="4" <?php if ($instance['logo_color'] == 4) echo "checked"; ?>>
		  <label for="4"><?php echo esc_html__( 'Standard', 'logo_color' ); ?></label><br>
		  <input id="<?php echo esc_attr( $this->get_field_id( 'logo_color' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'logo_color') ); ?>" type="radio" value="5" <?php if ($instance['logo_color'] == 5) echo "checked"; ?>>
		  <label for="5"><?php echo esc_html__( 'Unloct Logo', 'logo_color' ); ?></label><br>
        <?php
 
    }
 
    public function update( $new_instance, $old_instance ) {
 
        $instance = array();
 
        $instance['app_id'] = ( !empty( $new_instance['app_id'] ) ) ? strip_tags( $new_instance['app_id'] ) : '';
		$instance['logo_color'] = ( !empty( $new_instance['logo_color'] ) ) ? strip_tags( $new_instance['logo_color'] ) : '';
        //$instance['app_loginurl'] = ( !empty( $new_instance['app_loginurl'] ) ) ? $new_instance['app_loginurl'] : '';
 
        return $instance;
    }
 
}
$my_widget = new Unloct_Widget();
?>