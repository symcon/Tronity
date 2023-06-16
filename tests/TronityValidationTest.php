<?php

declare(strict_types=1);
include_once __DIR__ . '/stubs/Validator.php';
class TronityValidationTest extends TestCaseSymconValidation
{
    public function testValidateTronity(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }
    public function testValidateTronityIO(): void
    {
        $this->validateModule(__DIR__ . '/../TronityIO');
    }
    public function testValidateTronityConfigurator(): void
    {
        $this->validateModule(__DIR__ . '/../TronityConfigurator');
    }
}