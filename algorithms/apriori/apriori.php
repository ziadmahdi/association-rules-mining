<?php

class Apriori
{
   private $delimiter   = ',';
   private $minSup      = 0;

   private $table       = array();
   private $allthings   = array();
   private $allsups     = array();

   private $freqItmsts  = array();
   private $phase       = 1;

   //maxPhase>=2
   private $maxPhase    = 20;

   public function __construct($dataset, $minsupp, $allthings, $allsups, $table)
   {
      $this->dataset = $dataset;
      $this->minSup = $minsupp;
      $this->allthings = $allthings;
      $this->allsups = $allsups;
      $this->table = $table;
   }

   public function run()
   {
      $this->freqItemsets();


      $tmp = [];


      foreach ($this->freqItmsts as $k => $v) {

         $arr     = explode($this->delimiter, $k);

         $subsets = $this->subsets($arr);
         foreach ($subsets as $subset) {
            array_push($tmp, $subset);
         }
      }

      return array_map("unserialize", array_unique(array_map("serialize", $tmp)));
   }

   private function scan($arr)
   {
      $cr = 0;
      sort($arr);
      $implodeArr = implode($this->delimiter, $arr);
      if (isset($this->allsups[$implodeArr])) {
         return $this->allsups[$implodeArr];
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


   private function freqItemsets()
   {

      while (1) {

         if ($this->phase >= $this->maxPhase) {
            break;
         }

         $num = count($this->allthings[$this->phase]);

         $cr  = 0;
         for ($i = 0; $i < $num; $i++) {
            for ($j = $i; $j < $num; $j++) {
               if ($i == $j) {
                  continue;
               }

               $item = $this->combine($this->allthings[$this->phase][$i], $this->allthings[$this->phase][$j]);
               sort($item);

               $implodeArr = implode($this->delimiter, $item);
               if (!isset($this->freqItmsts[$implodeArr])) {

                  $sup = $this->scan($item, $implodeArr);
                  if ($sup >= $this->minSup) {
                     $this->allthings[$this->phase + 1][] = $item;
                     $this->freqItmsts[$implodeArr] = 1;
                     $cr++;
                  }
               }
            }
         }

         if ($cr <= 1) {
            break;
         }

         $this->phase++;
      }


      foreach ($this->freqItmsts as $k => $v) {
         $arr = explode($this->delimiter, $k);
         $num = count($arr);
         if ($num >= 3) {
            $subsets = $this->subsets($arr);
            $num1    = count($subsets);
            for ($i = 0; $i < $num1; $i++) {
               if (count($subsets[$i]) < $num) {
                  unset($this->freqItmsts[implode($this->delimiter, $subsets[$i])]);
               } else {
                  break;
               }
            }
         }
      }
   }
}
