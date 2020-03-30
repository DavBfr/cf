<div class="row">
	<div class="col-md-4 hover-reveal-parent">
		<h1>[[ login.account | tr ]]</h1>
		<img data-ng-cloak src="https://www.gravatar.com/avatar/{{ account.gravatar }}?d=mp&s=200" alt="Avatar"
				 class="avatar">

		<button data-ng-click="editImage()" type="button"
						class="hover-reveal btn btn-default btn-xs"
						title="Change">
			<span class="glyphicon glyphicon-pencil"></span>
		</button>
	</div>

	<div class="user-info col-md-8" data-ng-cloak>
		<h1 class="hover-reveal-parent">
			{{ account.name }}
			<?php if ($this->config('login.allow_change_name')): ?>
				<button data-ng-click="editUsername()" type="button"
								class="hover-reveal btn btn-default btn-xs"
								title="Change">
					<span class="glyphicon glyphicon-pencil"></span>
				</button>
			<?php endif; ?>
		</h1>
		<?php if ($this->config('login.allow_change_name')): ?>
			<form class="form-inline" ng-show="changingUsername">
				<div class="form-group">
					<input type="text" class="form-control" ng-model="account.name">
					<button type="submit" ng-click="saveUsername()" class="btn btn-primary">[[ core.submit | tr ]]</button>
				</div>
			</form>
		<?php endif; ?>

		<div class="row account-detail" data-ng-show="account.admin">
			<div class="col-md-6">
				[[ login.admin | tr ]]
			</div>
			<div class="col-md-6">
				<span class="glyphicon glyphicon-ok"></span>
			</div>
		</div>

		<div class="row account-detail">
			<div class="col-md-6">
				[[ login.email | tr ]]
			</div>
			<div class="col-md-6 hover-reveal-parent">
				<span ng-hide="changingEmail">
					{{ account.email }}
					<?php if ($this->config('login.allow_change_email')): ?>
						<button data-ng-click="editEmail()" type="button"
										class="hover-reveal btn btn-default btn-xs"
										title="Change">
						<span class="glyphicon glyphicon-pencil"></span>
					</button>
					<?php endif; ?>
				</span>
				<?php if ($this->config('login.allow_change_email')): ?>
					<form class="form-inline" ng-show="changingEmail">
						<div class="form-group">
							<input type="email" class="form-control" ng-model="account.email">
							<button type="submit" ng-click="saveEmail()" class="btn btn-primary">[[ core.submit | tr ]]</button>
						</div>
					</form>
				<?php endif; ?>
			</div>
		</div>

		<div class="row account-detail">
			<div class="col-md-6">
				[[ login.since | tr ]]
			</div>
			<div class="col-md-6">
				{{ account.creation * 1000 | date }}
			</div>
		</div>

		<?php $this->insert('account-page-info.php', true); ?>

		<?php if ($this->config('login.allow_change_password')): ?>
			<div class="row account-detail">
				<button ng-hide="changingPassword" data-ng-click="changePassword()" type="button" class="btn btn-default"
								title="Change">
					<span class="glyphicon glyphicon-lock"></span> [[ login.change_pwd | tr ]]
				</button>

				<form ng-cloak ng-hide="!changingPassword" name="form" class="well form-horizontal" data-role="form">
					<div class="form-group" data-ng-class="{'has-error': form.old_password.$invalid}">
						<label class="col-sm-4 control-label" for="old_password">[[ login.pwd_old | tr ]]</label>
						<div class="col-sm-8" style="margin-bottom:20px;">
							<input class="form-control" type="password" id="old_password" data-ng-required="1" name="old_password"
										 data-ng-model="old_pass" placeholder="[[ login.pwd_old | tr ]]">
							<p class="help-block error"
								 data-ng-show="form.old_password.$dirty && form.old_password.$error.required"><?php $this->tr("core.enter_value") ?></p>
							<p class="help-block error"
								 data-ng-show="form.old_password.$error.old_password"><?php $this->tr("core.enter_valid_value") ?></p>
						</div>
					</div>

					<div class="form-group" data-ng-class="{'has-error': form.new_password.$invalid}">
						<label class="col-sm-4 control-label" for="new_password">[[ login.pwd_new | tr ]]</label>
						<div class="col-sm-8" style="margin-bottom:20px;">
							<input type="password" class="form-control" id="new_password" data-ng-required="1" name="new_password"
										 data-ng-model="new_pass" placeholder="[[ login.pwd_new | tr ]]">
							<p class="help-block error"
								 data-ng-show="form.new_password.$dirty && form.new_password.$error.required"><?php $this->tr("core.enter_value") ?></p>
							<p class="help-block error"
								 data-ng-show="form.new_password.$error.new_password"><?php $this->tr("core.enter_valid_value") ?></p>
						</div>
					</div>

					<div class="form-group" data-ng-class="{'has-error': form.new_password_again.$invalid}">
						<label class="col-sm-4 control-label" for="new_password_again">[[ login.pwd_new2 | tr ]]</label>
						<div class="col-sm-8" style="margin-bottom:20px;">
							<input type="password" class="form-control" id="new_password_again" data-ng-required="1"
										 name="new_password_again" data-ng-model="new_pass2" placeholder="[[ login.pwd_new2 | tr ]]">
							<p class="help-block error"
								 data-ng-show="form.new_password_again.$dirty && form.new_password_again.$error.required"><?php $this->tr("core.enter_value") ?></p>
							<p class="help-block error" data-ng-show="form.new_password_again.$dirty && new_pass != new_pass2">[[ login.pwd_nomatch | tr ]]</p>
							<p class="help-block error"
								 data-ng-show="form.new_password_again.$error.new_password_again"><?php $this->tr("core.enter_valid_value") ?></p>
						</div>
					</div>

					<div class="form-group">
						<div class="col-sm-offset-4 col-sm-8">
							<button type="submit" data-loading-text="<?php $this->tr("core.saving") ?> ..." class="btn btn-primary"
											data-ng-click="savePassword()"
											data-ng-disabled="form.$invalid || new_pass != new_pass2">[[ core.submit | tr ]]
							</button>
							<button type="button" class="btn btn-default"
											data-ng-click="cancelChangePassword()">[[ core.cancel | tr ]]
							</button>
						</div>
					</div>
				</form>
			</div>
		<?php endif; ?>
	</div>
</div>


