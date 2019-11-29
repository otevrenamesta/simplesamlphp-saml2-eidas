<?php

declare(strict_types=1);

namespace OMSAML2;

use Exception;
use SAML2\Constants;
use SAML2\DOMDocumentFactory;
use SAML2\XML\md\EntityDescriptor;
use SAML2\XML\md\IDPSSODescriptor;

/**
 * @package OMSAML2
 */
class OMSAML2
{
    protected static $idp_metadata_url = null;
    protected static $idp_metadata_contents = null;

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
     * Reset state of whole component to default
     */
    public static function reset(): void
    {
        self::$idp_metadata_contents = null;
        self::$idp_metadata_url = null;
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
}