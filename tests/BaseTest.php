<?php

declare(strict_types=1);

use OMSAML2\OMSAML2;
use PHPUnit\Framework\TestCase;

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

    public function testEmptyContents(): void {
        OMSAML2::reset();
        OMSAML2::setIdPMetadataContents(" ");
        $this->expectException(Exception::class);
        OMSAML2::getIdPMetadataContents();

        OMSAML2::setIdPMetadataContents("a");
        $this->assertNotEmpty(OMSAML2::getIdPMetadataContents());
    }

    public function testTypeErrors(): void {
        OMSAML2::reset();
        $this->expectException(TypeError::class);
        OMSAML2::setIdPMetadataUrl(null);
        OMSAML2::setIdPMetadataUrl(null);
    }

}