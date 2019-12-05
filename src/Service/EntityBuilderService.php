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

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use TwitchArchive\Entity\TwitchUser;

class EntityBuilderService {
	/**
	 * @var EntityManagerInterface $entityManager
	 */
	private $entityManager;

	/**
	 * @var LoggerInterface $logger
	 */
	private $logger;

	/**
	 * EntityBuilderService constructor.
	 * @param EntityManagerInterface $entityManager
	 * @param LoggerInterface $logger
	 */
	public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger) {
		$this->entityManager = $entityManager;
		$this->logger = $logger;
	}

	/**
	 * Builds a TwitchUser object based on values passed through the API.
	 *
	 * @param string $id
	 * @param string $login
	 * @param string $displayName
	 * @param string $type
	 * @param string $broadcasterType
	 * @param string|null $description
	 * @param string|null $profileImageURL
	 * @param string|null $offlineImageURL
	 * @param int $viewCount
	 * @return TwitchUser|null
	 * @throws Exception
	 */
	public function buildTwitchUser(string $id, string $login, string $displayName, string $type, string $broadcasterType, ?string $description = null, ?string $profileImageURL = null, ?string $offlineImageURL = null, int $viewCount = 0): ?TwitchUser {
		$twitchUser = $this->entityManager->getRepository(TwitchUser::class)->findOneBy([
			"id" => $id
		]);

		if (is_null($twitchUser)) $twitchUser = (new TwitchUser())->setTime(new DateTime("now"));

		$twitchUser->setId($id)
			->setLogin($login)
			->setDisplayName($displayName)
			->setType($type === "" ? null : $type)
			->setBroadcasterType($broadcasterType === "" ? null : $broadcasterType)
			->setDescription($description)
			->setProfileImage($profileImageURL)
			->setOfflineImage($offlineImageURL)
			->setViewCount($viewCount);

		$this->entityManager->persist($twitchUser);
		$this->entityManager->flush();

		return $twitchUser;
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
}