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


class Game8v{

    private $service_url = null;
    public function __construct()
    {
        $this->service_url = Config::get('app._SERVICE_URL');
    }
    
    public function scrape()
    {   
        $this->crawler('http://game8.vn/tin-game/pubg/31415', 'PUBG', 12);
    }
    
    public function crawler($url, $category, $timeCheck){
        try{
            $client = new Client();
            $crawler = $client->request('GET', $url);
            $GLOBALS['categories'] = [];    
            array_push(
                $GLOBALS['categories'], 
                Category::where(['name' => 'E-sports'])->first()->id,
                Category::where(['name' => 'PUBG'])->first()->id,
            );
            $each_crawler = $crawler->filter('.sub-list article');
            if($each_crawler->count() > 0){
                $each_crawler->each(
                    function(Crawler $node) use ($category, $timeCheck){
                        if ($node->filter('img')->count() <= 0 ) return;
                        else{
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
    
                            //get img content
                            $GLOBALS['had_news_image'] = false;
                            $GLOBALS['images'] = [];
                            $detail_crawler->filter('.content-detail p img')->each(function (Crawler $node) {
                                $src = $node->attr('src');
                                if($GLOBALS['had_news_image'] === true) return;
                                else{
                                    if(Image::where(['src' => $src])->first() === null){
                                        $image = Image::create([
                                            'src' => $node->attr('src'),
                                            'description' =>  'PUBG',
                                        ]);
        
                                        array_push($GLOBALS['images'], $image);
                                    }
                                    else{
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
                            ->whereBetween('date_publish', [now()->subMonths($timeCheck), now()->addDay()])->pluck('content');
                            if ($db_content_monthDay->count() > 0) {
                                $request_servce = Http::post($this->service_url . '/check_similarity', [
                                    'from_db' => $db_content_monthDay,
                                    'data_check' => $content,
                                ]);
                                if ((!boolval($request_servce->body()) && trim($content) != "" ) || (empty($GLOBALS['images']) && $GLOBALS['had_news_image'] === false )) {
                                    $news = new News;
                                    $news->title = $title;
                                    $news->title_img = $title_img;
                                    $news->summary = $summary;
                                    $news->content = $content;
                                    $news->date_publish = $datetime;
                                    $news->status = 1;
                                    $news->save();
                                    $news->images()->saveMany($GLOBALS['images']);
                                    $news->categories()->attach($GLOBALS['categories']);
                                }
                                echo $request_servce->body();
                            } else {
                                if (trim($content) != "" || (empty($GLOBALS['images']) && $GLOBALS['had_news_image'] === false )) {
                                    $news = new News;
                                    $news->title = $title;
                                    $news->title_img = $title_img;
                                    $news->summary = $summary;
                                    $news->content = $content;
                                    $news->date_publish = $datetime;
                                    $news->status = 1;
                                    $news->save();
                                    $news->images()->saveMany($GLOBALS['images']);
                                    $news->categories()->attach($GLOBALS['categories']);
                                }
                            }
                            $GLOBALS['images'] = [];
                            $GLOBALS['had_news_image'] = false;
                        }
                        }
                        
                );
            }
            $GLOBALS['categories'] = [];
        }catch(Exception $e){
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }
}