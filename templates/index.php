<?php
script('ndcversionstatus', 'script');
// style('ndcversionstatus', 'style');
?>


<div id="app-content">
	<div style="padding: 25px;">

		<h2 style="display:inline-block;"><?php p($l->t('Ndc Version Status')) ?></h2>
		<span>（<?php p($l->t('Last Time: '))?>: <?php p($_['lastCheckTime'])?>）</span>
		<h3><?php p($l->t('Version Infos')) ?></h3>
		<ul style="list-style: disc; padding-left: 20px; font-size:15px;">
			<li>
				<b><?php p($l->t('【NDCODFWEB】'))?></b> - <span><?php p($_['version_online']) ?></span>
			</li>
			<li>
				<b><?php p($l->t('【Odfweb】'))?></b> - <span><?php p($_['version_odfweb']) ?></span>
			</li>
		</ul>
		<br>
		<a href="<?php p($_['redirect_url']) ?>" target="_balnk">
			<button id="getVersion" ><?php p($l->t('Check')) ?></button>
		</a>
	</div>
</div>
