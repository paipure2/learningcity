<?php
/********************************************************************************************
 * Plugin Name: WP Ultimate CSV Importer Pro
 * Description: Seamlessly create / update posts, custom posts, pages, products, media, SEO and contents  of premium plugins from your CSV data with ease.  The import / update can be scheduled at regular intervals. The Importer can pull files from remote servers via ftp or http protocol.
 * Version: 8.11
 * Text Domain: wp-ultimate-csv-importer
 * Domain Path: /languages
 * Author: Smackcoders
 * Plugin URI: https://goo.gl/kKWPui
 * Author URI: https://goo.gl/kKWPui
 *
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/

namespace Smackcoders\WCSV;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly
$upload = wp_upload_dir();
$exports_dir = $upload['basedir'] . '/smack_uci_uploads/exports/';
$htaccess_file = $exports_dir . '.htaccess';
if (file_exists($htaccess_file)) {
	unlink($htaccess_file);
}
define('WCSVPLUGINSLUG', 'wp-ultimate-csv-importer-pro');
define('WCSVPLUGINDIR', plugin_dir_path(__FILE__));
require_once 'Plugin.php';
require_once 'ErrorHandling.php';
require_once 'SmackCSVInstall.php';
require_once 'Uninstall.php';
require_once 'Tables.php';
require_once 'languages/LangIT.php';
require_once 'languages/LangEN.php';
require_once 'languages/LangGE.php';
require_once 'languages/LangFR.php';
require_once 'languages/LangES.php';
require_once 'languages/LangJA.php';
require_once 'languages/LangNL.php';
include_once 'languages/LangRU.php';
include_once 'languages/LangPT.php';
include_once 'languages/LangTR.php';
include_once 'languages/LangenGB.php';
include_once 'languages/LangenCA.php';
include_once 'languages/LangenZA.php';
require_once 'SmackCSVParser.php';
require_once 'SmackCSVVars.php';
require_once 'SmackXLSX.php';
require_once 'FailedImagesUpdate.php';
require_once 'Preview.php';
require_once 'ClientMode.php';


include_once ABSPATH . 'wp-admin/includes/plugin.php';
if (is_plugin_active('wp-ultimate-csv-importer-pro/wp-ultimate-csv-importer-pro.php')) {

    $plugin_pages = ['com.smackcoders.csvimporternewpro.menu'];
    include __DIR__ . '/wp-csv-hooks.php';

    //if (in_array(isset($_REQUEST['page']), $plugin_pages) || in_array(isset($_REQUEST['action']), $plugin_ajax_hooks)) {

        $extension_uploader = glob(__DIR__ . '/extensionUploader/*.php');
        foreach ($extension_uploader as $extension_upload_value) {
            require_once $extension_upload_value;
        }

        $upload_modules = glob(__DIR__ . '/uploadModules/*.php');
        foreach ($upload_modules as $upload_module_value) {
            require_once $upload_module_value;
        }

        $extension_modules = glob(__DIR__ . '/extensionModules/*.php');
        foreach ($extension_modules as $extension_module_value) {
            require_once $extension_module_value;
        }

        $export_modules = glob(__DIR__ . '/exportExtensions/*.php');
        foreach ($export_modules as $export_module_value) {
            require_once $export_module_value;
        }

        $manager_extension = glob(__DIR__ . '/managerExtensions/*.php');
        foreach ($manager_extension as $manager_extension_value) {
            require_once $manager_extension_value;
        }

        $import_extensions = glob(__DIR__ . '/importExtensions/*.php');
        foreach ($import_extensions as $import_extension_value) {
            require_once $import_extension_value;
        }

        require_once 'SaveMapping.php';
        require_once 'MediaHandling.php';
        require_once 'ImportConfiguration.php';
        require_once 'Dashboard.php';
        require_once 'DragandDropExtension.php';
        require_once 'scheduleExtensions/ScheduleExtension.php';
        require_once 'scheduleExtensions/ScheduleImport.php';
        require_once 'scheduleExtensions/ScheduleExport.php';
        require_once 'controllers/DBOptimizer.php';
        require_once 'controllers/SendPassword.php';
        require_once 'controllers/SupportMail.php';
        require_once 'controllers/GSheet.php';
        include_once('controllers/HelperExtension.php');
		include_once('controllers/NeedHelperExtension.php');
        require_once 'controllers/Security.php';
        require_once 'ScheduleHandler.php';
        require_once 'SmackcliHandler.php';
        include_once('SingleImportExport.php');
        $singlecsv_class = new SingleImportExport();

}

class SmackCSV
{
    
    private static $instance = null;
    private static $table_instance = null;
    private static $desktop_upload_instance = null;
    private static $server_upload_instance = null;
    private static $url_upload_instance = null;
    private static $ftp_upload_instance = null;
    private static $ftps_upload_instance = null;
    private static $sftp_upload_instance = null;
    private static $xml_instance = null;
    private static $mapping_instance = null;
    private static $extension_instance = null;
    private static $save_mapping_instance = null;
    private static $plugin_instance = null;
    private static $helper_instance = null ;
	private static $need_helper_instance = null ;

    private static $import_config_instance = null;
    private static $dashboard_instance = null;
    private static $drag_drop_instance = null;
    private static $file_manager_instance = null;
    private static $template_manager_instance = null;
    private static $log_manager_instance = null;
    private static $schedule_manager_instance = null;
    private static $schedule_instance = null;
    private static $media_instance = null;
    private static $export_instance = null;
    private static $export_handler = null;
    private static $db_optimizer = null;
    private static $send_password = null;
    private static $nextgen_instance = null;
    private static $security = null;
    private static $support_instance = null;
    private static $uninstall = null;
    private static $failed_images_instance = null;
    private static $install = null;
    private static $schedule_export = null;
    private static $schedule_import = null;
    private static $en_instance = null;
    private static $italy_instance = null;
    private static $german_instance = null;
    private static $france_instance = null;
    private static $spanish_instance = null;
    private static $japanese_instance = null;
    private static $dutch_instance = null;
    private static $russian_instance = null;
    private	static $portuguese_instance = null;
    private static $turkish_instance = null;
    private static $en_CA_instance = null ;
    private static $en_GB_instance = null ;
    private static $en_ZA_instance = null;
    private static $image_schedule_instance = null;
    public $version = '8.11';

    private function __construct()
    {
        add_action('init', array(__CLASS__, 'show_admin_menus'));
        self::initializing_scheduler();
        add_action('smack_uci_cron_scheduled_export',array(__CLASS__,'schedule_event_function'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'singlecsv_enqueue_function'));
        add_action('add_meta_boxes', [$this,'product_import_export_metabox']);
        $this->init_review_notice();

    }

    public function init_review_notice() {
    add_action('admin_notices', [$this, 'render_review_notice']);
    add_action('admin_init', [$this, 'handle_review_notice_actions']);
}

public function render_review_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $activation_time = get_option('wcsv_pro_activation_time');
    $dismissed       = get_option('wcsv_pro_review_dismissed');
    $later           = get_option('wcsv_pro_review_later');

    if (!$activation_time) {
        update_option('wcsv_pro_activation_time', time());
        return;
    }

    if ((time() - $activation_time) < 7 * DAY_IN_SECONDS) {
        return;
    }

    if ($dismissed) {
        return;
    }

    if ($later && (time() - $later) < 7 * DAY_IN_SECONDS) {
        return;
    }
    ?>
    <div class="notice notice-success is-dismissible">
        <h2><?php esc_html_e('Give CSV Importer Pro a review', 'wp-ultimate-csv-importer'); ?></h2>
        <p>
            <?php esc_html_e('Thank you for choosing CSV Importer Pro! We hope you love it. Could you take a couple of seconds to post a nice review?', 'wp-ultimate-csv-importer'); ?>
        </p>
        <p>
            <a href="https://wordpress.org/plugins/wp-ultimate-csv-importer/reviews/?rate=5#new-post"
               target="_blank" class="button button-primary">⭐ Rate now</a>
            <a href="<?php echo esc_url(add_query_arg('wcsv_pro_review_later', '1')); ?>" class="button">Later</a>
            <a href="<?php echo esc_url(add_query_arg('wcsv_pro_review_dismiss', '1')); ?>" class="button">No, thanks</a>
        </p>
    </div>
    <?php
}

public function handle_review_notice_actions() {
    if (isset($_GET['wcsv_pro_review_dismiss'])) {
        update_option('wcsv_pro_review_dismissed', 1);
        wp_redirect(remove_query_arg('wcsv_pro_review_dismiss'));
        exit;
    }

    if (isset($_GET['wcsv_pro_review_later'])) {
        update_option('wcsv_pro_review_later', time());
        wp_redirect(remove_query_arg('wcsv_pro_review_later')); 
        exit;
    }
}


    public static function singlecsv_enqueue_function()
    {
        wp_register_script('react-product-js', plugins_url('assets/js/react-product-app.js', __FILE__), array('react'));
        wp_enqueue_script(
			'my-react-app',
			plugins_url('assets/js/react-product-app.js', __FILE__), // Path to your React build
			'1.0',
			true
		);
        wp_enqueue_style('my-product-css', plugins_url('assets/css/deps/csv-product.css', __FILE__));
		wp_register_script('react-js', plugins_url('assets/js/react-app.js', __FILE__), array('react'));
     
		wp_enqueue_script(
			'react-js',
			plugin_dir_url(__FILE__) . 'assets/js/react-app.js', // Path to your React build
			['wp-element', 'wp-components', 'wp-i18n'], // Gutenberg dependencies
			'1.0',
			true
		);
        $secure_uniquekey_csv = array(
            'url' => admin_url('admin-ajax.php') ,
            'nonce' => wp_create_nonce('smack-ultimate-csv-importer'),
            'imagePath' => plugins_url('/assets/images/', __FILE__)
        );
       
        wp_localize_script('react-js', 'smack_nonce_object', $secure_uniquekey_csv);

    }
 
    public static function product_import_export_metabox() {
        $post_type = get_post_type(get_the_ID());
        $all_post_types = get_post_types(array('public' => true), 'names');
        // Add the meta box if the post type is a standard one or a custom post type
        if (in_array($post_type, $all_post_types)) {
            add_meta_box(
                'smackcsv_product_id',           // Unique ID
                'Import/Export',                 // Box title
                array(__CLASS__, 'importandexport'),  // Callback function
                $post_type,                      // Post type
                'side',                          // Context ('normal', 'side', or 'advanced')
                'high'                           // Priority ('high' or 'low')
            );
        }
    }
   public static function importandexport($post) {
        ?>
       <div class="product-tab-containers">
        <div class="product-tab-menu">
            <button class="product-tab active" data-tab="tab1">Import</button>
            <button class="product-tab" data-tab="tab2">Export</button>                        
        </div>
        <div class="tab-content">
            <div id="tab1" class="tab-panel active">
                <p>Import options go here. You can import your CSV file using the options provided.</p>
                <input type="file" id="product-import-file" name="product-import-file" accept=".csv">
                <div class="loading-bar">
                    <div id="loading-progress"></div>
                </div>
                <small id="product-import-status"></small>
                <button id="upload-product-import-btn" >Upload Import</button>
                    <button id="clear-btn">clear</button>
                    <small id="smack-imp-message"></small>
                <p id="smack-message"></p>
            </div>
            <div id="tab2" class="tab-panel">
                <p>Export options go here. You can export your CSV file using the options provided.</p>
                <button id="product-export-btn" style="margin: 20px auto 0; display: block; background-color: #007cba; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;margin-bottom:16px">Export CSV</button>
                <small id="smack-product-exp-message" style=" display: none; color:#FF0000; text-align: right;  "></small>
            </div>
        </div>
    </div>
    <style>
 
</style>
<script>
  
</script>
        <script>
            // document.getElementById('my-custom-button').addEventListener('click', function(event) {
            //     event.preventDefault();
            //     alert('Button clicked!');
            // });
        </script>
        <?php
    }
    
    public static function  initInstance()
    {
        add_action('init', array(__CLASS__, 'show_admin_menus'));
    }

    public static function show_admin_menus()
    {
        $roles = wp_roles();
        $higher_level_roles = ['administrator'];
        // $client_mode_values = get_option('client_mode_value');

        // if(!empty($client_mode_values)){
        // $client_array = explode(',',$client_mode_values);
        // $higher_level_roles = array_merge($higher_level_roles,$client_array);
        // }
        // By default, administrator role will have the capabilities
        foreach ($roles->role_objects as $role) {
            if (in_array($role->name, $higher_level_roles)) {
                if (!$role->has_cap('csv_importer_pro')) {
                    $role->add_cap('csv_importer_pro');
                }
            }
        }    
        $current_user = wp_get_current_user();
        $current_role = $current_user->roles;
        global $wpdb;
         foreach($current_role as $role_key => $roles){
            $checkrole = $wpdb->get_results("SELECT client_mode_values FROM {$wpdb->prefix}ultimate_csv_importer_export_template WHERE client_mode='true' AND client_mode_values like '%$roles%'",ARRAY_A);
            if(!empty($checkrole)){
                $higher_level_roles = array_merge($higher_level_roles,$current_role);
            }
         }
         $admin_mode =1;
        // $current_user->hascap()
// $current_user->add_cap('csv_importer_pro');
        $ucisettings = get_option('sm_uci_pro_settings');
        add_action('admin_menu', array(__CLASS__, 'load_functionalities'));
        foreach($current_role as $role_key => $roles){
            if(($roles !=='administrator') && in_array($roles,$higher_level_roles)){
                $admin_mode =0;
                add_action('admin_menu', [__CLASS__,'editor_menu']);
            }   
        }  
        $plugin_pages = ['com.smackcoders.csvimporternewpro.menu'];
   if (isset($_REQUEST['page']) && in_array($_REQUEST['page'], $plugin_pages)) {

    // Register and enqueue admin script
    wp_register_script(
        SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer',
        plugins_url('assets/js/admin-v7.3.js', __FILE__),
        array('jquery'),
        '7.3',
        true
    );
    wp_enqueue_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer');

    // Enqueue your React-based client JS
    wp_enqueue_script(
        'client-js',
        plugin_dir_url(__FILE__) . 'assets/js/react-app.js',
        ['wp-element', 'wp-components', 'wp-i18n'],
        '1.0',
        true
    );

    // Pass localized data to JS
    $secure_uniquekey_csvs = array(
        'url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('smack-ultimate-csv-importer'),
        'admin_mode' => $admin_mode,
    );

    wp_localize_script('client-js', 'smack_nonce_object_client', $secure_uniquekey_csvs);
}

}
    

    public static function getInstance()
    {

        if (SmackCSV::$instance == null) {

            SmackCSV::$instance = new SmackCSV();
            SmackCSV::$table_instance = Tables::getInstance();
            SmackCSV::$schedule_instance = ScheduleExtension::getInstance();
            SmackCSV::$schedule_export = ScheduleExport::getInstance();
            SmackCSV::$desktop_upload_instance = DesktopUpload::getInstance();
            SmackCSV::$server_upload_instance = ServerUpload::getInstance();
            SmackCSV::$url_upload_instance = UrlUpload::getInstance();
            SmackCSV::$ftp_upload_instance = FtpUpload::getInstance();
            SmackCSV::$ftps_upload_instance = FtpsUpload::getInstance();
            SmackCSV::$sftp_upload_instance = SftpUpload::getInstance();
            SmackCSV::$xml_instance = XmlHandler::getInstance();
            SmackCSV::$mapping_instance = MappingExtension::getInstance();
            SmackCSV::$extension_instance = new ExtensionHandler;
            SmackCSV::$save_mapping_instance = SaveMapping::getInstance();
            SmackCSV::$media_instance = MediaHandling::getInstance();
            SmackCSV::$import_config_instance = ImportConfiguration::getInstance();
            SmackCSV::$dashboard_instance = Dashboard::getInstance();
            SmackCSV::$drag_drop_instance = DragandDropExtension::getInstance();
            SmackCSV::$file_manager_instance = FileManager::getInstance();
            SmackCSV::$template_manager_instance = TemplateManager::getInstance();
            SmackCSV::$log_manager_instance = LogManager::getInstance();
            SmackCSV::$schedule_manager_instance = ScheduleManager::getInstance();
            SmackCSV::$plugin_instance = Plugin::getInstance();
            SmackCSV::$export_instance = ExportExtension::getInstance();
            SmackCSV::$schedule_instance = ScheduleExtension::getInstance();
            SmackCSV::$schedule_import = ScheduleImport::getInstance();
            SmackCSV::$schedule_export = ScheduleExport::getInstance();
            SmackCSV::$export_handler = ExportHandler::getInstance();
            SmackCSV::$db_optimizer = DBOptimizer::getInstance();
            SmackCSV::$send_password = SendPassword::getInstance();
            SmackCSV::$nextgen_instance = NextGenGalleryImport::getInstance();
            SmackCSV::$security = Security::getInstance();
            SmackCSV::$support_instance = SupportMail::getInstance();
            SmackCSV::$helper_instance = HelperExtension::getInstance();
			SmackCSV::$need_helper_instance = NeedHelperExtension::getInstance();
            SmackCSV::$uninstall = SmackUCIUnInstall::getInstance();
            SmackCSV::$install = SmackCSVInstall::getInstance();
            SmackCSV::$italy_instance = LangIT::getInstance();
            SmackCSV::$france_instance = LangFR::getInstance();
            SmackCSV::$german_instance = LangGE::getInstance();
            SmackCSV::$en_instance = LangEN::getInstance();
            SmackCSV::$spanish_instance = LangES::getInstance();
            SmackCSV::$japanese_instance = LangJA::getInstance();
            SmackCSV::$dutch_instance = LangNL::getInstance();
            SmackCSV::$en_CA_instance = LangEN_CA::getInstance();
            SmackCSV::$en_GB_instance = LangEN_GB::getInstance();
            SmackCSV::$en_ZA_instance = LangEN_ZA::getInstance();
            SmackCSV::$russian_instance = LangRU::getInstance();
            SmackCSV::$portuguese_instance = LangPT::getInstance();
            SmackCSV::$turkish_instance = LangTR::getInstance();
            SmackCSV::$failed_images_instance = FailedImagesUpdate::getInstance();
            add_filter('http_request_args', array(SmackCSV::$install, 'curlArgs'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(SmackCSV::$install, 'plugin_row_meta'), 10, 2);
            add_action('after_plugin_row_' . plugin_basename(__FILE__), array(SmackCSV::$install, 'after_plugin_row_meta'), 10, 3);
           
           // add_filter('cron_schedules', array(SmackCSV::$install, 'cron_schedules'));        
           
            add_filter( 'https_local_ssl_verify', '__return_false' );
            add_filter('https_ssl_verify', '__return_false');
            
            if (!function_exists('is_plugin_active')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            if (is_plugin_active('wp-ultimate-csv-importer-pro/wp-ultimate-csv-importer-pro.php')) {
                add_action('admin_notices', array(SmackCSV::$install, 'wp_ultimate_csv_importer_notice'));
                add_action('admin_notices', array(SmackCSV::$install, 'important_cron_notice'));
                // deactivate_plugins('wp-ultimate-csv-importer/wp-ultimate-csv-importer.php');
                deactivate_plugins('wp-ultimate-exporter/wp-ultimate-exporter.php');
                deactivate_plugins('wp-user-import/import-users.php');
                deactivate_plugins('import-woocommerce/import-woocommerce.php');
                deactivate_plugins('wp-importer-custom-fields-basic-pro/wp-importer-custom-fields-basic-pro.php');
                deactivate_plugins('wordpress-importer-for-wpml-pro/ wordpress-importer-wpml-pro.php');
            }
            
            self::init_hooks();

            return SmackCSV::$instance;
        }
        return SmackCSV::$instance;
    }

    public static function init_hooks()
    {
        $ucisettings = get_option('sm_uci_pro_settings');
        if (isset($ucisettings['enable_main_mode']) && $ucisettings['enable_main_mode'] == 'true') {
            add_action('admin_bar_menu', array(SmackCSV::$instance, 'admin_bar_menu'));
            add_action('wp_head', array(SmackCSV::$instance, 'activate_maintenance_mode'));
        } 
    }

    public static function initializing_scheduler()
    {
        if (!wp_next_scheduled('smack_uci_cron_scheduler')) {
            wp_schedule_event(time(), 'wp_ultimate_csv_importer_scheduled_csv_data', 'smack_uci_cron_scheduler');
        }
        if (!wp_next_scheduled('smack_uci_cron_scheduled_export')) {
            wp_schedule_event(time(), 'wp_ultimate_csv_importer_scheduled_csv_data', 'smack_uci_cron_scheduled_export');
        }
        if (!wp_next_scheduled('smack_uci_image_scheduler')) {
            wp_schedule_event(time(), 'wp_ultimate_csv_importer_scheduled_images', 'smack_uci_image_scheduler');
        }
        if (!wp_next_scheduled('smack_uci_email_scheduler')) {
            wp_schedule_event(time(), 'wp_ultimate_csv_importer_scheduled_emails', 'smack_uci_email_scheduler');
        }
        if (!wp_next_scheduled('smack_uci_replace_inline_images')) {
            wp_schedule_event(time(), 'wp_ultimate_csv_importer_replace_inline_images', 'smack_uci_replace_inline_images');
        }
    }

    public static function schedule_event_function(){
        $scheduleex_obj =new ScheduleExport;
        $scheduleex_obj->smack_uci_cron_scheduled_export();
    } 

    public  static function load_functionalities()
    {
        remove_menu_page('com.smackcoders.csvimporternewpro.menu');
        $my_page = add_menu_page('Ultimate CSV Importer PRO', 'Ultimate CSV Importer PRO', 'manage_options',
            'com.smackcoders.csvimporternewpro.menu', array(__CLASS__, 'load_menu'), plugins_url("assets/images/wp-ultimate-csv-importer.png", __FILE__));
        add_action('load-' . $my_page, array(__CLASS__, 'load_admin_js'));
    }

    public static function load_admin_js()
    {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'csv_enqueue_function'));
    }

    public static function editor_menu()
    {
        remove_menu_page('com.smackcoders.csvimporternewpro.menu');
        $my_page = add_menu_page('Ultimate CSV Importer PRO', 'Ultimate CSV Importer PRO', '2',
            'com.smackcoders.csvimporternewpro.menu', array(__CLASS__, 'load_menu'), plugins_url("assets/images/wp-ultimate-csv-importer.png", __FILE__));
        add_action('load-' . $my_page, array(__CLASS__, 'load_admin_js'));
    }

    public static function load_menu()
    {
        ?><div id="wp-csv-importer-admin-pro"></div><?php
    }

    public static function csv_enqueue_function()
    {
        wp_register_script(SmackCSV::$plugin_instance->getPluginSlug() . 'jquery-ui-js', plugins_url('assets/js/deps/jquery-ui.min.js', __FILE__), array('jquery'));
        wp_enqueue_script(SmackCSV::$plugin_instance->getPluginSlug() . 'jquery-ui-js');

        wp_register_script(SmackCSV::$plugin_instance->getPluginSlug() . 'popper', plugins_url('assets/js/deps/popper.js', __FILE__), array('jquery'));
        wp_enqueue_script(SmackCSV::$plugin_instance->getPluginSlug() . 'popper');

        wp_register_script(SmackCSV::$plugin_instance->getPluginSlug() . 'bootstrap', plugins_url('assets/js/deps/bootstrap.min.js', __FILE__), array('jquery'));
        wp_enqueue_script(SmackCSV::$plugin_instance->getPluginSlug() . 'bootstrap');

        wp_register_script(SmackCSV::$plugin_instance->getPluginSlug() . 'main-js', plugins_url('assets/js/deps/main.js', __FILE__), array('jquery'));
        wp_enqueue_script(SmackCSV::$plugin_instance->getPluginSlug() . 'main-js');
        wp_register_script(SmackCSV::$plugin_instance->getPluginSlug() . 'file-tree', plugins_url('assets/js/deps/jQueryFileTree.min.js', __FILE__), array('jquery'));
        wp_enqueue_script(SmackCSV::$plugin_instance->getPluginSlug() . 'file-tree');

         wp_enqueue_style(SmackCSV::$plugin_instance->getPluginSlug() . 'bootstrap-css', plugins_url('assets/css/deps/bootstrap.min.css', __FILE__));
        wp_enqueue_style(SmackCSV::$plugin_instance->getPluginSlug() . 'filepond-css', plugins_url('assets/css/deps/filepond.min.css', __FILE__));
        wp_enqueue_style(SmackCSV::$plugin_instance->getPluginSlug() . 'csv-importer-css', plugins_url('assets/css/deps/csv-importer.css', __FILE__));
        wp_enqueue_style(SmackCSV::$plugin_instance->getPluginSlug() . 'style-css', plugins_url('assets/css/deps/style.css', __FILE__));
        wp_enqueue_style(SmackCSV::$plugin_instance->getPluginSlug() . 'react-datepicker-css', plugins_url('assets/css/deps/react-datepicker.css', __FILE__));
        // wp_enqueue_style(SmackCSV::$plugin_instance->getPluginSlug() . 'react-toasty-css', plugins_url('assets/css/deps/ReactToastify.min.css', __FILE__));
        wp_enqueue_style(SmackCSV::$plugin_instance->getPluginSlug() . 'react-toastify-css', plugins_url('assets/css/deps/ReactToastify.css', __FILE__));
        wp_enqueue_style(SmackCSV::$plugin_instance->getPluginSlug() . 'react-confirm-alert-css', plugins_url('assets/css/deps/react-confirm-alert.css', __FILE__));
    
        wp_register_script(SmackCSV::$plugin_instance->getPluginSlug() . 'main-js', plugins_url('assets/js/deps/main.js', __FILE__), array('jquery'));
        wp_enqueue_script(SmackCSV::$plugin_instance->getPluginSlug() . 'main-js');
        wp_register_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer', plugins_url('assets/js/admin-v7.3.js', __FILE__), array('jquery'));
        wp_enqueue_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer');

        $user_id = get_current_user_id();
        $language = get_locale();
        $upload = wp_upload_dir();
        $upload_base_url = $upload['baseurl'];
        $upload_url = $upload_base_url . '/smack_uci_uploads/imports';
    
        if ($language == 'it_IT') {
            $contents = SmackCSV::$italy_instance->contents();
            $response = wp_json_encode($contents);
            wp_localize_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer', 'wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugins_url('/assets/images/', __FILE__),'logfielpath' => $upload_url));
        } elseif ($language == 'fr_FR' || $language == 'fr_BE') {
            $contents = SmackCSV::$france_instance->contents();
            $response = wp_json_encode($contents);
            wp_localize_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer', 'wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugins_url('/assets/images/', __FILE__),'logfielpath' => $upload_url));
        } elseif ($language == 'de_DE' || $language == 'de_AT') {
            $contents = SmackCSV::$german_instance->contents();
            $response = wp_json_encode($contents);
            wp_localize_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer', 'wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugins_url('/assets/images/', __FILE__),'logfielpath' => $upload_url));
        } elseif ($language == 'es_ES') {
            $contents = SmackCSV::$spanish_instance->contents();
            $response = wp_json_encode($contents);
            wp_localize_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer', 'wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugins_url('/assets/images/', __FILE__),'logfielpath' => $upload_url));
        } elseif ($language == 'ja') {
            $contents = SmackCSV::$japanese_instance->contents();
            $response = wp_json_encode($contents);
            wp_localize_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer', 'wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugins_url('/assets/images/', __FILE__),'logfielpath' => $upload_url));
        }
        elseif ($language == 'nl_NL') {
            $contents = SmackCSV::$dutch_instance->contents();
            $response = wp_json_encode($contents);
            wp_localize_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer', 'wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugins_url('/assets/images/', __FILE__),'logfielpath' => $upload_url));
        }
        elseif ($language == 'en_CA') {
			$contents = SmackCSV::$en_CA_instance->contents();
			$response = wp_json_encode($contents);
			wp_localize_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer', 'wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugins_url('/assets/images/', __FILE__),'logfielpath' => $upload_url));
		}
		elseif ($language == 'en_GB') {
			$contents = SmackCSV::$en_GB_instance->contents();
			$response = wp_json_encode($contents);
			wp_localize_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer', 'wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugins_url('/assets/images/', __FILE__),'logfielpath' => $upload_url));
		}
		elseif ($language == 'tr_TR') {
			$contents = SmackCSV::$turkish_instance->contents();
			$response = wp_json_encode($contents);
			wp_localize_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer', 'wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugins_url('/assets/images/', __FILE__),'logfielpath' => $upload_url));
		}
		elseif ($language == 'en_ZA') {
			$contents = SmackCSV::$en_ZA_instance->contents();
			$response = wp_json_encode($contents);
			wp_localize_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer', 'wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugins_url('/assets/images/', __FILE__),'logfielpath' => $upload_url));
		}
		elseif ($language == 'ru_RU') {
			$contents = SmackCSV::$russian_instance->contents();
			$response = wp_json_encode($contents);
			wp_localize_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer', 'wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugins_url('/assets/images/', __FILE__),'logfielpath' => $upload_url));
		}
		elseif($language == 'pt_BR') {
			$contents = SmackCSV::$portuguese_instance->contents();
			$response = wp_json_encode($contents);
			wp_localize_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer', 'wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugins_url('/assets/images/', __FILE__),'logfielpath' => $upload_url));
		}
        else {
            $contents = SmackCSV::$en_instance->contents();
            $response = wp_json_encode($contents);
            wp_localize_script(SmackCSV::$plugin_instance->getPluginSlug() . 'script_csv_importer', 'wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugins_url('/assets/images/', __FILE__),'logfielpath' => $upload_url));
        }

        /* Create Nonce */
        $secure_uniquekey_csvpro = array(
            'url' => admin_url('admin-ajax.php') ,
            'nonce' => wp_create_nonce('smack-ultimate-csv-importer-pro')
        );
		wp_localize_script(SmackCSV::$plugin_instance->getPluginSlug().'script_csv_importer', 'smack_nonce_object_pro', $secure_uniquekey_csvpro);

    }

    /**
     * Generates unique key for each file.
     * @param string $value - filename
     * @return string hashkey
     */
    public function convert_string2hash_key($value)
    {
        $file_name = hash_hmac('md5', "$value" . time(), 'secret');
        return $file_name;
    }

    /**
     * Creates a folder in uploads.
     * @return string path to that folder
     */
    public function create_upload_dir($mode = null)
    {        
		$upload = wp_upload_dir();
		$upload_dir = $upload['basedir'];
			if(!is_dir($upload_dir)){
				return false;
        } else {
				$upload_dir = $upload_dir . '/smack_uci_uploads/imports/';
				if (!is_dir($upload_dir)) {
					wp_mkdir_p($upload_dir);
					chmod($upload_dir, 0755);

					$index_php_file = $upload_dir . 'index.php';
					if (!file_exists($index_php_file)) {
						$file_content = '<?php' . PHP_EOL . '?>';
						file_put_contents($index_php_file, $file_content);
					}
				}
			if($mode != 'CLI')
            {
				chmod($upload_dir, 0777);
			}
			return $upload_dir;
		}
	}

    public function admin_bar_menu()
    {
        global $wp_admin_bar;
        $wp_admin_bar->add_menu(array(
            'id' => 'debug-bar',
            'href' => admin_url() . 'admin.php?page=com.smackcoders.csvimporternewpro.menu',
            'parent' => 'top-secondary',
            'title' => apply_filters('debug_bar_title', __('Maintenance Mode', 'ultimate-maintenance-mode')),
            'meta' => array('class' => 'smack-main-mode'),
        ));
    }

    public function activate_maintenance_mode()
    {
        include ABSPATH . "wp-includes/pluggable.php";
        global $maintainance_text;
        $maintainance_text = "Site is under maintenance mode. Please wait few min!";
        if (!current_user_can('manage_options')) {
            ?>
        <div class="main-mode-front"> <span> <?php echo $maintainance_text; ?> </span> </div>
        <?php }
    }
}

$activate_plugin = SmackCSVInstall::getInstance();
$deactive_plugin = SmackUCIUnInstall::getInstance();
register_activation_hook(__FILE__, array($activate_plugin, 'install'));
register_deactivation_hook(__FILE__, array($deactive_plugin, 'unInstall'));

add_action('plugins_loaded', 'Smackcoders\\WCSV\\onpluginsload');

function onpluginsload()
{
    $plugin_pages = ['com.smackcoders.csvimporternewpro.menu'];
    include __DIR__ . '/wp-csv-hooks.php';
   //if (in_array(isset($_REQUEST['page']), $plugin_pages) || in_array(isset($_REQUEST['action']), $plugin_ajax_hooks)) {
        SmackCSV::getInstance();
    // } else {
    //     SmackCSV::initInstance();
    // }         
}


//remove other plugins message box
add_action('admin_head', 'Smackcoders\\WCSV\\disable_admin_notices_on_custom_plugin_page');

function disable_admin_notices_on_custom_plugin_page() {
   
    $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    if ($page === 'com.smackcoders.csvimporternewpro.menu') {
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
    }
}



function start_schedule_function() {
    $schedule_obj = new ScheduleExtension();
    $schedule_obj->smack_uci_cron_scheduler();
}

?>