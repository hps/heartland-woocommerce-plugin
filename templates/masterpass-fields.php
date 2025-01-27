<?php
if (!defined('ABSPATH')) {
    exit();
}
?>

<div class="masterpass-checkout">
  <input type="hidden"
         name="action"
         value="securesubmit_masterpass_lookup" />

  <?php if (!empty($cards)): ?>
    <h6>Credit Cards</h6>
    <?php foreach ($cards as $card): ?>
      <label>
        <input type="radio"
               name="masterpass_card_id"
               value="<?php echo esc_html($card->CardId); ?>"
               <?php if ($card->SelectedAsDefault == 'true'): ?> checked="checked"<?php endif;?> />
        <?php echo esc_html($card->BrandName); ?>
        ending in <?php echo esc_html($card->LastFour); ?>
        expiring <?php echo esc_html($card->ExpiryMonth); ?>/<?php echo esc_html($card->ExpiryYear); ?>
        <?php if ($card->SelectedAsDefault == 'true'): ?>(default)<?php endif; ?>
      </label><br />
    <?php endforeach; ?>
  <?php endif; ?>

  <button type="button" class="button" id="securesubmit-buy-with-masterpass">
    <span class="masterpass-logo">
      <img src="https://www.mastercard.com/mc_us/wallet/img/en/US/mcpp_wllt_btn_chk_147x034px.png"
           alt="<?php echo esc_html('Buy with MasterPass'); ?>" />
    </span>
    <span class="sr-only"><?php echo esc_html('Buy with MasterPass'); ?></span>
  </button>

  <a href="http://www.mastercard.com/mc_us/wallet/learnmore/en"
     class="masterpass-learn-more"
     target="_blank"
     title="<?php echo esc_html('Learn more about MasterPass', 'wc_securesubmit'); ?>">
    <?php echo esc_html('Learn more', 'wc_securesubmit'); ?>
  </a>

  <script data-cfasync="false" type="text/javascript">
    window.securesubmitMasterPassLookup = window.securesubmitMasterPassLookup || function () {};
    window.securesubmitMasterPassLookup();
  </script>
</div>
