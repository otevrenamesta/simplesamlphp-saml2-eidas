<?php

use OMSAML2\OMSAML2;
use OMSAML2\SamlpExtensions;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;
use SAML2\AuthnRequest;
use SAML2\Compat\AbstractContainer;
use SAML2\Constants;
use SAML2\DOMDocumentFactory;

class TutorialTest extends TestCase
{
    private $valid_privatekey_data = "-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEA8Eavdm/gIlfaZUmy3RPoEnFnNVKzgMzX8iI9gGG+Md21jVhE
F1P1Dc/vgHmvsyu9Any/l0nrmmruw9WnoTC09SaGJ+rdZCt5umFbOILra3xNoxGM
HrP5VviJHqPQ6Gjv4L/XZfOCdT2LcHc/bLqGV7cpnvv6VSXFrTDRdvVNDDTumJHS
pMXTlnE72JseDkhwJeTzN17uPyGJoCp8AhCBrLXK4BwHg2t8h0MLkY/ZRVuoEwUS
8Wrvblye9Dmb/bLuHVSaDLQzeGgVbmhjbXG3OG9MWerZ2Ux4OJP5si8f/BJ2ZcLt
h7OkY3IUdrvXYRkliNMFgMxInFgqMAMIG1I4VwIDAQABAoIBAQDObsx9Yy0cFILM
lv8XNe0jO09C3uTd+iBmJcjVLiTsTuGWqIkHydg2n7nTlbjApQlkw60P3DCRoOG2
WzAEKwatwQVt8jl3wGp7GP34lXKSDF/fhEnwbwwADtQwAOqRYucFH41+SDKDa/cL
D0jsr2yQnAskTrUKxsMJQ3ITVDl4C6/VPfLJR7cTHkc4ENPI8IsvcyxauYC4433i
vAM9K7gHbp3r3kmGBj9gEnEiYu7LZHVVfxAFKtnin25947PHIFg+ELNM2H1C1d67
WfVIFQyleNGD+OBPQCmX7iGhKjvQqpLUSfsigRvSCZKLDx6ObAyExJ3WvkQtSvV+
HvENlJlhAoGBAP2F3GYI3X2TLgeVsKL6kfsqmf4LhevCjfcOMlqmgvDgVPegAt7R
Np4O4kwGCJefSLpBIB6GXH8miOtcmK+KnUiBBacLrOGHui3hYgE9ko1nwSFamAFB
EaiYKQXJmK8hYpKmfZxWWit0Ykrhh1i2ZT3N07J3mHBk5rdpVbpUjV3pAoGBAPKf
sK/D/Ova9JeX9tIlKTimAZ9HFRc+mwQt4ZalIWyX3LtOTQUj8eaLI1GBPAzxeAOy
we4Y7pk/aOgCJ3oNv+2wtYzjyTHvbawcxEFQpFrAkdaXSXFEtPJYC+dj9I9NnSah
8Q2qKQXDfW4jmLZdU4NpMKbsYG7tw9/Vq2zfabw/AoGAYhVG5qbpYirt9PtBwlwk
3EJoH3Q/1K2JlRqF+rJPGHgCB2d9lMzmT5I8lOMEsfxq+7w0e/rJkFvNPxms7MU5
ApMAJ9eJhBupuRRogUhcCZ8phgjxpBKTjWGJBXcwPhkxdME6+aAi9IrreEL2xSiT
1KxsCbDhZiJzbGQxSYxqwPkCgYBywO7bozH8B9qJ3LlD2Ymunm3D/OXP0a+WAXFi
RAYUC5u+B6HMHZ0rMoHo6dwSLx9ZeHHbAHXRi7k9is9LHje530tvMMmXUaworI5y
agbiWZRgz7tP1HRU7ynqLk+ce9QpUozlrqaqcDTiI/n1vxxh2h3FxaUyskhjlPb4
jo6/FwKBgF9Pci2YXLCC87Y47mgueBdBHWCKXx9nDdiX2YLZCClim5tdqovzGl7y
AuHuqcsjWtJCZGSErb+bep5h5wOveuNebpHbQNVC8cSg1yCZd7mdBaRDojIvOIkA
lBIgv8KvQ/v9/0Pag9j6VyVIh+QMGGWFBd4XDcrzOhPzfiGm7cZi
-----END RSA PRIVATE KEY-----";
    private $valid_public_cert_data = "-----BEGIN CERTIFICATE-----
MIIDeDCCAmCgAwIBAgIQNWjnLn49PRsP6A6n/LUKhzANBgkqhkiG9w0BAQsFADAk
MSIwIAYDVQQDDBlodHRwczovL290ZXZyZW5hbWVzdGEuY3ovMB4XDTE5MTIwMTE0
NDUyOFoXDTIyMTExNTE0NDUyOFowGTEXMBUGA1UEAwwOa29tcHJvbWl0b3Zhbm8w
ggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDwRq92b+AiV9plSbLdE+gS
cWc1UrOAzNfyIj2AYb4x3bWNWEQXU/UNz++Aea+zK70CfL+XSeuaau7D1aehMLT1
JoYn6t1kK3m6YVs4gutrfE2jEYwes/lW+Ikeo9DoaO/gv9dl84J1PYtwdz9suoZX
tyme+/pVJcWtMNF29U0MNO6YkdKkxdOWcTvYmx4OSHAl5PM3Xu4/IYmgKnwCEIGs
tcrgHAeDa3yHQwuRj9lFW6gTBRLxau9uXJ70OZv9su4dVJoMtDN4aBVuaGNtcbc4
b0xZ6tnZTHg4k/myLx/8EnZlwu2Hs6RjchR2u9dhGSWI0wWAzEicWCowAwgbUjhX
AgMBAAGjgbAwga0wCQYDVR0TBAIwADAdBgNVHQ4EFgQUn3n33M4wzRgO5aqKvST8
+LQ6tQYwXwYDVR0jBFgwVoAU8qTNNJw99AQI1ziKzvJesRZ88b2hKKQmMCQxIjAg
BgNVBAMMGWh0dHBzOi8vb3RldnJlbmFtZXN0YS5jei+CFHQnO6RbEfjhgZPmm3e7
LEWiCDNRMBMGA1UdJQQMMAoGCCsGAQUFBwMCMAsGA1UdDwQEAwIHgDANBgkqhkiG
9w0BAQsFAAOCAQEAOfMZJ+30/fBIPJN0zYzEtAMWTrFWNfs9tv0rAdMQlCzuNKLW
rhIi1vJFWJ1SZiJAjHORgg5XpLnzILq3cdo7jKXCGkPJQXq7+WN81aN6fkhe1q+4
Rbcbbl3hCkPKwhdO/QsSixgWSl27Yk/TciVjzAjNqnItWZFp2fy0OPLb4FS/Epit
9PV+RtjYBNZfqpAuLhjedNr8Q3ELqXRPg7qLTtegKjoiOBox/l4iYF8/wwHFnYQL
Q7V1ridqMxUbtndMHy7Rls9nOiZ50gbCR8zO03XWin7R5OGB9yo3IJOwEYEKe4x8
hIHZxfevboDVljM9aHaE35vKSU9D0wE1ak1P9Q==
-----END CERTIFICATE-----";

    public function testStep1(): void
    {
        $test_metadata_url = "https://tnia.eidentita.cz/FPSTS/FederationMetadata/2007-06/FederationMetadata.xml";
        $test_certificate_url = "https://nia.otevrenamesta.cz/tnia.crt";
        OMSAML2::setIdPMetadataUrl($test_metadata_url);
        $this->assertTrue(OMSAML2::validateSignature(OMSAML2::getPublicKeyFromCertificate($test_certificate_url)));

        $login_redirect_urls = OMSAML2::extractSSOLoginUrls();
        $this->assertArrayHasKey(Constants::BINDING_HTTP_REDIRECT, $login_redirect_urls);
    }

    /**
     * @depends testStep1
     */
    public function testStep2(): void
    {
        OMSAML2::setOwnPrivateKeyData($this->valid_privatekey_data);
        OMSAML2::setOwnCertificatePublicKey($this->valid_public_cert_data);

        $request = OMSAML2::generateAuthRequest(
            $this->getDummyContainer(),
            'https://sep.example.com',
            'https://sep.example.com/assertion_consumer_service',
            OMSAML2::extractSSOLoginUrls()[Constants::BINDING_HTTP_REDIRECT],
            OMSAML2::LOA_LOW,
            'minimum'
        );

        // test that correct AuthnRequest was generated, no exception thrown
        $this->assertInstanceOf(AuthnRequest::class, $request);

        $extensions = new SamlpExtensions(null, $request);
        $extensions->addAllDefaultAttributes();
        $extensions->setSPType('public');

        $request_signed = OMSAML2::signDocument($extensions->toXML());

        // validate request signature
        $request_copy = DOMDocumentFactory::fromString($request_signed->ownerDocument->saveXML($request_signed))->documentElement;
        $this->assertTrue((new AuthnRequest($request_copy))->validate(OMSAML2::getOwnCertificatePublicKey()));

        $sso_redirect_url = OMSAML2::getSSORedirectUrl($request_signed);

        // create redirect URL
        $this->assertIsString($sso_redirect_url);
    }

    private function getDummyContainer()
    {
        return new class extends AbstractContainer {
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

            }

            public function postRedirect(string $url, array $data = []): void
            {

            }

            public function getTempDir(): string
            {
                return sys_get_temp_dir();
            }

            public function writeFile(string $filename, string $data, int $mode = null): void
            {
                $file = tempnam($this->getTempDir(), $filename);
                $handle = fopen($file, $mode === null ? 0600 : $mode);
                fwrite($handle, $data);
                fclose($handle);
            }
        };
    }

}