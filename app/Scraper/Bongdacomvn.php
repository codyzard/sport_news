<?php

namespace App\Scraper;

use App\Helper\CrawlerHelper;
use Illuminate\Support\Facades\Config;
use App\Models\Category;
use App\Models\Image;
use Goutte\Client;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\News;
use App\Models\Tag;
use Exception;

class Bongdacomvn
{
    private $service_url = null;

    public function __construct()
    {
        $this->service_url = Config::get('app._SERVICE_URL');
    }

    public function scrape()
    {
        $cate_soccer = 'Bóng đá';
        $this->soccer($cate_soccer, 'Anh', 'http://www.bongda.com.vn/ngoai-hang-anh/', 2);
        $this->soccer($cate_soccer, 'Anh', 'http://www.bongda.com.vn/bong-da-anh/', 2);
        $this->soccer($cate_soccer, 'Việt Nam', 'http://www.bongda.com.vn/viet-nam/', 2);
        $this->soccer($cate_soccer, 'Tây Ban Nha', 'http://www.bongda.com.vn/bong-da-tbn/', 2);
        $this->soccer($cate_soccer, 'Đức', 'http://www.bongda.com.vn/bong-da-duc/', 2);
        $this->soccer($cate_soccer, 'Ý', 'http://www.bongda.com.vn/bong-da-y/', 2);
        $this->soccer($cate_soccer, 'Pháp', 'http://www.bongda.com.vn/bong-da-phap/', 2);
        $this->soccer($cate_soccer, 'C1', 'http://www.bongda.com.vn/champions-league/', 3);
        $this->soccer($cate_soccer, 'C2', 'http://www.bongda.com.vn/europa-league/', 3);
        $this->soccer($cate_soccer, 'Các giải khác', 'http://www.bongda.com.vn/euro-2020/', 48);
        $this->soccer($cate_soccer, 'Các giải khác', 'http://www.bongda.com.vn/bong-da-chau-au/', 3);
        $this->soccer($cate_soccer, 'Chuyển nhượng', 'http://www.bongda.com.vn/tin-chuyen-nhuong/', 3);
    }
    public function soccer($parent_category, $category, $url, $timeCheck)
    {

        try {
            $client = new Client();
            $crawler = $client->request('GET', $url);
            $each_crawler = $crawler->filter('.list_top_news.list_news_cate li');
            if ($each_crawler->count() > 0) {
                $GLOBALS['categories'] = [];
                array_push(
                    $GLOBALS['categories'],
                    Category::where(['name' =>  $parent_category])->first()->id,
                    Category::where(['name' =>  $category])->first() ? Category::where(['name' =>  $category])->first()->id : null,
                );
                $each_crawler->each(
                    function (Crawler $node) use ($timeCheck, $category) {
                        $title_img = $node->filter('img')->attr('src');
                        $title =  $node->filter('h2 a')->text();
                        $summary = $node->filter('div .sapo_news')->text();

                        $detail_href = $node->filter('h2 a')->attr('href');
                        $detail_client = new Client();
                        $detail_crawler = $detail_client->request('GET', $detail_href);

                        // get datetime content

                        $datetime = substr(trim($detail_crawler->filter('div.f13')->text()), -16);
                        $datetime = now()->createFromFormat('H:i d/m/Y', $datetime, 'GMT+7');

                        //
                        $GLOBALS['tags'] = [];
                        $detail_crawler->filter('div.list_tag_trend a')->each(function (Crawler $node) {
                            $get_tag = Tag::where(['name' => $node->text()])->first();
                            if (!$get_tag) {
                                $get_tag = Tag::create(['name' => $node->text()]);
                            }
                            array_push($GLOBALS['tags'], $get_tag->id);
                        });

                        //set index for content and image
                        $news_image_detect = $detail_crawler->filter('#content_detail .news_details')->children()->each(function (Crawler $node) {
                            if ($node->filter('p')->count() > 0) {
                                return "0";
                            } else if ($node->filter('img')->count() > 0 || $node->filter('figure')->count() > 0) {
                                return "1";
                            }
                            return null;
                        });
                        $news_image_detect = array_filter($news_image_detect, function ($item) {
                            return $item !== null;
                        });

                        $news_image_detect = implode(' ', array_values($news_image_detect));

                        //set index for content and image

                        //get img content
                        $GLOBALS['had_news_image'] = false;
                        $GLOBALS['images'] = [];
                        $detail_crawler->filter('#content_detail figure')->each(function (Crawler $node) {
                            $src = $node->filter('img')->attr('src');
                            if ($GLOBALS['had_news_image'] === true) return;
                            else {
                                if (Image::where(['src' => $src])->first() === null) {
                                    $image = Image::create([
                                        'src' => $node->filter('img')->attr('src'),
                                        'description' =>  $node->filter('figcaption')->count() > 0 ? $node->filter('figcaption')->text() : "Bongda.com.vn",
                                    ]);

                                    array_push($GLOBALS['images'], $image);
                                } else {
                                    $GLOBALS['had_news_image'] = true; //bug cho anh
                                    $GLOBALS['images'] = [];
                                    return;
                                }
                            }
                        });
                        $content = $detail_crawler->filter('#content_detail p')->each(function (Crawler $node) {
                            return '<p>' . $node->text() . '</p>';
                        });
                        $content = implode(' ', $content);
                        // $db_content_monthDay = Category::where(['name' => $category])->first()->news()->get()
                        $db_content_monthDay = Category::where(['name' => 'Bóng đá'])->first()->news()->get()
                            ->whereBetween('date_publish', [now()->subMonths($timeCheck), now()->addDay()])->pluck('content');

                        if ($db_content_monthDay->count() > 0) {
                            $request_servce = Http::post($this->service_url . '/check_similarity', [
                                'from_db' => $db_content_monthDay,
                                'data_check' => $content,
                            ]);
                            if ((!boolval($request_servce->body()) && trim($content) != "") || (empty($GLOBALS['images']) && $GLOBALS['had_news_image'] === false)) {
                                $status = Config::get('app.STATUS_NEWS');
                                $view_count = random_int(100, 500);
                                $hot_or_nor = random_int(0, 1);
                                News::saveNews($title, $title_img, $summary, $content, $datetime, $status, $view_count, $hot_or_nor, $news_image_detect, $GLOBALS['images'], $GLOBALS['categories'], $GLOBALS['tags'], 'Bongda.com.vn');
                            }
                            echo $request_servce->body();
                        } else {
                            if (trim($content) != "" || (empty($GLOBALS['images']) && $GLOBALS['had_news_image'] === false)) {
                                $status = Config::get('app.STATUS_NEWS');
                                $view_count = random_int(100, 500);
                                $hot_or_nor = random_int(0, 1);
                                News::saveNews($title, $title_img, $summary, $content, $datetime, $status, $view_count, $hot_or_nor, $news_image_detect, $GLOBALS['images'], $GLOBALS['categories'], $GLOBALS['tags'], 'Bongda.com.vn');
                            }
                        }
                        $GLOBALS['tags'] = [];
                        $GLOBALS['images'] = [];
                        $GLOBALS['had_news_image'] = false;
                    }
                );
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }
}
