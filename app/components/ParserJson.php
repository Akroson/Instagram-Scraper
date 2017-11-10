<?php

namespace App\Components;

use App\Config\ConstName;

class ParserJson
{
    const FOLLOWING_ARR = 'edge_follow';
    const FOLLOWERS_ARR = 'edge_followed_by';
    
    public static function parseJsonMedia($json)
    {
        $result = json_decode($json, true);
        $resultParse = [];
        $nodes = $result['items'];
        $hasNextPage = $result['more_available'];

       return $result;
    }
    public static function parseJsonComment($json, $param)
    {
        $result = json_decode($json, true);
        $resultParse = [];
        $nodes = $result['data']['shortcode_media']['edge_media_to_comment']['edges'];
        $hasNextPage = $result['data']['shortcode_media']['edge_media_to_comment']['page_info']['has_next_page'];

        $j = 0;
        foreach ($nodes as $item) {
            if ($item['node']['owner']['username'] == $param) {
                $resultParse[$j]['text'] = $item['node']['text'];
                $resultParse[$j]['created'] = $item['node']['created_at'];
                $j++;
            }
        }

        if ($hasNextPage) {
            $resultParse[ConstName::END_POINT] = $nodes[0]['node']['id'];
        }
        $resultParse[ConstName::REPEAT] = $hasNextPage;

        return $resultParse;
    }

    public static function parseJsonLike($json, $param)
    {
        $result = json_decode($json, true);
        $resultParse = [];
        $nodes = $result['data']['shortcode_media']['edge_liked_by']['edges'];
        $hasNextPage = $result['data']['shortcode_media']['edge_liked_by']['page_info']['has_next_page'];

        foreach ($nodes as $item) {
            if($item['node']['username'] == $param) {
                $resultParse['confirm'] = true;
                $resultParse[ConstName::REPEAT] = false;
                return $resultParse;
            }
        }

        if ($hasNextPage) {
            $resultParse[ConstName::END_POINT] = $result['data']['shortcode_media']['edge_liked_by']['page_info']['end_cursor'];
        } else if (!$hasNextPage && !$resultParse['confirm']){
             $resultParse['confirm'] = false;
        }
        $resultParse[ConstName::REPEAT] = $hasNextPage;

        return $resultParse;
    }

    //need authentication
    public static function parseJsonFollowing($json, $param)
    {
        return self::parseJsonFollow($param, $json, ParserJson::FOLLOWING_ARR);
    }

    public static function parseJsonFollowers($json, $param)
    {
        return self::parseJsonFollow($param, $json, ParserJson::FOLLOWERS_ARR);
    }

    private static function parseJsonFollow($param, $json, $arrName)
    {
        $result = json_decode($json, true);
        $resultParse = [];
        $nodes = $result['data']['user'][$arrName]['edges'];
        $hasNextPage = $result['data']['user'][$arrName]['page_info']['has_next_page'];

        $j = 0;
        foreach ($nodes as $item) {
            if (strpos($item['node']['username'], $param) !== false) {
                $resultParse[$j]['username'] = $item['node']['username'];
                $resultParse[$j]['profile_pic'] = $item['node']['profile_pic_url'];
                $j++;
            }
        }

        if ($hasNextPage) {
            $resultParse[ConstName::END_POINT] = $result['data']['user'][$arrName]['page_info']['end_cursor'];
        }
        $resultParse[ConstName::REPEAT] = $hasNextPage;

        return $resultParse;
    }
}