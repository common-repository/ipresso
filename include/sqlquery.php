<?php

class iPresso_SQL
{

    const IPRESSO_PREFIX = 'ipresso';
    const IPRESSO_DB_VERSION = '1.0';

    public $prefix;
    public $wpdb;
    private $tableName;

    function __construct()
    {

        global $wpdb;
        $this->prefix = $wpdb->prefix;
        $this->wpdb = $wpdb;
        $this->tableName = $this->prefix . self::IPRESSO_PREFIX;

    }

    public function ipresso_install()
    {

        if ($this->wpdb->get_var("SHOW TABLES LIKE '" . $this->tableName . "'") != $this->tableName) {
            $query = "
                        CREATE TABLE " . $this->tableName . "(
                        id_ipresso_account int(255) NOT NULL AUTO_INCREMENT,
                        ipresso_url varchar(255) NOT NULL,
                        ipresso_login varchar(100) NOT NULL,
                        ipresso_password varchar(100) NOT NULL,
                        ipresso_customerkey varchar(255) NOT NULL,
                        ipresso_token varchar(255),
                        ipresso_monitoringCode TEXT NULL DEFAULT NULL,
                        create_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY  (id_ipresso_account)
                        )";

            $this->wpdb->query($query);

            $alter = "ALTER TABLE " . $this->tableName . " CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
            $this->wpdb->query($alter);

            add_option("ipresso_db_version", self::IPRESSO_DB_VERSION);
            return true;
        }
        return false;
    }

    /**
     * Usuwa tabelki iPresso, podczas usuwania pluginu
     */
    public function ipresso_uninstall()
    {
        $query = 'DROP TABLE ' . $this->tableName;
        $this->wpdb->query($query);

        $query2 = 'DROP TABLE ' . $this->tableName . '_user_agreement';
        $this->wpdb->query($query2);

        $query3 = 'DROP TABLE ' . $this->tableName . '_agreement';
        $this->wpdb->query($query3);

        $query4 = 'DROP TABLE ' . $this->tableName . '_user_comment';
        $this->wpdb->query($query4);

        $query5 = 'DROP TABLE ' . $this->tableName . '_user_log';
        $this->wpdb->query($query5);
    }

    public function ipresso_install_agreement()
    {
        $ipresso_tablename = $this->tableName . '_agreement';

        if ($this->wpdb->get_var("SHOW TABLES LIKE '" . $ipresso_tablename . "'") != $ipresso_tablename) {
            $query = "CREATE TABLE " . $ipresso_tablename . "(
                        id_ipresso BIGINT( 20 ) NOT NULL,
                        wp_content VARCHAR(255) NOT NULL,
                        wp_is_active TINYINT(1) NOT NULL,
                        PRIMARY KEY  (id_ipresso))";

            $this->wpdb->query($query);

            $alter = "ALTER TABLE " . $ipresso_tablename . " CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
            $this->wpdb->query($alter);
        }
    }

    public function ipresso_install_user_comment()
    {
        $ipresso_tablename = $this->tableName . '_user_comment';

        if ($this->wpdb->get_var("SHOW TABLES LIKE '" . $ipresso_tablename . "'") != $ipresso_tablename) {
            $query = "CREATE TABLE " . $ipresso_tablename . "(
                         id_wp_comment BIGINT( 20 ) UNSIGNED,
                         id_ipresso_user BIGINT( 20 ) NOT NULL,
                         FOREIGN KEY ( id_wp_comment ) REFERENCES  " . $this->prefix . "comments( comment_ID )
                             ON DELETE NO ACTION
                             ON UPDATE NO ACTION)";
            $this->wpdb->query($query);
        }
    }

    public function ipresso_install_user_agreement()
    {
        $ipresso_tablename = $this->tableName . '_user_agreement';

        if ($this->wpdb->get_var("SHOW TABLES LIKE '" . $ipresso_tablename . "'") != $ipresso_tablename) {
            $query = "CREATE TABLE " . $ipresso_tablename . "(
                         id_ipresso_user BIGINT( 20 ) NOT NULL,
                         id_agreement BIGINT (20) NOT NULL)";
            $this->wpdb->query($query);

            $queryAlter = "ALTER TABLE " . $ipresso_tablename . " ADD UNIQUE unique_index(id_ipresso_user,id_agreement)";
            $this->wpdb->query($queryAlter);
        }
    }

    public function ipresso_install_user_log()
    {
        $ipresso_tablename = $this->tableName . '_user_log';
        if ($this->wpdb->get_var("SHOW TABLES LIKE '" . $ipresso_tablename . "'") != $ipresso_tablename) {
            $query = "CREATE TABLE " . $ipresso_tablename . "(
                        id_wp_user BIGINT( 20 ) UNSIGNED,
                        id_ipresso_user BIGINT( 20 ) NOT NULL,
                        FOREIGN KEY ( id_wp_user ) REFERENCES  " . $this->prefix . "users( ID )
                            ON DELETE NO ACTION
                            ON UPDATE NO ACTION)";

            $this->wpdb->query($query);
        }
    }

    /**
     * Główna funkcja do wykonywania zapytań SQL
     * @param string $query
     * @return bool|mysqli_result
     */
    public function sqlQuery($query)
    {
        $conn = new mysqli($this->wpdb->dbhost, $this->wpdb->dbuser, $this->wpdb->dbpassword, $this->wpdb->dbname);
        $conn->query('SET NAMES utf8');
        $conn->query('SET CHARACTER_SET utf8_unicode_ci');

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $result = $conn->query($query);
        return $result;

    }

    /**
     * Dodaje nowego użytkownika iPresso do Wordpress DB
     * @param string $url
     * @param string $login
     * @param string $pass
     * @param string $customerKey
     * @param string $token
     * @return bool|mysqli_result
     */
    public function insertUser($url, $login, $pass, $customerKey, $token)
    {
        return $this->sqlQuery('INSERT INTO ' . $this->prefix . 'ipresso (ipresso_url, ipresso_login, ipresso_password, ipresso_customerkey, ipresso_token)
               VALUES ("' . $url . '", "' . $login . '", "' . base64_encode($pass) . '", "' . base64_encode($customerKey) . '", "' . $token . '")');
    }

    /**
     * Sprwadza czy w Wordpress istnieje użytkownik iPresso
     * @return bool|mysqli_result
     */
    public function checkIfUserExists()
    {
        return $this->sqlQuery('
                            SELECT
                                COUNT(*) AS count
                            FROM ' . $this->prefix . 'ipresso'
        );
    }

    /**
     * Zwraca wszystkie zapisane w bazie zgody iPresso
     * @return bool|mysqli_result
     */
    public function getAgreementsFromDb()
    {
        return $this->sqlQuery('SELECT
                                id_ipresso,
                                wp_content,
                                wp_is_active
                            FROM ' . $this->prefix . 'ipresso_agreement');
    }

    /**
     * Aktualizacja wybranych zgód w DB
     * @param int $id_agreement
     * @param int $active
     * @return bool|mysqli_result
     */
    public function updateAgreementInDb($id_agreement, $active = 0)
    {
        return $this->sqlQuery('UPDATE ' . $this->prefix . 'ipresso_agreement
                                SET wp_is_active = ' . $active . '
                                WHERE id_ipresso = ' . $id_agreement);
    }

    /**
     * Zwraca zgody przypisane dla danego usera
     * @param $idUser
     * @return bool|mixed
     */
    public function getAgreementsForUser($idUser)
    {
        $sqlAgreement = "SELECT wp_content, id_agreement, a.id_ipresso_user
                        FROM " . $this->prefix . "ipresso_user_agreement AS a
                        LEFT JOIN " . $this->prefix . "ipresso_agreement aa ON a.id_agreement = aa.id_ipresso
                        WHERE a.id_ipresso_user ='" . $idUser . "'";
        $ret = $this->sqlQuery($sqlAgreement);
        if ($ret->num_rows > 0) {
            while ($row = $ret->fetch_assoc()) {
                $userAgreement[$row['id_agreement']] = $row;
            }
            return $userAgreement;
        }

        return false;
    }

    /**
     * Zwraca zgody przypisane dla danego użytkownika WP
     * @param int $user_ID
     * @return array|bool
     */
    public function getAgreementsForWordpressUser($user_ID)
    {
        $result = $this->sqlQuery("SELECT id_ipresso,wp_is_active,wp_content
                                    FROM " . $this->prefix . "ipresso_agreement ia
                                    INNER JOIN " . $this->prefix . "ipresso_user_agreement iua ON ia.id_ipresso = iua.id_agreement
                                    INNER JOIN " . $this->prefix . "ipresso_user_log iul ON iua.id_ipresso_user = iul.id_ipresso_user
                                    WHERE iul.id_wp_user = $user_ID");
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $userAgreement[] = array($row['id_ipresso']);
            }
            return $userAgreement;
        }
        return false;
    }

    /**
     * Zwraca dane dotyczace uzytkownika iPresso
     * @return bool|mysqli_result
     */
    public function getUserData()
    {
        $result = $this->sqlQuery("SELECT
                                          ipresso_url,
                                          ipresso_login,
                                          ipresso_password,
                                          ipresso_customerkey,
                                          ipresso_monitoringCode,
                                          id_ipresso_account
                                    FROM " . $this->prefix . "ipresso ");

        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {

                $userData = array(
                    "user_url" => $row["ipresso_url"],
                    "user_login" => $row["ipresso_login"],
                    "user_pass" => base64_decode($row["ipresso_password"]),
                    "user_key" => base64_decode($row["ipresso_customerkey"]),
                    "monitoringCode" => $row["ipresso_monitoringCode"],
                    "id_account" => $row["id_ipresso_account"],
                );
            }
        }
        if (isset($userData)) {
            return $userData;
        }
        return false;
    }

    /**
     * Zwraca z bazy token iPresso
     * @return bool|mysqli_result
     */
    public function getUserToken()
    {

        $resultDb = $this->sqlQuery('SELECT
                                          ipresso_token
                                    FROM ' . $this->prefix . 'ipresso ');
        if ($resultDb->num_rows > 0) {
            $result = $resultDb->fetch_assoc();

            if (isset($result['ipresso_token']) && $result['ipresso_token']) {
                return $result['ipresso_token'];
            }
        }
        return false;
    }

    /**
     * Zapisuje kod monitorujacy
     * @param int $id_account
     * @param string $code
     * @return bool|mysqli_result
     */
    public function updateMonitoringCode($id_account, $code)
    {
        $conn = new mysqli($this->wpdb->dbhost, $this->wpdb->dbuser, $this->wpdb->dbpassword, $this->wpdb->dbname);
        return $this->sqlQuery('UPDATE ' . $this->prefix . 'ipresso SET ipresso_monitoringCode = "' . mysqli_real_escape_string($conn, $code) . '" WHERE  id_ipresso_account = ' . $id_account);

    }

    /**
     * Zapisanie zgód z API do DB
     * @param $agreementObj
     */
    public function insertAgreementsIntoDb($agreementObj)
    {
        if ($agreementObj) {
            foreach ((array)$agreementObj as $id => $val) {
                $this->sqlQuery('INSERT INTO ' . $this->prefix . 'ipresso_agreement(id_ipresso, wp_content, wp_is_active) VALUES (' . $id . ',"' . $val->name . '",0)');
            }
        }

    }

    /**
     * Aktualizacja zgód z API do DB
     * Dodawanie brakujacych
     * @param $array_ipresso
     * @param $array_wordpress
     */
    public function updateAgreementsAndInsertNewFromIpresso($array_ipresso, $array_wordpress)
    {
        for ($i = 0; $i < sizeof($array_ipresso); $i++) {
            $updated = false;
            for ($j = 0; $j < sizeof($array_wordpress); $j++) {
                // if found the same record
                if ($array_ipresso[$i][0] == $array_wordpress[$j][0]) {
                    $sql = 'UPDATE ' . $this->prefix . 'ipresso_agreement SET wp_content = "' . $array_ipresso[$i][1] . '" WHERE  id_ipresso = ' . $array_ipresso[$i][0];
                    $updated = true;
                }
            }

            if ($updated == false) {
                $sql = 'INSERT INTO ' . $this->prefix . 'ipresso_agreement(id_ipresso, wp_content, wp_is_active) VALUES (' . $array_ipresso[$i][0] . ', "' . $array_ipresso[$i][1] . '",0)';
            }
            $this->sqlQuery($sql);
        }
    }

    /**
     * Aktualizacja zgód z API do DB
     * Usuwanie starych, nieistniejących już w iPresso
     * @param $array_wordpress
     * @param $array_ipresso
     */
    public function updateAgreementsAndDeleteOld($array_wordpress, $array_ipresso)
    {
        for ($i = 0; $i < sizeof($array_wordpress); $i++) {
            $update = false;
            for ($j = 0; $j < sizeof($array_ipresso); $j++) {
                if ($array_wordpress[$i][0] == $array_ipresso[$j][0]) {
                    $sql = 'UPDATE ' . $this->prefix . 'ipresso_agreement SET wp_content = "' . $array_ipresso[$j][1] . '"" WHERE  id_ipresso = ' . $array_ipresso[$j][0];
                    $update = true;
                }
            }
            if ($update == false) {
                $sql = 'DELETE FROM ' . $this->prefix . 'ipresso_agreement WHERE id_ipresso = ' . $array_wordpress[$i][0];
            }
            $this->sqlQuery($sql);
        }
    }

    /**
     * Zwraca liczbe komentarzy
     * @return bool
     */
    public function countComments()
    {
        $AllCommentsSQL = "SELECT COUNT(*) as count
                FROM " . $this->prefix . "comments wpc
                INNER JOIN " . $this->prefix . "ipresso_user_comment wpuc ON wpc.comment_ID = wpuc.id_wp_comment
                INNER JOIN " . $this->prefix . "posts p ON wpc.comment_post_ID = p.ID
                ORDER BY wpc.comment_content ASC";

        $resultAll = $this->sqlQuery($AllCommentsSQL);
        if ($resultAll->num_rows > 0) {
            $data = $resultAll->fetch_assoc();
            return $data['count'];
        }
        return false;
    }

    /**
     * Pobranie treści komentarza
     * @param $idComment
     * @return bool|mysqli_result
     */
    public function getCommentContent($idComment)
    {
        $sql = 'SELECT
                    wpc.comment_content
                FROM ' . $this->prefix . 'comments wpc
                WHERE wpc.comment_ID =' . $idComment;
        return $this->sqlQuery($sql);
    }

    /**
     * Pobierz komentarze z bazy
     * @param bool $author
     * @param bool $dateFrom
     * @param bool $dateTo
     * @return bool|mysqli_result
     */
    public function getComments($author = false, $dateFrom = false, $dateTo = false)
    {
        $where = '';

        if ($author) {
            $where .= ' AND wpc.comment_author like "%' . $author . '%"';
        }
        if ($dateFrom) {
            $where .= ' AND DATE( wpc.comment_date ) >= "' . $dateFrom . '"';
        }
        if ($dateTo) {
            $where .= ' AND DATE( wpc.comment_date ) <= "' . $dateTo . '"';
        }

        $sql = 'SELECT
                    comment_author,
                    comment_author_email,
                    comment_date,
                    post_title,
                    LEFT( post_content, 30 ) AS Post,
                    comment_content,
                    id_ipresso_user
                FROM ' . $this->prefix . 'comments wpc
                    INNER JOIN ' . $this->prefix . 'ipresso_user_comment wpuc ON wpc.comment_ID = wpuc.id_wp_comment
                    INNER JOIN ' . $this->prefix . 'posts p ON wpc.comment_post_ID = p.ID
                WHERE 1 ' . $where . '
                ORDER BY wpc.comment_date DESC';
        return $this->sqlQuery($sql);
    }

    /**
     * Czyści wszystkie tabele pluginu w momencie usuwania danych autoryzacyjnych
     * @return bool
     */
    public function truncatePlugin()
    {
        $this->sqlQuery('TRUNCATE ' . $this->tableName . '_user_agreement');
        $this->sqlQuery('TRUNCATE ' . $this->tableName . '_user_comment');
        $this->sqlQuery('TRUNCATE ' . $this->tableName . '_user_log');
        $this->sqlQuery('TRUNCATE ' . $this->tableName . '_user');
        $this->sqlQuery('TRUNCATE ' . $this->tableName . '_agreement');
        $this->sqlQuery('TRUNCATE ' . $this->tableName);
        return true;
    }

    /**
     * Dodaje nowego usera, jego ipresso_id i zgody
     * @param int $ipressoId
     * @param int $userId
     * @param array $agreementArr
     */
    public function insertNewIpressoUser($ipressoId, $userId, $agreementArr, $commentId = false)
    {
        if ($userId != 0) {
            $this->sqlQuery('INSERT INTO ' . $this->prefix . 'ipresso_user_log VALUES (' . $userId . ',' . $ipressoId . ')');
        }

        if ($commentId) {
            $this->sqlQuery('INSERT INTO ' . $this->prefix . 'ipresso_user_comment VALUES (' . $commentId . ',' . $ipressoId . ')');
        }

        if (isset($agreementArr)) {
            foreach ($agreementArr as $key => $val) {
                $this->sqlQuery('INSERT INTO ' . $this->prefix . 'ipresso_user_agreement VALUES (' . $ipressoId . ',' . $key . ')');
            }
        }

    }

    /**
     * Dodaje komentarz do kontaktu
     * @param $ipressoId
     * @param $comment_id
     */
    public function insertCommentForContact($ipressoId, $comment_id)
    {
        return $this->sqlQuery('INSERT INTO ' . $this->prefix . 'ipresso_user_comment VALUES (' . $comment_id . ',' . $ipressoId . ')');
    }

    /**
     * Sprawdza poziom usera w WP
     * @param int $user_ID
     * @return int|bool
     */
    public function getUserMetavalue($user_ID)
    {
        $result = $this->sqlQuery('SELECT meta_value FROM ' . $this->prefix . 'usermeta
                                WHERE meta_key="' . $this->prefix . 'user_level"
                                AND user_id = ' . $user_ID);

        $data = $result->fetch_assoc();
        if ($data['meta_value']) {
            return $data['meta_value'];
        }
        return false;
    }

    /**
     * Zwraca iPresso ID na podstawie WP User ID
     * @param int $userId
     * @return int|bool
     */
    public function getIpressoIdByUserId($userId)
    {
        $result = $this->sqlQuery('SELECT id_ipresso_user
                                   FROM ' . $this->prefix . 'ipresso_user_log
                                   WHERE id_wp_user =' . $userId . '
                                   LIMIT 1');
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['id_ipresso_user']) {
                return $row['id_ipresso_user'];
            }
        }
        return false;
    }

    /**
     * Dodaje nowa kategorie dla Usera WP
     * @param int $ipressoId
     * @param int $agreementId
     * @return bool|mysqli_result
     */
    public function insertAgreementForUser($ipressoId, $agreementId)
    {
        return $this->sqlQuery('INSERT INTO ' . $this->prefix . 'ipresso_user_agreement VALUES (' . $ipressoId . ', ' . $agreementId . ')');
    }

    public function insertRefreshTokenToDB($token)
    {
        return $this->sqlQuery('UPDATE ' . $this->prefix . 'ipresso' . ' SET ipresso_token= "' . $token . '"');
    }

    public function dump($die, $variable, $desc = false, $noHtml = false)
    {
        if (is_string($variable)) {
            $variable = str_replace("<_new_line_>", "<BR>", $variable);
        }

        if ($noHtml) {
            echo "\n";
        } else {
            echo "<pre>";
        }

        if ($desc) {
            echo $desc . ": ";
        }

        print_r($variable);

        if ($noHtml) {
            echo "";
        } else {
            echo "</pre>";
        }

        if ($die) {
            die();
        }
    }

}
