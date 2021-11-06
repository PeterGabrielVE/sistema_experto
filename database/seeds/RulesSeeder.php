<?php

use Illuminate\Database\Seeder;
use App\Rule;

class RulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Rule::create(['name'=>'Bajo peso','min'=>'0','max'=>'18.4']);
        Rule::create(['name'=>'Normal','min'=>'18.5','max'=>'24.9']);
        Rule::create(['name'=>'Sobrepeso','min'=>'25.0','max'=>'29.9']);
        Rule::create(['name'=>'Obesidad','min'=>'30.0','max'=>'más']);
    }
}
