<?php
/*
 * Plugin Name: Easy Header Maker
 * Description: ページごとに独自のヘッダーを追加できるプラグイン
 * Version: 1.0.0
 * Author: Shota Takazawa
 * Author URI: https://github.com/takazawashota/easy-header-maker/
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
        
        // フロントエンドでヘッダーを表示
        add_action('wp_head', array($this, 'display_custom_header'), 1);
        add_action('wp_footer', array($this, 'display_custom_header_fallback'), 1);
        
        // 管理画面でのスクリプトとスタイル
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // 管理画面メニューを追加
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // テスト用のショートコード
        add_shortcode('easy_header_test', array($this, 'header_test_shortcode'));
    }
    
    /**
     * メタフィールドを登録
     */
    public function register_meta_fields() {
        $post_types = get_post_types(array('public' => true), 'names');
        
        foreach ($post_types as $post_type) {
            register_post_meta($post_type, '_easy_header_enable', array(
                'single' => true,
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean'
            ));
            
            register_post_meta($post_type, '_easy_header_logo', array(
                'single' => true,
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => 'esc_url_raw'
            ));
            
            register_post_meta($post_type, '_easy_header_title', array(
                'single' => true,
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field'
            ));
            
            register_post_meta($post_type, '_easy_header_subtitle', array(
                'single' => true,
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field'
            ));
            
            register_post_meta($post_type, '_easy_header_bg_color', array(
                'single' => true,
                'type' => 'string',
                'default' => '#ffffff',
                'sanitize_callback' => 'sanitize_hex_color'
            ));
            
            register_post_meta($post_type, '_easy_header_text_color', array(
                'single' => true,
                'type' => 'string',
                'default' => '#000000',
                'sanitize_callback' => 'sanitize_hex_color'
            ));
            
            register_post_meta($post_type, '_easy_header_logo_width', array(
                'single' => true,
                'type' => 'string',
                'default' => '200',
                'sanitize_callback' => 'sanitize_text_field'
            ));
            
            // レイアウト設定
            register_post_meta($post_type, '_easy_header_layout', array(
                'single' => true,
                'type' => 'string',
                'default' => 'center',
                'sanitize_callback' => 'sanitize_text_field'
            ));
            
            // リンクURL設定
            register_post_meta($post_type, '_easy_header_link_url', array(
                'single' => true,
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => 'esc_url_raw'
            ));
            
            // メニュー設定
            register_post_meta($post_type, '_easy_header_menu_id', array(
                'single' => true,
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field'
            ));
            
            // ヘッダーの横幅設定
            register_post_meta($post_type, '_easy_header_width', array(
                'single' => true,
                'type' => 'string',
                'default' => 'full',
                'sanitize_callback' => 'sanitize_text_field'
            ));
        }
    }
    

    
    /**
     * メタボックスを追加
     */
    public function add_meta_boxes() {
        $post_types = get_post_types(array('public' => true), 'names');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'easy_header_maker',
                '独自ヘッダー設定',
                array($this, 'meta_box_callback'),
                $post_type,
                'normal',
                'high'
            );
        }
    }
    
    /**
     * メタボックスの内容を表示
     */
    public function meta_box_callback($post) {
        // nonce フィールドを追加
        wp_nonce_field('easy_header_maker_meta_box', 'easy_header_maker_nonce');
        
        // 現在の値を取得
        $enable_custom_header = get_post_meta($post->ID, '_easy_header_enable', true);
        $header_logo = get_post_meta($post->ID, '_easy_header_logo', true);
        $header_title = get_post_meta($post->ID, '_easy_header_title', true);
        $header_subtitle = get_post_meta($post->ID, '_easy_header_subtitle', true);
        $header_bg_color = get_post_meta($post->ID, '_easy_header_bg_color', true);
        $header_text_color = get_post_meta($post->ID, '_easy_header_text_color', true);
        $header_logo_width = get_post_meta($post->ID, '_easy_header_logo_width', true);
        $header_layout = get_post_meta($post->ID, '_easy_header_layout', true);
        $header_link_url = get_post_meta($post->ID, '_easy_header_link_url', true);
        $header_menu_id = get_post_meta($post->ID, '_easy_header_menu_id', true);
        $header_width = get_post_meta($post->ID, '_easy_header_width', true);
        
        // デフォルト値設定
        if (empty($header_bg_color)) $header_bg_color = '#ffffff';
        if (empty($header_text_color)) $header_text_color = '#000000';
        if (empty($header_logo_width)) $header_logo_width = '200';
        if (empty($header_layout)) $header_layout = 'center';
        if (empty($header_width)) $header_width = 'full';
        
        echo '<div id="easy-header-maker-settings">';
        echo '<table class="form-table">';
        
        // 独自ヘッダーを有効にするチェックボックス
        echo '<tr>';
        echo '<th scope="row"><label for="easy_header_enable">独自ヘッダーを有効にする</label></th>';
        echo '<td>';
        echo '<input type="checkbox" id="easy_header_enable" name="easy_header_enable" value="1" ' . checked($enable_custom_header, 1, false) . ' />';
        echo '<label for="easy_header_enable">このページに独自ヘッダーを表示する</label>';
        echo '</td>';
        echo '</tr>';
        
        // ロゴ画像の設定
        echo '<tr class="easy-header-option" style="' . ($enable_custom_header ? '' : 'display:none;') . '">';
        echo '<th scope="row"><label for="easy_header_logo_url">ロゴ画像</label></th>';
        echo '<td>';
        echo '<input type="url" id="easy_header_logo_url" name="easy_header_logo" value="' . esc_attr($header_logo) . '" class="regular-text" placeholder="画像URLを入力" />';
        echo '<button type="button" id="easy_header_upload_btn" class="button">メディアから選択</button>';
        echo '<button type="button" id="easy_header_remove_btn" class="button">削除</button>';
        echo '<p class="description">ロゴ画像が設定されている場合、ヘッダータイトルは非表示になります</p>';
        echo '<div id="easy_header_logo_preview" style="margin-top: 10px;">';
        if ($header_logo) {
            $preview_width = $header_logo_width ? min($header_logo_width, 300) : 200;
            echo '<img src="' . esc_url($header_logo) . '" style="max-width: ' . $preview_width . 'px; height: auto; border: 1px solid #ddd; padding: 5px;" />';
        }
        echo '</div>';
        echo '</td>';
        echo '</tr>';
        
        // ロゴ画像の横幅設定
        echo '<tr class="easy-header-option" style="' . ($enable_custom_header ? '' : 'display:none;') . '">';
        echo '<th scope="row"><label for="easy_header_logo_width">ロゴ画像の横幅</label></th>';
        echo '<td>';
        echo '<input type="text" id="easy_header_logo_width" name="easy_header_logo_width" value="' . esc_attr($header_logo_width) . '" style="width: 100px;" placeholder="200" /> <span>px または auto</span>';
        echo '<p class="description">ロゴ画像の横幅を指定します（50-1200px）。「auto」と入力すると元のサイズで表示されます。</p>';
        echo '</td>';
        echo '</tr>';
        
        // ヘッダータイトル
        echo '<tr class="easy-header-option" style="' . ($enable_custom_header ? '' : 'display:none;') . '">';
        echo '<th scope="row"><label for="easy_header_title">ヘッダータイトル</label></th>';
        echo '<td>';
        echo '<input type="text" id="easy_header_title" name="easy_header_title" value="' . esc_attr($header_title) . '" class="regular-text" />';
        echo '<p class="description">ロゴ画像が設定されていない場合のみ表示されます。空白の場合は投稿タイトルが使用されます。</p>';
        echo '</td>';
        echo '</tr>';
        
        // ヘッダーサブタイトル
        echo '<tr class="easy-header-option" style="' . ($enable_custom_header ? '' : 'display:none;') . '">';
        echo '<th scope="row"><label for="easy_header_subtitle">ヘッダーサブタイトル</label></th>';
        echo '<td>';
        echo '<input type="text" id="easy_header_subtitle" name="easy_header_subtitle" value="' . esc_attr($header_subtitle) . '" class="regular-text" />';
        echo '</td>';
        echo '</tr>';
        
        // 背景色
        echo '<tr class="easy-header-option" style="' . ($enable_custom_header ? '' : 'display:none;') . '">';
        echo '<th scope="row"><label for="easy_header_bg_color">背景色</label></th>';
        echo '<td>';
        echo '<input type="color" id="easy_header_bg_color" name="easy_header_bg_color" value="' . esc_attr($header_bg_color) . '" />';
        echo '</td>';
        echo '</tr>';
        
        // 文字色
        echo '<tr class="easy-header-option" style="' . ($enable_custom_header ? '' : 'display:none;') . '">';
        echo '<th scope="row"><label for="easy_header_text_color">文字色</label></th>';
        echo '<td>';
        echo '<input type="color" id="easy_header_text_color" name="easy_header_text_color" value="' . esc_attr($header_text_color) . '" />';
        echo '</td>';
        echo '</tr>';
        
        // レイアウト設定
        echo '<tr class="easy-header-option" style="' . ($enable_custom_header ? '' : 'display:none;') . '">';
        echo '<th scope="row"><label for="easy_header_layout">レイアウト</label></th>';
        echo '<td>';
        echo '<select id="easy_header_layout" name="easy_header_layout">';
        echo '<option value="center"' . selected($header_layout, 'center', false) . '>中央寄せ</option>';
        echo '<option value="horizontal"' . selected($header_layout, 'horizontal', false) . '>横並び</option>';
        echo '</select>';
        echo '<p class="description">ヘッダーの配置を選択してください。横並びではロゴ（またはタイトル）が左側、サブタイトルが右側に表示されます。</p>';
        echo '</td>';
        echo '</tr>';
        
        // リンクURL設定
        echo '<tr class="easy-header-option" style="' . ($enable_custom_header ? '' : 'display:none;') . '">';
        echo '<th scope="row"><label for="easy_header_link_url">リンクURL</label></th>';
        echo '<td>';
        echo '<input type="url" id="easy_header_link_url" name="easy_header_link_url" value="' . esc_attr($header_link_url) . '" class="regular-text" placeholder="https://example.com" />';
        echo '<p class="description">ロゴ画像やヘッダータイトルをクリックした際のリンク先URLを設定できます。空白の場合はリンクなしです。</p>';
        echo '</td>';
        echo '</tr>';
        
        // メニュー設定
        echo '<tr class="easy-header-option" style="' . ($enable_custom_header ? '' : 'display:none;') . '">';
        echo '<th scope="row"><label for="easy_header_menu_id">ナビゲーションメニュー</label></th>';
        echo '<td>';
        echo '<select id="easy_header_menu_id" name="easy_header_menu_id">';
        echo '<option value="">メニューを選択しない</option>';
        
        // WordPressのメニューを取得
        $menus = wp_get_nav_menus();
        foreach ($menus as $menu) {
            echo '<option value="' . esc_attr($menu->term_id) . '"' . selected($header_menu_id, $menu->term_id, false) . '>' . esc_html($menu->name) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">ヘッダーに表示するナビゲーションメニューを選択できます。「外観 > メニュー」で作成したメニューが表示されます。</p>';
        echo '</td>';
        echo '</tr>';
        
        // ヘッダー横幅設定
        echo '<tr class="easy-header-option" style="' . ($enable_custom_header ? '' : 'display:none;') . '">';
        echo '<th scope="row"><label for="easy_header_width">ヘッダーの横幅</label></th>';
        echo '<td>';
        echo '<select id="easy_header_width" name="easy_header_width">';
        echo '<option value="full"' . selected($header_width, 'full', false) . '>全幅</option>';
        echo '<option value="1200"' . selected($header_width, '1200', false) . '>1200px</option>';
        echo '<option value="1000"' . selected($header_width, '1000', false) . '>1000px</option>';
        echo '<option value="800"' . selected($header_width, '800', false) . '>800px</option>';
        echo '<option value="custom"' . selected($header_width, 'custom', false) . '>カスタム</option>';
        echo '</select>';
        echo '<input type="text" id="easy_header_width_custom" name="easy_header_width_custom" value="' . (is_numeric($header_width) ? esc_attr($header_width) : '') . '" placeholder="横幅をpx単位で入力" style="margin-left: 10px; width: 150px; ' . ($header_width !== 'custom' && !is_numeric($header_width) ? 'display:none;' : '') . '" />';
        echo '<p class="description">ヘッダー内容の最大横幅を設定します。「全幅」は画面幅いっぱいに表示されます。</p>';
        echo '</td>';
        echo '</tr>';
        
        echo '</table>';
        echo '</div>';
    }
    
    /**
     * 管理画面でのスクリプトとスタイル
     */
    public function admin_scripts($hook) {
        // 投稿・ページ編集画面でのみ読み込み
        if ($hook !== 'post-new.php' && $hook !== 'post.php') {
            return;
        }
        
        // WordPress メディアライブラリを確実に読み込み
        wp_enqueue_media();
        
        // スクリプトとスタイルを直接出力
        add_action('admin_footer', array($this, 'admin_footer_scripts'));
    }
    
    /**
     * 管理画面フッターでスクリプトを出力
     */
    public function admin_footer_scripts() {
        global $post;
        
        // 投稿編集画面でない場合は何もしない
        if (!$post) {
            return;
        }
        
        ?>
        <style>
            #easy-header-maker-settings .easy-header-option {
                transition: all 0.3s ease;
            }
            
            #easy_header_maker .form-table th {
                width: 150px;
            }
            
            #easy_header_maker input[type="color"] {
                width: 50px;
                height: 30px;
                border: 1px solid #ddd;
                border-radius: 3px;
                cursor: pointer;
            }
            
            #easy_header_maker .button {
                margin-right: 8px;
            }
            
            #easy_header_logo_preview img {
                border: 1px solid #ddd;
                padding: 5px;
                border-radius: 4px;
            }
        </style>
        
        <script>
            jQuery(document).ready(function($) {
                console.log('Easy Header Maker: Initializing...');
                
                // メディアアップローダー変数
                var mediaUploader;
                
                // チェックボックスの状態に応じて表示/非表示
                function toggleHeaderOptions() {
                    var isChecked = $('#easy_header_enable').is(':checked');
                    console.log('Toggle options:', isChecked);
                    if (isChecked) {
                        $('.easy-header-option').slideDown();
                    } else {
                        $('.easy-header-option').slideUp();
                    }
                }
                
                // 初期化
                $('#easy_header_enable').on('change', toggleHeaderOptions);
                toggleHeaderOptions();
                
                // メディアアップロードボタン
                $('#easy_header_upload_btn').on('click', function(e) {
                    e.preventDefault();
                    console.log('Upload button clicked');
                    
                    // メディアアップローダーが既に作成されている場合は再利用
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    
                    // 新しいメディアアップローダーを作成
                    mediaUploader = wp.media.frames.file_frame = wp.media({
                        title: 'ロゴ画像を選択',
                        button: {
                            text: '選択'
                        },
                        multiple: false,
                        library: {
                            type: ['image']
                        }
                    });
                    
                    // 画像が選択された時の処理
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        console.log('Image selected:', attachment);
                        
                        // URLをテキストフィールドに設定
                        $('#easy_header_logo_url').val(attachment.url);
                        
                        // プレビューを更新
                        $('#easy_header_logo_preview').html(
                            '<img src="' + attachment.url + '" style="max-width: 200px; height: auto; border: 1px solid #ddd; padding: 5px;" />'
                        );
                    });
                    
                    // アップローダーを開く
                    mediaUploader.open();
                });
                
                // 削除ボタン
                $('#easy_header_remove_btn').on('click', function(e) {
                    e.preventDefault();
                    console.log('Remove button clicked');
                    
                    $('#easy_header_logo_url').val('');
                    $('#easy_header_logo_preview').html('');
                });
                
                // URLフィールドの変更でプレビューを更新
                $('#easy_header_logo_url').on('change paste keyup', function() {
                    var imageUrl = $(this).val();
                    if (imageUrl && imageUrl.match(/\.(jpeg|jpg|gif|png|webp)$/i)) {
                        $('#easy_header_logo_preview').html(
                            '<img src="' + imageUrl + '" style="max-width: 200px; height: auto; border: 1px solid #ddd; padding: 5px;" />'
                        );
                    } else if (!imageUrl) {
                        $('#easy_header_logo_preview').html('');
                    }
                });
                
                // デバッグ情報
                console.log('Easy Header Maker: wp.media available:', typeof wp.media !== 'undefined');
                console.log('Easy Header Maker: Elements found:', {
                    enable: $('#easy_header_enable').length,
                    upload: $('#easy_header_upload_btn').length,
                    remove: $('#easy_header_remove_btn').length,
                    url: $('#easy_header_logo_url').length,
                    preview: $('#easy_header_logo_preview').length
                });
                
                // 横幅設定の切り替え機能
                function toggleWidthCustomField() {
                    var selectedWidth = $('#easy_header_width').val();
                    if (selectedWidth === 'custom') {
                        $('#easy_header_width_custom').show();
                    } else {
                        $('#easy_header_width_custom').hide();
                    }
                }
                
                $('#easy_header_width').on('change', toggleWidthCustomField);
                toggleWidthCustomField();
            });
        </script>
        <?php
    }    /**
     * メタデータを保存
     */
    public function save_post_meta($post_id) {
        // nonce チェック
        if (!isset($_POST['easy_header_maker_nonce']) || !wp_verify_nonce($_POST['easy_header_maker_nonce'], 'easy_header_maker_meta_box')) {
            return;
        }
        
        // 自動保存をスキップ
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // 権限チェック
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // メタデータを保存
        $fields = array(
            'easy_header_enable' => '_easy_header_enable',
            'easy_header_logo' => '_easy_header_logo',
            'easy_header_title' => '_easy_header_title',
            'easy_header_subtitle' => '_easy_header_subtitle',
            'easy_header_bg_color' => '_easy_header_bg_color',
            'easy_header_text_color' => '_easy_header_text_color',
            'easy_header_logo_width' => '_easy_header_logo_width',
            'easy_header_layout' => '_easy_header_layout',
            'easy_header_link_url' => '_easy_header_link_url',
            'easy_header_menu_id' => '_easy_header_menu_id',
            'easy_header_width' => '_easy_header_width'
        );
        
        foreach ($fields as $input_name => $meta_key) {
            if ($input_name === 'easy_header_enable') {
                // チェックボックスの場合
                $value = isset($_POST[$input_name]) ? 1 : 0;
                update_post_meta($post_id, $meta_key, $value);
            } else {
                // その他のフィールド
                if (isset($_POST[$input_name])) {
                    $value = sanitize_text_field($_POST[$input_name]);
                    if ($input_name === 'easy_header_logo') {
                        $value = esc_url_raw($value);
                    } elseif (strpos($input_name, 'color') !== false) {
                        $value = sanitize_hex_color($value);
                    }
                    update_post_meta($post_id, $meta_key, $value);
                } else {
                    delete_post_meta($post_id, $meta_key);
                }
            }
        }
        
        // カスタム横幅の処理
        if (isset($_POST['easy_header_width']) && $_POST['easy_header_width'] === 'custom' && isset($_POST['easy_header_width_custom'])) {
            $custom_width = sanitize_text_field($_POST['easy_header_width_custom']);
            if (is_numeric($custom_width) && $custom_width > 0) {
                update_post_meta($post_id, '_easy_header_width', $custom_width);
            }
        }
    }
    
    /**
     * フロントエンドで独自ヘッダーを表示
     */
    public function display_custom_header() {
        global $post;
        
        $post_id = null;
        $enable_custom_header = false;
        $header_logo = '';
        $header_title = '';
        $header_subtitle = '';
        $header_bg_color = '#ffffff';
        $header_text_color = '#000000';
        
        // デバッグ情報（開発時のみ使用）
        $debug_info = array();
        
        // 投稿・ページの場合
        if (is_singular() && $post) {
            $post_id = $post->ID;
            $enable_custom_header = get_post_meta($post_id, '_easy_header_enable', true);
            $debug_info[] = "Singular page detected. Post ID: {$post_id}, Enable header: " . ($enable_custom_header ? 'true' : 'false');
        }
        // フロントページの場合
        elseif (is_front_page()) {
            $debug_info[] = "Front page detected";
            
            // まず管理画面の設定をチェック
            $front_enable = get_option('easy_header_front_enable', 0);
            $debug_info[] = "Front page enable option: " . ($front_enable ? 'true' : 'false');
            
            if ($front_enable) {
                $enable_custom_header = true;
                // フロントページ用の設定を直接取得
                $header_logo = get_option('easy_header_front_logo', '');
                $header_title = get_option('easy_header_front_title', '');
                $header_subtitle = get_option('easy_header_front_subtitle', '');
                $header_bg_color = get_option('easy_header_front_bg_color', '#ffffff');
                $header_text_color = get_option('easy_header_front_text_color', '#000000');
                
                $debug_info[] = "Using front page settings from admin";
                $debug_info[] = "Logo: {$header_logo}";
                $debug_info[] = "Title: {$header_title}";
                $debug_info[] = "BG Color: {$header_bg_color}";
            }
            // 管理画面設定が無効の場合、静的フロントページの設定をチェック
            elseif (get_option('show_on_front') == 'page') {
                $post_id = get_option('page_on_front');
                if ($post_id) {
                    $enable_custom_header = get_post_meta($post_id, '_easy_header_enable', true);
                    $debug_info[] = "Checking static front page. Page ID: {$post_id}, Enable: " . ($enable_custom_header ? 'true' : 'false');
                }
            }
        }
        
        $debug_info[] = "Final enable_custom_header: " . ($enable_custom_header ? 'true' : 'false');
        
        // デバッグ情報を出力（管理者のみ）
        if (current_user_can('manage_options') && isset($_GET['debug_header'])) {
            echo '<!-- Easy Header Maker Debug Info: ' . implode(' | ', $debug_info) . ' -->';
        }
        
        if (!$enable_custom_header) {
            return;
        }
        
        // フロントページで管理画面設定を使用する場合は、既に取得済み
        if (!is_front_page() || !get_option('easy_header_front_enable', 0)) {
            if (!$post_id) {
                return;
            }
            
            $header_logo = get_post_meta($post_id, '_easy_header_logo', true);
            $header_title = get_post_meta($post_id, '_easy_header_title', true);
            $header_subtitle = get_post_meta($post_id, '_easy_header_subtitle', true);
            $header_bg_color = get_post_meta($post_id, '_easy_header_bg_color', true);
            $header_text_color = get_post_meta($post_id, '_easy_header_text_color', true);
            $header_logo_width = get_post_meta($post_id, '_easy_header_logo_width', true);
            $header_layout = get_post_meta($post_id, '_easy_header_layout', true);
            $header_link_url = get_post_meta($post_id, '_easy_header_link_url', true);
            $header_menu_id = get_post_meta($post_id, '_easy_header_menu_id', true);
            $header_width = get_post_meta($post_id, '_easy_header_width', true);
        } else {
            // フロントページ用の設定を取得
            $header_logo_width = get_option('easy_header_front_logo_width', '200');
            $header_layout = get_option('easy_header_front_layout', 'center');
            $header_link_url = get_option('easy_header_front_link_url', '');
            $header_menu_id = get_option('easy_header_front_menu_id', '');
            $header_width = get_option('easy_header_front_width', 'full');
        }
        
        // デフォルト値の設定
        if (empty($header_title)) {
            if (is_front_page()) {
                $header_title = get_bloginfo('name'); // サイト名を使用
            } else {
                $header_title = get_the_title($post_id);
            }
        }
        if (empty($header_bg_color)) {
            $header_bg_color = '#ffffff';
        }
        if (empty($header_text_color)) {
            $header_text_color = '#000000';
        }
        if (empty($header_layout)) {
            $header_layout = 'center';
        }
        if (empty($header_width)) {
            $header_width = 'full';
        }
        
        // 横幅設定
        $max_width_style = '';
        if ($header_width !== 'full') {
            if (is_numeric($header_width)) {
                $max_width_style = 'max-width: ' . intval($header_width) . 'px; margin-left: auto; margin-right: auto;';
            } else {
                $max_width_style = 'max-width: ' . esc_attr($header_width) . 'px; margin-left: auto; margin-right: auto;';
            }
        }
        
        echo '<style>
            .easy-custom-header {
                background-color: ' . esc_attr($header_bg_color) . ';
                color: ' . esc_attr($header_text_color) . ';
                padding: 26px 30px;
                text-align: ' . ($header_layout === 'horizontal' ? 'left' : 'center') . ';
                position: relative;
                z-index: 999;
            }
            .easy-custom-header .header-inner {
                ' . $max_width_style . '
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
                text-align: right;
            }
            .easy-custom-header .header-logo {
                height: auto;
                margin-bottom: 20px;
            }
            .easy-custom-header.layout-horizontal .header-logo {
                margin-bottom: 0;
                margin-right: 0;
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
                color: inherit;
                text-decoration: none;
            }
            .easy-custom-header a:hover {
                opacity: 0.8;
            }
            .easy-custom-header .header-navigation {
                margin-top: 20px;
            }
            .easy-custom-header.layout-horizontal .header-navigation {
                margin-top: 0;
                margin-left: auto;
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
            .easy-custom-header.layout-horizontal .header-navigation ul {
                justify-content: flex-end;
            }
            .easy-custom-header .header-navigation li {
                margin: 0;
                position: relative;
            }
            .easy-custom-header .header-navigation a {
                display: block;
                padding: 8px 12px;
                border-radius: 4px;
                transition: background-color 0.3s ease;
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
            .easy-custom-header .header-navigation .sub-menu .menu-item-has-children > a:after {
                content: "▶";
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 10px;
                opacity: 0.7;
            }
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
                font-size: 1.2em;
                margin: 0;
                opacity: 0.8;
            }
            .easy-custom-header.layout-horizontal .header-subtitle {
                margin: 0;
                white-space: nowrap;
                text-align: left;
                margin-left: 5px;
                font-size: 14px;
            }
            @media (max-width: 768px) {
                .easy-custom-header .header-title {
                    font-size: 2em;
                }
                .easy-custom-header .header-subtitle {
                    font-size: 1em;
                }
                .easy-custom-header.layout-horizontal .header-inner {
                    flex-direction: column;
                    text-align: center;
                }
                .easy-custom-header.layout-horizontal .header-left {
                    flex-direction: column;
                    text-align: center;
                    margin-top: 0;
                }
                .easy-custom-header.layout-horizontal .header-right {
                    text-align: center;
                    margin-top: 15px;
                }
                .easy-custom-header.layout-horizontal .header-subtitle {
                    white-space: normal;
                    margin-top: 5px;
                }
                .easy-custom-header.layout-horizontal .header-navigation {
                    margin-left: 0;
                    margin-top: 15px;
                }
                .easy-custom-header .header-navigation ul {
                    flex-direction: column;
                    align-items: center;
                    gap: 10px;
                }
                .easy-custom-header .header-navigation .sub-menu {
                    position: static;
                    min-width: auto;
                    width: 100%;
                    background: rgba(255, 255, 255, 0.1);
                    box-shadow: none;
                    margin-top: 10px;
                    transform: none;
                    opacity: 1;
                    visibility: visible;
                }
                .easy-custom-header .header-navigation .sub-menu a {
                    padding: 8px 16px;
                }
                /* モバイルでの孫メニュー以降も縦に表示 */
                .easy-custom-header .header-navigation .sub-menu .sub-menu {
                    position: static;
                    left: auto;
                    top: auto;
                    margin-left: 20px;
                    background: rgba(255, 255, 255, 0.05);
                }
                .easy-custom-header .header-navigation .sub-menu .menu-item-has-children > a:after {
                    content: "▼";
                    transform: translateY(-50%) rotate(0);
                }
            }
        </style>';
        
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                // jQueryが読み込まれているかチェック
                if (typeof jQuery !== "undefined") {
                    executeHeaderScript();
                } else {
                    // jQueryが無い場合は純粋なJavaScriptで実行
                    executeHeaderScriptVanilla();
                }
                
                function executeHeaderScript() {
                    jQuery(function($) {
                        var customHeader = \'<div class="easy-custom-header layout-' . esc_js($header_layout) . '"><div class="header-inner">\';
                        ';
        
        // 横並びレイアウトの場合はleft divを開始
        if ($header_layout === 'horizontal') {
            echo 'customHeader += \'<div class="header-left">\';';
        }
        
        // リンクの開始タグを準備
        $link_start = '';
        $link_end = '';
        if (!empty($header_link_url)) {
            $link_start = '<a href="' . esc_url($header_link_url) . '">';
            $link_end = '</a>';
        }
        
        if ($header_logo) {
            // 横幅のスタイルを設定
            $width_style = '';
            if (!empty($header_logo_width)) {
                if ($header_logo_width === 'auto') {
                    $width_style = 'width: auto; ';
                } else {
                    $width_style = 'width: ' . intval($header_logo_width) . 'px; ';
                }
            } else {
                // デフォルトは200px
                $width_style = 'width: 200px; ';
            }
            echo 'customHeader += \'<div>' . $link_start . '<img src="' . esc_url($header_logo) . '" alt="Header Logo" class="header-logo" style="' . $width_style . '" />' . $link_end . '</div>\';';
            // ロゴがある場合はタイトルを非表示
        } else {
            // ロゴがない場合のみタイトルを表示
            echo 'customHeader += \'<h1 class="header-title">' . $link_start . esc_js($header_title) . $link_end . '</h1>\';';
        }
        
        // サブタイトルの処理
        if ($header_subtitle) {
            if ($header_layout === 'horizontal') {
                // 横並びレイアウトの場合はロゴの右側に配置
                echo 'customHeader += \'<p class="header-subtitle">' . esc_js($header_subtitle) . '</p>\';';
                echo 'customHeader += \'</div>\';'; // 左側divを閉じる
                echo 'customHeader += \'<div class="header-right">\';'; // 右側divを開始
            } else {
                // 中央寄せレイアウトの場合は通常通り
                echo 'customHeader += \'<p class="header-subtitle">' . esc_js($header_subtitle) . '</p>\';';
            }
        } else {
            // 横並びレイアウトでサブタイトルがない場合は左側divを閉じる
            if ($header_layout === 'horizontal') {
                echo 'customHeader += \'</div>\';';
            }
        }
        
        // メニューを表示
        if (!empty($header_menu_id)) {
            // メニューのHTMLを生成（サブメニュー対応）
            $menu_html = wp_nav_menu(array(
                'menu' => $header_menu_id,
                'container' => false,
                'items_wrap' => '<ul class="easy-header-menu">%3$s</ul>',
                'echo' => false,
                'fallback_cb' => false,
                'walker' => new Easy_Header_Walker()
            ));
            
            if ($menu_html) {
                // JavaScriptで安全に使えるようにエスケープ
                $menu_html_escaped = str_replace(array("\r", "\n", "\t"), '', $menu_html);
                $menu_html_escaped = addslashes($menu_html_escaped);
                
                echo 'customHeader += \'<nav class="header-navigation">' . $menu_html_escaped . '</nav>\';';
            }
        }
        
        // 横並びレイアウトで右側divが開いている場合は閉じる
        if ($header_layout === 'horizontal' && $header_subtitle) {
            echo 'customHeader += \'</div>\';';
        }
        
        echo 'customHeader += \'</div></div>\';
                        
                        // ヘッダーをページの最上部に追加
                        if ($("body").length > 0) {
                            $("body").prepend(customHeader);
                        }
                    });
                }
                
                function executeHeaderScriptVanilla() {
                    var customHeader = document.createElement("div");
                    customHeader.className = "easy-custom-header";
                    customHeader.innerHTML = \'';
        
        if ($header_logo) {
            // 横幅のスタイルを設定
            $width_style = '';
            if (!empty($header_logo_width) && $header_logo_width !== 'auto') {
                $width_style = 'width: ' . intval($header_logo_width) . 'px; ';
            }
            echo '<div><img src="' . esc_url($header_logo) . '" alt="Header Logo" class="header-logo" style="' . $width_style . '" /></div>';
            // ロゴがある場合はタイトルを非表示
        } else {
            // ロゴがない場合のみタイトルを表示
            echo '<h1 class="header-title">' . esc_js($header_title) . '</h1>';
        }
        
        if ($header_subtitle) {
            echo '<p class="header-subtitle">' . esc_js($header_subtitle) . '</p>';
        }
        
        echo '\';
                    
                    // ヘッダーをページの最上部に追加
                    var body = document.body;
                    if (body) {
                        body.insertBefore(customHeader, body.firstChild);
                        
                        // ドロップダウンメニューの機能を初期化
                        initDropdownMenu(customHeader);
                    }
                }
            });
            
            // ドロップダウンメニューの初期化
            function initDropdownMenu(header) {
                var menuItems = header.querySelectorAll(".header-navigation li");
                
                menuItems.forEach(function(item) {
                    var subMenu = item.querySelector(".sub-menu");
                    if (subMenu) {
                        // タッチデバイス対応：クリックでサブメニューの表示/非表示を切り替え
                        var parentLink = item.querySelector("a");
                        if (parentLink) {
                            parentLink.addEventListener("click", function(e) {
                                // モバイルデバイスの場合
                                if (window.innerWidth <= 768) {
                                    e.preventDefault();
                                    var isVisible = subMenu.style.display === "block";
                                    
                                    // 同じレベルのサブメニューを閉じる
                                    var siblings = item.parentNode.children;
                                    for (var i = 0; i < siblings.length; i++) {
                                        if (siblings[i] !== item) {
                                            var siblingSubMenu = siblings[i].querySelector(".sub-menu");
                                            if (siblingSubMenu) {
                                                siblingSubMenu.style.display = "none";
                                            }
                                        }
                                    }
                                    
                                    // 現在のサブメニューの表示を切り替え
                                    subMenu.style.display = isVisible ? "none" : "block";
                                }
                            });
                        }
                        
                        // マウスホバーイベント（デスクトップ）
                        item.addEventListener("mouseenter", function() {
                            if (window.innerWidth > 768) {
                                // 孫メニュー以降の位置調整
                                adjustSubmenuPosition(subMenu);
                                
                                // 1レベル目のサブメニュー
                                if (item.closest(".sub-menu") === null) {
                                    subMenu.style.opacity = "1";
                                    subMenu.style.visibility = "visible";
                                    subMenu.style.transform = "translateY(0)";
                                } else {
                                    // 2レベル目以降のサブメニュー
                                    subMenu.style.opacity = "1";
                                    subMenu.style.visibility = "visible";
                                    subMenu.style.transform = "translateX(0)";
                                }
                            }
                        });
                        
                        item.addEventListener("mouseleave", function() {
                            if (window.innerWidth > 768) {
                                // 1レベル目のサブメニュー
                                if (item.closest(".sub-menu") === null) {
                                    subMenu.style.opacity = "0";
                                    subMenu.style.visibility = "hidden";
                                    subMenu.style.transform = "translateY(-10px)";
                                } else {
                                    // 2レベル目以降のサブメニュー
                                    subMenu.style.opacity = "0";
                                    subMenu.style.visibility = "hidden";
                                    if (subMenu.classList.contains("show-left")) {
                                        subMenu.style.transform = "translateX(10px)";
                                    } else {
                                        subMenu.style.transform = "translateX(-10px)";
                                    }
                                }
                            }
                        });
                    }
                });
                
                // サブメニューの位置調整関数
                function adjustSubmenuPosition(subMenu) {
                    // 孫メニュー以降の場合のみ実行
                    if (subMenu.closest(".sub-menu")) {
                        var rect = subMenu.getBoundingClientRect();
                        var windowWidth = window.innerWidth;
                        
                        // 右端にはみ出る場合は左に表示
                        if (rect.right > windowWidth - 20) {
                            subMenu.classList.add("show-left");
                        } else {
                            subMenu.classList.remove("show-left");
                        }
                    }
                }
                
                // 外側クリックでサブメニューを閉じる
                document.addEventListener("click", function(e) {
                    if (!header.contains(e.target)) {
                        header.querySelectorAll(".sub-menu").forEach(function(menu) {
                            menu.style.display = "none";
                        });
                    }
                });
            }
        </script>';
    }
    
    /**
     * フォールバック：wp_footerでヘッダーを表示（wp_headで表示されなかった場合）
     */
    public function display_custom_header_fallback() {
        static $header_displayed = false;
        
        if ($header_displayed) {
            return;
        }
        
        // ヘッダーが表示されていない場合のデバッグ情報
        if (current_user_can('manage_options') && isset($_GET['debug_header'])) {
            echo '<!-- Easy Header Maker: Fallback triggered -->';
        }
        
        $header_displayed = true;
    }
    
    /**
     * テスト用ショートコード
     */
    public function header_test_shortcode($atts) {
        $front_enable = get_option('easy_header_front_enable', 0);
        $front_title = get_option('easy_header_front_title', '');
        $front_logo = get_option('easy_header_front_logo', '');
        
        $output = '<div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin: 10px 0;">';
        $output .= '<h4>Easy Header Maker - 設定確認</h4>';
        $output .= '<p><strong>フロントページヘッダー有効:</strong> ' . ($front_enable ? 'はい' : 'いいえ') . '</p>';
        $output .= '<p><strong>タイトル設定:</strong> ' . ($front_title ? esc_html($front_title) : 'サイト名を使用') . '</p>';
        $output .= '<p><strong>ロゴ設定:</strong> ' . ($front_logo ? 'あり' : 'なし') . '</p>';
        $output .= '<p><strong>現在のページ:</strong> ' . (is_front_page() ? 'フロントページ' : 'その他のページ') . '</p>';
        
        if (is_front_page() && $front_enable) {
            $output .= '<p style="color: green;"><strong>✓ フロントページでヘッダーが表示される設定です</strong></p>';
        } else {
            $output .= '<p style="color: red;"><strong>✗ 現在の設定ではヘッダーは表示されません</strong></p>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 管理画面メニューを追加
     */
    public function add_admin_menu() {
        add_options_page(
            'Easy Header Maker設定',
            'Easy Header Maker',
            'manage_options',
            'easy-header-maker',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 設定ページの表示
     */
    public function admin_page() {
        // メディアライブラリを有効化
        wp_enqueue_media();
        
        // 設定保存処理
        if (isset($_POST['submit']) && isset($_POST['easy_header_maker_nonce'])) {
            if (wp_verify_nonce($_POST['easy_header_maker_nonce'], 'easy_header_maker_settings')) {
                // 権限チェック
                if (current_user_can('manage_options')) {
                    update_option('easy_header_front_enable', isset($_POST['front_enable']) ? 1 : 0);
                    update_option('easy_header_front_logo', esc_url_raw($_POST['front_logo']));
                    update_option('easy_header_front_title', sanitize_text_field($_POST['front_title']));
                    update_option('easy_header_front_subtitle', sanitize_text_field($_POST['front_subtitle']));
                    update_option('easy_header_front_bg_color', sanitize_hex_color($_POST['front_bg_color']));
                    update_option('easy_header_front_text_color', sanitize_hex_color($_POST['front_text_color']));
                    update_option('easy_header_front_logo_width', sanitize_text_field($_POST['front_logo_width']));
                    update_option('easy_header_front_layout', sanitize_text_field($_POST['front_layout']));
                    update_option('easy_header_front_link_url', esc_url_raw($_POST['front_link_url']));
                    update_option('easy_header_front_menu_id', sanitize_text_field($_POST['front_menu_id']));
                    
                    // 横幅設定の処理
                    if (isset($_POST['front_width']) && $_POST['front_width'] === 'custom' && isset($_POST['front_width_custom'])) {
                        $custom_width = sanitize_text_field($_POST['front_width_custom']);
                        if (is_numeric($custom_width) && $custom_width > 0) {
                            update_option('easy_header_front_width', $custom_width);
                        }
                    } else {
                        update_option('easy_header_front_width', sanitize_text_field($_POST['front_width']));
                    }
                    
                    echo '<div class="notice notice-success is-dismissible"><p>設定を保存しました。</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>設定を保存する権限がありません。</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>セキュリティチェックに失敗しました。</p></div>';
            }
        }
        
        $front_enable = get_option('easy_header_front_enable', 0);
        $front_logo = get_option('easy_header_front_logo', '');
        $front_title = get_option('easy_header_front_title', '');
        $front_subtitle = get_option('easy_header_front_subtitle', '');
        $front_bg_color = get_option('easy_header_front_bg_color', '#ffffff');
        $front_text_color = get_option('easy_header_front_text_color', '#000000');
        $front_logo_width = get_option('easy_header_front_logo_width', '200');
        $front_layout = get_option('easy_header_front_layout', 'center');
        $front_link_url = get_option('easy_header_front_link_url', '');
        $front_menu_id = get_option('easy_header_front_menu_id', '');
        $front_width = get_option('easy_header_front_width', 'full');
        
        ?>
        <div class="wrap">
            <h1>Easy Header Maker設定</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('easy_header_maker_settings', 'easy_header_maker_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">フロントページでヘッダーを表示</th>
                        <td>
                            <label>
                                <input type="checkbox" name="front_enable" value="1" <?php checked($front_enable, 1); ?> />
                                フロントページに独自ヘッダーを表示する
                            </label>
                            <p class="description">チェックを入れると、サイトのトップページに独自ヘッダーが表示されます。</p>
                        </td>
                    </tr>
                    
                    <tr class="front-option" style="<?php echo $front_enable ? '' : 'display:none;'; ?>">
                        <th scope="row">ロゴ画像</th>
                        <td>
                            <input type="url" id="front_logo" name="front_logo" value="<?php echo esc_attr($front_logo); ?>" class="regular-text" placeholder="画像URLを入力またはボタンで選択" />
                            <button type="button" id="upload_front_logo_button" class="button">画像を選択</button>
                            <button type="button" id="remove_front_logo_button" class="button">削除</button>
                            <p class="description">ロゴ画像が設定されている場合、ヘッダータイトルは非表示になります</p>
                            <div id="front_logo_preview" style="margin-top: 10px;">
                                <?php if ($front_logo): ?>
                                    <?php $preview_width = $front_logo_width ? min($front_logo_width, 300) : 200; ?>
                                    <img src="<?php echo esc_url($front_logo); ?>" style="max-width: <?php echo $preview_width; ?>px; height: auto; border: 1px solid #ddd; padding: 5px;" />
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    
                    <tr class="front-option" style="<?php echo $front_enable ? '' : 'display:none;'; ?>">
                        <th scope="row">ロゴ画像の横幅</th>
                        <td>
                            <input type="text" name="front_logo_width" value="<?php echo esc_attr($front_logo_width); ?>" style="width: 100px;" placeholder="200" />
                            <span style="margin-left: 5px;">px または auto</span>
                            <p class="description">ロゴ画像の横幅を指定します（50-1200px）。「auto」と入力すると元のサイズで表示されます。</p>
                        </td>
                    </tr>
                    
                    <tr class="front-option" style="<?php echo $front_enable ? '' : 'display:none;'; ?>">
                        <th scope="row">ヘッダータイトル</th>
                        <td>
                            <input type="text" name="front_title" value="<?php echo esc_attr($front_title); ?>" class="regular-text" placeholder="カスタムタイトルを入力" />
                            <p class="description">ロゴ画像が設定されていない場合のみ表示されます。空白の場合はサイト名「<?php echo esc_html(get_bloginfo('name')); ?>」が使用されます</p>
                        </td>
                    </tr>
                    
                    <tr class="front-option" style="<?php echo $front_enable ? '' : 'display:none;'; ?>">
                        <th scope="row">ヘッダーサブタイトル</th>
                        <td>
                            <input type="text" name="front_subtitle" value="<?php echo esc_attr($front_subtitle); ?>" class="regular-text" placeholder="サブタイトルを入力（オプション）" />
                        </td>
                    </tr>
                    
                    <tr class="front-option" style="<?php echo $front_enable ? '' : 'display:none;'; ?>">
                        <th scope="row">背景色</th>
                        <td>
                            <input type="color" name="front_bg_color" value="<?php echo esc_attr($front_bg_color); ?>" />
                            <span style="margin-left: 10px;"><?php echo esc_html($front_bg_color); ?></span>
                        </td>
                    </tr>
                    
                    <tr class="front-option" style="<?php echo $front_enable ? '' : 'display:none;'; ?>">
                        <th scope="row">文字色</th>
                        <td>
                            <input type="color" name="front_text_color" value="<?php echo esc_attr($front_text_color); ?>" />
                            <span style="margin-left: 10px;"><?php echo esc_html($front_text_color); ?></span>
                        </td>
                    </tr>
                    
                    <tr class="front-option" style="<?php echo $front_enable ? '' : 'display:none;'; ?>">
                        <th scope="row">レイアウト</th>
                        <td>
                            <select name="front_layout">
                                <option value="center"<?php selected($front_layout, 'center'); ?>>中央寄せ</option>
                                <option value="horizontal"<?php selected($front_layout, 'horizontal'); ?>>横並び</option>
                            </select>
                            <p class="description">ヘッダーの配置を選択してください。横並びではロゴ（またはタイトル）が左側、サブタイトルが右側に表示されます。</p>
                        </td>
                    </tr>
                    
                    <tr class="front-option" style="<?php echo $front_enable ? '' : 'display:none;'; ?>">
                        <th scope="row">リンクURL</th>
                        <td>
                            <input type="url" name="front_link_url" value="<?php echo esc_attr($front_link_url); ?>" class="regular-text" placeholder="https://example.com" />
                            <p class="description">ロゴ画像やヘッダータイトルをクリックした際のリンク先URLを設定できます。空白の場合はリンクなしです。</p>
                        </td>
                    </tr>
                    
                    <tr class="front-option" style="<?php echo $front_enable ? '' : 'display:none;'; ?>">
                        <th scope="row">ナビゲーションメニュー</th>
                        <td>
                            <select name="front_menu_id">
                                <option value="">メニューを選択しない</option>
                                <?php
                                $menus = wp_get_nav_menus();
                                foreach ($menus as $menu) {
                                    echo '<option value="' . esc_attr($menu->term_id) . '"' . selected($front_menu_id, $menu->term_id, false) . '>' . esc_html($menu->name) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">ヘッダーに表示するナビゲーションメニューを選択できます。「外観 > メニュー」で作成したメニューが表示されます。</p>
                        </td>
                    </tr>
                    
                    <tr class="front-option" style="<?php echo $front_enable ? '' : 'display:none;'; ?>">
                        <th scope="row">ヘッダーの横幅</th>
                        <td>
                            <select name="front_width" id="front_width">
                                <option value="full"<?php selected($front_width, 'full'); ?>>全幅</option>
                                <option value="1200"<?php selected($front_width, '1200'); ?>>1200px</option>
                                <option value="1000"<?php selected($front_width, '1000'); ?>>1000px</option>
                                <option value="800"<?php selected($front_width, '800'); ?>>800px</option>
                                <option value="custom"<?php selected($front_width, 'custom'); ?>>カスタム</option>
                            </select>
                            <input type="text" name="front_width_custom" id="front_width_custom" value="<?php echo (is_numeric($front_width) ? esc_attr($front_width) : ''); ?>" placeholder="横幅をpx単位で入力" style="margin-left: 10px; width: 150px; <?php echo ($front_width !== 'custom' && !is_numeric($front_width) ? 'display:none;' : ''); ?>" />
                            <p class="description">ヘッダー内容の最大横幅を設定します。「全幅」は画面幅いっぱいに表示されます。</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('設定を保存'); ?>
            </form>
            
            <div style="margin-top: 30px; padding: 15px; background: #f1f1f1; border-left: 4px solid #0073aa;">
                <h3>使用方法</h3>
                <ul>
                    <li>フロントページでヘッダーを表示するには、上記のチェックボックスをオンにしてください。</li>
                    <li>個別の投稿や固定ページでは、編集画面の「独自ヘッダー設定」メタボックスで個別に設定できます。</li>
                </ul>
            </div>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                // プレビュー画像を更新する関数
                window.updateLogoPreview = function() {
                    var logoUrl = $('#front_logo').val();
                    var logoWidth = $('input[name="front_logo_width"]').val() || '200';
                    var previewWidth;
                    
                    if (logoWidth === 'auto') {
                        previewWidth = 300; // autoの場合は最大300pxでプレビュー
                    } else {
                        previewWidth = Math.min(parseInt(logoWidth) || 200, 300); // プレビューは最大300pxに制限
                    }
                    
                    if (logoUrl) {
                        $('#front_logo_preview').html('<img src="' + logoUrl + '" style="max-width: ' + previewWidth + 'px; height: auto; border: 1px solid #ddd; padding: 5px;" />');
                    } else {
                        $('#front_logo_preview').html('');
                    }
                };
                
                // 横幅の変更時にプレビューを更新
                $('input[name="front_logo_width"]').on('input', function() {
                    window.updateLogoPreview();
                });
                
                // チェックボックスの状態に応じて表示/非表示
                $('input[name="front_enable"]').change(function() {
                    if ($(this).is(':checked')) {
                        $('.front-option').slideDown();
                    } else {
                        $('.front-option').slideUp();
                    }
                });
                
                // ロゴ画像のアップロード
                $('#upload_front_logo_button').click(function(e) {
                    e.preventDefault();
                    
                    var frame = wp.media({
                        title: 'ロゴ画像を選択',
                        button: {
                            text: '選択'
                        },
                        multiple: false,
                        library: {
                            type: 'image'
                        }
                    });
                    
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('#front_logo').val(attachment.url);
                        window.updateLogoPreview();
                    });
                    
                    frame.open();
                });
                
                // ロゴ画像の削除
                $('#remove_front_logo_button').click(function(e) {
                    e.preventDefault();
                    $('#front_logo').val('');
                    $('#front_logo_preview').html('');
                });
                
                // 色の変更時にカラーコードを表示
                $('input[type="color"]').change(function() {
                    $(this).next('span').text($(this).val());
                });
                
                // フロントページ横幅設定の切り替え機能
                function toggleFrontWidthCustomField() {
                    var selectedWidth = $('#front_width').val();
                    if (selectedWidth === 'custom') {
                        $('#front_width_custom').show();
                    } else {
                        $('#front_width_custom').hide();
                    }
                }
                
                $('#front_width').on('change', toggleFrontWidthCustomField);
                toggleFrontWidthCustomField();
            });
        </script>
        
        <style>
            .front-option {
                transition: all 0.3s ease;
            }
            .form-table th {
                width: 200px;
            }
            .notice {
                margin: 5px 0 15px;
            }
            input[type="color"] {
                width: 50px;
                height: 30px;
                border: 1px solid #ddd;
                border-radius: 3px;
                cursor: pointer;
            }
        </style>
        <?php
    }
}

// プラグインを初期化
new EasyHeaderMaker();

