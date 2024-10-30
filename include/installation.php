<?php

class iPresso_Installation
{
    private $sql;
    private $api;

    function __construct()
    {
        $this->sql = new iPresso_SQL();
        $this->api = new iPresso_API();
    }

    public function iPressoInstall()
    {
        $this->sql->ipresso_install();
    }

    public function iPressoAgreementInstall()
    {
        $this->sql->ipresso_install_agreement();
    }

    public function iPressoUserCommentInstall()
    {
        $this->sql->ipresso_install_user_comment();
    }

    public function iPressoUserAgreementInstall()
    {
        $this->sql->ipresso_install_user_agreement();
    }

    public function iPressoUserLogInstall()
    {
        $this->sql->ipresso_install_user_log();
    }

    public function iPressoUninstall()
    {
        unset($_SESSION);
        $this->sql->ipresso_uninstall();
    }


    public function register_plugin_styles()
    {
        wp_register_style('jQuery', plugins_url('ipresso/css/jquery.css'));
        wp_enqueue_style('jQuery');

        wp_register_style('bootstrap', plugins_url('ipresso/css/bootstrap.css'));
        wp_enqueue_style('bootstrap');

        wp_register_style('style', plugins_url('ipresso/css/style.css'));
        wp_enqueue_style('style');

        wp_register_style('DataTablesCSS', plugins_url('ipresso/css/jquery.dataTables.css'));
        wp_enqueue_style('DataTablesCSS');

        wp_register_style('datepickercss', plugins_url('ipresso/css/datepicker.min.css'));
        wp_enqueue_style('datepickercss');

    }

    public function register_plugin_scripts()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script('json2');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('jquery-ui-dialog');

        wp_register_script('calendarjs', plugins_url('ipresso/js/calendar.js'));
        wp_enqueue_script('calendarjs');

        wp_register_script('DataTables', plugins_url('ipresso/js/jquery.dataTables.min.js'));
        wp_enqueue_script('DataTables');

        wp_register_script('table', plugins_url('ipresso/js/table.js'));
        wp_enqueue_script('table');

        wp_register_script('bootstrap', plugins_url('ipresso/js/bootstrap.min.js'));
        wp_enqueue_script('bootstrap');

    }

    public function add_to_head($output)
    {
        echo $output;
    }

    public function init_theme_method()
    {
        add_thickbox();
    }


    /**
     * Wyświetla zgody marketingowe przy rejestracji nowego użytkownika
     * @return mixed
     */
    public function myplugin_register_form()
    {
        $result = $this->sql->getAgreementsFromDb();
        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {

                if ($row['wp_is_active'] == 1) {
                    $agreementName[] = $row['id_ipresso'];
                    $rejestratonArray[] = array($row['id_ipresso'], $row['wp_content']);
                    echo '<input type="checkbox" name="' . iPresso_Agreements::AGREEMENT_PREFIX . $row['id_ipresso'] . '" value="value" checked=checked"/> ' . $row['wp_content'] . '<br><br>';
                }
            }
            unset($_SESSION['agreementN_rejestr']);
            if (isset($agreementName)) {
                $_SESSION['agreementN_rejestr'] = $agreementName;
            }
            unset($_SESSION['agreement_rejestrArr']);
            if (isset($rejestratonArray)) {
                $_SESSION['agreement_rejestrArr'] = $rejestratonArray;
            }
        }

    }

    /**
     * Formularz do komentowania dla niezalogowanych użytkowników
     * @param $fields
     * @return mixed
     */
    public function alter_comment_form_fields($fields)
    {
        $result = $this->sql->getAgreementsFromDb();
        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {

                if ($row['wp_is_active'] == 1) {
                    $agreementName[] = $row['id_ipresso'];
                    $fields[$row['id_ipresso']] = '<input type="checkbox" name="' . $row['id_ipresso'] . '" value="value"checked=checked"/> ' . $row['wp_content'] . '<br><br>';
                }
            }
            unset($_SESSION['agreementN']);
            if (isset($agreementName)) {
                $_SESSION['agreementN'] = $agreementName;
            }
        }

        if (isset($_SESSION['monitoringCode']) && $_SESSION['monitoringCode'] != "") {
            echo $_SESSION['monitoringCode'];
            unset($_SESSION['monitoringCode']);
        }
        return $fields;
    }

    /**
     * Formularz komentowania dla zalogowanego użytkownika
     * @TODO
     * @param $agree
     * @return string
     */
    function comment_form_logged_in($agree)
    {
        global $user_ID;

        $user_level = $this->sql->getUserMetavalue($user_ID);
        $userAgreement = $this->sql->getAgreementsForWordpressUser($user_ID);
        if ($user_level < 8) {
            $agreements = $this->sql->getAgreementsFromDb();

            if ($userAgreement) {

                if ($agreements->num_rows > 0) {
                    while ($agreement = $agreements->fetch_assoc()) {
                        $AllAgreement[] = array($agreement['id_ipresso'], $agreement['wp_content'], $agreement['wp_is_active']);
                    }
                }

                if (sizeof($AllAgreement) >= sizeof($userAgreement)) {

                    for ($i = 0; $i < sizeof($AllAgreement); $i++) {
                        $updated = false;
                        for ($j = 0; $j < sizeof($userAgreement); $j++) {
                            if ($AllAgreement[$i][0] == $userAgreement[$j][0]) {
                                $updated = true;
                            }
                        }

                        if ($updated == false && $AllAgreement[$i][2] == 1) {
                            $agreementName_login[] = $AllAgreement[$i][0];
                            $marketing_agree[$i][0] = '<input type="checkbox" name="' . $AllAgreement[$i][0] . '" value="value"checked=checked"/> ' . $AllAgreement[$i][1] . '<br><br>';
                            $agree .= $marketing_agree[$i][0];
                        }
                    }
                }
            } else {
                if ($agreements->num_rows > 0) {
                    while ($agreement = $agreements->fetch_assoc()) {
                        if ($agreement['wp_is_active'] == 1) {
                            $agreementName_login[] = $agreement['id_ipresso'];
                            $marketing_agree[$agreement['id_ipresso']] = '<input type="checkbox" name="' . $agreement['id_ipresso'] . '" value="value"checked=checked"/> ' . $agreement['wp_content'] . '<br><br>';
                            $agree .= $marketing_agree[$agreement['id_ipresso']];
                        }
                    }

                }
            }
            if (isset($agreementName_login)) {
                $_SESSION['agreementN_login'] = $agreementName_login;
            }

            return $agree;
        }
        return false;
    }

    public function truncateIpresso()
    {
        $wr = intval($_POST['whatever']);

        if ($wr == 1) {
            $this->sql->truncatePlugin();
            unset($_SESSION);
            echo "
                    <script>
                        location.href='admin.php?page=ipresso';
                    </script>
                     ";
        }
        wp_die();
    }

    public function loadPluginLanguage()
    {
        $path = dirname(dirname(plugin_basename(__FILE__)));
        load_plugin_textdomain('wp-admin-ipresso', FALSE, $path . '/languages/');
    }


}