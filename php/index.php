<!DOCTYPE>
<html ng-app="myApp" lang="en">
    <head>
        <title data-template="config:app-title">TokenEditor</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <meta data-template="config:app-meta"/>
        <link rel="shortcut icon" href="$shared/resources/images/exist_icon_16x16.ico"/>
        <link rel="stylesheet" type="text/css" href="../css/style.css"/>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
        <link rel="stylesheet" href="style.css"/>
        <!--<script type="text/javascript" src="$shared/resources/scripts/loadsource.js"/>-->
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>
        <script type="text/javascript" src="https://raw.githubusercontent.com/markusslima/bootstrap-filestyle/master/src/bootstrap-filestyle.js"> </script>
        <!-- Optional theme -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css"/>
        <!-- Latest compiled and minified JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="https://raw.githubusercontent.com/Mottie/tablesorter/master/js/jquery.tablesorter.js"></script>
        <script type="text/javascript" src="https://raw.githubusercontent.com/Mottie/tablesorter/master/js/jquery.tablesorter.widgets.js"></script>           
        <script type="text/javascript" src="https://raw.githubusercontent.com/Mottie/tablesorter/js/parsers/parser-input-select.js"></script>
    <script>
    $(document).ready(function(){
        $("option").click(function(){
            $(this).parent().parent().submit();
        });
    });
    </script>
    </head>
    <body>
        
        <div class="container">
            <div class="row">
                <div class="col-md-3">
            <h3> Your files:</h3>
            <form action="generatejson.php" method="post">
            <select name='docid' class="form-control" size="10">
<?php 
require_once('config.inc.php');
include 'Documentlist.php';
include 'TokenArray.php';
$userid = 'mzoltak@oeaw.ac.at';
$documentid = 40;
echo $userid;
$con = new PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);

$doclist = new Documentlist();
$list = $doclist->createList($userid,$con);
foreach ($list as $docid){
    echo "<option value=".$docid['document_id'].">".$docid['name']."</option>";
}

/**/

?>
            </select>
            </form>
            </div>
                <div class="col-md-9">
                  
                </div>
        </div>
</html>
