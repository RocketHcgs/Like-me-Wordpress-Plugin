<?php
/*Plugin Name: like me
Description: 可爱的点赞小工具插件
Author: RocketHcgs
Author URI: http://www.rhw-team.com/
Version: 1.0
License: MIT
*/

class Like_me extends WP_Widget {
    public function __construct() {
		parent::__construct(
			'like_me',
			__( 'Like me' ),
			array (
				'description' => __( '可爱的点赞小工具' )
			)
		);
    }
    public function form( $instance ) {
		$defaults = array(
			'text1'	=> 'Do you like me?',
			'text2'	=> '我也喜欢你(*≧▽≦)',
			'text3'	=> '你的爱我已经感受到了~'
		);
		$text1 = empty($instance[ 'text1' ]) ? $defaults[ 'text1' ] : $instance[ 'text1' ];
		$text2 = empty($instance[ 'text2' ]) ? $defaults[ 'text2' ] : $instance[ 'text2' ];
		$text3 = empty($instance[ 'text3' ]) ? $defaults[ 'text3' ] : $instance[ 'text3' ];
		?>
		<p>
		  <label for="<?php echo $this->get_field_id( 'text1' ); ?>">未点赞时的显示:</label>
		  <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'text1' ); ?>" name="<?php echo $this->get_field_name( 'text1' ); ?>" value="<?php echo esc_attr( $text1 ); ?>">
		</p>
        <p>
		  <label for="<?php echo $this->get_field_id( 'text2' ); ?>">点赞时的显示:</label>
		  <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'text2' ); ?>" name="<?php echo $this->get_field_name( 'text2' ); ?>" value="<?php echo esc_attr( $text2 ); ?>">
		</p>
        <p>
		  <label for="<?php echo $this->get_field_id( 'text3' ); ?>">重复点赞的显示:</label>
		  <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'text3' ); ?>" name="<?php echo $this->get_field_name( 'text3' ); ?>" value="<?php echo esc_attr( $text3 ); ?>">
		</p>
		<?php
    }
    public function update( $new_instance, $old_instance ) {    
		$instance = $old_instance;
		$instance[ 'text1' ] = $new_instance[ 'text1' ];
		$instance[ 'text2' ] = $new_instance[ 'text2' ];
		$instance[ 'text3' ] = $new_instance[ 'text3' ];
		return $instance;
    }
    public function widget( $args, $instance ) {
		global $wpdb;
		$table_name1 = $wpdb->prefix . 'likes';//保存like数量的表
		$table_name2 = $wpdb->prefix . 'likes_ip';//保存ip的表
		like_setup( $table_name1, $table_name2 );
		$now_count = like_get( $table_name1 );
		$new_count = $now_count + 1;
		
		extract( $args );
		echo $before_widget;
		echo '<div class="like-div" onClick="like()">';
		echo '<div id="like-text">';
		echo $instance[ 'text1' ];
		echo '</div>';
		echo '<div id="like-count">';
		echo '<i class="fa fa-heart"></i> ' . $now_count;
		echo '</div>';
		echo '</div>';
		$admin_url = admin_url( 'admin-ajax.php' );
		?>
        <script>
		function like() {
			$(function() {
				var data={
					action:'like',
					table1:"<?php echo $table_name1; ?>",
					table2:"<?php echo $table_name2; ?>",
					ip:"<?php echo like_get_ip(); ?>"
				}
				$.post("<?php echo $admin_url;?>", data, function(response) {
					if( response!='0' ) {//这里很诡异啊，总是返回'0'
						$('#like-text').html("<?php echo $instance[ 'text3' ]; ?>");
					} else {
						$('#like-text').html("<?php echo $instance[ 'text2' ]; ?>");
						$('#like-count').html("<?php echo "<i class='fa fa-heart'></i> " . $new_count; ?>");
					}
				});
			});
		}
		</script>
        <?php
		echo $after_widget;
    }
}

//初始化数据库
function like_setup( $table_name1, $table_name2 ) {
	global $wpdb;
	//创建数据表
	$wpdb->query( "
		CREATE TABLE IF NOT EXISTS `$table_name1` (
		`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		`likes` bigint(20) NOT NULL DEFAULT '0',
		PRIMARY KEY (`id`)
		);
	" );
	$wpdb->insert(
		$table_name1,
		array(
			'id' => 1,
			'likes' => 0
	) );
	$wpdb->query( "
		CREATE TABLE IF NOT EXISTS `$table_name2` (
		`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		`ip` varchar(40) NOT NULL,
		PRIMARY KEY (`id`)
		);
	" );
}

//获取like数量
function like_get( $table_name ) {
	global $wpdb;
	$sql = "SELECT likes FROM $table_name WHERE id='1'";
	$check = $wpdb->get_var( $sql );
	return isset($check) ? absint($check) : 0;
}

//like!
function like() {
	global $wpdb;
	$table_name1 = $_POST['table1'];
	$table_name2 = $_POST['table2'];
	$ip = $_POST['ip'];
	$like = $wpdb->get_var( "SELECT likes FROM $table_name1 WHERE id='1'" );
	$check = $wpdb->get_var( "SELECT ip FROM $table_name2 WHERE ip='$ip'" );
	if( isset( $check ) ) {
		echo 'fuck';
	} else {
		$likes = (int)$like + 1;
		$wpdb->update(
			$table_name1,
			array(
				'likes' => $likes
			),
			array(
				'id' => 1
		) );
		$wpdb->insert(
			$table_name2, 
			array(
				'ip' => $ip
		) );
	}
}
add_action('wp_ajax_nopriv_like', 'like');
add_action('wp_ajax_like', 'like');

//获取真·ip
function like_get_ip() {
	if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
		$ip = getenv("HTTP_CLIENT_IP");
	elseif (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
		$ip = getenv("HTTP_X_FORWARDED_FOR");
	elseif (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
		$ip = getenv("REMOTE_ADDR");
	elseif (isset ($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
		$ip = $_SERVER['REMOTE_ADDR'];
	else $ip = "unknown";
	return ($ip);
}


//注册小工具
function register_like_widget() {
    register_widget( 'Like_me' );
}
add_action( 'widgets_init', 'register_like_widget' );

//加载css
function load_like_scripts() {
	wp_enqueue_style( 'fontawesome', plugins_url('css/font-awesome.css',__FILE__), array(), '4.5.0' );
	wp_enqueue_style( 'like-custom', plugins_url('css/like-me.css',__FILE__), array(), '1.0' );
}
add_action( 'wp_enqueue_scripts', 'load_like_scripts' );