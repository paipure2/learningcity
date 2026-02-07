<?php

if(!defined('ABSPATH')){
    exit;
}

// check setting
if(!acf_get_setting('acfe/modules/ui')){
    return;
}

// check setting
if(!acf_get_setting('acfe/modules/post_ui')){
    return;
}

if(!class_exists('acfe_pro_enhanced_ui_post')):
    
class acfe_pro_enhanced_ui_post{
    
    /**
     * initialize
     */
    function __construct(){
        
        add_action('acfe/load_post', array($this, 'load_post'), 10, 2);
        
    }
    
    
    /**
     * load_post
     *
     * @param $post_type
     * @param $post_id
     *
     * @return void
     */
    function load_post($post_type, $post_id){
        
        // enqueue style
        wp_enqueue_style('acf-extended-pro-ui-post');
        
        // admin footer
        add_action('admin_footer', array($this, 'admin_footer'));
        
    }
    
    
    function admin_footer(){
        ?>
        <script type="text/javascript">
            (function($){
                
                var saveEmpty = false;
                var previewEmpty = false;
                
                document.querySelectorAll('#minor-publishing-actions #save-action').forEach(function(el){
                    var style = window.getComputedStyle(el);
                    if((el.textContent.trim().length === 0 && el.children.length === 0) || style.display === 'none'){
                        el.classList.add('-empty');
                        saveEmpty = true;
                    }
                });
                
                document.querySelectorAll('#minor-publishing-actions #preview-action').forEach(function(el){
                    var style = window.getComputedStyle(el);
                    if((el.textContent.trim().length === 0 && el.children.length === 0) || style.display === 'none'){
                        el.classList.add('-empty');
                        previewEmpty = true;
                    }
                });
                
                if(saveEmpty && previewEmpty){
                    var wrapper = document.querySelector('#minor-publishing-actions');
                    if(wrapper.getClientRects().length <= 1){
                        wrapper.classList.add('-empty');
                    }
                }
                
            })(jQuery);
        </script>
        <?php
    }

}

new acfe_pro_enhanced_ui_post();

endif;