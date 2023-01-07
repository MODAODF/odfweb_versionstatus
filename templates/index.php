<?php
style('ndcversionstatus', 'index');
?>

<div id="ndcversionstatus">

	<h2><?php p($l->t('ODF Tools Version Status')) ?></h2>

	<?php if($_['lastCheckTime'] && !empty($_['lastCheckTime'])) { ?>
		<span>（<?php p($l->t('Last Time'))?>: <?php p($_['lastCheckTime'])?>）</span>
	<?php }?>

	<h3><?php p($l->t('Version Infos')) ?></h3>
	<ul>
		<li>
			<b><?php p($l->t('【Odfweb】'))?></b> - <span><?php p($_['odfweb'] ?? $l->t('Fail to get version.') )?></span>
		</li>
		<li>
			<b><?php p($l->t('【MODAODFWEB】'))?></b> - <span><?php p($_['modaodfweb'] ?? $l->t('Fail to get version.') )  ?></span>
		</li>
	</ul>
	<br>

	<form method="POST" action="<?php p($_['resultPage']) ?>" >
		<button type="submit"><?php p($l->t('Check')) ?></button>
	</form>
</div>
