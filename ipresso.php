<?php

/**
 * @package iPresso
 * @version 1.2
 */
/*
  Plugin Name: iPresso
  Description: iPresso is a complex Marketing Automation 360 platform, integrating the most important tools for successful marketing.
  Author: Michał Per
  Author URI: http://ipresso.com/
  Version: 1.2
 */

session_start();
define('IPRESSO_PATH', plugin_dir_path(__FILE__));

require IPRESSO_PATH . '/include/agreements.php';
require IPRESSO_PATH . '/include/api.php';
require IPRESSO_PATH . '/include/comments.php';
require IPRESSO_PATH . '/include/funct.php';
require IPRESSO_PATH . '/include/installation.php';
require IPRESSO_PATH . '/include/registration.php';
require IPRESSO_PATH . '/include/sqlquery.php';


class iPresso
{
    private $functions;
    private $registrationForm;
    private $commentsForm;
    private $agreementsForm;
    private $installation;

    function __construct()
    {
        $this->functions = new iPresso_Function();
        $this->registrationForm = new iPresso_RegistrationForm();
        $this->commentsForm = new iPresso_CommentsForm();
        $this->agreementsForm = new iPresso_Agreements();
        $this->installation = new iPresso_Installation();
    }

    /**
     * Wyświetlanie menu pluginu iPresso
     */
    public function ipressoPluginMenu()
    {
        $userDataSubmenuName = __('User data', 'wp-admin-ipresso');
        $commentsSubmenuName = __('Comments', 'wp-admin-ipresso');
        $agreementsSubmenuName = __('Marketing agreements', 'wp-admin-ipresso');
        $registrationSubmenuName = __('Registration', 'wp-admin-ipresso');


        add_menu_page('iPresso Plugin', 'iPresso', 'administrator', 'ipresso', array(), plugins_url('ipresso/images/i_mini2.png'));
        if ($_SESSION['showRegistration'] == true) {
            add_submenu_page('ipresso', $registrationSubmenuName, $registrationSubmenuName, 'administrator', 'ipresso', array($this->registrationForm, 'registrationForm'));
        } elseif ($_SESSION['showRegistration'] == false) {
            add_submenu_page('ipresso', $userDataSubmenuName, $userDataSubmenuName, 'administrator', 'ipresso', array($this->registrationForm, 'authorizedUserBox'));;
            add_submenu_page('ipresso', $commentsSubmenuName, $commentsSubmenuName, 'administrator', 'comments', array($this->commentsForm, 'commentsForm'));
            add_submenu_page('ipresso', $agreementsSubmenuName, $agreementsSubmenuName, 'administrator', 'agreements', array($this->agreementsForm, 'agreementsForm'));
        }
    }

    public function startPlugin()
    {

        register_activation_hook(__FILE__, array($this->installation, 'iPressoInstall'));
        register_activation_hook(__FILE__, array($this->installation, 'iPressoAgreementInstall'));
        register_activation_hook(__FILE__, array($this->installation, 'iPressoUserCommentInstall'));
        register_activation_hook(__FILE__, array($this->installation, 'iPressoUserAgreementInstall'));
        register_activation_hook(__FILE__, array($this->installation, 'iPressoUserLogInstall'));
        register_deactivation_hook(__FILE__, array($this->installation, 'iPressoUninstall'));

        add_action('admin_menu', array($this, 'ipressoPluginMenu'));
        add_action('comment_post', array($this->functions, 'save_comment_check_status'));
        add_action('register_form', array($this->installation, 'myplugin_register_form'));
        add_action('user_register', array($this->functions, 'registerNewUser'));
        add_action('admin_enqueue_scripts', array($this->installation, 'register_plugin_styles'));
        add_action('admin_enqueue_scripts', array($this->installation, 'register_plugin_scripts'));
        add_action('wp_head', array($this->functions, 'showMonitoringCode'));
        add_action('login_enqueue_scripts', array($this->functions, 'userRegisterCode'));
        add_action('init', array($this->installation, 'init_theme_method'));
        add_action('init', array($this->installation, 'loadPluginLanguage'));
        add_action('wp_ajax_my_action', array($this->installation, 'truncateIpresso'));
        add_action('admin_footer', array($this->registrationForm, 'deleteAccountAjax'));

        add_filter('comment_form_default_fields', array($this->installation, 'alter_comment_form_fields'));
        add_filter('comment_form_logged_in', array($this->installation, 'comment_form_logged_in'));

        $this->functions->checkRegistration();

    }
}

$ipresso = new iPresso();
$ipresso->startPlugin();




