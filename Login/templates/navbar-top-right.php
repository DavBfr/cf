<?php if (\DavbFr\CF\Session::isLogged()): ?>
	<?php if ($this->config('login.show_account_menu')): ?>
		<li class="dropdown">
			<a href="javascript:void(0)"
				 class="dropdown-toggle"
				 data-toggle="dropdown"
				 role="button"
				 aria-haspopup="true"
				 aria-expanded="false">[[ login.user | tr ]] <span class="caret"></span></a>
			<ul class="dropdown-menu">
				<li><a href="#/account">[[ login.account | tr ]]</a></li>
				<li role="separator" class="divider"></li>
				<li><a href="javascript:void(0)" data-ng-click="logout()">[[ core.logout | tr ]]</a></li>
			</ul>
		</li>
	<?php else: ?>
		<li>
			<a href="javascript:void(0)" data-ng-click="logout()">[[ core.logout | tr ]]</a>
		</li>
	<?php endif; ?>
<?php endif; ?>
