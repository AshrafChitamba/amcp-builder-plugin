<?php

/**
 * Preview links and reporting instructions attached to every saved change.
 *
 * @package AmcpBuilder
 */

namespace AmcpBuilder\Support;

defined('ABSPATH') || exit;

/**
 * Tells the AI where the user can see a change on the front end, and that it
 * must report each change in plain words with those links.
 */
final class ChangeReport
{

	/** Most element links listed for one change; the rest are counted. */
	private const MAX_LINKS = 8;

	public const INSTRUCTION = 'Report this change to the user now: share the preview link(s) above, then describe each section or element you changed in 1-3 plain lines, e.g. "Changed the button background to red and its corner radius to 28px." Mention it if the page is a draft (the preview needs a login).';

	/**
	 * Fields to add to a successful write result.
	 *
	 * @param array<string, mixed> $result Tool result.
	 * @return array<string, mixed>
	 */
	public static function for_result(array $result): array
	{
		if (! empty($result['dryRun'])) {
			return array();
		}

		$post_id = (int) ($result['postId'] ?? 0);
		$post    = $post_id ? get_post($post_id) : null;
		if (! $post) {
			return array(
				'preview'      => array(
					'url'   => home_url('/'),
					'scope' => 'site-wide: this change affects every page that uses it',
				),
				'reportToUser' => self::INSTRUCTION,
			);
		}

		$builder = PageBuilder::of($post->ID);
		$url     = self::page_url($post);
		$ids     = self::element_ids($result);

		$preview = array(
			'url'     => $url,
			'isDraft' => 'publish' !== $post->post_status,
		);
		if ($ids) {
			$shown = array_slice($ids, 0, self::MAX_LINKS);
			if (PageBuilder::BRICKS === $builder) {
				// Bricks renders each element with id="brxe-{id}", so the link scrolls to it.
				$preview['elements'] = array_map(
					static fn(string $id): array => array(
						'id'  => $id,
						'url' => $url . '#brxe-' . $id,
					),
					$shown
				);
			} else {
				$preview['elements'] = array_map(
					static fn(string $id): array => array(
						'id'       => $id,
						'selector' => '.elementor-element-' . $id,
					),
					$shown
				);
			}
			if (count($ids) > self::MAX_LINKS) {
				$preview['moreElements'] = count($ids) - self::MAX_LINKS;
			}
		}

		return array(
			'preview'      => $preview,
			'reportToUser' => self::INSTRUCTION,
		);
	}

	/**
	 * Public URL for published posts, the preview URL otherwise.
	 */
	private static function page_url(\WP_Post $post): string
	{
		if ('publish' === $post->post_status) {
			return (string) get_permalink($post);
		}
		return (string) get_preview_post_link($post);
	}

	/**
	 * The elements a write touched, most specific first.
	 *
	 * @param array<string, mixed> $result Tool result.
	 * @return list<string>
	 */
	private static function element_ids(array $result): array
	{
		foreach (array('rootIds', 'newId', 'elementId', 'elementIds') as $key) {
			if (! empty($result[$key])) {
				return array_values(array_map('strval', (array) $result[$key]));
			}
		}
		$changed = (array) ($result['diff']['changed'] ?? array());
		$added   = (array) ($result['diff']['added'] ?? array());
		return array_values(array_unique(array_map('strval', array_merge($added, $changed))));
	}
}
