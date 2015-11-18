<!DOCTYPE html>
<html ng-app="myApp" lang="en">
    <head>
        <title data-template="config:app-title">TokenEditor</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <link rel="stylesheet" type="text/css" href="../tokeneditor/css/style.css"/>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.16/angular.js"></script>
      <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.16/angular-touch.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.16/angular-animate.js"></script>
        <script src="https://raw.githubusercontent.com/angular-ui/ui-grid.info/gh-pages/release/ui-grid-stable.js"></script>
        
        <link rel="stylesheet" href="https://cdn.rawgit.com/angular-ui/bower-ui-grid/master/ui-grid.min.css" type="text/css">
        <script src="js/app.js"></script>
        
        
        <!--<script type="text/javascript" src="$shared/resources/scripts/loadsource.js"/>-->
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>
        <script type="text/javascript" src="https://raw.githubusercontent.com/markusslima/bootstrap-filestyle/master/src/bootstrap-filestyle.js"> </script>
        <!-- Optional theme -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css"/>
        <!-- Latest compiled and minified JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
       <!-- <script type="text/javascript" src="https://raw.githubusercontent.com/Mottie/tablesorter/master/js/jquery.tablesorter.js"></script>
        <script type="text/javascript" src="https://raw.githubusercontent.com/Mottie/tablesorter/master/js/jquery.tablesorter.widgets.js"></script>           
        <script type="text/javascript" src="https://raw.githubusercontent.com/Mottie/tablesorter/js/parsers/parser-input-select.js"></script>-->
        <script type="text/javascript" src="https://raw.githubusercontent.com/nnnick/Chart.js/master/Chart.js"></script>
        <script type="text/javascript" src="js/angular-chartjs/angular-chart.js"></script>
        <script type="text/javascript" src="js/TokenEditorImporter.js"></script>
        <link rel="stylesheet" href="js/angular-chartjs/angular-chart.css">
    <script src="https://raw.githubusercontent.com/jashkenas/underscore/master/underscore.js"></script>
    <script>
    $(document).ready(function(){
      $("option").click(function(){
     $("#gridcontainer").append('<div id="grid1"  ui-grid="gridOptions"  ui-grid-selection gri ui-grid-edit ui-grid-cellnav ui-grid-pagination ui-grid-exporter class="gridstyle"></div>');
      });
        new TokenEditorImporter(
            $('#import').get(0), 
            'document',
            function(data){
                if(data.status == 'OK'){
                    $('#docid').append('<option value="' + data.document_id + '">' + data.name + '</option>');
                }
            }
        );
     /*   $('#docid').change(function(){
            var docId = $(this).attr('value');
            $http.get('https://clarin.oeaw.ac.at/tokenEditor/generatejson.php?docid='+ docId).success(function (data) {
                $scope.gridOptions.data = data;
            });
        });*/
});
            
          
          
       
    </script>
    </head>
    <body>
         <div class="container" style="width:90%;"  ng-controller="MainCtrl" >
             <div class="row">
                <div class="col-md-3">
                     <h3> Your files:</h3>
                     <!--  <form action="generatejson.php">-->
                         <select id='docid' class="form-control" size="10">
                            <?php 
                            require_once('config.inc.php');
                            include 'Documentlist.php';
                            include 'TokenArray.php';
                            
                            $userid = $_SERVER[$CONFIG['userid']];
                            $con = new PDO($CONFIG['dbConn'], $CONFIG['dbUser'], $CONFIG['dbPasswd']);
                            
                            $doclist = new Documentlist();
                            $list = $doclist->createList($userid,$con);
                            foreach ($list as $docid){
                                echo "<option ng-click='httprequest()' value=".$docid['document_id'].">".$docid['name']."</option>";
                            }
                            ?>
                        </select>
                        <div id="import">
                        </div>
                        <!--      </form>-->
                         <h4>Progress</h4>
              <span class="text-muted"><div ng-controller="DoughnutCtrl">
                        <canvas id="doughnut"   class="chart chart-doughnut" data="data" labels="labels"></canvas> 
                    </div>
                     </span>
                </div>
                <div class="col-md-9">
                    <div id="gridcontainer">
                   
                    </div>
              
             
              
                    </div>
                </div>
        </div>
        </body>
</html>