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
        return DOMDocumentFactory::create()->createElement('root');
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
}