<?php

declare(strict_types=1);

use OMSAML2\OMSAML2;
use PHPUnit\Framework\TestCase;
use SAML2\Constants;
use SAML2\XML\md\EntityDescriptor;

final class BaseTest extends TestCase
{

    private $test_url_1 = "https://nia.eidentita.cz/FPSTS/FederationMetadata/2007-06/FederationMetadata.xml";

    public function testUnconfiguredThrows(): void
    {
        OMSAML2::reset();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("IdP Metadata URL not yet configured");
        OMSAML2::getIdPMetadataContents();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("IdP Metadata URL not yet configured");
        OMSAML2::getIdpDescriptor();
    }

    public function testEmptyUnconfiguredUrl(): void
    {
        OMSAML2::reset();
        $this->assertNull(OMSAML2::getIdPMetadataUrl());
        OMSAML2::setIdPMetadataUrl("");
        $this->assertNull(OMSAML2::getIdPMetadataUrl());
        OMSAML2::setIdPMetadataUrl(" ");
        $this->assertNull(OMSAML2::getIdPMetadataUrl());
    }

    public function testEmptyContents(): void
    {
        OMSAML2::reset();
        OMSAML2::setIdPMetadataContents(" ");
        $this->expectException(Exception::class);
        OMSAML2::getIdPMetadataContents();

        OMSAML2::setIdPMetadataContents("a");
        $this->assertNotEmpty(OMSAML2::getIdPMetadataContents());
    }

    public function testTypeErrors(): void
    {
        OMSAML2::reset();
        $this->expectException(TypeError::class);
        OMSAML2::setIdPMetadataUrl(null);
        OMSAML2::setIdPMetadataUrl(null);
    }

    public function testCorrectUrl(): void
    {
        OMSAML2::reset();
        OMSAML2::setIdPMetadataUrl($this->test_url_1);
        $this->assertNotEmpty(OMSAML2::getIdPMetadataUrl());
        $this->assertNotEmpty(OMSAML2::getIdPMetadataContents());
        $this->assertInstanceOf(EntityDescriptor::class, OMSAML2::getIdpDescriptor());
        $extract_login = OMSAML2::extractSSOUrls();
        $extract_login_2 = OMSAML2::extractSSOLoginUrls();
        $extract_logout = OMSAML2::extractSSOLogoutUrls();
        $this->assertIsArray($extract_login);
        $this->assertIsArray($extract_login_2);
        $this->assertIsArray($extract_logout);
        $this->assertEquals($extract_login, $extract_login_2);
        $this->assertNotEquals($extract_login, $extract_logout);
        $this->assertNotEquals($extract_login_2, $extract_logout);
        $this->assertArrayHasKey(Constants::BINDING_HTTP_REDIRECT, $extract_login);
        $this->assertArrayHasKey(Constants::BINDING_HTTP_REDIRECT, $extract_logout);
        $this->assertArrayHasKey(Constants::BINDING_HTTP_POST, $extract_login);
    }

    public function testCorrectUrl2()
    {
        OMSAML2::reset();
        $this->assertNotEmpty(OMSAML2::getIdPMetadataContents($this->test_url_1));
    }

    public function testNotFetchableMetadata(): void
    {
        OMSAML2::reset();
        OMSAML2::setIdPMetadataUrl("https://httpbin.org/bytes/0");
        $this->assertNull(OMSAML2::getIdpDescriptor());
    }

    public function testMetadataWithoutBinding(): void
    {
        OMSAML2::reset();
        $metadata_string = "<EntityDescriptor xmlns=\"urn:oasis:names:tc:SAML:2.0:metadata\" ID=\"_850519a0-487d-4cc4-976a-e636575bdb2a\" entityID=\"urn:microsoft:cgg2010:fpsts\">
<Signature xmlns=\"http://www.w3.org/2000/09/xmldsig#\">
<SignedInfo>
<CanonicalizationMethod Algorithm=\"http://www.w3.org/2001/10/xml-exc-c14n#\"/>
<SignatureMethod Algorithm=\"http://www.w3.org/2001/04/xmldsig-more#rsa-sha256\"/>
<Reference URI=\"#_850519a0-487d-4cc4-976a-e636575bdb2a\">
<Transforms>
<Transform Algorithm=\"http://www.w3.org/2000/09/xmldsig#enveloped-signature\"/>
<Transform Algorithm=\"http://www.w3.org/2001/10/xml-exc-c14n#\"/>
</Transforms>
<DigestMethod Algorithm=\"http://www.w3.org/2001/04/xmlenc#sha256\"/>
<DigestValue>FtwUtVetCAWX7k8CbHKAODMOOsL4NAiO//q/umpoeY4=</DigestValue>
</Reference>
</SignedInfo>
<SignatureValue>
o+oH6mYwAWD+wJA13Efmk2AAw1kCZkfSBRuPia1BuVvhr/UjBQtyGddZQ7RtjEqxCXIym363SP/dio55JBr4ldv8sVS8zHaxbv6BN7bxXukU8My4K9fJsyvuI+J/Jaa9bS6n7iHKsEM2yoBfRQR8Hp736vRwnRBEwQw66eaZfvAU40CPcskbR6J0EtoD1OS0RIvH/0tLK6V8RFwiw+8FXt6hLix0GZ7NKSvKm4WWd5esvtJjaw7ZxD+SngIVDkwZ5RcbodImCgDNMHQHAD6npDjiWsLKpriSxW5hC8RlfqclNL3k0iLuRbUBgKRW+Xgc+pJ2t+/kQUNJXH6Q29CZhg==
</SignatureValue>
<KeyInfo>
<X509Data>
<X509Certificate>
MIIHcTCCBlmgAwIBAgIDPgUiMA0GCSqGSIb3DQEBCwUAMF8xCzAJBgNVBAYTAkNaMSwwKgYDVQQKDCPEjGVza8OhIHBvxaF0YSwgcy5wLiBbScSMIDQ3MTE0OTgzXTEiMCAGA1UEAxMZUG9zdFNpZ251bSBRdWFsaWZpZWQgQ0EgMjAeFw0xODEyMDQwODMwMjRaFw0xOTEyMjQwODMwMjRaMIGwMQswCQYDVQQGEwJDWjEXMBUGA1UEYRMOTlRSQ1otNzIwNTQ1MDYxNjA0BgNVBAoMLVNwcsOhdmEgesOha2xhZG7DrWNoIHJlZ2lzdHLFryBbScSMIDcyMDU0NTA2XTErMCkGA1UECwwiT2RkxJtsZW7DrSBhcmNoaXRla3R1cnkgYSBhbmFsw716eTERMA8GA1UEAwwIR0dfRlBTVFMxEDAOBgNVBAUTB1MyNzU3MzAwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDYgCRWPorl3y0kBp8H8HkHEvNfQDWmdadewihKkoyOJIslzcceksFajpHOJTAJYOQ8JplUTYhI5wSBSnTtGy/z0K4GlkN1BXZSGsjjpLVvI01hq90iHtQHq8oRPq9PPzB8neidFWJ1Dt1EYK8jo7nSMvFBya20FDQ56udfQzVn26wzC5hwDFRmfrW6bGBDI2cMkApV5aaZY42lYYlj4Gb9xd2b0GGd7FrbOU9ot5lrbztli8QRMyk0JsznofL0FfJR0xDupkoOy80Zuo5DCT0gGTD2HrCSAXn2/r5kDWZ8Ue3axFqDpbd442Thk5o1uKiEiHBuf4R5ozI6OxUWWBfVAgMBAAGjggPiMIID3jAUBgNVHREEDTALoAkGA1UEDaACEwAwggElBgNVHSAEggEcMIIBGDCCAQkGCGeBBgEEARJ4MIH8MIHTBggrBgEFBQcCAjCBxhqBw1RlbnRvIGt2YWxpZmlrb3ZhbnkgY2VydGlmaWthdCBwcm8gZWxla3Ryb25pY2tvdSBwZWNldCBieWwgdnlkYW4gdiBzb3VsYWR1IHMgbmFyaXplbmltIEVVIGMuIDkxMC8yMDE0LlRoaXMgaXMgYSBxdWFsaWZpZWQgY2VydGlmaWNhdGUgZm9yIGVsZWN0cm9uaWMgc2VhbCBhY2NvcmRpbmcgdG8gUmVndWxhdGlvbiAoRVUpIE5vIDkxMC8yMDE0LjAkBggrBgEFBQcCARYYaHR0cDovL3d3dy5wb3N0c2lnbnVtLmN6MAkGBwQAi+xAAQEwgZsGCCsGAQUFBwEDBIGOMIGLMAgGBgQAjkYBATBqBgYEAI5GAQUwYDAuFihodHRwczovL3d3dy5wb3N0c2lnbnVtLmN6L3Bkcy9wZHNfZW4ucGRmEwJlbjAuFihodHRwczovL3d3dy5wb3N0c2lnbnVtLmN6L3Bkcy9wZHNfY3MucGRmEwJjczATBgYEAI5GAQYwCQYHBACORgEGAjCB+gYIKwYBBQUHAQEEge0wgeowOwYIKwYBBQUHMAKGL2h0dHA6Ly93d3cucG9zdHNpZ251bS5jei9jcnQvcHNxdWFsaWZpZWRjYTIuY3J0MDwGCCsGAQUFBzAChjBodHRwOi8vd3d3Mi5wb3N0c2lnbnVtLmN6L2NydC9wc3F1YWxpZmllZGNhMi5jcnQwOwYIKwYBBQUHMAKGL2h0dHA6Ly9wb3N0c2lnbnVtLnR0Yy5jei9jcnQvcHNxdWFsaWZpZWRjYTIuY3J0MDAGCCsGAQUFBzABhiRodHRwOi8vb2NzcC5wb3N0c2lnbnVtLmN6L09DU1AvUUNBMi8wDgYDVR0PAQH/BAQDAgXgMB8GA1UdIwQYMBaAFInoTN+LJjk+1yQuEg565+Yn5daXMIGxBgNVHR8EgakwgaYwNaAzoDGGL2h0dHA6Ly93d3cucG9zdHNpZ251bS5jei9jcmwvcHNxdWFsaWZpZWRjYTIuY3JsMDagNKAyhjBodHRwOi8vd3d3Mi5wb3N0c2lnbnVtLmN6L2NybC9wc3F1YWxpZmllZGNhMi5jcmwwNaAzoDGGL2h0dHA6Ly9wb3N0c2lnbnVtLnR0Yy5jei9jcmwvcHNxdWFsaWZpZWRjYTIuY3JsMB0GA1UdDgQWBBRUzsMtz/2AvJuRl9kD6YdHKN3MVjANBgkqhkiG9w0BAQsFAAOCAQEAcZ6HLYRNWqZzeEZCzfQcp3E2MS7UAOuUYIDo1IXzYXNBEdA4eWpURHls1bjk2M09w6srfWXV/cjX2duPsDtNDDmuLVN8MDHh6Hh4m9G0TUcgO9r9sTzHF80/6Bn4RXIY5B0XmEeVqmYCwyfDZqhNjDRAYMoiTevWr6QoO0e0PIEF7mEl4XSEayfEjGBY+TwPJeZAx02rdhm03pU49xhH7ADUa18yvzgG9ZQpYFpu5n0u0eIVvv3vky8T7WzI6pkzm6AfTdmfKTIW11shKU8sqpRy7a49camgHkWGhN1ucGvvDwTmTawVBMzuLXSEcTkY4rrOc7fJqS2YjljVe5HZfw==
</X509Certificate>
</X509Data>
</KeyInfo>
</Signature>
<RoleDescriptor xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:fed=\"http://docs.oasis-open.org/wsfed/federation/200706\" xsi:type=\"fed:SecurityTokenServiceType\" protocolSupportEnumeration=\"http://docs.oasis-open.org/wsfed/federation/200706\">
<KeyDescriptor use=\"signing\">
<KeyInfo xmlns=\"http://www.w3.org/2000/09/xmldsig#\">
<X509Data>
<X509Certificate>
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
</X509Certificate>
</X509Data>
</KeyInfo>
</KeyDescriptor>
<fed:TokenTypesOffered>
<fed:TokenType Uri=\"http://schemas.microsoft.com/ws/2006/05/identitymodel/tokens/Saml\"/>
</fed:TokenTypesOffered>
<fed:ClaimTypesOffered>
<auth:ClaimType xmlns:auth=\"http://docs.oasis-open.org/wsfed/authorization/200706\" Uri=\"http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress\">
<auth:DisplayName>Email address</auth:DisplayName>
<auth:Description>The email of the subject.</auth:Description>
</auth:ClaimType>
</fed:ClaimTypesOffered>
<fed:SecurityTokenServiceEndpoint>
<wsa:EndpointReference xmlns:wsa=\"http://www.w3.org/2005/08/addressing\">
<wsa:Address>https://nia.eidentita.cz/FPSTS/issue.svc</wsa:Address>
</wsa:EndpointReference>
</fed:SecurityTokenServiceEndpoint>
<fed:PassiveRequestorEndpoint>
<wsa:EndpointReference xmlns:wsa=\"http://www.w3.org/2005/08/addressing\">
<wsa:Address>https://nia.eidentita.cz/FPSTS/default.aspx</wsa:Address>
</wsa:EndpointReference>
</fed:PassiveRequestorEndpoint>
</RoleDescriptor>
<IDPSSODescriptor protocolSupportEnumeration=\"urn:oasis:names:tc:SAML:2.0:protocol\">
<KeyDescriptor use=\"signing\">
<KeyInfo xmlns=\"http://www.w3.org/2000/09/xmldsig#\">
<X509Data>
<X509Certificate>
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
</X509Certificate>
</X509Data>
</KeyInfo>
</KeyDescriptor>
<SingleLogoutService Binding=\"\" Location=\"https://nia.eidentita.cz/FPSTS/saml2/basic\"/>
<SingleSignOnService Binding=\"\" Location=\"https://nia.eidentita.cz/FPSTS/saml2/basic\"/>
<SingleSignOnService Binding=\"\" Location=\"https://nia.eidentita.cz/FPSTS/saml2/basic\"/>
</IDPSSODescriptor>
</EntityDescriptor>";
        OMSAML2::setIdPMetadataContents($metadata_string);
        $login_urls = OMSAML2::extractSSOLoginUrls();
        $logout_urls = OMSAML2::extractSSOLogoutUrls();
        $this->assertIsArray($login_urls);
        $this->assertIsArray($logout_urls);
        $this->assertEmpty($login_urls);
        $this->assertEmpty($logout_urls);
    }
}