<?php
namespace BackBuilder\Test\Mock;

use BackBuilder\Site\Site;

use Faker\Factory;

/**
 * @category    BackBuilder
 * @package     BackBuilder\TestUnit\Mock
 * @copyright   Lp system
 * @author      n.dufreche
 */
class MockSite extends Site implements IMock
{
    public function __construct()
    {
        $faker = Factory::create();
        $faker->seed(1337);
        parent::__construct($faker->md5);
    }
}