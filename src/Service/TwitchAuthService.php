<?php
/**
 * Copyright (C) 2019 Gigadrive - All rights reserved.
 * https://gigadrivegroup.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://gnu.org/licenses/>
 */

namespace TwitchArchive\Service;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use TwitchArchive\Entity\TwitchUserTokenData;
use function urlencode;

class TwitchAuthService {
	/**
	 * @var EntityManagerInterface $entityManager
	 */
	private $entityManager;

	/**
	 * @var LoggerInterface $logger
	 */
	private $logger;

	/**
	 * @var TwitchConstantsService $constantsService
	 */
	private $constantsService;

	/**
	 * @var HelixService $helixService
	 */
	private $helixService;

	/**
	 * TwitchAuthService constructor.
	 * @param EntityManagerInterface $entityManager
	 * @param LoggerInterface $logger
	 * @param TwitchConstantsService $constantsService
	 * @param HelixService $helixService
	 */
	public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, TwitchConstantsService $constantsService, HelixService $helixService) {
		$this->entityManager = $entityManager;
		$this->logger = $logger;
		$this->constantsService = $constantsService;
		$this->helixService = $helixService;
	}

	/**
	 * Exchanges an OAuth2 code for authentication data.
	 *
	 * @param string $code The code to exchange.
	 * @return TwitchUserTokenData|null
	 * @throws ClientExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 */
	public function exchangeCode(string $code): ?TwitchUserTokenData {
		$client = HttpClient::create();
		$clientId = $this->constantsService->getClientId();
		$clientSecret = $this->constantsService->getClientSecret();

		try {
			$response = $client->request("POST", "https://id.twitch.tv/oauth2/token?client_id=" . $clientId . "&client_secret=" . $clientSecret . "&code=" . $code . "&redirect_uri=" . urlencode($this->constantsService->getRedirectURL()) . "&grant_type=authorization_code");

			$data = $response->toArray(true);

			if (isset($data["access_token"]) && isset($data["refresh_token"]) && isset($data["expires_in"]) && isset($data["scope"]) && isset($data["token_type"])) {
				$accessToken = $data["access_token"];
				$refreshToken = $data["refresh_token"];
				$expiresIn = $data["expires_in"];

				$authData = (new TwitchUserTokenData())->setAccessToken($accessToken)->setTime(new DateTime("now"));

				$users = $this->helixService->getUsers($authData);
				if ($users && count($users) > 0) {
					$twitchUser = $users[0];
					if (!is_null($twitchUser->getTwitchUserTokenData())) {
						$authData = $twitchUser->getTwitchUserTokenData()
							->setAccessToken($accessToken);
					} else {
						$authData->setUser($twitchUser);
					}

					$expiry = new DateTime("now");
					$expiry->add(new DateInterval("PT" . $expiresIn . "S"));

					$authData->setRefreshToken($refreshToken)
						->setLastInvocation(new DateTime("now"))
						->setClientId($clientId)
						->setClientSecret($clientSecret)
						->setExpiresAt($expiry);

					$this->entityManager->persist($authData);
					$this->entityManager->flush();

					$this->logger->info("Auth data saved.", [
						"authData" => $authData
					]);

					return $authData;
				} else {
					$this->logger->error("Failed to exchange code: Could not identify user.", [
						"users" => $users
					]);
				}
			} else {
				$this->logger->error("Failed to exchange code: Invalid response.");
			}
		} catch (Exception $e) {
			return null;
		}

		return null;
	}

	/**
	 * Gets the next TwitchUserTokenData to use from the database.
	 *
	 * @return TwitchUserTokenData|null
	 * @throws Exception
	 */
	public function getNextAuthDetails(): ?TwitchUserTokenData {
		/**
		 * @var TwitchUserTokenData[] $data
		 */
		$data = $this->entityManager->getRepository(TwitchUserTokenData::class)->findOneBy([], [
			"lastInvocation" => "ASC"
		], 1);

		if (count($data) > 0) {
			$result = $data[0];

			$result->setLastInvocation(new DateTime("now"));

			// TODO: Refresh

			$this->entityManager->persist($result);
			$this->entityManager->flush();
		}

		return null;
	}

	/**
	 * @return EntityManagerInterface
	 */
	public function getEntityManager(): EntityManagerInterface {
		return $this->entityManager;
	}

	/**
	 * @return LoggerInterface
	 */
	public function getLogger(): LoggerInterface {
		return $this->logger;
	}

	/**
	 * @return TwitchConstantsService
	 */
	public function getConstantsService(): TwitchConstantsService {
		return $this->constantsService;
	}
}