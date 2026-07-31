<?php

use LBHurtado\FormFlowManager\Tests\TestCase;

uses(TestCase::class)->in('Unit', 'Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
