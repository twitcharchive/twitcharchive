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

class TwigGlobalsService {
	/**
	 * Gets the name of the JS bundle file.
	 *
	 * @return string|null
	 */
	public function getJSBundleName(): ?string {
		return $this->loadBundleFile(__DIR__ . "/../../public/build/bundle*.js");
	}

	/**
	 * Finds a file by it's glob pattern, sorted by most recent file modification time.
	 *
	 * @param string $pattern
	 * @return string|null
	 */
	private function loadBundleFile(string $pattern): ?string {
		$results = glob($pattern);
		if ($results && count($results) > 0) {
			if (count($results) > 1) {
				usort($results, function ($a, $b) {
					$aTime = filemtime($a);
					$bTime = filemtime($b);
					return $aTime === $bTime ? 0 : $aTime > $bTime ? -1 : 1;
				});
			}

			return basename($results[0]);
		}
		return null;
	}

	/**
	 * Gets the name of the CSS bundle file.
	 *
	 * @return string|null
	 */
	public function getCSSBundleName(): ?string {
		return $this->loadBundleFile(__DIR__ . "/../../public/build/main*.css");
	}
}