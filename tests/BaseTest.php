<?php

declare(strict_types=1);

use OMSAML2\OMSAML2;
use PHPUnit\Framework\TestCase;
use SAML2\Constants;
use SAML2\XML\md\EntityDescriptor;

final class BaseTest extends TestCase
{

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
        OMSAML2::setIdPMetadataUrl("https://tnia.eidentita.cz/FPSTS/FederationMetadata/2007-06/FederationMetadata.xml");
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
}