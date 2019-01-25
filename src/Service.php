<?php
namespace OffbeatWP\AcfBlocks;

use OffbeatWP\AcfCore\ComponentFields;
use OffbeatWP\Services\AbstractService;

class AcfEditorBlockService extends AbstractService
{
    public function register()
    {
        add_action('offbeat.component.register', [$this, 'registerComponent']);
        add_filter('block_categories', [$this, 'registerComponentsCategory'], 10, 2);
    }

    public function afterRegister()
    {
        if(offbeat('console')->isConsole()) {
            offbeat('console')->register(Console\Install::class);
        }
    }

    public function registerComponent($component)
    {
        $componentClass = $component['class'];
        if (!$componentClass::supports('editor')) {
            return null;
        }

        acf_register_block(array(
            'name'            => $component['name'],
            'component_id'    => $component['name'],
            'title'           => $componentClass::getName(),
            'description'     => $componentClass::getDescription(),
            'render_callback' => [$this, 'renderBlock'],
            'category'        => 'components',
            'icon'            => 'wordpress',
        ));

        add_action('init', function () use ($component) {
            $this->registerBlockFields($component['name']);
        });
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

        $fields = ComponentFields::get($name);

        acf_add_local_field_group(array(
            'key'                   => 'block_component_' . $name,
            'title'                 => $name,
            'fields'                => $fields,
            'location'              => array(
                array(
                    array(
                        'param'    => 'block',
                        'operator' => '==',
                        'value'    => 'acf/' . $name,
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
