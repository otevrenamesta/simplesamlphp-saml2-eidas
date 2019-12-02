<?php

declare(strict_types=1);

use OMSAML2\OMSAML2;
use PHPUnit\Framework\TestCase;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Compat\ContainerSingleton;
use SAML2\Compat\MockContainer;
use SAML2\Constants;
use SAML2\XML\md\EntityDescriptor;

final class BaseTest extends TestCase
{

    private $test_url_1 = "https://nia.eidentita.cz/FPSTS/FederationMetadata/2007-06/FederationMetadata.xml";
    private $test_url_contents = "<EntityDescriptor xmlns=\"urn:oasis:names:tc:SAML:2.0:metadata\" ID=\"_9a80361c-7629-4562-9154-dd271a6c257f\" entityID=\"urn:microsoft:cgg2010:fpsts\">
<Signature xmlns=\"http://www.w3.org/2000/09/xmldsig#\">
<SignedInfo>
<CanonicalizationMethod Algorithm=\"http://www.w3.org/2001/10/xml-exc-c14n#\"/>
<SignatureMethod Algorithm=\"http://www.w3.org/2001/04/xmldsig-more#rsa-sha256\"/>
<Reference URI=\"#_9a80361c-7629-4562-9154-dd271a6c257f\">
<Transforms>
<Transform Algorithm=\"http://www.w3.org/2000/09/xmldsig#enveloped-signature\"/>
<Transform Algorithm=\"http://www.w3.org/2001/10/xml-exc-c14n#\"/>
</Transforms>
<DigestMethod Algorithm=\"http://www.w3.org/2001/04/xmlenc#sha256\"/>
<DigestValue>u4FCfMLFoZSuzZGfooqhaW1kWB8WzxfV/A2m54VL8Ws=</DigestValue>
</Reference>
</SignedInfo>
<SignatureValue>
NQir7nYgGJ0ET5aOG0FBC8egdroElzTazEXhqrnvhWdzjV2Y3qNj+6fEac66FWx3x/pxdk1I83y4TyeBYhLgyaIrCHA8/5NBAgxVAkv0949yGMWT9JuXgyyUJHrpfzs3BsmeVWDLSJAWXQ3LlvnF+6bpUvPRa+3nYC1SvrFNvnAfwUj6S3Liau9bOqFivTkidLWfenpMGZ6FCEggKbJdIhfFR2IH1+1QCUbYZC7nk3nsGY+oghG0n00XBbcmajXwAqgvDNgkm3FiAZBvBxQNOG08fUIdk9y+iG07p4S5wpo0/E32OjSU2Fi9oIOp2an2OeCEHlgSz0tqMNN0uvdYGQ==
</SignatureValue>
<KeyInfo>
<X509Data>
<X509Certificate>
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
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
MIIHcTCCBlmgAwIBAgIDPgUiMA0GCSqGSIb3DQEBCwUAMF8xCzAJBgNVBAYTAkNaMSwwKgYDVQQKDCPEjGVza8OhIHBvxaF0YSwgcy5wLiBbScSMIDQ3MTE0OTgzXTEiMCAGA1UEAxMZUG9zdFNpZ251bSBRdWFsaWZpZWQgQ0EgMjAeFw0xODEyMDQwODMwMjRaFw0xOTEyMjQwODMwMjRaMIGwMQswCQYDVQQGEwJDWjEXMBUGA1UEYRMOTlRSQ1otNzIwNTQ1MDYxNjA0BgNVBAoMLVNwcsOhdmEgesOha2xhZG7DrWNoIHJlZ2lzdHLFryBbScSMIDcyMDU0NTA2XTErMCkGA1UECwwiT2RkxJtsZW7DrSBhcmNoaXRla3R1cnkgYSBhbmFsw716eTERMA8GA1UEAwwIR0dfRlBTVFMxEDAOBgNVBAUTB1MyNzU3MzAwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDYgCRWPorl3y0kBp8H8HkHEvNfQDWmdadewihKkoyOJIslzcceksFajpHOJTAJYOQ8JplUTYhI5wSBSnTtGy/z0K4GlkN1BXZSGsjjpLVvI01hq90iHtQHq8oRPq9PPzB8neidFWJ1Dt1EYK8jo7nSMvFBya20FDQ56udfQzVn26wzC5hwDFRmfrW6bGBDI2cMkApV5aaZY42lYYlj4Gb9xd2b0GGd7FrbOU9ot5lrbztli8QRMyk0JsznofL0FfJR0xDupkoOy80Zuo5DCT0gGTD2HrCSAXn2/r5kDWZ8Ue3axFqDpbd442Thk5o1uKiEiHBuf4R5ozI6OxUWWBfVAgMBAAGjggPiMIID3jAUBgNVHREEDTALoAkGA1UEDaACEwAwggElBgNVHSAEggEcMIIBGDCCAQkGCGeBBgEEARJ4MIH8MIHTBggrBgEFBQcCAjCBxhqBw1RlbnRvIGt2YWxpZmlrb3ZhbnkgY2VydGlmaWthdCBwcm8gZWxla3Ryb25pY2tvdSBwZWNldCBieWwgdnlkYW4gdiBzb3VsYWR1IHMgbmFyaXplbmltIEVVIGMuIDkxMC8yMDE0LlRoaXMgaXMgYSBxdWFsaWZpZWQgY2VydGlmaWNhdGUgZm9yIGVsZWN0cm9uaWMgc2VhbCBhY2NvcmRpbmcgdG8gUmVndWxhdGlvbiAoRVUpIE5vIDkxMC8yMDE0LjAkBggrBgEFBQcCARYYaHR0cDovL3d3dy5wb3N0c2lnbnVtLmN6MAkGBwQAi+xAAQEwgZsGCCsGAQUFBwEDBIGOMIGLMAgGBgQAjkYBATBqBgYEAI5GAQUwYDAuFihodHRwczovL3d3dy5wb3N0c2lnbnVtLmN6L3Bkcy9wZHNfZW4ucGRmEwJlbjAuFihodHRwczovL3d3dy5wb3N0c2lnbnVtLmN6L3Bkcy9wZHNfY3MucGRmEwJjczATBgYEAI5GAQYwCQYHBACORgEGAjCB+gYIKwYBBQUHAQEEge0wgeowOwYIKwYBBQUHMAKGL2h0dHA6Ly93d3cucG9zdHNpZ251bS5jei9jcnQvcHNxdWFsaWZpZWRjYTIuY3J0MDwGCCsGAQUFBzAChjBodHRwOi8vd3d3Mi5wb3N0c2lnbnVtLmN6L2NydC9wc3F1YWxpZmllZGNhMi5jcnQwOwYIKwYBBQUHMAKGL2h0dHA6Ly9wb3N0c2lnbnVtLnR0Yy5jei9jcnQvcHNxdWFsaWZpZWRjYTIuY3J0MDAGCCsGAQUFBzABhiRodHRwOi8vb2NzcC5wb3N0c2lnbnVtLmN6L09DU1AvUUNBMi8wDgYDVR0PAQH/BAQDAgXgMB8GA1UdIwQYMBaAFInoTN+LJjk+1yQuEg565+Yn5daXMIGxBgNVHR8EgakwgaYwNaAzoDGGL2h0dHA6Ly93d3cucG9zdHNpZ251bS5jei9jcmwvcHNxdWFsaWZpZWRjYTIuY3JsMDagNKAyhjBodHRwOi8vd3d3Mi5wb3N0c2lnbnVtLmN6L2NybC9wc3F1YWxpZmllZGNhMi5jcmwwNaAzoDGGL2h0dHA6Ly9wb3N0c2lnbnVtLnR0Yy5jei9jcmwvcHNxdWFsaWZpZWRjYTIuY3JsMB0GA1UdDgQWBBRUzsMtz/2AvJuRl9kD6YdHKN3MVjANBgkqhkiG9w0BAQsFAAOCAQEAcZ6HLYRNWqZzeEZCzfQcp3E2MS7UAOuUYIDo1IXzYXNBEdA4eWpURHls1bjk2M09w6srfWXV/cjX2duPsDtNDDmuLVN8MDHh6Hh4m9G0TUcgO9r9sTzHF80/6Bn4RXIY5B0XmEeVqmYCwyfDZqhNjDRAYMoiTevWr6QoO0e0PIEF7mEl4XSEayfEjGBY+TwPJeZAx02rdhm03pU49xhH7ADUa18yvzgG9ZQpYFpu5n0u0eIVvv3vky8T7WzI6pkzm6AfTdmfKTIW11shKU8sqpRy7a49camgHkWGhN1ucGvvDwTmTawVBMzuLXSEcTkY4rrOc7fJqS2YjljVe5HZfw==
</X509Certificate>
</X509Data>
</KeyInfo>
</KeyDescriptor>
<SingleLogoutService Binding=\"urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect\" Location=\"https://nia.eidentita.cz/FPSTS/saml2/basic\"/>
<SingleSignOnService Binding=\"urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect\" Location=\"https://nia.eidentita.cz/FPSTS/saml2/basic\"/>
<SingleSignOnService Binding=\"urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST\" Location=\"https://nia.eidentita.cz/FPSTS/saml2/basic\"/>
</IDPSSODescriptor>
</EntityDescriptor>";
    private $nia_cert = "-----BEGIN CERTIFICATE-----
MIIHcTCCBlmgAwIBAgIDPgUiMA0GCSqGSIb3DQEBCwUAMF8xCzAJBgNVBAYTAkNa
MSwwKgYDVQQKDCPEjGVza8OhIHBvxaF0YSwgcy5wLiBbScSMIDQ3MTE0OTgzXTEi
MCAGA1UEAxMZUG9zdFNpZ251bSBRdWFsaWZpZWQgQ0EgMjAeFw0xODEyMDQwODMw
MjRaFw0xOTEyMjQwODMwMjRaMIGwMQswCQYDVQQGEwJDWjEXMBUGA1UEYRMOTlRS
Q1otNzIwNTQ1MDYxNjA0BgNVBAoMLVNwcsOhdmEgesOha2xhZG7DrWNoIHJlZ2lz
dHLFryBbScSMIDcyMDU0NTA2XTErMCkGA1UECwwiT2RkxJtsZW7DrSBhcmNoaXRl
a3R1cnkgYSBhbmFsw716eTERMA8GA1UEAwwIR0dfRlBTVFMxEDAOBgNVBAUTB1My
NzU3MzAwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDYgCRWPorl3y0k
Bp8H8HkHEvNfQDWmdadewihKkoyOJIslzcceksFajpHOJTAJYOQ8JplUTYhI5wSB
SnTtGy/z0K4GlkN1BXZSGsjjpLVvI01hq90iHtQHq8oRPq9PPzB8neidFWJ1Dt1E
YK8jo7nSMvFBya20FDQ56udfQzVn26wzC5hwDFRmfrW6bGBDI2cMkApV5aaZY42l
YYlj4Gb9xd2b0GGd7FrbOU9ot5lrbztli8QRMyk0JsznofL0FfJR0xDupkoOy80Z
uo5DCT0gGTD2HrCSAXn2/r5kDWZ8Ue3axFqDpbd442Thk5o1uKiEiHBuf4R5ozI6
OxUWWBfVAgMBAAGjggPiMIID3jAUBgNVHREEDTALoAkGA1UEDaACEwAwggElBgNV
HSAEggEcMIIBGDCCAQkGCGeBBgEEARJ4MIH8MIHTBggrBgEFBQcCAjCBxhqBw1Rl
bnRvIGt2YWxpZmlrb3ZhbnkgY2VydGlmaWthdCBwcm8gZWxla3Ryb25pY2tvdSBw
ZWNldCBieWwgdnlkYW4gdiBzb3VsYWR1IHMgbmFyaXplbmltIEVVIGMuIDkxMC8y
MDE0LlRoaXMgaXMgYSBxdWFsaWZpZWQgY2VydGlmaWNhdGUgZm9yIGVsZWN0cm9u
aWMgc2VhbCBhY2NvcmRpbmcgdG8gUmVndWxhdGlvbiAoRVUpIE5vIDkxMC8yMDE0
LjAkBggrBgEFBQcCARYYaHR0cDovL3d3dy5wb3N0c2lnbnVtLmN6MAkGBwQAi+xA
AQEwgZsGCCsGAQUFBwEDBIGOMIGLMAgGBgQAjkYBATBqBgYEAI5GAQUwYDAuFiho
dHRwczovL3d3dy5wb3N0c2lnbnVtLmN6L3Bkcy9wZHNfZW4ucGRmEwJlbjAuFiho
dHRwczovL3d3dy5wb3N0c2lnbnVtLmN6L3Bkcy9wZHNfY3MucGRmEwJjczATBgYE
AI5GAQYwCQYHBACORgEGAjCB+gYIKwYBBQUHAQEEge0wgeowOwYIKwYBBQUHMAKG
L2h0dHA6Ly93d3cucG9zdHNpZ251bS5jei9jcnQvcHNxdWFsaWZpZWRjYTIuY3J0
MDwGCCsGAQUFBzAChjBodHRwOi8vd3d3Mi5wb3N0c2lnbnVtLmN6L2NydC9wc3F1
YWxpZmllZGNhMi5jcnQwOwYIKwYBBQUHMAKGL2h0dHA6Ly9wb3N0c2lnbnVtLnR0
Yy5jei9jcnQvcHNxdWFsaWZpZWRjYTIuY3J0MDAGCCsGAQUFBzABhiRodHRwOi8v
b2NzcC5wb3N0c2lnbnVtLmN6L09DU1AvUUNBMi8wDgYDVR0PAQH/BAQDAgXgMB8G
A1UdIwQYMBaAFInoTN+LJjk+1yQuEg565+Yn5daXMIGxBgNVHR8EgakwgaYwNaAz
oDGGL2h0dHA6Ly93d3cucG9zdHNpZ251bS5jei9jcmwvcHNxdWFsaWZpZWRjYTIu
Y3JsMDagNKAyhjBodHRwOi8vd3d3Mi5wb3N0c2lnbnVtLmN6L2NybC9wc3F1YWxp
ZmllZGNhMi5jcmwwNaAzoDGGL2h0dHA6Ly9wb3N0c2lnbnVtLnR0Yy5jei9jcmwv
cHNxdWFsaWZpZWRjYTIuY3JsMB0GA1UdDgQWBBRUzsMtz/2AvJuRl9kD6YdHKN3M
VjANBgkqhkiG9w0BAQsFAAOCAQEAcZ6HLYRNWqZzeEZCzfQcp3E2MS7UAOuUYIDo
1IXzYXNBEdA4eWpURHls1bjk2M09w6srfWXV/cjX2duPsDtNDDmuLVN8MDHh6Hh4
m9G0TUcgO9r9sTzHF80/6Bn4RXIY5B0XmEeVqmYCwyfDZqhNjDRAYMoiTevWr6Qo
O0e0PIEF7mEl4XSEayfEjGBY+TwPJeZAx02rdhm03pU49xhH7ADUa18yvzgG9ZQp
YFpu5n0u0eIVvv3vky8T7WzI6pkzm6AfTdmfKTIW11shKU8sqpRy7a49camgHkWG
hN1ucGvvDwTmTawVBMzuLXSEcTkY4rrOc7fJqS2YjljVe5HZfw==
-----END CERTIFICATE-----";
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

    public function testCreatePublicKeyAndVerifySignature()
    {
        $tempfile = tmpfile();
        fwrite($tempfile, $this->nia_cert);

        $pubkey = OMSAML2::getPublicKeyFromCertificate(stream_get_meta_data($tempfile)['uri']);

        OMSAML2::setIdPMetadataContents($this->test_url_contents);
        $this->assertFalse(OMSAML2::validateSignature($pubkey));
    }

    public function testCreatePublicKeyAndVerifySignature_2()
    {
        $tempfile = tmpfile();
        fwrite($tempfile, $this->nia_cert);

        $pubkey = OMSAML2::getPublicKeyFromCertificate(stream_get_meta_data($tempfile)['uri']);

        $this->assertFalse(OMSAML2::validateSignature($pubkey, OMSAML2::getIdpDescriptor($this->test_url_contents)));
    }

    public function testCreatePrivateKey()
    {

        $tempfile = tmpfile();
        fwrite($tempfile, $this->valid_privatekey_data);

        $privkey = OMSAML2::getPrivateKeyFromFile(stream_get_meta_data($tempfile)['uri']);

        $this->assertEquals(XMLSecurityKey::RSA_SHA256, $privkey->getAlgorithm());
    }

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

    public function testInvalidPrivateKeyData(): void
    {
        ContainerSingleton::setContainer(new MockContainer());
        $this->assertFalse(OMSAML2::setOwnPrivateKeyData("invalid privkey content"));
    }

    public function testInvalidCertificateData(): void
    {
        $this->assertFalse(OMSAML2::setOwnCertificatePublicKey("invalid certificate content"));
    }

    public function testNullOnUnconfiguredCertificate(): void
    {
        OMSAML2::reset();
        $this->assertNull(OMSAML2::getOwnCertificatePublicKey());
    }

    public function testNullOnUnconfiguredPrivateKey(): void
    {
        OMSAML2::reset();
        $this->assertNull(OMSAML2::getOwnPrivateKey());
    }

    public function testValidateSignatureWithoutPubkeyParam(): void
    {
        OMSAML2::reset();
        OMSAML2::setIdPMetadataUrl($this->test_url_1);
        OMSAML2::setIdpCertificate(null, $this->nia_cert);
        $this->assertTrue(OMSAML2::validateSignature());
    }

    public function testSetInvalidIdpCertificateData():void {
        OMSAML2::reset();
        $this->assertFalse(OMSAML2::setIdpCertificate(""));
    }

    public function testMetadataWithoutBinding(): void
    {
        OMSAML2::reset();
        $modified_contents = str_replace(Constants::BINDING_HTTP_REDIRECT, "", $this->test_url_contents);
        $modified_contents = str_replace(Constants::BINDING_HTTP_POST, "", $modified_contents);
        OMSAML2::setIdPMetadataContents($modified_contents);
        $login_urls = OMSAML2::extractSSOLoginUrls();
        $logout_urls = OMSAML2::extractSSOLogoutUrls();
        $this->assertIsArray($login_urls);
        $this->assertIsArray($logout_urls);
        $this->assertEmpty($login_urls);
        $this->assertEmpty($logout_urls);
    }
}