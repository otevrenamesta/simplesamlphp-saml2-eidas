# Library to use with eIDAS Nodes (IdP/SeP)

[![GitHub license](https://img.shields.io/github/license/otevrenamesta/simplesamlphp-saml2-eidas)](https://github.com/otevrenamesta/simplesamlphp-saml2-eidas/blob/master/LICENSE)
[![Build Status](https://travis-ci.org/otevrenamesta/simplesamlphp-saml2-eidas.svg?branch=master)](https://travis-ci.org/otevrenamesta/simplesamlphp-saml2-eidas)
[![codecov](https://codecov.io/gh/otevrenamesta/simplesamlphp-saml2-eidas/branch/master/graph/badge.svg)](https://codecov.io/gh/otevrenamesta/simplesamlphp-saml2-eidas)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/otevrenamesta/simplesamlphp-saml2-eidas/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/otevrenamesta/simplesamlphp-saml2-eidas/?branch=master)

### About

This library is about simplifying development of PHP applications in need of eIDAS SAML integration, specifically user Authentication using IdP (Identity Providers), based on great and extensive simplesamlphp/saml2 library, which is basis of SimpleSAMLphp project, which is too heavy to integrate within usual applications

### Requirements

  - PHP >= 7.2 (this is given by underlying simplesamlphp/saml2 library)
  - PHP extensions DOM, Zlib, OpenSSL

### Usage

  - (1) Get to know underlying library [simplesamlphp/saml2](https://github.com/simplesamlphp/saml2)
  - (2) Install the library using composer `composer require otevrenamesta/simplesamlphp-saml2-eidas`
  - (3) Implement your container (important methods are `generateId` and depending on your env, file storage methods and logging interface)

```php
// sample generic container implementation

use SAML2\Compat\AbstractContainer;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;

class CustomContainerInterface extends AbstractContainer {
    public function getLogger(): LoggerInterface
    {
        return new TestLogger();
    }
                               
    public function generateId(): string
    {
        return uniqid('_');
    }
                               
    public function debugMessage($message, string $type): void
    {
        $this->getLogger()->debug($message, [$type]);
    }
                               
    public function redirect(string $url, array $data = []): void
    {
        // provide your own redirect method with GET QUERY data
    }
                               
    public function postRedirect(string $url, array $data = []): void
    {
        // provide your own code to do redirect with POST data
    }
                               
    public function getTempDir(): string
    {
        // very simple solution
        return sys_get_temp_dir();
    }
                               
    public function writeFile(string $filename, string $data, int $mode = null): void
    {
        // put contents into file in temporary directory
        $file = tempnam($this->getTempDir(), $filename);
        $handle = fopen($file, $mode === null ? 0600 : $mode);
        fwrite($handle, $data);
        fclose($handle);
    }
}
```

  - (4.a) Use this library to create signed AuthRequest with eIDAS RequestedAttributes and SPType

```php
use OMSAML2\OMSAML2;
use OMSAML2\SamlpExtensions;
use SAML2\Constants;

OMSAML2::setOwnPrivateKeyData("-----BEGIN RSA PRIVATE KEY----- pem key data -----END RSA PRIVATE KEY-----");
OMSAML2::setOwnCertificatePublicKey("-----BEGIN CERTIFICATE----- pem certificate data -----END CERTIFICATE-----");
OMSAML2::setIdPMetadataUrl("https://tnia.eidentita.cz/FPSTS/FederationMetadata/2007-06/FederationMetadata.xml");

$request = OMSAML2::generateAuthRequest(
            new CustomContainerInterface(),
            'https://sep.example.com', // SeP issuer value
            'https://sep.example.com/assertion_consumer_service', // SeP ACS URL, url to redirect user after authentication
            OMSAML2::extractSSOLoginUrls()[Constants::BINDING_HTTP_REDIRECT], // retrieve SSO redirect url from IdP metadata 
            OMSAML2::LOA_LOW, // require minimal level-of-assurance (LoA), ie. authentication methods, that do not guarantee user identity exists in real world
            'minimum'
        );

// create eIDAS samlp:Extensions with 
$extensions = new SamlpExtensions();
// single RequestedAttribute Name="email" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" isRequired="false" without AttributeValue
$extensions->addRequestedAttributeParams('email');
// single RequestedAttribute Name="http://www.stork.gov.eu/1.0/age" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" isRequired="true" without AttributeValue
$extensions->addRequestedAttributeParams('http://www.stork.gov.eu/1.0/age', 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri', true);
// you do not have to do this, public is default type of SeP within this library, allowed values are 'public' or 'private'
$extensions->setSPType('public');

// sign the samlp:AuthnRequest using private key set  in the beginning
$request_signed = OMSAML2::signDocument($extensions->toXML($request->toUnsignedXML()));
// generate final user redirect URL with embedded SAMLRequest containing signed AuthnRequest
$sso_redirect_url = OMSAML2::getSSORedirectUrl($request_signed);
// example result of this action is url like this:
// https://tnia.eidentita.cz/FPSTS/saml2/basic?SAMLRequest=1ZhtU%2BI6FMff%2BymY%2BtKBtMhjR9gpIAgCYnm4ypud0KZtpE1qkkLx09%2B0PIjuenXv7s6uM844PT3n5JeTk39SLr7EgZ9ZIcYxJTVFy6nKl%2FrJBYeBH%2BpGJDxioscIcZGRfoTr6YuaEjGiU8gx1wkMENeFpY%2BNQV%2FP51Q9ZFRQi%2FrKUch%2FR0DOERMSQMl0WzXla9FGRW2hFh3t3NGUzGxPJ32lB%2BcR6hIuIBHSpGrVrJbPqvmJVtDVqvybK5mWBMYEijTKEyLkOgCCYJhD2EZEYAFz1hNoj8aTMUj48mAh2SwlY%2BxRmpTwKEBsjNgKW2hq9p8zcRTmUAyD0Ec5iwbgwP%2FV2kV95dswpZ6WUk%2BhWf2NBBfg2OnC5voYuxI%2FYmhXQ5tvR5ex6%2FU6tz7PUeaCvKqqQK0C6WNz7J4q9ZNMZh%2BO7C5xaJqtCQkl2II%2BfkprMkDCo3bG8F3KsPCCN3JrQFOT3FkUW1lLK5BTBSQjPI%2BRIn44m1rYk2YDytAp4zDLPZgvlrZ5k6wmchBDxEKZqdmtKaeveiGdzoRBwh3KAv7y8V2EF8VCZIV8GiI7y%2FczkRg%2FlvC7FboA3zK2sCtb8gcrJctweqjPc5YZ9CNUb%2BYr7aezZbfs%2Bi1RHZad3qyydorXnp8fmZFlDy7pw3DWEPOgUEuJjoNTw6HU28dXPXNY3m0EPveGdwGfmP60nS9fgvNZ%2BQ6dG2sKqA%2BqgIjBw6x7NkePTbhwy0HQWore7Ea4jnnFz9TxvAVjNS7dDx9KMXDd8rCjRVcjz5jdr%2BebASuu2pVLc1OMbNskXqft3JubsluZPWLA85vuVX45Lrhq0Mj742p7OLuf3Xi9cWU%2BabnmZn6vGcbYwuGN02CDXmkuunewMyPanagsL9kmnjTu7zyGmw21OhJMvbGK%2Fcb5w%2FXUfpoUr8Z24QY9hIulof0DkRaXOl7BNUrjzm2MjTh%2BKoTqukr60bRBysOGWer1wxbE4fVVxyFWMdaum6umNbn0V%2BSxbziV2%2FnqCfeM6Zq1W2zkUGqui41O17FKZ3HR8JaN6ePtZtSDLV65mlO3VjuU%2FqjWJy%2BN9Z0a74QY2aksS30SKBaZJg1CyDBPlC7ABAdRsBedY7%2BmL0VKrnh913FSByHPoYjRUIpiBPrUAD5d75Tou5Hbd29w7CEvY4FIotZ8p1zpQIdGfz1sEiQ3zz5Gkqce%2Bng02YTo%2F6UIo4WPrQtwnGmf95ldCIYXkUA%2Fx%2FltvsxQHm8vNjYXlC1zLl0lieQBC6CLlAzmSSxmyK4pDvS5NCWRbSkZULxzXMqxEmvWSZ31iOFEIH6CyKIREWzTpDaSuwgz4f05wNelh4eVAul%2BgH4o7wOUgGbEpHwJw7YZ4vzzALdhgP1NkuLzMHfwCpFPgtyC4s938Xe3GRpA7P9lTJgbLrqRHwC%2FnGuPdaDZnm5aZS%2FNr17szd9O45e3yCj9100%2FBRz8G%2Bb%2B4TXhloeCBPr4u0TejjGXN0GtBHbmDbB8iAMun0kULD4XsZAH8OfhFQwmBwrE9t%2FDvHmHOfQoQX%2B6MT6%2B%2B3xo%2FU6FflNH%2BOEO%2B3xNPZiOf%2Byon%2FwL&param=2
```

  - (4.b) Use this library to verify IdP metadata or SAMLResponse

```php
use OMSAML2\OMSAML2;
use SAML2\DOMDocumentFactory;
use SAML2\Response;

// validate SAMLResponse
$responseData = base64_decode($_POST['SAMLResponse'], true);
$response_dom = DOMDocumentFactory::fromString($responseData);
$response = new Response($response_dom->documentElement);

OMSAML2::validateSignature(OMSAML2::getPublicKeyFromCertificate("https://nia.otevrenamesta.cz/tnia.crt"), $response);

// validate IdP Metadata signature by pinning certificate
OMSAML2::setIdPMetadataUrl("https://tnia.eidentita.cz/FPSTS/FederationMetadata/2007-06/FederationMetadata.xml");
OMSAML2::validateSignature(OMSAML2::getPublicKeyFromCertificate("https://nia.otevrenamesta.cz/tnia.crt"));
```

And more, if you seek inspiration, look within [tests](https://github.com/otevrenamesta/simplesamlphp-saml2-eidas/tree/master/tests)

### Disclaimer

This library was developed and tested against [Czech IdP NIA](https://www.eidentita.cz/Home), and might not work with others IdPs or eIDAS nodes

### Credits

This library was developed by [Otevřená Města z.s.](https://otevrenamesta.cz/)