<?php

namespace App\Components;

class RequestUrl 
{
	const MEDIA_URL = 'https://www.instagram.com/{userName}/media/?max_id={{id}}';
	const COMMENT_URL = 'https://www.instagram.com/graphql/query/?query_id=17852405266163336&shortcode={code}&first=300&after=';
	const LIKE_URL = 'https://www.instagram.com/graphql/query/?query_id=17864450716183058&variables={"shortcode":"{code}","first":200,"after":""}';
	const FOLLOWING_URL = 'https://www.instagram.com/graphql/query/?query_id=17874545323001329&id={{id}}&first=200&after=';
	const FOLLOWERS_URL = 'https://www.instagram.com/graphql/query/?query_id=17851374694183129&id={{id}}&first=200&after=';

	public static function getUrlUserMedia($user, $maxId)
	{
		$url = str_replace('{userName}', urlencode($user), RequestUrl::MEDIA_URL);
		return str_replace('{{id}}', urlencode($maxId), $url);
	}
	public static function getUrlCommentByCode(...$code)
	{
		return self::getUrlByCode($code, RequestUrl::COMMENT_URL);
	}

	public static function getUrlLikeByCode(...$code)
	{
		return self::getUrlByCode($code, RequestUrl::LIKE_URL);
	}

	public static function getUrlFollowingById($id)
	{
		return str_replace('{{id}}', urlencode($id), RequestUrl::FOLLOWING_URL);
	}

	public static function getUrlFollowersById($id)
	{
		return str_replace('{{id}}', urlencode($id), RequestUrl::FOLLOWERS_URL);
	}

	private static function getUrlByCode($code, $constUrl)
	{
		if(count($code) > 1) {
			$urls = [];
			foreach($code as $item) {
				$urls[] = str_replace('{code}', urlencode($item), $constUrl);
			}
			return $urls;
		}
		return str_replace('{code}', urlencode($code[0]), $constUrl);
	}
}