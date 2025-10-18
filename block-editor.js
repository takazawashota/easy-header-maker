(function (wp) {
    const { registerPlugin } = wp.plugins;
    const { PluginDocumentSettingPanel } = wp.editPost;
    const {
        CheckboxControl,
        TextControl,
        Button,
        BaseControl,
        ColorPicker,
        Popover
    } = wp.components;
    const { withSelect, withDispatch } = wp.data;
    const { compose } = wp.compose;
    const { Fragment, useState } = wp.element;
    const { __ } = wp.i18n;
    const { MediaUpload, MediaUploadCheck } = wp.blockEditor;

    const EasyHeaderMakerPanel = compose([
        withSelect((select) => {
            const { getEditedPostAttribute } = select('core/editor');
            const meta = getEditedPostAttribute('meta') || {};
            return {
                enableHeader: Boolean(meta._easy_header_enable),
                headerLogo: meta._easy_header_logo || '',
                headerTitle: meta._easy_header_title || '',
                headerSubtitle: meta._easy_header_subtitle || '',
                headerBgColor: meta._easy_header_bg_color || '#ffffff',
                headerTextColor: meta._easy_header_text_color || '#000000',
            };
        }),
        withDispatch((dispatch) => ({
            setMetaValue: (key, value) => {
                dispatch('core/editor').editPost({
                    meta: { [key]: value }
                });
            }
        }))
    ])((props) => {
        const {
            enableHeader,
            headerLogo,
            headerTitle,
            headerSubtitle,
            headerBgColor,
            headerTextColor,
            setMetaValue
        } = props;

        const [showBgColorPicker, setShowBgColorPicker] = useState(false);
        const [showTextColorPicker, setShowTextColorPicker] = useState(false);

        return wp.element.createElement(
            PluginDocumentSettingPanel,
            {
                name: "easy-header-maker",
                title: "独自ヘッダー設定",
                className: "easy-header-maker-panel"
            },

            // チェックボックス
            wp.element.createElement(CheckboxControl, {
                label: "独自ヘッダーを有効にする",
                checked: enableHeader,
                onChange: (value) => setMetaValue('_easy_header_enable', value)
            }),

            // 設定項目（チェックされた時のみ表示）
            enableHeader && wp.element.createElement(
                Fragment,
                null,

                // ロゴ画像
                wp.element.createElement(
                    BaseControl,
                    {
                        label: "ロゴ画像",
                        className: "easy-header-logo-control"
                    },
                    wp.element.createElement(
                        MediaUploadCheck,
                        null,
                        wp.element.createElement(MediaUpload, {
                            onSelect: (media) => setMetaValue('_easy_header_logo', media.url),
                            allowedTypes: ['image'],
                            value: headerLogo,
                            render: ({ open }) => wp.element.createElement(
                                'div',
                                { style: { marginBottom: '10px' } },
                                wp.element.createElement(Button, {
                                    onClick: open,
                                    variant: "secondary",
                                    style: { marginBottom: '8px', marginRight: '8px' }
                                }, headerLogo ? '画像を変更' : '画像を選択'),
                                headerLogo && wp.element.createElement(Button, {
                                    onClick: () => setMetaValue('_easy_header_logo', ''),
                                    variant: "link",
                                    isDestructive: true
                                }, "削除"),
                                headerLogo && wp.element.createElement(
                                    'div',
                                    { style: { marginTop: '8px' } },
                                    wp.element.createElement('img', {
                                        src: headerLogo,
                                        alt: "Header Logo Preview",
                                        style: { maxWidth: '150px', height: 'auto', display: 'block' }
                                    })
                                )
                            )
                        })
                    )
                ),

                // タイトル
                wp.element.createElement(TextControl, {
                    label: "ヘッダータイトル",
                    value: headerTitle,
                    onChange: (value) => setMetaValue('_easy_header_title', value),
                    help: "空白の場合は投稿タイトルが使用されます",
                    style: { marginBottom: '16px' }
                }),

                // サブタイトル
                wp.element.createElement(TextControl, {
                    label: "ヘッダーサブタイトル",
                    value: headerSubtitle,
                    onChange: (value) => setMetaValue('_easy_header_subtitle', value),
                    style: { marginBottom: '16px' }
                }),

                // 背景色
                wp.element.createElement(
                    BaseControl,
                    {
                        label: "背景色",
                        className: "easy-header-color-control"
                    },
                    wp.element.createElement(
                        'div',
                        { style: { position: 'relative' } },
                        wp.element.createElement(
                            'div',
                            {
                                style: {
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '8px',
                                    marginBottom: '8px'
                                }
                            },
                            wp.element.createElement('div', {
                                style: {
                                    width: '30px',
                                    height: '30px',
                                    backgroundColor: headerBgColor,
                                    border: '2px solid #ddd',
                                    borderRadius: '3px',
                                    cursor: 'pointer'
                                },
                                onClick: () => setShowBgColorPicker(!showBgColorPicker)
                            }),
                            wp.element.createElement('span', {
                                style: { fontSize: '13px', color: '#555' }
                            }, headerBgColor)
                        ),
                        showBgColorPicker && wp.element.createElement(
                            Popover,
                            {
                                position: "bottom left",
                                onClose: () => setShowBgColorPicker(false)
                            },
                            wp.element.createElement(ColorPicker, {
                                color: headerBgColor,
                                onChange: (color) => setMetaValue('_easy_header_bg_color', color.hex),
                                disableAlpha: true
                            })
                        )
                    )
                ),

                // 文字色
                wp.element.createElement(
                    BaseControl,
                    {
                        label: "文字色",
                        className: "easy-header-color-control"
                    },
                    wp.element.createElement(
                        'div',
                        { style: { position: 'relative' } },
                        wp.element.createElement(
                            'div',
                            {
                                style: {
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '8px',
                                    marginBottom: '8px'
                                }
                            },
                            wp.element.createElement('div', {
                                style: {
                                    width: '30px',
                                    height: '30px',
                                    backgroundColor: headerTextColor,
                                    border: '2px solid #ddd',
                                    borderRadius: '3px',
                                    cursor: 'pointer'
                                },
                                onClick: () => setShowTextColorPicker(!showTextColorPicker)
                            }),
                            wp.element.createElement('span', {
                                style: { fontSize: '13px', color: '#555' }
                            }, headerTextColor)
                        ),
                        showTextColorPicker && wp.element.createElement(
                            Popover,
                            {
                                position: "bottom left",
                                onClose: () => setShowTextColorPicker(false)
                            },
                            wp.element.createElement(ColorPicker, {
                                color: headerTextColor,
                                onChange: (color) => setMetaValue('_easy_header_text_color', color.hex),
                                disableAlpha: true
                            })
                        )
                    )
                )
            )
        );
    });

    // プラグインを登録
    registerPlugin('easy-header-maker', {
        render: EasyHeaderMakerPanel,
        icon: 'admin-customizer'
    });

})(window.wp);