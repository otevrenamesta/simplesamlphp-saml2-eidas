<?php

declare(strict_types=1);

use OMSAML2\SamlpExtensions;
use PHPUnit\Framework\TestCase;
use SAML2\DOMDocumentFactory;

final class ExtensionsTest extends TestCase
{
    public function testDefaultAttributesWork(): void
    {
        $exts = new SamlpExtensions(self::getDummyDOMElement());
        $correct_count = count($exts->getAllDefaultAttributes());
        $exts->addAllDefaultAttributes();
        $final_count = count($exts->getRequestedAttributes());
        $this->assertEquals($correct_count, $final_count);
    }

    static function getDummyDOMElement(): DOMElement
    {
        $xmlstring = <<<DOC
<?xml version="1.0"?>
<root></root>
DOC;
        return DOMDocumentFactory::fromString($xmlstring)->documentElement;
    }

    public function testSPType(): void
    {
        $exts = new SamlpExtensions(self::getDummyDOMElement());
        $this->assertIsString($exts->getSPType());
        $exts->setSPType('private');
        $this->assertEquals('private', $exts->getSPType());
        $exts->setSPType('incorrect');
        $this->assertEquals('public', $exts->getSPType());
    }

    public function testAttributesCanBeReset(): void
    {
        $exts = new SamlpExtensions(self::getDummyDOMElement());
        $this->assertEmpty($exts->getRequestedAttributes());
        $exts->addRequestedAttribute(SamlpExtensions::$ATTR_AGE);
        $this->assertEquals(1, count($exts->getRequestedAttributes()));
        $exts->removeAllRequestedAttributes();
        $this->assertEquals(0, count($exts->getRequestedAttributes()));
    }

    public function testAddAttributeByParams_Correct(): void
    {
        $exts = new SamlpExtensions(self::getDummyDOMElement());
        $exts->addRequestedAttributeParams('JustName');
        $this->assertEquals(1, count($exts->getRequestedAttributes()));
        $exts->addRequestedAttributeParams('Name', 'UriFormat');
        $this->assertEquals(2, count($exts->getRequestedAttributes()));
        $exts->addRequestedAttributeParams('Name', 'UriFormat', true);
        $this->assertEquals(3, count($exts->getRequestedAttributes()));
        $exts->addRequestedAttributeParams('Name', 'UriFormat', true, "18");
        $this->assertEquals(4, count($exts->getRequestedAttributes()));
    }

    public function testToXML(): void
    {
        $exts = new SamlpExtensions();
        $exts->addAllDefaultAttributes();
        $toxml = $exts->toXML(self::getDummyDOMElement());
        $string_output = $toxml->ownerDocument->saveXML($toxml);

        // has sptype
        $this->assertStringContainsString('<eidas:SPType xmlns:eidas="http://eidas.europa.eu/saml-extensions">public</eidas:SPType>', $string_output);
        // has correct number of RequestedAttribute elements of correct namespace
        $this->assertEquals(count($exts->getAllDefaultAttributes()), count($toxml->getElementsByTagNameNS('http://eidas.europa.eu/saml-extensions', 'RequestedAttribute')));
    }

    /**
     * Tests all possible incorrect cases with data from ${incorrectAttributesProvider}
     *
     * @dataProvider incorrectAttributesProvider
     * @param $args
     * @see          incorrectAttributesProvider
     */
    public function testAddAtrributeByArray_Incorrect($args): void
    {
        $exts = new SamlpExtensions(self::getDummyDOMElement());
        $this->expectException(InvalidArgumentException::class);

        $exts->addRequestedAttribute($args);
    }

    public function testDOMParserConstructor():void {
        $xml = <<<EOF
<?xml version="1.0"?>
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_daae61e2-a5b8-492f-886f-d5ebfca29aa1" Version="2.0" IssueInstant="2019-12-10T11:14:00Z" Destination="https://tnia.eidentita.cz/FPSTS/saml2/basic" AssertionConsumerServiceURL="https://nia.otevrenamesta.cz/ExternalLogin">
  <saml:Issuer>https://nia.otevrenamesta.cz/</saml:Issuer>
  <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
  <ds:SignedInfo><ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
    <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
  <ds:Reference URI="#_daae61e2-a5b8-492f-886f-d5ebfca29aa1"><ds:Transforms><ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/><ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/></ds:Transforms><ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/><ds:DigestValue>vgxyA4Yrl/Sp1a9+w3cLeFtYl1h1ucO9MuOBjI6TSNA=</ds:DigestValue></ds:Reference></ds:SignedInfo><ds:SignatureValue>nqF/KfUAF9MQPAsmVhjtZPLtD/mdG4koewe7Z5FZJRG/+ZkuFNHYLOHKj7a0wMjKNKLuYPU2Nud0VhRUig5owV/oAEKwRprVT7PWwsAtfljPEyxkOeS4RIwPO0BFGltEcd/HFoeB2WjtrfoY4k8Y0J8MxNcXjcNtq8c2Dyi4OJWB8Q9kPgKIILFSRqjwiFKO9F4qym8V0Wlr6J4iNiSIMFEyyQUlW+hhHf88AP5p8zYiMQVTD+VF6zewc9DQ+1TWoM7vDDBVUPV6GN0GEZUXbjlAI6lUlcbC9COa8luqti/uPWdbvJMTTzqF2UTMWwiSsD23Tq+wuY3UcoIghPFDuQ==</ds:SignatureValue>
<ds:KeyInfo><ds:X509Data><ds:X509Certificate>MIIDdDCCAlygAwIBAgIRAP9sUbldL412M4EpX2fV5PwwDQYJKoZIhvcNAQELBQAwJDEiMCAGA1UEAwwZaHR0cHM6Ly9vdGV2cmVuYW1lc3RhLmN6LzAeFw0xOTEwMDEwOTM1MjhaFw0yMjA5MTUwOTM1MjhaMBQxEjAQBgNVBAMMCXN6cmMtdGVzdDCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAL7C+gGDVHuZSYz2MDq21UB/e3UXiU7L3iJvv8GEzJoi+SvauCU/Ui5oGc/w2MqZ21E463aDadjksRFSB+9z/uw2yNna+Wctg2RoY4CMZNdp/MxunRIT9/U0ecXVHPcqsnTnVykK1QYUv8BaGHLQH0Okk/7+SWHR/MMXcJ7OTI4owm8bFRKN/PFaUtCSNYxhUnP51quWwx6EXoHXZWAq//7YCZP+WL7dcmnql4JxjpZGq4lINqCGA8WXw0EuXbUs5vakl5SFmDMezmiO5IEi1Mk5vmv649p2gUa4qpVPgHkhCqOSRB0BAeEh63hZM05Z+HkDb6R8VYAdFW3ZEL0lXw8CAwEAAaOBsDCBrTAJBgNVHRMEAjAAMB0GA1UdDgQWBBRJIq+FqevCnV5u7guRRfs8k4KbmDBfBgNVHSMEWDBWgBTypM00nD30BAjXOIrO8l6xFnzxvaEopCYwJDEiMCAGA1UEAwwZaHR0cHM6Ly9vdGV2cmVuYW1lc3RhLmN6L4IUdCc7pFsR+OGBk+abd7ssRaIIM1EwEwYDVR0lBAwwCgYIKwYBBQUHAwIwCwYDVR0PBAQDAgeAMA0GCSqGSIb3DQEBCwUAA4IBAQBHSwJPSnC6G+978X/Lk4UMx1QMXmUpvaWnELBMyAcdpRsN9RsOsiJKLYiTAHFLHDwAF0cd/2ZxcwqHu2dX2jwVfOE+Z3UhHEBmvLTPBQq96y62KO4Px7//6gQchK+zER5ZfOP7jAqqziIu+SuI4xJ3zBgEGb4wr3EdQqonNnk6rZh7uJlnCWaoZACg5+S97aK77HaJgk775lFYhDiuQBRD6GKLJoqR1Yvg12RN0X1UbCV5hUF0UEOgHhbNNmZIU9qrKeVKefekDSjzd8xDIU6Ic5w3gKS01CecLQL7/tSpi/s3X+1f4yTjozurqNjUV7gBxcyYRw+4vE4aa4qx/gWd</ds:X509Certificate></ds:X509Data></ds:KeyInfo></ds:Signature>
  <saml:Conditions>
    <saml:AudienceRestriction>
      <saml:Audience>https://nia.otevrenamesta.cz/ExternalLogin</saml:Audience>
    </saml:AudienceRestriction>
  </saml:Conditions>
  <samlp:RequestedAuthnContext Comparison="minimum">
    <saml:AuthnContextClassRef>http://eidas.europa.eu/LoA/low</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
  <samlp:Extensions xmlns:eidas="http://eidas.europa.eu/saml-extensions" id="extensionsTest">
    <eidas:SPType xmlns:eidas="http://eidas.europa.eu/saml-extensions">public</eidas:SPType>
    <eidas:RequestedAttributes xmlns:eidas="http://eidas.europa.eu/saml-extensions">
      <eidas:RequestedAttribute Name="http://www.stork.gov.eu/1.0/age" isRequired="false" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"/>
      <eidas:RequestedAttribute Name="http://www.stork.gov.eu/1.0/countryCodeOfBirth" isRequired="false" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"/>
      <eidas:RequestedAttribute Name="http://eidas.europa.eu/attributes/naturalperson/CurrentAddress" isRequired="false" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"/>
      <eidas:RequestedAttribute Name="http://eidas.europa.eu/attributes/naturalperson/CurrentFamilyName" isRequired="false" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"/>
      <eidas:RequestedAttribute Name="http://eidas.europa.eu/attributes/naturalperson/CurrentGivenName" isRequired="false" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"/>
      <eidas:RequestedAttribute Name="http://eidas.europa.eu/attributes/naturalperson/DateOfBirth" isRequired="false" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"/>
      <eidas:RequestedAttribute Name="http://www.stork.gov.eu/1.0/eMail" isRequired="false" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"/>
      <eidas:RequestedAttribute Name="http://www.stork.gov.eu/1.0/isAgeOver" isRequired="false" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <eidas:AttributeValue>18</eidas:AttributeValue>
      </eidas:RequestedAttribute>
      <eidas:RequestedAttribute Name="http://eidas.europa.eu/attributes/naturalperson/PersonIdentifier" isRequired="false" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"/>
      <eidas:RequestedAttribute Name="http://schemas.eidentita.cz/moris/2016/identity/claims/idnumber" isRequired="false" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"/>
      <eidas:RequestedAttribute Name="http://schemas.eidentita.cz/moris/2016/identity/claims/idtype" isRequired="false" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"/>
      <eidas:RequestedAttribute Name="http://schemas.eidentita.cz/moris/2016/identity/claims/tradresaid" isRequired="false" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"/>
      <eidas:RequestedAttribute Name="http://schemas.eidentity.cz/moris/2016/identity/claims/phonenumber" isRequired="false" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"/>
    </eidas:RequestedAttributes>
  </samlp:Extensions>
</samlp:AuthnRequest>
EOF;
        $dom = DOMDocumentFactory::fromString($xml);
        $extensions = new SamlpExtensions($dom->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:protocol','Extensions')->item(0));
        $this->assertEquals($extensions->getSPType(), 'public');
        $this->assertEquals(13, count($extensions->getRequestedAttributes()));
    }

    public function incorrectAttributesProvider(): array
    {
        return [
            "Name not set" => [[]],
            "Name not string" => [['Name' => 1]],
            "NameFormat not string" => [['Name' => 'Value', 'NameFormat' => 1]],
            "isRequired not boolean" => [['Name' => 'Value', 'isRequired' => "true"]],
            "AttributeValue not scalar" => [['Name' => 'Value', 'AttributeValue' => ["key" => "value"]]]
        ];
    }
}