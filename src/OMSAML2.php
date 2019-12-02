<?php

declare(strict_types=1);

namespace OMSAML2;

use DOMElement;
use Exception;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\AuthnRequest;
use SAML2\Compat\AbstractContainer;
use SAML2\Compat\ContainerSingleton;
use SAML2\Constants;
use SAML2\DOMDocumentFactory;
use SAML2\SignedElement;
use SAML2\Utils;
use SAML2\XML\md\EntityDescriptor;
use SAML2\XML\md\IDPSSODescriptor;
use SAML2\XML\saml\Issuer;

/**
 * @package OMSAML2
 */
class OMSAML2
{
    public const LOA_LOW = 'http://eidas.europa.eu/LoA/low';
    public const LOA_SUBSTANTIAL = 'http://eidas.europa.eu/LoA/substantial';
    public const LOA_HIGH = 'http://eidas.europa.eu/LoA/high';

    /**@var $idp_metadata_url string */
    protected static $idp_metadata_url = null;
    /**@var $idp_metadata_contents string */
    protected static $idp_metadata_contents = null;
    /**@var $idp_certificate XMLSecurityKey */
    protected static $idp_certificate = null;
    /**@var $own_certificate XMLSecurityKey */
    protected static $own_certificate = null;
    /**@var $own_private_key XMLSecurityKey */
    protected static $own_private_key = null;

    /**
     * Reset state of whole component to default
     */
    public static function reset(): void
    {
        self::$idp_metadata_contents = null;
        self::$idp_metadata_url = null;
        self::$own_private_key = null;
        self::$own_certificate = null;
        self::$idp_certificate = null;
    }

    /**
     * Returns associative array of found Logout URLs
     * keys are binding constants, such as Constants::BINDING_HTTP_REDIRECT
     *
     * @param EntityDescriptor|null $idp_descriptor
     * @return array
     * @throws Exception
     * @see Constants
     *
     */
    public static function extractSSOLogoutUrls(?EntityDescriptor $idp_descriptor = null): array
    {
        return self::extractSSOUrls(true, $idp_descriptor);
    }

    /**
     * @param bool $extract_logout_urls
     * @param EntityDescriptor $idp_descriptor
     * @return array
     * @throws Exception
     */
    public static function extractSSOUrls(bool $extract_logout_urls = false, ?EntityDescriptor $idp_descriptor = null): array
    {
        if (empty($idp_descriptor)) {
            $idp_descriptor = self::getIdpDescriptor();
        }

        $idp_sso_descriptor = false;
        if ($idp_descriptor instanceof EntityDescriptor) {
            foreach ($idp_descriptor->getRoleDescriptor() as $role_descriptor) {
                if ($role_descriptor instanceof IDPSSODescriptor) {
                    $idp_sso_descriptor = $role_descriptor;
                }
            }
        }

        $found = [];

        if ($idp_sso_descriptor instanceof IDPSSODescriptor) {
            foreach ($extract_logout_urls ? $idp_sso_descriptor->getSingleLogoutService() : $idp_sso_descriptor->getSingleSignOnService() as $descriptorType) {
                if (empty($descriptorType->getBinding())) {
                    continue;
                }
                $found[$descriptorType->getBinding()] = $descriptorType->getLocation();
            }
        }

        return $found;
    }

    /**
     * Returns EntityDescriptor instance or null, if metadata could not be fetched
     * throws exception in case of invalid or dangerous XML contents
     *
     * @param string|null $metadata_string
     * @return EntityDescriptor|null null if provided string or automatically retrieved string is empty
     * @throws Exception
     */
    public static function getIdpDescriptor(?string $metadata_string = null): ?EntityDescriptor
    {
        if (empty($metadata_string)) {
            $metadata_string = self::getIdPMetadataContents();
            if (empty($metadata_string)) {
                return null;
            }
        }
        $metadata_dom = DOMDocumentFactory::fromString($metadata_string);
        return new EntityDescriptor($metadata_dom->documentElement);
    }

    /**
     * Returns cached or freshly retrieved IdP metadata as a string, or null
     *
     * @param string|null $url
     * @return null|string
     * @throws Exception
     */
    public static function getIdPMetadataContents(?string $url = null): ?string
    {
        if (!empty($url)) {
            self::setIdPMetadataUrl($url);
        }
        if (empty(self::$idp_metadata_contents)) {
            if (empty(self::getIdPMetadataUrl())) {
                throw new Exception("IdP Metadata URL not yet configured");
            }
            $idp_metadata_contents_fresh = file_get_contents(self::getIdPMetadataUrl());
            self::setIdPMetadataContents($idp_metadata_contents_fresh);
        }
        return self::$idp_metadata_contents;
    }

    /**
     * Sets metadata content cache to provided string contents or null, if provided value is empty
     *
     * @param string $contents
     */
    public static function setIdPMetadataContents(?string $contents): void
    {
        $contents = trim($contents);
        self::$idp_metadata_contents = empty($contents) ? null : $contents;
    }

    /**
     * Retrieves currently configured IdP Metadata URL or null if current value is empty
     *
     * @return null|string
     */
    public static function getIdPMetadataUrl(): ?string
    {
        return empty(self::$idp_metadata_url) ? null : self::$idp_metadata_url;
    }

    /**
     * If provided URL is not string or is empty, will set null
     *
     * @param string $url
     */
    public static function setIdPMetadataUrl(string $url): void
    {
        $url = trim($url);
        if ($url != self::$idp_metadata_url) {
            // empty metadata contents cache if URL changes
            self::$idp_metadata_contents = null;
        }
        self::$idp_metadata_url = empty($url) ? null : $url;
    }

    /**
     * Validates signed element (metadata, auth-response, logout-response) signature
     *
     * @param XMLSecurityKey $publicKey
     * @param SignedElement|null $idp_descriptor if not provided, will be retrieved internally by configured URL
     * @return bool
     * @throws Exception
     */
    public static function validateSignature(XMLSecurityKey $publicKey, ?SignedElement $idp_descriptor = null): bool
    {
        if (empty($idp_descriptor)) {
            $idp_descriptor = self::getIdpDescriptor();
        }

        return $idp_descriptor->validate($publicKey);
    }

    /**
     * Creates public key for verifying RSA/SHA256 signature
     *
     * @param string $path absolute path to certificate file or URL from which it can be retrieved
     * @param string $algorithm
     * @param string $type
     * @return XMLSecurityKey
     * @throws Exception
     */
    public static function getPublicKeyFromCertificate(string $path, $algorithm = XMLSecurityKey::RSA_SHA256, $type = 'public'): XMLSecurityKey
    {
        $cert_data = file_get_contents($path);
        $key = new XMLSecurityKey($algorithm, ['type' => $type]);
        $key->loadKey($cert_data, false, true);
        return $key;
    }

    /**
     * Create private key for verifying RSA/SHA256 signature
     *
     * @param string $path absolute path to key file or URL from which it can be retrieved
     * @param string $algorithm
     * @param string $type
     * @return XMLSecurityKey
     * @throws Exception
     */
    public static function getPrivateKeyFromFile(string $path, $algorithm = XMLSecurityKey::RSA_SHA256, $type = 'private'): XMLSecurityKey
    {
        $key_data = file_get_contents($path);
        $key = new XMLSecurityKey($algorithm, ['type' => $type]);
        $key->loadKey($key_data, false, false);
        return $key;
    }

    /**
     * Generates AuthRequest/AuthnRequest using provided information
     *
     * @param AbstractContainer $container
     * @param string $issuer
     * @param string $assertionConsumerServiceURL
     * @param string $idpLoginRedirectUrl
     * @param string $levelOfAssurance
     * @param string $requestedAuthnContextComparison
     * @return AuthnRequest
     * @throws Exception
     */
    public static function generateAuthRequest(AbstractContainer $container, string $issuer, string $assertionConsumerServiceURL, string $idpLoginRedirectUrl, string $levelOfAssurance, string $requestedAuthnContextComparison): AuthnRequest
    {
        ContainerSingleton::setContainer($container);
        $request = new AuthnRequest();

        $issuerImpl = new Issuer();
        $issuerImpl->setValue($issuer);

        $request->setIssuer($issuerImpl);
        $request->setId($container->generateId());
        $request->setAssertionConsumerServiceURL($assertionConsumerServiceURL);
        $request->setDestination($idpLoginRedirectUrl);
        $request->setRequestedAuthnContext([
            'AuthnContextClassRef' => [$levelOfAssurance],
            'Comparison' => $requestedAuthnContextComparison
        ]);

        return $request;
    }

    /**
     * Signs given DOMDocument (ie. AuthRequest) with $privateKey, and optionally adds certificate, if provided.
     * Signature type (ie. RSA/SHA256) is determined by type of XMLSecurityKey provided
     *
     * @param DOMElement $document
     * @param XMLSecurityKey $privateKey
     * @param XMLSecurityKey|null $certificate
     * @return DOMElement
     */
    public static function signDocument(DOMElement $document, XMLSecurityKey $privateKey = null, XMLSecurityKey $certificate = null): DOMElement
    {
        if (empty($privateKey)) {
            $privateKey = self::$own_private_key;
        }

        $insertAfter = $document->firstChild;

        if ($document->getElementsByTagName('Issuer')->length > 0) {
            $insertAfter = $document->getElementsByTagName('Issuer')->item(0)->nextSibling;
        }

        Utils::insertSignature($privateKey, !empty($certificate) ? [$certificate] : [], $document, $insertAfter);

        return $document;
    }

    /**
     * Sets internal private-key from PEM data
     *
     * @param string $private_key_pem_string
     * @param string $algorithm
     * @return bool true if set successfully, false if operation failed (exception will not be thrown)
     * @throws Exception
     */
    public static function setOwnPrivateKeyData(string $private_key_pem_string, $algorithm = XMLSecurityKey::RSA_SHA256): bool
    {
        try {
            self::$own_private_key = new XMLSecurityKey($algorithm, ['type' => 'private']);
            self::$own_private_key->loadKey($private_key_pem_string, false, false);
            self::$own_private_key->signData("abcdef");
            return true;
        } catch (Exception $e) {
            self::$own_private_key = null;
            return false;
        }
    }

    /**
     * Sets internal certificate/public-key from PEM certificate data
     *
     * @param string $certificate_pem_string
     * @param string $algorithm
     * @return bool true if set successfully, false if operation failed (exception will not be thrown)
     * @throws Exception
     */
    public static function setOwnCertificatePublicKey(string $certificate_pem_string, $algorithm = XMLSecurityKey::RSA_SHA256): bool
    {
        try {
            self::$own_certificate = new XMLSecurityKey($algorithm, ['type' => 'public']);
            self::$own_certificate->loadKey($certificate_pem_string, false, true);
            return true;
        } catch (Exception $e) {
            self::$own_certificate = null;
            return false;
        }
    }

    /**
     * Returns stored certificate/public-key in form of XMLSecurityKey
     *
     * @return XMLSecurityKey
     */
    public static function getOwnCertificatePublicKey(): XMLSecurityKey
    {
        return self::$own_certificate;
    }

    /**
     * @param DOMElement $element
     * @return string
     * @throws Exception
     */
    public static function getSSORedirectUrl(DOMElement $element): string
    {
        return self::getSAMLRequestUrl($element, self::extractSSOLoginUrls()[Constants::BINDING_HTTP_REDIRECT]);
    }

    /**
     * @param DOMElement $element
     * @param $base_request_url
     * @return string
     */
    public static function getSAMLRequestUrl(DOMElement $element, $base_request_url): string
    {
        $encoded_element = $element->ownerDocument->saveXML();
        $encoded_element = gzdeflate($encoded_element);
        $encoded_element = base64_encode($encoded_element);
        $encoded_element = urlencode($encoded_element);

        return $base_request_url . (parse_url($base_request_url, PHP_URL_QUERY) ? '&' : '?') . 'SAMLRequest=' . $encoded_element;
    }

    /**
     * Returns associative array of found Login URLs
     * keys are binding constants, such as Constants::BINDING_HTTP_POST
     *
     * @param EntityDescriptor|null $idp_descriptor
     * @return array
     * @throws Exception
     * @see Constants
     *
     */
    public static function extractSSOLoginUrls(?EntityDescriptor $idp_descriptor = null): array
    {
        return self::extractSSOUrls(false, $idp_descriptor);
    }


}