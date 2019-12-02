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
        $exts = new SamlpExtensions(self::getDummyDOMElement());
        $exts->addAllDefaultAttributes();
        $toxml = $exts->toXML();
        $string_output = $toxml->ownerDocument->saveXML($toxml);

        // has sptype
        $this->assertStringContainsString('<eidas:SPType xmlns:eidas="http://eidas.europa.eu/saml-extensions">public</eidas:SPType>', $string_output);
        // has correct number of RequestedAttribute elements of correct namespace
        $this->assertEquals(count($exts->getAllDefaultAttributes()), count($toxml->getElementsByTagNameNS('http://eidas.europa.eu/saml-extensions', 'RequestedAttribute')));
    }

    public function testIncorrectConstructorThrows():void{
        $this->expectException(Exception::class);
        new SamlpExtensions();
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