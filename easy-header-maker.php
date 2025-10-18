<?php
/**
 * Plugin Name: Easy Header Maker
 * Description: WordPressで簡単にページごとの独自ヘッダーを作成できるプラグイン
 * Version: 1.0.0
 * Author: Your Name
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
        $post_types = array('post', 'page');
        foreach ($post_types as $post_type) {
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
        }
    }
    
    /**
     * メタボックスの追加
     */
    public function add_meta_boxes() {
        add_meta_box(
            'easy_header_meta_box',
            '独自ヘッダー設定',
            array($this, 'meta_box_callback'),
            array('post', 'page'),
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
        // フロントページの場合
        elseif (is_front_page()) {
            // まず管理画面の設定をチェック
            $front_enable = get_option('easy_header_front_enable', 0);
            
            if ($front_enable) {
                // フロントページ用の設定を直接使用
                return array(
                    'enable_custom_header' => true,
                    'header_logo' => get_option('easy_header_front_logo', ''),
                    'header_title' => get_option('easy_header_front_title', '') ?: get_bloginfo('name'),
                    'header_subtitle' => get_option('easy_header_front_subtitle', ''),
                    'header_bg_color' => get_option('easy_header_front_bg_color', '#ffffff'),
                    'header_text_color' => get_option('easy_header_front_text_color', '#000000'),
                    'header_logo_width' => get_option('easy_header_front_logo_width', '200'),
                    'header_logo_width_mobile' => get_option('easy_header_front_logo_width_mobile', '120'),
                    'header_subtitle_bg_color' => get_option('easy_header_front_subtitle_bg_color', '#ddd'),
                    'header_subtitle_text_color' => get_option('easy_header_front_subtitle_text_color', '#ffffff'),
                    'header_layout' => get_option('easy_header_front_layout', 'center'),
                    'header_link_url' => get_option('easy_header_front_link_url', ''),
                    'header_menu_id' => get_option('easy_header_front_menu_id', ''),
                    'header_width' => get_option('easy_header_front_width', 'full'),
                    'header_sticky_desktop' => get_option('easy_header_front_sticky_desktop', 0),
                    'header_sticky_mobile' => get_option('easy_header_front_sticky_mobile', 0)
                );
            }
            // 管理画面設定が無効の場合、静的フロントページの設定をチェック
            elseif (get_option('show_on_front') == 'page') {
                $post_id = get_option('page_on_front');
                if ($post_id) {
                    $enable_custom_header = get_post_meta($post_id, '_easy_header_enable', true);
                }
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
            'header_sticky_mobile' => get_post_meta($post_id, '_easy_header_sticky_mobile', true)
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
                <?php if ($header_sticky_desktop && $header_sticky_mobile): ?>
                position: sticky;
                top: 0;
                <?php endif; ?>
            }
            
            /* デスクトップでのスティッキー設定 */
            @media (min-width: 769px) {
                <?php if ($header_sticky_desktop && !$header_sticky_mobile): ?>
                .easy-custom-header {
                    position: sticky;
                    top: 0;
                }
                <?php endif; ?>
            }
            
            /* モバイルでのスティッキー設定 */
            @media (max-width: 768px) {
                <?php if ($header_sticky_mobile && !$header_sticky_desktop): ?>
                .easy-custom-header {
                    position: sticky;
                    top: 0;
                }
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
                gap: 20px;
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
                padding: 8px 4px;
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
                    padding: 26px 30px;
                    box-sizing: content-box;
                }
                
                .easy-custom-header.layout-center:has(.header-navigation) .header-inner {
                    padding-top: 36px;
                }

                /* 横レイアウトでのサブタイトル調整 */
                .easy-custom-header.layout-horizontal .header-subtitle {
                    background: <?php echo esc_attr($header_subtitle_bg_color); ?>;
                    color: <?php echo esc_attr($header_subtitle_text_color); ?>;
                    font-size: 14px;
                    padding: 6px 0;
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
                <?php endif; ?>
            </div>
            
            <?php if (!empty($header_menu_id)): ?>
                <div class="menu-overlay"></div>
            <?php endif; ?>
        </div>
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
        ?>
        <script id="easy-header-scripts">
        document.addEventListener("DOMContentLoaded", function() {
            var hamburgerBtn = document.querySelector(".hamburger-menu");
            var menu = document.querySelector(".easy-header-menu");
            var overlay = document.querySelector(".menu-overlay");
            
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
                            <label style="margin-top: 5px; display: inline-block;">
                                <input type="radio" name="easy_header_width" value="1200" <?php checked($header_width, '1200'); ?> />
                                1200px
                            </label><br />
                            <label style="margin-top: 5px; display: inline-block;">
                                <input type="radio" name="easy_header_width" value="1000" <?php checked($header_width, '1000'); ?> />
                                1000px
                            </label><br />
                            <label style="margin-top: 5px; display: inline-block;">
                                <input type="radio" name="easy_header_width" value="800" <?php checked($header_width, '800'); ?> />
                                800px
                            </label><br />
                            <label style="margin-top: 10px; display: inline-block;">
                                <input type="radio" name="easy_header_width" value="custom" <?php 
                                    if (!in_array($header_width, array('full', '1200', '1000', '800'))) {
                                        echo 'checked="checked"';
                                    }
                                ?> />
                                カスタム：
                                <input type="number" id="custom_header_width" name="easy_header_width_custom" 
                                       value="<?php echo !in_array($header_width, array('full', '1200', '1000', '800')) ? esc_attr($header_width) : '960'; ?>" 
                                       min="300" max="1920" step="10" style="width: 80px; margin-left: 5px;" />px
                            </label>
                            <p class="description">ヘッダー内容の最大横幅を設定します。フル幅の場合はコンテナの幅いっぱいに表示されます。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">ヘッダーロゴ</th>
                        <td>
                            <input type="url" id="easy_header_logo" name="easy_header_logo" value="<?php echo esc_attr($header_logo); ?>" style="width: 100%; max-width: 400px;" />
                            <button type="button" id="upload_logo_button" class="button">ロゴを選択</button>
                            <p class="description">ロゴ画像のURLを入力するか、「ロゴを選択」ボタンでメディアライブラリから選択してください。</p>
                            <?php if ($header_logo): ?>
                                <div style="margin-top: 10px;">
                                    <img src="<?php echo esc_url($header_logo); ?>" style="max-width: 200px; height: auto;" />
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">ロゴ幅</th>
                        <td>
                            <label>
                                <input type="radio" name="easy_header_logo_width" value="auto" <?php checked($header_logo_width, 'auto'); ?> />
                                オリジナルサイズ
                            </label><br />
                            <label style="margin-top: 5px; display: inline-block;">
                                <input type="radio" name="easy_header_logo_width" value="150" <?php checked($header_logo_width, '150'); ?> />
                                150px
                            </label><br />
                            <label style="margin-top: 5px; display: inline-block;">
                                <input type="radio" name="easy_header_logo_width" value="200" <?php checked($header_logo_width, '200'); ?> />
                                200px
                            </label><br />
                            <label style="margin-top: 5px; display: inline-block;">
                                <input type="radio" name="easy_header_logo_width" value="300" <?php checked($header_logo_width, '300'); ?> />
                                300px
                            </label><br />
                            <label style="margin-top: 10px; display: inline-block;">
                                <input type="radio" name="easy_header_logo_width" value="custom" <?php 
                                    if (!in_array($header_logo_width, array('auto', '150', '200', '300'))) {
                                        echo 'checked="checked"';
                                    }
                                ?> />
                                カスタム：
                                <input type="number" id="custom_logo_width" name="easy_header_logo_width_custom" 
                                       value="<?php echo !in_array($header_logo_width, array('auto', '150', '200', '300')) ? esc_attr($header_logo_width) : '250'; ?>" 
                                       min="50" max="800" step="10" style="width: 80px; margin-left: 5px;" />px
                            </label>
                            <p class="description">ロゴ画像の表示幅を設定します。高さは自動で調整されます。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">ロゴ幅（スマホ）</th>
                        <td>
                            <label>
                                <input type="radio" name="easy_header_logo_width_mobile" value="auto" <?php checked($header_logo_width_mobile, 'auto'); ?> />
                                オリジナルサイズ
                            </label><br />
                            <label style="margin-top: 5px; display: inline-block;">
                                <input type="radio" name="easy_header_logo_width_mobile" value="80" <?php checked($header_logo_width_mobile, '80'); ?> />
                                80px
                            </label><br />
                            <label style="margin-top: 5px; display: inline-block;">
                                <input type="radio" name="easy_header_logo_width_mobile" value="120" <?php checked($header_logo_width_mobile, '120'); ?> />
                                120px
                            </label><br />
                            <label style="margin-top: 5px; display: inline-block;">
                                <input type="radio" name="easy_header_logo_width_mobile" value="150" <?php checked($header_logo_width_mobile, '150'); ?> />
                                150px
                            </label><br />
                            <label style="margin-top: 10px; display: inline-block;">
                                <input type="radio" name="easy_header_logo_width_mobile" value="custom" <?php 
                                    if (!in_array($header_logo_width_mobile, array('auto', '80', '120', '150'))) {
                                        echo 'checked="checked"';
                                    }
                                ?> />
                                カスタム：
                                <input type="number" id="custom_logo_width_mobile" name="easy_header_logo_width_mobile_custom" 
                                       value="<?php echo !in_array($header_logo_width_mobile, array('auto', '80', '120', '150')) ? esc_attr($header_logo_width_mobile) : '100'; ?>" 
                                       min="30" max="300" step="5" style="width: 80px; margin-left: 5px;" />px
                            </label>
                            <p class="description">スマホでのロゴ画像の表示幅を設定します。</p>
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
            
            // カスタムロゴ幅の処理
            $('input[name="easy_header_logo_width"]').change(function() {
                if ($(this).val() === 'custom') {
                    $('#custom_logo_width').focus();
                }
            });
            
            // カスタムスマホロゴ幅の処理
            $('input[name="easy_header_logo_width_mobile"]').change(function() {
                if ($(this).val() === 'custom') {
                    $('#custom_logo_width_mobile').focus();
                }
            });
            
            // カスタムヘッダー幅の処理
            $('input[name="easy_header_width"]').change(function() {
                if ($(this).val() === 'custom') {
                    $('#custom_header_width').focus();
                }
            });
            
            // カスタム値が入力された場合、対応するラジオボタンを選択
            $('#custom_logo_width').on('input', function() {
                $('input[name="easy_header_logo_width"][value="custom"]').prop('checked', true);
            });
            
            $('#custom_logo_width_mobile').on('input', function() {
                $('input[name="easy_header_logo_width_mobile"][value="custom"]').prop('checked', true);
            });
            
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
                });
                
                mediaUploader.open();
            });
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
            if ($_POST['easy_header_logo_width'] === 'custom' && isset($_POST['easy_header_logo_width_custom'])) {
                update_post_meta($post_id, '_easy_header_logo_width', intval($_POST['easy_header_logo_width_custom']));
            } else {
                update_post_meta($post_id, '_easy_header_logo_width', sanitize_text_field($_POST['easy_header_logo_width']));
            }
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
            if ($_POST['easy_header_logo_width_mobile'] === 'custom' && isset($_POST['easy_header_logo_width_mobile_custom'])) {
                update_post_meta($post_id, '_easy_header_logo_width_mobile', intval($_POST['easy_header_logo_width_mobile_custom']));
            } else {
                update_post_meta($post_id, '_easy_header_logo_width_mobile', sanitize_text_field($_POST['easy_header_logo_width_mobile']));
            }
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
        add_menu_page(
            'Easy Header Maker',
            'Header Maker',
            'manage_options',
            'easy-header-maker',
            array($this, 'admin_page'),
            'dashicons-editor-kitchensink',
            30
        );
    }
    
    /**
     * 管理画面ページ
     */
    public function admin_page() {
        // 設定の保存
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['easy_header_nonce'], 'easy_header_settings')) {
            // フロントページの設定を保存
            update_option('easy_header_front_enable', isset($_POST['easy_header_front_enable']) ? 1 : 0);
            update_option('easy_header_front_logo', sanitize_url($_POST['easy_header_front_logo']));
            update_option('easy_header_front_title', sanitize_text_field($_POST['easy_header_front_title']));
            update_option('easy_header_front_subtitle', sanitize_text_field($_POST['easy_header_front_subtitle']));
            update_option('easy_header_front_bg_color', sanitize_hex_color($_POST['easy_header_front_bg_color']));
            update_option('easy_header_front_text_color', sanitize_hex_color($_POST['easy_header_front_text_color']));
            
            // ロゴ幅の処理
            if ($_POST['easy_header_front_logo_width'] === 'custom' && isset($_POST['easy_header_front_logo_width_custom'])) {
                update_option('easy_header_front_logo_width', intval($_POST['easy_header_front_logo_width_custom']));
            } else {
                update_option('easy_header_front_logo_width', sanitize_text_field($_POST['easy_header_front_logo_width']));
            }
            
            update_option('easy_header_front_layout', sanitize_text_field($_POST['easy_header_front_layout']));
            update_option('easy_header_front_link_url', sanitize_url($_POST['easy_header_front_link_url']));
            update_option('easy_header_front_menu_id', intval($_POST['easy_header_front_menu_id']));
            
            // モバイル用ロゴ幅の処理
            if (isset($_POST['easy_header_front_logo_width_mobile'])) {
                if ($_POST['easy_header_front_logo_width_mobile'] === 'custom' && isset($_POST['easy_header_front_logo_width_mobile_custom'])) {
                    update_option('easy_header_front_logo_width_mobile', intval($_POST['easy_header_front_logo_width_mobile_custom']));
                } else {
                    update_option('easy_header_front_logo_width_mobile', sanitize_text_field($_POST['easy_header_front_logo_width_mobile']));
                }
            }
            
            // サブタイトル色設定の処理
            if (isset($_POST['easy_header_front_subtitle_bg_color'])) {
                update_option('easy_header_front_subtitle_bg_color', sanitize_text_field($_POST['easy_header_front_subtitle_bg_color']));
            }
            if (isset($_POST['easy_header_front_subtitle_text_color'])) {
                update_option('easy_header_front_subtitle_text_color', sanitize_text_field($_POST['easy_header_front_subtitle_text_color']));
            }
            
            // ヘッダー幅の処理
            if ($_POST['easy_header_front_width'] === 'custom' && isset($_POST['easy_header_front_width_custom'])) {
                update_option('easy_header_front_width', intval($_POST['easy_header_front_width_custom']));
            } else {
                update_option('easy_header_front_width', sanitize_text_field($_POST['easy_header_front_width']));
            }
            
            // スティッキー設定の処理
            update_option('easy_header_front_sticky_desktop', isset($_POST['easy_header_front_sticky_desktop']) ? 1 : 0);
            update_option('easy_header_front_sticky_mobile', isset($_POST['easy_header_front_sticky_mobile']) ? 1 : 0);
            
            echo '<div class="notice notice-success is-dismissible"><p>設定が保存されました。</p></div>';
        }
        
        // 現在の設定を取得
        $front_enable = get_option('easy_header_front_enable', 0);
        $front_logo = get_option('easy_header_front_logo', '');
        $front_title = get_option('easy_header_front_title', '');
        $front_subtitle = get_option('easy_header_front_subtitle', '');
        $front_bg_color = get_option('easy_header_front_bg_color', '#ffffff');
        $front_text_color = get_option('easy_header_front_text_color', '#000000');
        $front_logo_width = get_option('easy_header_front_logo_width', '200');
        $front_logo_width_mobile = get_option('easy_header_front_logo_width_mobile', '120');
        $front_subtitle_bg_color = get_option('easy_header_front_subtitle_bg_color', '#ddd');
        $front_subtitle_text_color = get_option('easy_header_front_subtitle_text_color', '#ffffff');
        $front_layout = get_option('easy_header_front_layout', 'center');
        $front_link_url = get_option('easy_header_front_link_url', '');
        $front_menu_id = get_option('easy_header_front_menu_id', '');
        $front_width = get_option('easy_header_front_width', 'full');
        $front_sticky_desktop = get_option('easy_header_front_sticky_desktop', 0);
        $front_sticky_mobile = get_option('easy_header_front_sticky_mobile', 0);
        ?>
        <div class="wrap">
            <h1>Easy Header Maker 設定</h1>
            
            <div style="max-width: 800px; margin: 20px 0;">
                <h2>使い方</h2>
                <ol>
                    <li><strong>個別ページ設定</strong>：投稿・ページ編集画面の「独自ヘッダー設定」メタボックスで各ページごとに設定</li>
                    <li><strong>フロントページ設定</strong>：下記の設定でサイトのトップページ用ヘッダーを設定</li>
                    <li><strong>ナビゲーションメニュー</strong>：「外観」→「メニュー」で作成したメニューをヘッダーに表示可能</li>
                    <li><strong>レスポンシブ対応</strong>：モバイルデバイスでも適切に表示されます</li>
                </ol>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('easy_header_settings', 'easy_header_nonce'); ?>
                
                <h2>フロントページ設定</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">フロントページヘッダーを有効化</th>
                        <td>
                            <label>
                                <input type="checkbox" name="easy_header_front_enable" value="1" <?php checked($front_enable, 1); ?> />
                                フロントページに独自ヘッダーを表示する
                            </label>
                            <p class="description">個別ページの設定より優先されます。</p>
                        </td>
                    </tr>
                </table>
                
                <div id="front-header-settings" style="<?php echo !$front_enable ? 'display: none;' : ''; ?>">
                    <table class="form-table">
                        <tr>
                            <th scope="row">レイアウト</th>
                            <td>
                                <label>
                                    <input type="radio" name="easy_header_front_layout" value="center" <?php checked($front_layout, 'center'); ?> />
                                    中央寄せ（縦レイアウト）
                                </label><br />
                                <label style="margin-top: 5px; display: inline-block;">
                                    <input type="radio" name="easy_header_front_layout" value="horizontal" <?php checked($front_layout, 'horizontal'); ?> />
                                    横並びレイアウト
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">ヘッダー横幅</th>
                            <td>
                                <label>
                                    <input type="radio" name="easy_header_front_width" value="full" <?php checked($front_width, 'full'); ?> />
                                    フル幅
                                </label><br />
                                <label style="margin-top: 5px; display: inline-block;">
                                    <input type="radio" name="easy_header_front_width" value="1200" <?php checked($front_width, '1200'); ?> />
                                    1200px
                                </label><br />
                                <label style="margin-top: 5px; display: inline-block;">
                                    <input type="radio" name="easy_header_front_width" value="1000" <?php checked($front_width, '1000'); ?> />
                                    1000px
                                </label><br />
                                <label style="margin-top: 5px; display: inline-block;">
                                    <input type="radio" name="easy_header_front_width" value="800" <?php checked($front_width, '800'); ?> />
                                    800px
                                </label><br />
                                <label style="margin-top: 10px; display: inline-block;">
                                    <input type="radio" name="easy_header_front_width" value="custom" <?php 
                                        if (!in_array($front_width, array('full', '1200', '1000', '800'))) {
                                            echo 'checked="checked"';
                                        }
                                    ?> />
                                    カスタム：
                                    <input type="number" id="front_custom_header_width" name="easy_header_front_width_custom" 
                                           value="<?php echo !in_array($front_width, array('full', '1200', '1000', '800')) ? esc_attr($front_width) : '960'; ?>" 
                                           min="300" max="1920" step="10" style="width: 80px; margin-left: 5px;" />px
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">ヘッダーロゴ</th>
                            <td>
                                <input type="url" id="easy_header_front_logo" name="easy_header_front_logo" value="<?php echo esc_attr($front_logo); ?>" style="width: 100%; max-width: 400px;" />
                                <button type="button" id="upload_front_logo_button" class="button">ロゴを選択</button>
                                <?php if ($front_logo): ?>
                                    <div style="margin-top: 10px;">
                                        <img src="<?php echo esc_url($front_logo); ?>" style="max-width: 200px; height: auto;" />
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">ロゴ幅</th>
                            <td>
                                <label>
                                    <input type="radio" name="easy_header_front_logo_width" value="auto" <?php checked($front_logo_width, 'auto'); ?> />
                                    オリジナルサイズ
                                </label><br />
                                <label style="margin-top: 5px; display: inline-block;">
                                    <input type="radio" name="easy_header_front_logo_width" value="150" <?php checked($front_logo_width, '150'); ?> />
                                    150px
                                </label><br />
                                <label style="margin-top: 5px; display: inline-block;">
                                    <input type="radio" name="easy_header_front_logo_width" value="200" <?php checked($front_logo_width, '200'); ?> />
                                    200px
                                </label><br />
                                <label style="margin-top: 5px; display: inline-block;">
                                    <input type="radio" name="easy_header_front_logo_width" value="300" <?php checked($front_logo_width, '300'); ?> />
                                    300px
                                </label><br />
                                <label style="margin-top: 10px; display: inline-block;">
                                    <input type="radio" name="easy_header_front_logo_width" value="custom" <?php 
                                        if (!in_array($front_logo_width, array('auto', '150', '200', '300'))) {
                                            echo 'checked="checked"';
                                        }
                                    ?> />
                                    カスタム：
                                    <input type="number" id="front_custom_logo_width" name="easy_header_front_logo_width_custom" 
                                           value="<?php echo !in_array($front_logo_width, array('auto', '150', '200', '300')) ? esc_attr($front_logo_width) : '250'; ?>" 
                                           min="50" max="800" step="10" style="width: 80px; margin-left: 5px;" />px
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">ヘッダータイトル</th>
                            <td>
                                <input type="text" name="easy_header_front_title" value="<?php echo esc_attr($front_title); ?>" style="width: 100%; max-width: 400px;" />
                                <p class="description">空の場合はサイトのタイトルが表示されます。</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">ヘッダーサブタイトル</th>
                            <td>
                                <input type="text" name="easy_header_front_subtitle" value="<?php echo esc_attr($front_subtitle); ?>" style="width: 100%; max-width: 400px;" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">リンクURL</th>
                            <td>
                                <input type="url" name="easy_header_front_link_url" value="<?php echo esc_attr($front_link_url); ?>" style="width: 100%; max-width: 400px;" />
                                <p class="description">ロゴやタイトルにリンクを設定する場合は、URLを入力してください（任意）。</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">背景色</th>
                            <td>
                                <input type="color" name="easy_header_front_bg_color" value="<?php echo esc_attr($front_bg_color); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">文字色</th>
                            <td>
                                <input type="color" name="easy_header_front_text_color" value="<?php echo esc_attr($front_text_color); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">ナビゲーションメニュー</th>
                            <td>
                                <select name="easy_header_front_menu_id" style="max-width: 300px;">
                                    <option value="">メニューを選択してください</option>
                                    <?php
                                    $menus = wp_get_nav_menus();
                                    foreach ($menus as $menu) {
                                        echo '<option value="' . esc_attr($menu->term_id) . '"' . selected($front_menu_id, $menu->term_id, false) . '>' . esc_html($menu->name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // フロントページヘッダーの有効化チェック
            $('input[name="easy_header_front_enable"]').change(function() {
                if ($(this).is(':checked')) {
                    $('#front-header-settings').slideDown();
                } else {
                    $('#front-header-settings').slideUp();
                }
            });
            
            // フロントページロゴアップローダー
            $('#upload_front_logo_button').click(function(e) {
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
                    $('#easy_header_front_logo').val(attachment.url);
                });
                
                mediaUploader.open();
            });
            
            // カスタム値の処理
            $('input[name="easy_header_front_logo_width"]').change(function() {
                if ($(this).val() === 'custom') {
                    $('#front_custom_logo_width').focus();
                }
            });
            
            $('input[name="easy_header_front_width"]').change(function() {
                if ($(this).val() === 'custom') {
                    $('#front_custom_header_width').focus();
                }
            });
            
            $('#front_custom_logo_width').on('input', function() {
                $('input[name="easy_header_front_logo_width"][value="custom"]').prop('checked', true);
            });
            
            $('#front_custom_header_width').on('input', function() {
                $('input[name="easy_header_front_width"][value="custom"]').prop('checked', true);
            });
        });
        </script>
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