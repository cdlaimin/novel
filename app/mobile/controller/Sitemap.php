<?php


namespace app\mobile\controller;


use app\model\ArticleArticle;
use app\model\ArticleChapter;
use app\model\Tail;

class Sitemap extends Base
{
    public function book()
    {
        $num = config('seo.sitemap_gen_num');
        $site_name = config('site.domain');
        $books = ArticleArticle::order('articleid','desc')->limit($num)->select();
        $data = array();
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $content .= '<urlset>';
        foreach ($books  as &$book){ //这里构建所有的内容页数组
            if ($this->end_point == 'id') {
                $book['param'] = $book['articleid'];
            } else {
                $book['param'] = $book['backname'];
            }
            $temp = array(
                'loc' => $site_name . '/m/' . BOOKCTRL . '/' . $book['param'],
                'priority' => '0.9',
            );
            array_push($data, $temp);
        }
        foreach ($data as $item) {
            $content .= $this->create_item($item);
        }
        $content .= '</urlset>';

        ob_clean();
        return xml($content,200,[],['root_node'=>'xml']);
    }

    public function chapter() {
        $num = config('seo.sitemap_gen_num');
        $site_name = config('site.domain');
        $chapters = ArticleChapter::order('chapterid','desc')->limit($num)->select();
        $arr = array();
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $content .= '<urlset>';
        foreach ($chapters as $chapter) {
            $temp = array(
                'loc' => $site_name . '/m/' . CHAPTERCTRL . '/' . $chapter['chapterid'],
                'priority' => '0.9',
            );
            array_push($arr, $temp);
        }
        foreach ($arr as $item) {
            $content .= $this->create_item($item);
        }
        $content .= '</urlset>';
        ob_clean();
        return xml($content,200,[],['root_node'=>'xml']);
    }

    public function tail() {
        $num = config('seo.sitemap_gen_num');
        $site_name = config('site.domain');
        $tails = Tail::order('id','desc')->limit($num)->select();
        $arr = array();
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $content .= '<urlset>';
        foreach ($tails as $tail) {
            $temp = array(
                'loc' => $site_name . '/m/tail/' . $tail['tailcode'],
                'priority' => '0.9',
            );
            array_push($arr, $temp);
        }
        foreach ($arr as $item) {
            $content .= $this->create_item($item);
        }
        $content .= '</urlset>';
        ob_clean();
        return xml($content,200,[],['root_node'=>'xml']);
    }
}