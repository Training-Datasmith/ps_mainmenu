<?php

class Language
{
    public $id;

    public function __construct($id = null)
    {
        $this->id = (int) $id;
    }

    public static function getLanguages($active = true)
    {
        return array(
            array('id_lang' => 1, 'name' => 'English'),
            array('id_lang' => 2, 'name' => 'French'),
        );
    }
}
