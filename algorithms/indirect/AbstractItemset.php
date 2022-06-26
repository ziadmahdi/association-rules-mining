<?php

abstract class AbstractItemset
{
    
    abstract function size();
    
    abstract function toString();
   
    function print()
    {
        echo $this->toString();
    }
    
    abstract function getAbsoluteSupport();
    
    abstract function getRelativeSupport($nbObject);
   
    function getRelativeSupportAsString($nbObject)
    {
        // get the relative support
        $frequence = $this->getRelativeSupport($nbObject);
        // convert it to a string with two decimals
        
        return number_format($frequence,5);
    }
    
    abstract function contains($item);
}

