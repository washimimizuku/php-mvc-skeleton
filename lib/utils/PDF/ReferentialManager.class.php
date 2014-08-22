<?php
/**
 * A class manager for referential pdfs
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class PDF_ReferentialManager extends PDF_Manager {

    /**
     * Create a pdf file of companies
     * @param array     $aCompanies
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createCompaniesPdf($aCompanies, $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle    = _("Companies");
        $this->initPdfFile($headerTitle);

        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }

        $companiesTable = $this->generateCompaniesTable($aCompanies);
        $companiesTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aCompanies));
        $nbRowsTable->PDFexport($this->pdf, true, true);
    }

    /**
     * Generate the company list table
     * @param type $aCompanies
     * @return Table
     */
    private function generateCompaniesTable($aCompanies)
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);

        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['socodsoc']     = 15;
        $aColumnsWidths['sonomsoc']     = 40;
        $aColumnsWidths['sovillesoc']   = 25;
        $aColumnsWidths['sodivsoc']     = 15;
        $aColumnsWidths['soisopays']    = 5;

        // header of the section
        $header                 = UtilsManager::createTableHeader(array (
                                                                    array(_('Code'),                        $aColumnsWidths['socodsoc']),
                                                                    array(_('Name'),                        $aColumnsWidths['sonomsoc']),
                                                                    array(_('City'),                        $aColumnsWidths['sovillesoc']),
                                                                    array(_('Division'),                    $aColumnsWidths['sodivsoc']),
                                                                    array(_('Country'),                     $aColumnsWidths['soisopays'])
                                                                    )
                                                             );
        $table->setHeader($header);
        unset($header);

        $layoutEvenLines    = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_LEFT.Layout::BORDER_RIGHT);
        $layoutOddLines     = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235,230,225)));
        $i                  = 0;
        foreach ($aCompanies as $company) {
            $layout             = $layoutEvenLines;
            if ($i%2 != 0) {
                $layout         = $layoutOddLines;
            }

            // company informations
            $companyLine        = UtilsManager::createTableDataLine(array (
                                                                        array(trim($company->socodsoc),     $aColumnsWidths['socodsoc'], $layout),
                                                                        array(trim($company->sonomsoc),     $aColumnsWidths['sonomsoc'], $layout),
                                                                        array(trim($company->sovillesoc),   $aColumnsWidths['sovillesoc'], $layout),
                                                                        array(trim($company->sodivsoc),     $aColumnsWidths['sodivsoc'], $layout),
                                                                        array($company->soisopays,          $aColumnsWidths['soisopays'], $layout)
                                                                        )
                                                                );
            $table->addDataLine($companyLine);
            unset($companyLine);

            $i++;
        }

        return $table;
    }

    /**
     * Create a pdf file of accounts
     * @param array     $aAccounts
     * @param array     $aFilters
     * @param string    $locale
     * return void
     */
    public function createAccountsPdf($aAccounts = array(), $aFilters = array(), $locale = 'fr_FR')
    {
        $this->setLanguage($locale);

        // init pdf file
        $headerTitle    = _("Accounts");
        $this->initPdfFile($headerTitle);

        if (count($aFilters)) {
            $filtersLine    = $this->generateFiltersTable($aFilters);
            $filtersLine->PDFexport($this->pdf);
        }

        $accountsTable  = $this->generateAccountsTable($aAccounts);
        $accountsTable->PDFexport($this->pdf, true, true);
        
        $nbRowsTable            = $this->generateNumberOfRowsTable(count($aAccounts));
        $nbRowsTable->PDFexport($this->pdf, true, true);
    }

    /**
     * Generate the account list table
     * @param type $aCompanies
     * @return Table
     */
    private function generateAccountsTable($aAccounts)
    {
        // init the table
        $table  = new Table();
        $table->setDefaultLayout($this->defaultLayout);
        $table->setDefaultHeaderLayout($this->defaultHeaderLayout);
        $table->setDefaultLineHeight(self::TABLE_LINE_HEIGHT_DEFAULT);

        // columns widths
        $aColumnsWidths = array();
        $aColumnsWidths['sonomsoc']     = 25;
        $aColumnsWidths['sbbic']        = 10;
        $aColumnsWidths['sbcptiban']    = 15;
        $aColumnsWidths['sbcptbban']    = 14;
        $aColumnsWidths['sbdscpt']      = 30;
        $aColumnsWidths['sbmonnaie']    = 6;

        // header of the section
        $header                 = UtilsManager::createTableHeader(array (
                                                                    array(_('Company'),                         $aColumnsWidths['sonomsoc']),
                                                                    array(_('BIC'),                             $aColumnsWidths['sbbic']),
                                                                    array(_('IBAN'),                            $aColumnsWidths['sbcptiban']),
                                                                    array(_('BBAN'),                            $aColumnsWidths['sbcptbban']),
                                                                    array(_('Description'),                     $aColumnsWidths['sbdscpt']),
                                                                    array(_('Currency'),                        $aColumnsWidths['sbmonnaie']),
                                                                    )
                                                             );
        $table->setHeader($header);
        unset($header);

        $layoutEvenLines    = clone $this->defaultHorizontalValueLayout;
        $layoutEvenLines->setBorder(Layout::BORDER_LEFT.Layout::BORDER_RIGHT);
        $layoutOddLines     = clone $layoutEvenLines;
        $layoutOddLines->setFillColor(new Layout_Color(array(235,230,225)));
        $i                  = 0;
        foreach ($aAccounts as $account) {
            $layout             = $layoutEvenLines;
            if ($i%2 != 0) {
                $layout         = $layoutOddLines;
            }

            // company informations
            $accountLine        = UtilsManager::createTableDataLine(array (
                                                                        array(trim($account->getCompany()->sonomsoc).'('.trim($account->sbsoc).')', $aColumnsWidths['sonomsoc'], $layout),
                                                                        array(trim($account->sbbic),                                                $aColumnsWidths['sbbic'], $layout),
                                                                        array(trim($account->sbcptiban),                                            $aColumnsWidths['sbcptiban'], $layout),
                                                                        array(trim($account->sbcptbban),                                            $aColumnsWidths['sbcptbban'], $layout),
                                                                        array(trim($account->sbdscpt),                                              $aColumnsWidths['sbdscpt'], $layout),
                                                                        array(trim($account->sbmonnaie),                                            $aColumnsWidths['sbmonnaie'], $layout),
                                                                        )
                                                                );
            $table->addDataLine($accountLine);
            unset($accountLine);

            $i++;
        }

        return $table;
    }


}

?>
