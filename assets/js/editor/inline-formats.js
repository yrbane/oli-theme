/**
 * Formats inline custom pour Gutenberg :
 *   - core/underline : ré-active le souligné (retiré du toolbar par défaut)
 *   - oli/inline-color : couleur appliquée à la sélection seule
 *
 * Aucun bundler — usage direct de l'objet global `wp.*`.
 */
(function (wp) {
    if (!wp || !wp.richText || !wp.blockEditor || !wp.element || !wp.components || !wp.i18n) {
        return;
    }

    var registerFormatType = wp.richText.registerFormatType;
    var toggleFormat = wp.richText.toggleFormat;
    var applyFormat = wp.richText.applyFormat;
    var removeFormat = wp.richText.removeFormat;
    var RichTextToolbarButton = wp.blockEditor.RichTextToolbarButton;
    var ColorPalette = wp.blockEditor.ColorPalette;
    var Popover = wp.components.Popover;
    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var useState = wp.element.useState;
    var __ = wp.i18n.__;

    // --- Souligné ----------------------------------------------------------
    registerFormatType('core/underline', {
        title: __('Souligné', 'oli-theme'),
        tagName: 'u',
        className: null,
        edit: function (props) {
            return el(RichTextToolbarButton, {
                icon: 'editor-underline',
                title: __('Souligné', 'oli-theme'),
                onClick: function () {
                    props.onChange(toggleFormat(props.value, { type: 'core/underline' }));
                },
                isActive: props.isActive,
                shortcutType: 'primary',
                shortcutCharacter: 'u',
            });
        },
    });

    // --- Couleur inline sur sélection -------------------------------------
    var extractColor = function (styleAttr) {
        if (typeof styleAttr !== 'string') {
            return undefined;
        }
        var match = styleAttr.match(/color:\s*([^;]+)/);
        return match ? match[1].trim() : undefined;
    };

    registerFormatType('oli/inline-color', {
        title: __('Couleur du texte (sélection)', 'oli-theme'),
        tagName: 'span',
        className: 'has-inline-color',
        attributes: {
            style: 'style',
        },
        edit: function (props) {
            var state = useState(false);
            var isOpen = state[0];
            var setOpen = state[1];
            var currentColor = extractColor(props.activeAttributes && props.activeAttributes.style);

            return el(
                Fragment,
                null,
                el(RichTextToolbarButton, {
                    icon: 'editor-textcolor',
                    title: __('Couleur du texte', 'oli-theme'),
                    onClick: function () { setOpen(true); },
                    isActive: props.isActive,
                }),
                isOpen && el(
                    Popover,
                    { onClose: function () { setOpen(false); }, position: 'bottom center' },
                    el(
                        'div',
                        { style: { padding: '12px', minWidth: '180px' } },
                        el(ColorPalette, {
                            value: currentColor,
                            disableCustomColors: false,
                            clearable: true,
                            onChange: function (color) {
                                if (!color) {
                                    props.onChange(removeFormat(props.value, 'oli/inline-color'));
                                    return;
                                }
                                props.onChange(applyFormat(props.value, {
                                    type: 'oli/inline-color',
                                    attributes: { style: 'color: ' + color },
                                }));
                            },
                        })
                    )
                )
            );
        },
    });
})(window.wp);
