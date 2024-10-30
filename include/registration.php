<?php

class iPresso_RegistrationForm
{
    private $postData;
    private $url;
    private $login;
    private $pass;
    private $customerKey;

    private $apiClass;
    private $sqlClass;
    private $function;

    function __construct()
    {
        if ($_POST) {
            $this->postData = $_POST;
            $this->url = (isset($_POST["url"]) && $_POST["url"] ? $_POST["url"] : false);
            $this->login = (isset($_POST["login"]) && $_POST["login"] ? $_POST["login"] : false);
            $this->pass = (isset($_POST["pass"]) && $_POST["pass"] ? $_POST["pass"] : false);
            $this->customerKey = (isset($_POST["key"]) && $_POST["key"] ? $_POST["key"] : false);
        }

        $this->apiClass = new iPresso_API();
        $this->sqlClass = new iPresso_SQL();
        $this->function = new iPresso_Function();
    }

    public function registrationForm()
    {
        $this->showRegistrationForm();

        if (isset($this->postData["rejestr"])) {
            $token = $this->apiClass->getTokenRegistration($this->url, $this->login, $this->pass, $this->customerKey);
            if ($token) {
                $_SESSION['userUrl'] = $this->url;
                $this->showPreloader();
                $result = $this->sqlClass->insertUser($this->url, $this->login, $this->pass, $this->customerKey, $token);

                if ($result === TRUE) {
                    $this->function->updateWordpressDatabase();
                    $_SESSION['showRegistration'] = false;
                  print '<meta http-equiv="refresh" content="1">';
                }
            } else {
                /**
                 * @TODO
                 * brak tłumaczenia
                 */
                print'<script> alert("Podano niepoprawne dane.") </script>';
            }
        }
        return false;
    }

    /**
     * Wyświetlane preloadera dla akcji wykonywanych w panelu admina
     */
    private function showPreloader()
    {
        echo "
                    <center>
                        <div id=\"loading\" style = \"z-index: 2; position:absolute;
                        background: transparent;
                        height: 80px;
                        width: 80px;
                        border: 0px solid black;
                        top:0;
                        left:0;
                        right:0;
                        bottom:0;
                        margin:auto;\">
                            <center>
                            <img style = \"height: 70px;
                            width: 70px;
                            top:0;
                            left:0;
                            right:0;
                            bottom:0;
                            margin:auto;\" src=" . plugins_url('ipresso/images/ajax-loader.gif') . " alt=\"\" />
                            </center>
                        </div>
                    </center>";
    }

    /**
     * Formularz rejestracyjny
     */
    private function showRegistrationForm()
    {
        $registrationUsername = __( 'Username', 'wp-admin-ipresso');
        $registrationUsernamePlaceHolder = __( 'Username', 'wp-admin-ipresso');
        $registrationPassword = __( 'Password', 'wp-admin-ipresso');
        $registrationPasswordPlaceHolder  = __( 'Password', 'wp-admin-ipresso');
        $registrationKey =  __( 'Customer Key', 'wp-admin-ipresso');
        $registrationKeyPlaceHolder = __( 'Customer Key', 'wp-admin-ipresso');
        $registrationButton = __( 'Register now', 'wp-admin-ipresso');

        echo '<section class = "rejestr-section">
                <form action = "" method = "post">
                <center>
                    <div class = fieldset style ="z-index: 1; width:620px; margin : 20px auto 0 auto; background-color: white; border-radius: 2px; box-shadow: 0px 2px 20px rgba(0, 0, 0, 0.5);">
                        <fieldset>
                            <legend style = "padding: 20px; background: linear-gradient(to bottom, #333 50%, #000 70%) repeat scroll 0% 0% transparent; border-top-left-radius: 2px; border-top-right-radius: 2px;">
                            <img src = "' . plugins_url('ipresso/images/ipresso-logo-white.svg') . '" alt="" width="120">
                            </legend>
                                <div class="col-sm-6 col-lg-6">
                                <br>
                                <img src = "' . plugins_url('ipresso/images/i_logo2.png') . '" alt="" width="300" style ="position: positive;">
                                </br>
                            </div>
                            <div class="col-sm-6 col-lg-6">
                                <div class="form-group">
                                    <label class="col-lg-4 control-label">URL</label>
                                    <div class="col-sm-12">
                                    <input type="url" name="url" class="form-control" placeholder = "https://" style="width:250px;" required><br>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4 control-label">' . $registrationUsername . '</label>
                                    <div class="col-sm-12">
                                        <input type="text" name="login" class="form-control" placeholder = "' . $registrationUsernamePlaceHolder . '" style="width:250px;" required><br>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4 control-label">' . $registrationPassword . '</label>
                                    <div class="col-sm-12">
                                        <input type="password" name="pass" class="form-control" placeholder = "' . $registrationPasswordPlaceHolder . '" style="width:250px;" required><br>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-6 control-label">' . $registrationKey . '</label>
                                    <div class="col-sm-12">
                                        <input type="text" name="key" class="form-control" placeholder = "' . $registrationKeyPlaceHolder . '" style="width:250px;" required><br>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-12">
                                        <button type="submit" class="btn-login" name = "rejestr" id="rejestr">' . $registrationButton . '</button>
                                        <br>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </center>
                </form>
            </section>';
    }

    /**
     * DO WYMIANY
     * @TODO
     */
    public function deleteAccountAjax()
    {
        if (get_locale() == "pl_PL") {
            $yes = 'Tak';
            $no = 'Nie';
        } else {
            $yes = 'Tak';
            $no = 'Nie';
        }
        echo '
            <script type="text/javascript">

                jQuery(document).ready(function ($) {
                    $("#delete").click(function () {
                        $("#testowyDiv").dialog({
                            resizable: false,
                            height: 150,
                            modal: true,
                            buttons: {
                                "' . $no . '": function () {
                                    $(this).dialog("close");
                                },
                                "' . $yes . '": function () {

                                    var data = {
                                        "action": "my_action",
                                        "whatever": 1
                                    };

                                    $.post(ajaxurl, data, function (response) {
                                        $("#wpbody").html(response);
                                    });
                                    $(this).dialog("close");
                                }
                            }
                        });
                    });
                });
            </script>';
    }

    /**
     * Wyświetla dane zautoryzowanego użytkownika iPresso w boxie
     */
    public function authorizedUserBox()
    {
        $deleteNotice = __( 'Are you sure to remove plugin from your site iPresso ?', 'wp-admin-ipresso');
        $deleteName = __( 'Delete', 'wp-admin-ipresso');
        $registrationUsername = __( 'Username', 'wp-admin-ipresso');
        $registrationKey = __( 'Customer Key', 'wp-admin-ipresso');

        $userData = $this->function->getUserData();

        if ($userData) {
            echo '
                    <section class = "dane-section">
                        <form action = "" method = "post">
                            <center>
                            <div class = fieldset style ="width:320px; margin : -5px auto 0 auto; background-color: white; border-radius: 2px; box-shadow: 0px 2px 20px rgba(0, 0, 0, 0.5);">
                                <fieldset>
                                    <legend style = "padding: 20px; background: linear-gradient(to bottom, #333 50%, #000 70%) repeat scroll 0% 0% transparent; border-top-left-radius: 2px; border-top-right-radius: 2px;">
                                        <img src = "' . plugins_url('ipresso/images/ipresso-logo-white.svg') . '" alt="" width="120">
                                    </legend>
                                    <div class="form-group">
                                        <label style = "float:center;"><b>URL</b></label>
                                        <div class="col-sm-12"><p>' . $userData["user_url"] . '</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label style="float:center;">' . $registrationUsername . '</label>
                                        <div class="col-sm-12"><p>' . $userData["user_login"] . '</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label style="float:center;">' . $registrationKey . '</label>
                                        <div class="col-sm-12"><p>' . $userData["user_key"] . '</p>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12">
                                        <button type="button" id = "delete" class="btn-login">' . $deleteName . '</button>
                                        <h1> </h1>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            </center>
                        </form>
                    </section>
                    <div id = "testowyDiv" title="' . $deleteName . '" hidden="true">
                        <p style = "margin-left: 0.3cm; margin-right: 0.3cm;"> ' . $deleteNotice . '</p>
                    </div> ';

        }
    }
}


