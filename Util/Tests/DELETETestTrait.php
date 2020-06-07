<?php


namespace EasyApiBundle\Util\Tests;

use Symfony\Component\HttpFoundation\Response;
use EasyApiBundle\Util\ApiProblem;

trait DELETETestTrait
{
    protected static $executeSetupOnAllTest = false;
    protected static $routeName;

    /**
     * DELETE - Nominal case.
     */
    public function testDelete()
    {
        $apiOutput = $this->httpDelete(['name' => static::$routeName, 'params' => ['id' => self::ENTITY_ID_1]]);
        self::assertEquals(Response::HTTP_NO_CONTENT, $apiOutput->getStatusCode());

        $apiOutput = $this->httpGet(['name' => '{{ route_name_get }}', 'params' => ['id' => self::ENTITY_ID_1]]);
        self::assertEquals(Response::HTTP_NOT_FOUND, $apiOutput->getStatusCode());

        // check another
        $apiOutput = $this->httpGet(['name' => '{{ route_name_get }}', 'params' => ['id' => self::ENTITY_ID_2]]);
        self::assertEquals(Response::HTTP_OK, $apiOutput->getStatusCode());
    }

    /**
     * DELETE - Unexisting entity.
     */
    public function testDeleteNotFound()
    {
        $apiOutput = $this->httpDelete(['name' => static::$routeName, 'params' => ['id' => 888888]]);

        self::assertEquals(Response::HTTP_NOT_FOUND, $apiOutput->getStatusCode());
    }

    /**
     * DELETE - Error case - 401 - Without authentication.
     */
    public function testDelete401()
    {
        $apiOutput = $this->httpDelete(['name' => static::$routeName, 'params' => ['id' => self::ENTITY_ID_2],], false);
        static::assertApiProblemError($apiOutput, Response::HTTP_UNAUTHORIZED, [ApiProblem::JWT_NOT_FOUND]);
    }

    /**
     * DELETE - Error case - 403 - Missing right.
     */
    public function testDeleteWithoutRight403()
    {
        $token = self::loginHttp('[API-TESTS-NO-RULES]', 'u-norules-pwd');
        $apiOutput = $this->httpDelete([
            'name' => static::$routeName, 'params' => ['id' => self::ENTITY_ID_1]],
            false,
            ['Authorization' => self::getAuthorizationTokenPrefix()." {$token}"]
        );

        static::assertApiProblemError($apiOutput, Response::HTTP_FORBIDDEN, [ApiProblem::RESTRICTED_ACCESS]);
    }
}