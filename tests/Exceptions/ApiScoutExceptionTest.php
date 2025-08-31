<?php

namespace jcnghm\ApiScout\Tests\Exceptions;

use jcnghm\ApiScout\Tests\TestCase;
use jcnghm\ApiScout\Exceptions\ApiScoutException;

class ApiScoutExceptionTest extends TestCase
{
    public const TEST_ERROR_MESSAGE = 'Test error message';
    public function test_exception_creation()
    {
        $message = self::TEST_ERROR_MESSAGE;
        $exception = new ApiScoutException($message);
        
        $this->assertInstanceOf(ApiScoutException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function test_exception_with_code()
    {
        $message = self::TEST_ERROR_MESSAGE;
        $code = 500;
        $exception = new ApiScoutException($message, $code);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function test_exception_with_previous_exception()
    {
        $previous_exception = new \Exception('Previous error');
        $message = self::TEST_ERROR_MESSAGE;
        $exception = new ApiScoutException($message, 0, $previous_exception);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertSame($previous_exception, $exception->getPrevious());
    }

    public function test_exception_inheritance()
    {
        $exception = new ApiScoutException('Test');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function test_exception_string_representation()
    {
        $message = self::TEST_ERROR_MESSAGE;
        $exception = new ApiScoutException($message);
        
        $string = (string) $exception;
        $this->assertStringContainsString($message, $string);
        $this->assertStringContainsString('ApiScoutException', $string);
    }
}