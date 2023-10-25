<?php

declare(strict_types=1);

namespace OCA\Collectives\Service;

class SubpageOrderService {
	/**
	 * @param string|null $subpageOrder
	 *
	 * @return array
	 * @throws NotPermittedException
	 */
	private static function toArray(?string $subpageOrder): array {
		if ($subpageOrder === null) {
			return [];
		}

		try {
			$subpageOrderArray = json_decode($subpageOrder, true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException $e) {
			throw new NotPermittedException('Invalid format of subpage order');
		}
		if (!is_array($subpageOrderArray)) {
			throw new NotPermittedException('Invalid format of subpage order');
		}

		return $subpageOrderArray;
	}

	/**
	 * @param array $subpageOrderArray
	 *
	 * @return string
	 * @throws NotPermittedException
	 */
	private static function fromArray(array $subpageOrderArray): string {
		try {
			return json_encode(array_values($subpageOrderArray), JSON_THROW_ON_ERROR);
		} catch (\JsonException $e) {
			throw new NotPermittedException('Invalid format of subpage order');
		}
	}

	/**
	 * @param string|null $subpageOrder
	 *
	 * @return void
	 * @throws NotPermittedException
	 */
	public static function verify(?string $subpageOrder): void {
		if ($subpageOrder) {
			$subpageOrderArray = self::toArray($subpageOrder);

			foreach ($subpageOrderArray as $pageId) {
				if (!is_int($pageId)) {
					throw new NotPermittedException('Invalid format of subpage order');
				}
			}
		}
	}

	/**
	 * @param string|null $subpageOrder
	 * @param int         $pageId
	 * @param int         $index
	 *
	 * @return string
	 * @throws NotPermittedException
	 */
	public static function add(?string $subpageOrder, int $pageId, int $index = 0): string {
		$subpageOrderArray = self::toArray($subpageOrder);

		if ($key = array_search($pageId, $subpageOrderArray, true)) {
			// pageId already in array, remove first
			unset($subpageOrderArray[$key]);
		}

		array_splice($subpageOrderArray, $index, 0, [$pageId]);

		return self::fromArray($subpageOrderArray);
	}

	/**
	 * @param string|null $subpageOrder
	 * @param int         $pageId
	 *
	 * @return string
	 * @throws NotPermittedException
	 */
	public static function remove(?string $subpageOrder, int $pageId): string {
		$subpageOrderArray = self::toArray($subpageOrder);

		if (false !== $key = array_search($pageId, $subpageOrderArray, true)) {
			unset($subpageOrderArray[$key]);
		}

		return self::fromArray($subpageOrderArray);
	}
}
