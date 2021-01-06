<?php

class PartyRow
{
    public $name = '';
    public function __construct($data)
    {
        $this->_data = $data;
        $this->name = $data['name'];
    }

    public function getColor()
    {
        return $this->_data['color'];
    }

    public function getParty()
    {
        return $this;
    }

    public function getImageURL($size)
    {
        $id = md5('lydatatw-party-' . $this->_data['name']);
        $default = $this->_data['img'];
        $grav_url = "https://www.gravatar.com/avatar/" . $id . "?d=" . urlencode( $default ) . "&s=" . intval($size);
        return $grav_url;
    }

}

class Party
{
    public static $_cache = array();

    public static function getParty($name)
    {
        if (array_key_exists($name, self::$_cache)) {
            return self::$_cache[$name];
        }

        $data = self::getPartyData()[$name];
        $data['name'] = $name;
        return self::$_cache[$name] = new PartyRow($data);
    }

    public static function getPartyData()
    {
        return array(
            '中國國民黨' => array(
                'img' => 'https://party.moi.gov.tw/politics/home!mark.action?id=ff808081542c937601542d21be91001b',
                'color' => '#1A21FD',
            ),
            '民主進步黨' => array(
                'img' => 'https://party.moi.gov.tw/politics/home!mark.action?id=ff808081542c937601542d16549d0015',
                'color' => '#519F1E',
            ),
            '親民黨' => array(
                'img' => 'https://party.moi.gov.tw/politics/home!mark.action?id=ff808081542c937601542d20fd53001a',
                'color' => '#EE8A37',
            ),
            '無黨籍' => array(
                'img' => '',
                'color' => '#FFFFFF',
            ),
            '新黨' => array(
                'img' => 'https://party.moi.gov.tw/politics/home!mark.action?id=ff808081542c937601542d14e6b30014',
                'color' => '#FDFEFD',
            ),
            '台灣團結聯盟' => array(
                'img' => 'https://party.moi.gov.tw/politics/home!mark.action?id=ff808081542c937601542d39a979002d',
                'color' => '#99461D',
            ),
            '時代力量' => array(
                'img' => 'https://party.moi.gov.tw/politics/home!mark.action?id=ff808081542c937601542cac968c000a',
                'color' => '#F7BE33',
            ),
            '台灣民眾黨' => array(
                'img' => 'https://www.tpp.org.tw/_nuxt/img/logo2.1192a20.png',
                'color' => '#5EC9C9',
            ),
            '建國黨' => array(
                'img' => '',
                'color' => '',
            ),
            '民國黨' => array(
                'img' => '',
                'color' => '',
            ),
            '台灣基進' => array(
                'img' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/77/Daijish%C5%8D_in_brown.svg/200px-Daijish%C5%8D_in_brown.svg.png',
                'color' => '#A83D22',
            ), 
        );
    }
}
