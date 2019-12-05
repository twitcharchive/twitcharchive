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
	 * TwitchAuthService constructor.
	 * @param EntityManagerInterface $entityManager
	 * @param LoggerInterface $logger
	 * @param TwitchConstantsService $constantsService
	 */
	public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, TwitchConstantsService $constantsService) {
		$this->entityManager = $entityManager;
		$this->logger = $logger;
		$this->constantsService = $constantsService;
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

				$expiry = new DateTime("now");
				$expiry->add(new DateInterval("PT" . $expiresIn . "S"));

				return (new TwitchUserTokenData())
					->setAccessToken($accessToken)
					->setTime(new DateTime("now"))
					->setRefreshToken($refreshToken)
					->setExpiresAt($expiry)
					->setClientId($clientId)
					->setClientSecret($clientSecret);
			} else {
				$this->logger->error("Failed to exchange code: Invalid response.");
			}
		} catch (Exception $e) {
			return null;
		}

		return null;
	}

	/**
	 * Refresh an access token if necessary.
	 *
	 * @param TwitchUserTokenData $authData
	 * @return TwitchUserTokenData
	 * @throws ClientExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws Exception
	 */
	public function refreshToken(TwitchUserTokenData $authData): TwitchUserTokenData {
		if ($authData->isExpired() === false) return $authData;

		$client = HttpClient::create();
		$response = $client->request("POST", "https://id.twitch.tv/oauth2/token?client_id=" . $authData->getClientId() . "&client_secret=" . $authData->getClientSecret() . "&refresh_token=" . $authData->getRefreshToken() . "&grant_type=refresh_token");

		$data = $response->toArray(true);

		if (isset($data["access_token"]) && isset($data["refresh_token"]) && isset($data["expires_in"])) {
			$accessToken = $data["access_token"];
			$refreshToken = $data["refresh_token"];
			$expiresIn = $data["expires_in"];

			$expiry = new DateTime("now");
			$expiry->add(new DateInterval("PT" . $expiresIn . "S"));

			$authData->setAccessToken($accessToken)
				->setRefreshToken($refreshToken)
				->setExpiresAt($expiry);

			$this->entityManager->persist($authData);
			$this->entityManager->flush();
		}

		return $authData;
	}

	/**
	 * Gets the next TwitchUserTokenData to use from the database.
	 *
	 * @return TwitchUserTokenData|null
	 * @throws ClientExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 */
	public function getNextAuthDetails(): ?TwitchUserTokenData {
		/**
		 * @var TwitchUserTokenData $data
		 */
		$data = $this->entityManager->getRepository(TwitchUserTokenData::class)->createQueryBuilder("t")
			->orderBy("t.lastInvocation", "ASC")
			->setMaxResults(1)
			->getQuery()
			->getOneOrNullResult();

		if ($data) {
			$data->setLastInvocation(new DateTime("now"));

			$this->refreshToken($data);

			$this->entityManager->persist($data);
			$this->entityManager->flush();
		}

		return $data;
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