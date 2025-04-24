<?php

namespace OffbeatWP\AcfBlocks;

use OffbeatWP\AcfBlocks\Console\Install;
use OffbeatWP\AcfCore\ComponentFields;
use OffbeatWP\Components\AbstractComponent;
use OffbeatWP\Services\AbstractService;
use OffbeatWP\Support\Wordpress\Console;

class Service extends AbstractService
{
    public function register(): void
    {
        global $wp_version;

        add_action('offbeat.component.register', [$this, 'registerComponent']);

        // The block_categories filter is deprecated since WordPress 5.8.0, it's replacement is block_categories_all
        if (version_compare($wp_version, '5.8-beta0', '<')) {
            add_filter('block_categories', [$this, 'registerComponentsCategory'], 10, 2);
        } else {
            add_filter('block_categories_all', [$this, 'registerComponentsCategory'], 10, 2);
        }

        if (Console::isConsole()) {
            offbeat('console')->register(Install::class);
        }
    }

    /** @param array{name: string, class: class-string<AbstractComponent>} $component */
    public function registerComponent($component): void
    {
        if (!function_exists('acf_register_block')) {
            return;
        }

        $componentClass = $component['class'];
        if (!$componentClass::supports('editor')) {
            return;
        }

        $blockSettings = $componentClass::getSetting('block') ?? [];

        acf_register_block([
            'name' => $this->normalizeName($component['name']),
            'component_id' => $component['name'],
            'title' => $componentClass::getName(),
            'description' => $componentClass::getDescription(),
            'render_callback' => [$this, 'renderBlock'],
            'enqueue_assets' => [$componentClass, '_enqueueAssets'],
            'category' => 'components',
            'icon' => $componentClass::getSetting('icon') ?? 'wordpress',
            'acf_block_version' => $blockSettings['block_version'] ?? '1',
            'uses_context' => $blockSettings['uses_context'] ?? null,
            'parent' => isset($blockSettings['parent']) && is_array($blockSettings['parent']) ? $blockSettings['parent'] : [],
            'supports' => [
                'jsx' => isset($blockSettings['jsx']) && is_bool($blockSettings['jsx']) ? $blockSettings['jsx'] : true
            ],
            'mode' => 'preview',
        ]);

        add_action('init', function () use ($component) {
            $this->registerBlockFields($component['name']);
        });
    }

    /**
     * @param non-empty-string $name
     * @return string
     */
    public function normalizeName($name): string
    {
        $name = strtolower($name);
        $name = str_replace(['_', '.', ' '], '-', $name);

        return $name;
    }

    /** @param non-empty-string $name */
    public function registerBlockFields($name): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        if (defined('WP_ENV') && WP_ENV === 'production') {
            $transientName = 'offbeat/acf_blocks/fields/' . $name;
            $fields = get_transient($transientName);

            if (!$fields) {
                $fields = ComponentFields::get($name, 'block');
                set_transient($transientName, $fields);
            }
        } else {
            $fields = ComponentFields::get($name, 'block');
        }

        acf_add_local_field_group([
            'key' => 'block_component_' . $this->normalizeName($name),
            'title' => $name,
            'fields' => $fields,
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/' . $this->normalizeName($name),
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => 1,
            'description' => '',
        ]);
    }

    /**
     * @param array $block
     * @param $content
     * @param bool $isPreview
     * @param int $postId
     * @param $wpBlock
     * @param $context
     * @return void
     */
    public function renderBlock($block, $content, $isPreview, $postId, $wpBlock, $context = null): void
    {
        $data = get_fields() ?: [];
        $data['block'] = $block;

        $data['blockArgs'] = [
            'block' => $block,
            'content' => $content,
            'isPreview' => $isPreview,
            'postId' => $postId,
            'wpBlock' => $wpBlock
        ];

        if ($context !== null) {
            $data['blockArgs']['context'] = $context;
        }
        
        $data['className'] = '';
        
        if (!empty($block['className'])) {
            $data['className'] = $block['className'];
        }

        if (!empty($block['align'])) {
            switch($block['align'])
            {
                case 'full':
                    $data['className'] .= 'alignfull';
                    break;
                case 'wide':
                    $data['className'] .= 'alignwide';
                    break;
                case 'left':
                    $data['className'] .= 'alignleft';
                    break;
                case 'right':
                    $data['className'] .= 'alignright';
                    break;
            }
        }

        $data['className'] = trim($data['className']);
        $data['cssClasses'] = $data['className'];

        $blockContent = offbeat('components')->render($block['component_id'], $data);

        echo offbeat('components')->render('block', ['blockContent' => $blockContent]);
    }

    public function registerComponentsCategory(array $categories): array
    {
        return array_merge(
            $categories,
            [
                [
                    'slug' => 'components',
                    'title' => __('Components', 'ofbeatwp'),
                    'icon' => 'wordpress',
                ],
            ]
        );
    }
}
