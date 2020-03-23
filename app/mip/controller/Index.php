<?php


namespace app\mip\controller;


use app\common\RedisHelper;
use app\model\Author;
use app\model\Banner;
use app\model\Cate;
use app\model\Chapter;
use app\service\BookService;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\facade\View;

class Index extends Base
{
    protected $bookService;
    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->bookService = new BookService();
    }

    public function index()
    {
        $pid = input('pid');
        if ($pid) { //如果有推广pid
            cookie('xwx_promotion', $pid); //将pid写入cookie
        }
        $banners = cache('bannersHomepage');
        if (!$banners) {
            $banners = Banner::with('book')->where('banner_order','>', 0)->order('banner_order','desc')->select();
            cache('bannersHomepage',$banners, null, 'redis');
        }

        $hot_books = cache('hotBooks');
        if (!$hot_books) {
            $hot_books = $this->bookService->getHotBooks($this->prefix, $this->end_point);
            cache('hotBooks', $hot_books, null, 'redis');
        }

        $newest = cache('newestHomepage');
        if (!$newest) {
            $newest = $this->bookService->getBooks( $this->end_point, 'last_time', '1=1', 10);
            cache('newestHomepage', $newest, null, 'redis');
        }

        $ends = cache('endsHomepage');
        if (!$ends) {
            $ends = $this->bookService->getBooks( $this->end_point, 'last_time', [['end', '=', '2']], 10);
            cache('endsHomepage', $ends, null, 'redis');
        }

        $most_charged = cache('mostCharged');
        if (!$most_charged) {
            $arr = $this->bookService->getMostChargedBook($this->end_point);
            if (count($arr) > 0) {
                foreach ($arr as $item) {
                    $most_charged[] = $item['book'];
                }
            } else {
                $arr = [];
            }
            cache('mostCharged', $most_charged, null, 'redis');
        }

        $cates = cache('cates');
        if (!$cates) {
            $cates = Cate::select();
            cache('cates', $cates, null, 'redis');
        }

        $catelist = array(); //分类漫画数组
        $cateItem = array();
        foreach ($cates as $cate) {
            $books = cache('booksFilterByCate'.$cate);
            if (!$books) {
                $books = $this->bookService->getByCate($cate->id, $this->end_point);
                cache('booksFilterByCate:'.$cate, $books, null, 'redis');
            }
            $cateItem['books'] = $books->toArray();
            $cateItem['cate'] = ['id' => $cate->id, 'cate_name' => $cate->cate_name];
            $catelist[] = $cateItem;
        }


        View::assign([
            'banners' => $banners,
            'banners_count' => count($banners),
            'newest' => $newest,
            'hot' => $hot_books,
            'ends' => $ends,
            'most_charged' => $most_charged,
            'cates' => $cates,
            'catelist' => $catelist
        ]);
        return view($this->tpl);
    }

    public function search()
    {
        $keyword = input('keyword');
        $redis = RedisHelper::GetInstance();
        $redis->zIncrBy($this->redis_prefix . 'hot_search', 1, $keyword); //搜索词写入redis热搜
        $hot_search_json = $redis->zRevRange($this->redis_prefix . 'hot_search', 0, 4, true);
        $hot_search = array();
        foreach ($hot_search_json as $k => $v) {
            $hot_search[] = $k;
        }

        $books = cache('searchresult:' . $keyword);
        if (!$books) {
            $books = $this->bookService->search($keyword, 35, $this->prefix);
            foreach ($books as &$book) {
                try {
                    $author = Author::findOrFail($book['author_id']);
                    $cate = Cate::findOrFail($book['cate_id']);
                    $book['author'] = $author;
                    $book['cate'] = $cate;
                    $book['last_chapter'] = Db::query('SELECT * FROM '.$this->prefix.
                        'chapter WHERE id = (SELECT MAX(id) FROM (SELECT id FROM xwx_chapter WHERE book_id=?) as a)',
                        [$book['id']])[0];
                    if ($this->end_point == 'id') {
                        $book['param'] = $book['id'];
                    } else {
                        $book['param'] = $book['unique_id'];
                    }
                } catch (DataNotFoundException $e) {
                    abort(404, $e->getMessage());
                } catch (ModelNotFoundException $e) {
                    abort(404, $e->getMessage());
                }
            }
            cache('searchresult:' . $keyword, $books, null, 'redis');
        }

        View::assign([
            'books' => $books,
            'count' => count($books),
            'hot_search' => $hot_search,
            'keyword' => $keyword,
            'header' => '搜索'
        ]);
        return view($this->tpl);
    }
}