/* global input */

var app = angular.module('myApp', ['ui.grid','ui.grid.pagination','ui.grid.edit','ui.grid.cellNav','ui.grid.exporter','chart.js','ui.grid.selection']);



$scope.httprequest = function(){
app.controller('MainCtrl',['$scope', '$http','$timeout','$location', function($scope,$http,$timeout,$locationProvider,$location) {
  //  var url = $locationProvider.$$absUrl;
  //  var parts = url.split("=");
  //  $scope.parts = parts[1];
  
      $scope.creategrid = true;
      console.log($scope.creategrid);
        $http.get('https://clarin.oeaw.ac.at/tokenEditor/generatejson.php?docid=40').success(function (data) {
        $scope.gridOptions.data = data;
    });
  
    $scope.filterOptions = {
        filterText: "",
        useExternalFilter: true
    };

 
 
 
 
$scope.update = function(column, row, cellValue) {
    
};
  //  $scope.saveItem = function(id,type,lemma) {
    //                          $http.post('http://tokeneditor.hephaistos.arz.oeaw.ac.at/exist/apps/tagging/xmltojson.xq',{id: id,type: type,lemma: lemma}).success(function(data) {
      //                            alert('update completed!');
        //                      });
 //Array für veränderte Objekte
 $scope.arrayOfChangedObjects = [];
 
 
    $scope.gridOptions = {
        multiselect:true,
        enableFiltering: true,
        enableSelectAll: true,
  //      plugins: [new ngGridFlexibleHeightPlugin()],
         enableRowSelection: true,
        paginationPageSizes: [25, 50, 75],
        paginationPageSize: 25,
        enableGridMenu: true,
       enableCellEditOnFocus: true,
        
            
        columnDefs: [
      { field:'id',name: 'id',enableCellEdit: false},
      { name: 'value', displayName: 'Token',resizable: true,enableCellEdit: false },
      { name: 'properties[1].type',displayName: 'Type'},
      { name: 'properties[0].lemma',displayName: 'Lemma' },
      { name: 'properties[2].morph',displayName: 'Morph' },
	{name: 'properties[3].state',displayName: 'State' }
        ], exporterAllDataFn: function() {
        return getPage(1, $scope.gridOptions.totalItems, paginationOptions.sort)
        .then(function() {
          $scope.gridOptions.useExternalPagination = false;
          $scope.gridOptions.useExternalSorting = false;
          $scope.gridOptions.multiSelect = true;
          getPage = null;
        });
      } , onRegisterApi: function(gridApi) {
        $scope.gridApi = gridApi;
 // $scope.gridApi.cellNav.on.navigate($scope,function(newRowCol, oldRowCol) {      
             
 //   $scope.selectedltype = ($scope.gridApi.grid.getCellValue(newRowCol.row,newRowCol.col));
 //    $scope.selectedlemma = ($scope.gridApi.grid.getCellValue(newRowCol.row,newRowCol.col));
 //  console.log(newRowCol.col);
//});
$scope.selectedlemma= null;
$scope.selectedtype= null;
$scope.selectedstate= null;
 gridApi.selection.on.rowSelectionChanged($scope,function(row){
    $scope.selectedtype = row.entity.type;
    $scope.selectedlemma = row.entity.lemma;
     $scope.selectedstate = row.entity.state;
    
  });
 gridApi.selection.on.rowSelectionChangedBatch($scope,function(rows){
        $scope.selectedtype = rows[0].entity.type;
         $scope.selectedlemma = rows[0].entity.lemma;
          $scope.selectedstate = rows[0].entity.state;
       
      });

      
         $scope.gridApi.edit.on.afterCellEdit($scope, function(rowEntity, colDef, newValue, oldValue) {
    //Do your REST call here via $http.get or $http.post
      //   $scope.arrayOfChangedObjects.push(rowEntity.id,rowEntity.type,rowEntity.lemma,rowEntity.state);
        
    if (newValue != oldValue){
        $scope.$apply();
        $scope.refreshstats();
        $http({ 
    method: 'POST',
    url: 'http://tokeneditor.hephaistos.arz.oeaw.ac.at/exist/apps/tagging/syncwithxml.xq?col='+$scope.parts,
    data:'<?xml version="1.0" encoding="UTF-8" standalone="no"?><TEI xmlns:tei="http://www.tei-c.org/ns/1.0"><tei:w  id="'+rowEntity.id+'"  type="'+rowEntity.type+'" lemma="'+ rowEntity.lemma+'" >'+rowEntity['#text']+'</tei:w></TEI>',
    headers: { "Content-Type": "application/xml" }
})
        // $http.post('http://tokeneditor.hephaistos.arz.oeaw.ac.at/exist/apps/tagging/syncjsonwithxml.xq',{id: rowEntity.id,type: rowEntity.type,lemma: rowEntity.lemma}).success(function(data) {
        //                        alert('update completed!');
        //                     });
    //alert( ' ID: ' + rowEntity.id + ' Name: ' + rowEntity.lemma + colDef +  ' Age: ' + rowEntity.type +  rowEntity.status);
    }
  });
        
        
        $scope.gridApi.core.on.sortChanged($scope, function(grid, sortColumns) {
          if(getPage) {
            if (sortColumns.height > 0) {
              paginationOptions.sort = sortColumns[0].sort.direction;
            } else {
              paginationOptions.sort = null;
            }
            getPage(grid.options.paginationCurrentPage, grid.options.paginationPageSize, paginationOptions.sort)
          }
        });
        gridApi.pagination.on.paginationChanged($scope, function (newPage, pageSize) {
          if(getPage) {
            getPage(newPage, pageSize, paginationOptions.sort);
          }
        });
      }, rowTemplate: 
        '<div ng-class="{ \'green\': grid.appScope.rowFormatter( row ),\'grey\':row.entity.state===\'u\' }">' +
                 '  <div ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader,\'custom\': true  }"  ui-grid-cell></div>' +
                 '</div>'
   
    };
    
    $scope.rowFormatter = function( row ) {
 return row.entity.state === 's';
  
 };
 $scope.labels =[];
 $scope.items =[];
  $scope.data =[];
  $scope.counters = [];
$timeout(callAtTimeout, 3000);
  function callAtTimeout() {
  var countData = _.countBy($scope.gridOptions.data, function(item){
    
    return item.type;
}); 
 angular.forEach(countData, function(key,item) {
     $scope.labels.push(item);
     $scope.data.push(key);
}); 
  
  $scope.labels;
  $scope.data;
  
  } 

     
}]);

}
app.controller("DoughnutCtrl", function ($scope) {
 $scope.labels;
 $scope.data;
  
});
app.controller("StatsCtrl", function ($scope,$timeout) {
 $scope.labels =[];
 $scope.items =[];
  $scope.data =[];
  $scope.counters = [];
  $timeout(callAtTimeout, 3000);
  function callAtTimeout() {

var statsData = _.countBy($scope.$parent.gridOptions.data, function(item){
    return item.state;
});

 angular.forEach(statsData, function(key,item) {
     $scope.labels.push(item);
     $scope.data.push(key);
}); 

  
  $scope.labels;
  $scope.data;
  } 
  $scope.refreshprogress = function(){
       $scope.labels = [];
     $scope.data = [];
     console.log($scope.$parent.gridOptions.data);
statsData = _.countBy($scope.$parent.gridOptions.data, function(item){
    return item.state;
            });
    angular.forEach(statsData, function(key,item) {
       
     $scope.labels.push(item);
     $scope.data.push(key);
});
$scope.labels;
  $scope.data;
  }
});
