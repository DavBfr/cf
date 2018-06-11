<?php if (\DavbFr\CF\Session::isLogged()): ?>
<li>
	<a href="javascript:void(0)" data-ng-click="logout()">[[ core.logout | tr ]]</a>
</li>
<?php endif; ?>
