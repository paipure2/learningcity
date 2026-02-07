<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_pro_field_fc_search')):

class acfe_pro_field_fc_search{
    
    /**
     * construct
     */
    function __construct(){
        
        add_filter('acfe/flexible/defaults_field',              array($this, 'defaults_field'), 10);
        add_action('acfe/flexible/render_field_settings',       array($this, 'render_field_settings'), 10);
        add_action('acfe/flexible/render_popup_select',         array($this, 'render_popup_select_search'), 9);
        add_filter('acf/translate_field/type=flexible_content', array($this, 'translate_field'));
        
    }
    
    
    /**
     * translate_field
     *
     * @return array
     */
    function translate_field(){
        
        if(isset($field['acfe_flexible_search']['acfe_flexible_search_text'])){
            $field['acfe_flexible_search']['acfe_flexible_search_text'] = acf_translate($field['acfe_flexible_search']['acfe_flexible_search_text']);
        }
        
        if(isset($field['acfe_flexible_search']['acfe_flexible_search_not_found'])){
            $field['acfe_flexible_search']['acfe_flexible_search_not_found'] = acf_translate($field['acfe_flexible_search']['acfe_flexible_search_not_found']);
        }
        
        return $field;
        
    }
    
    
    /**
     * defaults_field
     *
     * @param $field
     *
     * @return mixed
     */
    function defaults_field($field){
        
        $field['acfe_flexible_search'] = array(
            'acfe_flexible_search_enabled'   => false,
            'acfe_flexible_search_text'      => '',
            'acfe_flexible_search_not_found' => '',
        );
        
        return $field;
        
    }
    
    
    /**
     * render_field_settings
     *
     * @param $field
     */
    function render_field_settings($field){
        
        acf_render_field_setting($field, array(
            'label'         => __('Search Layouts', 'acfe'),
            'name'          => 'acfe_flexible_search',
            'key'           => 'acfe_flexible_search',
            'instructions'  => __('Allow users to find layouts using text search.', 'acfe'),
            'type'          => 'group',
            'layout'        => 'block',
            'sub_fields'    => array(
                array(
                    'label'             => '',
                    'name'              => 'acfe_flexible_search_enabled',
                    'key'               => 'acfe_flexible_search_enabled',
                    'type'              => 'true_false',
                    'instructions'      => '',
                    'required'          => false,
                    'wrapper'           => array(
                        'class' => 'acfe_width_auto',
                        'id'    => '',
                    ),
                    'message'           => '',
                    'default_value'     => false,
                    'ui'                => true,
                    'ui_on_text'        => '',
                    'ui_off_text'       => '',
                    'conditional_logic' => false,
                ),
                array(
                    'label'         => '',
                    'name'          => 'acfe_flexible_search_text',
                    'key'           => 'acfe_flexible_search_text',
                    'type'          => 'text',
                    'prepend'       => __('Placeholder', 'acfe'),
                    'placeholder'   => __('Search layouts...', 'acfe'),
                    'instructions'  => false,
                    'required'      => false,
                    'wrapper'       => array(
                        'width' => '25%',
                        'class' => '',
                        'id'    => '',
                    ),
                    'conditional_logic' => array(
                        array(
                            array(
                                'field'     => 'acfe_flexible_search_enabled',
                                'operator'  => '==',
                                'value'     => '1',
                            )
                        )
                    )
                ),
                array(
                    'label'         => '',
                    'name'          => 'acfe_flexible_search_not_found',
                    'key'           => 'acfe_flexible_search_not_found',
                    'type'          => 'text',
                    'prepend'       => __('Not found message', 'acfe'),
                    'placeholder'   => __('No results found.', 'acfe'),
                    'instructions'  => false,
                    'required'      => false,
                    'wrapper'       => array(
                        'width' => '55%',
                        'class' => '',
                        'id'    => '',
                    ),
                    'conditional_logic' => array(
                        array(
                            array(
                                'field'     => 'acfe_flexible_search_enabled',
                                'operator'  => '==',
                                'value'     => '1',
                            )
                        )
                    )
                ),
            ),
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'acfe_flexible_advanced',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                    array(
                        'field'     => 'acfe_flexible_modal_enabled',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                )
            ),
            'wrapper' => array(
                'class' => 'acfe-field-setting-flex'
            )
        ));
        
    }
    
    
    /**
     * render_popup_select_search
     *
     * @param $field
     *
     * @return void
     */
    function render_popup_select_search($field){
        
        // categories
        if(!$field['acfe_flexible_search']['acfe_flexible_search_enabled']){
            return;
        }
        
        ?>
        <div class="acfe-fc-search">
            <input type="text" placeholder="<?php _e('Search layouts...', 'acfe'); ?>" />
            <span class="clear"></span>
        </div>
        <div class="acfe-fc-search-no-results acf-hidden">
            <?php _e('No results found.', 'acfe'); ?>
        </div>
        <?php
        
    }
    
}

// initialize
new acfe_pro_field_fc_search();

endif;