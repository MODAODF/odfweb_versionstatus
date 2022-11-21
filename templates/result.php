<?php
script('ndcversionstatus', 'result');
style('ndcversionstatus', 'index');
?>

<div id="ndcversionstatus">
	<h2><?php p($l->t('ODF Tools Version Status')) ?></h2>
	<h3><?php p($l->t('Version Check Result')) ?></h3>
	<ul>

		<?php if (isset($_['odfweb'])) { ?>
		<li>
			<b><?php p($l->t('【Odfweb】'))?></b> -
			<span><?php p($_['odfweb']['msg']) ?> </span>
			<span style="color:<?php p($_['odfweb']['color']) ?> ;">
				<?php p($_['odfweb']['result']); ?>
			</span>
		</li>
		<?php }?>

		<?php if (isset($_['modaodfweb'])) { ?>
		<li>
			<b><?php p($l->t('【MODAODFWEB】'))?></b> -
			<span><?php p($_['modaodfweb']['msg']) ?> </span>
			<span style="color:<?php p($_['modaodfweb']['color']) ?> ;">
				<?php p($_['modaodfweb']['result']); ?>
			</span>
		</li>
		<?php }?>

	</ul>

	<br /><br />
	<h3><?php p($l->t('Email members of admin group about check result: ')); ?></h3>
	<div><span class="msg"></span></div>
	<ul class="mailResult"></ul>
</div>
