<?php
/**
 * Plugin Name: Easy Header Maker
 * Description: WordPressで簡単にページごとの独自ヘッダーを作成できるプラグイン
 * Version: 1.0.0
 * Author: Shota Takazawa
 * Author URI: https://github.com/takazawashota/wp-simple-file-creator/
 * License: GPL2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// カスタムメニューウォーカークラス（ドロップダウンメニュー対応）
class Easy_Header_Walker extends Walker_Nav_Menu {
    
    // メニューレベルの開始
    function start_lvl(&$output, $depth = 0, $args = null) {
        $indent = str_repeat("\t", $depth);
        $output .= "\n$indent<ul class=\"sub-menu\">\n";
    }
    
    // メニューレベルの終了
    function end_lvl(&$output, $depth = 0, $args = null) {
        $indent = str_repeat("\t", $depth);
        $output .= "$indent</ul>\n";
    }
    
    // メニュー項目の開始
    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
        
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        
        // サブメニューがある場合のクラス追加
        if (in_array('menu-item-has-children', $classes)) {
            $classes[] = 'has-dropdown';
        }
        
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
        
        $id = apply_filters('nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args);
        $id = $id ? ' id="' . esc_attr($id) . '"' : '';
        
        $output .= $indent . '<li' . $id . $class_names .'>';
        
        $attributes = ! empty($item->attr_title) ? ' title="'  . esc_attr($item->attr_title) .'"' : '';
        $attributes .= ! empty($item->target)     ? ' target="' . esc_attr($item->target     ) .'"' : '';
        $attributes .= ! empty($item->xfn)        ? ' rel="'    . esc_attr($item->xfn        ) .'"' : '';
        $attributes .= ! empty($item->url)        ? ' href="'   . esc_attr($item->url        ) .'"' : '';
        
        $item_output = isset($args->before) ? $args->before : '';
        $item_output .= '<a' . $attributes . '>';
        $item_output .= (isset($args->link_before) ? $args->link_before : '') . apply_filters('the_title', $item->title, $item->ID) . (isset($args->link_after) ? $args->link_after : '');
        $item_output .= '</a>';
        $item_output .= isset($args->after) ? $args->after : '';
        
        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }
    
    // メニュー項目の終了
    function end_el(&$output, $item, $depth = 0, $args = null) {
        $output .= "</li>\n";
    }
}

class EasyHeaderMaker {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // メタフィールドを登録
        add_action('init', array($this, 'register_meta_fields'));
        
        // メタボックスの追加
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // メタデータの保存
        add_action('save_post', array($this, 'save_post_meta'));
        
        // フロントエンドでヘッダーを表示（PHP直接出力）
        add_action('wp_head', array($this, 'output_header_styles'), 1);
        add_action('wp_body_open', array($this, 'display_custom_header'), 1);
        add_action('wp_footer', array($this, 'output_header_scripts'), 1);
        
        // wp_body_openがサポートされていない場合のフォールバック
        if (!function_exists('wp_body_open')) {
            add_action('wp_footer', array($this, 'output_fallback_header_insertion'), 2);
        }
        
        // 管理画面でのスクリプトとスタイル
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // 管理画面メニューを追加
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // ショートコードを追加
        add_shortcode('header_test', array($this, 'header_test_shortcode'));
    }
    
    /**
     * メタフィールドの登録
     */
    public function register_meta_fields() {
        $enabled_post_types = get_option('easy_header_enabled_post_types', array('page'));
        
        foreach ($enabled_post_types as $post_type) {
            register_post_meta($post_type, '_easy_header_enable', array(
                'type' => 'boolean',
                'single' => true,
                'default' => false
            ));
            
            register_post_meta($post_type, '_easy_header_logo', array(
                'type' => 'string',
                'single' => true,
                'default' => ''
            ));
            
            register_post_meta($post_type, '_easy_header_title', array(
                'type' => 'string',
                'single' => true,
                'default' => ''
            ));
            
            register_post_meta($post_type, '_easy_header_subtitle', array(
                'type' => 'string',
                'single' => true,
                'default' => ''
            ));
            
            register_post_meta($post_type, '_easy_header_bg_color', array(
                'type' => 'string',
                'single' => true,
                'default' => '#ffffff'
            ));
            
            register_post_meta($post_type, '_easy_header_text_color', array(
                'type' => 'string',
                'single' => true,
                'default' => '#000000'
            ));
            
            register_post_meta($post_type, '_easy_header_logo_width', array(
                'type' => 'string',
                'single' => true,
                'default' => '200'
            ));
            
            register_post_meta($post_type, '_easy_header_layout', array(
                'type' => 'string',
                'single' => true,
                'default' => 'center'
            ));
            
            register_post_meta($post_type, '_easy_header_link_url', array(
                'type' => 'string',
                'single' => true,
                'default' => ''
            ));
            
            register_post_meta($post_type, '_easy_header_menu_id', array(
                'type' => 'string',
                'single' => true,
                'default' => ''
            ));
            
            register_post_meta($post_type, '_easy_header_width', array(
                'type' => 'string',
                'single' => true,
                'default' => 'full'
            ));
            
            register_post_meta($post_type, '_easy_header_logo_width_mobile', array(
                'type' => 'string',
                'single' => true,
                'default' => '120'
            ));
            
            register_post_meta($post_type, '_easy_header_subtitle_bg_color', array(
                'type' => 'string',
                'single' => true,
                'default' => '#1a1a1a'
            ));
            
            register_post_meta($post_type, '_easy_header_subtitle_text_color', array(
                'type' => 'string',
                'single' => true,
                'default' => '#ffffff'
            ));
            
            register_post_meta($post_type, '_easy_header_sticky_desktop', array(
                'type' => 'boolean',
                'single' => true,
                'default' => false
            ));
            
            register_post_meta($post_type, '_easy_header_sticky_mobile', array(
                'type' => 'boolean',
                'single' => true,
                'default' => false
            ));
            
            register_post_meta($post_type, '_easy_header_shadow', array(
                'type' => 'boolean',
                'single' => true,
                'default' => false
            ));
            
            register_post_meta($post_type, '_easy_header_custom_css', array(
                'type' => 'string',
                'single' => true,
                'default' => ''
            ));
            
            register_post_meta($post_type, '_easy_header_custom_js', array(
                'type' => 'string',
                'single' => true,
                'default' => ''
            ));
            
            register_post_meta($post_type, '_easy_header_custom_html', array(
                'type' => 'string',
                'single' => true,
                'default' => ''
            ));
            
            register_post_meta($post_type, '_easy_header_notice_enable', array(
                'type' => 'boolean',
                'single' => true,
                'default' => false
            ));
            
            register_post_meta($post_type, '_easy_header_notice_text', array(
                'type' => 'string',
                'single' => true,
                'default' => ''
            ));
            
            register_post_meta($post_type, '_easy_header_notice_url', array(
                'type' => 'string',
                'single' => true,
                'default' => ''
            ));
            
            register_post_meta($post_type, '_easy_header_notice_bg_color', array(
                'type' => 'string',
                'single' => true,
                'default' => '#f39c12'
            ));
            
            register_post_meta($post_type, '_easy_header_notice_text_color', array(
                'type' => 'string',
                'single' => true,
                'default' => '#ffffff'
            ));
            
            register_post_meta($post_type, '_easy_header_notice_external', array(
                'type' => 'boolean',
                'single' => true,
                'default' => false
            ));
        }
    }
    
    /**
     * メタボックスの追加
     */
    public function add_meta_boxes() {
        $enabled_post_types = get_option('easy_header_enabled_post_types', array('page'));
        
        add_meta_box(
            'easy_header_meta_box',
            'Easy Header Maker',
            array($this, 'meta_box_callback'),
            $enabled_post_types,
            'normal',
            'high'
        );
    }
    
    /**
     * ヘッダーデータを取得する共通メソッド
     */
    private function get_header_data() {
        global $post;
        
        $post_id = null;
        $enable_custom_header = false;
        
        // 投稿・ページの場合
        if (is_singular() && $post) {
            $post_id = $post->ID;
            $enable_custom_header = get_post_meta($post_id, '_easy_header_enable', true);
        }
        // フロントページの場合（静的フロントページのみ）
        elseif (is_front_page() && get_option('show_on_front') == 'page') {
            $post_id = get_option('page_on_front');
            if ($post_id) {
                $enable_custom_header = get_post_meta($post_id, '_easy_header_enable', true);
            }
        }
        
        if (!$enable_custom_header || !$post_id) {
            return false;
        }
        
        // 投稿・ページ用のデータを取得
        $header_title = get_post_meta($post_id, '_easy_header_title', true);
        if (empty($header_title)) {
            $header_title = is_front_page() ? get_bloginfo('name') : get_the_title($post_id);
        }
        
        return array(
            'enable_custom_header' => true,
            'header_logo' => get_post_meta($post_id, '_easy_header_logo', true),
            'header_title' => $header_title,
            'header_subtitle' => get_post_meta($post_id, '_easy_header_subtitle', true),
            'header_bg_color' => get_post_meta($post_id, '_easy_header_bg_color', true) ?: '#ffffff',
            'header_text_color' => get_post_meta($post_id, '_easy_header_text_color', true) ?: '#000000',
            'header_logo_width' => get_post_meta($post_id, '_easy_header_logo_width', true) ?: '200',
            'header_logo_width_mobile' => get_post_meta($post_id, '_easy_header_logo_width_mobile', true) ?: '120',
            'header_subtitle_bg_color' => get_post_meta($post_id, '_easy_header_subtitle_bg_color', true) ?: '#ddd',
            'header_subtitle_text_color' => get_post_meta($post_id, '_easy_header_subtitle_text_color', true) ?: '#ffffff',
            'header_layout' => get_post_meta($post_id, '_easy_header_layout', true) ?: 'center',
            'header_link_url' => get_post_meta($post_id, '_easy_header_link_url', true),
            'header_menu_id' => get_post_meta($post_id, '_easy_header_menu_id', true),
            'header_width' => get_post_meta($post_id, '_easy_header_width', true) ?: 'full',
            'header_sticky_desktop' => get_post_meta($post_id, '_easy_header_sticky_desktop', true),
            'header_sticky_mobile' => get_post_meta($post_id, '_easy_header_sticky_mobile', true),
            'header_shadow' => get_post_meta($post_id, '_easy_header_shadow', true),
            'header_custom_css' => get_post_meta($post_id, '_easy_header_custom_css', true),
            'header_custom_js' => get_post_meta($post_id, '_easy_header_custom_js', true),
            'header_custom_html' => get_post_meta($post_id, '_easy_header_custom_html', true),
            'notice_enable' => get_post_meta($post_id, '_easy_header_notice_enable', true),
            'notice_text' => get_post_meta($post_id, '_easy_header_notice_text', true),
            'notice_url' => get_post_meta($post_id, '_easy_header_notice_url', true),
            'notice_bg_color' => get_post_meta($post_id, '_easy_header_notice_bg_color', true) ?: '#f39c12',
            'notice_text_color' => get_post_meta($post_id, '_easy_header_notice_text_color', true) ?: '#ffffff',
            'notice_external' => get_post_meta($post_id, '_easy_header_notice_external', true)
        );
    }
    
    /**
     * ヘッダー用CSSスタイルを出力
     */
    public function output_header_styles() {
        $header_data = $this->get_header_data();
        if (!$header_data) {
            return;
        }
        
        extract($header_data);
        
        // 横幅設定
        $max_width_style = '';
        if ($header_width !== 'full') {
            if (is_numeric($header_width)) {
                $max_width_style = 'max-width: ' . intval($header_width) . 'px; margin-left: auto; margin-right: auto;';
            } else {
                $max_width_style = 'max-width: ' . esc_attr($header_width) . 'px; margin-left: auto; margin-right: auto;';
            }
        }
        ?>
        <style id="easy-header-styles">
            .easy-custom-header {
                background-color: <?php echo esc_attr($header_bg_color); ?>;
                color: <?php echo esc_attr($header_text_color); ?>;
                text-align: <?php echo ($header_layout === 'horizontal' ? 'left' : 'center'); ?>;
                position: relative;
                z-index: 999;
                <?php if ($header_shadow): ?>
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                <?php endif; ?>
            }

            .easy-custom-header .header-inner {
                <?php echo $max_width_style; ?>
            }
            .easy-custom-header.layout-horizontal .header-inner {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
            }
            .easy-custom-header.layout-horizontal .header-left {
                flex: 0 0 auto;
                display: flex;
                align-items: center;
                gap: 15px;
            }
            .easy-custom-header.layout-horizontal .header-right {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 20px;
            }
            .header-custom-html {
                display: inline-block;
            }
            
            /* 通知バーのスタイル */
            .easy-header-notice {

            }
            .easy-header-notice .notice-inner {

            }
            .easy-header-notice a {
                padding: 7px 20px;
                font-weight: 600;
                text-align: center;
                font-size: 14px;
                line-height: 1.4;
            }
            .easy-header-notice a:hover {

            }
            .easy-custom-header .header-logo {
                height: auto;
                margin-bottom: 20px;
                <?php if ($header_logo_width === 'auto'): ?>
                width: auto;
                <?php else: ?>
                width: <?php echo esc_attr($header_logo_width); ?>px;
                <?php endif; ?>
            }
            .easy-custom-header.layout-horizontal .header-logo {
                margin-bottom: 0;
                margin-right: 0;
            }
            .easy-custom-header.layout-center .header-logo {
                margin-bottom: 0;
            }
            .easy-custom-header .header-title {
                font-size: 2.5em;
                font-weight: bold;
                margin: 0 0 10px 0;
            }
            .easy-custom-header.layout-horizontal .header-title {
                margin-bottom: 0;
                margin-right: 0;
            }
            .easy-custom-header.layout-center .header-title {
                margin: 0;
            }
            .easy-custom-header a {
                display: flex;
                color: inherit;
                text-decoration: none;
            }
            .easy-custom-header.layout-center a {
                justify-content: center;
            }
            .easy-custom-header a:hover {
                opacity: 0.8;
            }
            .easy-custom-header .header-navigation {
                margin-top: 20px;
                position: relative;
            }
            .easy-custom-header.layout-horizontal .header-navigation {
                margin-top: 0;
                margin-left: auto;
            }
            .easy-custom-header.layout-center .header-navigation {
                margin-top: 20px;
            }
            .easy-custom-header .header-navigation ul {
                list-style: none;
                padding: 0;
                margin: 0;
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
                gap: 16px;
            }
            /* デスクトップ（768px超）でのメニュー表示とハンバーガーボタン非表示 */
            @media (min-width: 769px) {
                .easy-custom-header .hamburger-menu {
                    display: none !important;
                }
                .easy-custom-header .header-navigation ul.easy-header-menu {
                    position: static !important;
                    width: auto !important;
                    height: auto !important;
                    background: transparent !important;
                    flex-direction: row !important;
                    padding: 0 !important;
                    left: auto !important;
                    display: flex !important;
                }
                
                /* デスクトップでカスタムHTMLを表示 */
                .header-custom-html {
                    display: inline-block !important;
                }
                
                /* 横レイアウトでのカスタムHTML調整 */
                .easy-custom-header.layout-horizontal .header-custom-html {
                    margin-left: 10px;
                }
                
                /* 縦レイアウトでのカスタムHTML調整 */
                .easy-custom-header:not(.layout-horizontal) .header-custom-html {
                    margin-top: 10px;
                    text-align: center;
                }
            }
            /* ハンバーガーメニューボタン（デフォルトで非表示） */
            .easy-custom-header .hamburger-menu {
                display: none;
                flex-direction: column;
                cursor: pointer;
                width: 30px;
                height: 20px;
                justify-content: space-between;
                background: none !important;
                border: none !important;
                padding: 0;
                z-index: 1001;
                position: relative;
                outline: none !important;
                box-shadow: none !important;
            }
            .easy-custom-header .hamburger-menu:focus,
            .easy-custom-header .hamburger-menu:active,
            .easy-custom-header .hamburger-menu:hover {
                background: none !important;
                outline: none !important;
                box-shadow: none !important;
            }
            .easy-custom-header .hamburger-menu span {
                display: block;
                height: 3px;
                width: 100%;
                background-color: currentColor;
                border-radius: 2px;
                transition: all 0.3s ease-in-out;
                transform-origin: center center;
                position: absolute;
            }
            .easy-custom-header .hamburger-menu span:nth-child(1) {
                top: 0;
            }
            .easy-custom-header .hamburger-menu span:nth-child(2) {
                top: 50%;
                transform: translateY(-50%);
            }
            .easy-custom-header .hamburger-menu span:nth-child(3) {
                bottom: 0;
            }
            /* ハンバーガーメニューのアニメーション */
            .easy-custom-header .hamburger-menu.active span:nth-child(1) {
                top: 50%;
                transform: translateY(-50%) rotate(45deg);
            }
            .easy-custom-header .hamburger-menu.active span:nth-child(2) {
                opacity: 0;
                transform: translateY(-50%) scaleX(0);
            }
            .easy-custom-header .hamburger-menu.active span:nth-child(3) {
                bottom: 50%;
                transform: translateY(50%) rotate(-45deg);
            }
            .easy-custom-header.layout-horizontal .header-navigation ul {
                justify-content: flex-end;
            }
            .easy-custom-header .header-navigation li {
                margin: 0;
                position: relative;
            }
            .easy-custom-header .header-navigation a {
                display: block;
                padding: 8px 0;
                border-radius: 4px;
                transition: background-color 0.3s ease;
                text-align: left;
            }
            .easy-custom-header .header-navigation a:hover {
                background-color: rgba(255, 255, 255, 0.1);
            }
            /* ドロップダウンメニューのスタイル */
            .easy-custom-header .header-navigation .sub-menu {
                position: absolute;
                top: 100%;
                left: 0;
                min-width: 200px;
                background: rgba(0, 0, 0, 0.9);
                border-radius: 4px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                flex-direction: column;
                gap: 0;
                opacity: 0;
                visibility: hidden;
                transform: translateY(-10px);
                transition: all 0.3s ease;
                z-index: 9999;
            }
            .easy-custom-header .header-navigation li:hover > .sub-menu {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }
            .easy-custom-header .header-navigation .sub-menu li {
                width: 100%;
                position: relative;
            }
            .easy-custom-header .header-navigation .sub-menu a {
                padding: 12px 16px;
                border-radius: 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                color: #fff;
                position: relative;
            }
            /* 孫メニューがある項目にインジケーター追加 */
            .easy-custom-header .header-navigation .sub-menu li:last-child a {
                border-bottom: none;
                border-radius: 0 0 4px 4px;
            }
            .easy-custom-header .header-navigation .sub-menu li:first-child a {
                border-radius: 4px 4px 0 0;
            }
            .easy-custom-header .header-navigation .sub-menu a:hover {
                background-color: rgba(255, 255, 255, 0.2);
            }
            /* 孫メニュー以降のスタイル（横に表示） */
            .easy-custom-header .header-navigation .sub-menu .sub-menu {
                top: 0;
                left: 100%;
                transform: translateX(-10px);
            }
            .easy-custom-header .header-navigation .sub-menu li:hover > .sub-menu {
                opacity: 1;
                visibility: visible;
                transform: translateX(0);
            }
            /* 右端での表示調整 */
            .easy-custom-header .header-navigation .sub-menu .sub-menu.show-left {
                left: -100%;
                transform: translateX(10px);
            }
            .easy-custom-header .header-navigation .sub-menu .sub-menu.show-left:hover {
                transform: translateX(0);
            }
            /* 深いレベルのメニューにz-indexを適用 */
            .easy-custom-header .header-navigation .sub-menu .sub-menu {
                z-index: 10000;
            }
            .easy-custom-header .header-navigation .sub-menu .sub-menu .sub-menu {
                z-index: 10001;
            }
            .easy-custom-header .header-subtitle {
                margin: 0;
                opacity: 0.8;
            }
            .easy-custom-header.layout-horizontal .header-subtitle {
                margin: 0;
                white-space: nowrap;
                font-size: 14px;
            }
            
            /* デスクトップでのサブタイトル表示（帯状に背景色付き） */
            @media (min-width: 769px) {
                /* デスクトップでのheader-innerに基本パディング適用 */
                .easy-custom-header .header-inner {
                    padding: 26px 0;
                }
                
                .easy-custom-header .header-subtitle {
                    background: <?php echo esc_attr($header_subtitle_bg_color); ?>;
                    color: <?php echo esc_attr($header_subtitle_text_color); ?>;
                    font-size: 14px;
                    padding: 8px 0;
                    margin: 0;
                    width: 100%;
                    position: relative;
                }
                
                .easy-custom-header .header-subtitle-inner {
                    <?php echo $max_width_style; ?>
                }
                
                .easy-custom-header:has(.header-subtitle) .header-inner,
                .easy-custom-header .header-subtitle + .header-inner {
                    margin-top: 0 !important;
                    padding: 16px 30px;
                    box-sizing: content-box;
                }
                
                .easy-custom-header.layout-center:has(.header-navigation) .header-inner {
                    padding-top: 36px;
                }

                /* 横レイアウトでのサブタイトル調整 */
                .easy-custom-header.layout-horizontal .header-subtitle {
                    background: <?php echo esc_attr($header_subtitle_bg_color); ?>;
                    color: <?php echo esc_attr($header_subtitle_text_color); ?>;
                    font-size: 12px;
                    padding: 3px 0;
                }
                
                .easy-custom-header.layout-horizontal .header-subtitle-inner {
                    <?php echo $max_width_style; ?>
                    padding: 0 30px;
                    box-sizing: content-box;
                }
            }
            @media (max-width: 768px) {
                /* モバイル専用のヘッダー構造 */
                .easy-custom-header {
                    padding: 0;
                    position: relative;
                }
                
                /* サブタイトルを帯状に上部表示 */
                .easy-custom-header .header-subtitle {
                    background: <?php echo esc_attr($header_subtitle_bg_color); ?>;
                    color: <?php echo esc_attr($header_subtitle_text_color); ?>;
                    font-size: 11px;
                    padding: 4px 0;
                    margin: 0;
                    z-index: 1;
                }
                
                .easy-custom-header .header-subtitle-inner {
                    padding: 0 15px;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                /* ヘッダー内部のレイアウト調整 */
                .easy-custom-header .header-inner {
                    padding: 15px 20px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: nowrap;
                }
                
                /* 横レイアウトの調整 */
                .easy-custom-header.layout-horizontal .header-inner {

                }
                
                /* 縦レイアウトの調整 */
                .easy-custom-header:not(.layout-horizontal) .header-inner {
                    flex-direction: row;
                    align-items: center;
                    justify-content: space-between;
                }
                
                /* 左側コンテンツ（ロゴ・タイトル）*/
                .easy-custom-header .header-left,
                .easy-custom-header .header-content {
                    flex: 1;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin: 0;
                    text-align: left;
                }
                
                /* ロゴ画像のサイズ調整 */
                .easy-custom-header .header-logo {
                    max-width: none;
                    max-height: 40px;
                    <?php if ($header_logo_width_mobile === 'auto'): ?>
                    width: auto;
                    <?php else: ?>
                    width: <?php echo esc_attr($header_logo_width_mobile); ?>px;
                    <?php endif; ?>
                    height: auto;
                    margin: 0;
                    object-fit: contain;
                }
                
                /* タイトルのサイズ調整 */
                .easy-custom-header .header-title {
                    font-size: 1.3em;
                    margin: 0;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                
                /* 右側のナビゲーションエリア */
                .easy-custom-header .header-right,
                .easy-custom-header .header-navigation {
                    flex: 0 0 auto;
                    margin: 0;
                    display: flex;
                    align-items: center;
                    justify-content: flex-end;
                }
                
                /* 縦レイアウトでのナビゲーション調整 */
                .easy-custom-header:not(.layout-horizontal) .header-navigation {
                    position: static;
                    margin: 0;
                }
                
                /* モバイルではカスタムHTMLを非表示 */
                .header-custom-html {
                    display: none !important;
                }
                /* ハンバーガーメニューボタンを表示 */
                .easy-custom-header .header-navigation .hamburger-menu {
                    display: flex !important;
                    position: relative;
                    z-index: 1002;
                }
                /* 横レイアウトでのハンバーガーメニュー位置調整 */
                .easy-custom-header.layout-horizontal .header-navigation .hamburger-menu {
                    margin-left: auto;
                }
                /* ハンバーガーメニューボタンを表示 */
                .easy-custom-header .header-navigation .hamburger-menu {
                    display: flex;
                }
                /* デスクトップでメニューを通常表示、モバイルでハンバーガー化 */
                .easy-custom-header .header-navigation ul.easy-header-menu {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 280px;
                    height: 100vh;
                    background: rgba(0, 0, 0, 0.95);
                    flex-direction: column;
                    justify-content: flex-start;
                    align-items: stretch;
                    gap: 0;
                    padding: 60px 0 20px 0;
                    margin: 0;
                    z-index: 1000;
                    transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
                    transform: translateX(-100%);
                    overflow-y: auto;
                    display: flex;
                }
                .easy-custom-header .header-navigation ul.easy-header-menu.active {
                    transform: translateX(0);
                }
                .easy-custom-header .header-navigation ul.easy-header-menu li {
                    width: 100%;
                    margin: 0;
                }
                .easy-custom-header .header-navigation ul.easy-header-menu li a {
                    display: block;
                    padding: 15px 20px;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                    color: #fff;
                    font-size: 16px;
                    border-radius: 0;
                    transition: background-color 0.3s ease, transform 0.2s ease;
                }
                .easy-custom-header .header-navigation ul.easy-header-menu li a:hover {
                    background-color: rgba(255, 255, 255, 0.1);
                    transform: translateX(5px);
                }
                /* サブメニューの処理 */
                .easy-custom-header .header-navigation .sub-menu {
                    position: static !important;
                    left: auto !important;
                    top: auto !important;
                    min-width: auto;
                    width: 100% !important;
                    background: rgba(255, 255, 255, 0.05) !important;
                    box-shadow: none !important;
                    margin-top: 0 !important;
                    transform: none !important;
                    display: flex !important;
                    flex-direction: column !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                }
                .easy-custom-header .header-navigation .sub-menu li {
                    width: 100% !important;
                    margin: 0 !important;
                    display: block !important;
                }
                .easy-custom-header .header-navigation .sub-menu a {
                    display: block !important;
                    padding: 12px 40px !important;
                    font-size: 14px !important;
                    color: #fff !important;
                    text-decoration: none !important;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
                }
                /* 孫メニュー以降も同様に */
                .easy-custom-header .header-navigation .sub-menu .sub-menu {
                    background: rgba(255, 255, 255, 0.03);
                }
                .easy-custom-header .header-navigation .sub-menu .sub-menu a {
                    padding: 10px 60px !important;
                }


                /* オーバーレイ */
                .easy-custom-header .menu-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 999;
                    opacity: 0;
                    visibility: hidden;
                    transition: opacity 0.4s cubic-bezier(0.25, 0.8, 0.25, 1), 
                                visibility 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
                }
                .easy-custom-header .menu-overlay.active {
                    opacity: 1;
                    visibility: visible;
                }
                /* 縦レイアウトでも横並び表示にする */
                .easy-custom-header:not(.layout-horizontal) .header-inner {
                    display: flex !important;
                    flex-direction: row !important;
                    align-items: center !important;
                    justify-content: space-between !important;
                }
                
                /* ハンバーガーメニューボタンの位置調整 */
                .easy-custom-header .hamburger-menu {
                    position: relative !important;
                    top: auto !important;
                    right: auto !important;
                    display: flex !important;
                }
                
                /* サブタイトルがない場合の調整 */
                .easy-custom-header:not(:has(.header-subtitle)) .header-inner {
                    margin-top: 0;
                }
            }
            
            /* ボタン要素のスタイル */
            button.hamburger-menu{
                color: #000 !important;
            }

            /* デスクトップでのスティッキー設定 */
            @media (min-width: 769px) {
                <?php if ($header_sticky_desktop): ?>
                .easy-custom-header {
                    position: -webkit-sticky;
                    position: sticky;
                    top: 0;
                    z-index: 9999 !important;
                }
                
                /* 管理バーがある場合の調整 */
                .admin-bar .easy-custom-header {
                    top: 32px;
                }
                <?php endif; ?>
            }
            
            /* モバイルでのスティッキー設定 */
            @media (max-width: 768px) {
                <?php if ($header_sticky_mobile): ?>
                .easy-custom-header {
                    position: -webkit-sticky;
                    position: sticky;
                    top: 0;
                    z-index: 9999 !important;
                }
                <?php endif; ?>
            }
            
            /* カスタムCSS */
            <?php if (!empty($header_custom_css)): ?>
            <?php echo $header_custom_css; ?>
            <?php endif; ?>
        </style>
        <?php
    }
    
    /**
     * フロントエンドでカスタムヘッダーをPHP直接出力
     */
    public function display_custom_header() {
        $header_data = $this->get_header_data();
        if (!$header_data) {
            return;
        }
        
        extract($header_data);
        
        // リンクURL設定
        $link_start = '';
        $link_end = '';
        if (!empty($header_link_url)) {
            $link_start = '<a href="' . esc_url($header_link_url) . '">';
            $link_end = '</a>';
        }
        
        // ロゴ幅はCSSで制御するため、インラインスタイルは使用しない
        $logo_width_style = '';
        
        ?>
        <div class="easy-custom-header layout-<?php echo esc_attr($header_layout); ?>">
            <?php if ($header_subtitle): ?>
                <div class="header-subtitle">
                    <div class="header-subtitle-inner"><?php echo esc_html($header_subtitle); ?></div>
                </div>
            <?php endif; ?>
            
            <div class="header-inner">
                <?php if ($header_layout === 'horizontal'): ?>
                    <div class="header-left">
                        <?php if ($header_logo): ?>
                            <div>
                                <?php echo $link_start; ?>
                                <img src="<?php echo esc_url($header_logo); ?>" alt="Header Logo" class="header-logo" style="<?php echo $logo_width_style; ?>" />
                                <?php echo $link_end; ?>
                            </div>
                        <?php else: ?>
                            <h1 class="header-title">
                                <?php echo $link_start . esc_html($header_title) . $link_end; ?>
                            </h1>
                        <?php endif; ?>
                    </div>
                    
                    <div class="header-right">
                        <?php if (!empty($header_menu_id)): ?>
                            <nav class="header-navigation">
                                <button class="hamburger-menu" aria-label="メニューを開く">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </button>
                                <?php
                                wp_nav_menu(array(
                                    'menu' => $header_menu_id,
                                    'container' => false,
                                    'items_wrap' => '<ul class="easy-header-menu">%3$s</ul>',
                                    'fallback_cb' => false,
                                    'walker' => new Easy_Header_Walker()
                                ));
                                ?>
                            </nav>
                        <?php endif; ?>
                        
                        <?php if (!empty($header_custom_html)): ?>
                            <div class="header-custom-html">
                                <?php echo wp_kses_post($header_custom_html); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="header-content">
                        <?php if ($header_logo): ?>
                            <div>
                                <?php echo $link_start; ?>
                                <img src="<?php echo esc_url($header_logo); ?>" alt="Header Logo" class="header-logo" style="<?php echo $logo_width_style; ?>" />
                                <?php echo $link_end; ?>
                            </div>
                        <?php else: ?>
                            <h1 class="header-title">
                                <?php echo $link_start . esc_html($header_title) . $link_end; ?>
                            </h1>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($header_menu_id)): ?>
                        <nav class="header-navigation">
                            <button class="hamburger-menu" aria-label="メニューを開く">
                                <span></span>
                                <span></span>
                                <span></span>
                            </button>
                            <?php
                            wp_nav_menu(array(
                                'menu' => $header_menu_id,
                                'container' => false,
                                'items_wrap' => '<ul class="easy-header-menu">%3$s</ul>',
                                'fallback_cb' => false,
                                'walker' => new Easy_Header_Walker()
                            ));
                            ?>
                        </nav>
                    <?php endif; ?>
                    
                    <?php if (!empty($header_custom_html)): ?>
                        <div class="header-custom-html">
                            <?php echo wp_kses_post($header_custom_html); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($header_menu_id)): ?>
                <div class="menu-overlay"></div>
            <?php endif; ?>
        </div>
        
        <?php if ($notice_enable && !empty($notice_text)): ?>
            <div class="easy-header-notice" style="background-color: <?php echo esc_attr($notice_bg_color); ?>; color: <?php echo esc_attr($notice_text_color); ?>;">
                <div class="notice-inner">
                    <?php if (!empty($notice_url)): ?>
                        <a href="<?php echo esc_url($notice_url); ?>" 
                           style="color: inherit; text-decoration: none; display: block;"
                           <?php if ($notice_external): ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>><?php echo esc_html($notice_text); ?></a>
                    <?php else: ?>
                        <?php echo esc_html($notice_text); ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php
    }
    
    /**
     * ドロップダウンメニュー用JavaScriptを出力
     */
    public function output_header_scripts() {
        $header_data = $this->get_header_data();
        if (!$header_data) {
            return;
        }
        
        extract($header_data);
        ?>
        <script id="easy-header-scripts">
        document.addEventListener("DOMContentLoaded", function() {
            var hamburgerBtn = document.querySelector(".hamburger-menu");
            var menu = document.querySelector(".easy-header-menu");
            var overlay = document.querySelector(".menu-overlay");
            var header = document.querySelector(".easy-custom-header");
            
            if (hamburgerBtn && menu) {
                // ハンバーガーボタンクリック
                hamburgerBtn.addEventListener("click", function() {
                    menu.classList.toggle("active");
                    hamburgerBtn.classList.toggle("active");
                    if (overlay) {
                        overlay.classList.toggle("active");
                    }
                });
                
                // オーバーレイクリックでメニューを閉じる
                if (overlay) {
                    overlay.addEventListener("click", function() {
                        menu.classList.remove("active");
                        hamburgerBtn.classList.remove("active");
                        overlay.classList.remove("active");
                    });
                }
            }
            

            
            // カスタムJavaScript
            <?php if (!empty($header_custom_js)): ?>
            <?php echo $header_custom_js; ?>
            <?php endif; ?>
        });
        </script>
        <?php
    }
    
    /**
     * wp_body_openがサポートされていない場合のフォールバック
     */
    public function output_fallback_header_insertion() {
        ?>
        <script>
        // wp_body_openフックが実行されていない場合のフォールバック
        document.addEventListener("DOMContentLoaded", function() {
            // ヘッダーが既に存在するかチェック
            if (!document.querySelector(".easy-custom-header")) {
                // 管理バーの存在をチェック
                var adminBar = document.getElementById('wpadminbar');
                
                // PHPでヘッダーHTMLを出力
                <?php ob_start(); ?>
                <?php $this->display_custom_header(); ?>
                <?php 
                $header_html = ob_get_clean();
                if ($header_html): 
                ?>
                var headerContainer = document.createElement('div');
                headerContainer.innerHTML = <?php echo json_encode($header_html); ?>;
                
                if (adminBar) {
                    adminBar.parentNode.insertBefore(headerContainer.firstElementChild, adminBar.nextSibling);
                } else {
                    document.body.insertBefore(headerContainer.firstElementChild, document.body.firstChild);
                }
                

                <?php endif; ?>
            }
        });
        </script>
        <?php
    }
    
    /**
     * メタボックスのコールバック関数
     */
    public function meta_box_callback($post) {
        wp_nonce_field('easy_header_meta_box', 'easy_header_meta_box_nonce');
        
        // 保存された値を取得
        $enable_custom_header = get_post_meta($post->ID, '_easy_header_enable', true);
        $header_logo = get_post_meta($post->ID, '_easy_header_logo', true);
        $header_title = get_post_meta($post->ID, '_easy_header_title', true);
        $header_subtitle = get_post_meta($post->ID, '_easy_header_subtitle', true);
        $header_bg_color = get_post_meta($post->ID, '_easy_header_bg_color', true) ?: '#ffffff';
        $header_text_color = get_post_meta($post->ID, '_easy_header_text_color', true) ?: '#000000';
        $header_logo_width = get_post_meta($post->ID, '_easy_header_logo_width', true) ?: '200';
        $header_logo_width_mobile = get_post_meta($post->ID, '_easy_header_logo_width_mobile', true) ?: '120';
        $header_subtitle_bg_color = get_post_meta($post->ID, '_easy_header_subtitle_bg_color', true) ?: '#ddd';
        $header_subtitle_text_color = get_post_meta($post->ID, '_easy_header_subtitle_text_color', true) ?: '#ffffff';
        $header_layout = get_post_meta($post->ID, '_easy_header_layout', true) ?: 'center';
        $header_link_url = get_post_meta($post->ID, '_easy_header_link_url', true);
        $header_menu_id = get_post_meta($post->ID, '_easy_header_menu_id', true);
        $header_width = get_post_meta($post->ID, '_easy_header_width', true) ?: 'full';
        $header_sticky_desktop = get_post_meta($post->ID, '_easy_header_sticky_desktop', true);
        $header_sticky_mobile = get_post_meta($post->ID, '_easy_header_sticky_mobile', true);
        $header_shadow = get_post_meta($post->ID, '_easy_header_shadow', true);
        $header_custom_css = get_post_meta($post->ID, '_easy_header_custom_css', true);
        $header_custom_js = get_post_meta($post->ID, '_easy_header_custom_js', true);
        $header_custom_html = get_post_meta($post->ID, '_easy_header_custom_html', true);
        $notice_enable = get_post_meta($post->ID, '_easy_header_notice_enable', true);
        $notice_text = get_post_meta($post->ID, '_easy_header_notice_text', true);
        $notice_url = get_post_meta($post->ID, '_easy_header_notice_url', true);
        $notice_bg_color = get_post_meta($post->ID, '_easy_header_notice_bg_color', true) ?: '#f39c12';
        $notice_text_color = get_post_meta($post->ID, '_easy_header_notice_text_color', true) ?: '#ffffff';
        $notice_external = get_post_meta($post->ID, '_easy_header_notice_external', true);
        ?>
        <div style="max-width: 800px;">
            <table class="form-table">
                <tr>
                    <th scope="row">独自ヘッダーを有効化</th>
                    <td>
                        <label>
                            <input type="checkbox" id="easy_header_enable" name="easy_header_enable" value="1" <?php checked($enable_custom_header, 1); ?> />
                            このページに独自ヘッダーを表示する
                        </label>
                    </td>
                </tr>
            </table>
            
            <div id="easy-header-settings" style="<?php echo !$enable_custom_header ? 'display: none;' : ''; ?>">
                <h3>ヘッダー設定</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">レイアウト</th>
                        <td>
                            <label>
                                <input type="radio" name="easy_header_layout" value="center" <?php checked($header_layout, 'center'); ?> />
                                中央寄せ（縦レイアウト）
                            </label><br />
                            <label style="margin-top: 5px; display: inline-block;">
                                <input type="radio" name="easy_header_layout" value="horizontal" <?php checked($header_layout, 'horizontal'); ?> />
                                横並びレイアウト
                            </label>
                            <p class="description">横並びレイアウトではロゴ/タイトルとメニューが左右に配置されます。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">ヘッダー横幅</th>
                        <td>
                            <label>
                                <input type="radio" name="easy_header_width" value="full" <?php checked($header_width, 'full'); ?> />
                                フル幅
                            </label><br />
                            <label style="margin-top: 10px; display: inline-block;">
                                <input type="radio" name="easy_header_width" value="custom" <?php 
                                    if ($header_width !== 'full') {
                                        echo 'checked="checked"';
                                    }
                                ?> />
                                カスタム：
                                <input type="number" id="custom_header_width" name="easy_header_width_custom" 
                                       value="<?php echo $header_width !== 'full' ? esc_attr($header_width) : '960'; ?>" 
                                       min="300" max="1920" step="10" style="width: 80px; margin-left: 5px;" />px
                            </label>
                            <p class="description">ヘッダー内容の最大横幅を設定します。フル幅の場合はコンテナの幅いっぱいに表示されます。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">ヘッダーロゴ</th>
                        <td>
                            <input type="url" id="easy_header_logo" name="easy_header_logo" value="<?php echo esc_attr($header_logo); ?>" style="width: 100%; max-width: 300px;" />
                            <button type="button" id="upload_logo_button" class="button">ロゴを選択</button>
                            <?php if ($header_logo): ?>
                                <button type="button" id="remove_logo_button" class="button" style="margin-left: 5px;">削除</button>
                            <?php endif; ?>
                            <p class="description">ロゴ画像のURLを入力するか、「ロゴを選択」ボタンでメディアライブラリから選択してください。</p>
                            <?php if ($header_logo): ?>
                                <div id="logo_preview" style="margin-top: 10px;">
                                    <img src="<?php echo esc_url($header_logo); ?>" style="max-width: 200px; height: auto;" />
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">ロゴ幅</th>
                        <td>
                            <input type="number" id="easy_header_logo_width" name="easy_header_logo_width" 
                                   value="<?php echo esc_attr($header_logo_width === 'auto' ? '200' : $header_logo_width); ?>" 
                                   min="50" max="800" step="10" style="width: 100px;" />px
                            <p class="description">ロゴ画像の表示幅をピクセル単位で設定します。高さは自動で調整されます。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">ロゴ幅（スマホ）</th>
                        <td>
                            <input type="number" id="easy_header_logo_width_mobile" name="easy_header_logo_width_mobile" 
                                   value="<?php echo esc_attr($header_logo_width_mobile === 'auto' ? '120' : $header_logo_width_mobile); ?>" 
                                   min="30" max="300" step="5" style="width: 100px;" />px
                            <p class="description">スマホでのロゴ画像の表示幅をピクセル単位で設定します。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">ヘッダータイトル</th>
                        <td>
                            <input type="text" id="easy_header_title" name="easy_header_title" value="<?php echo esc_attr($header_title); ?>" style="width: 100%; max-width: 400px;" />
                            <p class="description">ヘッダーに表示するタイトルを入力してください。空の場合はページタイトルが表示されます。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">ヘッダーサブタイトル</th>
                        <td>
                            <input type="text" id="easy_header_subtitle" name="easy_header_subtitle" value="<?php echo esc_attr($header_subtitle); ?>" style="width: 100%; max-width: 400px;" />
                            <p class="description">タイトル下に表示するサブタイトルを入力してください（任意）。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">サブタイトル背景色</th>
                        <td>
                            <input type="color" id="easy_header_subtitle_bg_color" name="easy_header_subtitle_bg_color" value="<?php echo esc_attr($header_subtitle_bg_color === 'rgba(0,0,0,0.1)' ? '#1a1a1a' : $header_subtitle_bg_color); ?>" />
                            <p class="description">サブタイトルの背景色を選択してください。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">サブタイトル文字色</th>
                        <td>
                            <input type="color" id="easy_header_subtitle_text_color" name="easy_header_subtitle_text_color" value="<?php echo esc_attr($header_subtitle_text_color === 'inherit' ? '#000000' : $header_subtitle_text_color); ?>" />
                            <p class="description">サブタイトルの文字色を選択してください。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">リンクURL</th>
                        <td>
                            <input type="url" id="easy_header_link_url" name="easy_header_link_url" value="<?php echo esc_attr($header_link_url); ?>" style="width: 100%; max-width: 400px;" />
                            <p class="description">ロゴやタイトルにリンクを設定する場合は、URLを入力してください（任意）。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">背景色</th>
                        <td>
                            <input type="color" id="easy_header_bg_color" name="easy_header_bg_color" value="<?php echo esc_attr($header_bg_color); ?>" />
                            <p class="description">ヘッダーの背景色を選択してください。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">文字色</th>
                        <td>
                            <input type="color" id="easy_header_text_color" name="easy_header_text_color" value="<?php echo esc_attr($header_text_color); ?>" />
                            <p class="description">ヘッダーの文字色を選択してください。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">ナビゲーションメニュー</th>
                        <td>
                            <select id="easy_header_menu_id" name="easy_header_menu_id" style="max-width: 300px;">
                                <option value="">メニューを選択してください</option>
                                <?php
                                $menus = wp_get_nav_menus();
                                foreach ($menus as $menu) {
                                    echo '<option value="' . esc_attr($menu->term_id) . '"' . selected($header_menu_id, $menu->term_id, false) . '>' . esc_html($menu->name) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">ヘッダーに表示するナビゲーションメニューを選択してください（任意）。<br />
                            メニューは「外観」→「メニュー」で作成できます。ドロップダウンメニューにも対応しています。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">スクロール時の固定表示</th>
                        <td>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="easy_header_sticky_desktop" value="1" <?php checked($header_sticky_desktop, 1); ?> />
                                PCでスクロール時にヘッダーを上部に固定する
                            </label>
                            <label>
                                <input type="checkbox" name="easy_header_sticky_mobile" value="1" <?php checked($header_sticky_mobile, 1); ?> />
                                スマホでスクロール時にヘッダーを上部に固定する
                            </label>
                            <p class="description">チェックすると、スクロール時にヘッダーが画面上部に固定表示されます。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">シャドウ</th>
                        <td>
                            <label>
                                <input type="checkbox" name="easy_header_shadow" value="1" <?php checked($header_shadow, 1); ?> />
                                ヘッダーの下部にシャドウ（影）を表示する
                            </label>
                            <p class="description">チェックすると、ヘッダーの下部に薄い影が表示されます。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">カスタムCSS</th>
                        <td>
                            <textarea name="easy_header_custom_css" rows="8" style="width: 100%; max-width: 600px; font-family: Monaco, Consolas, 'Courier New', monospace; font-size: 12px;"><?php echo esc_textarea($header_custom_css); ?></textarea>
                            <p class="description">このヘッダー専用のカスタムCSSを記述できます。セレクタは .easy-custom-header で始めることを推奨します。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">カスタムJavaScript</th>
                        <td>
                            <textarea name="easy_header_custom_js" rows="8" style="width: 100%; max-width: 600px; font-family: Monaco, Consolas, 'Courier New', monospace; font-size: 12px;"><?php echo esc_textarea($header_custom_js); ?></textarea>
                            <p class="description">このヘッダー専用のカスタムJavaScriptを記述できます。DOMContentLoadedイベント内で実行されます。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">カスタムHTML（PCのみ）</th>
                        <td>
                            <textarea name="easy_header_custom_html" rows="8" style="width: 100%; max-width: 600px; font-family: Monaco, Consolas, 'Courier New', monospace; font-size: 12px;"><?php echo esc_textarea($header_custom_html); ?></textarea>
                            <p class="description">ヘッダーの右端（横並びレイアウト）または下部（縦並びレイアウト）に表示するカスタムHTMLを記述できます。ボタンやアイコンなどに使用してください。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">通知バー</th>
                        <td>
                            <label>
                                <input type="checkbox" name="easy_header_notice_enable" value="1" <?php checked($notice_enable, 1); ?> />
                                通知バーを有効化
                            </label>
                            <div style="margin-top: 10px;">
                                <label>通知テキスト:</label><br>
                                <input type="text" name="easy_header_notice_text" value="<?php echo esc_attr($notice_text); ?>" style="width: 100%; max-width: 600px;" placeholder="お知らせテキストを入力してください" />
                            </div>
                            <div style="margin-top: 10px;">
                                <label>リンクURL（任意）:</label><br>
                                <input type="url" name="easy_header_notice_url" value="<?php echo esc_attr($notice_url); ?>" style="width: 100%; max-width: 600px;" placeholder="https://example.com" />
                            </div>
                            <div style="margin-top: 10px;">
                                <label>
                                    <input type="checkbox" name="easy_header_notice_external" value="1" <?php checked($notice_external, 1); ?> />
                                    外部サイトを新しいタブで開く
                                </label>
                            </div>
                            <div style="margin-top: 10px;">
                                <label>背景色:</label><br>
                                <input type="color" name="easy_header_notice_bg_color" value="<?php echo esc_attr($notice_bg_color); ?>" />
                            </div>
                            <div style="margin-top: 10px;">
                                <label>文字色:</label><br>
                                <input type="color" name="easy_header_notice_text_color" value="<?php echo esc_attr($notice_text_color); ?>" />
                            </div>
                            <p class="description">ヘッダーの下に表示される通知バーです。PC・スマホ共通で表示されます。</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <style>
        #easy-header-settings {
            border: 1px solid #ccc;
            padding: 20px;
            background: #f9f9f9;
            margin-top: 15px;
        }
        
        #easy-header-settings h3 {
            margin-top: 0;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // 有効化チェックボックスの状態に応じて設定エリアの表示/非表示を切り替え
            $('#easy_header_enable').change(function() {
                if ($(this).is(':checked')) {
                    $('#easy-header-settings').slideDown();
                } else {
                    $('#easy-header-settings').slideUp();
                }
            });
            
            // カスタムヘッダー幅の処理
            $('input[name="easy_header_width"]').change(function() {
                if ($(this).val() === 'custom') {
                    $('#custom_header_width').focus();
                }
            });
            
            // カスタム値が入力された場合、対応するラジオボタンを選択
            $('#custom_header_width').on('input', function() {
                $('input[name="easy_header_width"][value="custom"]').prop('checked', true);
            });
            
            // メディアアップローダー
            $('#upload_logo_button').click(function(e) {
                e.preventDefault();
                
                var mediaUploader = wp.media({
                    title: 'ロゴ画像を選択',
                    button: {
                        text: 'この画像を使用'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#easy_header_logo').val(attachment.url);
                    // プレビューを更新
                    updateLogoPreview(attachment.url);
                });
                
                mediaUploader.open();
            });
            
            // ロゴ削除ボタン
            $('#remove_logo_button').click(function(e) {
                e.preventDefault();
                $('#easy_header_logo').val('');
                $('#logo_preview').hide();
                $(this).hide();
            });
            
            // ロゴプレビュー更新関数
            function updateLogoPreview(url) {
                if (url) {
                    if ($('#logo_preview').length) {
                        $('#logo_preview img').attr('src', url);
                        $('#logo_preview').show();
                    } else {
                        $('#easy_header_logo').parent().append('<div id="logo_preview" style="margin-top: 10px;"><img src="' + url + '" style="max-width: 200px; height: auto;" /></div>');
                    }
                    if ($('#remove_logo_button').length === 0) {
                        $('#upload_logo_button').after('<button type="button" id="remove_logo_button" class="button" style="margin-left: 5px;">削除</button>');
                        // 新しく作成された削除ボタンにイベントを追加
                        $('#remove_logo_button').click(function(e) {
                            e.preventDefault();
                            $('#easy_header_logo').val('');
                            $('#logo_preview').hide();
                            $(this).hide();
                        });
                    } else {
                        $('#remove_logo_button').show();
                    }
                }
            }
        });
        </script>
        <?php
    }
    
    /**
     * メタデータの保存
     */
    public function save_post_meta($post_id) {
        // nonce チェック
        if (!isset($_POST['easy_header_meta_box_nonce']) || !wp_verify_nonce($_POST['easy_header_meta_box_nonce'], 'easy_header_meta_box')) {
            return;
        }
        
        // 自動保存の場合は処理しない
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // 権限チェック
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // データの保存
        update_post_meta($post_id, '_easy_header_enable', isset($_POST['easy_header_enable']) ? 1 : 0);
        
        if (isset($_POST['easy_header_logo'])) {
            update_post_meta($post_id, '_easy_header_logo', sanitize_url($_POST['easy_header_logo']));
        }
        
        if (isset($_POST['easy_header_title'])) {
            update_post_meta($post_id, '_easy_header_title', sanitize_text_field($_POST['easy_header_title']));
        }
        
        if (isset($_POST['easy_header_subtitle'])) {
            update_post_meta($post_id, '_easy_header_subtitle', sanitize_text_field($_POST['easy_header_subtitle']));
        }
        
        if (isset($_POST['easy_header_bg_color'])) {
            update_post_meta($post_id, '_easy_header_bg_color', sanitize_hex_color($_POST['easy_header_bg_color']));
        }
        
        if (isset($_POST['easy_header_text_color'])) {
            update_post_meta($post_id, '_easy_header_text_color', sanitize_hex_color($_POST['easy_header_text_color']));
        }
        
        // ロゴ幅の処理
        if (isset($_POST['easy_header_logo_width'])) {
            update_post_meta($post_id, '_easy_header_logo_width', intval($_POST['easy_header_logo_width']));
        }
        
        if (isset($_POST['easy_header_layout'])) {
            update_post_meta($post_id, '_easy_header_layout', sanitize_text_field($_POST['easy_header_layout']));
        }
        
        if (isset($_POST['easy_header_link_url'])) {
            update_post_meta($post_id, '_easy_header_link_url', sanitize_url($_POST['easy_header_link_url']));
        }
        
        if (isset($_POST['easy_header_menu_id'])) {
            update_post_meta($post_id, '_easy_header_menu_id', intval($_POST['easy_header_menu_id']));
        }
        
        // ヘッダー幅の処理
        if (isset($_POST['easy_header_width'])) {
            if ($_POST['easy_header_width'] === 'custom' && isset($_POST['easy_header_width_custom'])) {
                update_post_meta($post_id, '_easy_header_width', intval($_POST['easy_header_width_custom']));
            } else {
                update_post_meta($post_id, '_easy_header_width', sanitize_text_field($_POST['easy_header_width']));
            }
        }
        
        // スマホ用ロゴ幅の処理
        if (isset($_POST['easy_header_logo_width_mobile'])) {
            update_post_meta($post_id, '_easy_header_logo_width_mobile', intval($_POST['easy_header_logo_width_mobile']));
        }
        
        // サブタイトル背景色の処理
        if (isset($_POST['easy_header_subtitle_bg_color'])) {
            update_post_meta($post_id, '_easy_header_subtitle_bg_color', sanitize_text_field($_POST['easy_header_subtitle_bg_color']));
        }
        
        // サブタイトル文字色の処理
        if (isset($_POST['easy_header_subtitle_text_color_inherit']) && $_POST['easy_header_subtitle_text_color_inherit'] == '1') {
            update_post_meta($post_id, '_easy_header_subtitle_text_color', 'inherit');
        } elseif (isset($_POST['easy_header_subtitle_text_color'])) {
            update_post_meta($post_id, '_easy_header_subtitle_text_color', sanitize_hex_color($_POST['easy_header_subtitle_text_color']));
        }
        
        // スティッキー設定の処理
        update_post_meta($post_id, '_easy_header_sticky_desktop', isset($_POST['easy_header_sticky_desktop']) ? 1 : 0);
        update_post_meta($post_id, '_easy_header_sticky_mobile', isset($_POST['easy_header_sticky_mobile']) ? 1 : 0);
        
        // シャドウ設定の処理
        update_post_meta($post_id, '_easy_header_shadow', isset($_POST['easy_header_shadow']) ? 1 : 0);
        
        // カスタムCSSの処理
        if (isset($_POST['easy_header_custom_css'])) {
            update_post_meta($post_id, '_easy_header_custom_css', wp_unslash($_POST['easy_header_custom_css']));
        }
        
        // カスタムJSの処理
        if (isset($_POST['easy_header_custom_js'])) {
            update_post_meta($post_id, '_easy_header_custom_js', wp_unslash($_POST['easy_header_custom_js']));
        }
        
        // カスタムHTMLの処理
        if (isset($_POST['easy_header_custom_html'])) {
            update_post_meta($post_id, '_easy_header_custom_html', wp_unslash($_POST['easy_header_custom_html']));
        }
        
        // 通知バーの処理
        update_post_meta($post_id, '_easy_header_notice_enable', isset($_POST['easy_header_notice_enable']) ? 1 : 0);
        if (isset($_POST['easy_header_notice_text'])) {
            update_post_meta($post_id, '_easy_header_notice_text', sanitize_text_field($_POST['easy_header_notice_text']));
        }
        if (isset($_POST['easy_header_notice_url'])) {
            update_post_meta($post_id, '_easy_header_notice_url', esc_url_raw($_POST['easy_header_notice_url']));
        }
        update_post_meta($post_id, '_easy_header_notice_external', isset($_POST['easy_header_notice_external']) ? 1 : 0);
        if (isset($_POST['easy_header_notice_bg_color'])) {
            update_post_meta($post_id, '_easy_header_notice_bg_color', sanitize_hex_color($_POST['easy_header_notice_bg_color']));
        }
        if (isset($_POST['easy_header_notice_text_color'])) {
            update_post_meta($post_id, '_easy_header_notice_text_color', sanitize_hex_color($_POST['easy_header_notice_text_color']));
        }
    }
    
    /**
     * 管理画面でのスクリプトとスタイルの読み込み
     */
    public function admin_scripts($hook) {
        // 投稿編集画面でのみ読み込み
        if ($hook == 'post.php' || $hook == 'post-new.php' || $hook == 'toplevel_page_easy-header-maker') {
            wp_enqueue_media();
            wp_enqueue_script('jquery');
        }
    }
    
    /**
     * 管理画面メニューを追加
     */
    public function add_admin_menu() {
        add_options_page(
            'Easy Header Maker',
            'Easy Header Maker',
            'manage_options',
            'easy-header-maker',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 管理画面ページ
     */
    public function admin_page() {
        // 設定の保存
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['easy_header_nonce'], 'easy_header_settings')) {
            // 投稿タイプ設定を保存
            $enabled_post_types = isset($_POST['easy_header_enabled_post_types']) ? $_POST['easy_header_enabled_post_types'] : array('page');
            update_option('easy_header_enabled_post_types', array_map('sanitize_text_field', $enabled_post_types));
            
            echo '<div class="notice notice-success is-dismissible"><p>設定が保存されました。</p></div>';
        }
        
        // 現在の設定を取得
        $enabled_post_types = get_option('easy_header_enabled_post_types', array('page'));
        ?>
        <div class="wrap">
            <h1>Easy Header Maker</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('easy_header_settings', 'easy_header_nonce'); ?>
                
                <h2>基本設定</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">対象投稿タイプ</th>
                        <td>
                            <?php
                            $all_post_types = get_post_types(array('public' => true), 'objects');
                            foreach ($all_post_types as $post_type_obj) {
                                if ($post_type_obj->name === 'attachment') continue;
                                $checked = in_array($post_type_obj->name, $enabled_post_types) ? 'checked' : '';
                                echo '<label style="display: block; margin-bottom: 5px;">';
                                echo '<input type="checkbox" name="easy_header_enabled_post_types[]" value="' . esc_attr($post_type_obj->name) . '" ' . $checked . ' />';
                                echo esc_html($post_type_obj->label) . ' (' . esc_html($post_type_obj->name) . ')';
                                echo '</label>';
                            }
                            ?>
                            <p class="description">チェックした投稿タイプの編集画面に「独自ヘッダー設定」メタボックスが表示されます。</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="設定を保存" />
                </p>
            </form>

            <div style="max-width: 800px; margin: 20px 0 50px;">
                <h2>使い方</h2>
                <ol>
                    <li><strong>基本設定</strong>：下記の「対象投稿タイプ」で、ヘッダー設定を利用したい投稿タイプを選択</li>
                    <li><strong>個別ページ設定</strong>：選択した投稿タイプの編集画面で「独自ヘッダー設定」メタボックスが表示され、各ページごとに設定可能</li>
                    <li><strong>ナビゲーションメニュー</strong>：「外観」→「<a href="/wp-admin/nav-menus.php" target="_blank">メニュー</a>」で作成したメニューをヘッダーに表示可能</li>
                    <li><strong>レスポンシブ対応</strong>：モバイルデバイスでも適切に表示されます</li>
                </ol>
                <p>詳しくはこちらの<a href="https://sokulabo.com/products/easy-header-maker/" target="_blank">マニュアル</a>をご確認ください。</p>
            </div>
        </div>
        <?php
    }
    
    /**
     * テスト用ショートコード
     */
    public function header_test_shortcode() {
        ob_start();
        echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">';
        echo '<h3>Easy Header Maker テスト表示</h3>';
        
        $header_data = $this->get_header_data();
        if ($header_data) {
            echo '<p><strong>カスタムヘッダーが有効です</strong></p>';
            echo '<ul>';
            echo '<li>タイトル: ' . esc_html($header_data['header_title']) . '</li>';
            echo '<li>サブタイトル: ' . esc_html($header_data['header_subtitle']) . '</li>';
            echo '<li>背景色: ' . esc_html($header_data['header_bg_color']) . '</li>';
            echo '<li>文字色: ' . esc_html($header_data['header_text_color']) . '</li>';
            echo '<li>レイアウト: ' . esc_html($header_data['header_layout']) . '</li>';
            echo '<li>横幅: ' . esc_html($header_data['header_width']) . '</li>';
            if ($header_data['header_logo']) {
                echo '<li>ロゴ: <img src="' . esc_url($header_data['header_logo']) . '" style="max-height: 50px;" /></li>';
            }
            echo '</ul>';
        } else {
            echo '<p><strong>カスタムヘッダーは無効です</strong></p>';
        }
        
        echo '</div>';
        return ob_get_clean();
    }
}

// プラグインを初期化
new EasyHeaderMaker();