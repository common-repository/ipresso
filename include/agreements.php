<?php

class iPresso_Agreements
{
    const AGREEMENT_PREFIX = 'agreement_';

    private $sqlClass;
    private $function;
    private $postData;

    function __construct()
    {
        $this->sqlClass = new iPresso_SQL();
        $this->function = new iPresso_Function();
        $this->postData = $_POST;
    }

    public function agreementsForm()
    {
        $this->agreementsTableHeader();
        $this->agreementsTableDraw();

        print '</div>';
    }

    /**
     * Rysuje tabelkę dla zgód
     */
    private function agreementsTableHeader()
    {
        $agreementNotice = __('Below are amarketing agreements assigned to your account. Select the ones you want to display on the comments.', 'wp-admin-ipresso');
        $agreementsSubmenuName = __('Marketing agreements', 'wp-admin-ipresso');
        echo '
            <div class = fieldset style ="display : inline-block; padding: 20px 0; width:87%; margin : 30px auto 0 auto; margin-right: 20px; margin-left: 20px; background-color: white; border-radius: 2px; box-shadow: 0px 2px 20px rgba(0, 0, 0, 0.5);">
                <fieldset>
                    <legend style= "padding: 5px; text-align: center; border-bottom:none; margin-bottom: 10px;"> ' . $agreementsSubmenuName . '
                        <br>
                        <br>
                        <br>
                        <p style ="text-align:center;">
                        ' . $agreementNotice . '
                        </p>
                    </legend>
                </fieldset>';
    }

    private function agreementsTableDraw()
    {
        $this->agreementsTableForm();

        $result = $this->sqlClass->getAgreementsFromDb();
        $numbAgr = 0;
        $agreementData = array();

        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {
                $agreementData[] = $row["id_ipresso"];

                $checked = false;
                if ($row['wp_is_active'] == 1) {
                    $checked = true;
                }
                $this->agreementsRowDraw($row['id_ipresso'], $row['wp_content'], $checked);
                $numbAgr++;
            }
        }

        $this->agreementsPrintButtons();

        if (isset($this->postData["update"])) {
            $this->agreementsShowPreloader();
            $this->function->updateWordpressDatabase();
            $this->agreementsRefreshSite();
        }

        if (isset($this->postData["zapisz"])) {
            $this->agreementsShowPreloader();
            $this->agreementsUpdateInDb($agreementData);
            $this->agreementsRefreshSite();
        }

        print'  </form>';
    }

    /**
     * Aktualizuje zgody w bazie
     * @param array $agreementsArr
     * @return bool
     */
    private function agreementsUpdateInDb($agreementsArr)
    {
        if ($agreementsArr) {
            foreach ($agreementsArr as $id) {
                $active = 0;
                if (isset($this->postData[self::AGREEMENT_PREFIX . $id])) {
                    $active = 1;
                }
                $this->sqlClass->updateAgreementInDb($id, $active);
            }
            return true;
        }
        return false;
    }

    /**
     * Rysuje wiersze ze zgodami
     * @param int $id
     * @param string $content
     * @param bool $checked
     */
    private function agreementsRowDraw($id, $content, $checked = false)
    {
        $check = '';
        if ($checked) {
            $check = 'checked="checked"';
        }

        echo '<tr>
                <td>
                    <p style = "margin:0 0 0px;">' . $content . '</p>
                </td>
                <td>
                    <input type="checkbox" style = "float:right; margin-right: 20px;" name="' . self::AGREEMENT_PREFIX . $id . '" ' . $check . '>
                </td>
              </tr>';
    }

    /**
     *
     */
    private function agreementsShowPreloader()
    {
        echo '<center>
                <div id="loading"
                style = "
                    z-index: 2;
                    position:absolute;
                    background: transparent;
                    height: 72px;
                    width: 72px;
                    border: 0px solid black;
                    top:0;
                    left:0;
                    right:0;
                    bottom:0;
                    margin:auto;"><center><img style = "height: 70px;
                    width: 70px;
                    top:0;
                    left:0;
                    right:0;
                    bottom:0;
                    margin:auto;"
                src=' . plugins_url("ipresso/images/ajax-loader.gif") . '
                alt="" />
                </center>
                </div>
              </center>';
    }

    private function agreementsRefreshSite()
    {
        echo '<meta http-equiv="refresh" content="0.1">';
    }

    private function agreementsTableForm()
    {
        $agreementContentName = __('Agreement content', 'wp-admin-ipresso');
        $checkName = __('Check', 'wp-admin-ipresso');

        echo '<form method="post" action = "">
              <table class="table table-condensed" style = "width: 90%; margin-left:auto; margin-right:auto;">
              <tr style = "font-size: 15px; height: 35px; color:white; padding: 20px; background: linear-gradient(to bottom, #333 50%, #000 70%) repeat scroll 0% 0% transparent;
                border-top-left-radius: 2px;
                border-top-right-radius: 2px;">
                <th>' . $agreementContentName . '</th>
                <th style = "text-align:right; margin-right:10px;">' . $checkName . '</th>
                </tr>';
    }

    private function agreementsPrintButtons()
    {
        $saveName = __('Save', 'wp-admin-ipresso');
        $refreshName = __('Refresh', 'wp-admin-ipresso');

        echo '</table>
                <center>
                <div class="form-inline">
                    <div class="form-group">
                        <button type="submit" class="btn-agreement" name ="zapisz"  style="display: inline; width:120px;" >' . $saveName . '</button>
                    </div>
                    <div class="form-group">
                         <button type="submit" class="btn-agreement" name ="update" style="display: inline; width:120px;">' . $refreshName . '</button>
                    </div>
                </div>
                </center>';
    }
}
