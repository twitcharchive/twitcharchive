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
 * @ORM\Entity(repositoryClass="TwitchArchive\Repository\TwitchUserTokenDataRepository")
 */
class TwitchUserTokenData {
	/**
	 * @ORM\Id()
	 * @ORM\GeneratedValue()
	 * @ORM\Column(type="integer")
	 */
	private $id;

	/**
	 * @ORM\OneToOne(targetEntity="TwitchArchive\Entity\TwitchUser", inversedBy="twitchUserTokenData", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	private $accessToken;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	private $refreshToken;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	private $clientId;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	private $clientSecret;

	/**
	 * @ORM\Column(type="datetime")
	 */
	private $expiresAt;

	/**
	 * @ORM\Column(type="datetime")
	 */
	private $lastInvocation;

	/**
	 * @ORM\Column(type="datetime")
	 */
	private $time;

	public function getId(): ?int {
		return $this->id;
	}

	public function getUser(): ?TwitchUser {
		return $this->user;
	}

	public function setUser(TwitchUser $user): self {
		$this->user = $user;

		return $this;
	}

	public function getAccessToken(): ?string {
		return $this->accessToken;
	}

	public function setAccessToken(string $accessToken): self {
		$this->accessToken = $accessToken;

		return $this;
	}

	public function getRefreshToken(): ?string {
		return $this->refreshToken;
	}

	public function setRefreshToken(string $refreshToken): self {
		$this->refreshToken = $refreshToken;

		return $this;
	}

	public function getClientId(): ?string {
		return $this->clientId;
	}

	public function setClientId(string $clientId): self {
		$this->clientId = $clientId;

		return $this;
	}

	public function getClientSecret(): ?string {
		return $this->clientSecret;
	}

	public function setClientSecret(string $clientSecret): self {
		$this->clientSecret = $clientSecret;

		return $this;
	}

	public function getExpiresAt(): ?DateTimeInterface {
		return $this->expiresAt;
	}

	public function setExpiresAt(DateTimeInterface $expiresAt): self {
		$this->expiresAt = $expiresAt;

		return $this;
	}

	public function getLastInvocation(): ?DateTimeInterface {
		return $this->lastInvocation;
	}

	public function setLastInvocation(DateTimeInterface $lastInvocation): self {
		$this->lastInvocation = $lastInvocation;

		return $this;
	}

	public function getTime(): ?DateTimeInterface {
		return $this->time;
	}

	public function setTime(DateTimeInterface $time): self {
		$this->time = $time;

		return $this;
	}
}
