<ul data-ng-hide="pages <= 1" class="pagination pull-right crud-pagination">
	<li data-ng-class="page == 0?'disabled':''">
		<a data-ng-click="setPage(page - 1)" href="">&laquo;</a>
	</li>
	<li data-ng-class="i == page?'active':''" data-ng-repeat="i in getPages()">
		<a data-ng-click="setPage(i)" href="">{{i+1}}</a>
	</li>
	<li data-ng-class="page == pages -1?'disabled':''">
		<a data-ng-click="setPage(page + 1)" href="">&raquo;</a>
	</li>
</ul>
