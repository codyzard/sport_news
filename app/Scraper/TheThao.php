<?php

namespace App\Scraper;

use App\Models\Category;
use App\Models\Image;
use Goutte\Client;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\News;
use App\Models\Tag;
use Exception;
use Illuminate\Support\Facades\Config;

class TheThao
{
    private $main_url = 'https://thethao247.vn/';
    private $service_url = null;
    public function __construct()
    {
        $this->service_url = Config::get('app._SERVICE_URL');
    }
    public function scrape()
    {
        $this->soccer_crawler();
        echo PHP_EOL . '-------------------------------------' . PHP_EOL;
        $this->sport_crawler();
        echo PHP_EOL . '-------------------------------------' . PHP_EOL;
        $this->esport_LoL_crawler();
    }

    public function soccer_crawler()
    {
        try {
            // get all cate bóng đá
            $GLOBALS['cate_soccer'] = Category::where('parent_id', '=', 1)->get(['id', 'name']);
            $GLOBALS['arr_cate_name'] = [];
            foreach ($GLOBALS['cate_soccer'] as $cate) {
                array_push($GLOBALS['arr_cate_name'], $cate['name']);
            }
            //
            $client = new Client();

            $crawler = $client->request('GET', $this->main_url);

            $crawler->filter('#cate-2 a')->each(
                function (Crawler $node) {
                    $href = $node->attr('href');

                    // tạo cate parent cho sub cate
                    $GLOBALS['categories'] = [];
                    array_push($GLOBALS['categories'], Category::where('name', '=', 'Bóng đá')->first()->id);

                    // kiểm tra loại cate nào
                    $get_cate = ucwords(str_replace('Bóng đá ', '', $node->text()));
                    if (in_array($get_cate, $GLOBALS['arr_cate_name']) === true) {
                        array_push($GLOBALS['categories'], Category::where(['name' => $get_cate, 'parent_id' => 1])->first()->id);
                    } else {
                        array_push($GLOBALS['categories'], Category::where(['name' => 'Các giải khác', 'parent_id' => 1])->first()->id);
                    }
                    $each_client = new Client();

                    $each_crawler = $each_client->request('GET', $href);

                    //Copa 2019 null
                    if ($each_crawler->filter('ul.list_newest li')->count() > 0) {
                        $each_crawler->filter('ul.list_newest li')->each(
                            function (Crawler $node) {
                                $title_img = $node->filter('a img')->attr('data-src');
                                $detail_href = $node->filter('h3 a')->attr('href');
                                $detail_client = new Client();
                                $detail_crawler = $detail_client->request('GET', $detail_href);

                                $title = $detail_crawler->filter('div.colcontent h1')->text();
                                $summary = $detail_crawler->filter('div.colcontent p.typo_news_detail')->text();

                                // Tag
                                $GLOBALS['tag'] = [];
                                $detail_crawler->filter('div.tags_article a')->each(function (Crawler $node) {
                                    $get_tag = Tag::where(['name' => $node->text()])->first();
                                    if (!$get_tag) {
                                        $get_tag = Tag::create(['name' => $node->text()]);
                                    }
                                    array_push($GLOBALS['tag'], $get_tag->id);
                                });
                                // image
                                $GLOBALS['had_news_image'] = false;
                                $GLOBALS['images'] = [];
                                $detail_crawler->filter('figure')->each(function (Crawler $node) {
                                    $src = $node->filter('a img')->attr('src');
                                    if ($GLOBALS['had_news_image'] === true) return;
                                    else {
                                        if (Image::where(['src' => $src])->first() === null) {
                                            $image = Image::create([
                                                'src' => $src,
                                                'description' => $node->filter('figcaption')->count() > 0 ? $node->filter('figcaption')->text() : "",
                                            ]);

                                            array_push($GLOBALS['images'], $image);
                                        } else {
                                            $GLOBALS['had_news_image'] = true; //bug cho anh
                                            $GLOBALS['images'] = [];
                                            return;
                                        }
                                    }
                                });
                                //set publish_date
                                $datetime = $detail_crawler->filter('p.ptimezone.fregular')->text();
                                // $datetime = trim(str_replace(['(GMT+7)'], '', $datetime)); // convert (GMT)-> GMT
                                $datetime = substr($datetime, 0, 19);
                                $datetime = now()->createFromFormat('d/m/Y H:i:s', $datetime, 'GMT+7');
                                //news
                                $content = $detail_crawler->filter('#main-detail p')->each(function (Crawler $node) {
                                    if ($node->children()->count() == 0) return '<p>' . $node->text() . '</p>';
                                    // if($node->children()->count() == 0) return $node->text();
                                });
                                $content = implode(' ', $content);
                                // $db_content_thisDay = News::whereDate('created_at', '=', now()->today())->get('content');
                                $db_content_monthDay = Category::where(['name' => 'Bóng đá'])->first()->news()->get()
                                    ->whereBetween('date_publish', [now()->subDay(30), now()])->pluck('content');

                                if ($db_content_monthDay->count() != 0) {
                                    $request_servce = Http::post($this->service_url . '/check_similarity', [
                                        'from_db' => $db_content_monthDay,
                                        'data_check' => $content,
                                    ]);
                                    // if ((!boolval($request_servce->body()) && trim($content) != "") || (empty($GLOBALS['images']) && $GLOBALS['had_news_image'] === false)) {
                                    if ((!boolval($request_servce->body()) && trim($content) != "")) {
                                        $news = new News;
                                        $news->title = $title;
                                        $news->title_img = $title_img;
                                        $news->summary = $summary;
                                        $news->content = $content;
                                        $news->date_publish = $datetime;
                                        $news->status = Config::get('app.STATUS_NEWS');
                                        $news->view_count = random_int(100, 500);
                                        $news->hot_or_nor = random_int(0,1);
                                        $news->save();
                                        $news->tags()->attach($GLOBALS['tag']);
                                        $news->images()->saveMany($GLOBALS['images']);
                                        $news->categories()->attach($GLOBALS['categories']);
                                    }
                                    echo $request_servce->body();
                                } else {
                                    // if (trim($content) != "" || (empty($GLOBALS['images']) && $GLOBALS['had_news_image'] === false )) {
                                    if (trim($content) != "") {
                                        $news = new News;
                                        $news->title = $title;
                                        $news->title_img = $title_img;
                                        $news->summary = $summary;
                                        $news->content = $content;
                                        $news->date_publish = $datetime;
                                        $news->status = Config::get('app.STATUS_NEWS');
                                        $news->view_count = random_int(100, 500);
                                        $news->hot_or_nor = random_int(0,1);
                                        $news->save();
                                        $news->tags()->attach($GLOBALS['tag']);
                                        $news->images()->saveMany($GLOBALS['images']);
                                        $news->categories()->attach($GLOBALS['categories']);
                                    }
                                }
                                $GLOBALS['tag'] = [];
                                $GLOBALS['images'] = [];
                                $GLOBALS['had_news_image'] = false;
                            }
                        );
                    }
                }
            );
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function sport_crawler()
    {
        try {
            // list all cate sport
            $cate_thethao = Category::where(['name' => 'Thể thao'])->first();
            $GLOBALS['cate_sport'] = Category::where('parent_id', '=', $cate_thethao->id)->get(['id', 'name']);
            $GLOBALS['arr_cate_name'] = [];
            foreach ($GLOBALS['cate_sport'] as $cate) {
                array_push($GLOBALS['arr_cate_name'], $cate['name']);
            }
            //
            $client = new Client();

            $crawler = $client->request('GET', $this->main_url);

            $crawler->filter('#cate-5 a')->each(
                function (Crawler $node) use ($cate_thethao) {
                    $href = $node->attr('href');
                    // skip cate null news
                    $skip_cateNULL = ['Nhân vật & Sự kiện', 'Chạy bộ'];
                    if (in_array($node->text(), $skip_cateNULL))    return;
                    //
                    $GLOBALS['categories'] = [];
                    array_push($GLOBALS['categories'], Category::where('name', '=', 'Thể thao')->first()->id); // create arr category

                    // kiểm tra loại cate nào
                    $get_cate = $node->text();

                    if (in_array($get_cate, $GLOBALS['arr_cate_name']) === true) {
                        array_push($GLOBALS['categories'], Category::where(['name' => $get_cate, 'parent_id' => $cate_thethao->id])->first()->id);
                    } else {
                        array_push($GLOBALS['categories'], Category::where(['name' => 'Các môn khác'])->first()->id);
                    }
                    $each_client = new Client();
                    // DANG O DAY
                    $each_crawler = $each_client->request('GET', $href);

                    $each_crawler->filter('ul.list_newest li')->each(
                        function (Crawler $node) {
                            $title_img = $node->filter('a img')->attr('data-src');
                            $detail_href = $node->filter('h3 a')->attr('href');
                            $detail_client = new Client();
                            $detail_crawler = $detail_client->request('GET', $detail_href);

                            $title = $detail_crawler->filter('div.colcontent h1')->text();
                            $summary = $detail_crawler->filter('div.colcontent p.typo_news_detail')->text();

                            // Tag
                            $GLOBALS['tag'] = [];
                            $detail_crawler->filter('div.tags_article a')->each(function (Crawler $node) {
                                $get_tag = Tag::where(['name' => $node->text()])->first();
                                if (!$get_tag) {
                                    $get_tag = Tag::create(['name' => $node->text()]);
                                }
                                array_push($GLOBALS['tag'], $get_tag->id);
                            });
                            // image
                            // $GLOBALS['images'] = [];
                            // $detail_crawler->filter('figure')->each(function (Crawler $node) {
                            //     $image = Image::create([
                            //         'src' => $node->filter('a img')->attr('src'),
                            //         'description' => $node->filter('figcaption')->count() > 0 ? $node->filter('figcaption')->text() : "",
                            //     ]);
                            //     array_push($GLOBALS['images'], $image);
                            // });

                            //get img content
                            $GLOBALS['had_news_image'] = false;
                            $GLOBALS['images'] = [];
                            $detail_crawler->filter('figure')->each(function (Crawler $node) {
                                $src = $node->filter('img')->attr('src');
                                if ($GLOBALS['had_news_image'] === true) return;
                                else {
                                    if (Image::where(['src' => $src])->first() === null) {
                                        $image = Image::create([
                                            'src' => $node->filter('img')->attr('src'),
                                            'description' =>  $node->filter('figcaption')->count() > 0 ? $node->filter('figcaption')->text() : "",
                                        ]);

                                        array_push($GLOBALS['images'], $image);
                                    } else {
                                        $GLOBALS['had_news_image'] = true; //bug cho anh
                                        $GLOBALS['images'] = [];
                                        return;
                                    }
                                }
                            });

                            //set publish_date
                            $datetime = $detail_crawler->filter('p.ptimezone.fregular')->text();
                            // $datetime = trim(str_replace(['(GMT+7)'], '', $datetime)); // convert (GMT)-> GMT
                            $datetime = substr($datetime, 0, 19);
                            //news
                            $content = $detail_crawler->filter('#main-detail p')->each(function (Crawler $node) {
                                if ($node->children()->count() == 0) return '<p>' . $node->text() . '</p>';
                                // if($node->children()->count() == 0) return $node->text();
                            });
                            $content = implode(' ', $content);
                            $db_content_monthDay = Category::where(['name' => 'Thể thao'])->first()->news()->get()
                                ->whereBetween('date_publish', [now()->subYears(10), now()])->pluck('content');

                            if ($db_content_monthDay->count() != 0) {
                                $request_servce = Http::post($this->service_url . '/check_similarity', [
                                    'from_db' => $db_content_monthDay,
                                    'data_check' => $content,
                                ]);
                                // if ((!boolval($request_servce->body()) && trim($content) != "") || (empty($GLOBALS['images']) && $GLOBALS['had_news_image'] === false)) {
                                if ((!boolval($request_servce->body()) && trim($content) != "")) {
                                    $news = new News;
                                    $news->title = $title;
                                    $news->title_img = $title_img;
                                    $news->summary = $summary;
                                    $news->content = $content;
                                    $news->date_publish = now()->createFromFormat('d/m/Y H:i:s', $datetime, 'GMT+7');
                                    $news->status = Config::get('app.STATUS_NEWS');
                                    $news->view_count = random_int(100, 500);
                                    $news->hot_or_nor = random_int(0,1);
                                    $news->save();
                                    $news->tags()->attach($GLOBALS['tag']);
                                    $news->images()->saveMany($GLOBALS['images']);
                                    $news->categories()->attach($GLOBALS['categories']);
                                }
                                echo $request_servce->body();
                            } else {
                                // if (trim($content) != "" || (empty($GLOBALS['images']) && $GLOBALS['had_news_image'] === false)) {
                                if (trim($content) != "") {
                                    $news = new News;
                                    $news->title = $title;
                                    $news->title_img = $title_img;
                                    $news->summary = $summary;
                                    $news->content = $content;
                                    $news->date_publish = now()->createFromFormat('d/m/Y H:i:s', $datetime, 'GMT+7');
                                    $news->status = Config::get('app.STATUS_NEWS');
                                    $news->view_count = random_int(100, 500);
                                    $news->hot_or_nor = random_int(0,1);
                                    $news->save();
                                    $news->tags()->attach($GLOBALS['tag']);
                                    $news->images()->saveMany($GLOBALS['images']);
                                    $news->categories()->attach($GLOBALS['categories']);
                                }
                            }
                            $GLOBALS['tag'] = [];
                            $GLOBALS['images'] = [];
                            $GLOBALS['had_news_image'] = false;
                        }
                    );
                }
            );
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    public function esport_LoL_crawler()
    {
        try {
            $client = new Client();

            $crawler = $client->request('GET', 'https://thethao247.vn/lien-minh-huyen-thoai-c181/');
            $GLOBALS['categories'] = array(Category::where(['name' => 'LoL'])->first()->id, Category::where(['name' => 'E-sports'])->first()->id);
            $crawler->filter('ul.list_newest li')->each(
                function (Crawler $node) {
                    $title_img = $node->filter('a img')->attr('data-src');
                    $detail_href = $node->filter('h3 a')->attr('href');
                    $detail_client = new Client();
                    $detail_crawler = $detail_client->request('GET', $detail_href);

                    $title = $detail_crawler->filter('div.colcontent h1')->text();
                    $summary = $detail_crawler->filter('div.colcontent p.typo_news_detail')->text();

                    // Tag
                    $GLOBALS['tag'] = [];
                    $detail_crawler->filter('div.tags_article a')->each(function (Crawler $node) {
                        $get_tag = Tag::where(['name' => $node->text()])->first();
                        if (!$get_tag) {
                            $get_tag = Tag::create(['name' => $node->text()]);
                        }
                        array_push($GLOBALS['tag'], $get_tag->id);
                    });
                    // image
                    //get img content
                    $GLOBALS['had_news_image'] = false;
                    $GLOBALS['images'] = [];
                    $detail_crawler->filter('figure')->each(function (Crawler $node) {
                        $src = $node->filter('img')->attr('src');
                        if ($GLOBALS['had_news_image'] === true) return;
                        else {
                            if (Image::where(['src' => $src])->first() === null) {
                                $image = Image::create([
                                    'src' => $node->filter('img')->attr('src'),
                                    'description' =>  $node->filter('figcaption')->count() > 0 ? $node->filter('figcaption')->text() : "null",
                                ]);

                                array_push($GLOBALS['images'], $image);
                            } else {
                                $GLOBALS['had_news_image'] = true; //bug cho anh
                                $GLOBALS['images'] = [];
                                return;
                            }
                        }
                    });
                    //set publish_date
                    $datetime = $detail_crawler->filter('p.ptimezone.fregular')->text();
                    // $datetime = trim(str_replace(['(GMT+7)'], '', $datetime)); // convert (GMT)-> GMT
                    $datetime = substr($datetime, 0, 19);
                    //news
                    $content = $detail_crawler->filter('#main-detail p')->each(function (Crawler $node) {
                        if ($node->children()->count() == 0) return '<p>' . $node->text() . '</p>';
                        // if($node->children()->count() == 0) return $node->text();
                    });
                    $content = implode(' ', $content);
                    $db_content_monthDay = Category::where(['name' => 'LoL'])->first()->news()->get()
                        ->whereBetween('date_publish', [now()->subYears(2), now()])->pluck('content');

                    if ($db_content_monthDay->count() != 0) {
                        $request_servce = Http::post($this->service_url . '/check_similarity', [
                            'from_db' => $db_content_monthDay,
                            'data_check' => $content,
                        ]);
                        if ((!boolval($request_servce->body()) && trim($content) != "")) {
                            // if ((!boolval($request_servce->body()) && trim($content) != "" ) || (empty($GLOBALS['images']) && $GLOBALS['had_news_image'] === false )) {
                            $news = new News;
                            $news->title = $title;
                            $news->title_img = $title_img;
                            $news->summary = $summary;
                            $news->content = $content;
                            $news->date_publish = now()->createFromFormat('d/m/Y H:i:s', $datetime, 'GMT+7');
                            $news->status = Config::get('app.STATUS_NEWS');
                            $news->view_count = random_int(100, 500);
                            $news->hot_or_nor = random_int(0,1);
                            $news->save();
                            $news->tags()->attach($GLOBALS['tag']);
                            $news->images()->saveMany($GLOBALS['images']);
                            $news->categories()->attach($GLOBALS['categories']);
                        }
                        echo $request_servce->body();
                    } else {
                        if (trim($content) != "") {
                            // if (trim($content) != "" || (empty($GLOBALS['images']) && $GLOBALS['had_news_image'] === false )) {
                            $news = new News;
                            $news->title = $title;
                            $news->title_img = $title_img;
                            $news->summary = $summary;
                            $news->content = $content;
                            $news->date_publish = now()->createFromFormat('d/m/Y H:i:s', $datetime, 'GMT+7');
                            $news->status = Config::get('app.STATUS_NEWS');
                            $news->view_count = random_int(100, 500);
                            $news->hot_or_nor = random_int(0,1);
                            $news->save();
                            $news->tags()->attach($GLOBALS['tag']);
                            $news->images()->saveMany($GLOBALS['images']);
                            $news->categories()->attach($GLOBALS['categories']);
                        }
                    }
                    $GLOBALS['tag'] = [];
                    $GLOBALS['images'] = [];
                    $GLOBALS['had_news_image'] = false;
                }
            );
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
