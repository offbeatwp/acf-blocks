<?php
namespace OffbeatWP\AcfBlocks;

use OffbeatWP\AcfCore\ComponentFields;
use OffbeatWP\Services\AbstractService;

class Service extends AbstractService
{
    public function register()
    {
        add_action('offbeat.component.register', [$this, 'registerComponent']);
        add_filter('block_categories', [$this, 'registerComponentsCategory'], 10, 2);

        if(offbeat('console')->isConsole()) {
            offbeat('console')->register(Console\Install::class);
        }
    }

    public function registerComponent($component)
    {
        if (!function_exists('acf_register_block')) return null;

        $componentClass = $component['class'];
        if (!$componentClass::supports('editor')) {
            return null;
        }

        acf_register_block(array(
            'name'            => $this->normalizeName($component['name']),
            'component_id'    => $component['name'],
            'title'           => $componentClass::getName(),
            'description'     => $componentClass::getDescription(),
            'render_callback' => [$this, 'renderBlock'],
            'category'        => 'components',
            'icon'            => isset($component['editor_icon']) ? $component['editor_icon'] : 'wordpress',
            'supports'        => [
                'align' => true,
                'mode' => false,
                '__experimental_jsx' => true
            ],
            'mode'            => 'preview',
        ));

        add_action('init', function () use ($component) {
            $this->registerBlockFields($component['name']);
        });
    }

    public function normalizeName($name) {
        $name = strtolower($name);
        $name = str_replace(['_', '.', ' '], '-', $name);

        return $name;
    }

    public function renderBlock($block)
    {
        $blockContent = offbeat('components')->render($block['component_id'], get_fields());

        echo offbeat('components')->render('block', ['blockContent' => $blockContent]);
    }

    public function registerBlockFields($name)
    {
        if (!function_exists('acf_add_local_field_group')) {
            return null;
        }

        $fields = ComponentFields::get($name, 'block');

        acf_add_local_field_group(array(
            'key'                   => 'block_component_' . $this->normalizeName($name),
            'title'                 => $name,
            'fields'                => $fields,
            'location'              => array(
                array(
                    array(
                        'param'    => 'block',
                        'operator' => '==',
                        'value'    => 'acf/' . $this->normalizeName($name),
                    ),
                ),
            ),
            'menu_order'            => 0,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen'        => '',
            'active'                => 1,
            'description'           => '',
        ));
    }

    public function registerComponentsCategory($categories, $post)
    {
        return array_merge(
            $categories,
            [
                [
                    'slug'  => 'components',
                    'title' => __('Components', 'ofbeatwp'),
                    'icon'  => 'wordpress',
                ],
            ]
        );
    }
}
