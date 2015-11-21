<!DOCTYPE>
<html lang="en">
    <head>
        <title data-template="config:app-title">TreeTagger WebInterface</title>
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
    </head>
    <body>
        <div class="container">
            <h2>TreeTagger WebInterface</h2>
             <!--   <div class="alert alert-success"/>-->
                <div class="formcontainer">
                    <div class="row clearfix">
                        <form action="https://ttweb.eos.arz.oeaw.ac.at/rest/files/ttweb" enctype="multipart/form-data" accept-charset="utf-8" method="post">
                      <!--  <form action="http://tokeneditor.hephaistos.arz.oeaw.ac.at/taggingservice/rest/files/ttweb" enctype="multipart/form-data" accept-charset="utf-8" method="post">
                      <form action="http://taggingservice.digital-humanities.at/" enctype="multipart/form-data" accept-charset="utf-8" method="post">
                      <form action="http://clarin.aac.ac.at/tomcat/ttweb/rest/files/upload" enctype="multipart/form-data" accept-charset="utf-8" method="post">-->
                            <div class="col-md-12">
                           <!--     <div class="form-group">
                                    <label for="autor">Author of the text (Initials)</label>
                                    <input class="form-control" type="text" name="autor" id="autor" required="required"/>
                                </div>-->
                                <div class="form-group">
                                    <label for="titel">Title of the text</label>
                                    <input class="form-control" type="text" name="titel" id="titel" required="required"/>
                                </div>
                          <!--      <div class="form-group">
                                    <label for="initialen">Your initials</label>
                                    <input class="form-control" type="text" name="initialen" id="initialen" required="required"/>
                                </div>-->
                                <div class="form-group">
                                    <label for="text">Your text</label>
                                    <textarea class="form-control" name="text" rows="10" cols="40"></textarea>
                                </div>
                                <div>
                                  <!--  <input class="filestyle" style="height:auto;" type="file" name="file"/>-->
                                    <input name="_charset_" type="hidden" value="utf-8"/>
                                </div>
                                <div class="form-group" style="margin-top:1rem;">
                                   <label for="lang">Choose language</label>
                                    <select name="lang" class="form-control" required="required">
                                    <option class="form-control" value="german-utf8.par">german</option>
                                    <option class="form-control" value="english-utf8.par" name="lang">english</option>
                                    <option class="form-control" value="italian-utf8.par" name="lang">italian</option>
                                    <option class="form-control" value="french-utf8.par" name="lang">french</option>
                                    <option class="form-control" value="latin.par" name="lang">latin</option>
                                     </select>
                                     </div>
                                       <!--<div class="form-group" style="margin-top:1rem;">
                                   <label for="tokenized">Input is vertical</label>
                                    <select name="tokenized" class="form-control" required="required">
                                        <option class="form-control" value="no" selected="selected">no</option>
                                        <option class="form-control" value="yes">yes</option>
                                     </select>
                                     </div>-->
                                     
                                     Your data is processed with <a href="http://www.cis.uni-muenchen.de/~schmid/tools/TreeTagger/"> Helmut Schmidts TreeTagger</a>.<br/>
                                     <input class="btn btn-default" type="submit" value="Run"/>
                                </div>
                            
                        </form>
                  </div>
                </div>
            </div>
    </body>
</html>
