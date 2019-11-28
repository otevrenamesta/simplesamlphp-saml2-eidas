<?php

namespace OMSAML2;

use DOMElement;
use SAML2\Utils;

class NiaExtensions
{
    const NAME_FORMAT_URI = 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri';

    public static $ATTR_PERSON_IDENTIFIER = [
        'Name' => 'http://eidas.europa.eu/attributes/naturalperson/PersonIdentifier'
    ];
    public static $ATTR_CURRENT_GIVEN_NAME = [
        'Name' => 'http://eidas.europa.eu/attributes/naturalperson/CurrentGivenName'
    ];
    public static $ATTR_CURRENT_FAMILY_NAME = [
        'Name' => 'http://eidas.europa.eu/attributes/naturalperson/CurrentFamilyName'
    ];
    public static $ATTR_CURRENT_ADDRESS = [
        'Name' => 'http://eidas.europa.eu/attributes/naturalperson/CurrentAddress'
    ];
    public static $ATTR_DATE_OF_BIRTH = [
        'Name' => 'http://eidas.europa.eu/attributes/naturalperson/DateOfBirth'
    ];
    public static $ATTR_PLACE_OF_BIRTH = [
        'Name' => 'http://eidas.europa.eu/attributes/naturalperson/PlaceOfBirth'
    ];
    public static $ATTR_COUNTRY_CODE_OF_BIRTH = [
        'Name' => 'http://www.stork.gov.eu/1.0/countryCodeOfBirth'
    ];
    public static $ATTR_EMAIL = [
        'Name' => 'http://www.stork.gov.eu/1.0/eMail'
    ];
    public static $ATTR_AGE = [
        'Name' => 'http://www.stork.gov.eu/1.0/age'
    ];
    public static $ATTR_IS_AGE_OVER_18 = [
        'Name' => 'http://www.stork.gov.eu/1.0/isAgeOver',
        'AttributeValue' => 18
    ];
    public static $ATTR_CZMORIS_PHONE_NUMBER = [
        'Name' => 'http://schemas.eidentity.cz/moris/2016/identity/claims/phonenumber'
    ];
    public static $ATTR_CZMORIS_TR_ADRESA_ID = [
        'Name' => 'http://schemas.eidentita.cz/moris/2016/identity/claims/tradresaid'
    ];
    public static $ATTR_CZMORIS_ID_TYPE = [
        'Name' => 'http://schemas.eidentita.cz/moris/2016/identity/claims/idtype'
    ];
    public static $ATTR_CZMORIS_ID_NUMBER = [
        'Name' => 'http://schemas.eidentita.cz/moris/2016/identity/claims/idnumber'
    ];

    private $dom = false;
    private $sptype = 'public';
    private $requested_attributes = [];

    public function __construct(DOMelement $dom)
    {
        $this->dom = $dom;
    }

    public function setSPType($sptype): void
    {
        assert(in_array($sptype, ['private', 'public']));
        $this->sptype = $sptype;
    }

    public function addRequestedAttributeParams($Name, $NameFormat, $isRequired = false, $AttributeValue = false): void
    {
        $this->requested_attributes[] = [
            'Name' => $Name,
            'NameFormat' => $NameFormat,
            'isRequired' => $isRequired,
            'AttributeValue' => $AttributeValue
        ];
    }

    public function addAllDefaultAttributes(): void
    {
        foreach ($this->getAllDefaultAttributes() as $attrArray) {
            $this->addRequestedAttribute($attrArray);
        }
    }

    public function getAllDefaultAttributes(): array
    {
        return [
            self::$ATTR_AGE,
            self::$ATTR_COUNTRY_CODE_OF_BIRTH,
            self::$ATTR_CURRENT_ADDRESS,
            self::$ATTR_CURRENT_FAMILY_NAME,
            self::$ATTR_CURRENT_GIVEN_NAME,
            self::$ATTR_DATE_OF_BIRTH,
            self::$ATTR_EMAIL,
            self::$ATTR_IS_AGE_OVER_18,
            self::$ATTR_PERSON_IDENTIFIER,
            self::$ATTR_CZMORIS_ID_NUMBER,
            self::$ATTR_CZMORIS_ID_TYPE,
            self::$ATTR_CZMORIS_TR_ADRESA_ID,
            self::$ATTR_CZMORIS_PHONE_NUMBER
        ];
    }

    public function addRequestedAttribute(array $attribute): void
    {
        assert(isset($attribute['Name']));
        if (isset($attribute['NameFormat'])) {
            assert(in_array($attribute['NameFormat'], [self::NAME_FORMAT_URI]));
        } else {
            $attribute['NameFormat'] = self::NAME_FORMAT_URI;
        }
        if (isset($attribute['isRequired'])) {
            // allow only boolean
            assert(is_bool($attribute['isRequired']));
        } else {
            $attribute['isRequired'] = false;
        }
        if (isset($attribute['AttributeValue'])) {
            // allow only primitives
            assert(!is_array($attribute['AttributeValue']));
        } else {
            $attribute['AttributeValue'] = false;
        }
        $this->requested_attributes[] = $attribute;
    }

    public function toXML(): DOMElement
    {
        $dom = Utils::copyElement($this->dom);
        $doc = $dom->ownerDocument;

        $extensions = $doc->createElementNS('urn:oasis:names:tc:SAML:2.0:protocol', 'samlp:Extensions');

        // set SPType always
        $sptype = $doc->createElementNS('http://eidas.europa.eu/saml-extensions', 'eidas:SPType');
        $sptype->nodeValue = $this->sptype;
        $extensions->appendChild($sptype);

        // set eidas:RequestedAttributes if any defined
        if (!empty($this->requested_attributes)) {
            $requested_attributes = $doc->createElementNS('http://eidas.europa.eu/saml-extensions', 'eidas:RequestedAttributes');
            foreach ($this->requested_attributes as $attrArray) {
                $attrElement = $doc->createElementNS('http://eidas.europa.eu/saml-extensions', 'eidas:RequestedAttribute');
                $attrElement->setAttribute('Name', $attrArray['Name']);
                $attrElement->setAttribute('isRequired', $attrArray['isRequired'] ? 'true' : 'false');
                $attrElement->setAttribute('NameFormat', $attrArray['NameFormat']);
                if (!empty($attrArray['AttributeValue'])) {
                    $attrValueElement = $doc->createElementNS('http://eidas.europa.eu/saml-extensions', 'eidas:AttributeValue');
                    $attrValueElement->nodeValue = $attrArray['AttributeValue'];
                    $attrElement->appendChild($attrValueElement);
                }
                $requested_attributes->appendChild($attrElement);
            }
            $extensions->appendChild($requested_attributes);
        }

        $dom->appendChild($extensions);

        return Utils::copyElement($dom);
    }
}