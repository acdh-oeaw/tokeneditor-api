/* global input */

	var app = angular.module('myApp', ['ui.grid','ui.grid.pagination','ui.grid.edit','ui.grid.cellNav','ui.grid.exporter','chart.js','ui.grid.selection','ui.bootstrap']);



	app.controller('MainCtrl',['$scope', '$http','$timeout','$location', function($scope,$http,$timeout,$locationProvider,$location) {
		$scope.gridOptions = {};
		$scope.gridOptions = { paginationPageSizes: [25, 50, 75],
		paginationPageSize: 25,
		enableFiltering: true,
		enableGridMenu: true,
		enableCellEditOnFocus: true,
		useExternalPagination: true,
		useExternalSorting: true,
    columnDefs: [ 
	/*{ displayName: 'Id',field:'token_id',enableCellEdit: false},
      { displayName: 'Token',field:'value',enableCellEdit: false},
      { displayName: 'Type', field: 'type'},
      { displayName: 'Lemma',field: 'lemma'},
      {displayName: 'Morph',field: 'morph' },
      {displayName: 'State',field: 'state' }
     */   ],
    
		onRegisterApi: function(gridApi) { 
        $scope.gridApi = gridApi;
        $scope.gridApi.edit.on.afterCellEdit($scope, function(rowEntity, colDef, newValue, oldValue) {
			
              if (newValue != oldValue){
                var postdata = new Object();
                postdata.document_id = parseInt($("#docids").text());
                postdata.token_id = rowEntity['token id'];
                postdata.changedproperty = colDef.name; 
                postdata.value = newValue;
                
                
                				          
                  $scope.$apply();
                  $scope.refreshstats();
                  $http({
                      method: 'POST',
                      url: 'storejson.php?docid=' + $("#docids").text(),
                      data:JSON.stringify(postdata),
                      headers: { "Content-Type": "application/json" }
                      })
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
       },
		rowTemplate: '<div ng-class="{ \'green\': grid.appScope.rowFormatter( row ),\'grey\':row.entity.state===\'u\' }">' + '  <div ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader,\'custom\': true  }"  ui-grid-cell></div>' +   '</div>'  };
		$scope.httprequest = function(docid)
			{
				init();
				}
		function init() {

			 $scope.gridOptions.columnDefs = [];
			$scope.creategrid = true;
			$scope.refreshstats();
			var docid = $("select").val();
      
   //    $scope.newdata =[];
      //  $http.get('https://clarin.oeaw.ac.at/tokenEditor/generatejson.php?docid='+docid).success(function (data) {
			//$scope.gridOptions = {};
			 $http({
                      method: 'GET',
                      url: 'generatejson.php?docid='+ docid + '&pagesize=' + $scope.gridOptions.paginationPageSize,
                      headers: { "Content-Type": "application/json" }
                      }).success(function (data){
						$scope.flattened = [];	
                  
		/*				$.each(data,function(i,item){
							$scope.obj = new Object;
							$scope.obj.value = item.value;
              $scope.obj.token_id = item.token_id;
							$.each(item.properties,function(e,element) {
								if (element.hasOwnProperty('lemma')) {
									$scope.obj.lemma = element.lemma;
								}
								else if (element.hasOwnProperty('type')) {
									$scope.obj.type = element.type;
								}
								else if (element.hasOwnProperty('morph')) {
									$scope.obj.morph = element.morph;
								}
								else if (element.hasOwnProperty('state')) {
									$scope.obj.state = element.state;
								}
							
								});
					
							$scope.obj.type = (item.properties[0].type);
							$scope.obj.lemma = (item.properties[0].lemma);
							$scope.obj.state = (item.properties[0].state);
							$scope.obj.morph = (item.properties[0].morph);
							$scope.flattened.push($scope.obj);
						});
						console.log($scope.obj);
					
						
						*/
						$scope.gridOptions.data = data	
  
				});
 
 
 
       
  //        console.log(_.find(data, function (data) { return 'properties' in data.properties }));
  	
          
      
          
         
  
     $scope.filterOptions = {
        filterText: "",
        useExternalFilter: true
    };




 
 
$scope.update = function(column, row, cellValue) {};
  
 $scope.arrayOfChangedObjects = [];
 
 
  /*  $scope.gridOptions = {
        multiselect:true,
        enableFiltering: true,
        enableSelectAll: true,
  //      plugins: [new ngGridFlexibleHeightPlugin()],
         enableRowSelection: true,
        enableGridMenu: true,
             
     columnDefs: [
      { displayName: 'Token',field:'value',enableCellEdit: false},
      { displayName: 'Type', field: 'type'},
      { displayName: 'Lemma',field: 'lemma'},
      {displayName: 'Morph',field: 'morph' },
      {displayName: 'state',field: 'state' }
        ],
        
        
        exporterAllDataFn: function() {
        return getPage(1, $scope.gridOptions.totalItems, paginationOptions.sort)
        .then(function() {
          $scope.gridOptions.useExternalPagination = false;
          $scope.gridOptions.useExternalSorting = false;
          $scope.gridOptions.multiSelect = true;
          getPage = null;
        });
      } 
   
    };*/
   // console.log($scope.row.entity);
    $scope.rowFormatter = function( row ) {
 return row.entity.state === 's';
  
 };
 $scope.labels =[];
 $scope.items =[];
  $scope.data =[];
  $scope.counters = [];
$timeout(callAtTimeout, 2000);
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
//$("#gridcontainer").append('<div id="grid1"  ui-grid="gridOptions"  ui-grid-selection gri ui-grid-edit ui-grid-cellnav ui-grid-pagination ui-grid-exporter class="gridstyle"></div>');
     }
       $scope.refreshstats = function(){
       $scope.labels = [];
     $scope.data = [];
countData = _.countBy($scope.gridOptions.data, function(item){
    return item.type;
            });
    angular.forEach(countData, function(key,item) {
       
     $scope.labels.push(item);
     $scope.data.push(key);
});

  };
}]);


app.controller("DoughnutCtrl", function ($scope) {
 $scope.labels;
 $scope.data;
  
});
