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

class TwitchConstantsService {
	/**
	 * @var string $clientId
	 */
	private $clientId;

	/**
	 * @var string $clientSecret
	 */
	private $clientSecret;

	/**
	 * @var string $redirectURL
	 */
	private $redirectURL;

	/**
	 * @var string $apiBaseURL
	 */
	private $apiBaseURL;

	public function __construct() {
		$this->clientId = $_ENV["TWITCH_CLIENT_ID"];
		$this->clientSecret = $_ENV["TWITCH_CLIENT_SECRET"];
		$this->redirectURL = ""; // TODO
		$this->apiBaseURL = "https://api.twitch.tv/helix";
	}

	/**
	 * @return string
	 */
	public function getClientId(): string {
		return $this->clientId;
	}

	/**
	 * @return string
	 */
	public function getClientSecret(): string {
		return $this->clientSecret;
	}

	/**
	 * @return string
	 */
	public function getRedirectURL(): string {
		return $this->redirectURL;
	}

	/**
	 * @return string
	 */
	public function getAPIBaseURL(): string {
		return $this->apiBaseURL;
	}
}