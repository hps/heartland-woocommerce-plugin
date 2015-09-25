<div class="securesubmit-header clearfix">
   
   <div class="secure"></div>
   <div class="securesubmit-logo"><span class="sr-only">SecureSubmit</span></div>
   
</div>
  
  
   <fieldset>
      
    <?php if ($this->description): ?>
      <div class="securesubmit-content">
        <p class="securesubmit-description"><?php echo $this->description; ?></p>
      </div>
        <hr />
    <?php endif; ?>
    <?php if ($this->allow_card_saving): ?>
      <div class="securesubmit-content">
        <?php $cards = get_user_meta(get_current_user_id(), '_secure_submit_card', false); ?>
        <?php if (is_user_logged_in() && isset($cards)): ?>
            <p class="form-row form-row-wide saved-cards-option">

                <a class="button" style="float:right;" href="<?php echo get_permalink(get_option('woocommerce_myaccount_page_id')); ?>#saved-cards"><?php _e('Saved Cards', 'wc_securesubmit'); ?></a>

                <?php foreach ($cards as $i => $card): ?>
                    <input type="radio" id="secure_submit_card_<?php echo $i; ?>" name="secure_submit_card" style="width:auto;" value="<?php echo $i; ?>" />
                    <label style="display:inline;" for="secure_submit_card_<?php echo $i; ?>"><?php echo $card['card_type']; ?> ending in <?php echo $card['last_four']; ?> (<?php echo $card['exp_month'] . '/' . $card['exp_year']; ?>)</label><br />
                <?php endforeach; ?>

                <input type="radio" id="new_card" name="secure_submit_card" style="width:auto;" <?php checked(1, 1); ?> value="new" /> <label style="display:inline;" for="new_card">Use a new card</label>
            </p>
            <div class="clear"></div>
      </div>
        <?php endif; ?>
    <?php endif; ?>
      <div class="securesubmit-content">
    <div class="securesubmit_new_card">
       <div class="securesubmit_new_card_info">
        <p class="form-row form-row-wide">
            <label for="securesubmit_card_number"><?php _e("Credit Card number", 'wc_securesubmit') ?> <span class="required">*</span></label>
            <input type="text" autocomplete="off" class="input-text card-number" placeholder="**** **** **** ****" />
        </p>
        <div class="clear"></div>
        <p class="form-row form-row-first half-row">
            <label for="cc-expire-month"><?php _e("Expiration date", 'wc_securesubmit') ?> <span class="required">*</span></label>
            
               <input type="text" autocomplete="off" class="input-text expiry-date" placeholder="MM / YY" />         
               
        </p>
          <p class="form-row form-row-last half-row">
            <label for="securesubmit_card_csc"><?php _e("Security code", 'wc_securesubmit') ?> <span class="required">*</span></label>
            <input type="text" id="securesubmit_card_csc" maxlength="4" autocomplete="off" class="input-text card-cvc" placeholder="CVV" />
            <span class="help securesubmit_card_csc_description"></span>
        </p>
       </div>
        <?php if ($this->allow_card_saving == 'yes'): ?>
            <p class="form-row form-row-wide securesubmit-save-cards">
                <input type="checkbox" autocomplete="off" id="save_card" name="save_card" value="true" style="display:inline">
                <label for="save_card" style="display: inline;"><?php _e("Save Credit Card for Future Use", 'wc_securesubmit') ?></label>
            </p>
        <?php endif; ?>
        <div class="clear"></div>
    </div>
      </div>
</fieldset>
