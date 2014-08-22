<?php
/**
 * A class manager for general pdfs
 *
 * @author Nuno Barreto <nb@datasphere.ch>
 */
class PDF_GeneralManager extends PDF_Manager {

    private $aModulesAccessesDefault    = null;
    
    /**
     * Create a pdf file of access lists
     * @param array     $aAccessLists
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createAccessListsPdf($aAccessLists, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle    = _("Access Lists");
        $this->initPdfFile($headerTitle);
        
        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }

        $accessListsTable = $this->generateAccessListsTable($aAccessLists);
        $accessListsTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aAccessLists));
        $nbRowsTable->PDFexport($this->pdf, true, true);
    }

    /**
     * Generate the access list list table
     * @param array $aAccessLists
     * @return Table
     */
    private function generateAccessListsTable($aAccessLists)
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
        $aColumnsWidths['users']        = 60;

        // header of the section
        $header = UtilsManager::createTableHeader(
            array (
                array(_('Organization'), $aColumnsWidths['organization']),
                array(_('Name'),         $aColumnsWidths['name']),
                array(_('Users'),        $aColumnsWidths['users'])
            )
        );
        $table->setHeader($header);
        unset($header);

        $layoutEvenLines = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_LEFT.Layout::BORDER_RIGHT);
        $layoutOddLines = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235,230,225)));
        $i = 0;
        foreach ($aAccessLists as $accessList) {
            $layout = $layoutEvenLines;
            if ($i%2 != 0) {
                $layout = $layoutOddLines;
            }

            // access list informations
            $accessListLine = UtilsManager::createTableDataLine(
                array (
                    array(trim($accessList->getOrganizationName()), $aColumnsWidths['organization'], $layout),
                    array(trim($accessList->dsALN), $aColumnsWidths['name'], $layout),
                    array(trim($accessList->getLinkedUsersString()), $aColumnsWidths['users'], $layout),
                )
            );
            $table->addDataLine($accessListLine);
            unset($accessListLine);

            $i++;
        }

        return $table;
    }

    /**
     * Create a pdf file of users
     * @param array     $aUsers
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createUsersPdf($aUsers, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle    = _("Users");
        $this->initPdfFile($headerTitle);

        if (count($aFilters)) {
            $filtersLine = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }

        $usersTable = $this->generateUsersTable($aUsers);
        $usersTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aUsers));
        $nbRowsTable->PDFexport($this->pdf, true, true);
    }

    /**
     * Generate the user list table
     * @param type $aUsers
     * @return Table
     */
    private function generateUsersTable($aUsers)
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);

        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['certificate'] = 10;
        $aColumnsWidths['name']        = 10;
        $aColumnsWidths['accessLists'] = 20;
        $aColumnsWidths['roles']       = 20;
        $aColumnsWidths['permissions'] = 20;
        $aColumnsWidths['email']       = 20;

        // header of the section
        $header = UtilsManager::createTableHeader(
            array (
                array(_('Certificate'),      $aColumnsWidths['certificate']),
                array(_('Name'),             $aColumnsWidths['name']),
                array(_('Access Lists'),     $aColumnsWidths['accessLists']),
                array(_('Roles'),            $aColumnsWidths['roles']),
                array(_('User Permissions'), $aColumnsWidths['permissions']),
                array(_('Email'),            $aColumnsWidths['email'])
            )
        );
        $table->setHeader($header);
        unset($header);

        $layoutEvenLines = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_LEFT.Layout::BORDER_RIGHT);
        $layoutOddLines = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235,230,225)));
        $i = 0;
        foreach ($aUsers as $user) {
            $layout = $layoutEvenLines;
            if ($i%2 != 0) {
                $layout = $layoutOddLines;
            }

            // access list informations
            $userLine = UtilsManager::createTableDataLine(
                array (
                    array(trim($user->cn), $aColumnsWidths['certificate'], $layout),
                    array(trim($user->sn), $aColumnsWidths['name'], $layout),
                    array(trim($user->getAuthorizationListsNamesString()), $aColumnsWidths['accessLists'], $layout),
                    array(trim($user->getRolesNamesString()), $aColumnsWidths['roles'], $layout),
                    array(trim($user->getPermissionsNamesString()), $aColumnsWidths['permissions'], $layout),
                    array(trim($user->mail), $aColumnsWidths['email'], $layout),
                )
            );
            $table->addDataLine($userLine);
            unset($userLine);

            $i++;
        }

        return $table;
    }

    /**
     * Create a pdf file of roles
     * @param array     $aRoles
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createRolesPdf($aRoles, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle    = _("Roles");
        $this->initPdfFile($headerTitle);

        if (count($aFilters)) {
            $filtersLine = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }

        $rolesTable = $this->generateRolesTable($aRoles);
        $rolesTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aRoles));
        $nbRowsTable->PDFexport($this->pdf, true, true);
    }

    /**
     * Generate the roles list table
     * @param type $aRoles
     * @return Table
     */
    private function generateRolesTable($aRoles)
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);

        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['name']       = 20;
        $aColumnsWidths['permissions'] = 30;
        $aColumnsWidths['users']       = 50;

        // header of the section
        $header = UtilsManager::createTableHeader(
            array (
                array(_('Name'),        $aColumnsWidths['name']),
                array(_('Permissions'), $aColumnsWidths['permissions']),
                array(_('Users'),       $aColumnsWidths['users'])
            )
        );
        $table->setHeader($header);
        unset($header);

        $layoutEvenLines = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_LEFT.Layout::BORDER_RIGHT);
        $layoutOddLines = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235,230,225)));
        $i = 0;
        foreach ($aRoles as $role) {
            $layout = $layoutEvenLines;
            if ($i%2 != 0) {
                $layout = $layoutOddLines;
            }

            // access list informations
            $roleLine = UtilsManager::createTableDataLine(
                array (
                    array(trim($role->dsRN), $aColumnsWidths['name'], $layout),
                    array(trim($role->getPermissionsNamesString()), $aColumnsWidths['permissions'], $layout),
                    array(trim($role->getUsersNamesString()), $aColumnsWidths['users'], $layout),
                )
            );
            $table->addDataLine($roleLine);
            unset($roleLine);

            $i++;
        }

        return $table;
    }

    
     /**
     * Create a detailed pdf file of roles
     * @param array     $aRoles
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createAccessDetailedPdf($aAccess, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle    = _("Access Lists");
        $this->initPdfFile($headerTitle);
        
        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }
        
        foreach($aAccess as $access){
            $accessSection = $this->generateAccessDetailedSection($access);
            
            $accessSection->PDFexport($this->pdf, true, true);
            if(($access) !== end($aAccess)) {
                $this->pdf->AddPage();
            }
        }

       // $rolesTable = $this->generateRolesDetailedTable($aRoles);
       
    }
     /**
     * Create a detailed pdf file of roles
     * @param array     $aRoles
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createRolesDetailedPdf($aRoles, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle    = _("Roles");
        $this->initPdfFile($headerTitle);

        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }
        
        foreach($aRoles as $role){
            
            $rolesSection = $this->generateRolesDetailedTable($role);
            
            $rolesSection->PDFexport($this->pdf, true, true);
             if(($role) !== end($aRoles)) {
                $this->pdf->AddPage();
            }
    
        }

       // $rolesTable = $this->generateRolesDetailedTable($aRoles);
       
    }
    
    private function generatePermissionDetailed($role = null , $permissionList = null , $withHeader = true , $withRoleTile = false) {
        $table = new Table();
        $line = 0;
        $table->setDefaultLayout($this->defaultLayout);       
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        $aColumnsWidths['roleLeftColumn'] = 20;
        $aColumnsWidths['roleRightColumn'] = 80;
        $layoutEvenLines = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_LEFT . layout::BORDER_RIGHT);
        
        $layoutOddLines = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235, 230, 225)));
        $layoutTop = clone $layoutEvenLines;
        $layoutTop->setBorder(Layout::BORDER_TOP);
        
        $layoutPermissionTitle = clone $this->defaultHeaderLayout;
        $layoutPermissionTitle->setAlign(Layout::ALIGN_LEFT);
        $layoutPermissionTitle->setFillColor(new Layout_Color(array(153, 153, 153)));
        $aAuthList = array();
                
        $config = ApplicationConfig::getInstance();
        
        if (isset($role) AND $role->dsRN) {
            $aAuthList = $role->getAuthorizationListsNames();
            if ($withRoleTile) {
                $roleName = UtilsManager::createTableDataLine(array(
                            array(_('Role name') . ' : ' . $role->dsRN, 0, $this->defaultHeaderLayout)
                                )
                );
                $table->addDataLine($roleName);
                unset($roleName);
            }
        }
  
        if(isset($permissionList)){
            $aAuthList[]    = $permissionList->dsALN;
        }
        
        $organization       = LDAPOrganization_Manager::getOrganization($config->o);
        $aCompanies         = Company_Manager::getCompanies(null, null, null, false);
        $aDataLines         = array();
        $isFirst = true;
        
        foreach ($aAuthList as $list) {
            
            $line = 0;
            $roleAuthList = LDAPAuthorizationsList_Manager::getRoleAuthorizationsList($list, $organization->getDN());
            $aValues = $roleAuthList->getAuthorizationValues();
            $splitModules = explode('_', $roleAuthList->getModulesNames(), 2);
            $moduleName = $splitModules[0];

            //Permission title
            if ($list) {
                
                if ($withHeader) {

                    $listName = UtilsManager::createTableHeader(array(
                                array(_('Permission name') . ' : ' . $list, 0, $layoutPermissionTitle)
                                    )
                    );
              
                    $table->setHeader($listName);
                    
                }
                
                //modules           
                $splitModules = explode('_', $roleAuthList->getModulesNames(), 2);
                $moduleName = $splitModules[0];

                if ($moduleName) {
                   
                    $layout = clone $layoutEvenLines;
                    if ($line % 2 != 0) {
                        $layout = clone $layoutOddLines;
                    }
                     if($isFirst){
                         $layout->setBorder(Layout::BORDER_LEFT . layout::BORDER_RIGHT . Layout::BORDER_TOP);
                         $isFirst = false;                       
                    }
                    
                    $module = UtilsManager::createTableDataLine(array(
                                array(_('Module'), $aColumnsWidths['roleLeftColumn'], $layout),
                                array($moduleName, $aColumnsWidths['roleRightColumn'], $layout)
                                    )
                    );
                    $table->addDataLine($module);
                    $line++;
                }

                //section           
                $section = isset($splitModules[1]) ? $splitModules[1] : '';
                if ($section) {
                    $layout = clone $layoutEvenLines;
                    if ($line % 2 != 0) {
                        $layout = clone $layoutOddLines;
                    }
                     if($isFirst){
                         $layout->setBorder(Layout::BORDER_LEFT . layout::BORDER_RIGHT . Layout::BORDER_TOP);
                         $isFirst = false;                       
                    }
                    $sections = UtilsManager::createTableDataLine(array(
                                array(_('Sections'), $aColumnsWidths['roleLeftColumn'], $layout),
                                array($section, $aColumnsWidths['roleRightColumn'], $layout)
                                    )
                    );
                    $table->addDataLine($sections);
                    $line++;
                }
                
                //Detail
                if (isset($aValues['DETAIL'])) {
                    $layout = clone $layoutEvenLines;
                    if ($line % 2 != 0) {
                        $layout = clone $layoutOddLines;
                    }
                     if($isFirst){
                         $layout->setBorder(Layout::BORDER_LEFT . layout::BORDER_RIGHT . Layout::BORDER_TOP);
                         $isFirst = false;                       
                    }
                    
                    $detail     = UtilsManager::createTableDataLine(array(
                                array(_('Detail'), $aColumnsWidths['roleLeftColumn'], $layout),
                                array(($aValues['DETAIL'])?_('Yes'):_('No'), $aColumnsWidths['roleRightColumn'], $layout)
                                    )
                    );
                    $table->addDataLine($detail);
                    unset($detail);
                    $line++;
                }
                
                //get companies (thank you for the bad key name :( , COMPANIES should be better ...)
                if (isset($aValues['COMPANY'])) {
                    $layout = clone $layoutEvenLines;
                    if ($line % 2 != 0) {
                        $layout = clone $layoutOddLines;
                    }
                     if($isFirst){
                         $layout->setBorder(Layout::BORDER_LEFT . layout::BORDER_RIGHT . Layout::BORDER_TOP);
                         $isFirst   = false;                       
                    }
                    
                    $aCompaniesNames    = array();
                    foreach ($aValues['COMPANY'] as $value) {
                        $value  = trim($value);
                        if ($value === LDAPAuthorization_Manager::VALUE_ALL) {
                            $aCompaniesNames[]    = _('All');
                        } else {
                            $aCompaniesNames[] = $aCompanies[$value]->sonomsoc . ' (' . $aCompanies[$value]->socodsoc . ')';
                        }
                    }
                    
                    $companies = UtilsManager::createTableDataLine(array(
                                array(_('Companies'), $aColumnsWidths['roleLeftColumn'], $layout),
                                array(join(", ", $aCompaniesNames), $aColumnsWidths['roleRightColumn'], $layout)
                                    )
                    );
                    $table->addDataLine($companies);
                    // unset($companies);
                    $line++;
                }

                //get "REAL" account
                if (isset($aValues['ACCOUNT'])) {
                    $layout = clone $layoutEvenLines;
                    if ($line % 2 != 0) {
                        $layout = clone $layoutOddLines;
                    }
                    
                    if($isFirst){
                        $layout->setBorder(Layout::BORDER_LEFT . layout::BORDER_RIGHT . Layout::BORDER_TOP);
                        $isFirst = false;                       
                    }
                    
                    $aAccounts = array();
                    foreach ($aValues['ACCOUNT'] as $value) {
                        if ($value === LDAPAuthorization_Manager::VALUE_ALL) {
                            $aAccounts[]    = _('All');
                        } else {
                            $splitAccount = explode('-', $value);
                            if ($splitAccount[0]) {
                                $aAccounts[] = $splitAccount[0] . ' (' . $splitAccount[2] . ')';
                            } else {
                                $aAccounts[] = $splitAccount[1] . ' (' . $splitAccount[2] . ')';
                            }
                        }
                    }
                    //display accounts 
                    $accounts = UtilsManager::createTableDataLine(array(
                                array(_('Accounts'), $aColumnsWidths['roleLeftColumn'], $layout),
                                array(join(", ", $aAccounts), $aColumnsWidths['roleRightColumn'], $layout)
                                    )
                    );
                   $table->addDataLine($accounts);
                    unset($accounts);
                    $line++;
                }

                //operation Type
                if (isset($aValues['OPERATION_TYPE'])) {
                    $layout = clone $layoutEvenLines;
                    if ($line % 2 != 0) {
                        $layout = clone $layoutOddLines;
                    }
                    
                    if($isFirst){
                        $layout->setBorder(Layout::BORDER_LEFT . layout::BORDER_RIGHT . Layout::BORDER_TOP);
                        $isFirst = false;                       
                    }
                    
                    $aOpeType = array();
                    foreach ($aValues['OPERATION_TYPE'] as $value) {
                        if ($value === LDAPAuthorization_Manager::VALUE_ALL) {
                            $aOpeType[]    = _('All');
                        } else {
                            $aOpeType[] = $value;
                        }
                    }
                    $operationTypes = UtilsManager::createTableDataLine(array(
                                array(_('Operation type'), $aColumnsWidths['roleLeftColumn'], $layout),
                                array(join(", ", $aOpeType), $aColumnsWidths['roleRightColumn'], $layout)
                                    )
                    );
                    $table->addDataLine($operationTypes);
                    unset($operationTypes);
                    $line++;
                }

                //Currencies
                if (isset($aValues['CURRENCY'])) {
                    $layout = clone $layoutEvenLines;
                    if ($line % 2 != 0) {
                        $layout = clone $layoutOddLines;
                    }
                     if($isFirst){
                         $layout->setBorder(Layout::BORDER_LEFT . layout::BORDER_RIGHT . Layout::BORDER_TOP);
                         $isFirst = false;                       
                    }
                    
                    $aCurrencies = array();
                    foreach ($aValues['CURRENCY'] as $value) {
                        if ($value === LDAPAuthorization_Manager::VALUE_ALL) {
                            $aCurrencies[]    = _('All');
                        } else {
                            $aCurrencies[] = $value;
                        }
                    }
                    $currencies = UtilsManager::createTableDataLine(array(
                                array(_('Currencies'), $aColumnsWidths['roleLeftColumn'], $layout),
                                array(join(", ", $aCurrencies), $aColumnsWidths['roleRightColumn'], $layout)
                                    )
                    );
                    $table->addDataLine($currencies);
                    unset($currencies);
                    $line++;
                }
                
                //Amounts
                if (isset($aValues['AMOUNT'])) {
                    $aValues['AMOUNT'] = reset($aValues['AMOUNT']);
                    $layout = clone $layoutEvenLines;
                    if ($line % 2 != 0) {
                        $layout = clone $layoutOddLines;
                    }
                     if($isFirst){
                         $layout->setBorder(Layout::BORDER_LEFT . layout::BORDER_RIGHT . Layout::BORDER_TOP);
                         $isFirst = false;                       
                    }
                    
                    $sAmounts = ''; 
                    if ($aValues['AMOUNT'][0] != '') {
                        $sAmounts .= $aValues['AMOUNT'][0].' < '._('amount');
                    }
                    if ($aValues['AMOUNT'][1] != '') {
                        if (!$sAmounts) {
                            $sAmounts .= _('amount');
                        }
                        $sAmounts .= ' < '.$aValues['AMOUNT'][1];
                    }
                    
                    $amounts = UtilsManager::createTableDataLine(array(
                                array(_('Amounts'), $aColumnsWidths['roleLeftColumn'], $layout),
                                array($sAmounts, $aColumnsWidths['roleRightColumn'], $layout)
                                    )
                    );
                    $table->addDataLine($amounts);
                    unset($amounts);
                    $line++;
                }
            }
            
        }
        $section = new Section();
        $section->main = $table;
        $section->pageBreakTrigger  = $line*$table->getDefaultLineHeight();
        $section->isCompactView     = true;
        
        return $section;
        
    }

    /**
     * Generate the roles detailed list table
     * @param LDAPRole $role
     * @return Table
     */
    private function generateRolesDetailedTable($role) {
        
        // init the table
        $config = ApplicationConfig::getInstance();
        $table = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);

        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['roleName'] = 100;
        $aColumnsWidths['roleLeftColumn'] = 20;
        $aColumnsWidths['roleRightColumn'] = 80;
        $layoutEvenLines = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_ALL);
        $layoutOddLines = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235, 230, 225)));
        //Summary section
        //role name
        $roleName = UtilsManager::createTableDataLine(array(
                    array(_('Role name') . ' : ' . $role->dsRN, 0, $this->defaultHeaderLayout)
                        )
        );
        $table->addDataLine($roleName);
        unset($roleName);

        //Organization
        $organization = UtilsManager::createTableDataLine(array(
                    array(_('Organization'), $aColumnsWidths['roleLeftColumn'], $layoutEvenLines),
                    array($config->o, $aColumnsWidths['roleRightColumn'], $layoutEvenLines)
                        )
        );
        $table->addDataLine($organization);
        unset($organization);

        //Permissions
        $permissionsList = UtilsManager::createTableDataLine(array(
                    array(_('Permissions'), $aColumnsWidths['roleLeftColumn'], $layoutOddLines),
                    array($role->getPermissionsNamesString(), $aColumnsWidths['roleRightColumn'], $layoutOddLines)
                        )
        );
        $table->addDataLine($permissionsList);
        unset($permissionsList);

        //Users
        $userList = UtilsManager::createTableDataLine(array(
                    array(_('Users'), $aColumnsWidths['roleLeftColumn'], $layoutEvenLines),
                    array($role->getUsersNamesString(), $aColumnsWidths['roleRightColumn'], $layoutEvenLines)
                        )
        );

        $table->addDataLine($userList);
        unset($userList);
        $section                    = new Section();
        $section->main              = $table;
        $section->aSubSections[]    = $this->generatePermissionDetailed($role  , null , true);

        return $section;
    }
    
     /**
     * Generate the access detailed list table
     * @param LDAPAuthorizationsList_Access $access
     * @return Table
     */
    private function generateAccessDetailedSection(LDAPAuthorizationsList_Access $access, $withUserHeader = true) {

        // init the table
        $table = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        $layoutPermissionTitle = clone $this->defaultHeaderLayout;
        //$layoutPermissionTitle->setAlign(Layout::ALIGN_LEFT);
        $layoutPermissionTitle->setFillColor(new Layout_Color(array(153, 153, 153)));

        // columns widths
        $aColumnsWidths                = array();
        $aColumnsWidths['name']        = 100;
        $aColumnsWidths['leftColumn']  = 20;
        $aColumnsWidths['rightColumn'] = 80;

        //Summary section
        //role name
        $accessName = UtilsManager::createTableDataLine(array(
                    array(_('Access name') . ' : ' . $access->dsALN, $aColumnsWidths['name'], $layoutPermissionTitle)
                        )
        );
        $table->addDataLine($accessName);
        unset($accessName);

        if ($withUserHeader) {
            //Organization
            $organization = UtilsManager::createTableDataLine(array(
                        array(_('Organization'), $aColumnsWidths['leftColumn'], $this->defaultHorizontalTitleLayout),
                        array($access->getOrganizationName(), $aColumnsWidths['rightColumn'], $this->defaultHorizontalValueLayout)
                            )
            );
            $table->addDataLine($organization);
            unset($organization);


            //Users
            $userList = UtilsManager::createTableDataLine(array(
                        array(_('Users'), $aColumnsWidths['leftColumn'], $this->defaultHorizontalTitleLayout),
                        array($access->getLinkedUsersString(), $aColumnsWidths['rightColumn'], $this->defaultHorizontalValueLayout)
                            )
            );
            $table->addDataLine($userList);
            unset($userList);
        }
        
        $section                 = new Section();
        $section->isCompactView  = true;
        $section->main           = $table;
        $section->aSubSections[] = $this->generateAccessesTable($access);

        return $section;
    }
    
    
    private function generateAccessesTable(LDAPAuthorizationsList_Access $access)
    {

        $table = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);
        
        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['moduleName']   = 40;
        $aColumnsWidths['access']       = 12;
        $aColumnsWidths['create']       = 12;
        $aColumnsWidths['edit']         = 12;
        $aColumnsWidths['erase']        = 12;
        $aColumnsWidths['print']        = 12;

        // header of the section
        $header                 = UtilsManager::createTableHeader(array (
                                                                    array(_('Module'),                      $aColumnsWidths['moduleName']),
                                                                    array(_('Access'),                      $aColumnsWidths['access']),
                                                                    array(_('Create'),                      $aColumnsWidths['create']),
                                                                    array(_('Edit'),                        $aColumnsWidths['edit']),
                                                                    array(_('Erase'),                       $aColumnsWidths['erase']),
                                                                    array(_('Print'),                       $aColumnsWidths['print'])
                                                                    )
                                                             );
        $table->setHeader($header);
        unset($header);
        
        $this->initAccessesDefault();
        $aModulesAccesses   = array_replace_recursive ($this->aModulesAccessesDefault, $access->getModulesAccesses(false, true));
        $layoutEvenLines    = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_LEFT.Layout::BORDER_RIGHT);
        $layoutOddLines     = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235,230,225)));
        $i                  = 0;
        foreach ($aModulesAccesses as $pathName=>$moduleAccesses) {
            if (!isset($moduleAccesses['moduleName'])) {
                continue;
            }
            
            $layout             = clone $layoutEvenLines;
            if ($i%2 != 0) {
                $layout         = clone $layoutOddLines;
            }
            
            $layoutValues       = clone $layout;
            $layoutValues->setAlign(Layout::ALIGN_CENTER);
            $layoutValues->setFont(new Layout_Font('', 'B'));
            
            // setting font style
            $isSubModule        = (count(explode('_', $pathName)) > 1);
            $font               = new Layout_Font();
            $font->size         = 10;
            if ($moduleAccesses['isParent']) {
                $font->style    = 'B';
                $font->size     -= ($isSubModule)?1:0;
            } else {
                $font->size         = 8;
            }
            if ($isSubModule) {
                $layout->setAlign(Layout::ALIGN_RIGHT);
            }
            $layout->setFont($font);
            
            // module accesses
            $moduleLine         = UtilsManager::createTableDataLine(array (
                                                                        array($moduleAccesses['moduleName'],                                                            $aColumnsWidths['moduleName'], $layout),
                                                                        array(($moduleAccesses['access'])?_('Yes'):_('No'),                                             $aColumnsWidths['access'], $layoutValues),
                                                                        array(($moduleAccesses['create'] !== null)?($moduleAccesses['create'])?_('Yes'):_('No'):'',     $aColumnsWidths['create'], $layoutValues),
                                                                        array(($moduleAccesses['edit'] !== null)?($moduleAccesses['edit'])?_('Yes'):_('No'):'',         $aColumnsWidths['edit'], $layoutValues),
                                                                        array(($moduleAccesses['erase'] !== null)?($moduleAccesses['erase'])?_('Yes'):_('No'):'',       $aColumnsWidths['erase'], $layoutValues),
                                                                        array(($moduleAccesses['print'] !== null)?($moduleAccesses['print'])?_('Yes'):_('No'):'',       $aColumnsWidths['print'], $layoutValues)
                                                                        )
                                                                );
            $table->addDataLine($moduleLine);
            unset($moduleLine);
            $i++;
        }
        
        return $table;
    }
    
     /**
     * Create a detailed pdf file of permissions
     * @param array     $aPermissions
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createPermissionsDetailedPdf($aPermissions, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle    = _("Permissions Lists");
        $this->initPdfFile($headerTitle);
        
        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }
        
       // $this->pdf->AddPage();
        foreach($aPermissions as $permission){
            
            $section    = $this->generatePermissionsDetailedSection($permission);
            $section->PDFexport($this->pdf, true, true);
                        
            if(($permission) !== end($aPermissions)) {
               $this->pdf->AddPage();
            }
    
        }
//        $this->pdf->AddPage();
    }
    
     /**
     * Generate the permission detailed list table
     * @param LDAPAuthorizationsList $permission
     * @return Table
     */
    private function generatePermissionsDetailedSection(LDAPAuthorizationsList $permission) {
        
         // init the table       
        $config = ApplicationConfig::getInstance();
        $organization = $config->o;
        $table = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);

        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['roleName'] = 100;
        $aColumnsWidths['roleLeftColumn'] = 20;
        $aColumnsWidths['roleRightColumn'] = 80;
        
        $layoutEvenLines =  clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_ALL);
        
        //Role title 
        $layoutRoleTitle = clone $this->defaultHeaderLayout;
        $layoutRoleTitle->setAlign(Layout::ALIGN_LEFT);
        
        //Permission Tile layout
        $layoutPermissionTitle = clone $this->defaultHeaderLayout;
        $layoutPermissionTitle->setAlign(Layout::ALIGN_LEFT);
        $layoutPermissionTitle->setFillColor(new Layout_Color(array(153, 153, 153)));
        
        //User list layout
        $layoutUserList = clone $this->defaultHorizontalValueLayout;
        $layoutUserList->setAlign(Layout::ALIGN_LEFT);
        $layoutUserList->setBorder(Layout::BORDER_ALL);
        $layoutUserList->setFillColor(new Layout_Color(array(255, 255, 255)));
        
         //Summary section
        //role name
        $type = LDAPAuthorizationsList_Manager::getAuthorizationListPrefixByName($permission->dsALN);
        $section                    = new Section();
        switch ($type) {
            case LDAPAuthorizationsList_Manager::PREFIX_AUTH_ACCESS:
                 $accessName = UtilsManager::createTableDataLine(array(
                            array(_('Access list name') . ' : ' . $permission->dsALN, 0, $layoutPermissionTitle)
                                )
                        ); 
                       
                $table->addDataLine($accessName);              
                unset($accessName);
                              
                $accessTable             = $this->generateAccessesTable($permission);
                $section->aSubSections[] = $accessTable;
             
                break;
            case LDAPAuthorizationsList_Manager::PREFIX_AUTH_ROLE:                            
                
                $permissionName = UtilsManager::createTableDataLine(array(
                            array(_('Role permission') . ' : ' . $permission->dsALN, 0, $layoutPermissionTitle)
                                )
                );
                $table->addDataLine($permissionName);           
                unset($permissionName);

                $roleName = UtilsManager::createTableDataLine(array(
                            array(_('Role name') . ' : ' . $permission->getRolesNamesString(), 0, $layoutRoleTitle)
                                )
                        );              
                $table->addDataLine($roleName);              
                unset($roleName);
                
//                $role = LDAPRole_Manager::getRole($permission->getRolesNamesString(), $organization);
                $linkedUsers = UtilsManager::createTableDataLine(array(
                            array(_('Linked users') . ' : ' . $permission->getLinkedUsersString(), 0,$layoutUserList)
                                )
                        );   
                               
                $table->addDataLine($linkedUsers);
                unset($linkedUsers);
                $section->aSubSections[] = $this->generatePermissionDetailed(null , $permission , false);
          
                break;
            
            case LDAPAuthorizationsList_Manager::PREFIX_AUTH_USER:
                $userPermissionName = UtilsManager::createTableDataLine(array(
                            array(_('User permission') . ' : ' . $permission->dsALN, 0,  $layoutRoleTitle)
                                )
                );
                $table->addDataLine($userPermissionName);
                unset($userPermissionName);
                
                $username = UtilsManager::createTableDataLine(array(
                            array(_('User name') . ' : ' . $permission->getLinkedUsersString(), 0, $layoutUserList)
                                )
                );
                $table->addDataLine($username);
                unset($username);
                $section->aSubSections[] = $this->generatePermissionDetailed(null , $permission , false);
 
                break;
        }
   
        $section->main              = $table;
        $section->isCompactView     = true;
        return $section;
        
    }
    
      /**
     * Create a detailed pdf file of permissions
     * @param array     $aUsers
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createUsersDetailedPdf($aUsers, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle    = _("Users Lists");
        $this->initPdfFile($headerTitle);
        
        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }
        
       // $this->pdf->AddPage();
        foreach($aUsers as $user){
            
            $section= $this->generateUsersDetailedSection($user);
            
             $section->PDFexport($this->pdf, true, true);
                        
             if(($user) !== end($aUsers)) {
               $this->pdf->AddPage();
            }
    
        }
      

  
    }
    
     /**
     * Generate the user detailed list table
     * @param LDAPUser $user
     * @return Table
     */
    private function generateUsersDetailedSection(LDAPUser $user) {

        // init the table       
        $config = ApplicationConfig::getInstance();
        $organization = $config->o;
        $table = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);

        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['leftColumn'] = 20;
        $aColumnsWidths['rightColumn'] = 80;

        $layoutEvenLines = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_ALL);

        //Role title 
        $layoutRoleTitle = clone $this->defaultHeaderLayout;
        $layoutRoleTitle->setAlign(Layout::ALIGN_LEFT);

        //Permission Tile layout
        $layoutPermissionTitle = clone $this->defaultHeaderLayout;
        $layoutPermissionTitle->setAlign(Layout::ALIGN_LEFT);
        $layoutPermissionTitle->setFillColor(new Layout_Color(array(153, 153, 153)));

        //User list layout
        $layoutUserList = clone $this->defaultHorizontalValueLayout;
        $layoutUserList->setAlign(Layout::ALIGN_LEFT);
        $layoutUserList->setBorder(Layout::BORDER_ALL);
        $layoutUserList->setFillColor(new Layout_Color(array(255, 255, 255)));

        //Summary section
        $section = new Section();

        $userName = UtilsManager::createTableDataLine(array(
                    array(_('User name') . ' : ' . $user->sn, 0, $layoutRoleTitle)
                        )
        );

        $table->addDataLine($userName);
        unset($userName);

        $organization = UtilsManager::createTableDataLine(array(
                    array(_('Organization'), $aColumnsWidths['leftColumn'], $this->defaultHorizontalTitleLayout),
                    array($user->getOrganizationName(), $aColumnsWidths['rightColumn'], $this->defaultHorizontalValueLayout)
                        )
        );
        $table->addDataLine($organization);
        unset($organization);
        
         $certificate = UtilsManager::createTableDataLine(array(
                    array(_('Certificate name'), $aColumnsWidths['leftColumn'], $this->defaultHorizontalTitleLayout),
                    array($user->getCertificateName(), $aColumnsWidths['rightColumn'], $this->defaultHorizontalValueLayout)
                        )
        );
        $table->addDataLine($certificate);
        unset($certificate);

        if ($user->mail) {
            $email = UtilsManager::createTableDataLine(array(
                        array(_('Email'), $aColumnsWidths['leftColumn'], $this->defaultHorizontalTitleLayout),
                        array($user->mail, $aColumnsWidths['rightColumn'], $this->defaultHorizontalValueLayout)
                            )
            );
            $table->addDataLine($email);
            unset($email);
        }
        
        $section->main = $table;
        
        //Access list
        $aAuthorizationsAccess = array();
        $aAuthorizationsAccess = $user->getAuthorizationLists(LDAPAuthorizationsList_Manager::TYPE_AUTH_ACCESS);       
                
        foreach($aAuthorizationsAccess as $permissionAccess){

                $accessTable             = $this->generateAccessDetailedSection($permissionAccess,false);
                $section->aSubSections[] = $accessTable;
                unset($accessTable);          
        }      
        $aAuthorizationsRole = $user->getRoles();
   
        foreach ($aAuthorizationsRole as $role) {
            $roleTable = $this->generatePermissionDetailed($role, null, true,true);
            $section->aSubSections[] = $roleTable;
            unset($roleTable);
        }

        $aAuthorizationsUser = $user->getAuthorizationLists(LDAPAuthorizationsList_Manager::TYPE_AUTH_PERMISSION_USER);   
        foreach($aAuthorizationsUser as $permissionUser){

                $userTable             = $this->generatePermissionDetailed(null, $permissionUser , true);
                $section->aSubSections[] = $userTable;
                unset($userTable);
            
        }

        return $section;
    }
    
    
    
    
    /**
     * Initialize the modules accesses default array
     * @param boolean $wInactives   Define if we want inactives modules
     */
    private function initAccessesDefault($wInactives = false)
    {
        if (!$this->aModulesAccessesDefault) {
            $config         = ApplicationConfig::getInstance();
            $aModulesInfos  = $config->modules; 

            $this->aModulesAccessesDefault       = array();
            foreach ($aModulesInfos as $applicationInfos) {
                $this->aModulesAccessesDefault   = array_merge($this->aModulesAccessesDefault, $this->parseModuleAccesses($applicationInfos, '', $wInactives));
            }
        }
    }
    
    /**
     * Parse informations of a module to an array containing default accesses informations (recursive)
     * @param array     $moduleInfos        Module's informations 
     * @param string    $parentPathName     Path name of the parent module (to format the path as parent_child)
     * @param boolean   $wInactives         Define if we want inactives modules
     * @return array 
     */
    private function parseModuleAccesses($moduleInfos, $parentPathName = '', $wInactives = false)
    {
        $aModulesAccesses       = array();
        
        if ($moduleInfos['type'] !== 'inactive' OR $wInactives) {
            $pathName                                       = ($parentPathName !== '')?$parentPathName.'_'.$moduleInfos['pathName']:$moduleInfos['pathName'];

            $aModulesAccesses[$pathName]                    = array();
            $aModulesAccesses[$pathName]['moduleName']      = $moduleInfos['namePlural'];
            $aModulesAccesses[$pathName]['access']          = false;
            if ($moduleInfos['type'] === 'edit') {
                $aModulesAccesses[$pathName]['create']      = false;
                $aModulesAccesses[$pathName]['edit']        = false;
                $aModulesAccesses[$pathName]['erase']       = false;
                $aModulesAccesses[$pathName]['print']       = false;
            } else {
                $aModulesAccesses[$pathName]['create']      = null;
                $aModulesAccesses[$pathName]['edit']        = null;
                $aModulesAccesses[$pathName]['erase']       = null;
                if ($moduleInfos['type'] === 'list') {
                    $aModulesAccesses[$pathName]['print']   = false;
                } else {
                    $aModulesAccesses[$pathName]['print']   = null;
                }
            }

            $aModulesAccesses[$pathName]['isParent']        = ($parentPathName !== '')?false:true;
            if (isset($moduleInfos['sections']) AND is_array($moduleInfos['sections'])) {
                foreach ($moduleInfos['sections'] as $subModuleInfos) {
                    $aModulesAccesses                       = array_merge($aModulesAccesses, $this->parseModuleAccesses($subModuleInfos, $pathName));
                }
                
                if (count($moduleInfos['sections'])) {
                    $aModulesAccesses[$pathName]['isParent'] = true;
                }
            }
        }
        
        return $aModulesAccesses;
    }
    

    /**
     * Create a pdf file of permissions
     * @param array     $aPermissions
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createPermissionsPdf($aPermissions, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle    = _("Permissions");
        $this->initPdfFile($headerTitle);

        if (count($aFilters)) {
            $filtersLine = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }

        $permissionsTable = $this->generatePermissionsTable($aPermissions);
        $permissionsTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aPermissions));
        $nbRowsTable->PDFexport($this->pdf, true, true);
    }

    /**
     * Generate the permissions list table
     * @param type $aPermissions
     * @return Table
     */
    private function generatePermissionsTable($aPermissions)
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);

        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['type']    = 15;
        $aColumnsWidths['name']    = 15;
        $aColumnsWidths['module']  = 10;
        $aColumnsWidths['section'] = 10;
        $aColumnsWidths['role']    = 20;
        $aColumnsWidths['users']   = 30;

        // header of the section
        $header = UtilsManager::createTableHeader(
            array (
                array(_('Type'),    $aColumnsWidths['type']),
                array(_('Name'),    $aColumnsWidths['name']),
                array(_('Module'),  $aColumnsWidths['module']),
                array(_('Section'), $aColumnsWidths['section']),
                array(_('Role'),    $aColumnsWidths['role']),
                array(_('Users'),   $aColumnsWidths['users'])
            )
        );
        $table->setHeader($header);
        unset($header);

        $layoutEvenLines = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_LEFT.Layout::BORDER_RIGHT);
        $layoutOddLines = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235,230,225)));
        $i = 0;
        foreach ($aPermissions as $permission) {
            $layout = $layoutEvenLines;
            if ($i%2 != 0) {
                $layout = $layoutOddLines;
            }

            $type = explode('_', $permission->dsALN, 2);
            if ($type[0] == 'D') {
                $type = _('Access List Permission');
            } elseif ($type[0] == 'DU') {
                $type = _('User Permission');
            } elseif ($type[0] == 'DR') {
                $type = _('Role Permission');
            } else {
                $type = '';
            }
            $module = explode('_', $permission->dsMN);

            // access list informations
            $permissionLine = UtilsManager::createTableDataLine(
                array (
                    array(trim($type), $aColumnsWidths['type'], $layout),
                    array(trim($permission->dsALN), $aColumnsWidths['name'], $layout),
                    array(trim($module[0]), $aColumnsWidths['module'], $layout),
                    array(trim(isset($module[1])?$module[1]:''), $aColumnsWidths['section'], $layout),
                    array(trim($permission->getRolesNamesString()), $aColumnsWidths['role'], $layout),
                    array(trim($permission->getLinkedUsersString()), $aColumnsWidths['users'], $layout),
                )
            );
            $table->addDataLine($permissionLine);
            unset($permissionLine);

            $i++;
        }

        return $table;
    }
}

?>