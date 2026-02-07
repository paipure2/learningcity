<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_menu_item_depth')):

class acfe_location_menu_item_depth extends acfe_location{
    
    function initialize(){
        
        $this->name     = 'nav_menu_item_depth';
        $this->label    = __('Menu Item Depth', 'acf');
        $this->category = 'forms';
        
    }
    
    function rule_values($choices, $rule){
    
        if(!acf_is_screen('acf-field-group') && !acf_is_ajax('acf/field_group/render_location_rule') && !acf_is_ajax('acfe/layout/render_location_rule')){
        
            return array(
                $rule['value'] => $rule['value']
            );
        
        }
        
        // default prefix
        $prefix = 'acf_field_group[location]['.$rule['group'].']['.$rule['id'].']';
        
        // custom prefix passed in rule
        // most likley via flexible content layouts locations
        if(acf_maybe_get($rule['prefix'])){
            $prefix = $rule['prefix'];
        }
        
        ob_start();
        
        acf_render_field(array(
            'type'      => 'number',
            'name'      => 'value',
            'min'       => 0,
            'prefix'    => $prefix,
            'value'     => (isset($rule['value']) ? $rule['value'] : '')
        ));
        
        return ob_get_clean();
        
    }
    
    function rule_operators($choices, $rule){
    
        $choices['<']   = __('is less than', 'acf');
        $choices['<=']  = __('is less or equal to', 'acf');
        $choices['>']   = __('is greater than', 'acf');
        $choices['>=']  = __('is greater or equal to', 'acf');
        
        return $choices;
        
    }
    
    function rule_match($result, $rule, $screen){
    
        // Vars
        $depth = acf_maybe_get($screen, 'nav_menu_item_depth');
        
        // Bail early
        if(!$depth && $depth !== 0)
            return false;
    
        // Compare
        return $this->compare_advanced($depth, $rule);
        
    }
    
}

acf_register_location_rule('acfe_location_menu_item_depth');

endif;