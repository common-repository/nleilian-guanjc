<?php 
/*
Plugin Name: TAG标签关键词内链
Description: 可适用于文章内容页关键词标签改变颜色，添加链接。
Version: 1.0.1
Author: 沃之涛科技
Author URI: https://www.rbzzz.com
*/
// 声明全局变量$wpdb 和 数据表名常量
global $wpdb;

define('TAGMANAGE_URL','http://wp.seohnzz.com');
define('TAGMANAGE_SALT','seohnzz.com');
require plugin_dir_path( __FILE__ ) . 'post.php';//公用函数 
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'tagmanage_plugin_action_links');
//接收远程来的数据
if(isset($_POST['data'])){
    if(is_string($_POST['data'])){
       //推送过来的json数据 
       $tagmanage = json_decode($_POST['data'],true);
       //过滤推送的数据
        if(isset($tagmanage['tagmanage']) && $tagmanage['tagmanage']==46){
            $tagmanage1 = [
               'keywords'=>sanitize_text_field($tagmanage['daochu']['keywords']),
               'link'=>sanitize_url($tagmanage['daochu']['link']),
                'guo'=>sanitize_text_field($tagmanage['daochu']['guo']),
                'size'=>sanitize_text_field($tagmanage['daochu']['size']),
            ];
             $wpdb->update($wpdb->prefix . 'baiduseo_long',['link'=>$tagmanage1['link'],'guo'=>$tagmanage1['guo'],'size'=>$tagmanage1['size']],['keywords'=>$tagmanage1['keywords']]);
        }
    }
   
}
function tagmanage_plugin_action_links($links)
{
    $links[] = '<a href="' . admin_url( 'admin.php?page=tagmanage' ) . '">设置</a>';
    return $links;
}


//加载layui
if(is_admin()){

add_action('wp_ajax_tagmanage_post','tagmanage_post' );
add_action('wp_ajax_tagmanage_init','tagmanage_init' );

add_action('wp_ajax_tagmanage_shouquan','tagmanage_shouquan' );
add_action('wp_ajax_tagmanage_tag_add','tagmanage_tag_add' );
add_action('wp_ajax_tagmanage_xunhuan','tagmanage_xunhuan' );
add_action('wp_ajax_tagmanage_neilian','tagmanage_neilian' );
add_action('wp_ajax_tagmanage_add_tag', 'tagmanage_add_tag');
add_action('wp_ajax_tagmanage_add_pltag', 'tagmanage_add_pltag');
add_action('wp_ajax_tagmanage_reci','tagmanage_reci');
add_action('wp_ajax_tagmanage_neilian_delete','tagmanage_neilian_delete' );
add_action( 'admin_enqueue_scripts', 'tagmanage_enqueue' );
add_action('wp_ajax_tagmanage_5118', 'tagmanage_5118');
add_action('wp_ajax_tagmanage_5118_daochu', 'tagmanage_5118_daochu');
add_action('wp_ajax_tagmanage_get_neilian', 'tagmanage_get_neilian');
add_action('wp_ajax_tagmanage_neilian_delete_all', 'tagmanage_neilian_delete_all');
    global $wpdb;
    $charset_collate = '';
    if (!empty($wpdb->charset)) {
      $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
    }
    if (!empty( $wpdb->collate)) {
      $charset_collate .= " COLLATE {$wpdb->collate}";
    }
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    if($wpdb->get_var("show tables like '{$wpdb->prefix}baiduseo_neilian'") !=  $wpdb->prefix."baiduseo_neilian"){
        $sql15 = "CREATE TABLE " . $wpdb->prefix . "baiduseo_neilian   (
            id int(10) NOT NULL AUTO_INCREMENT,
            keywords varchar(255) NOT NULL ,
            link varchar(255) NOT NULL ,
            sort int(10) default 0,
            target int NOT NULL DEFAULT 0,
            nofollow int NOT NULL DEFAULT 0,
            UNIQUE KEY id (id)
        ) $charset_collate;";
        dbDelta($sql15);
    }
    if($wpdb->get_var("show tables like '{$wpdb->prefix}baiduseo_long'") !=  $wpdb->prefix."baiduseo_long"){
        $sql16 = "CREATE TABLE " . $wpdb->prefix . "baiduseo_long   (
            id bigint NOT NULL AUTO_INCREMENT,
            keywords varchar(255) NOT NULL ,
            total bigint NOT NULL DEFAULT 0 ,
            longs bigint NOT NULL DEFAULT 0,
            collect bigint NOT NULL DEFAULT 0,
            bidword bigint NOT NULL DEFAULT 0,
            link varchar(255) NUll,
            time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            guo timestamp NULL,
            size varchar(255) NOT NULL default '',
            UNIQUE KEY id (id)
        ) $charset_collate;";
        // var_dump($sql16);exit;
        dbDelta($sql16);
    }

}
//添加菜单栏
add_action('admin_menu', 'tagmanage_addtagpages');

if(!function_exists('tagmanage_addtagpages')){
	
	function tagmanage_addtagpages(){
		add_menu_page(__('tag标签内链','tagmanage_html'), __('tag标签内链','tagmanage_html'), 'administrator', 'tagmanage', 'tagmanage_toplevelpage' );
	}
}


//文章发布时调用
add_action('publish_post','tagmanage_articlepublish');

//tag标签
add_action( 'wp', 'tagmanage_tagchange' );





?>