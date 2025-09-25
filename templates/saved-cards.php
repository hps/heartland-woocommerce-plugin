<h2>Saved Credit Cards</h2>

<div class="saved-creditcards-list form-row form-row-wide account-saved-cards-list">
    
    <?php $cards = get_user_meta(get_current_user_id(), '_secure_submit_card', false); ?>
    
    <?php foreach ($cards as $i => $card): ?>

        <div class="clearfix saved-card saved-card-<?php echo esc_attr(strtolower($card['card_type'])); ?>">
            <div class="account-saved-card-logo">
                <div class="card-type-logo"></div>
            </div>
            <div class="saved-card-info">
                <label for="secure_submit_card_<?php echo esc_attr($i); ?>">
                    <p><?php echo esc_html($card['card_type']); ?> ending in <?php echo esc_html($card['last_four']); ?></p>
                    <p><span>Expires on <?php echo esc_html($card['exp_month']); ?>/<?php echo esc_html($card['exp_year']); ?></span></p>
                </label>
            </div>
            
            <form action="#saved-cards" method="POST">
                <?php wp_nonce_field('secure_submit_del_card'); ?>
                <input type="hidden" name="delete_card" value="<?php echo esc_attr($i); ?>">
                <input type="submit" value="Delete Card">
            </form>
           
        </div>
    
    <?php endforeach; ?>
    
</div>