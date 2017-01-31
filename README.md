## SecureSubmit WooCommerce Payment Gateway

This extension allows WooCommerce to use the Heartland Payment Systems Gateway. All card data is tokenized using Heartland's SecureSubmit product.

## Installation

This module installs as a standard WordPress module.

## Usage
Download the contents and extract to your WordPress plugin folder. Activate.

## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request

Changelog
------------

####1.9.2
* Updated PHP SDK to sanitize the card holder details
* validations are added to check the length of first name, last name, state, city, phone, email and zip values
* Test against Wordpress 4.7
* Correct CSS issue with some themes when CC image is scaled improperly
* Fix issue with capturing credit card authorizations

####1.9.1
* Corrected CSS bug with Rosa theam displaying contents of script tags

####1.9.0
* Fix full reversals
* Add consumer authentication (3DSecure) support
* Fix PayPal Express order totals

####1.8.5
* Begin unit/integration testing
* Add basic velocity checking
* Fix "Capture credit card authorization" action when managing orders
* Use reverse instead of void for active authorizations
* Support partial refunds

####1.8.4
* Remove console.log() call

####1.8.3
* Fix Javascript with iFrames during order review

####1.8.2
* Fix PayPal issue with discounts

####1.8.1
* Fix issue with invalid header on plugin activation

####1.8.0
* Heartland Gift and Loyalty support
* Fix issues with MasterPass and production accounts

####1.7.2
* Bug Fix - Resolves WooCommerce PayPal collision
* Improvement - Sets Heartland PayPal enabled flag to false for new installs

####1.7.1
* Bug Fix

####1.7.0
* PayPal as a payment method

####1.6.0
* Restructure SecureSubmit gateway class to reflect MasterPass structure
* Enable capture/void functionality through WooCommerce interface

####1.5.1
* Fix missing Subscriptions class

####1.5.0
* Improve WooCommerce Subscriptions 2.0 support to support new features
* Fix MasterPass lightbox firing when MasterPass not selected
* Fix MasterPass warnings with missing variable and missing address
* Fix Javascript library collision with slug used in wp_enqueue_script
* Fix PHP 5.2 compatibility issues with MasterPass feature

####1.4.0
* MasterPass as a payment method
* Fix issue with submitting order review page

####1.3.5
* Force scripts to be loaded with UTF-8 character set
* Fix JS typo in iframe tokenization
* Remove double tokenization
* Remove token value after resubmitting

####1.3.4
* Remove Heartland logo

####1.3.3
* Change bullet to middle dot

####1.3.2
* Fix bug with WooCommerce checkout form submit handlers
* Add support for subscriptions with free trials ($0 initial payment)

####1.3.1
* Fix bug with Javascript removing single-use token too soon after form submission

####1.3.0
* New option to use gateway-hosted iframes for credit card form fields
* New user experience changes in credit card form
* Fixed basic compatibility issues with WooCommerce Subscriptions 2.0. Support for new features has not been completed.

####1.2.5
* Change CERT gateway url

####1.2.4
* Remove possible failure point of using saved card while requesting to save a card. Uses saved card in this instance.
* Fix SimpleXMLElement serialization error when catching HpsException with gateway faultstring

####1.2.3
* Update certification url to support PCI DSS 3.1

####1.2.2
* Changed how errors are reported back

####1.2.1
* Fix bug with refund method name
* Fix SDK bug with older PHP versions

####1.2.0
* Updated SDK
* Added support for recurring payments through WooCommerce Subscriptions
* Added capability for setting custom error messages

####1.1.1
* Ensure SDK isn't already loaded

####1.1.0
* Adding refund capabilities

####1.0.5
* Clearing token variable after form submission

####1.0.4
* Clearing token if it already exists after error

####1.0.3
* Version only update

####1.0.2
* Fixed optional card-saving

####1.0.1
* Made Card-Saving optional
* Reversed order of Public/Secret Keys

####1.0.0
* Initial Release
