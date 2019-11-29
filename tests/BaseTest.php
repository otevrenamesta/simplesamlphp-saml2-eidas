<?php

declare(strict_types=1);

use OMSAML2\OMSAML2;
use PHPUnit\Framework\TestCase;

final class BaseTest extends TestCase
{

    public function testUnconfiguredThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("IdP Metadata URL not yet configured");
        OMSAML2::getIdPMetadataContents();
    }

    public function testEmptyUnconfiguredUrl(): void
    {
        $this->assertEmpty(OMSAML2::getIdPMetadataUrl());
    }

}