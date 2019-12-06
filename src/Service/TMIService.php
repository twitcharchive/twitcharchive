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

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TwitchArchive\Entity\TwitchUserTokenData;
use function array_unique;
use function is_array;
use function is_null;

class TMIService {
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
	 * @var EntityBuilderService $entityBuilderService
	 */
	private $entityBuilderService;

	/**
	 * @var TwitchAuthService $authService
	 */
	private $authService;

	public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, TwitchConstantsService $constantsService, EntityBuilderService $entityBuilderService, TwitchAuthService $authService) {
		$this->entityManager = $entityManager;
		$this->logger = $logger;
		$this->constantsService = $constantsService;
		$this->entityBuilderService = $entityBuilderService;
		$this->authService = $authService;
	}

	/**
	 * Gets all usernames currently in chat for the passed channel.
	 *
	 * @param string $channelName
	 * @return string[]|null
	 * @throws ClientExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 */
	public function getChatters(string $channelName): ?array {
		$client = $this->getClient($this->authService->getNextAuthDetails());

		$response = $client->request("GET", "/group/user/" . $channelName . "/chatters")->toArray(true);
		if ($response && isset($response["chatters"])) {
			$chatters = $response["chatters"];
			$keys = ["broadcaster", "vips", "moderators", "staff", "admins", "global_mods", "viewers"];
			$results = [];

			foreach ($keys as $key) {
				if (isset($chatters[$key])) {
					$names = $chatters[$key];

					if (is_array($names)) {
						foreach ($names as $name) {
							$results[] = $name;
						}
					}
				}
			}

			return array_unique($results);
		} else {
			$this->logger->error("Failed to get chatters: Invalid response.");
		}

		return null;
	}

	/**
	 * Gets an HttpClient to use for interaction with the Helix API.
	 *
	 * @param TwitchUserTokenData|null $auth
	 * @return HttpClientInterface
	 */
	public function getClient(?TwitchUserTokenData $auth = null): HttpClientInterface {
		$headers = [
			"User-Agent" => "TwitchArchive.net (https://gitlab.com/Gigadrive/twitcharchive/twitcharchive)"
		];

		if (!is_null($auth)) {
			// https://dev.twitch.tv/docs/authentication#sending-user-access-and-app-access-tokens
			$headers["Authorization"] = "Bearer " . $auth->getAccessToken();
		}

		$options = [
			"headers" => $headers
		];

		return HttpClient::createForBaseUri($this->constantsService->getTMIBaseURL(), $options);
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

	/**
	 * @return EntityBuilderService
	 */
	public function getEntityBuilderService(): EntityBuilderService {
		return $this->entityBuilderService;
	}

	/**
	 * @return TwitchAuthService
	 */
	public function getAuthService(): TwitchAuthService {
		return $this->authService;
	}
}