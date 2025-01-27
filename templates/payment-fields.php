<div class="securesubmit-header clearfix">
    <div class="secure"></div>
</div>

<fieldset>
    <!-- Start Description -->
    <?php if ($this->description): ?>
        <div class="securesubmit-content">
            <p class="securesubmit-description">
                <?php echo esc_html($this->description); ?>
            </p>
        </div>
        <hr />
    <?php endif; ?>
    <!-- End Description -->

    <!-- Start "if card saving is allowed" -->
    <?php $checked = ''; ?>
    <?php if ($this->allow_card_saving): ?>
        <div class="securesubmit-content">
            <!-- Start LOGGED IN SAVED CARDS -->
            <?php $cards = get_user_meta(get_current_user_id(), '_secure_submit_card', false); ?>
            <?php if (is_user_logged_in() && !empty($cards)): ?>
                <div class="saved-creditcards-list-header saved-creditcards-list form-row form-row-wide no-bottom-margin">
                    <div class="ss-section-header clearfix">
                        <h6>Select Payment Method</h6>
                        <a class="button" style="float:right;" href="<?php echo esc_html(get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>#saved-cards">
                            <?php esc_html_e('Manage Cards', 'wc_securesubmit'); ?>
                        </a>
                    </div>
                </div>

                <!-- START SAVED CREDIT CARD LIST -->
                <div class="saved-creditcards-list form-row form-row-wide no-bottom-margin">
                    <?php
                    $checked = ' checked="checked" ';
                    $totalcards = 0;
                    ?>
                    <?php foreach ($cards as $i => $card): ?>
                        <div class="clearfix saved-card saved-card-<?php echo esc_html(strtolower($card['card_type'])); ?><?php echo ($checked != '' ? ' active' : '');?>">
                            <div class="saved-card-selector">
                                <input <?php echo esc_html($checked); ?> class="saved-selector" type="radio" id="secure_submit_card_<?php echo esc_html($i); ?>" name="secure_submit_card" style="width:auto;" value="<?php echo esc_html($i); ?>" />
                            </div>
                            <div class="saved-card-info">
                                <label for="secure_submit_card_<?php echo esc_html($i); ?>">
                                    <p>
                                        <?php echo esc_html($card['card_type']); ?> ending in
                                        <?php echo esc_html($card['last_four']); ?>
                                    </p>
                                    <p><span>Expires on <?php echo esc_html($card['exp_month']) . '/' . esc_html($card['exp_year']); ?></span></p>
                                </label>
                            </div>
                           <div class="card-type-logo"></div>
                        </div>
                        <?php $checked = ""; ?>
                    <?php endforeach; ?>

                    <!-- START NEW CARD -->
                    <div class="clearfix saved-card saved-card-new<?php echo ($checked != '' ? ' active' : '');?>">
                        <div class="saved-card-selector">
                            <input <?php echo esc_html($checked); ?> type="radio" class="saved-selector" id="secure_submit_card_new" name="secure_submit_card" style="width:auto;" value="new" />
                        </div>
                        <div class="saved-card-info-new">
                            <label for="secure_submit_card_new">
                                <p>
                                    Pay with a new credit card
                                </p>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php $styletag = 'display:none;'; ?>
    <?php $newClass = ''; ?>
    <?php if (!isset($cards) || empty($cards)): ?>
      <?php $styletag = 'display:block;'; ?>
      <?php $newClass .= !is_user_logged_in() ? ' no-saved-cards' : ''; ?>
      <?php $newClass .= is_user_logged_in() ? ' logged-in-no-saved-cards' : ''; ?>
    <?php endif; ?>
    <div class="securesubmit-content new-card-content" <?php echo esc_html($newClass);?>" style="<?php echo esc_html($styletag); ?>">
        <div class="securesubmit_new_card">
            <div class="securesubmit_new_card_info">
                <div class="form-row form-row-wide no-bottom-margin hideable">
                    <label for="securesubmit_card_number">
                        <?php esc_html_e("Credit Card number", 'wc_securesubmit') ?>
                        <span class="required">*</span>
                    </label>
                    <div id="securesubmit_card_number"></div>
                </div>
                <div class="clear"></div>
                <div class="form-row hideable no-bottom-margin">
                    <div class="form-row-first half-row">
                        <label for="securesubmit_card_expiration">
                            <?php esc_html_e("Expiration date", 'wc_securesubmit') ?>
                            <span class="required">*</span>
                        </label>
                        <div id="securesubmit_card_expiration"></div>
                    </div>
                    <div class="form-row-last half-row">
                        <label for="securesubmit_card_cvv">
                            <?php esc_html_e("Security code", 'wc_securesubmit') ?>
                            <span class="required">*</span>
                        </label>
                        <div id="securesubmit_card_cvv"></div>
                        <span class="help securesubmit_card_csc_description"></span>
                    </div>
                </div>

                <?php if ($this->allow_card_saving == 'yes'): ?>
                    <div class="form-row form-row-wide no-top-margin no-top-padding no-bottom-margin">
                        <p class="form-row form-row-wide securesubmit-save-cards">
                            <input type="checkbox" autocomplete="off" id="save_card" name="save_card" value="true" style="display:inline" />
                            <label for="save_card" style="display: inline;">
                                <?php esc_html_e("Save Credit Card for Future Use", 'wc_securesubmit') ?>
                            </label>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="clear"></div>
        </div>
    </div>
    <input type="hidden" name="securesubmit_token" id="securesubmit_token" />
</fieldset>

<?php
$gift_cards = new WC_Gateway_SecureSubmit_GiftCards();
$gift_cards_allowed = $gift_cards->giftCardsAllowed();

if ($this->allow_gift_cards && $gift_cards_allowed) : // Allow customers to pay with Heartland gift cards ?>
    <fieldset>
          <!-- Start Gift Card -->
          <div class="securesubmit-content gift-card-content">
                <div class="form-row form-row-wide" id="gift-card-row">
                      <label id="gift-card-label" for="gift-card-number"><?php esc_html_e('Use a gift card', 'wc_securesubmit'); ?></label>
                      <div id="gift-card-input">
                            <input type="tel" placeholder="Gift card" id="gift-card-number" value="" class="input-text">
                            <input type="tel" placeholder="PIN" id="gift-card-pin" value="" class="input-text">
                            <p id="gift-card-error"></p>
                            <p id="gift-card-success"></p>
                      </div>
                      <button id="apply-gift-card" class="button" style="visibility:hidden"><?php esc_html_e('Apply', 'wc_securesubmit'); ?></button>
                      <script data-cfasync="false">
                          jQuery("#apply-gift-card").on('click', function (event) {
                              event.preventDefault();
                              window.applyGiftCard();
                          });
                          jQuery("#gift-card-number").on('keyup', function (event) {
                            checkEnableGiftCardApplyButton();
                          });
                          jQuery("#gift-card-pin").on('keyup', function (event) {
                            checkEnableGiftCardApplyButton();
                          });
                          function checkEnableGiftCardApplyButton() {
                            if (jQuery("#gift-card-number").val().length === 19 && [4,8].indexOf(jQuery("#gift-card-pin").val().length) !== -1) {
                                jQuery("#apply-gift-card").css("visibility", "visible");
                            } else {
                                jQuery("#apply-gift-card").css("visibility", "hidden");
                            }
                          }
                      </script>
                </div>
                <div class="clear"></div>
          </div>
          <!-- End Gift Card -->
    </fieldset>
<?php endif; ?>
<script data-cfasync="false">
    window.securesubmitLoadIframes = window.securesubmitLoadIframes || function () {};
    window.securesubmitLoadIframes();
</script>
<?php // Attach the field event handlers when WC refreshes the payment fields ?>
        
<script data-cfasync="false">
    window.securesubmitLoadEvents = window.securesubmitLoadEvents || function () {};
    window.securesubmitLoadEvents();
</script>

