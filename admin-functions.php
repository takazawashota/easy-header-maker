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
        $header_layout = get_post_meta($post->ID, '_easy_header_layout', true) ?: 'center';
        $header_link_url = get_post_meta($post->ID, '_easy_header_link_url', true);
        $header_menu_id = get_post_meta($post->ID, '_easy_header_menu_id', true);
        $header_width = get_post_meta($post->ID, '_easy_header_width', true) ?: 'full';
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
            
            // ヘッダー幅の処理
            if ($_POST['easy_header_front_width'] === 'custom' && isset($_POST['easy_header_front_width_custom'])) {
                update_option('easy_header_front_width', intval($_POST['easy_header_front_width_custom']));
            } else {
                update_option('easy_header_front_width', sanitize_text_field($_POST['easy_header_front_width']));
            }
            
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
        $front_layout = get_option('easy_header_front_layout', 'center');
        $front_link_url = get_option('easy_header_front_link_url', '');
        $front_menu_id = get_option('easy_header_front_menu_id', '');
        $front_width = get_option('easy_header_front_width', 'full');
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