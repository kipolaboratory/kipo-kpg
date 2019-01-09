# PHP Kipo KPG Library: make payment easy with kipo

[![Latest Stable Version](https://poser.pugx.org/kipolaboratory/kipo-kpg/v/stable)](https://packagist.org/packages/kipolaboratory/kipo-kpg)
[![](https://img.shields.io/github/license/kipolaboratory/kipo-kpg.svg)](https://github.com/kipolaboratory/kipo-kpg/blob/master/LICENSE)
[![](https://img.shields.io/travis/kipolaboratory/kipo-kpg.svg)](https://travis-ci.org/kipolaboratory/kipo-kpg/)
[![](https://img.shields.io/packagist/dt/kipolaboratory/kipo-kpg.svg)](https://github.com/kipolaboratory/kipo-kpg/releases/)

PHP Kipo KPG Library make it easy to stablish payment with kipo gateway.

![KipoPay Company logo](https://kipopay.com/img/fr.png)

---

- [Installation](#installation)
- [Quick Start and Examples](#quick-start-and-examples)
- [Properties](#properties)
- [HTML Form to transfer user to KPG](#html-form-to-transfer-user-to-kpg)
---

### Installation

To install PHP Kipo KPG Library, simply:

    $ composer require kipolaboratory/kipo-kpg

For latest commit version:

    $ composer require kipolaboratory/kipo-kpg @dev

### Requirements

PHP Kipo KPG Library works with PHP 5.6, 7.0, 7.1, 7.2.

### Quick Start and Examples
Initial Kipo KPG and request shoping key from kipo server.
```php
use kipolaboratory\KipoPay\KipoKPG;

/**
 * Initial Kipo Library and craete object from that class
 * Merchant key is merchant phone number
 */
$kipo = new KipoKPG(['merchant_key' => '09*********']);

/**
 * Replace "YOUR CALLBACK URL" and "AMOUNT" with what you want
 * KPGInitiate return ARRAY for result
 * Successful - ['status' => true, 'shopping_key' => SHOPING_KEY]
 * Failed - ['status' => false, 'message' => ERROR_CODE]
 */
$kpg_initiate = $kipo->KPGInitiate(AMOUNT, 'YOUR CALLBACK URL');

if ($kpg_initiate['status']) {
    /**
     * Store $kpg_initiate['shopping_key'] to session to verfiy
     * payment after user came back from gateway
     *
     * Call renderForm function to render a html form and send
     * user to Kipo KPG Gateway (you can create this form manually
     * where you want - form example is at the end of Quick Start
     */
     $kipo->renderForm($kpg_initiate['shopping_key']);
} else {
    /**
     * Show error to user
     *
     * You can call getErrorMessage and send error code to that
     * and get error message
     * $kipo->getErrorMessage(ERROR_CODE)
     */
}
```

Verify payment after user return back to *CALLBACK URL*
```php
/**
 * Replace "SHOPPING_KEY" with your SHOPPING_KEY that you taken from
 * Initiate function
 *
 * KPGInquery return ARRAY for result
 * Successful - ['status' => true, 'referent_code' => REFERENT_CODE]
 * Failed - ['status' => false, 'message' => ERROR_CODE]
 */
$kpg_inquery = $kipo->KPGInquery(SHOPPING_KEY);
```

```php
// Get shopping key after KPGInitiate called
$curl->getShoppingKey();
```

```php
// Get referent code after KPGInquery called
$curl->getReferentCode();
```

### Properties
```php
// URL of Kipo KPG - http://webgate.kipopay.com/
// Shopping key must post to this url with SK name
$kipo->kipo_webgate_url;
```

### HTML Form to transfer user to KPG
```html
<form id="kipopay-gateway" method="post" action="KIPO_WEBGATE_URL" style="display: none;">
    <input type="hidden" id="sk" name="sk" value="SHOPING_KEY"/>
</form>
<script language="javascript">document.forms['kipopay-gateway'].submit();</script>
```
    