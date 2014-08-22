<?php

/**
 * A Table Header Class 
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class Table_Header extends Table_DataLine {
    
    
    /**
     * Returns line's layout or default layout
     * @author Julien Hoarau <jh@datasphere.ch>
     * @return Layout 
     */
    public function getLayout($forceParent = false)
    {
        $layout = $this->layout;
        if ($layout === null OR $forceParent) {
            $layout = $this->getTable()->getDefaultHeaderLayout();
        }
        
        return $layout;
    }
}

?>
