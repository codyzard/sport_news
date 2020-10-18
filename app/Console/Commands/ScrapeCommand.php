<?php

namespace App\Console\Commands;

use App\Helper\CrawlerHelper;
use App\Scraper\Bongdacomvn;
use App\Scraper\TheThao;
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
        $thethao->scrape();
        $bongda->scrape();
        CrawlerHelper::clean_image_trash();
    }
}
