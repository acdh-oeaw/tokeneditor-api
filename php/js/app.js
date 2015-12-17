/* global input */

	var app = angular.module('myApp', ['ui.grid','ui.grid.pagination','ui.grid.edit','ui.grid.cellNav','ui.grid.exporter','chart.js','ui.grid.selection','ui.bootstrap']);

	var paginationOptions = {
					pageNumber: 1,
					pageSize: 25,
					sort: null
				};
				
	var filterQueryNo = 0;
	app.controller('MainCtrl',['$scope', '$http','$timeout','$location', function($scope,$http,$timeout,$locationProvider,$location) {
		
		$scope.gridOptions = {};
		$scope.gridOptions = { paginationPageSizes: [25, 50, 75,100,150,200,250,300,400,500,750,1000],
		paginationPageSize: 25,
		enableFiltering: true,
		enableGridMenu: true,
		enableCellEditOnFocus: true,
		useExternalPagination: true,
		useExternalSorting: true,
		 useExternalFiltering: true,
		columnDefs: [ 
	/*{ displayName: 'Id',field:'token_id',enableCellEdit: false},
      { displayName: 'Token',field:'value',enableCellEdit: false},
      { displayName: 'Type', field: 'type'},
      { displayName: 'Lemma',field: 'lemma'},
      {displayName: 'Morph',field: 'morph' },
      {displayName: 'State',field: 'state' }*/
      ],
	 
    
		onRegisterApi: function(gridApi) { 
        $scope.gridApi = gridApi;
        $scope.gridApi.edit.on.afterCellEdit($scope, function(rowEntity, colDef, newValue, oldValue) {
			
              if (newValue != oldValue){
                var postdata = new Object();
                postdata.document_id = $("#docids").text();
				console.log(rowEntity);
                postdata.token_id = rowEntity['token_id'];
                postdata.name = colDef.name; 
                postdata.value = newValue;
                
                
                				          
                  $scope.$apply();
                  $scope.refreshstats();
                  $http({
                      method: 'POST',
                      url: 'storejson.php',
                      data:postdata
 //                     ContentType: "application/json"
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
		  
	      $scope.gridApi.core.on.filterChanged( $scope, function() {
			var grid = this.grid;
			
			params = {};
			params['_docid'] = $("select").val();
			params["_pagesize"] = $scope.gridOptions.paginationPageSize,
			params["_offset"] = offset;
		 grid.columns.forEach(function(value, key) {
		if(value.filters[0].term) {
		
			params[value.name] = value.filters[0].term;
		}
		else if (!value.filters[0].term) {
			delete params[value.name];
		}
		 });		
			filterQueryNo++;
			var curFilterQueryNo = filterQueryNo;
		
			$http({
                      method: 'GET',
                      url: 'generatejson.php',
					  params:params,
                      headers: { "Content-Type": "application/json" }
                      }).success(function (data){
						    if(filterQueryNo == curFilterQueryNo) {
						$scope.flattened = [];	
					
						$scope.gridOptions.data = data.data;	
						$scope.gridOptions.totalItems = data.tokenCount;
						}
				});
			
		
		
		
       
			
          
				
	
        });
       },
	
		rowTemplate: '<div ng-class="{ \'green\': grid.appScope.rowFormatter( row ),\'grey\':row.entity.state===\'u\' }">' + '  <div ng-repeat="(colRenderIndex, col) in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ng-class="{ \'ui-grid-row-header-cell\': col.isRowHeader,\'custom\': true  }"  ui-grid-cell></div>' +   '</div>'  };
		var docid;
		$scope.httprequest = function(docid)
			{ 
				init();
				}
				
				var offset = 0;
				var getPage = function() {
				
				switch(paginationOptions.sort) {
					
					/* case uiGridConstants.ASC:
						url = '/data/100_ASC.json';
						break;
						case uiGridConstants.DESC:
						url = '/data/100_DESC.json';
						break;*/
				default:
				
				offset = ($scope.gridOptions.paginationCurrentPage - 1) * $scope.gridOptions.paginationPageSize;
				break;
				}
				 $http({
                      method: 'GET',
                      url: 'generatejson.php',
					  params:{
						  "_docid":$("select").val(),
						  "_pagesize": $scope.gridOptions.paginationPageSize,
						  "_offset": offset
						  },
                      headers: { "Content-Type": "application/json" }
                      }).success(function (data){
						$scope.flattened = [];	
					
						$scope.gridOptions.data = data.data;	
						$scope.gridOptions.totalItems = data.tokenCount;
  
				});
			}
		function init() {
			
			
			$scope.gridOptions.columnDefs =[];
			$scope.creategrid = true;
			$scope.refreshstats();
			var docid = $("select").val();
			
   //    $scope.newdata =[];
      //  $http.get('https://clarin.oeaw.ac.at/tokenEditor/generatejson.php?docid='+docid).success(function (data) {
			//$scope.gridOptions = {};
			 $http({
                      method: 'GET',
                      url: 'generatejson.php',
					  params:{
						  "_docid":docid,
						  "_pagesize": $scope.gridOptions.paginationPageSize,
						  "_offset": offset
						  },
                      headers: { "Content-Type": "application/json" }
                      }).success(function (data){
						$scope.flattened = [];	
						$scope.gridOptions.columnDefs =[];
						$scope.gridOptions.data = data.data;
						$scope.gridOptions.totalItems = data.tokenCount;
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
