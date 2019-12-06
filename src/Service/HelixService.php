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
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TwitchArchive\Cache\CacheHandler;
use TwitchArchive\Entity\TwitchUser;
use TwitchArchive\Entity\TwitchUserTokenData;
use function count;
use function is_null;
use function is_numeric;
use function strtolower;

class HelixService {
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

	/**
	 * HelixService constructor.
	 * @param EntityManagerInterface $entityManager
	 * @param LoggerInterface $logger
	 * @param TwitchConstantsService $constantsService
	 * @param EntityBuilderService $entityBuilderService
	 * @param TwitchAuthService $authService
	 */
	public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, TwitchConstantsService $constantsService, EntityBuilderService $entityBuilderService, TwitchAuthService $authService) {
		$this->entityManager = $entityManager;
		$this->logger = $logger;
		$this->constantsService = $constantsService;
		$this->entityBuilderService = $entityBuilderService;
		$this->authService = $authService;
	}

	/**
	 * Gets a user from either their username or ID.
	 *
	 * @param string $query
	 * @return TwitchUser|null
	 * @throws ClientExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 */
	public function getUser(string $query): ?TwitchUser {
		$n = "twitchUserId_" . strtolower($query);
		$userRepository = $this->entityManager->getRepository(TwitchUser::class);

		if (CacheHandler::existsInCache($n)) {
			return $userRepository->findOneBy(["id" => CacheHandler::getFromCache($n)]);
		}

		$authData = $this->authService->getNextAuthDetails();
		try {
			$users = $this->getUsers($authData, is_numeric($query) ? $query : null, $query);

			if ($users && count($users) > 0) {
				$user = $users[0];

				$time = 10 * 60;
				CacheHandler::setToCache("twitchUserId_" . strtolower($user->getLogin()), $user->getId(), $time);
				CacheHandler::setToCache("twitchUserId_" . $user->getId(), $user->getId(), $time);

				return $user;
			}
		} catch (Exception $e) {
			$this->logger->error("An error occurred.", [
				"exception" => $e
			]);
		}

		return null;
	}

	/**
	 * Gets an array of users from the Helix API by criteria. If no id(s) or login(s) is/are provided, the Bearer token will be used.
	 * https://dev.twitch.tv/docs/api/reference#get-users
	 *
	 * @param TwitchUserTokenData|null $auth
	 * @param string|string[]|null $id User ID. Multiple user IDs can be specified. Limit: 100.
	 * @param string|string[]|null $login User login name. Multiple login names can be specified. Limit: 100.
	 * @return TwitchUser[]|null
	 * @throws ClientExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 */
	public function getUsers(?TwitchUserTokenData $auth = null, $id = null, $login = null): ?array {
		$params = [];
		if (!is_null($id)) $params["id"] = $id;
		if (!is_null($login)) $params["login"] = $login;

		$client = $this->getClient($auth);

		try {
			$response = $client->request("GET", "/helix/users", [
				"query" => $params
			])->toArray(true);

			if (isset($response["data"])) {
				$data = $response["data"];

				$results = [];

				if (is_array($data)) {
					foreach ($data as $user) {
						$twitchUser = $this->entityBuilderService->buildTwitchUser($user["id"], $user["login"], $user["display_name"], $user["type"], $user["broadcaster_type"], $user["description"], $user["profile_image_url"], $user["offline_image_url"], $user["view_count"]);

						if ($twitchUser) {
							$results[] = $twitchUser;
						}
					}
				}

				return $results;
			} else {
				$this->logger->error("Data not found", ["response" => $response]);
			}
		} catch (Exception $e) {
			$this->logger->error("An error occurred.", ["exception" => $e]);
			return null;
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

		return HttpClient::createForBaseUri($this->constantsService->getAPIBaseURL(), $options);
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