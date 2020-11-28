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


class Game8v
{

    private $service_url = null;
    public function __construct()
    {
        $this->service_url = Config::get('app._SERVICE_URL');
    }

    public function scrape()
    {
        $this->crawler('http://game8.vn/tin-game/pubg/31415', 'PUBG', 12);
    }

    public function crawler($url, $category, $timeCheck)
    {
        try {
            $client = new Client();
            $crawler = $client->request('GET', $url);
            $GLOBALS['categories'] = [];
            array_push(
                $GLOBALS['categories'],
                Category::where(['name' => 'E-sports'])->first()->id,
                Category::where(['name' => 'PUBG'])->first()->id,
            );
            $each_crawler = $crawler->filter('.sub-list article');
            if ($each_crawler->count() > 0) {
                $each_crawler->each(
                    function (Crawler $node) use ($category, $timeCheck) {
                        if ($node->filter('img')->count() <= 0) return;
                        else {
                            $title_img = $node->filter('img')->attr('src');
                            $title =  $node->filter('.col-xs-8 h2')->text();
                            $summary = $node->filter('.col-xs-8 p')->text();
                            $detail_href = $node->filter('.col-xs-8 h2 a')->attr('href');
                            $detail_client = new Client();
                            $detail_crawler = $detail_client->request('GET', $detail_href);

                            // get datetime content

                            // xóa ký tự 'h' và -''
                            $datetime = $detail_crawler->filter('.date span')->last()->text();
                            $datetime = now()->createFromFormat('d/m/Y H:i', $datetime, 'GMT+7');

                            $GLOBALS['tags'] = [];
                            $detail_crawler->filter('#div_All_TagPartial a')->each(function (Crawler $node) {
                                $get_tag = Tag::where(['name' => $node->text()])->first();
                                if (!$get_tag) {
                                    $get_tag = Tag::create(['name' => $node->text()]);
                                }
                                array_push($GLOBALS['tags'], $get_tag->id);
                            });

                            //set index for content and image
                            $news_image_detect = $detail_crawler->filter('.content-detail')->children()->each(function (Crawler $node) {
                                $element = $node->filter('p');
                                if ($element->count() > 0 && $element->filter('img')->count() === 0) {
                                    return "0";
                                } else if ($element->count() > 0 && $element->filter('img')->count() > 0) {
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
                            $detail_crawler->filter('.content-detail p img')->each(function (Crawler $node) {
                                $src = $node->attr('src');
                                if ($GLOBALS['had_news_image'] === true) return;
                                else {
                                    if (Image::where(['src' => $src])->first() === null) {
                                        $image = Image::create([
                                            'src' => $node->attr('src'),
                                            'description' =>  'PUBG',
                                        ]);

                                        array_push($GLOBALS['images'], $image);
                                    } else {
                                        $GLOBALS['had_news_image'] = true;
                                        $GLOBALS['images'] = [];
                                        return;
                                    }
                                }
                            });
                            $content = $detail_crawler->filter('.content-detail p')->each(function (Crawler $node) {

                                return '<p>' . $node->text() . '</p>';
                            });
                            $content = implode(' ', $content);
                            $content = substr($content, 120);
                            $db_content_monthDay = Category::where(['name' => $category])->first()->news()->get()
                                ->whereBetween('date_publish', [now()->subMonths($timeCheck), now()->addDay()])->pluck('summary');
                            if ($db_content_monthDay->count() > 0) {
                                $request_servce = Http::post($this->service_url . '/check_similarity', [
                                    'from_db' => $db_content_monthDay,
                                    'data_check' => $summary,
                                ]);
                                if ((!boolval($request_servce->body()) && trim($content) != "") || (empty($GLOBALS['images']) && $GLOBALS['had_news_image'] === false)) {
                                    $status = Config::get('app.STATUS_NEWS');
                                    $view_count = random_int(100, 500);
                                    $hot_or_nor = random_int(0, 1);
                                    News::saveNews($title, $title_img, $summary, $content, $datetime, $status, $view_count, $hot_or_nor, $news_image_detect, $GLOBALS['images'], $GLOBALS['categories'], $GLOBALS['tags'], "Game8v.vn");
                                }
                                echo $request_servce->body();
                            } else {
                                if (trim($content) != "" || (empty($GLOBALS['images']) && $GLOBALS['had_news_image'] === false)) {
                                    $status = Config::get('app.STATUS_NEWS');
                                    $view_count = random_int(100, 500);
                                    $hot_or_nor = random_int(0, 1);
                                    News::saveNews($title, $title_img, $summary, $content, $datetime, $status, $view_count, $hot_or_nor, $news_image_detect, $GLOBALS['images'], $GLOBALS['categories'], $GLOBALS['tags'], "Game8v.vn");
                                }
                            }
                            $GLOBALS['tags'] = [];
                            $GLOBALS['images'] = [];
                            $GLOBALS['had_news_image'] = false;
                        }
                    }

                );
            }
            $GLOBALS['categories'] = [];
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }
}
