<?php

namespace EasyApiBundle\Services\User;

use EasyApiBundle\Entity\User\AbstractUser as User;
use EasyApiBundle\Entity\User\AbstractConnectionHistory as ConnectionHistory;
use EasyApiBundle\Services\AbstractService;
use Symfony\Component\HttpFoundation\Request;
use UserAgentParser\Exception\NoResultFoundException;
use UserAgentParser\Exception\PackageNotLoadedException;
use UserAgentParser\Model\UserAgent;
use UserAgentParser\Provider\WhichBrowser;

class Tracking extends AbstractService
{
    const CONNECTION_HISTORY_CLASS_PARAMETER = 'easy_api.user_tracking.connection_history_class';
    const TRACKING_ENABLE_PARAMETER = 'easy_api.user_tracking.enable';

    /**
     * @return array
     */
    protected function getProviders(): array
    {
        return [new WhichBrowser()];
    }

    /**
     * @param User    $user
     * @param Request $request
     * @param string  $token
     * @param bool    $isSSO
     *
     * @return ConnectionHistory
     *
     * @throws PackageNotLoadedException|\Exception
     */
    public function logConnection(User $user, Request $request, string $token, bool $isSSO = false): ConnectionHistory
    {
        $connectionHistoryClass = $this->getParameter(self::CONNECTION_HISTORY_CLASS_PARAMETER);

        if(null === $connectionHistoryClass) {
            throw new \Exception(self::CONNECTION_HISTORY_CLASS_PARAMETER.' must be defined to log connection');
        }

        $connectionHistory = new $connectionHistoryClass();
        $connectionHistory->setUser($user);
        $connectionHistory->setIsSSO($isSSO);
        $connectionHistory->setIp($request->getClientIp());
        $connectionHistory->setUserAgent($request->headers->get('User-Agent'));
        $connectionHistory->setTokenValue($token); // @todo hash token
        $connectionHistory->setLoginDate(new \DateTime());

        $this->updateConnectionFromUserAgent($connectionHistory);

        $this->PersistAndFlush($connectionHistory);

        return $connectionHistory;
    }

    /**
     * Update date when user execute action.
     *
     * @param User $user
     * @param Request $request
     * @param string $token
     *
     * @throws PackageNotLoadedException
     */
    public function updateLastAction(User $user, Request $request, string $token)
    {
        $connectionHistoryClass = $this->getParameter(self::CONNECTION_HISTORY_CLASS_PARAMETER);

        if(null === $connectionHistoryClass) {
            throw new \Exception(self::CONNECTION_HISTORY_CLASS_PARAMETER.' must be defined to log connection');
        }

        /** @var ConnectionHistory $connectionHistory */
        $connectionHistory = $this->getRepository($connectionHistoryClass)->findOneBy(['user' => $user->getId(), 'tokenValue' => $token]);

        if(null == $connectionHistory) {
            $connectionHistory = $this->logConnection($user, $request, $token);
        }

        $connectionHistory->setLastActionDate(new \DateTime());

        $this->persistAndFlush($connectionHistory);
    }

    /**
     * @param ConnectionHistory $connectionHistory
     *
     * @return ConnectionHistory
     */
    public function updateConnectionFromUserAgent(ConnectionHistory $connectionHistory): ConnectionHistory
    {
        $results = $this->parseUserAgent($connectionHistory->getUserAgent());

        foreach ($results as $result) {
            $this->updateBrowser($result, $connectionHistory);
            $this->updateOperatingSystem($result, $connectionHistory);
            $this->updateDevice($result, $connectionHistory);
        }

        return $connectionHistory;
    }

    /**
     * @param string $userAgent
     *
     * @return array
     */
    protected function parseUserAgent(string $userAgent): array
    {
        $results = [];

        if ($userAgent) {
            $providers = $this->getProviders();
            foreach ($providers as $provider) {
                try {
                    $results[] = $provider->parse($userAgent);
                } catch (NoResultFoundException $ex) {
                    // nothing found
                }
            }
        }

        return $results;
    }

    /**
     * @param UserAgent $result
     * @param ConnectionHistory $connectionHistory
     *
     * @return ConnectionHistory
     */
    protected function updateBrowser(UserAgent $result, ConnectionHistory $connectionHistory): ConnectionHistory
    {
        $browser = $result->getBrowser();
        $browserEngine = $result->getRenderingEngine();

        if (empty($connectionHistory->getBrowserName())) {
            $connectionHistory->setBrowserName($browser->getName());
        }
        if (empty($connectionHistory->getBrowserVersion())) {
            $connectionHistory->setBrowserVersion($browser->getVersion()->getComplete());
        }
        if (empty($connectionHistory->getBrowserEngineName())) {
            $connectionHistory->setBrowserEngineName($browserEngine->getName());
        }
        if (empty($connectionHistory->getBrowserEngineVersion())) {
            $connectionHistory->setBrowserEngineVersion($browserEngine->getVersion()->getComplete());
        }

        return $connectionHistory;
    }

    /**
     * @param UserAgent         $result
     * @param ConnectionHistory $connectionHistory
     */
    protected function updateOperatingSystem(UserAgent $result, ConnectionHistory $connectionHistory)
    {
        if (empty($connectionHistory->getOperatingSystemName())) {
            $connectionHistory->setOperatingSystemName($result->getOperatingSystem()->getName());
        }
        if (empty($connectionHistory->getOperatingSystemVersion())) {
            $connectionHistory->setOperatingSystemVersion($result->getOperatingSystem()->getVersion()->getComplete());
        }
    }

    /**
     * @param UserAgent         $result
     * @param ConnectionHistory $connectionHistory
     */
    protected function updateDevice(UserAgent $result, ConnectionHistory $connectionHistory)
    {
        if (empty($connectionHistory->getDeviceModel())) {
            $connectionHistory->setDeviceModel($result->getDevice()->getModel());
        }
        if (empty($connectionHistory->getDeviceBrand())) {
            $connectionHistory->setDeviceBrand($result->getDevice()->getBrand());
        }
        if (empty($connectionHistory->getDeviceType())) {
            $connectionHistory->setDeviceType($result->getDevice()->getType());
        }
        if (empty($connectionHistory->isMobile())) {
            $connectionHistory->setIsMobile($result->getDevice()->getIsMobile());
        }
        if (empty($connectionHistory->isTouch())) {
            $connectionHistory->setIsTouch($result->getDevice()->getIsTouch());
        }
    }
}