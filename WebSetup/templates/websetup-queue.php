<?php $stat = $this->get("queue", "raw"); ?>

<h2>CF Message Queue</h2>
<div class="well"><h3><?php echo($stat['msg_qnum']) ?> message<?php echo($stat['msg_qnum'] > 1 ? 's' : '') ?> in queue.</h3></div>
<ul>
	<li><b>UID/GID:</b> <?php echo($stat['msg_perm.uid']) ?>/<?php echo($stat['msg_perm.gid']) ?></li>
	<li><b>Mode:</b> 0<?php echo(decoct($stat['msg_perm.mode'])) ?></li>
	<li><b>Last message sent:</b> <?php echo(date("Y-m-d H:i:s", $stat['msg_stime'])) ?> sent by <?php echo($stat['msg_lspid']) ?></li>
	<li><b>Last message received:</b> <?php echo(date("Y-m-d H:i:s", $stat['msg_rtime'])) ?> received by <?php echo($stat['msg_lrpid']) ?></li>
	<li><b>Last changed:</b> <?php echo(date("Y-m-d H:i:s", $stat['msg_ctime'])) ?></li>
	<li><b>Maximum message size:</b> <?php echo($stat['msg_qbytes']) ?></li>
</ul>

<button <?php if ($stat['msg_qnum'] == 0): ?>disabled="disabled"<?php endif; ?> onclick="document.location='mq';" type="button" class="btn btn-primary">Process Queue</button>

