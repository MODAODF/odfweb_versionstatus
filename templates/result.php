<?php script('ndcversionstatus', 'result');?>

<div id="app-content">
	<div style="padding: 25px;">
		<h2 style="display:inline-block;"><?php p($l->t('Ndc Version Status')) ?></h2>

		<h3><?php p($l->t('Version Check Result')) ?></h3>
		<ul style="list-style: disc; padding-left: 20px; font-size:15px;">

			<?php if (isset($_['odfweb'] )) { ?>
			<li>
				<b><?php p($l->t('【Odfweb】'))?></b> -
				<span style="color:<?php p($_['odfweb'] ? 'red' : 'green') ?> ;">
					<?php p($_['odfweb'] ? $l->t('New version available, please update.') : $l->t('Using latest version')); ?>
				</span>
			</li>
			<?php }?>

			<?php if (isset($_['ndcodfweb']) ) { ?>
			<li>
				<b><?php p($l->t('【NDCODFWEB】'))?></b> -
				<span style="color:<?php p($_['ndcodfweb'] ? 'red' : 'green') ?> ;">
					<?php p($_['ndcodfweb'] ? $l->t('New version available, please update.') : $l->t('Using latest version')); ?>
				</span>
			</li>
			<?php }?>

			<br />
			<em><?php p($l->t('Email members of admin group about check result.')); ?></em>
			<input type="submit" name="sendemail" id="sendemail" value="<?php p($l->t('Send email')); ?>"/>
			<span id="sendmail_msg" class="msg"></span>

		</ul>

	</div>
</div>
