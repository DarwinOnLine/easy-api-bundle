<?php


namespace EasyApiBundle\Util\Tests;


use EasyApiBundle\Tests\Format;
use Symfony\Component\HttpFoundation\Response;
use EasyApiBundle\Util\ApiProblem;

trait GETTestTrait
{
    protected static $executeSetupOnAllTest = false;
    protected static $routeName;

    /**
     * GET - Nominal case.
     */
    public function testGet()
    {
        $expectedResult = self::createGETResponseData();

        $apiOutput = $this->httpGet(['name' => static::$routeName, 'params' => ['id' => self::ENTITY_ID_1]]);

        self::assertEquals(Response::HTTP_OK, $apiOutput->getStatusCode());
        $result = $apiOutput->getData();
        static::assertArrayHasKey('createdAt', $result);
        static::assertArrayHasKey('updatedAt', $result);
        unset($result['createdAt'], $result['updatedAt']);
        static::assertEquals($expectedResult, $result);
    }

    /**
     * GET - Unexisting entity.
     */
    public function testGetNotFound()
    {
        $apiOutput = $this->httpGet(['name' => static::$routeName, 'params' => ['id' => 99999]]);

        self::assertEquals(Response::HTTP_NOT_FOUND, $apiOutput->getStatusCode());
    }

    /**
     * GET - Error case - 401 - Without authentication.
     */
    public function testGet401()
    {
        $apiOutput = $this->httpGet([
            'name' => static::$routeName, 'params' => ['id' => 1]],
            false
        );

        self::assertEquals(Response::HTTP_UNAUTHORIZED, $apiOutput->getStatusCode());
    }

    /**
     * GET - Error case - 403 - Missing read right.
     */
    public function testGetWithoutRightR403()
    {
        $token = self::loginHttp('[API-TESTS-NO-RULES]', 'u-norules-pwd');
        $apiOutput = $this->httpGet([
            'name' => static::$routeName, 'params' => ['id' => self::ENTITY_ID_1]],
            false,
            Format::JSON,
            ['Authorization' => self::getAuthorizationTokenPrefix()." {$token}"]
        );

        static::assertApiProblemError($apiOutput, Response::HTTP_FORBIDDEN, [ApiProblem::RESTRICTED_ACCESS]);
    }

}