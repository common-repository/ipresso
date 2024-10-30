<?php

class iPresso_CommentsForm
{
    private $sqlClass;
    private $function;
    private $postData;

    function __construct()
    {
        $this->sqlClass = new iPresso_SQL();
        $this->function = new iPresso_Function();
        $this->postData = $_POST;
    }

    public function commentsForm()
    {
        $countComments = $this->sqlClass->countComments();
        if ($countComments == 0) {
            $this->showNoCommentsBox();
        } else {
            $this->showCommentsBox($countComments);

            $author = (isset($this->postData['autor']) && $this->postData['autor'] ? $this->postData['autor'] : false);
            $dateFrom = (isset($this->postData['data_od']) && $this->postData['data_od'] ? $this->postData['data_od'] : false);
            $dateTo = (isset($this->postData['data_do']) && $this->postData['data_do'] ? $this->postData['data_do'] : false);
            $result = $this->sqlClass->getComments($author, $dateFrom, $dateTo);

            $info = $this->createInfo();
            $this->function->commentsTableDraw($result, $info->header, $info->error);
        }
        echo '</section>';
    }

    /**
     * Wyświetla box gdy nie ma komentarzy
     */
    private function showNoCommentsBox()
    {
        $lackCommentsName = __( 'Lack comments', 'wp-admin-ipresso');
        $lackCommentNotice =  __( 'No comments with agreements added yet.', 'wp-admin-ipresso');

        echo '<section class = "table-section">
                    <div class="panel panel-default" style = "background-color:#fcfcfc; border-color:#fcfcfc;   box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.3);">
                    <p> </p>
                        <div class="row">
                            <div class="col-sm-12"><legend style = "text-align: left;">
                            <h1 style = "margin-left: 0.5cm;">' . $lackCommentsName . '</h1>
                            </legend>
                            <h3 style = "text-align: left; margin-left: 0.5cm;">' . $lackCommentNotice . '</h3>
                            <br>
                            </div>
                        </div>
                    </div>
                </section>';
    }

    /**
     * Wyświetla box z komentarzami
     * @param int $AllComments
     */
    private function showCommentsBox($AllComments)
    {
        $searchName  = __( 'Search', 'wp-admin-ipresso');
        $numerOfCommentsName  = __( 'Number of comments : ', 'wp-admin-ipresso');
        $authorName  = __( 'Author', 'wp-admin-ipresso');
        $sinceName  = __( 'Since: ', 'wp-admin-ipresso');
        $toName  = __( 'To: ', 'wp-admin-ipresso');
        $enterAuthorName  = __( 'Enter author name', 'wp-admin-ipresso');

        echo '<section class = "table-section">
                   <form class="form-inline" id="dateRangeForm" action = "" method = "post">
                        <div class="panel panel-default" style = "background-color:#fcfcfc; border-color:#fcfcfc;   box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.3);">
                                <p> </p>
                                <div class="row">
                                    <div class="col-sm-12"><legend style = "text-align: left;"><h1 style = "margin-left: 0.5cm;">' . $searchName . '</h1></legend>
                                        <h3 style = "text-align: left; margin-left: 0.5cm;">' . $numerOfCommentsName . '' . $AllComments . ' </h3><br>
                                        <div class="form-group">
                                            <label for="" style = "color: #444;">' . $authorName . '</label>
                                            <input type="text" class="form-control" name = "autor" id="autor" placeholder="' . $enterAuthorName . '">
                                        </div>
                                        <div class="form-group">
                                            <label for="" style = "color: #444;">' . $sinceName . '</label>
                                            <div class="input-group input-append date" id="dateRangePicker">
                                                <input type="text" class="form-control" name = "data_od" id="datepicker1">
                                                <span class="input-group-addon add-on">
                                                    <span class="glyphicon glyphicon-calendar"></span>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="" style = "color:#444;">' . $toName . '</label>
                                            <div class="input-group input-append date" id="dateRangePicker2">
                                                <input type="text" class="form-control"  name = "data_do" id="datepicker2">
                                                <span class="input-group-addon add-on">
                                                    <span class="glyphicon glyphicon-calendar"></span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" name = "pokaz" class="btn-login" style="font-size:15px;"><i class="glyphicon glyphicon-search" style="font-size:15px;"></i>' . $searchName . '</button>
                                        </div>
                                        <h1></h1>
                                    </div>
                                </div>
                                <br>
                        </div>
                    </form>
                </section>
                <section class = "tableFull-section">';
    }

    /**
     * Tworzy komunikaty dla wyszukiwarki
     * @return stdClass
     */
    private function createInfo()
    {
        $allName = __( 'All', 'wp-admin-ipresso');
        $noDataName = __( 'No data', 'wp-admin-ipresso');
        $resultsComments = __( 'Results of search: ', 'wp-admin-ipresso');
        $resultsError = __( 'No data for: ', 'wp-admin-ipresso');
        $sinceName = __( 'Since: ', 'wp-admin-ipresso');
        $toName = __( 'To: ', 'wp-admin-ipresso');
        $return = new stdClass();

        $return->header = $resultsComments;
        $return->error = $resultsError;

        if (isset($this->postData['autor']) && $this->postData['autor']) {
            $return->header .= $this->postData['autor'] . ', ';
            $return->error .= $this->postData['autor'] . ', ';
        }
        if (isset($this->postData['data_od']) && $this->postData['data_od']) {
            $return->header .=  $sinceName . $this->postData['data_od'] . ', ';
            $return->error .=  $sinceName . $this->postData['data_od'] . ', ';
        }
        if (isset($this->postData['data_do']) && $this->postData['data_do']) {
            $return->header .= $toName . $this->postData['data_do'];
            $return->error .= $toName . $this->postData['data_do'];
        }

        if (!$this->postData) {
            $return->header = $allName;
            $return->error = $noDataName;
        }

        return $return;

    }
}

