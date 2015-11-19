<!DOCTYPE html>
<html ng-app="myApp" lang="en">
    <head>
        <title data-template="config:app-title">TokenEditor</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <link rel="stylesheet" type="text/css" href="css/style.css"/>
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
        <script type="text/javascript" src="js/ui-bootstrap-tpls-0.14.3.min.js"></script>
        <link rel="stylesheet" href="js/angular-chartjs/angular-chart.css">
    <script src="https://raw.githubusercontent.com/jashkenas/underscore/master/underscore.js"></script>
    <script>
        
    $(document).ready(function(){
      $("option").click(function(){
          
          $('#docids').text($(this).attr('value'));
    // $("#gridcontainer").append('<div id="grid1"  ui-grid="gridOptions"  ui-grid-selection gri ui-grid-edit ui-grid-cellnav ui-grid-pagination ui-grid-exporter class="gridstyle"></div>');
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
      
            
          
      
});
            
          
          
       
    </script>
 
    </head>
    <body>
         <div class="container" style="width:90%;"  ng-controller="MainCtrl" >
             <div class="row">
                <div class="col-md-3">
                    <h3>Controls</h3>
                    <div class="panel panel-default">
                            <div class="panel-heading" ng-init="collapsefiles = !collapsefiles" ng-click="collapsefiles = !collapsefiles">
                                 <h4 class="panel-title">Your Files</h4>
                           </div>
                   
                     <!--  <form action="generatejson.php">-->
                         <select collapse="collapsefiles" id='docid' class="form-control" size="10">
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
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading" ng-init="collapseimport = !collapseimport" ng-click="collapseimport = !collapseimport">
                                 <h4 class="panel-title">Import</h4>
                           </div>
                        <div collapse="collapseimport" id="import">
                        </div>
                        </div>
                        <!--      </form>-->
                        <div class="panel panel-default">
                            <div class="panel-heading" ng-init="collapsestats = !collapsestats" ng-click="collapsestats = !collapsestats">
                                 <h4 class="panel-title"> PoS Stats</h4>
                           </div>
                        
                            <span class="text-muted">
                                <div collapse="collapsestats"  ng-controller="DoughnutCtrl">
                                    <div class="panel-body">
                                        <canvas id="doughnut"  class="chart chart-doughnut" data="data" labels="labels"></canvas> 
                                    </div>
                                </div>
                            </span>
                       </div>
                       <div class="panel panel-default">
                            <div class="panel-heading" ng-init="collapseexport = !collapseexport" ng-click="collapseexport = !collapseexport">
                                 <h4 class="panel-title">Export</h4>
                           </div>
                            <div collapse="collapseexport" id="export">
                                <ul style="list-style:none;">
                                 <li><a href="">Orginal Src-File</a></li>
                                 <li><a href="">Updated Src-File</a></li>
                                </ul>    
                            </div>
                       </div>
                    </div>
                <div class="col-md-9">
                    <h3>tokenEditor</h3>
                  <div id="grid1"  ui-grid="gridOptions"  ui-grid-selection gri ui-grid-edit ui-grid-cellnav ui-grid-pagination ui-grid-exporter class="gridstyle"></div>
             
              
                    </div>
                </div>
        </div>
        <div id="docids" style="display:none;"></div>
        </body>
</html>