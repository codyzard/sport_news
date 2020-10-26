<?php

namespace App\Console\Commands;

use App\Helper\CrawlerHelper;
use App\Scraper\Bongdacomvn;
use App\Scraper\Fo4;
use App\Scraper\Game8v;
use App\Scraper\TheThao;
use App\Scraper\Vikinggg;
use Illuminate\Console\Command;

class ScrapeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:crawl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $thethao = new TheThao;
        $bongda = new Bongdacomvn;
        $fo4 = new Fo4;
        $vikinggg = new Vikinggg;
        $game8v = new Game8v;
        $thethao->scrape();
        $bongda->scrape();
        $fo4->scrape();
        $vikinggg->scrape();
        $game8v->scrape();
        CrawlerHelper::clean_image_trash();
    }
}
