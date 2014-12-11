<div class="container " data-ng-cloak data-ng-controller="NotificationController" >
	<div data-ng-repeat="item in alerts" class="alert alert-{{item.type}}">{{item.message}}</div>
	<div id="confirm" class="modal fade">
		<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-body">
			<p>{{confirm.message}}</p>
			</div>
			<div class="modal-footer">
			<button type="button" class="btn btn-primary" data-ng-click="confirm.onYes && confirm.onYes()" data-dismiss="modal"><?php $this->tr("core.yes") ?></button>
			<button type="button" class="btn btn-default" data-ng-click="confirm.onNo && confirm.onNo()" data-dismiss="modal"><?php $this->tr("core.no") ?></button>
			</div>
		</div>
		</div>
	</div>
</div>
