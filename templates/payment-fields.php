<div class="securesubmit-header clearfix">
   <div class="secure"></div>
   <div class="securesubmit-logo"><span class="sr-only">SecureSubmit</span></div>
</div>

<fieldset>
   <?php if ($this->description): ?>
      <div class="securesubmit-content">
         <p class="securesubmit-description">
            <?php echo $this->description; ?>
         </p>
      </div>
      <hr />
   <?php endif; ?>
   <?php if ($this->allow_card_saving): ?>
      <div class="securesubmit-content">
         <?php $cards = get_user_meta(get_current_user_id(), '_secure_submit_card', false); ?>
         <?php if (is_user_logged_in() && isset($cards)): ?>
            <div class="saved-creditcards-list-header saved-creditcards-list form-row form-row-wide no-bottom-margin no-bottom-padding">
               <div class="ss-section-header clearfix">
                  <h6>Select Payment Method</h6>
                  <a class="button" style="float:right;" href="<?php echo get_permalink(get_option('woocommerce_myaccount_page_id')); ?>#saved-cards">
                     <?php _e('Manage Cards', 'wc_securesubmit'); ?>
                  </a>
               </div>
            </div>
            <div class="saved-creditcards-list form-row form-row-wide no-bottom-margin">
            <?php
               $checked = " checked ";
               $totalcards = 0;
            ?>
               <?php foreach ($cards as $i => $card): ?>
                  <div class="saved-card saved-card-<?php echo strtolower($card['card_type']); ?>">
                     <div class="saved-card-selector">
                        <input <?php echo $checked; ?> class="saved-selector" type="radio" id="secure_submit_card_<?php echo $i; ?>" name="secure_submit_card" style="width:auto;" value="<?php echo $i; ?>" />
                     </div>
                     <div class="saved-card-info">
                        <label style="display:inline;" for="secure_submit_card_<?php echo $i; ?>">
                           <p>
                              <?php echo $card['card_type']; ?> ending in
                              <?php echo $card['last_four']; ?>
                           </p>
                           <p><span>Expires on <?php echo $card['exp_month'] . '/' . $card['exp_year']; ?></span></p>
                        </label>
                     </div>
                  </div>
                  <?php $checked = ""; ?>
               <?php endforeach; ?>
                  <div class="saved-card saved-card-new">
                     <div class="saved-card-selector">
                        <input <?php echo $checked; ?> type="radio" class="saved-selector" id="secure_submit_card_new" name="secure_submit_card" style="width:auto;" value="<?php echo $i + 1; ?>" />
                     </div>
                     <div class="saved-card-info">
                        <label style="display:inline;" for="secure_submit_card_new">
                           <p>
                              Pay with a new credit card
                           </p>
                        </label>
                     </div>
                  </div>

               <input type="hidden" id="new_card" name="new_secure_submit_card" value="false" />
            </div>
            <div class="clear"></div>
         <?php endif; ?>
      </div>
   <?php endif; ?>

   <?php
   if ($checked = " checked ") {
      $styletag = "display:none;";
   } else {
      $styletag = "display:block;";
   }
   ?>

   <div class="securesubmit-content new-card-content" style="<?php echo $styletag; ?>">
      <div class="securesubmit_new_card">
         <div class="securesubmit_new_card_info">
            <div class="form-row form-row-wide no-bottom-margin no-bottom-padding hideable">
               <label for="securesubmit_card_number">
                  <?php _e("Credit Card number", 'wc_securesubmit') ?>
                  <span class="required">*</span>
               </label>
               <?php if ($this->use_iframes): ?>
                  <div id="securesubmit_card_number"></div>
               <?php else: ?>
                  <input id="securesubmit_card_number" type="tel" autocomplete="off" class="input-text card-number" placeholder="•••• •••• •••• ••••" />
               <?php endif; ?>
            </div>
            <div class="clear"></div>
            <div class="form-row hideable">
               <div class="form-row-first half-row">
                  <label for="securesubmit_card_expiration">
                     <?php _e("Expiration date", 'wc_securesubmit') ?>
                     <span class="required">*</span>
                  </label>
                  <?php if ($this->use_iframes): ?>
                     <div id="securesubmit_card_expiration"></div>
                  <?php else: ?>
                     <input id="securesubmit_card_expiration" type="tel" autocomplete="off" class="input-text expiry-date" placeholder="MM / YYYY" />
                  <?php endif; ?>
               </div>
               <div class="form-row-last half-row">
                  <label for="securesubmit_card_cvv">
                     <?php _e("Security code", 'wc_securesubmit') ?>
                     <span class="required">*</span>
                  </label>
                  <?php if ($this->use_iframes): ?>
                     <div id="securesubmit_card_cvv"></div>
                  <?php else: ?>
                     <input type="tel" id="securesubmit_card_cvv" maxlength="4" autocomplete="off" class="input-text card-cvc" placeholder="CVV" />
                  <?php endif; ?>
                  <span class="help securesubmit_card_csc_description"></span>
               </div>
            </div>
         </div>
         <?php if ($this->allow_card_saving == 'yes'): ?>
            <div class="form-row form-row-wide no-top-margin no-top-padding no-bottom-paddig no-bottom-margin">
               <p class="form-row form-row-wide securesubmit-save-cards">
                  <input type="checkbox" autocomplete="off" id="save_card" name="save_card" value="true" style="display:inline">
                  <label for="save_card" style="display: inline;">
                     <?php _e("Save Credit Card for Future Use", 'wc_securesubmit') ?>
                  </label>
               </div>
            </p>
         <?php endif; ?>
         <div class="clear"></div>

      </div>
   </div>
</fieldset>
<?php if ($this->use_iframes): // Create the iframes when WC refreshes the payment fields ?>
   <script>
      window.securesubmitLoadIframes = window.securesubmitLoadIframes || function () {};
      window.securesubmitLoadIframes();
   </script>
<?php endif; ?>
<?php // Attach the field event handlers when WC refreshes the payment fields ?>
<script>
   window.securesubmitLoadEvents = window.securesubmitLoadEvents || function () {};
   window.securesubmitLoadEvents();
</script>
