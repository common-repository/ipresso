<?php

class iPresso_Function
{

    private $sql;
    private $api;
    private $postData;

    function __construct()
    {
        $this->sql = new iPresso_SQL();
        $this->api = new iPresso_API();
        $this->postData = $_POST;
    }

    /**
     * Sprawdza czy w Wordpressie jest aktywny user iPresso
     */
    public function checkRegistration()
    {
        $result = $this->sql->checkIfUserExists();

        if (is_object($result) && ($result->num_rows > 0)) {

            $data = $result->fetch_assoc();

            if (isset($data['count']) && $data['count'] != 0) {
                $_SESSION['showRegistration'] = false;
            } else {
                $_SESSION['showRegistration'] = true;
            }
        }
        return true;
    }

    /**
     * Aktualizuje zgody znajdujące się w bazie Wordpress
     * @return bool
     */
    public function updateWordpressDatabase()
    {
        $array_wordpress = array();
        $array_ipresso = array();

        $result = $this->sql->getAgreementsFromDb();
        if (isset($result->num_rows) && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $array_wordpress[] = array($row["id_ipresso"], $row["wp_content"]);
            }
        }
        $url = $this->getUserURL();
        $agreements = $this->api->getAgreementsFromAPI($url);

        if ($agreements) {
            foreach ((array)$agreements as $k => $v) {
                $array_ipresso[] = array($v->id, $v->descr);
            }
        }
        /**
         * Pobieranie i aktualizacja zgód marketingowych
         * w zależności od liczby w bazie
         */
        if (!$array_wordpress) {
            /**
             * Gdy nie ma zgód, dodajemy wszystkie z API do DB
             */
            if ($agreements) {
                $this->insertAgreementsIntoDb($agreements);
            }
        } elseif (sizeof($array_ipresso) >= sizeof($array_wordpress)) {
            /**
             * Gdy więcej zgód jest w iPresso niż w DB
             */
            $this->sql->updateAgreementsAndInsertNewFromIpresso($array_ipresso, $array_wordpress);
        } elseif (sizeof($array_wordpress) >= sizeof($array_ipresso)) {
            /**
             * Gdy więcej zgód jest w DB niż w iPresso
             */
            $this->sql->updateAgreementsAndDeleteOld($array_wordpress, $array_ipresso);
        } else {
            return false;
        }
    }

    private function insertAgreementsIntoDb($agreement)
    {
        $this->sql->insertAgreementsIntoDb($agreement);
    }

    /**
     * Zwraca URL do iPresso
     * @return bool
     */
    public function getUserURL()
    {

        if (isset($_SESSION['userUrl']) && $_SESSION['userUrl']) {
            return $_SESSION['userUrl'];
        } else {
            $userData = $this->getUserData();
            if ($userData['user_url']) {
                $_SESSION['userUrl'] = $userData['user_url'];
                return $userData['user_url'];
            }
            return false;
        }
    }

    /**
     * Pobiera z bazy token iPresso
     * @return bool
     */
    public function getUserToken()
    {
        return $this->sql->getUserToken();
    }

    /**
     * Pobiera z bazy dane dot Uzytkownika iPresso
     * @return array
     */
    public function getUserData()
    {
        return $this->sql->getUserData();
    }

    /**
     * Wyświetla kod monitorujący
     * pobierany przez API
     * @return bool
     */
    public function showMonitoringCode()
    {
        $userData = $this->getUserData();
        if ($userData['monitoringCode']) {
            echo $userData['monitoringCode'];
        } elseif ($userData['user_url']) {
            $code = $this->api->getMonitoringCode($userData['user_url']);
            $this->sql->updateMonitoringCode($userData['id_account'], $code);
            echo $code;
            return true;
        }
        return false;
    }

    /**
     * Tworzy tabelkę z komentarzami
     * @param $result
     * @param $header
     * @param $alert
     */
    public function commentsTableDraw($result, $header, $alert)
    {

        $agreements = $this->sql->getAgreementsFromDb();
        $agreementsArr = array();

        if ($agreements->num_rows > 0) {
            while ($rows = $agreements->fetch_assoc()) {
                $agreementsArr[$rows["id_ipresso"]] = $rows["wp_content"];
            }
        }

        if ($result->num_rows > 0) {
            $this->commentsTableHeaders($header);

            while ($row = $result->fetch_assoc()) {

                echo "<tr>";
                echo "<td>" . $row["comment_author"] . "</td>";
                echo "<td>" . $row["comment_author_email"] . "</td>";
                echo "<td>" . $row["comment_date"] . "</td>";
                echo "<td>" . $row["post_title"] . "</td>";
                echo "<td>" . $row["Post"] . "...</td>";
                echo "<td>" . $row["comment_content"] . "</td>";

                $userAgreement = $this->sql->getAgreementsForUser($row["id_ipresso_user"]);

                echo '<td>';

                if (count($agreementsArr)) {
                    foreach ($agreementsArr as $id => $value) {
                        if ($userAgreement && in_array($id, array_keys($userAgreement))) {
                            echo "<span class=\"glyphicon glyphicon-ok\" data-toggle=\"tooltip\" title=\"Zgoda marketingowa\" aria-hidden=\"true\" style=\"color:green;  font-size:15px;\" ></span>";
                            echo " " . $value . "<br>";
                        }
                    }
                }
                echo ' </td></tr>';
            }
            echo "</tbody></table>";
        } else {
            echo $alert;
        }
    }

    /**
     * Wyświetla nagłówek do tablicy z komentarzami
     * @param $header
     */
    private function commentsTableHeaders($header)
    {
        $authorName = __( 'Author', 'wp-admin-ipresso');
        $addDateName = __( 'Add date', 'wp-admin-ipresso');
        $postContentName = __( 'Post content', 'wp-admin-ipresso');
        $commentContentName = __( 'Comment content', 'wp-admin-ipresso');
        $marketingAgrementName = __( 'Marketing agreement', 'wp-admin-ipresso');

        echo '<p style="margin-left:0.5cm;">' . $header . '</p>';
        if (get_locale() == "pl_PL") {
            echo '<table id="table_id1" class="table table-hover">';
        } else {
            echo '<table id="table_id2" class="table table-hover">';
        }
        echo '<thead>
                            <tr>
                                <th><b>' . $authorName . '</b></th>
                                <th><b>Email</b></th>
                                <th><b>' . $addDateName . '</b></th>
                                <th><b>Post</b></th>
                                <th><b>' . $postContentName . '</b></th>
                                <th><b>' . $commentContentName . '</b></th>
                                <th><b>' . $marketingAgrementName . '</b></th>
                            </tr>
                        </thead>
                    <tbody>';
    }

    /**
     * Rejestruje nowego usera do DB i iPresso
     * @param int $user_id
     * @return bool
     */
    public function registerNewUser($user_id)
    {
        $agreementArr = array();
        if (isset($_SESSION['agreementN_rejestr']) && $_SESSION['agreementN_rejestr']) {
            $agreementSession = $_SESSION['agreementN_rejestr'];
            foreach ($agreementSession as $id) {
                /**
                 * Przygotowuje tablice zgód dla nowego usera
                 */
                if (isset($_POST[iPresso_Agreements::AGREEMENT_PREFIX . $id])) {
                    $agreementArr[$id] = 1;
                }
            }
        }

        $urlIpresso = $this->getUserURL();
        /**
         * Tylko gdy jest URL, w przeciwnym wypadku false
         */
        if ($urlIpresso) {
            $contact = $this->api->addNewContact($urlIpresso, $_POST['user_email'], $agreementArr);
            if ($contact) {

                $this->sql->insertNewIpressoUser($contact->id, $user_id, $agreementArr);
                $this->api->addWordpressActivity($urlIpresso, $contact->id);

                if ($contact->code == iPresso_API::API_EXISTS_CODE && $agreementArr) {
                    $this->api->updateAgreement($urlIpresso, $contact->id, $agreementArr);
                }

                $_SESSION['monitoringCode'] = $contact->monitoringCode;
            }
            return true;
        }
        return false;
    }

    public function getUserMetavalue($userId)
    {
        return $this->sql->getUserMetavalue($userId);
    }

    /**
     * Zapisuje komentarz do bazy i wykonuje akcje na kontakcie
     * @param $comment_id
     * @return bool
     */
    public function save_comment_check_status($comment_id)
    {
        global $user_ID;
        global $user_email;
        global $user_url;
        $user_level = $this->sql->getUserMetavalue($user_ID);
        $urlIpresso = $this->getUserURL();

        $agreementArr = array();
        $agreementArr2 = array();

        if (!$urlIpresso) {
            return false;
        }

        /**
         * Dodawanie komentarza dla niezalogowanego użytkownika
         */
        if ($user_ID == 0) {

            if (isset($_SESSION['agreementN']) && $_SESSION['agreementN']) {
                foreach ($_SESSION['agreementN'] as $an) {
                    if (isset($_POST[$an])) {
                        $agreementArr[$an] = 1;
                        $agreementArr2[] = $an;

                    }
                }
            }

            /**
             * Tylko gdy jest URL, w przeciwnym wypadku false
             */
            $contact = $this->api->addNewContact($urlIpresso, $this->postData['email'], $agreementArr, $this->postData['url']);
            if ($contact) {

                $this->sql->insertNewIpressoUser($contact->id, $user_ID, $agreementArr, $comment_id);

                // DODANIE AKTYWNOŚCI KOMENTARZA
                $this->api->addWordpressActivity($urlIpresso, $contact->id, $comment_id);

                if ($contact->code == iPresso_API::API_EXISTS_CODE && $agreementArr) {
                    $this->api->updateAgreement($urlIpresso, $contact->id, $agreementArr);
                }
                $_SESSION['monitoringCode'] = $contact->monitoringCode;
            }
        } else {
            /**
             * Dla zalogowanego
             */
            if (isset($_SESSION['agreementN_login']) && $_SESSION['agreementN_login']) {
                foreach ($_SESSION['agreementN_login'] as $a) {
                    if (isset($_POST[$a])) {
                        $agreementArr[$a] = 1;
                        $agreementArr2[] = $a;
                    }
                }
            }

            $ipressoId = $this->sql->getIpressoIdByUserId($user_ID);

            if (empty($ipressoId) && $user_level < 8) {
                $contact = $this->api->addNewContact($urlIpresso, $user_email, $agreementArr, $user_url);
                if ($contact) {
                    $this->sql->insertNewIpressoUser($contact->id, $user_ID, $agreementArr, $comment_id);

                    // DODANIE AKTYWNOŚCI KOMENTARZA
                    $this->api->addWordpressActivity($urlIpresso, $contact->id, $comment_id);

                    if ($contact->code == iPresso_API::API_EXISTS_CODE && $agreementArr) {
                        $this->api->updateAgreement($urlIpresso, $contact->id, $agreementArr);
                    }
                    $_SESSION['monitoringCode'] = $contact->monitoringCode;
                }
            } else {

                $this->sql->insertCommentForContact($ipressoId, $comment_id);
                // DODANIE AKTYWNOŚCI KOMENTARZA
                $this->api->addWordpressActivity($urlIpresso, $ipressoId, $comment_id);
                if (isset($agreementArr)) {
                    $agreement = $this->api->updateAgreement($urlIpresso, $ipressoId, $agreementArr);
                    if ($agreement) {
                        $result3 = $this->sql->getAgreementsForUser($ipressoId);
                        foreach($result3 as $r)
                        {
                            $database_array[] = $r["id_agreement"];
                        }
                        for ($i = 0; $i < sizeof($agreementArr2); $i++) {
                            $exist = false;
                            for ($j = 0; $j < sizeof($database_array); $j++) {
                                if ($agreementArr2[$i] == $database_array[$j]) {
                                    $exist = true;
                                }
                            }
                            if ($exist == false) {
                                $this->sql->insertAgreementForUser($ipressoId, $agreementArr2[$i]);
                            }
                        }
                        return true;
                    }
                }
            }

        }
        return false;
    }

    /**
     * Wyświetla kod monitorujący iPresso
     * nowo zarejestrowanemu userowi WP
     */
    public function userRegisterCode()
    {
        if (isset($_SESSION['monitoringCode']) && $_SESSION['monitoringCode']) {
        echo $_SESSION['monitoringCode'];
            unset($_SESSION['monitoringCode']);
        }
    }
}

