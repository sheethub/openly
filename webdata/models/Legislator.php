<?php

// http://data.ly.gov.tw/odw/usageFile.action?id=16&type=CSV&fname=16_CSV.csv
class LegislatorRow extends Pix_Table_Row
{
    public function getImageUrl($size)
    {
        $data = json_decode($this->data);
        $id = md5('lydatatw-' . $this->term . '-' . $this->name);
        $default = $data->picUrl;
        $grav_url = "https://www.gravatar.com/avatar/" . $id . "?d=" . urlencode( $default ) . "&s=" . intval($size);
        return $grav_url;
    }

    public function getParty()
    {
        $data = json_decode($this->data);
        return Party::getParty($data->party);
    }
}

class Legislator extends Pix_Table
{
    public function init()
    {
        $this->_name = 'legislator';
        $this->_primary = array('term', 'name');
        $this->_rowClass = 'LegislatorRow';

        $this->_columns['term'] = array('type' => 'int');
        $this->_columns['name'] = array('type' => 'varchar', 'size' => 32);
        $this->_columns['data'] = array('type' => 'jsonb');
    }

    protected static $_terms_names = null;

    public static function getNames($terms)
    {
        if (is_null(self::$_terms_names)) {
            self::$_terms_names = array();
            foreach (Legislator::search(1) as $l) {
                if (!array_key_exists(intval($l->term), self::$_terms_names)) {
                    self::$_terms_names[intval($l->term)] = array();
                }
                self::$_terms_names[intval($l->term)][] = $l->name;
            }
        }

        $ret = array();
        foreach ($terms as $t) {
            foreach (self::$_terms_names[intval($t)] as $n) {
                $nn = $n;
                $n = str_replace(' ', '', $n);
                $n = str_replace('‧', '', $n);
                $n = str_replace('．', '', $n);
                $ret[$n] = $nn;
            }
        }
        $ret['王琬諭'] = '王婉諭'; // 1091029070201800
        $ret['陳柏維'] = '陳柏唯'; // 1090410070203400
        $ret['吳志楊'] = '吳志揚'; // 1081017070200300
        $ret['鍾孔紹'] = '鍾孔炤'; // 1061204070200600
        $ret['蔡宜津'] = '葉宜津'; // 1060316070200100
        $ret['高金素'] = '高金素梅'; // 1050704070200500
        $ret['周陳秀'] = '周陳秀霞'; // 1050303070203600
        $ret['陳賴素'] = '陳賴素美'; // 1050301070202200

        $ret['謝衣.'] = '謝衣鳯';
        $ret['陳秀.'] = '陳秀寳';
        $ret['傅.崐萁'] = '傅崐萁';
        $ret['傅崐.萁'] = '傅崐萁';
        $ret['傅.萁'] = '傅崐萁';

        $ret['廖國棟'] = '廖國棟Sufin‧Siluko';
        $ret['伍麗華'] = '伍麗華Saidhai ‧Tahovecahe';
        $ret['簡東明'] = '簡東明Uliw．Qaljupayare';
        $ret['鄭天財'] = '鄭天財Sra．Kacaw';
        $ret['KolasYotaka'] = '谷辣斯．尤達卡Kolas Yotaka';
        $ret['kolasYotaka'] = '谷辣斯．尤達卡Kolas Yotaka';
        $ret['高潞以用巴魕剌KawloIyunacidal'] = '高潞．以用．巴魕剌Kawlo．Iyun．Pacidal'; // 1060522070202600
        $ret['高潞以用巴魕剌KawloIyunPacida'] = '高潞．以用．巴魕剌Kawlo．Iyun．Pacidal';
        $ret['高潞以用巴魕剌kawloIyunPacidal'] = '高潞．以用．巴魕剌Kawlo．Iyun．Pacidal';
        $ret['高潞以用巴魕剌'] = '高潞．以用．巴魕剌Kawlo．Iyun．Pacidal';

        $ret['高潞以用'] = '高潞．以用．巴魕剌Kawlo．Iyun．Pacidal';

        $ret['時代力量立法院黨團'] = '時代力量立法院黨團';
        $ret['台灣民眾黨立法院黨團'] = '台灣民眾黨立法院黨團';
        $ret['台灣民眾黨黨團'] = '台灣民眾黨立法院黨團';
        $ret['中國國民黨立法院黨團'] = '中國國民黨立法院黨團';
        $ret['民主進步黨立法院黨團'] = '民主進步黨立法院黨團';
        $ret['親民黨立法院黨團'] = '親民黨立法院黨團';
        return $ret;
    }

    public static function parseNames($str, $terms)
    {
        if (!is_array($terms)) {
            $terms = array($terms);
        }
        $hit = array();
        $str = preg_replace('#[^ 　]+草案#u', '', $str);
        $str = str_replace('　', '', $str);
        $str = str_replace("\r", '', $str);
        $str = str_replace("\n", '', $str);
        $str = str_replace(' ', '', $str);
        $str = str_replace('‧', '', $str);
        $str = str_replace('．', '', $str);
        $str = str_replace('&nbsp;', '', $str);
        $names = self::getNames($terms);
        $skip = '';
        while (strlen($str)) {
            foreach ($names as $n => $nn) {
                if (strpos($n, '.') !== false) {
                    if (preg_match("#^{$n}(.*)$#u", $str, $matches)) {
                        $str = $matches[1];
                        $hit[] = $nn;
                        continue 2;
                    }
                } else {
                    if (strpos($str, $n) === 0) {
                        $str = substr($str, strlen($n));
                        $hit[] = $nn;
                        continue 2;
                    }
                }
            }
            $skip .= mb_substr($str, 0, 1, 'UTF-8');
            $str = mb_substr($str, 1, 0, 'UTF-8');
        }
        return $hit;
    }
}
