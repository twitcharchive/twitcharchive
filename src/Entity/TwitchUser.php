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

namespace TwitchArchive\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="TwitchArchive\Repository\TwitchUserRepository")
 */
class TwitchUser {
	/**
	 * @ORM\Id()
	 * @ORM\GeneratedValue()
	 * @ORM\Column(type="string", length=24)
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", length=16)
	 */
	private $login;

	/**
	 * @ORM\Column(type="string", length=16)
	 */
	private $displayName;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $description;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $email;

	/**
	 * @ORM\Column(type="string", length=24, nullable=true)
	 */
	private $broadcasterType;

	/**
	 * @ORM\Column(type="string", length=24, nullable=true)
	 */
	private $type;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $viewCount;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $offlineImage;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $profileImage;

	/**
	 * @ORM\Column(type="datetime")
	 */
	private $time;

	/**
	 * @ORM\OneToOne(targetEntity="TwitchArchive\Entity\TwitchUserTokenData", mappedBy="user", cascade={"persist", "remove"})
	 */
	private $twitchUserTokenData;

	public function getId(): ?int {
		return $this->id;
	}

	public function getLogin(): ?string {
		return $this->login;
	}

	public function setLogin(string $login): self {
		$this->login = $login;

		return $this;
	}

	public function getDisplayName(): ?string {
		return $this->displayName;
	}

	public function setDisplayName(string $displayName): self {
		$this->displayName = $displayName;

		return $this;
	}

	public function getDescription(): ?string {
		return $this->description;
	}

	public function setDescription(?string $description): self {
		$this->description = $description;

		return $this;
	}

	public function getEmail(): ?string {
		return $this->email;
	}

	public function setEmail(?string $email): self {
		$this->email = $email;

		return $this;
	}

	public function getBroadcasterType(): ?string {
		return $this->broadcasterType;
	}

	public function setBroadcasterType(?string $broadcasterType): self {
		$this->broadcasterType = $broadcasterType;

		return $this;
	}

	public function getType(): ?string {
		return $this->type;
	}

	public function setType(?string $type): self {
		$this->type = $type;

		return $this;
	}

	public function getViewCount(): ?int {
		return $this->viewCount;
	}

	public function setViewCount(int $viewCount): self {
		$this->viewCount = $viewCount;

		return $this;
	}

	public function getOfflineImage(): ?string {
		return $this->offlineImage;
	}

	public function setOfflineImage(?string $offlineImage): self {
		$this->offlineImage = $offlineImage;

		return $this;
	}

	public function getProfileImage(): ?string {
		return $this->profileImage;
	}

	public function setProfileImage(?string $profileImage): self {
		$this->profileImage = $profileImage;

		return $this;
	}

	public function getTime(): ?DateTimeInterface {
		return $this->time;
	}

	public function setTime(DateTimeInterface $time): self {
		$this->time = $time;

		return $this;
	}

	public function getTwitchUserTokenData(): ?TwitchUserTokenData {
		return $this->twitchUserTokenData;
	}

	public function setTwitchUserTokenData(TwitchUserTokenData $twitchUserTokenData): self {
		$this->twitchUserTokenData = $twitchUserTokenData;

		// set the owning side of the relation if necessary
		if ($twitchUserTokenData->getUser() !== $this) {
			$twitchUserTokenData->setUser($this);
		}

		return $this;
	}
}
