<?php

namespace OMSAML2\Chunks;

use DOMElement;
use SAML2\XML\Chunk;

class EidasRequestedAttributes extends Chunk
{

    const NS_EIDAS = 'http://eidas.europa.eu/saml-extensions';
    const LOCAL_NAME = 'RequestedAttributes';
    const QUALIFIED_NAME = 'eidas:' . self::LOCAL_NAME;
    /**@var $requested_attributes EidasRequestedAttribute[] */
    public $requested_attributes = [];

    public function __construct(DOMElement $xml = null)
    {
        if (!empty($xml) && $xml->localName != self::LOCAL_NAME) {
            $xml = $xml->ownerDocument->getElementsByTagNameNS(self::NS_EIDAS, self::LOCAL_NAME)->item(0);
        }
        if (empty($xml)) {
            return;
        }
        parent::__construct($xml);
        foreach ($xml->getElementsByTagNameNS(self::NS_EIDAS, EidasRequestedAttribute::LOCAL_NAME) as $requestedAttr) {
            $this->requested_attributes[] = new EidasRequestedAttribute($requestedAttr);
        }
    }

    public function toXML(DOMElement $parent): DOMElement
    {
        $e = $parent->ownerDocument->createElementNS(self::NS_EIDAS, self::QUALIFIED_NAME);
        foreach ($this->requested_attributes as $attribute) {
            $attribute->toXML($e);
        }
        $parent->appendChild($e);
        return $e;
    }
}