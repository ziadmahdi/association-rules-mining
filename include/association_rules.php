<?php

class AssociationRules
{
    private $freq;
    public $rules;
    public $table       = array();
    private $allsups     = array();
    private $keys        = array();
    private $delimiter = ',';

    private $minConf     = 0;


    public function __construct($freq, $minsup, $minconf, $keys, $allsups, $table)
    {
        $this->freq = $freq;
        $this->minConf = $minconf;
        $this->minSup = $minsup;
        $this->keys = $keys;
        $this->table = $table;
        $this->allsups = $allsups;
    }

    public function process()
    {
        $result = [];
        foreach ($this->freq as $k => $v) {
            $subsets = $this->subsets($v);

            $num     = count($subsets);
            for ($i = 0; $i < $num; $i++) {
                for ($j = 0; $j < $num; $j++) {

                    if ($this->checkRule($subsets[$i], $subsets[$j])) {

                        $n1 = $this->realName($subsets[$i]);
                        $n2 = $this->realName($subsets[$j]);

                        $scan = $this->scan($this->combine($subsets[$i], $subsets[$j]));

                        $c1   = $this->confidence($this->scan($subsets[$i]), $scan);
                        $c2   = $this->confidence($this->scan($subsets[$j]), $scan);

                        if ($c1 >= $this->minConf) {
                            $result[$n1][$n2] = $c1;
                        }

                        if ($c2 >= $this->minConf) {
                            $result[$n2][$n1] = $c2;
                        }

                        $checked[$n1 . $this->delimiter . $n2] = 1;
                        $checked[$n2 . $this->delimiter . $n1] = 1;
                    }
                }
            }
        }

        return $this->rules = $result;
    }
    private function subsets($items)
    {

        $result  = array();
        $num     = count($items);
        $members = pow(2, $num);
        for ($i = 0; $i < $members; $i++) {
            $b   = sprintf("%0" . $num . "b", $i);
            $tmp = array();
            for ($j = 0; $j < $num; $j++) {
                if ($b[$j] == '1') {
                    $tmp[] = $items[$j];
                }
            }

            if ($tmp) {
                sort($tmp);
                $result[] = $tmp;
            }
        }
        return $result;
    }
    //1-2=>2-3 : false
    //1-2=>5-6 : true
    private function checkRule($a, $b)
    {
        $a_num = count($a);
        $b_num = count($b);
        for ($i = 0; $i < $a_num; $i++) {
            for ($j = 0; $j < $b_num; $j++) {
                if ($a[$i] == $b[$j]) {
                    return false;
                }
            }
        }

        return true;
    }
    private function realName($arr)
    {

        $result = '';
        $num = count($arr);
        for ($j = 0; $j < $num; $j++) {
            if ($j) {
                $result .= $this->delimiter;
            }

            $result .= $this->keys['k->v'][$arr[$j]];
        }
        //echo($result).'<br>';
        return $result;
    }
    private function scan($arr, $implodeArr = '')
    {

        $cr = 0;

        if ($implodeArr) {
            if (isset($this->allsups[$implodeArr])) {
                return $this->allsups[$implodeArr];
            }
        } else {
            sort($arr);
            $implodeArr = implode($this->delimiter, $arr);
            if (isset($this->allsups[$implodeArr])) {
                return $this->allsups[$implodeArr];
            }
        }

        $num  = count($this->table);
        $num1 = count($arr);
        for ($i = 0; $i < $num; $i++) {
            $bool = true;
            for ($j = 0; $j < $num1; $j++) {
                if (!isset($this->table[$i][$arr[$j]])) {
                    $bool = false;
                    break;
                }
            }

            if ($bool) {
                $cr++;
            }
        }

        $this->allsups[$implodeArr] = $cr;

        return $cr;
    }
    private function combine($arr1, $arr2)
    {
        $result = array();

        $num  = count($arr1);
        $num1 = count($arr2);
        for ($i = 0; $i < $num; $i++) {
            if (!isset($result['k'][$arr1[$i]])) {
                $result['v'][] = $arr1[$i];
                $result['k'][$arr1[$i]] = 1;
            }
        }

        for ($i = 0; $i < $num1; $i++) {
            if (!isset($result['k'][$arr2[$i]])) {
                $result['v'][] = $arr2[$i];
                $result['k'][$arr2[$i]] = 1;
            }
        }

        return $result['v'];
    }
    private function confidence($sup_a, $sup_ab)
    {
        //echo $sup_ab.'/'.$sup_a.'<br>';
        return round(($sup_ab / $sup_a) * 100, 2);
    }
}
