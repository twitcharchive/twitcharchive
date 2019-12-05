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

namespace TwitchArchive\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use TwitchArchive\Service\TwitchAuthService;
use TwitchArchive\Service\TwitchConstantsService;
use function implode;
use function urlencode;

class LoginController extends AbstractController {
	/**
	 * @Route("/login")
	 *
	 * @param TwitchConstantsService $constantsService
	 * @return RedirectResponse
	 */
	public function loginAction(TwitchConstantsService $constantsService) {
		// https://dev.twitch.tv/docs/authentication/#scopes
		$scopes = [
			"channel:read:subscriptions",
			"user:read:email",
			"channel_check_subscription",
			"chat:read",
			"chat:edit"
		];

		$url = "https://id.twitch.tv/oauth2/authorize?client_id=" . $constantsService->getClientId() . "&redirect_uri=" . urlencode($constantsService->getRedirectURL()) . "&response_type=code&scope=" . urlencode(implode(" ", $scopes));

		return $this->redirect($url);
	}

	/**
	 * @Route("/callbacks/twitch")
	 *
	 * @param Request $request
	 * @param LoggerInterface $logger
	 * @param TwitchAuthService $authService
	 * @return RedirectResponse
	 * @throws ClientExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 */
	public function callbackAction(Request $request, LoggerInterface $logger, TwitchAuthService $authService) {
		$constantsService = $authService->getConstantsService();

		if ($request->query->has("code")) {
			$code = $request->query->get("code");
			$authData = $authService->exchangeCode($code);

			$logger->info("Auth data retrieved.", [
				"authData" => $authData
			]);
		} else {
			$logger->error("No code passed");
		}

		return $this->redirect($this->generateUrl("twitcharchive_home_index"));
	}
}