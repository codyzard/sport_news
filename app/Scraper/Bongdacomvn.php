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

class Bongdacomvn{
    private $main_url = 'https://thethao247.vn/';
    private $service_url = null;

    public function __construct()
    {
        $this->service_url = Config::get('app._SERVICE_URL');
    }

    public function scrape(){
        $cate_soccer = 'Bóng đá';
        $this->soccer($cate_soccer, 'Việt Nam', 'http://www.bongda.com.vn/viet-nam/', 1);
        $this->soccer($cate_soccer, 'Tây Ban Nha', 'http://www.bongda.com.vn/bong-da-tbn/', 1);
        $this->soccer($cate_soccer, 'Đức', 'http://www.bongda.com.vn/champions-league/', 1);
        $this->soccer($cate_soccer, 'Ý', 'http://www.bongda.com.vn/bong-da-y/', 1);
        $this->soccer($cate_soccer, 'Pháp', 'http://www.bongda.com.vn/bong-da-phap/', 1);
        $this->soccer($cate_soccer, 'C1', 'http://www.bongda.com.vn/champions-league/', 4);
        $this->soccer($cate_soccer, 'C2', 'http://www.bongda.com.vn/europa-league/', 4);
        $this->soccer($cate_soccer, 'Các giải khác', 'http://www.bongda.com.vn/euro-2020/', 6);
        $this->soccer($cate_soccer, 'Các giải khác', 'http://www.bongda.com.vn/bong-da-chau-au/', 3);
        $this->soccer($cate_soccer, 'Chuyển nhượng', 'http://www.bongda.com.vn/tin-chuyen-nhuong/', 1);
    }
    public function soccer($parent_category, $category, $url, $timeCheck){

        try{
            $client = new Client();
            $crawler = $client->request('GET', $url);
            $each_crawler = $crawler->filter('.list_top_news.list_news_cate li');
            if($each_crawler->count() > 0){
                $GLOBALS['categories'] = [];
                array_push(
                    $GLOBALS['categories'],
                    Category::where(['name' =>  $parent_category])->first()->id,
                    Category::where(['name' =>  $category])->first()? Category::where(['name' =>  $category])->first()->id : null,
                );
                $each_crawler->each(
                    function(Crawler $node) use ($timeCheck, $category){
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
                        $GLOBALS['tag'] = [];
                        $detail_crawler->filter('div.list_tag_trend a')->each(function (Crawler $node) {
                            $get_tag = Tag::where(['name' => $node->text()])->first();
                            if (!$get_tag) {
                                $get_tag = Tag::create(['name' => $node->text()]);
                            }
                            array_push($GLOBALS['tag'], $get_tag->id);
                        });

                        //get img content
                        $GLOBALS['had_news_image'] = false;
                        $GLOBALS['images'] = [];
                        $detail_crawler->filter('#content_detail figure')->each(function (Crawler $node) {
                            $src = $node->filter('img')->attr('src');
                            if(Image::where(['src' => $src])->first() == null){
                                $image = Image::create([
                                    'src' => $node->filter('img')->attr('src'),
                                    'description' =>  $node->filter('figcaption')->text(),
                                ]);

                                array_push($GLOBALS['images'], $image);
                            }
                            else{
                                $GLOBALS['had_news_image'] = true; //bug cho anh
                                $GLOBALS['images'] = [];
                                return;
                            }

                        });

                        $content = $detail_crawler->filter('#content_detail p')->each(function (Crawler $node) {
                            return '<p>' . $node->text() . '</p>';
                        });
                        $content = implode(' ', $content);
                        $db_content_monthDay = Category::where(['name' => $category])->first()->news()->get()
                        ->whereBetween('date_publish', [now()->subMonths($timeCheck), now()->addDay()])->pluck('content');

                        if ($db_content_monthDay->count() != 0) {
                            $request_servce = Http::post($this->service_url . '/check_similarity', [
                                'from_db' => $db_content_monthDay,
                                'data_check' => $content,
                            ]);
                            if (( !boolval($request_servce->body()) && trim($content) != "" )|| (empty($GLOBALS['images'] && $GLOBALS['had_news_image'] === false ))) {
                                $news = new News;
                                $news->title = $title;
                                $news->title_img = $title_img;
                                $news->summary = $summary;
                                $news->content = $content;
                                $news->date_publish = $datetime;
                                $news->status = 1;
                                $news->save();
                                $news->tags()->attach($GLOBALS['tag']);
                                $news->images()->saveMany($GLOBALS['images']);
                                $news->categories()->attach($GLOBALS['categories']);
                            }
                            echo $request_servce->body();
                        } else {
                            if (trim($content) != "" || ($GLOBALS['had_news_image'] === false)) {
                                $news = new News;
                                $news->title = $title;
                                $news->title_img = $title_img;
                                $news->summary = $summary;
                                $news->content = $content;
                                $news->date_publish = $datetime;
                                $news->status = 1;
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
            $GLOBALS['categories'] = [];
        }catch(Exception $e){
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }
}
