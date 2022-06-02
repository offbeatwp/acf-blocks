<?php

namespace OffbeatWP\AcfBlocks;

use ArrayAccess;
use OffbeatWP\AcfBlocks\Console\Install;
use OffbeatWP\AcfCore\ComponentFields;
use OffbeatWP\Components\AbstractComponent;
use OffbeatWP\Services\AbstractService;

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

        if (offbeat('console')->isConsole()) {
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

        acf_register_block([
            'name' => $this->normalizeName($component['name']),
            'component_id' => $component['name'],
            'title' => $componentClass::getName(),
            'description' => $componentClass::getDescription(),
            'render_callback' => [$this, 'renderBlock'],
            'category' => 'components',
            'icon' => $componentClass::getSetting('icon') ?? 'wordpress',
            'supports' => ['jsx' => true],
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

        $transientName = 'offbeat/acf_blocks/fields/' . $name;
        $fields = get_transient($transientName);

        if (empty($fields)) {
            $fields = ComponentFields::get($name, 'block');
            set_transient($transientName, $fields);
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

    /** @param array|ArrayAccess $block */
    public function renderBlock($block): void
    {
        $data = get_fields();
        $data['block'] = $block;

        if (!empty($block['className'])) {
            $data['className'] = $block['className'];
        }

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
