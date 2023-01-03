<?php
namespace util;

class NewsRedis extends RedisUtil
{
    /**
     * 缓存upChina新闻
     */
    public static function cacheUpChina()
    {
        try {
            $url     = 'http://47.114.91.240:21271/';
            $content = file_get_contents($url);
            $key     = 'up_china';
            self::redis()->setex($key, 30, $content);
        } catch (\Exception $e) {
        }
    }

    /**
     * 获取upChina新闻
     *
     * @return mixed
     */
    public static function getNews($newsid)
    {
        $key = 'news_' . $newsid;
        return self::redis()->hGetAll($key) ?? '';
    }
    /*获取新闻链接ID*/
    public static function getBriefInfo($newsid){
        $url = "https://gbapi.eastmoney.com/abstract/api/PostShort/ArticleBriefInfo?postid=".$newsid."&type=1&deviceid=0d2798cab1716439a343c9965c20c59d&version=2&product=eastmoney&plat=wap";
        $str = curl($url);
        return $str;
    }
    /*获取新闻链接*/
    public static function getNewsInfo($newsid,$postid){
        $key = 'news_' . $newsid;
        $url = "https://gbapi.eastmoney.com/content/api/Post/ArticleContent?postid=".$postid."&newsid=&deviceid=0d2798cab1716439a343c9965c20c59d&version=2&product=eastmoney&plat=wap";
        $str = curl($url);
        $data = [];
        if($str){
            $str  = json_decode($str, true);
            $data = [
                'Art_ShowTime'  => $str['post']['post_last_time'],
                //'Art_Image'     => $str['post']['Art_Image'],
                'Art_MediaName' => $str['post']['extend']['MediaName'],
                'Art_Code'      => $newsid,
                'Art_Title'     => $str['post']['post_title'],
                'Art_info'      => $str['post']['post_content'],
            ];
            if($str)self::redis()->hMSet($key,$data);
        }
        return $data ?? [];
    }
    /*正则方式获取新闻内容*/
    public static function getNewsPath($item){
        $return = curl('https://finance.eastmoney.com/a/'.$item['Art_Code'].'.html');
        $data   = [
            'Art_ShowTime'  => $item['Art_ShowTime'],
            'Art_Image'     => $item['Art_Image'],
            'Art_MediaName' => $item['Art_MediaName'],
            'Art_Code'      => $item['Art_Code'],
            'Art_Title'     => $item['Art_Title'],
            'Art_info'      => get_between($return, '<!-- 文本区域 -->','<!-- 文尾部其它信息 -->'),
        ];
        $key = 'news_' . $data['Art_Code'];
        self::redis()->hMSet($key,$data);
        return $data;
    }
}
