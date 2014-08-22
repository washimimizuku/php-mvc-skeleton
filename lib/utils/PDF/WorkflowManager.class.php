<?php
/**
 * A class manager for workflow pdfs
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class PDF_WorkflowManager extends PDF_Manager {
    
    /**
     * Create a pdf file of groups
     * @param array     $aGroups
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createGroupsPdf($aGroups = array(), $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle    = _("Groups");
        $this->initPdfFile($headerTitle);
        
        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }

        $groupsTable        = $this->generateGroupsTable($aGroups);
        $groupsTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aGroups));
        $nbRowsTable->PDFexport($this->pdf, true, true);
    }

    /**
     * Generate the group list table
     * @param type $aGroups
     * @return Table
     */
    private function generateGroupsTable($aGroups)
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['organization'] = 20;
        $aColumnsWidths['name']         = 20;
        $aColumnsWidths['users']        = 30;
        $aColumnsWidths['graphs']       = 30;

        // header of the section
        $header = UtilsManager::createTableHeader(
            array (
                array(_('Organization'), $aColumnsWidths['organization']),
                array(_('Name'),         $aColumnsWidths['name']),
                array(_('Users'),        $aColumnsWidths['users']),
                array(_('Graphs'),        $aColumnsWidths['graphs'])
            )
        );
        $table->setHeader($header);
        unset($header);
        
        $layoutEvenLines = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_LEFT.Layout::BORDER_RIGHT);
        $layoutOddLines = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235,230,225)));
        $i = 0;
        foreach ($aGroups as $group) {
            $layout = $layoutEvenLines;
            if ($i%2 != 0) {
                $layout = $layoutOddLines;
            }

            // access list informations
            $groupLine = UtilsManager::createTableDataLine(
                array (
                    array(trim($group->getOrganizationName()), $aColumnsWidths['organization'], $layout),
                    array(trim($group->dsGN), $aColumnsWidths['name'], $layout),
                    array(trim($group->getMembersNamesString()), $aColumnsWidths['users'], $layout),
                    array(trim($group->getGraphsNamesString()), $aColumnsWidths['graphs'], $layout)
                )
            );
            $table->addDataLine($groupLine);
            unset($groupLine);

            $i++;
        }

        return $table;
    }
    
    /**
     * Create a pdf file of filters groups
     * @param array     $aGroups
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createFiltersGroupsPdf($aGroups = array(), $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle    = _("Groups");
        $this->initPdfFile($headerTitle);
        
        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }

        $groupsTable        = $this->generateFiltersGroupsTable($aGroups);
        $groupsTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aGroups));
        $nbRowsTable->PDFexport($this->pdf, true, true);
    }
    
    /**
     * Generate the filters group list table
     * @param type $aGroups
     * @return Table
     */
    private function generateFiltersGroupsTable($aGroups)
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['organization'] = 20;
        $aColumnsWidths['type']         = 20;
        $aColumnsWidths['name']         = 20;
        $aColumnsWidths['contents']     = 40;

        // header of the section
        $header = UtilsManager::createTableHeader(
            array (
                array(_('Organization'), $aColumnsWidths['organization']),
                array(_('Type'),         $aColumnsWidths['type']),
                array(_('Name'),         $aColumnsWidths['name']),
                array(_('Contents'),     $aColumnsWidths['contents']),
            )
        );
        $table->setHeader($header);
        unset($header);
        
        $config = ApplicationConfig::getInstance();
        
        $layoutEvenLines = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_LEFT.Layout::BORDER_RIGHT);
        $layoutOddLines = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235,230,225)));
        $i = 0;
        foreach ($aGroups as $group) {
            $layout = $layoutEvenLines;
            if ($i%2 != 0) {
                $layout = $layoutOddLines;
            }

            // access list informations
            $groupLine = UtilsManager::createTableDataLine(
                array (
                    array(trim($group->getOrganizationName()), $aColumnsWidths['organization'], $layout),
                    array(trim($config->filtersGroupsTypes[$group->dsType]['value']), $aColumnsWidths['type'], $layout),
                    array(trim($group->dsGN), $aColumnsWidths['name'], $layout),
                    array(trim($group->getMembersNamesString()), $aColumnsWidths['contents'], $layout),
                )
            );
            $table->addDataLine($groupLine);
            unset($groupLine);

            $i++;
        }

        return $table;
    }
    
    /**
     * Create a pdf file of graph filters
     * @param array     $aGraphFilters
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createFiltersPdf($aGraphFilters = array(), $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle    = _("Filters");
        $this->initPdfFile($headerTitle);
        
        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }

        $graphFiltersTable        = $this->generateGraphFiltersTable($aGraphFilters);
        $graphFiltersTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aGraphFilters));
        $nbRowsTable->PDFexport($this->pdf, true, true);        
    }

    /**
     * Generate the graph filters list table
     * @param type $aGraphFilters
     * @return Table
     */
    private function generateGraphFiltersTable($aGraphFilters)
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['organization'] = 20;
        $aColumnsWidths['name']         = 20;
        $aColumnsWidths['description']  = 20;
        $aColumnsWidths['graphs']       = 20;
        $aColumnsWidths['systemActivation']       = 10;
        $aColumnsWidths['active']       = 10;

        // header of the section
        $header = UtilsManager::createTableHeader(
            array (
                array(_('Organization'),    $aColumnsWidths['organization']),
                array(_('Name'),            $aColumnsWidths['name']),
                array(_('Description'),     $aColumnsWidths['description']),
                array(_('Graphs'),          $aColumnsWidths['graphs']),
                array(_('System activation'),$aColumnsWidths['systemActivation']),
                array(_('Active'),$aColumnsWidths['active'])
                
            )
        );
        $table->setHeader($header);
        unset($header);
        
        $layoutEvenLines = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_LEFT.Layout::BORDER_RIGHT);
        $layoutOddLines = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235,230,225)));
        $i = 0;
        foreach ($aGraphFilters as $graphFilter) {
            $layout = $layoutEvenLines;
            if ($i%2 != 0) {
                $layout = $layoutOddLines;
            }
            
            $systemActivation = _('Yes');
            $active           = _('No');
            if ( isset($graphFilter->fiactive) && $graphFilter->fiactive == Filter_FilterManager::ACTIVE) {
                $active           = _('Yes');
            }
             
             if (isset($graphFilter->fiactspadm) && $graphFilter->fiactspadm == Filter_FilterManager::INACTIVE) {
                 $systemActivation = _('No');
                 $active           = _('No');
            } 

            // access list informations
            $graphFilterLine = UtilsManager::createTableDataLine(
                array (
                    array(trim($graphFilter->fiorganis), $aColumnsWidths['organization'], $layout),
                    array(trim($graphFilter->finame), $aColumnsWidths['name'], $layout),
                    array(trim($graphFilter->fidesc), $aColumnsWidths['description'], $layout),
                    array(trim($graphFilter->getGraphsNamesString()), $aColumnsWidths['graphs'], $layout),
                    array($systemActivation, $aColumnsWidths['systemActivation'], $layout),
                    array($active, $aColumnsWidths['active'], $layout)
                    
                )
            );
            $table->addDataLine($graphFilterLine);
            unset($graphFilterLine);

            $i++;
        }

        return $table;
    }
    
    /**
     * Create a pdf file of graphs
     * @param array     $aGraphs
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createGraphsPdf($aGraphs = array(), $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle    = _("Graphs");
        $this->initPdfFile($headerTitle);
        
        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }

        $graphsTable        = $this->generateGraphsTable($aGraphs);
        $graphsTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aGraphs));
        $nbRowsTable->PDFexport($this->pdf, true, true);    
    }

    /**
     * Generate the graphs list table
     * @param type $aGraphs
     * @return Table
     */
    private function generateGraphsTable($aGraphs)
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['organization']     = 10;
        $aColumnsWidths['name']             = 10;
        $aColumnsWidths['description']      = 20;
        $aColumnsWidths['users']            = 20;
        $aColumnsWidths['groups']           = 10;
        $aColumnsWidths['filter']           = 10;
        $aColumnsWidths['systemActivation'] = 10;
        $aColumnsWidths['active']           = 10;

        // header of the section
        $header = UtilsManager::createTableHeader(
            array (
                array(_('Organization'),    $aColumnsWidths['organization']),
                array(_('Name'),            $aColumnsWidths['name']),
                array(_('Description'),     $aColumnsWidths['description']),
                array(_('Users'),           $aColumnsWidths['users']),
                array(_('Groups'),          $aColumnsWidths['groups']),
                array(_('Filter'),          $aColumnsWidths['filter']),
                array(_('System activation'),$aColumnsWidths['systemActivation']),
                array(_('Active'),$aColumnsWidths['active'])
            )
        );
        $table->setHeader($header);
        unset($header);
        
        $layoutEvenLines = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_LEFT.Layout::BORDER_RIGHT);
        $layoutOddLines = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235,230,225)));
        $i = 0;
        foreach ($aGraphs as $graph) {
            $layout = $layoutEvenLines;
            if ($i%2 != 0) {
                $layout = $layoutOddLines;
            }
            $systemActivation = _('Yes');
            $active           = _('No');
            //$graph instanceof Workflow;
            if ( isset($graph->gractive) && $graph->gractive == Workflow_WorkflowManager::ACTIVE) {
                $active           = _('Yes');
            }
             
             if (isset($graph->gractspadm) && $graph->gractspadm == Workflow_WorkflowManager::INACTIVE) {
                 $systemActivation = _('No');
                 $active           = _('No');
            } 
            
            // access list informations
            $graphLine = UtilsManager::createTableDataLine(
                array (
                    array(trim($graph->grorganis), $aColumnsWidths['organization'], $layout),
                    array(trim($graph->grname), $aColumnsWidths['name'], $layout),
                    array(trim($graph->grdesc), $aColumnsWidths['description'], $layout),
                    array(trim($graph->getUsersNamesString()), $aColumnsWidths['users'], $layout),
                    array(trim($graph->getGroupsNamesString()), $aColumnsWidths['groups'], $layout),
                    array(trim($graph->getFiltersNamesString()), $aColumnsWidths['filter'], $layout),
                    array($systemActivation, $aColumnsWidths['systemActivation'], $layout),
                    array($active, $aColumnsWidths['active'], $layout)
                )
            );
            $table->addDataLine($graphLine);
            unset($graphLine);

            $i++;
        }

        return $table;
    }
}

?>
