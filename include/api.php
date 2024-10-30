<?php

class iPresso_API
{

    const API_VER = '/2';
    const API_URL = '/api';
    const API_METHOD_GET = 'GET';
    const API_METHOD_POST = 'POST';
    const API_METHOD_DELETE = 'DELETE';

    const API_WORDPRESS_ORIGIN_KEY = 'wordpress';
    const API_WORDPRESS_ACTIVITY_REGISTRATION_KEY = 'wordpress_registration';
    const API_WORDPRESS_ACTIVITY_COMMENT_KEY = 'wordpress_comment';

    const API_EXISTS_CODE = 303;

    private $sql;
    private $curlHandler;

    function __construct()
    {
        $this->sql = new iPresso_SQL();
        $this->iteration = 0;
    }

    public function getTokenRegistration($url, $login, $pass, $key)
    {

        $url2 = $url . self::API_URL . self::API_VER . '/auth/' . $key;

        $usrpass = $login . ':' . $pass;
        $this->curlInit();

        curl_setopt($this->curlHandler, CURLOPT_URL, $url2);
        curl_setopt($this->curlHandler, CURLOPT_USERPWD, $usrpass);

        $server_output = curl_exec($this->curlHandler);
        $server_info = curl_getinfo($this->curlHandler);

        curl_close($this->curlHandler);

        $data = json_decode($server_output);
        if (isset($server_info['http_code']) && $server_info['http_code'] == 200) {
            return $data->data;
        }
        return false;
    }

    /**
     * Wywołanie cURL dla iPresso API
     * @todo CHECK
     * @param string $url
     * @param string $method
     * @param array $postFields
     * @return bool|mixed
     */
    private function cURL($url, $method = 'GET', $postFields = array())
    {
        $this->curlInit();
        $token = array('IPRESSO_TOKEN:' . $this->sql->getUserToken());

        if ($method == 'POST') {
            curl_setopt($this->curlHandler, CURLOPT_POST, 1);
            curl_setopt($this->curlHandler, CURLOPT_POSTFIELDS, http_build_query($postFields));
        }

        curl_setopt($this->curlHandler, CURLOPT_HTTPHEADER, $token);
        curl_setopt($this->curlHandler, CURLOPT_URL, $url);

        $server_output = curl_exec($this->curlHandler);
        $server_info = curl_getinfo($this->curlHandler);
        curl_close($this->curlHandler);
        if (isset($server_info['http_code']) && in_array($server_info['http_code'], array(201, 200, 302, 303))) {
            $data = json_decode($server_output);
            return $data->data;
        } elseif (isset($server_info['http_code']) && $server_info['http_code'] == 403) {
            if ($this->refreshToken() && $this->iteration < 1) {
                $this->iteration++;
                return $this->cURL($url, $method, $postFields);
            } else {
                return false;
            }
        } elseif (isset($server_info['http_code']) && $server_info['http_code'] == 409) {
            $data = json_decode($server_output);
            return $data->data;
        }
        return false;
    }

    /**
     * Odświeża token w iPresso API
     * @return bool
     */
    private function refreshToken()
    {
        $userData = $this->sql->getUserData();
        if ($userData) {

            $url = $userData["user_url"] . self::API_URL . self::API_VER . '/auth/' . $userData["user_key"];
            $userPass = $userData["user_login"] . ':' . $userData["user_pass"];

            $this->curlInit();
            curl_setopt($this->curlHandler, CURLOPT_URL, $url);
            curl_setopt($this->curlHandler, CURLOPT_USERPWD, $userPass);
            $server_output = curl_exec($this->curlHandler);
            $server_info = curl_getinfo($this->curlHandler);

            curl_close($this->curlHandler);

            if (isset($server_info['http_code']) && $server_info['http_code'] == 200) {
                $data = json_decode($server_output);
                $this->sql->insertRefreshTokenToDB($data->data);
                return true;
            }
        }
        return false;
    }

    private function curlInit()
    {
        $this->curlHandler = curl_init();
        curl_setopt($this->curlHandler, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($this->curlHandler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlHandler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curlHandler, CURLOPT_SSL_VERIFYHOST, false);
    }

    /**
     * Zwraca zgody marketingowe z iPresso
     * @param string $ipressoUrl
     * @return bool|mixed
     */
    public function getAgreementsFromAPI($ipressoUrl)
    {
        $url = $ipressoUrl . self::API_URL . self::API_VER . '/agreement/';

        $agreement = $this->cURL($url, self::API_METHOD_GET);
        if (isset($agreement->agreement) && $agreement->agreement) {
            return $agreement->agreement;
        }
        return false;
    }

    /**
     * Pobiera przez API2 kod monitorujacy dla danej strony
     * @param $url
     * @return bool|string
     */
    public function getMonitoringCode($url)
    {
        $curl = $url . self::API_URL . self::API_VER . '/www/';
        $postFields['www']['url'] = "http://" . $_SERVER["SERVER_NAME"];

        $result = $this->cURL($curl, self::API_METHOD_POST, $postFields);
        if (isset($result->www->code) && $result->www->code) {
            return $result->www->code;
        }
        return false;
    }

    /**
     * Dodaje nowy kontakt do iPresso
     * @param string $url
     * @param string $email
     * @param array $agreementArr
     * @return bool|stdClass
     */
    public function addNewContact($url, $email, $agreementArr, $www = false)
    {
        $curl = $url . self::API_URL . self::API_VER . '/contact/';

        $postFields = array(
            'email' => $email,
            'origin' => self::API_WORDPRESS_ORIGIN_KEY,
            'agreement' => $agreementArr,
        );

        if ($www) {
            $postFields['www'] = $www;
        }

        $contact['contact'][] = $postFields;

        $result = $this->cURL($curl, self::API_METHOD_POST, $contact);
        if (isset($result->contact) && $result->contact) {
            $return = new stdClass();
            foreach ($result->contact as $contact) {
                $return->id = $contact->id;
                $return->code = $contact->code;
                $return->monitoringCode = $contact->monitoringCode;
            }

            return $return;
        }
        return false;
    }

    /**
     * Dodaje aktywności rejestracji w Wordpress dla kontaktu o idcontact
     * @param $url
     * @param $idContact
     * @param bool $comment
     * @return bool|mixed
     */
    public function addWordpressActivity($url, $idContact, $comment = false)
    {
        $curl = $url . self::API_URL . self::API_VER . '/contact/' . $idContact . '/activity/';

        $data = array();

        $data['parameter'] = array(
            'www' => $_SERVER["SERVER_NAME"]
        );

        if ($comment) {
            $data['key'] = self::API_WORDPRESS_ACTIVITY_COMMENT_KEY;
            $content = '';
            $result = $this->sql->getCommentContent($comment);
            if (isset($result->num_rows) && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $content = $row['comment_content'];
                }
            }
            $data['parameter']['comment'] = $content;
        } else {
            $data['key'] = self::API_WORDPRESS_ACTIVITY_REGISTRATION_KEY;
        }

        $data['parameter']['www'] = $_SERVER["SERVER_NAME"];

        $activity['activity'][] = $data;

        return $this->cURL($curl, self::API_METHOD_POST, $activity);
    }

    /**
     * Przypisuje zgody dla danego kontaktu
     * @param $url
     * @param $idContact
     * @param $agreementArr
     * @return bool|mixed
     */
    public function updateAgreement($url, $idContact, $agreementArr)
    {
        $curl = $url . self::API_URL . self::API_VER . '/contact/' . $idContact . '/agreement/';
        $agreement['agreement'] = $agreementArr;
        return $this->cURL($curl, self::API_METHOD_POST, $agreement);
    }

}