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
