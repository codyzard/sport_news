<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            'Bóng đá' => [
                'Việt Nam',
                'Anh',
                'Tây Ban Nha',
                'Đức',
                'Ý',
                'Pháp',
                'C1',
                'C2',
                // 'Euro',
                // 'World cup',
                'Các giải khác',
            ],
            'Chuyển nhượng',
            'Thể thao' => [
                'Quần vợt',
                'Golf',
                'Bóng rổ',
                'Bóng chuyền',
                'Các môn khác',
            ],
            'E-sports' => [
                'LoL', // thethao247
                'Fifa online 4', // ??
                'PUBG', // gamek, game8v
                'CSGO',
                'Mobile' //gamek, game8v
            ],
            'Khác',
            'Video',
        ];
        $this->create_category($categories);
    }

    public function create_category($categories,  $parent_id = null){
        foreach ($categories as $key => $value){
            if(is_array($value)){
                $temp = $parent_id;
                $parent_id = Category::create([
                    'name' => $key,
                    'parent_id' => $parent_id,
                ])->id;
                $this->create_category($value, $parent_id);
                $parent_id = $temp;
            }
            else{
                Category::create([
                    'name' => $value,
                    'parent_id' => $parent_id,
                ]);
            }
        }
        return;
    }
}
