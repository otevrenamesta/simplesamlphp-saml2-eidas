<?php

namespace OMSAML2;

use DOMElement;
use DOMNode;
use InvalidArgumentException;
use SAML2\DOMDocumentFactory;
use SAML2\Utils;
use SAML2\XML\Chunk;

/**
 * Class implementing samlp(urn:oasis:names:tc:SAML:2.0:protocol):Extensions with (http://eidas.europa.eu/saml-extensions)
 * eidas:SPType and eidas:RequestedAttributes, for simplesamlphp/saml2 library
 *
 * @package OMSAML2
 */
class SamlpExtensions extends Chunk
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

    /**@var string $sptype */
    private $sptype = 'public';
    /**@var $requested_attributes array */
    private $requested_attributes = [];

    /**
     * Constructor will parse DOMElement containing samlp:Extensions for known attributes (RequestedAttributes, RequestedAttribute and SPType)
     *
     * @param DOMElement|DOMNode $dom
     * @throws InvalidArgumentException
     */
    public function __construct(?DOMelement $dom = null)
    {
        if ($dom === null) {
            return;
        }
        parent::__construct($dom);
        $sptype = $dom->getElementsByTagNameNS('http://eidas.europa.eu/saml-extensions', 'SPType');
        if ($sptype->length > 0) {
            $this->sptype = $sptype->item(0)->nodeValue;
        }
        $req_attrs = $dom->getElementsByTagNameNS('http://eidas.europa.eu/saml-extensions', 'RequestedAttribute');
        for ($counter = 0; $counter < $req_attrs->length; $counter++) {
            $req_attr = $req_attrs->item($counter);
            if (!$req_attr->hasAttributes()) {
                continue;
            }
            $a_Name = $req_attr->attributes->getNamedItem('Name');
            $a_isRequired = $req_attr->attributes->getNamedItem('isRequired') === true;
            $a_NameFormat = $req_attr->attributes->getNamedItem('NameFormat');
            if (empty($a_Name)) {
                continue;
            }
            $this->addRequestedAttributeParams($a_Name->nodeValue, $a_NameFormat->nodeValue, $a_isRequired);
        }
    }

    public function addRequestedAttributeParams(string $Name, ?string $NameFormat = self::NAME_FORMAT_URI, bool $isRequired = false, $AttributeValue = null): SamlpExtensions
    {
        return $this->addRequestedAttribute([
            'Name' => $Name,
            'NameFormat' => $NameFormat,
            'isRequired' => $isRequired,
            'AttributeValue' => $AttributeValue
        ]);
    }

    /**
     * Adds requested attribute by single array definition, array must contain only 'Name' key with string value, all other keys are optional
     * Defaults are NameFormat=${NAME_FORMAT_URI} isRequired=false and no AttributeValue
     *
     * @param array $attribute
     * @return SamlpExtensions
     * @throws InvalidArgumentException if any required argument is missing or if provided argument type is invalid
     */
    public function addRequestedAttribute(array $attribute): SamlpExtensions
    {
        if (empty($attribute['Name'])) {
            throw new InvalidArgumentException("Required attribute Name is missing");
        } else if (!is_string($attribute['Name'])) {
            throw new InvalidArgumentException("Attribute Name must be string");
        }
        if (!empty($attribute['NameFormat']) && !is_string($attribute['NameFormat'])) {
            throw new InvalidArgumentException("Attribute NameFormat must be string");
        }
        if (!empty($attribute['isRequired']) && !is_bool($attribute['isRequired'])) {
            throw new InvalidArgumentException("Attribute isRequired must be boolean");
        }
        if (!empty($attribute['AttributeValue']) && !is_scalar($attribute['AttributeValue'])) {
            throw new InvalidArgumentException("AttributeValue should be primitive type, such as string, number or boolean");
        }

        // set default values for not-defined attributes
        if (empty($attribute['NameFormat'])) {
            $attribute['NameFormat'] = self::NAME_FORMAT_URI;
        }
        if (empty($attribute['isRequired'])) {
            $attribute['isRequired'] = false;
        }

        // add to queue
        $this->requested_attributes[] = $attribute;

        // return $this for chaining
        return $this;
    }

    public function getSPType(): string
    {
        return $this->sptype;
    }

    /**
     * Allowed values for SPType (Service Provider Type) are "public" and "private",
     * invalid values will be replaced by default value "public"
     *
     * @param string $sptype
     * @return SamlpExtensions
     */
    public function setSPType(string $sptype): SamlpExtensions
    {
        $this->sptype = in_array($sptype, ['public', 'private']) ? $sptype : 'public';
        return $this;
    }

    /**
     * Adds all pre-defined attributes (from ${getAllDefaultAttributes}) to attributes,
     * that should be added into DOMElement later (using ${toXML})
     *
     * @return SamlpExtensions
     * @see toXML
     * @see getAllDefaultAttributes
     */
    public function addAllDefaultAttributes(): SamlpExtensions
    {
        foreach ($this->getAllDefaultAttributes() as $attrArray) {
            $this->addRequestedAttribute($attrArray);
        }
        return $this;
    }

    /**
     * Returns array of all pre-defined attributes,
     * each attribute as an array compatible with this class method ${addRequestedAttribute}
     *
     * @return array
     * @see addRequestedAttribute
     */
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
            self::$ATTR_CZMORIS_PHONE_NUMBER,
            self::$ATTR_PLACE_OF_BIRTH
        ];
    }

    public function toXML(DOMElement $parent = null): DOMElement
    {
        // will throw TypeError on empty or non-compatible $this->dom value
        $dom = Utils::copyElement($parent);
        $doc = $dom->ownerDocument;

        $extensions = $doc->createElementNS('urn:oasis:names:tc:SAML:2.0:protocol', 'samlp:Extensions');

        // set SPType always
        $sptype = $doc->createElementNS('http://eidas.europa.eu/saml-extensions', 'eidas:SPType');
        $sptype->nodeValue = $this->sptype;
        $extensions->appendChild($sptype);

        // set eidas:RequestedAttributes if any defined
        if (!empty($this->getRequestedAttributes())) {
            $requested_attributes = $doc->createElementNS('http://eidas.europa.eu/saml-extensions', 'eidas:RequestedAttributes');
            foreach ($this->getRequestedAttributes() as $attrArray) {
                $attrElement = $doc->createElementNS('http://eidas.europa.eu/saml-extensions', 'eidas:RequestedAttribute');
                $attrElement->setAttribute('Name', $attrArray['Name']);
                $attrElement->setAttribute('isRequired', $attrArray['isRequired'] ? 'true' : 'false');
                $attrElement->setAttribute('NameFormat', $attrArray['NameFormat']);
                if (isset($attrArray['AttributeValue']) && (!empty($attrArray['AttributeValue']) || $attrArray['AttributeValue'] === false)) {
                    $attrValueElement = $doc->createElementNS('http://eidas.europa.eu/saml-extensions', 'eidas:AttributeValue');
                    $attrValueElement->nodeValue = (string)$attrArray['AttributeValue'];
                    $attrElement->appendChild($attrValueElement);
                }
                $requested_attributes->appendChild($attrElement);
            }
            $extensions->appendChild($requested_attributes);
        }

        $dom->appendChild($extensions);

        return DOMDocumentFactory::fromString($dom->ownerDocument->saveXML($dom))->documentElement;
    }

    /**
     * Return currently queued RequestedAttributes in form of array configuration
     *
     * @return array
     */
    public function getRequestedAttributes(): array
    {
        return $this->requested_attributes;
    }

    /**
     * Removes all queued RequestedAttributes
     *
     * @return SamlpExtensions
     */
    public function removeAllRequestedAttributes(): SamlpExtensions
    {
        $this->requested_attributes = [];
        return $this;
    }
}