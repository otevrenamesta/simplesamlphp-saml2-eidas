<?php

namespace OMSAML2\Chunks;

use DOMElement;
use SAML2\XML\Chunk;

class EidasRequestedAttribute extends Chunk
{

    const NAME_FORMAT_URI = 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri';
    const NS_EIDAS = 'http://eidas.europa.eu/saml-extensions';
    const LOCAL_NAME = 'RequestedAttribute';

    public $Name = null;
    public $isRequired = false;
    public $NameFormat = self::NAME_FORMAT_URI;
    public $NodeValue = null;

    public function __construct(?DOMElement $xml = null)
    {
        if (empty($xml)) {
            return;
        }
        parent::__construct($xml);
        $this->Name = $xml->attributes->getNamedItem('Name')->nodeValue;
        $isRequired = $xml->attributes->getNamedItem('isRequired');
        $this->isRequired = empty($isRequired) ? false : $isRequired->nodeValue;
        $NameFormat = $xml->attributes->getNamedItem('NameFormat');
        $this->NameFormat = empty($NameFormat) ? self::NAME_FORMAT_URI : $NameFormat->nodeValue;
        $NodeValue = $xml->getElementsByTagNameNS(self::NS_EIDAS, 'AttributeValue')->item(0);
        $this->NodeValue = empty($NodeValue) ? null : $NodeValue->nodeValue;
    }

    public function toXML(DOMElement $parent): DOMElement
    {
        $e = $parent->ownerDocument->createElementNS(self::NS_EIDAS, self::LOCAL_NAME);
        $e->setAttribute('Name', $this->Name);
        $e->setAttribute('NameFormat', $this->NameFormat);
        $e->setAttribute('isRequired', $this->isRequired);
        if ($this->NodeValue != null) {
            $val = $e->ownerDocument->createElementNS(self::NS_EIDAS, 'AttributeValue');
            $val->nodeValue = var_export($this->NodeValue, true);
            $e->appendChild($val);
        }
        $parent->appendChild($e);
        return $e;
    }

}