<?php

namespace jcnghm\ApiScout\Tests\Exceptions;

use jcnghm\ApiScout\Tests\TestCase;
use jcnghm\ApiScout\Exceptions\ApiScoutException;

class ApiScoutExceptionTest extends TestCase
{
    public function test_exception_creation()
    {
        $message = 'Test error message';
        $exception = new ApiScoutException($message);
        
        $this->assertInstanceOf(ApiScoutException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function test_exception_with_code()
    {
        $message = 'Test error message';
        $code = 500;
        $exception = new ApiScoutException($message, $code);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function test_exception_with_previous_exception()
    {
        $previousException = new \Exception('Previous error');
        $message = 'Test error message';
        $exception = new ApiScoutException($message, 0, $previousException);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function test_exception_inheritance()
    {
        $exception = new ApiScoutException('Test');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function test_exception_string_representation()
    {
        $message = 'Test error message';
        $exception = new ApiScoutException($message);
        
        $string = (string) $exception;
        $this->assertStringContainsString($message, $string);
        $this->assertStringContainsString('ApiScoutException', $string);
    }
}
