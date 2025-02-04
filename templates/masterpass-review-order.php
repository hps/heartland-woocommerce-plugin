<?php
if (!defined( 'ABSPATH')) {
  exit();
}
?>

<?php $checkoutForm = maybe_unserialize(WC()->session->checkout_form); ?>
<form method="POST">
  <input type="hidden" name="mp_action" value="process_payment" />
  <div class="wp_notice_own"></div>
  <?php wc_print_notices();?>

  <div class="title">
    <h2><?php esc_html_e('Customer details', 'wc_securesubmit'); ?></h2>
  </div>

  <div class="col2-set addresses">
    <div class="col-1">
      <div class="title">
        <h3><?php esc_html_e('Billing Address', 'wc_securesubmit'); ?></h3>
      </div>
      <div class="address">
        <p>
          <?php echo esc_html($masterpass->getFormattedAddress($masterpass->getBuyerData($checkoutForm))); ?>
        </p>
      </div>
    </div>
    <?php if (WC()->cart->show_shipping()): ?>
      <div class="col-2">
        <div class="title">
          <h3><?php esc_html_e('Shipping Address', 'wc_securesubmit'); ?></h3>
        </div>
        <div class="address">
          <p>
            <?php echo esc_html($masterpass->getFormattedAddress($masterpass->getShippingInfo($checkoutForm))); ?>
          </p>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php do_action('woocommerce_after_order_notes', WC()->checkout()); ?>

  <table class="shop_table woocommerce-checkout-review-order-table">
    <thead>
      <tr>
        <th class="product-name"><?php esc_html_e('Product', 'wc_securesubmit'); ?></th>
        <th class="product-total"><?php esc_html_e('Total', 'wc_securesubmit'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php do_action('woocommerce_review_order_before_cart_contents'); ?>
      <?php foreach (WC()->cart->get_cart() as $key => $item): ?>
        <?php $product = apply_filters('woocommerce_cart_item_product', $item['data'], $item, $key); ?>
        <?php if ($product && $product->exists() && $item['quantity'] > 0 && apply_filters('woocommerce_checkout_cart_item_visible', true, $item, $key)): ?>
          <tr class="<?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $item, $key)); ?>">
            <td class="product-name">
              <?php echo esc_html(apply_filters('woocommerce_cart_item_name', $product->get_title(), $item, $key)).'&nbsp;'; ?>
              <?php echo esc_html(apply_filters('woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity">'.sprintf('&times; %s', $item['quantity']).'</strong>', $item, $key)); ?>
              <?php echo esc_html(WC()->cart->get_item_data($item)); ?>
            </td>
            <td class="product-total">
              <?php echo esc_html(apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($product, $item['quantity']), $item, $key)); ?>
            </td>
          </tr>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php do_action('woocommerce_review_order_after_cart_contents'); ?>
    </tbody>
    <tfoot>
      <tr class="cart-subtotal">
        <th><?php esc_html_e('Subtotal', 'wc_securesubmit'); ?></th>
        <td><?php wc_cart_totals_subtotal_html(); ?></td>
      </tr>

      <?php foreach (WC()->cart->get_coupons() as $code => $coupon): ?>
        <tr class="cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
          <th><?php wc_cart_totals_coupon_label($coupon); ?></th>
          <td><?php wc_cart_totals_coupon_html($coupon); ?></td>
        </tr>
      <?php endforeach; ?>

      <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()): ?>
        <?php //WC()->shipping->calculate_shipping(WC()->cart->get_cart());?>
        <?php do_action('woocommerce_review_order_before_shipping'); ?>
        <?php wc_cart_totals_shipping_html(); ?>
        <?php do_action('woocommerce_review_order_after_shipping'); ?>
      <?php endif; ?>

      <?php foreach (WC()->cart->get_fees() as $fee): ?>
        <tr class="fee">
          <th><?php echo esc_html($fee->name); ?></th>
          <td><?php wc_cart_totals_fee_html($fee); ?></td>
        </tr>
      <?php endforeach; ?>

      <?php if (wc_tax_enabled() && 'excl' === WC()->cart->tax_display_cart) : ?>
        <?php if ('itemized' === get_option('woocommerce_tax_total_display')) : ?>
          <?php foreach (WC()->cart->get_tax_totals() as $code => $tax) : ?>
            <tr class="tax-rate tax-rate-<?php echo esc_html(sanitize_title($code)); ?>">
              <th><?php echo esc_html($tax->label); ?></th>
              <td><?php echo wp_kses_post($tax->formatted_amount); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else : ?>
          <tr class="tax-total">
            <th><?php echo esc_html(WC()->countries->tax_or_vat()); ?></th>
            <td><?php wc_cart_totals_taxes_total_html(); ?></td>
          </tr>
        <?php endif; ?>
      <?php endif; ?>

      <?php do_action('woocommerce_review_order_before_order_total'); ?>
      <tr class="order-total">
        <th><?php esc_html_e('Total', 'wc_securesubmit'); ?></th>
        <td><?php wc_cart_totals_order_total_html(); ?></td>
      </tr>
      <?php do_action('woocommerce_review_order_after_order_total'); ?>
    </tfoot>
  </table>

  <div class="clear"></div>
  <p>
    <a class="button" href="<?php echo esc_html(WC()->cart->get_cart_url()); ?>"><?php echo esc_html('Cancel order', 'wc_securesubmit'); ?></a>
    <input type="submit"
           onclick="jQuery(this).attr('disabled', 'disabled').val('Processing'); jQuery(this).parents('form').submit(); return false;"
           class="button checkout-button"
           value="<?php echo esc_html('Place Order', 'wc_securesubmit');?>" />
  </p>
</form>
<div class="clear"></div>
